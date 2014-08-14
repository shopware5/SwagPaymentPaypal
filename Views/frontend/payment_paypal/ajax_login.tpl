{block name="frontend_account_ajax_login_error_messages" append}
<div id="paypalLogin" style="left: 200px; position: absolute; top: 200px;"></div>
<script>
    paypal.use( ["login"], function(login) {
        var r = login.render ({
            "appid": "{$PaypalClientId}",
{if $PaypalSandbox}
            "authend": "sandbox",
{/if}
            "scopes": "openid profile email address phone https://uri.paypal.com/services/paypalattributes{if $PaypalSeamlessCheckout} https://uri.paypal.com/services/expresscheckout{/if}",
            "containerid": "paypalLogin",
            "locale": "{$PaypalLocale|replace:'_':'-'|strtolower}",
            "returnurl": "{url controller=payment_paypal action=login}"
        });
    });
</script>
{/block}