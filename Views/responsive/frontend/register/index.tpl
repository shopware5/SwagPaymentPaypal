{extends file="parent:frontend/register/index.tpl"}

{block name="frontend_register_login_form" append}
    <div id="paypal-login--button"></div>
{/block}


{block name="frontend_index_header_javascript" append}
    {if $PaypalLogIn}
        <script src="https://www.paypalobjects.com/js/external/api.js"></script>
        <script>
            if (paypal) {
                paypal.use(["login"], function (login) {
                    var r = login.render({
                        "appid": "{$PaypalClientId}",
                        {if $PaypalSandbox}
                        "authend": "sandbox",
                        {/if}
                        "scopes": "openid profile email address phone https://uri.paypal.com/services/paypalattributes{if $PaypalSeamlessCheckout} https://uri.paypal.com/services/expresscheckout{/if}",
                        "containerid": "paypal-login--button",
                        "locale": "{$PaypalLocale|replace:'_':'-'|strtolower}",
                        "returnurl": "{url controller=payment_paypal action=login}"
                    });
                });
            }
        </script>
    {/if}
{/block}
