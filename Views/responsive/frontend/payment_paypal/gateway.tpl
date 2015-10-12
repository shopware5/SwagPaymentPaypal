{extends file='frontend/index/index.tpl'}

{block name='frontend_index_content_left'}{/block}

{* Breadcrumb *}
{block name='frontend_index_start' append}
    {$sBreadcrumb = [['name'=>"{s name='PaypalBreadcrumbTitle'}{/s}"]]}
{/block}

{* Main content *}
{block name="frontend_index_content"}
    <div class="content block">

        <h1 class="paypal-gateway--title">
            {s name="PaypalGatewayHeader"}{/s}
        </h1>

        <div class="paypal-gateway--loader">
            <div class="js--loading-indicator indicator--relative">
                <i class="icon--default"></i>
            </div>

            <p class="paypal-gateway--loader-text">
                {s name="PaypalGatewayInfoWait"}{/s}
            </p>

            <p class="paypal-gateway--fallback">
                {s name="PaypalGatewayInfoFallback"}{/s}
            </p>
        </div>

    </div>
{/block}

{block name="frontend_index_header_meta_http_tags" append}
    <meta http-equiv="refresh" content="0; url={$PaypalGatewayUrl}">
{/block}
