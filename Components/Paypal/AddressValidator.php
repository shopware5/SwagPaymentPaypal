<?php

namespace Shopware\Plugins\SwagPaymentPaypal\Components\Paypal;

use Shopware\Bundle\AccountBundle\Service\Validator\AddressValidatorInterface;
use Shopware\Components\Api\Exception\ValidationException;
use Shopware\Components\DependencyInjection\Container as DIContainer;
use Shopware\Models\Customer\Address;
use Symfony\Component\Validator\ConstraintViolationInterface;

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
        if (!$this->container->get('front')->Request()
            || $this->container->get('front')->Request()->getControllerName() !== 'payment_paypal'
        ) {
            $this->innerValidator->validate($address);

            return;
        }

        try {
            $this->innerValidator->validate($address);
        } catch (ValidationException $exception) {
            $violations = $exception->getViolations();
            $allowedViolations = array('state', 'phone', 'additionalAddressLine1', 'additionalAddressLine2');

            /** @var $violation ConstraintViolationInterface */
            foreach ($violations->getIterator() as $violation) {
                if (!in_array($violation->getPropertyPath(), $allowedViolations)) {
                    throw $exception;
                }
            }

            return;
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
