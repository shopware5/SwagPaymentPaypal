{extends file='frontend/index/index.tpl'}

{* Breadcrumb *}
{block name='frontend_index_start' append}
    {$sBreadcrumb = [['name'=>"{s name='PaypalBreadcrumbTitle'}{/s}"]]}
{/block}

{* Main content *}
{block name='frontend_index_content'}
    <div class="paypal-content content custom-page--content">

        {$cancelMessage = "{s name='PaypalCancelMessage'}{/s}"}
        {include file="frontend/_includes/messages.tpl" type="info" content="{$cancelMessage}"}

        <div class="paypal-content--actions">
            <a class="btn"
               href="{url controller=checkout action=cart}"
               title="{s name='PaypalLinkChangeCart'}{/s}">
                {s name='PaypalLinkChangeCart'}{/s}
            </a>
            <a class="btn is--primary right"
               href="{url controller=checkout action=shippingPayment sTarget=checkout}"
               title="{s name='PaypalLinkChangePaymentMethod'}{/s}">
                {s name='PaypalLinkChangePaymentMethod'}{/s}
            </a>
        </div>

    </div>
{/block}

{block name='frontend_index_actions'}{/block}
