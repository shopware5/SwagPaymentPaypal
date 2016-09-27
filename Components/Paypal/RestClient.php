<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Shopware Paypal Rest Client
 */
class Shopware_Components_Paypal_RestClient extends Zend_Http_Client
{
    /**
     * The sandbox url.
     *
     * @var string
     */
    const URL_SANDBOX = 'https://api.sandbox.paypal.com/v1/';

    /**
     * The live url.
     *
     * @var string
     */
    const URL_LIVE = 'https://api.paypal.com/v1/';

    protected $pluginConfig;

    /**
     * Constructor method
     *
     * Expects a configuration parameter.
     *
     * @param Enlight_Config $config
     * @param string $caPath path to Bundle of CA Root Certificates (see: https://curl.haxx.se/ca/cacert.pem)
     */
    public function __construct($config, $caPath = null)
    {
        $this->pluginConfig = $config;
        parent::__construct($this->getBaseUri());
        $this->setAdapter(self::createAdapterFromConfig($config, $caPath));
    }

    /**
     * @param Enlight_Config $config
     * @param string $caPath path to Bundle of CA Root Certificates (see: https://curl.haxx.se/ca/cacert.pem)
     * @see https://github.com/paypal/sdk-core-php
     * @return Zend_Http_Client_Adapter_Curl|Zend_Http_Client_Adapter_Socket
     */
    public static function createAdapterFromConfig($config, $caPath = null)
    {
        $curl = $config->get('paypalCurl', true);
        $timeout = $config->get('paypalTimeout') ?: 60;
        $userAgent = 'Shopware/' . Shopware::VERSION;

        if ($curl && extension_loaded('curl')) {
            $adapter = new Zend_Http_Client_Adapter_Curl();
            $adapter->setConfig(array(
                'useragent' => $userAgent,
                'timeout' => $timeout,
            ));
            if (!empty($config->paypalSandbox)) {
                $adapter->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
                $adapter->setCurlOption(CURLOPT_SSL_VERIFYHOST, false);
            }

            if ($caPath) {
                $adapter->setCurlOption(CURLOPT_CAINFO, $caPath);
            }

            $adapter->setCurlOption(CURLOPT_TIMEOUT, $timeout);
            //$adapter->setCurlOption(CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
            //$adapter->setCurlOption(CURLOPT_SSL_VERIFYPEER, 1);
            //$adapter->setCurlOption(CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            $adapter = new Zend_Http_Client_Adapter_Socket();
            $adapter->setConfig(array(
                'useragent' => $userAgent,
                'timeout' => $timeout
            ));
        }
        return $adapter;
    }

    protected function getBaseUri()
    {
        if (!empty($this->pluginConfig->paypalSandbox)) {
            return self::URL_SANDBOX;
        } else {
            return self::URL_LIVE;
        }
    }

    public function setAuthBase()
    {
        parent::setAuth(
            $this->pluginConfig->get('paypalClientId'),
            $this->pluginConfig->get('paypalSecret')
        );
    }

    public function setAuthToken($auth = null)
    {
        static $defaultAuth;
        if (!isset($defaultAuth) && $auth === null) {
            $uri = 'oauth2/token';
            $params = array(
                'grant_type' => 'client_credentials',
            );
            $this->setAuthBase();
            $defaultAuth = $this->post($uri, $params);
            $this->resetParameters();
        }
        if ($auth === null) {
            $auth = $defaultAuth;
        }
        $this->setAuth(false);
        $this->setHeaders('Authorization', "{$auth['token_type']} {$auth['access_token']}");
        return $auth;
    }

    public function getOpenIdAuth($code, $redirectUri)
    {
        $uri = 'identity/openidconnect/tokenservice';
        $params = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        );
        $this->setAuthBase();
        return $this->post($uri, $params);
    }

    public function getOpenIdIdentity($auth)
    {
        $uri = 'identity/openidconnect/userinfo/';
        $params = array('schema' => 'openid');
        $this->setAuthToken($auth);
        return $this->get($uri, $params);
    }

    public function create($uri, $params)
    {
        $this->setRawData(json_encode($params), 'application/json');
        return $this->post($uri);
    }

    public function update($uri, $params)
    {
        $this->setRawData(json_encode($params), 'application/json');
        return $this->put($uri);
    }

    public function request($method = null, $uri = null, $params = null)
    {
        if ($method !== null) {
            $this->setMethod($method);
        }
        if ($uri !== null) {
            if (strpos($uri, 'http') !== 0) {
                $uri = $this->getBaseUri() . $uri;
            }
            $this->setUri($uri);
        }
        if ($params !== null) {
            $this->resetParameters();
            if ($this->method == self::POST) {
                $this->setMethod($this->method);
                $this->setParameterPost($params);
            } else {
                $this->setParameterGet($params);
            }
        }
        $response = parent::request();
        return $this->filterResponse($response);
    }

    private function filterResponse($response)
    {
        $body = $response->getBody();

        $data = array();
        $data['status'] = $response->getStatus();
        $data['message'] = $response->getMessage();

        if (strpos($response->getHeader('content-type'), 'application/json') === 0) {
            $body = json_decode($body, true);
        }
        if (!is_array($body)) {
            $body = array('body' => $body);
        }
        return $data + $body;
    }

    public function get($uri = null, $params = null)
    {
        return $this->request(self::GET, $uri, $params);
    }

    public function post($uri = null, $params = null)
    {
        return $this->request(self::POST, $uri, $params);
    }

    public function put($uri = null, $params = null)
    {
        return $this->request(self::PUT, $uri, $params);
    }
}
