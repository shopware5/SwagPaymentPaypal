{extends file="frontend/checkout/payment_fieldset.tpl"}

{block name='frontend_register_payment_fieldset_description'}
    {if $payment_mean.name == 'paypal'}
        <div class="grid_10 last">
            {include file="string:{$payment_mean.additionaldescription}"}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
