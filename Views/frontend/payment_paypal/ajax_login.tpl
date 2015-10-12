{block name="frontend_account_ajax_login_error_messages" append}
    <div class="paypal_login_container">
        <div id="paypal_login_button"></div>
    </div>

    <script>
        if (paypal) {
            paypal.use(["login"], function (login) {
                var r = login.render({
                    "appid": "{$PaypalClientId}",
                    {if $PaypalSandbox}
                    "authend": "sandbox",
                    {/if}
                    "scopes": "openid profile email address phone https://uri.paypal.com/services/paypalattributes{if $PaypalSeamlessCheckout} https://uri.paypal.com/services/expresscheckout{/if}",
                    "containerid": "paypal_login_button",
                    "locale": "{$PaypalLocale|replace:'_':'-'|strtolower}",
                    "returnurl": "{url controller=payment_paypal action=login forceSecure}"
                });
            });
        }
    </script>
{/block}
