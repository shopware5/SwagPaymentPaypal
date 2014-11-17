<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Shopware Paypal Client
 *
 * @method array setExpressCheckout(array $params)
 * @method array getExpressCheckoutDetails(array $params)
 * @method array doExpressCheckoutPayment(array $params)
 * @method array doReferenceTransaction(array $params)
 * @method array getTransactionDetails(array $params)
 * @method array getBalance(array $params = array())
 * @method array getPalDetails(array $params = array())
 * @method array TransactionSearch(array $params)
 * @method array RefundTransaction(array $params)
 * @method array doReAuthorization(array $params)
 * @method array doAuthorization(array $params)
 * @method array doCapture(array $params)
 * @method array doVoid(array $params)
 */
class Shopware_Components_Paypal_Client extends Zend_Http_Client
{
    /**
     * The sandbox url.
     *
     * @var string
     */
    const URL_SANDBOX = 'https://api-3t.sandbox.paypal.com/nvp';

    /**
     * The live url.
     *
     * @var string
     */
    const URL_LIVE = 'https://api-3t.paypal.com/nvp';

    /**
     * @var string
     */
    protected $apiUsername;

    /**
     * @var string
     */
    protected $apiPassword;

    /**
     * @var string
     */
    protected $apiSignature;

    /**
     * @var string
     */
    protected $apiVersion;

    /**
     * Constructor method
     *
     * Expects a configuration parameter.
     *
     * @param Enlight_Config $config
     */
    public function __construct($config)
    {
        if (!empty($config->paypalSandbox)) {
            $url = self::URL_SANDBOX;
        } else {
            $url = self::URL_LIVE;
        }
        $this->apiUsername = $config->get('paypalUsername');
        $this->apiPassword = $config->get('paypalPassword');
        $this->apiSignature = $config->get('paypalSignature');
        $timeout = $config->get('paypalTimeout', 20);
        $this->apiVersion = $config->get('paypalVersion');
        parent::__construct($url, array(
            'useragent' => 'Shopware/' . Shopware()->Config()->version,
            'timeout' => $timeout,
        ));
        if (extension_loaded('curl')) {
            $adapter = new Zend_Http_Client_Adapter_Curl();
            if (!empty($config->paypalSandbox)) {
                $adapter->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
                $adapter->setCurlOption(CURLOPT_SSL_VERIFYHOST, false);
            }
            $adapter->setCurlOption(CURLOPT_TIMEOUT, $timeout);
            $this->setAdapter($adapter);
        }
    }

    /**
     * @param $name
     * @param array $args
     * @return array|bool
     */
    public function __call($name, $args = array())
    {
        $name = ucfirst($name);
        $this->resetParameters();
        $this->setParameterGet(array(
            'METHOD' => $name,
            'VERSION' => $this->apiVersion,
            'PWD' => $this->apiPassword,
            'USER' => $this->apiUsername,
            'SIGNATURE' => $this->apiSignature
        ));
        if (!empty($args[0])) {
            $this->setParameterGet($args[0]);
        }
        try {
            $response = $this->request('GET');
        } catch (Exception $e) {
            return false;
        }

        $body = $response->getBody();
        $params = array();
        parse_str($body, $params);
        return $params;
    }
}
