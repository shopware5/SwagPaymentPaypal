{if $PaypalShowButton}
    <div class="basket_button_paypal{if !$PaypalLocale || $PaypalLocale == 'de_DE'}_de{/if}">
        <a href="{url controller=payment_paypal action=express forceSecure}">
            {if !$PaypalLocale || $PaypalLocale == 'de_DE'}
                <img src="{link file="frontend/_resources/images/paypal_expresscheckout.png"}">
            {else}
                <img src="https://www.paypal.com/{$PaypalLocale}/i/btn/btn_xpressCheckout.gif">
            {/if}
        </a>
        {if !$PaypalLocale || $PaypalLocale == 'de_DE'}
            {s name=PaymentButtonDelimiterDe}<span class="paypal_button_delimiter">oder</span>{/s}
        {else}
            {s name=PaymentButtonDelimiter force}<span class="paypal_button_delimiter">or</span>{/s}
        {/if}
    </div>
{/if}
