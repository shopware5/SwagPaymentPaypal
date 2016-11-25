<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware_Components_Paypal_RestClient as RestClient;

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
    protected $apiVersion = '113.0';

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
        parent::__construct($url);
        $this->setAdapter(RestClient::createAdapterFromConfig($config));
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
        $this->setParameterPost(array(
            'METHOD' => $name,
            'VERSION' => $this->apiVersion,
            'PWD' => $this->apiPassword,
            'USER' => $this->apiUsername,
            'SIGNATURE' => $this->apiSignature
        ));
        if (!empty($args[0])) {
            $this->setParameterPost($args[0]);
        }
        $response = $this->request('POST');

        $body = $response->getBody();
        $params = array();
        parse_str($body, $params);

        return $params;
    }
}
