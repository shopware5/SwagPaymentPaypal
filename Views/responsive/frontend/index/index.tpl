{extends file="parent:frontend/index/index.tpl"}

{block name="frontend_index_header_javascript" append}
    {if $PaypalLogIn && !$PaypalPlusApprovalUrl}
        <script src="https://www.paypalobjects.com/js/external/api.js"></script>
    {/if}
{/block}