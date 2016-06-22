<?php

namespace Shopware\Plugins\SwagPaymentPaypal\Components\Paypal;

use Shopware\Bundle\AccountBundle\Service\Validator\AddressValidatorInterface;
use Shopware\Components\Api\Exception\ValidationException;
use Shopware\Components\DependencyInjection\Container as DIContainer;
use Shopware\Models\Customer\Address;

class AddressValidator implements AddressValidatorInterface
{
    /** @var  AddressValidatorInterface */
    private $innerValidator;

    /** @var  DIContainer $container */
    private $container;

    /**
     * PaypalAddressValidator constructor.
     *
     * @param AddressValidatorInterface $innerValidator
     * @param DIContainer $container
     */
    public function __construct(AddressValidatorInterface $innerValidator, DIContainer $container)
    {
        $this->innerValidator = $innerValidator;
        $this->container = $container;
    }

    /**
     * @param Address $address
     * @throws ValidationException
     */
    public function validate(Address $address)
    {
        if (!$this->container->get('front')
            || $this->container->get('front')->Request()->getControllerName() !== 'payment_paypal'
        ) {
            $this->innerValidator->validate($address);

            return;
        }

        try {
            $this->innerValidator->validate($address);
        } catch (ValidationException $exception) {
            $violations = $exception->getViolations();

            if ($violations->count() === 1 && $violations->get(0)->getPropertyPath() === 'state') {
                return;
            }

            throw $exception;
        }
    }

    /**
     * @param Address $address
     * @return bool
     */
    public function isValid(Address $address)
    {
        return $this->innerValidator->isValid($address);
    }
}
