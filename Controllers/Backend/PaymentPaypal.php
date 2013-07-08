<?php
/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Plugin
 * @copyright  Copyright (c) 2012, shopware AG (http://www.shopware.de)
 * @version    $Id$
 * @author     Heiner Lohaus
 * @author     $Author$
 */

/**
 * Billsafe payment controller
 *
 * todo@all: Documentation
 */
class Shopware_Controllers_Backend_PaymentPaypal extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * List payments action.
     *
     * Outputs the payment data as json list.
     */
    public function getListAction()
    {
        $limit = $this->Request()->getParam('limit', 20);
        $start = $this->Request()->getParam('start', 0);
        $subShopFilter = $this->Request()->getParam('filter', false);

        if ($subShopFilter && !empty($subShopFilter)) {
            $subShopFilter = array_pop($subShopFilter);
            if ($subShopFilter['property'] == 'shopId') {
                $subShopFilter = (int) $subShopFilter['value'];
            }
        }

        if ($sort = $this->Request()->getParam('sort')) {
            //$sort = Zend_Json::decode($sort);
            $sort = current($sort);
        }
        $direction = empty($sort['direction']) || $sort['direction'] == 'DESC' ? 'DESC' : 'ASC';
        $property = empty($sort['property']) ? 'orderDate' : $sort['property'];

        if ($filter = $this->Request()->getParam('filter')) {
            foreach ($filter as $value) {
                if (empty($value['property']) || empty($value['value'])) {
                    continue;
                }
                if ($value['property'] == 'search') {
                    $this->Request()->setParam('search', $value['value']);
                }
            }
        }

        $select = Shopware()->Db()
            ->select()
            ->from(array('o' => 's_order'), array(
            new Zend_Db_Expr('SQL_CALC_FOUND_ROWS o.id'),
            'clearedId' => 'cleared',
            'statusId' => 'status',
            'amount' => 'invoice_amount', 'currency',
            'orderDate' => 'ordertime', 'orderNumber' => 'ordernumber', 'shopId' => 'subshopID',
            'transactionId',
            'comment' => 'customercomment',
            'clearedDate' => 'cleareddate',
            'trackingId' => 'trackingcode',
            'customerId' => 'u.userID',
            'invoiceId' => new Zend_Db_Expr('(' . Shopware()->Db()
                ->select()
                ->from(array('s_order_documents'), array('ID'))
                ->where('orderID=o.id')
                ->order('ID DESC')
                ->limit(1) . ')'),
            'invoiceHash' => new Zend_Db_Expr('(' . Shopware()->Db()
                ->select()
                ->from(array('s_order_documents'), array('hash'))
                ->where('orderID=o.id')
                ->order('ID DESC')
                ->limit(1) . ')')
        ))
            ->joinLeft(
                array('shops' => 's_core_shops'),
                'shops.id =  o.subshopID',
                array(
                    'shopName' => 'shops.name'
                )
        )
            ->join(
            array('p' => 's_core_paymentmeans'),
            'p.id =  o.paymentID',
            array(
                'paymentDescription' => 'p.description'
            )
        )
            ->joinLeft(
            array('so' => 's_core_states'),
            'so.id =  o.status',
            array(
                'statusDescription' => 'so.description'
            )
        )
            ->joinLeft(
            array('sc' => 's_core_states'),
            'sc.id =  o.cleared',
            array(
                'clearedDescription' => 'sc.description'
            )
        )
            ->joinLeft(
            array('u' => 's_user_billingaddress'),
            'u.userID = o.userID',
            array()
        )
            ->joinLeft(
            array('b' => 's_order_billingaddress'),
            'b.orderID = o.id',
            new Zend_Db_Expr("
					IF(b.id IS NULL,
						IF(u.company='', CONCAT(u.firstname, ' ', u.lastname), u.company),
						IF(b.company='', CONCAT(b.firstname, ' ', b.lastname), b.company)
					) as customer
				")
        )
            ->joinLeft(
            array('d' => 's_premium_dispatch'),
            'd.id = o.dispatchID',
            array(
                'dispatchDescription' => 'd.name'
            )
        )
            ->where('p.name LIKE ?', 'paypal')
            ->where('o.status >= 0')
            ->order(array($property . ' ' . $direction))
            ->limit($limit, $start);

        if ($search = $this->Request()->getParam('search')) {
            $search = trim($search);
            $search = '%' . $search . '%';
            $search = Shopware()->Db()->quote($search);

            $select->where('o.transactionID LIKE ' . $search
                . ' OR o.ordernumber LIKE ' . $search
                . ' OR b.lastname LIKE ' . $search
                . ' OR u.lastname LIKE ' . $search
                . ' OR b.company LIKE ' . $search
                . ' OR u.company LIKE ' . $search);

        }

        if($subShopFilter) {
            $select->where('o.subshopID = ' .  $subShopFilter);
        }
        $rows = Shopware()->Db()->fetchAll($select);
        $total = Shopware()->Db()->fetchOne('SELECT FOUND_ROWS()');

        foreach ($rows as &$row) {
            if ($row['clearedDate'] == '0000-00-00 00:00:00') {
                $row['clearedDate'] = null;
            }
            if (isset($row['clearedDate'])) {
                $row['clearedDate'] = new DateTime($row['clearedDate']);
            }
            $row['orderDate'] = new DateTime($row['orderDate']);
            $row['amountFormat'] = Shopware()->Currency()->toCurrency($row['amount'], array('currency' => $row['currency']));
        }

        $this->View()->assign(array('data' => $rows, 'total' => $total, 'success' => true));
    }

    /**
     * Helper which registers a shop in order to use the PayPal API client within the context of the given shop
     *
     * @param $shopId
     * @throws Exception
     */
    public function registerShopByShopId($shopId)
    {
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');

        if (empty($shopId)) {
            $shop = $repository->getActiveDefault();
            return $shop->registerResources(Shopware()->Bootstrap());

        }

        $shop = $repository->getActiveById($shopId);
        if (!$shop) {
            throw new \Exception("Shop {$shopId} not found");

        }
        $shop->registerResources(Shopware()->Bootstrap());
    }

    /**
     * Will register the correct shop for a given transactionId.
     *
     * @param $transactionId
     */
    public function registerShopByTransactionId($transactionId)
    {
        // Query shopId and api-user if available
        $sql = '
            SELECT s_order.`subshopID`
            FROM s_order
            LEFT JOIN s_order_attributes
              ON s_order_attributes.orderID = s_order.id
            WHERE s_order.transactionID = ?
        ';
        $result = Shopware()->Db()->fetchOne($sql, array($transactionId));

        if (!empty($result)) {
            $this->registerShopByShopId($result);
        }
    }

    /**
     * Get paypal account balance
     */
    public function getBalanceAction()
    {
        $shopId = (int) $this->Request()->getParam('shopId', null);
        $this->registerShopByShopId($shopId);

        $client = $this->Plugin()->Client();
        $balance = $client->getBalance(array(
            'RETURNALLCURRENCIES' => 0
        ));

        if ($balance['ACK'] == 'Success') {
            $rows = array();
            for ($i = 0; isset($balance['L_AMT' . $i]); $i++) {
                $data = array(
                    'default' => $i == 0,
                    'balance' => $balance['L_AMT' . $i],
                    'currency' => $balance['L_CURRENCYCODE' . $i]
                );
                $data['balanceFormat'] = Shopware()->Currency()->toCurrency(
                    $data['balance'], array('currency' => $data['currency'])
                );
                $rows[] = $data;
            }
            $this->View()->assign(array('success' => true, 'data' => $rows));
        } else {
            $error = sprintf("An error occured: %s: %s - %s", $balance['L_ERRORCODE0'], $balance['L_SHORTMESSAGE0'], $balance['L_LONGMESSAGE0']);
            $this->View()->assign(array('success' => false, 'error' => $error, 'errorCode' => $balance['L_ERRORCODE0']));
        }
    }

    /**
     * Get payment details
     */
    public function getDetailsAction()
    {
        $filter = $this->Request()->getParam('filter');
        if (isset($filter[0]['property']) && $filter[0]['property'] == 'transactionId') {
            $this->Request()->setParam('transactionId', $filter[0]['value']);
        }
        $transactionId = $this->Request()->getParam('transactionId');

        // Load the correct shop in order to use the correct api credentials
        $this->registerShopByTransactionId($transactionId);


        $client = $this->Plugin()->Client();
        $details = $client->getTransactionDetails(array(
            'TRANSACTIONID' => $transactionId
        ));
        if (empty($details)) {
            $this->View()->assign(array('success' => false, 'message' => 'No details found for this transaction'));
            return;
        }

        if ($details['ACK'] != 'Success') {
            $error = sprintf("An error occured: %s: %s - %s", $details['L_ERRORCODE0'], $details['L_SHORTMESSAGE0'], $details['L_LONGMESSAGE0']);
            $this->View()->assign(array('success' => false, 'message' => $error, 'errorCode' => $details['L_ERRORCODE0']));
            return;
        }


        $row = array(
            'accountEmail' => $details['EMAIL'],
            'accountName' =>
            (isset($details['PAYERBUSINESS']) ? $details['PAYERBUSINESS'] . ' - ' : '') .
                $details['FIRSTNAME'] . ' ' . $details['LASTNAME'] .
                ' (' . $details['COUNTRYCODE'] . ')',
            'accountStatus' => $details['PAYERSTATUS'],
            'accountCountry' => $details['COUNTRYCODE'],

            'addressStatus' => $details['ADDRESSSTATUS'],
            'addressName' => $details['SHIPTONAME'],
            'addressStreet' => $details['SHIPTOSTREET'] . ' ' . $details['SHIPTOSTREET2'],
            'addressCity' => $details['SHIPTOSTATE'] . ' ' . $details['SHIPTOZIP'] . ' ' . $details['SHIPTOCITY'],
            'addressCountry' => $details['SHIPTOCOUNTRYCODE'],
            'addressPhone' => $details['SHIPTOPHONENUM'],

            'protectionStatus' => $details['PROTECTIONELIGIBILITY'], //Eligible, ItemNotReceivedEligible, UnauthorizedPaymentEligible, Ineligible
            'paymentStatus' => $details['PAYMENTSTATUS'],
            'pendingReason' => $details['PENDINGREASON'],
            'paymentDate' => new DateTime($details['ORDERTIME']),
            'paymentType' => $details['PAYMENTTYPE'], //none, echeck, instant
            'paymentAmount' => $details['AMT'],
            'paymentCurrency' => $details['CURRENCYCODE'],

            'transactionId' => $details['TRANSACTIONID'],
            //'orderNumber' => $details['INVNUM'],
        );
        $sql = 'SELECT `countryname` FROM `s_core_countries` WHERE `countryiso` LIKE ?';
        $row['addressCountry'] = Shopware()->Db()->fetchOne($sql, array($row['addressCountry']));
        $row['paymentAmountFormat'] = Shopware()->Currency()->toCurrency(
            $row['paymentAmount'], array('currency' => $row['paymentCurrency'])
        );

        $transactionsData = $client->TransactionSearch(array(
            'STARTDATE' => $details['ORDERTIME'],
            'TRANSACTIONID' => $transactionId
            //'INVNUM' => $details['INVNUM']
        ));

        if ($transactionsData['ACK'] != 'Success') {
            $error = sprintf("An error occured: %s: %s - %s", $transactionsData['L_ERRORCODE0'], $transactionsData['L_SHORTMESSAGE0'], $transactionsData['L_LONGMESSAGE0']);
            $this->View()->assign(array('success' => false, 'message' => $error, 'errorCode' => $transactionsData['L_ERRORCODE0']));
            return;
        }

        $row['transactions'] = array();
        for ($i = 0; isset($transactionsData['L_AMT' . $i]); $i++) {
            $transaction = array(
                'id' => $transactionsData['L_TRANSACTIONID' . $i],
                'date' => new DateTime($transactionsData['L_TIMESTAMP' . $i]),
                'name' => $transactionsData['L_NAME' . $i],
                'email' => $transactionsData['L_EMAIL' . $i],
                'type' => $transactionsData['L_TYPE' . $i],
                'status' => $transactionsData['L_STATUS' . $i],
                'amount' => $transactionsData['L_AMT' . $i],
                'currency' => $transactionsData['L_CURRENCYCODE' . $i],
            );
            $transaction['amountFormat'] = Shopware()->Currency()->toCurrency(
                $transaction['amount'], array('currency' => $transaction['currency'])
            );
            $row['transactions'][] = $transaction;
        }

        $this->View()->assign(array('success' => true, 'data' => array($row)));
    }


    /**
     * Do payment action
     */
    public function doActionAction()
    {
        $transactionId = $this->Request()->getParam('transactionId');

        // Load the correct shop in order to use the correct api credentials
        $this->registerShopByTransactionId($transactionId);

        $config = Shopware()->Config();

        $client = $this->Plugin()->Client();

        $action = $this->Request()->getParam('paymentAction');
        $amount = $this->Request()->getParam('paymentAmount');
        $amount = str_replace(',', '.', $amount);
        $currency = $this->Request()->getParam('paymentCurrency');
        $orderNumber = $this->Request()->getParam('orderNumber');
        $full = $this->Request()->getParam('paymentFull') === 'true';
        $last = $this->Request()->getParam('paymentLast') === 'true';
        $note = $this->Request()->getParam('note');

        $invoiceId = null;
        if ($config->get('paypalSendInvoiceId') === true) {
            $prefix = $config->get('paypalPrefixInvoiceId');
            if (!empty($prefix)) {
                // Set prefixed invoice id - Remove special chars and spaces
                $prefix = str_replace(' ', '', $prefix);
                $prefix = preg_replace('/[^A-Za-z0-9\-]/', '', $prefix);
                $invoiceId = $prefix.$orderNumber;
            } else {
                $invoiceId = $orderNumber;
            }
        }


        try {
            switch ($action) {
                case 'refund':
                    $data = array(
                        'TRANSACTIONID' => $transactionId,
                        'REFUNDTYPE' => $full ? 'Full' : 'Partial',
                        'AMT' => $full ? '' : $amount,
                        'CURRENCYCODE' => $full ? '' : $currency,
                        'NOTE' => $note
                    );
                    if ($invoiceId) {
                        $data['INVOICEID'] = $invoiceId;
                    }
                    $result = $client->RefundTransaction($data);
                    break;
                case 'auth':
                    $result = $client->doReAuthorization(array(
                        'AUTHORIZATIONID' => $transactionId,
                        'AMT' => $amount,
                        'CURRENCYCODE' => $currency
                    ));
                    break;
                case 'capture':
                    $data = array(
                        'AUTHORIZATIONID' => $transactionId,
                        'AMT' => $amount,
                        'CURRENCYCODE' => $currency,
                        'COMPLETETYPE' => $last ? 'Complete' : 'NotComplete',
                        'NOTE' => $note
                    );
                    if ($invoiceId) {
                        $data['INVOICEID'] = $invoiceId;
                    }
                    $result = $client->doCapture($data);
                    break;
                case 'void':
                    $result = $client->doVoid(array(
                        'AUTHORIZATIONID' => $transactionId,
                        'NOTE' => $note
                    ));
                    break;
                case 'book':
                    $result = $client->doAuthorization(array(
                        'TRANSACTIONID' => $transactionId,
                        'AMT' => $amount,
                        'CURRENCYCODE' => $currency
                    ));
                    if ($result['ACK'] == 'Success') {
                        $data = array(
                            'AUTHORIZATIONID' => $result['TRANSACTIONID'],
                            'AMT' => $amount,
                            'CURRENCYCODE' => $currency,
                            'COMPLETETYPE' => $last ? 'Complete' : 'NotComplete',
                            'NOTE' => $note
                        );
                        if ($invoiceId) {
                            $data['INVOICEID'] = $invoiceId;
                        }
                        $result = $client->doCapture($data);
                    }
                    break;
                default:
                    return;
            }

            if ($result['ACK'] != 'Success') {
                throw new Exception(
                    '[' . $result['L_SEVERITYCODE0'] . '] ' .
                        $result['L_SHORTMESSAGE0'] . " " . $result['L_LONGMESSAGE0'] . "<br>\n"
                );
            }

            // Switch transaction id
            if ($action !== 'book' && (isset($result['TRANSACTIONID']) || isset($result['AUTHORIZATIONID']))) {
                $sql = '
                    UPDATE s_order SET transactionID=?
                    WHERE transactionID=? LIMIT 1
                ';
                Shopware()->Db()->query($sql, array(
                    isset($result['TRANSACTIONID']) ? $result['TRANSACTIONID'] : $result['AUTHORIZATIONID'],
                    $transactionId
                ));
                $transactionId = $result['TRANSACTIONID'];
            }

            if ($action == 'void') {
                $paymentStatus = 'Voided';
            } elseif ($action == 'refund') {
                $paymentStatus = 'Refunded';
            } elseif (isset($result['PAYMENTSTATUS'])) {
                $paymentStatus = $result['PAYMENTSTATUS'];
            }
            if (isset($paymentStatus)) {
                try {
                    $this->Plugin()->setPaymentStatus($transactionId, $paymentStatus, $note);
                } catch (Exception $e) {
                    $result['SW_STATUS_ERROR'] = $e->getMessage();
                }
            }
            $this->View()->assign(array('success' => true, 'result' => $result));
        } catch (Exception $e) {
            $this->View()->assign(array('message' => $e->getMessage(), 'success' => false));
        }
    }

    /**
     * Returns the payment plugin config data.
     *
     * @return Shopware_Plugins_Frontend_SwagPaymentPaypal_Bootstrap
     */
    public function Plugin()
    {
        return Shopware()->Plugins()->Frontend()->SwagPaymentPaypal();
    }
}