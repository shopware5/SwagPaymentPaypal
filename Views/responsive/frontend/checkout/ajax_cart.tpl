{extends file="parent:frontend/checkout/ajax_cart.tpl"}

{block name="frontend_checkout_ajax_cart_button_container_inner"}
    {$smarty.block.parent}
    {if $sBasket.content}
        {block name="frontend_checkout_ajax_cart_includes_paypal_express"}
            {include file="frontend/_includes_paypal/express.tpl"}
        {/block}
    {/if}
{/block}
