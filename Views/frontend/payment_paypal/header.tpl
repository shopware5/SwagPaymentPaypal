{block name="frontend_index_header_css_screen" append}
    <link type="text/css" media="all" rel="stylesheet" href="{link file='frontend/_resources/styles/paypal.css'}"/>
{/block}
{block name="frontend_index_body_inline" append}
    {if $PaypalLogIn && !$PaypalPlusApprovalUrl}
        <script src="https://www.paypalobjects.com/js/external/api.js"></script>
    {/if}
{/block}
