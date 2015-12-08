<?php

class Shopware_Components_Paypal_Currency extends Enlight_Class
{
    protected $currencyId;

    public function __construct($config)
    {
        $this->currencyId = $config->get('paypalCurrency');
    }

    /**
     * Return currency information
     *
     * @return mixed
     */
    public function getCurrencyData()
    {
        if ($this->currencyId) {
            $currency = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Currency')->find($this->currencyId);
            if ($currency) {
                return $currency;
            }
        }

        return false;
    }

    /**
     * Convert price depending on currency factor
     *
     * @param $price
     * @param null $currencyFactor
     * @return float
     */
    public function priceConvert($price, $currencyFactor = null)
    {
        $price = str_replace(',', '.', $price);

        if ($currencyFactor == null) {
            return $price;
        }

        $shopCurrencyFactor = Shopware()->Shop()->getCurrency()->getFactor();
        $oldPrice = round(($price / $shopCurrencyFactor), 2, 0);
        $newPrice = round(($oldPrice * $currencyFactor), 2, 0);

        return $newPrice;
    }

}