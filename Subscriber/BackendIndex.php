<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\SwagPaymentPaypal\Subscriber;

use Shopware_Plugins_Frontend_SwagPaymentPaypal_Bootstrap as Bootstrap;

class BackendIndex
{
    /**
     * @var Bootstrap $bootstrap
     */
    protected $bootstrap;

    /**
     * BackendIndex constructor.
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Backend_Index' => 'onPostDispatchBackendIndex'
        );
    }

    /**
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchBackendIndex($args)
    {
        $action = $args->getSubject();
        $request = $action->Request();
        $response = $action->Response();
        $view = $action->View();

        if (!$request->isDispatched()
            || $response->isException()
            || $request->getActionName() != 'index'
            || !$view->hasTemplate()
        ) {
            return;
        }

        $this->bootstrap->registerMyTemplateDir();
        $view->extendsTemplate('backend/index/paypal_header.tpl');
    }
}
