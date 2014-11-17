/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//{block name="backend/payment_paypal/application"}
Ext.define('Shopware.apps.PaymentPaypal', {

    extend: 'Enlight.app.SubApplication',

    bulkLoad: true,
    loadPath: '{url action=load}',

    params: {},

    controllers: [ 'Main' ],

    stores: [
        'main.List'
    ],
    models: [
        'main.List'
    ],
    views: [
        'main.Window', 'main.List'
    ],

    /**
     * This method will be called when all dependencies are solved and
     * all member controllers, models, views and stores are initialized.
     */
    launch: function() {
        var me = this;
        me.controller = me.getController('Main');
        return me.controller.mainWindow;
    }
});
//{/block}

