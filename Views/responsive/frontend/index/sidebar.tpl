{extends file="parent:frontend/index/sidebar.tpl"}

{block name="frontend_index_left_menu"}
    {$smarty.block.parent}
    {block name="frontend_sidebar_paypal_payment_logo"}
        {include file="frontend/_includes_paypal/logo.tpl"}
    {/block}
{/block}
