<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Shopware_Plugins_Frontend_SwagPaymentPaypal_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * Installs the plugin
     *
     * Creates and subscribe the events and hooks
     * Creates and save the payment row
     * Creates the payment table
     * Creates payment menu item
     *
     * @return bool
     */
    public function install()
    {
        $this->createMyEvents();
        $this->createMyPayment();
        $this->createMyMenu();
        $this->createMyForm();
        $this->createMyTranslations();
        $this->fixOrderMail();
        $this->createMyAttributes();
        return true;
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        try {
            $this->Application()->Models()->removeAttribute(
                's_order_attributes',
                'swag_payal',
                'billing_agreement_id'
            );
            $this->Application()->Models()->removeAttribute(
                's_order_attributes',
                'swag_payal',
                'express'
            );
            $this->Application()->Models()->generateAttributeModels(array(
                's_order_attributes'
            ));
        } catch (Exception $e) {
        }

        return true;
    }

    /**
     * @param string $version
     * @return bool
     */
    public function update($version)
    {
        if (strpos($version, '2.0.') === 0) {
            try {
                $this->Application()->Models()->removeAttribute(
                    's_user_attributes',
                    'swag_payal',
                    'billing_agreement_id'
                );
            } catch (Exception $e) {
            }
            try {
                $this->Application()->Models()->addAttribute(
                    's_order_attributes', 'swag_payal',
                    'billing_agreement_id', 'VARCHAR(255)'
                );
            } catch (Exception $e) {
            }

            $this->Application()->Models()->generateAttributeModels(array(
                's_order_attributes', 's_user_attributes'
            ));
            $this->Form()->removeElement('paypalAllowGuestCheckout');
        }
        if (version_compare($version, '2.1.5', '<=')) {
            $this->fixOrderMail();
        }
        if (version_compare($version, '2.1.6', '<=')) {
            $this->createMyAttributes();
        }
        if (version_compare($version, '3.0.0', '<=')) {
            $this->Form()->removeElement('paypalPaymentActionPending');
            $this->fixPluginDescription();
        }

        //Update form
        $this->createMyForm();
        $this->createMyEvents();
        return true;
    }

    protected function fixOrderMail()
    {
        // Make sure, that the additionadescription field is evaluated by smarty
        $sql = <<<'EOD'
                UPDATE  `s_core_config_mails`
                SET
                    content=REPLACE(content, '{$additional.payment.additionaldescription}', '{include file="string:`$additional.payment.additionaldescription`"}'),
                    contentHTML=REPLACE(contentHTML, '{$additional.payment.additionaldescription}', '{include file="string:`$additional.payment.additionaldescription`"}')
                WHERE name='sORDER';
EOD;
        Shopware()->Db()->query($sql);
    }

    protected function fixPluginDescription()
    {
        if ($this->Payment() === null) {
            return;
        }
        $newLogo = '<!-- PayPal Logo -->' .
            '<a onclick="window.open(this.href, \'olcwhatispaypal\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=400, height=500\'); return false;"' .
            '    href="https://www.paypal.com/de/cgi-bin/webscr?cmd=xpt/cps/popup/OLCWhatIsPayPal-outside" target="_blank">' .
            '<img src="{link file="engine/Shopware/Plugins/Default/Frontend/SwagPaymentPaypal/Views/frontend/_resources/images/paypal_logo.png" "fullPath"}" alt="Logo \'PayPal empfohlen\'">' .
            '</a>' . '<!-- PayPal Logo -->';
        $description = $this->Payment()->getAdditionalDescription();
        $description = preg_replace('#<!-- PayPal Logo -->.+<!-- PayPal Logo -->#msi', $newLogo, $description);
        $description = str_replace('<p>PayPal. <em>Sicherererer.</em></p>', '<br><br>', $description);
        $this->Payment()->setAdditionalDescription($description);
    }

    /**
     * Fetches and returns paypal payment row instance.
     *
     * @return \Shopware\Models\Payment\Payment
     */
    public function Payment()
    {
        return $this->Payments()->findOneBy(
            array('name' => 'paypal')
        );
    }

    /**
     * @return \Shopware_Components_Paypal_Client
     */
    public function Client()
    {
        return $this->Application()->PaypalClient();
    }

    /**
     * Activate the plugin paypal plugin.
     * Sets the active flag in the payment row.
     *
     * @return bool
     */
    public function enable()
    {
        $payment = $this->Payment();
        if ($payment !== null) {
            $payment->setActive(true);
        }
        return true;
    }

    /**
     * Disable plugin method and sets the active flag in the payment row
     *
     * @return bool
     */
    public function disable()
    {
        $payment = $this->Payment();
        if ($payment !== null) {
            $payment->setActive(false);
        }
        return true;
    }

    /**
     * Creates and subscribe the events and hooks.
     */
    protected function createMyEvents()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentPaypal',
            'onGetControllerPathFrontend'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_PaymentPaypal',
            'onGetControllerPathBackend'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch',
            'onPostDispatch',
            110
        );

        $this->subscribeEvent(
            'Enlight_Bootstrap_InitResource_PaypalClient',
            'onInitResourcePaypalClient'
        );
    }

    /**
     * Creates and save the payment row.
     */
    protected function createMyPayment()
    {
        $this->createPayment(array(
            'name' => 'paypal',
            'description' => 'PayPal',
            'action' => 'payment_paypal',
            'active' => 0,
            'position' => 0,
            'additionalDescription' => '<!-- PayPal Logo -->' .
                '<a onclick="window.open(this.href, \'olcwhatispaypal\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=400, height=500\'); return false;"' .
                '    href="https://www.paypal.com/de/cgi-bin/webscr?cmd=xpt/cps/popup/OLCWhatIsPayPal-outside" target="_blank">' .
                '<img src="{link file="engine/Shopware/Plugins/Default/Frontend/SwagPaymentPaypal/Views/frontend/_resources/images/paypal_logo.png" "fullPath"}" alt="Logo \'PayPal empfohlen\'">' .
                '</a>' . '<!-- PayPal Logo -->' .
                'Bezahlung per PayPal - einfach, schnell und sicher.'
        ));
    }

    /**
     * Creates and stores a payment item.
     */
    protected function createMyMenu()
    {
        $parent = $this->Menu()->findOneBy(array('label' => 'Zahlungen'));
        $this->createMenuItem(array(
            'label' => 'PayPal',
            'controller' => 'PaymentPaypal',
            'action' => 'Index',
            'class' => 'ico2 date2',
            'active' => 1,
            'parent' => $parent
        ));
    }

    /**
     * Creates and stores the payment config form.
     */
    protected function createMyForm()
    {
        $form = $this->Form();

        // API settings
        $form->setElement('text', 'paypalUsername', array(
            'label' => 'API-Benutzername',
            'required' => true,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('text', 'paypalPassword', array(
            'label' => 'API-Passwort',
            'required' => true,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('text', 'paypalSignature', array(
            'label' => 'API-Unterschrift',
            'required' => true,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP

        ));
        $form->setElement('text', 'paypalVersion', array(
            'label' => 'API-Version',
            'value' => '113.0',
            'required' => true,
            'readOnly' => true,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('button', 'paypalButtonApi', array(
            'label' => '<strong>Jetzt API-Signatur erhalten</strong>',
            'handler' => "function(btn) {
                var sandbox = btn.up('panel').down('[elementName=paypalSandbox]').getValue();
                if(sandbox) {
                    var link = 'https://www.sandbox.paypal.com/';
                } else {
                    var link = 'https://www.paypal.com/';
                }
                link += 'de/cgi-bin/webscr?cmd=_get-api-signature&generic-flow=true';
                window.open(link, '', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=400, height=540');
            }"
        ));
        $form->setElement('text', 'paypalClientId', array(
            'label' => 'REST-API Client ID',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('text', 'paypalSecret', array(
            'label' => 'REST-API Secret',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('button', 'paypalButtonRestApi', array(
            'label' => '<strong>Jetzt Daten für REST-API erhalten</strong>',
            'handler' => "function(btn) {
                var link = document.location.pathname + 'paymentPaypal/downloadRestDocument';
                window.open(link, '');
            }"
        ));

        $form->setElement('boolean', 'paypalSandbox', array(
            'label' => 'Sandbox-Modus aktivieren',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('number', 'paypalTimeout', array(
            'label' => 'API-Timeout in Sekunden',
            'value' => 5,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('boolean', 'paypalErrorMode', array(
            'label' => 'Fehlermeldungen ausgeben',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        // Payment page settings
        $form->setElement('text', 'paypalBrandName', array(
            'label' => 'Alternativer Shop-Name auf der PayPal-Seite',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('media', 'paypalLogoImage', array(
            'label' => 'Shop-Logo auf der PayPal-Seite',
            'value' => 'frontend/_resources/images/logo.jpg',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('media', 'paypalHeaderImage', array(
            'label' => 'Header-Logo auf der PayPal-Seite',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('color', 'paypalCartBorderColor', array(
            'label' => 'Farbe des Warenkorbs auf der PayPal-Seite',
            'value' => '#E1540F',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        // Frontend settings
        $form->setElement('boolean', 'paypalFrontendLogo', array(
            'label' => 'Payment-Logo im Frontend ausgeben',
            'value' => true,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('text', 'paypalFrontendLogoBlock', array(
            'label' => 'Template-Block für das Payment-Logo',
            'value' => 'frontend_index_left_campaigns_bottom',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));

        // Payment settings
        $form->setElement('select', 'paypalPaymentAction', array(
            'label' => 'Zahlungsabschluss',
            'value' => 'Sale',
            'store' => array(
                array('Sale', 'Zahlung sofort abschließen (Sale)'),
                array('Authorization', 'Zeitverzögerter Zahlungseinzug (Auth-Capture)'),
                array('Order', 'Zeitverzögerter Zahlungseinzug (Order-Auth-Capture)')
            ),
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('boolean', 'paypalBillingAgreement', array(
            'label' => 'Zahlungsvereinbarung treffen / „Sofort-Kaufen“ aktivieren',
            'description' => 'Achtung: Diese Funktion muss erst für Ihren PayPal-Account von PayPal aktiviert werden.',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('boolean', 'paypalLogIn', array(
            'label' => '„Login mit PayPal“ aktivieren',
            'description' => 'Achtung: Für diese Funktion müssen Sie erst die Daten für die REST-API hinterlegen.',
            'value' => false,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('boolean', 'paypalFinishRegister', array(
            'label' => 'Nach dem ersten „Login mit PayPal“ auf die Registrierung umleiten',
            'value' => true,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('boolean', 'paypalSeamlessCheckout', array(
            'label' => '„Seamless Checkout“ beim „Login mit PayPal“ aktivieren',
            'value' => true,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('boolean', 'paypalTransferCart', array(
            'label' => 'Warenkorb an PayPal übertragen',
            'value' => true,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('boolean', 'paypalExpressButton', array(
            'label' => '„Direkt zu PayPal Button“ im Warenkorb anzeigen',
            'value' => true,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('boolean', 'paypalExpressButtonLayer', array(
            'label' => '„Direkt zu PayPal Button“ in der Modal-Box anzeigen',
            'value' => true,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        //$form->setElement('boolean', 'paypalAddressOverride', array(
        //    'label' => 'Lieferadresse in PayPal ändern erlauben',
        //    'value' => false,
        //    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        //));
        $form->setElement('select', 'paypalStatusId', array(
            'label' => 'Zahlstatus nach der kompletter Zahlung',
            'value' => 12,
            'store' => 'base.PaymentStatus',
            'displayField' => 'description',
            'valueField' => 'id',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('select', 'paypalPendingStatusId', array(
            'label' => 'Zahlstatus nach der Autorisierung',
            'value' => 18,
            'store' => 'base.PaymentStatus',
            'displayField' => 'description',
            'valueField' => 'id',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('boolean', 'paypalStatusMail', array(
            'label' => 'eMail bei Zahlstatus-Änderung verschicken',
            'value' => false,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('boolean', 'paypalSendInvoiceId', array(
            'label' => 'Bestellnummer an PayPal übertragen',
            'description' => 'Ist ggf. für einige Warenwirtschaften erforderlich. Stellen Sie in diesem Fall sicher, dass ihr Nummernkreis für Bestellnummern sich nicht mit anderen/vorherigen Shops überschneidet, die Sie ebenfalls über ihren PayPal-Account betreiben.',
            'value' => false,
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        ));
        $form->setElement('text', 'paypalPrefixInvoiceId', array(
            'label' => 'Bestellnummer für PayPal mit einem Shop-Prefix versehen',
            'description' => 'Wenn Sie Ihren PayPal-Account für mehrere Shops nutzen, können Sie vermeiden, dass es Überschneidungen bei den Bestellnummern gibt, indem Sie hier ein eindeutiges Prefix definieren.',
            'value' => 'MeinShop_',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
            'vtype' => 'alphanum'
        ));
    }

    /**
     *
     */
    public function createMyTranslations()
    {
        $form = $this->Form();
        $translations = array(
            'en_GB' => array(
                'paypalUsername' => 'API username',
                'paypalPassword' => 'API password',
                'paypalSignature' => 'API signature',
                'paypalVersion' => 'API version',
                'paypalSandbox' => 'Activate sandbox mode',
                'paypalBrandName' => 'Alternative shop name on PayPal\'s site',
                'paypalLogoImage' => 'Shop image for PayPal',
                'paypalCartBorderColor' => 'Color of the basket on PayPal',
                'paypalFrontendLogo' => 'Show payment logo on frontend',
                'paypalFrontendLogoBlock' => 'Template block for the frontend logo',
                'paypalBillingAgreement' => 'Billing agreement / Activate "Buy it now"',
                'paypalTransferCart' => 'Transfer basket to PayPal',
                'paypalExpressButton' => 'Show express-purchase button in basket',
                'paypalExpressButtonLayer' => 'Show express-purchase button in modal box',
                'paypalStatusId' => 'Payment state after completing the payment',
                'paypalPendingStatusId' => 'Payment state after being authorized',
                'paypalStatusMail' => 'Send mail on payment state change',
                'paypalSendInvoiceId' => 'Transfer invoice id to paypal',
                'paypalPrefixInvoiceId' => 'Add shop prefix to the invoice id'
            )
        );
        $shopRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');
        foreach ($translations as $locale => $snippets) {
            $localeModel = $shopRepository->findOneBy(array(
                'locale' => $locale
            ));
            foreach ($snippets as $element => $snippet) {
                if ($localeModel === null) {
                    continue;
                }
                $elementModel = $form->getElement($element);
                if ($elementModel === null) {
                    continue;
                }
                $translationModel = new \Shopware\Models\Config\ElementTranslation();
                $translationModel->setLabel($snippet);
                $translationModel->setLocale($localeModel);
                $elementModel->addTranslation($translationModel);
            }
        }
    }

    /**
     *
     */
    public function createMyAttributes()
    {
        try {
            $this->Application()->Models()->addAttribute(
                's_order_attributes', 'swag_payal',
                'billing_agreement_id', 'VARCHAR(255)'
            );
        } catch (Exception $e) {
        }
        try {
            $this->Application()->Models()->addAttribute(
                's_order_attributes', 'swag_payal',
                'express', 'boolean'
            );
        } catch (Exception $e) {
        }

        $this->Application()->Models()->generateAttributeModels(array(
            's_order_attributes'
        ));
    }

    /**
     *
     */
    protected function registerMyTemplateDir()
    {
        $this->Application()->Template()->addTemplateDir(
            $this->Path() . 'Views/', 'paypal'
        );
    }

    /**
     * Returns the path to a frontend controller for an event.
     *
     * @param Enlight_Event_EventArgs $args
     * @return string
     */
    public function onGetControllerPathFrontend(Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        return $this->Path() . 'Controllers/Frontend/PaymentPaypal.php';
    }

    /**
     * Returns the path to a backend controller for an event.
     *
     * @param Enlight_Event_EventArgs $args
     * @return string
     */
    public function onGetControllerPathBackend(Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        $this->Application()->Snippets()->addConfigDir(
            $this->Path() . 'Snippets/'
        );
        return $this->Path() . 'Controllers/Backend/PaymentPaypal.php';
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        $locale = $this->Application()->Locale()->toString();
        if (strpos($locale, 'de_') === 0) {
            $locale = 'de_DE';
        }
        return $locale;
    }

    /**
     * Returns the path to a backend controller for an event.
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onPostDispatch(Enlight_Event_EventArgs $args)
    {
        /** @var $action Enlight_Controller_Action */
        $action = $args->getSubject();
        $request = $action->Request();
        $response = $action->Response();
        $view = $action->View();

        if (!$request->isDispatched()
            || $response->isException()
            || $request->getModuleName() != 'frontend'
        ) {
            return;
        }

        if ($request->getControllerName() == 'checkout' || $request->getControllerName() == 'account') {
            $this->registerMyTemplateDir();
        }

        $config = $this->Config();
        if ($view->hasTemplate() && !empty($config->paypalFrontendLogo)) {
            $this->registerMyTemplateDir();
            $view->extendsBlock(
                $config->paypalFrontendLogoBlock,
                '{include file="frontend/payment_paypal/logo.tpl"}' . "\n",
                'append'
            );
        }

        if (!empty($config->paypalExpressButtonLayer)
            && $request->getControllerName() == 'checkout' && $request->getActionName() == 'ajax_add_article') {
            $view->PaypalShowButton = true;
            $view->PaypalLocale = $this->getLocale();
            $view->extendsBlock(
                'frontend_checkout_ajax_add_article_action_buttons',
                '{include file="frontend/payment_paypal/layer.tpl"}' . "\n",
                'prepend'
            );
        }

        if (!empty($config->paypalExpressButton)
          && $request->getControllerName() == 'checkout' && $request->getActionName() == 'cart') {
            $view->PaypalShowButton = true;
            $view->PaypalLocale = $this->getLocale();
            $view->extendsBlock(
                'frontend_checkout_actions_confirm',
                '{include file="frontend/payment_paypal/express.tpl"}' . "\n",
                'prepend'
            );
        }

        if (!empty($config->paypalLogIn)
          && $request->getControllerName() == 'account' && $request->getActionName() == 'ajax_login') {
            $view->PaypalShowButton = true;
            $view->PaypalLocale = $this->getLocale();
            $view->PaypalClientId = $this->Config()->get('paypalClientId');
            $view->PaypalSandbox = $this->Config()->get('paypalSandbox');
            $view->PaypalSeamlessCheckout = $this->Config()->get('paypalSeamlessCheckout');
            $view->extendsTemplate('frontend/payment_paypal/ajax_login.tpl');
        }

        if ($view->hasTemplate() && isset($view->PaypalShowButton)) {
            $showButton = false;
            $admin = Shopware()->Modules()->Admin();
            $payments = isset($view->sPayments) ? $view->sPayments : $admin->sGetPaymentMeans();
            foreach ($payments as $payment) {
                if ($payment['name'] == 'paypal') {
                    $showButton = true;
                    break;
                }
            }
            $view->PaypalShowButton = $showButton;
        }

        if (Shopware()->Modules()->Admin()->sCheckUser()) {
            $view->PaypalShowButton = false;
        }

        if ($view->hasTemplate()) {
            $this->registerMyTemplateDir();
            $view->extendsTemplate('frontend/payment_paypal/header.tpl');
        }

        if ($request->getControllerName() == 'checkout' && $request->getActionName() == 'confirm'
          && !empty(Shopware()->Session()->PaypalResponse)) {
            $view->sRegisterFinished = false;
        }
    }

    /**
     * @param   string $paymentStatus
     * @return  int
     */
    public function getPaymentStatusId($paymentStatus)
    {
        switch ($paymentStatus) {
            case 'Completed':
                $paymentStatusId = $this->Config()->get('paypalStatusId', 12); break;
            case 'Pending':
            case 'In-Progress':
                 $paymentStatusId = $this->Config()->get('paypalPendingStatusId', 18); break; //Reserviert
            case 'Processed':
                $paymentStatusId = 18; break; //In Bearbeitung > Reserviert
            case 'Refunded':
                $paymentStatusId = 20; break; //Wiedergutschrift
            case 'Partially-Refunded':
                $paymentStatusId = 20; break; //Wiedergutschrift
            case 'Cancelled-Reversal':
                $paymentStatusId = 12; break;
            case 'Expired': //Offen
            case 'Denied':
            case 'Voided':
                $paymentStatusId = 17; break;
            case 'Reversed':
            default:
                $paymentStatusId = 21; break;
        }
        return $paymentStatusId;
    }

    /**
     * @param string $transactionId
     * @param string $paymentStatus
     * @param string|null $note
     * @return void
     */
    public function setPaymentStatus($transactionId, $paymentStatus, $note = null)
    {
        $paymentStatusId = $this->getPaymentStatusId($paymentStatus);
        $sql = '
            SELECT id FROM s_order WHERE transactionID=? AND status!=-1
        ';
        $orderId = Shopware()->Db()->fetchOne($sql, array(
            $transactionId
        ));
        $order = Shopware()->Modules()->Order();
        $order->setPaymentStatus($orderId, $paymentStatusId, false, $note);
        if ($paymentStatusId == 21) {
            $sql = 'UPDATE  `s_order` SET internalcomment = CONCAT( internalcomment, :pStatus) WHERE transactionID = :transactionId';
            Shopware()->Db()->query($sql, array(
                'pStatus' => "\nPayPal Status: " . $paymentStatus,
                'transactionId' => $transactionId
            ));
        }
        if ($paymentStatus == 'Completed') {
            $sql  = '
                UPDATE s_order SET cleareddate=NOW()
                WHERE transactionID=?
                AND cleareddate IS NULL LIMIT 1
            ';
            Shopware()->Db()->query($sql, array(
                $transactionId
            ));
        }
    }

    /**
     *
     * @return array
     */
    public function getLabel()
    {
        return 'PayPal Payment';
    }

    /**
     * Returns the version of plugin as string.
     *
     * @throws Exception
     * @return string
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);
        if ($info) {
            return $info['currentVersion'];
        } else {
            throw new Exception('The plugin has an invalid version file.');
        }
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            'description' => file_get_contents($this->Path() . 'info.txt')
        );
    }

    /**
     * Creates and returns the paypal client for an event.
     *
     * @param Enlight_Event_EventArgs $args
     * @return \Shopware_Components_Paypal_Client
     */
    public function onInitResourcePaypalClient(Enlight_Event_EventArgs $args)
    {
        $this->Application()->Loader()->registerNamespace(
            'Shopware_Components_Paypal',
            $this->Path() . 'Components/Paypal/'
        );
        $client = new Shopware_Components_Paypal_Client($this->Config());
        return $client;
    }
}
