{extends file="parent:frontend/checkout/cart.tpl"}

{block name="frontend_checkout_cart_table_actions"}
    {$smarty.block.parent}
    {block name="frontend_checkout_paypal_payment_express_top"}
        <div class="paypal-express--container">
            {include file="frontend/_includes_paypal/express.tpl"}
        </div>
    {/block}
{/block}

{block name="frontend_checkout_cart_table_actions_bottom"}
    {$smarty.block.parent}
    {block name="frontend_checkout_paypal_payment_express_bottom"}
        <div class="paypal-express--container">
            {include file="frontend/_includes_paypal/express.tpl"}
        </div>
    {/block}
{/block}
