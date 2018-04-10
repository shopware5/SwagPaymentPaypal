<?php
/**
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
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var \Enlight_Config
     */
    protected $config;

    /**
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
            'Enlight_Controller_Action_PostDispatch' => 'onPostDispatch',
        );
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onPostDispatch(\Enlight_Event_EventArgs $args)
    {
        /** @var $action \Enlight_Controller_Action */
        $action = $args->get('subject');
        $request = $action->Request();
        $response = $action->Response();
        $view = $action->View();
        $config = $this->config;

        if (!$request->isDispatched()
            || $response->isException()
            || $request->getModuleName() !== 'frontend'
            || !$view->hasTemplate()
        ) {
            return;
        }

        $admin = Shopware()->Modules()->Admin();
        $session = Shopware()->Session();
        $controllerName = $request->getControllerName();
        $actionName = $request->getActionName();

        /** @var $shopContext \Shopware\Models\Shop\Shop */
        $shopContext = $this->bootstrap->get('shop');
        $templateVersion = $shopContext->getTemplate()->getVersion();

        if ($templateVersion >= 3) {
            $this->bootstrap->registerMyTemplateDir(true);
        } else {
            $this->bootstrap->registerMyTemplateDir();
        }

        if ((bool) $config->get('paypalFrontendLogo')) {
            if ($templateVersion < 3) {
                $view->extendsBlock(
                    $config->get('paypalFrontendLogoBlock', 'frontend_index_left_campaigns_bottom'),
                    '{include file="frontend/payment_paypal/logo.tpl"}' . "\n",
                    'append'
                );
            } else {
                $view->assign('PaypalShowLogo', true);
            }
        }

        if ((bool) $config->get('paypalExpressButtonLayer')
            && (($templateVersion < 3 && $controllerName === 'checkout' && $actionName === 'ajax_add_article')
                || ($templateVersion >= 3 && $controllerName === 'checkout' && $actionName === 'ajaxCart'))
        ) {
            $view->assign('PaypalShowButton', true);
            if ($templateVersion < 3) {
                $view->extendsBlock(
                    'frontend_checkout_ajax_add_article_action_buttons',
                    '{include file="frontend/payment_paypal/layer.tpl"}' . "\n",
                    'prepend'
                );
            }
        }

        if ((bool) $config->get('paypalExpressButton')
            && $controllerName === 'checkout'
            && $actionName === 'cart'
        ) {
            $view->assign('PaypalShowButton', true);
            if ($templateVersion < 3) {
                $view->extendsBlock(
                    'frontend_checkout_actions_confirm',
                    '{include file="frontend/payment_paypal/express.tpl"}' . "\n",
                    'prepend'
                );
            }
        }

        if ((bool) $config->get('paypalExpressButtonLogin')
            && $controllerName === 'register'
            && $actionName === 'index'
            && $request->getParam('sTarget') === 'checkout'
            && $request->getParam('sTargetAction') === 'confirm'
        ) {
            $view->assign('PaypalShowButton', true);
        }

        if ((bool) $config->get('paypalLogIn')) {
            if (($templateVersion < 3 && $controllerName === 'account' && $actionName === 'ajax_login')
                || ($templateVersion >= 3 && $controllerName === 'register' && $actionName === 'index')
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

        if ($view->getAssign('PaypalShowButton')) {
            $showButton = false;
            $payments = $view->getAssign('sPayments');
            if ($payments === null) {
                $payments = $admin->sGetPaymentMeans();
            }
            $view->assign('sPayments', $payments);
            foreach ($payments as $payment) {
                if ($payment['name'] === 'paypal') {
                    $showButton = true;
                    break;
                }
            }
            $view->assign('PaypalShowButton', $showButton);
        }

        if ($view->getAssign('PaypalShowButton') && $session->offsetGet('sUserId')) {
            $view->assign('PaypalShowButton', false);
        }

        $view->assign('PaypalLocale', $this->bootstrap->getLocaleCode());

        if ($templateVersion < 3) {
            $view->extendsTemplate('frontend/payment_paypal/header.tpl');
            $view->extendsTemplate('frontend/payment_paypal/change_payment.tpl');
        }

        if ($controllerName === 'checkout'
            && $actionName === 'confirm'
            && $session->offsetGet('PaypalResponse')
        ) {
            $view->assign('sRegisterFinished', false);
        }
    }
}
