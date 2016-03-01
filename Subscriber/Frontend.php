<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\SwagPaymentPaypal\Subscriber;

use Shopware_Plugins_Frontend_SwagPaymentPaypal_Bootstrap as Bootstrap;

class Frontend
{
    /**
     * @var Bootstrap $bootstrap
     */
    protected $bootstrap;

    /**
     * @var \Enlight_Config $config
     */
    protected $config;

    /**
     * Frontend constructor.
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
        $this->config = $bootstrap->Config();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch' => 'onPostDispatch'
        );
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onPostDispatch(\Enlight_Event_EventArgs $args)
    {
        /** @var $action \Enlight_Controller_Action */
        $action = $args->getSubject();
        $request = $action->Request();
        $response = $action->Response();
        $view = $action->View();
        $config = $this->config;

        if (!$request->isDispatched()
            || $response->isException()
            || $request->getModuleName() != 'frontend'
            || !$view->hasTemplate()
        ) {
            return;
        }

        $admin = Shopware()->Modules()->Admin();
        $session = Shopware()->Session();

        /** @var $shopContext \Shopware\Models\Shop\Shop */
        $shopContext = $this->bootstrap->get('shop');
        $templateVersion = $shopContext->getTemplate()->getVersion();

        if ($templateVersion >= 3) {
            $this->bootstrap->registerMyTemplateDir(true);
        } else {
            $this->bootstrap->registerMyTemplateDir();
        }

        if (!empty($config->paypalFrontendLogo)) {
            if ($templateVersion < 3) {
                $view->extendsBlock(
                    $config->get('paypalFrontendLogoBlock', 'frontend_index_left_campaigns_bottom'),
                    '{include file="frontend/payment_paypal/logo.tpl"}' . "\n",
                    'append'
                );
            } else {
                $view->PaypalShowLogo = true;
            }
        }

        if (!empty($config->paypalExpressButtonLayer)) {
            if (($templateVersion < 3 && $request->getControllerName() == 'checkout' && $request->getActionName() == 'ajax_add_article')
                || ($templateVersion >= 3 && $request->getControllerName() == 'checkout' && $request->getActionName() == 'ajaxCart')
            ) {
                $view->PaypalShowButton = true;
                if ($templateVersion < 3) {
                    $view->extendsBlock(
                        'frontend_checkout_ajax_add_article_action_buttons',
                        '{include file="frontend/payment_paypal/layer.tpl"}' . "\n",
                        'prepend'
                    );
                }
            }
        }

        if (!empty($config->paypalExpressButton)
            && $request->getControllerName() == 'checkout'
            && $request->getActionName() == 'cart'
        ) {
            $view->PaypalShowButton = true;
            if ($templateVersion < 3) {
                $view->extendsBlock(
                    'frontend_checkout_actions_confirm',
                    '{include file="frontend/payment_paypal/express.tpl"}' . "\n",
                    'prepend'
                );
            }
        }

        if (!empty($config->paypalLogIn)) {
            if (($templateVersion < 3 && $request->getControllerName() == 'account' && $request->getActionName() == 'ajax_login')
                || ($templateVersion >= 3 && $request->getControllerName() == 'register' && $request->getActionName() == 'index')
            ) {
                $view->PaypalShowButton = true;
                $view->PaypalClientId = $config->get('paypalClientId');
                $view->PaypalSandbox = $config->get('paypalSandbox');
                if ($templateVersion < 3) {
                    $view->PaypalSeamlessCheckout = $config->get('paypalSeamlessCheckout');
                    $view->extendsTemplate('frontend/payment_paypal/ajax_login.tpl');
                }
            }
        }

        if (isset($view->PaypalShowButton)) {
            $showButton = false;
            $view->sPayments = isset($view->sPayments) ? $view->sPayments : $admin->sGetPaymentMeans();
            foreach ($view->sPayments as $payment) {
                if ($payment['name'] == 'paypal') {
                    $showButton = true;
                    break;
                }
            }
            $view->PaypalShowButton = $showButton;
        }

        if (!empty($view->PaypalShowButton) && !empty($session->sUserId)) {
            $view->PaypalShowButton = false;
        }

        $view->PaypalLogIn = $config->get('paypalLogIn');
        $view->PaypalLocale = $this->bootstrap->getLocaleCode();

        if ($templateVersion < 3) {
            $view->extendsTemplate('frontend/payment_paypal/header.tpl');
            $view->extendsTemplate('frontend/payment_paypal/change_payment.tpl');
        }

        if ($request->getControllerName() == 'checkout'
            && $request->getActionName() == 'confirm'
            && !empty($session->PaypalResponse)
        ) {
            $view->sRegisterFinished = false;
        }
    }
}
