{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name="frontend_checkout_payment_fieldset_description"}
    {block name="frontend_checkout_paypal_payment_fieldset_description"}
        {if $payment_mean.name == 'paypal'}
            {include file="frontend/swag_paypal/checkout/change_payment.tpl"}
        {else}
            {$smarty.block.parent}
        {/if}
    {/block}
{/block}
