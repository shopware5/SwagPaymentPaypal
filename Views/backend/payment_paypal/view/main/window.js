/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//{namespace name=backend/payment_paypal/view/main}

//{block name="backend/config/view/main/window"}
Ext.define('Shopware.apps.PaymentPaypal.view.main.Window', {
    extend: 'Enlight.app.Window',
    alias: 'widget.paypal-main-window',

    width: 1200,
    height: 500,
    layout: 'border',

    title: '{s name=window/title}PayPal Payments{/s}',

    /**
     *
     */
    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: me.getItems()
        });

        me.callParent(arguments);
    },

    /**
     * @return array
     */
    getItems: function() {
        var me = this;
        return [{
            region: 'east',
            xtype: 'paypal-main-detail'
        }, {
            region: 'center',
            xtype: 'paypal-main-list'
        }];
    }
});
//{/block}
