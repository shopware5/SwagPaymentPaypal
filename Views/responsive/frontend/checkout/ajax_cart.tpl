{extends file="parent:frontend/checkout/ajax_cart.tpl"}

{block name="frontend_checkout_ajax_cart_button_container_inner" append}
    {if $sBasket.content}
        {include file="frontend/_includes_paypal/express.tpl"}
    {/if}
{/block}
