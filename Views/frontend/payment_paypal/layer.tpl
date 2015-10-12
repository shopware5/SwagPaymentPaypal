{if $PaypalShowButton}
    <div class="right modal_paypal_button{if !$PaypalLocale || $PaypalLocale == 'de_DE'}_de{/if}">
        <a href="{url controller=payment_paypal action=express forceSecure}">
            {if !$PaypalLocale || $PaypalLocale == 'de_DE'}
                <img src="{link file="frontend/_resources/images/paypal_expresscheckout.png"}">
            {else}
                <img src="https://www.paypal.com/{$PaypalLocale}/i/btn/btn_xpressCheckout.gif">
            {/if}
        </a>
    </div>
{/if}
