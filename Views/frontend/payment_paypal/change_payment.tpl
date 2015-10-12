{extends file="frontend/checkout/payment_fieldset.tpl"}

{block name='frontend_register_payment_fieldset_description'}
    {if $payment_mean.name == 'paypal'}
        <div class="grid_10 last">
            <!-- PayPal Logo -->
            <a onclick="window.open(this.href, 'olcwhatispaypal','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=400, height=500'); return false;" href="https://www.paypal.com/de/cgi-bin/webscr?cmd=xpt/cps/popup/OLCWhatIsPayPal-outside" target="_blank">
                <img src="{link file='frontend/_resources/images/paypal_logo.png' fullPath}" alt="Logo 'PayPal empfohlen'">
            </a>
            <!-- PayPal Logo -->
            {include file="string:{$payment_mean.additionaldescription}"}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
