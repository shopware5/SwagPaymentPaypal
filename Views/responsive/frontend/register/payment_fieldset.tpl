{extends file="parent:frontend/register/payment_fieldset.tpl"}

{block name="frontend_register_payment_fieldset_description"}
    {block name="frontend_register_paypal_payment_fieldset_description"}
        {if $payment_mean.name == 'paypal'}
            {include file="frontend/swag_paypal/register/payment_fieldset.tpl"}
        {else}
            {$smarty.block.parent}
        {/if}
    {/block}
{/block}
