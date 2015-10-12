{extends file="parent:frontend/checkout/cart.tpl"}

{block name="frontend_checkout_cart_table_actions" append}
    <div class="paypal-express--container">
        {include file="frontend/_includes_paypal/express.tpl"}
    </div>
{/block}

{block name="frontend_checkout_cart_table_actions_bottom" append}
    <div class="paypal-express--container">
        {include file="frontend/_includes_paypal/express.tpl"}
    </div>
{/block}
