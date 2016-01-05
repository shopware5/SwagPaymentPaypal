{extends file='frontend/index/index.tpl'}

{* Breadcrumb *}
{block name='frontend_index_start' append}
    {$sBreadcrumb = [['name'=>"{s name='PaypalBreadcrumbTitle'}{/s}"]]}
{/block}

{* Main content *}
{block name='frontend_index_content'}
    <div class="paypal-content content custom-page--content">

        {if !empty($PaypalResponse.ACK) && $PaypalResponse.ACK == 'Failure'
        && ($PaypalConfig.paypalSandbox || $PaypalConfig.paypalErrorMode)}

            {$debugErrorMessage = "{s name='PaypalDebugErrorMessage'}{/s}"}
            {include file="frontend/_includes/messages.tpl" type="error" content="{$debugErrorMessage}"}

            {$i=0}{while isset($PaypalResponse["L_LONGMESSAGE{$i}"])}
                <p>[{$PaypalResponse["L_ERRORCODE{$i}"]}] - {$PaypalResponse["L_SHORTMESSAGE{$i}"]|escape|nl2br}. {$PaypalResponse["L_LONGMESSAGE{$i}"]|escape|nl2br}</p>
            {$i=$i+1}{/while}
        {else}
            {$errorMessage = "{s name='PaypalErrorMessage'}{/s}"}
            {include file="frontend/_includes/messages.tpl" type="error" content="{$errorMessage}"}
            <p>{s name='PaypalErrorInfo'}{/s}</p>
        {/if}

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
