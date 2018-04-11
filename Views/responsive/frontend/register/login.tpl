{extends file='parent:frontend/register/login.tpl'}

{block name='frontend_register_login_form'}
    {$smarty.block.parent}

    {block name='frontend_checkout_cart_table_actions_paypal_unified_ec_button'}
        <div class="paypal-express--container">
            {include file="frontend/_includes_paypal/express.tpl"}
        </div>
    {/block}
{/block}
