<?php

/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Plugins\SwagPaymentPaypal\Components\Paypal\AddressValidator;

class Shopware_Plugins_Frontend_SwagPaymentPaypal_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * Installs the plugin
     *
     * @return bool
     */
    public function install()
    {
        $this->createMyEvents();
        $this->createMyMenu();
        $this->createMyForm();
        $this->createMyTranslations();
        $this->fixOrderMail();
        $this->createMyAttributes();
        $this->fixPaymentLogo();
        $this->createMyPayment();

        return true;
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        $this->secureUninstall();
        $this->removeMyAttributes();

        return array('success' => true, 'invalidateCache' => array('config', 'backend', 'proxy', 'frontend'));
    }

    /**
     * @return bool
     */
    public function secureUninstall()
    {
        return true;
    }

    /**
     * @param string $version
     * @return bool|array
     */
    public function update($version)
    {
        if ($version == '0.0.1') {
            return false;
        }
        if (strpos($version, '2.0.') === 0) {
            try {
                $this->get('models')->removeAttribute(
                    's_user_attributes',
                    'swag_payal',
                    'billing_agreement_id'
                );
            } catch (Exception $e) {
            }
            try {
                $this->get('models')->addAttribute(
                    's_order_attributes',
                    'swag_payal',
                    'billing_agreement_id',
                    'VARCHAR(255)'
                );
            } catch (Exception $e) {
            }

            $this->get('models')->generateAttributeModels(array('s_order_attributes', 's_user_attributes'));
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
        }
        if (version_compare($version, '3.1.0', '<=')) {
            $this->createMyMenu();
        }
        if (version_compare($version, '3.1.0', '<=')) {
            $sql = 'ALTER TABLE `s_order_attributes`
                    CHANGE `swag_payal_express` `swag_payal_express` INT( 11 ) NULL DEFAULT NULL';
            $this->get('db')->exec($sql);
        }
        if (version_compare($version, '3.3.2', '<=')) {
            //always remove unneeded settings
            $em = $this->get('models');
            $form = $this->Form();
            $paypalLogInApi = $form->getElement('paypalLogInApi');
            if ($paypalLogInApi !== null) {
                $em->remove($paypalLogInApi);
            }
            $paypalSeamlessCheckout = $form->getElement('paypalSeamlessCheckout');
            if ($paypalSeamlessCheckout !== null) {
                $em->remove($paypalSeamlessCheckout);

            }
            $em->flush();
        }
        if (version_compare($version, '3.3.4', '<')) {
            $this->fixPaymentLogo();
            $this->fixPluginDescription();
        }
        if (version_compare($version, '3.4.3', '<=')) {
            $this->Form()->removeElement('paypalVersion');
        }

        //Update form
        $this->createMyForm();
        $this->createMyEvents();

        return array(
            'success' => true,
            'invalidateCache' => array('config', 'backend', 'proxy', 'frontend')
        );
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        if (version_compare(Shopware::VERSION, '4.2.0', '<') && Shopware::VERSION != '___VERSION___') {
            $name = ucfirst($name);

            return $this->Application()->Bootstrap()->getResource($name);
        }

        return parent::get($name);
    }

    private function fixOrderMail()
    {
        // Make sure, that the additionadescription field is evaluated by smarty
        $sql = <<<'EOD'
            UPDATE  `s_core_config_mails`
            SET
                content=REPLACE(content, '{$additional.payment.additionaldescription}', '{include file="string:`$additional.payment.additionaldescription`"}'),
                contentHTML=REPLACE(contentHTML, '{$additional.payment.additionaldescription}', '{include file="string:`$additional.payment.additionaldescription`"}')
            WHERE name='sORDER';
EOD;
        $this->get('db')->query($sql);
    }

    private function fixPluginDescription()
    {
        $payment = $this->getPayment();
        if ($payment === null) {
            return;
        }

        $description = $payment->getAdditionalDescription();
        $description = preg_replace('#<!-- PayPal Logo -->.+<!-- PayPal Logo -->#msi', '', $description);
        $description = str_replace('<p>PayPal. <em>Sicherererer.</em></p>', '<br><br>', $description);
        $payment->setAdditionalDescription($description);
        $this->get('models')->flush($payment);
    }

    /**
     * Check if paypal logo exists in "unsorted" album otherwise create it and remove old logo
     *
     */
    private function fixPaymentLogo()
    {
        $logo = 'paypal_logo.png';
        $mediaPath = $this->Application()->DocPath() . 'media/image/' . $logo;

        $mediaRepo = $this->get('models')->getRepository('Shopware\Models\Media\Media');
        $image = $mediaRepo->findOneBy(array('name' => 'paypal_logo'));
        if ($image) {
            $this->get('models')->remove($image);
            $this->get('models')->flush();
        }

        //Remove file if don't have it in media manager but we have it under media/image folder
        if (file_exists($mediaPath)) {
            unlink($mediaPath);
        }
    }

    /**
     * Fetches and returns paypal payment row instance.
     *
     * @return \Shopware\Models\Payment\Payment
     */
    public function getPayment()
    {
        return $this->Payments()->findOneBy(
            array('name' => 'paypal')
        );
    }

    /**
     * Activate the plugin paypal plugin.
     * Sets the active flag in the payment row.
     *
     * @return bool
     */
    public function enable()
    {
        $payment = $this->getPayment();
        if ($payment !== null) {
            $payment->setActive(true);
            $this->get('models')->flush($payment);
        }

        return array(
            'success' => true,
            'invalidateCache' => array('config', 'backend', 'proxy', 'frontend')
        );
    }

    /**
     * Disable plugin method and sets the active flag in the payment row
     *
     * @return bool
     */
    public function disable()
    {
        $payment = $this->getPayment();
        if ($payment !== null) {
            $payment->setActive(false);
            $this->get('models')->flush($payment);
        }

        return array(
            'success' => true,
            'invalidateCache' => array('config', 'backend')
        );
    }

    /**
     * Creates and subscribe the events and hooks.
     */
    private function createMyEvents()
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

        $this->subscribeEvent(
            'Enlight_Bootstrap_InitResource_PaypalRestClient',
            'onInitResourcePaypalRestClient'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_Index',
            'onExtendBackendIndex'
        );

        $this->subscribeEvent(
            'Theme_Compiler_Collect_Plugin_Less',
            'addLessFiles'
        );

        $this->subscribeEvent('Enlight_Bootstrap_AfterInitResource_shopware_account.address_validator', 'decorateAddressValidator');
    }

    /**
     * Modifies the default address validator to be able to allow values which were not provided by paypal but may be required in shopware.
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function decorateAddressValidator(Enlight_Event_EventArgs $args)
    {
        require_once __DIR__ . '/Components/Paypal/AddressValidator.php';

        $innerValidator = $this->get('shopware_account.address_validator');
        $validator = new AddressValidator($innerValidator, Shopware()->Container());

        Shopware()->Container()->set('shopware_account.address_validator', $validator);
    }

    /**
     * Creates and save the payment row.
     */
    private function createMyPayment()
    {
        $this->createPayment(
            array(
                'name' => 'paypal',
                'description' => 'PayPal',
                'action' => 'payment_paypal',
                'active' => 0,
                'position' => 0,
                'additionalDescription' => $this->getPaymentLogo() . 'Bezahlung per PayPal - einfach, schnell und sicher.'
            )
        );
    }

    private function getPaymentLogo()
    {
        $this->Application()->Template()->addTemplateDir(
            $this->Path() . 'Views/'
        );

        return '<!-- PayPal Logo -->'
        . '<a onclick="window.open(this.href, \'olcwhatispaypal\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=400, height=500\'); return false;"'
        . ' href="https://www.paypal.com/de/cgi-bin/webscr?cmd=xpt/cps/popup/OLCWhatIsPayPal-outside" target="_blank">'
        . '<img src="{link file=\'frontend/_resources/images/paypal_logo.png\' fullPath}" alt="Logo \'PayPal empfohlen\'">'
        . '</a><br>' . '<!-- PayPal Logo -->';
    }

    /**
     * Creates and stores a payment item.
     */
    private function createMyMenu()
    {
        $parent = $this->Menu()->findOneBy(array('label' => 'Zahlungen'));
        $this->createMenuItem(
            array(
                'label' => 'PayPal',
                'controller' => 'PaymentPaypal',
                'action' => 'Index',
                'class' => 'paypal--icon',
                'active' => 1,
                'parent' => $parent
            )
        );
    }

    /**
     * Creates and stores the payment config form.
     */
    private function createMyForm()
    {
        $form = $this->Form();

        // API settings
        $form->setElement(
            'text',
            'paypalUsername',
            array(
                'label' => 'API-Benutzername',
                'required' => true,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'stripCharsRe' => " \t"
            )
        );
        $form->setElement(
            'text',
            'paypalPassword',
            array(
                'label' => 'API-Passwort',
                'required' => true,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'stripCharsRe' => " \t"
            )
        );
        $form->setElement(
            'text',
            'paypalSignature',
            array(
                'label' => 'API-Unterschrift',
                'required' => true,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'stripCharsRe' => " \t"
            )
        );
        $form->setElement(
            'button',
            'paypalButtonApi',
            array(
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
            )
        );
        $form->setElement(
            'text',
            'paypalClientId',
            array(
                'label' => 'REST-API Client ID',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'stripCharsRe' => " \t",
                'description' => 'Hinweis: Diese Zugangsdaten werden nur benötigt, wenn sie PayPal PLUS installieren und einrichten wollen. Weitere Informationen dazu erhalten sie von Ihrem PayPal-Ansprechpartner.',
            )
        );
        $form->setElement(
            'text',
            'paypalSecret',
            array(
                'label' => 'REST-API Secret',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'stripCharsRe' => " \t"
            )
        );
        $form->setElement(
            'button',
            'paypalButtonRestApi',
            array(
                'label' => '<strong>Jetzt Daten für REST-API erhalten</strong>',
                'handler' => "function(btn) {
                var link = document.location.pathname + 'paymentPaypal/downloadRestDocument';
                window.open(link, '');
            }"
            )
        );

        $form->setElement(
            'boolean',
            'paypalSandbox',
            array(
                'label' => 'Sandbox-Modus aktivieren',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $form->setElement(
            'number',
            'paypalTimeout',
            array(
                'label' => 'API-Timeout in Sekunden',
                'emptyText' => 'Default (60)',
                'value' => null,
                'scope' => 0
            )
        );
        $form->setElement(
            'boolean',
            'paypalCurl',
            array(
                'label' => '<a href="http://php.net/manual/de/book.curl.php" target="_blank">Curl</a> verwenden (wenn es verfügbar ist)',
                'value' => true
            )
        );

        if (is_file(__DIR__ . '/Views/backend/plugins/paypal/test.js')) {
            $form->setElement(
                'button',
                'paypalButtonClientTest',
                array(
                    'label' => '<strong>Jetzt API testen<strong>',
                    'handler' => "function(btn) {"
                        . file_get_contents(__DIR__ . '/Views/backend/plugins/paypal/test.js') . "}"
                )
            );
        }

        $form->setElement(
            'boolean',
            'paypalErrorMode',
            array(
                'label' => 'Fehlermeldungen ausgeben',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        // Payment page settings
        $form->setElement(
            'text',
            'paypalBrandName',
            array(
                'label' => 'Alternativer Shop-Name auf der PayPal-Seite',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'maxLength' => 127,
                'enforceMaxLength' => true
            )
        );
        $form->setElement(
            'text',
            'paypalLocaleCode',
            array(
                'label' => 'Alternative Sprache (LocaleCode)',
                'emptyText' => 'Beispiel: de_DE',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $form->setElement(
            'media',
            'paypalLogoImage',
            array(
                'label' => 'Shop-Logo auf der PayPal-Seite',
                'value' => null,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'readOnly' => false,
            )
        );
        $form->setElement(
            'color',
            'paypalCartBorderColor',
            array(
                'label' => 'Farbe des Warenkorbs auf der PayPal-Seite',
                'value' => '#E1540F',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        // Frontend settings
        $form->setElement(
            'boolean',
            'paypalFrontendLogo',
            array(
                'label' => 'Payment-Logo im Frontend ausgeben',
                'value' => true,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        // Payment settings
        $store = array(
            array('Sale', array('en_GB' => 'Complete payment immediately (Sale)', 'de_DE' => 'Zahlung sofort abschließen (Sale)')),
            array('Authorization', array('en_GB' => 'Delayed payment collection (Auth-Capture)', 'de_DE' => 'Zeitverzögerter Zahlungseinzug (Auth-Capture)')),
            array('Order', array('en_GB' => 'Delayed payment collection (Order-Auth-Capture)', 'de_DE' => 'Zeitverzögerter Zahlungseinzug (Order-Auth-Capture)')),
        );
        if (!$this->assertMinimumVersion('4.3.0')) {
            $store = array_map(function ($option) {
                return array($option[0], $option[1]['de_DE']);
            }, $store);
        }
        $form->setElement(
            'select',
            'paypalPaymentAction',
            array(
                'label' => 'Zahlungsabschluss',
                'value' => 'Sale',
                'store' => $store,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $form->setElement(
            'boolean',
            'paypalBillingAgreement',
            array(
                'label' => 'Zahlungsvereinbarung treffen / „Sofort-Kaufen“ aktivieren',
                'description' => 'Achtung: Diese Funktion muss erst für Ihren PayPal-Account von PayPal aktiviert werden.',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
//        $form->setElement('boolean', 'paypalLogIn', array(
//            'label' => '„Login mit PayPal“ aktivieren',
//            'description' => 'Achtung: Für diese Funktion müssen Sie erst die Daten für die REST-API hinterlegen.',
//            'value' => false,
//            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
//        ));
//        $form->setElement('boolean', 'paypalFinishRegister', array(
//            'label' => 'Nach dem ersten „Login mit PayPal“ auf die Registrierung umleiten',
//            'value' => true,
//            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
//        ));
        $form->setElement(
            'boolean',
            'paypalTransferCart',
            array(
                'label' => 'Warenkorb an PayPal übertragen',
                'value' => true,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'description' => 'Hinweis: Die Anzahl der Positionen ist technisch auf 24 Stück beschränkt. Bei Netto-Bestellung werden teilweise keine Stückpreise übergeben, da diese mehr als zwei Nachkommastellen haben können.'
            )
        );
        $form->setElement(
            'boolean',
            'paypalExpressButton',
            array(
                'label' => '„Direkt zu PayPal Button“ im Warenkorb anzeigen',
                'value' => true,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $form->setElement(
            'boolean',
            'paypalExpressButtonLayer',
            array(
                'label' => '„Direkt zu PayPal Button“ in der Modal-Box anzeigen',
                'value' => true,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $form->setElement(
            'select',
            'paypalStatusId',
            array(
                'label' => 'Zahlungsstatus nach der kompletten Zahlung',
                'value' => 12,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $form->setElement(
            'select',
            'paypalPendingStatusId',
            array(
                'label' => 'Zahlungsstatus nach der Autorisierung',
                'value' => 18,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $form->setElement(
            'boolean',
            'paypalSendInvoiceId',
            array(
                'label' => 'Bestellnummer an PayPal übertragen',
                'description' => 'Achtung: Aktivieren Sie diese Option nur in Absprache mit ihrem Shopware-Partner oder dem Shopware-Support. Durch diese Option wird die Bestellung vor der Weiterleitung an PayPal erzeugt und kann daher Bestellungen ohne gültige Zahlungen erzeugen. Zusätzlich kann es zu Abbrüchen führen, wenn Sie die Bestellnummer schon einmal in ihrem PayPal-Account benutzt haben.',
                'value' => false,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $form->setElement(
            'text',
            'paypalPrefixInvoiceId',
            array(
                'label' => 'Bestellnummer für PayPal mit einem Shop-Prefix versehen',
                'description' => 'Wenn Sie Ihren PayPal-Account für mehrere Shops nutzen, können Sie vermeiden, dass es Überschneidungen bei den Bestellnummern gibt, indem Sie hier ein eindeutiges Prefix definieren.',
                'emptyText' => 'MeinShop_',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                'vtype' => 'alphanum'
            )
        );
    }

    private function createMyTranslations()
    {
        $form = $this->Form();
        $translations = array(
            'en_GB' => array(
                'paypalUsername' => 'API username',
                'paypalPassword' => 'API password',
                'paypalSignature' => 'API signature',
                'paypalButtonApi' => '<strong>Get API signature now</strong>',
                'paypalClientId' => 'REST-API client ID',
                'paypalSecret' => 'REST-API secret',
                'paypalButtonRestApi' => '<strong>Get REST API data now</strong>',
                'paypalSandbox' => 'Activate sandbox mode',
                'paypalTimeout' => 'API timeout in seconds',
                'paypalCurl' => 'Use CURL (if available)',
                'paypalButtonClientTest' => '<strong>Test the API now</strong>',
                'paypalErrorMode' => 'Display error mesages',
                'paypalBrandName' => 'Alternative shop name for PayPal',
                'paypalLocaleCode' => 'Alternative language (locale)',
                'paypalLogoImage' => 'Shop logo for PayPal',
                'paypalCartBorderColor' => 'Cart color for PayPal',
                'paypalFrontendLogo' => 'Show payment logo in frontend',
                'paypalPaymentAction' => 'Payment acquisition',
                'paypalBillingAgreement' => 'Billing agreement / activate "Buy it now"',
                'paypalTransferCart' => 'Transafer cart to PayPal',
                'paypalExpressButton' => 'Show express purchase button in basket',
                'paypalExpressButtonLayer' => 'Show express purchase button in modalbox',
                'paypalStatusId' => 'Payment state after completing the transaction',
                'paypalPendingStatusId' => 'Payment state after being authorized',
                'paypalSendInvoiceId' => 'Transfer order number to PayPal',
                'paypalPrefixInvoiceId' => 'Add shop prefix to the order number'
            )
        );
        $descriptionTranslations = array(
            'en_GB' => array(
                'paypalBillingAgreement' => 'Important: This function must first be activated in your PayPal account.',
                'paypalClientId' => 'These credentials are only required if you want to install and use PayPal PLUS. For more information, please contact your PayPal representative.',
                'paypalTransferCart' => 'Note: The number of positions is technically limited to 24 units. With net orders, it’s possible that pieces of unit prices will not be transferred, since these can have more than two decimal places in Shopware.',
                'paypalSendInvoiceId' => 'Important: Only activate this option in consultation with your Shopware partner or Shopware support. This option places the order before forwarding to PayPal, which can lead to orders being placed without valid payment. In addition, it may lead to cancellations if the order number has already been used in your PayPal account.',
                'paypalPrefixInvoiceId' => 'If your PayPal account is used for multiple shops, you can avoid overlapping any order numbers by defining a unique prefix here.',
            )
        );
        $shopRepository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Locale');
        foreach ($translations as $locale => $snippets) {
            /** @var Shopware\Models\Shop\Locale $localeModel */
            $localeModel = $shopRepository->findOneBy(array('locale' => $locale));
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
                if (isset($descriptionTranslations[$locale][$element])) {
                    $translationModel->setDescription($descriptionTranslations[$locale][$element]);
                }
                $elementModel->addTranslation($translationModel);
            }
        }
    }

    private function createMyAttributes()
    {
        try {
            $this->get('models')->addAttribute(
                's_order_attributes',
                'swag_payal',
                'billing_agreement_id',
                'VARCHAR(255)'
            );
        } catch (Exception $e) {
        }
        try {
            $this->get('models')->addAttribute('s_order_attributes', 'swag_payal', 'express', 'int(11)');
        } catch (Exception $e) {
        }

        $this->get('models')->generateAttributeModels(array('s_order_attributes'));
    }

    private function removeMyAttributes()
    {
        /** @var $modelManager \Shopware\Components\Model\ModelManager */
        $modelManager = $this->get('models');
        try {
            $modelManager->removeAttribute('s_order_attributes', 'swag_payal', 'billing_agreement_id');
            $modelManager->removeAttribute('s_order_attributes', 'swag_payal', 'express');
            $modelManager->generateAttributeModels(array('s_order_attributes'));
        } catch (Exception $e) {
        }
    }

    /**
     * @param bool $responsive
     */
    public function registerMyTemplateDir($responsive = false)
    {
        if ($responsive) {
            $this->get('template')->addTemplateDir(__DIR__ . '/Views/responsive/', 'paypal_responsive');
        }
        $this->get('template')->addTemplateDir(__DIR__ . '/Views/', 'paypal');
    }

    /**
     * Returns the path to a frontend controller for an event.
     *
     * @return string
     */
    public function onGetControllerPathFrontend()
    {
        $templateVersion = $this->get('shop')->getTemplate()->getVersion();
        $this->registerMyTemplateDir($templateVersion >= 3);

        return __DIR__ . '/Controllers/Frontend/PaymentPaypal.php';
    }

    /**
     * Returns the path to a backend controller for an event.
     *
     * @return string
     */
    public function onGetControllerPathBackend()
    {
        $this->registerMyTemplateDir();
        $this->Application()->Snippets()->addConfigDir(__DIR__ . '/Snippets/');

        return __DIR__ . '/Controllers/Backend/PaymentPaypal.php';
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function onPostDispatch(Enlight_Event_EventArgs $args)
    {
        static $subscriber;
        if (!isset($subscriber)) {
            require_once __DIR__ . '/Subscriber/Frontend.php';
            $subscriber = new \Shopware\SwagPaymentPaypal\Subscriber\Frontend($this);
        }
        $subscriber->onPostDispatch($args);
    }

    /**
     * @param $args
     */
    public function onExtendBackendIndex($args)
    {
        static $subscriber;
        if (!isset($subscriber)) {
            require_once __DIR__ . '/Subscriber/BackendIndex.php';
            $subscriber = new \Shopware\SwagPaymentPaypal\Subscriber\BackendIndex($this);
        }
        $subscriber->onPostDispatchBackendIndex($args);
    }

    /**
     * Provide the file collection for less
     *
     * @param Enlight_Event_EventArgs $args
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function addLessFiles(Enlight_Event_EventArgs $args)
    {
        $less = new \Shopware\Components\Theme\LessDefinition(
            array(),
            array(__DIR__ . '/Views/responsive/frontend/_public/src/less/all.less'),
            __DIR__
        );

        return new Doctrine\Common\Collections\ArrayCollection(array($less));
    }

    /**
     * @param   string $paymentStatus
     * @return  int
     */
    public function getPaymentStatusId($paymentStatus)
    {
        switch ($paymentStatus) {
            case 'Completed':
                $paymentStatusId = $this->Config()->get('paypalStatusId', 12);
                break;
            case 'Pending':
            case 'In-Progress':
                $paymentStatusId = $this->Config()->get('paypalPendingStatusId', 18);
                break; //Reserviert
            case 'Processed':
                $paymentStatusId = 18;
                break; //In Bearbeitung > Reserviert
            case 'Refunded':
                $paymentStatusId = 20;
                break; //Wiedergutschrift
            case 'Partially-Refunded':
                $paymentStatusId = 20;
                break; //Wiedergutschrift
            case 'Cancelled-Reversal':
                $paymentStatusId = 12;
                break;
            case 'Expired':
            case 'Denied':
            case 'Voided':
                $paymentStatusId = 17;
                break; //Offen
            case 'Reversed':
            default:
                $paymentStatusId = 21;
                break;
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
        $orderId = $this->get('db')->fetchOne($sql, array($transactionId));
        $order = Shopware()->Modules()->Order();
        $order->setPaymentStatus($orderId, $paymentStatusId, false, $note);
        if ($paymentStatusId == 21) {
            $sql = 'UPDATE  s_order
                    SET internalcomment = CONCAT(internalcomment, :pStatus)
                    WHERE transactionID = :transactionId';
            $this->get('db')->query(
                $sql,
                array('pStatus' => "\nPayPal Status: " . $paymentStatus, 'transactionId' => $transactionId)
            );
        }
        if ($paymentStatus == 'Completed') {
            $sql = '
                UPDATE s_order SET cleareddate=NOW()
                WHERE transactionID=?
                AND cleareddate IS NULL LIMIT 1
            ';
            $this->get('db')->query($sql, array($transactionId));
        }
    }

    /**
     * @param bool $short
     * @return string
     */
    public function getLocaleCode($short = false)
    {
        $locale = $this->Config()->get('paypalLocaleCode');
        if (empty($locale)) {
            $locale = $this->get('locale')->toString();
            list($l, $c) = explode('_', $locale);
            if ($short && $c !== null) {
                $locale = $c;
            } elseif ($l == 'de') {
                $locale = 'de_DE';
            }
        }

        return $locale;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'PayPal';
    }

    /**
     * Returns the version of plugin as string.
     *
     * @throws Exception
     * @return string
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . '/plugin.json'), true);
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
            'description' => file_get_contents(__DIR__ . '/info.txt')
        );
    }

    /**
     * Creates and returns the paypal client for an event.
     *
     * @return \Shopware_Components_Paypal_Client
     */
    public function onInitResourcePaypalClient()
    {
        require_once __DIR__ . '/Components/Paypal/RestClient.php';
        require_once __DIR__ . '/Components/Paypal/Client.php';
        $client = new Shopware_Components_Paypal_Client($this->Config());

        return $client;
    }

    /**
     * Creates and returns the paypal rest client for an event.
     *
     * @return \Shopware_Components_Paypal_RestClient
     */
    public function onInitResourcePaypalRestClient()
    {
        require_once __DIR__ . '/Components/Paypal/RestClient.php';


        $rootDir = Shopware()->Container()->getParameter('kernel.root_dir');
        $caPath = $rootDir . '/engine/Shopware/Components/HttpClient/cacert.pem';
        if (!is_readable($caPath)) {
            $caPath = null;
        }

        $client = new Shopware_Components_Paypal_RestClient($this->Config(), $caPath);

        return $client;
    }

    /**
     * @return bool
     */
    public function isAtLeastShopware42()
    {
        return $this->assertMinimumVersion('4.2.0');
    }

    /**
     * @return bool
     */
    public function isShopware51()
    {
        return $this->assertMinimumVersion('5.1.0');
    }
}
