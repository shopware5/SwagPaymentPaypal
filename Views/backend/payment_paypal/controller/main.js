/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//{namespace name=backend/payment_paypal/view/main}
//{block name="backend/payment_paypal/controller/main"}
Ext.define('Shopware.apps.PaymentPaypal.controller.Main', {
    extend: 'Enlight.app.Controller',

    refs: [
        { ref: 'window', selector: 'paypal-main-window' },
        { ref: 'detail', selector: 'paypal-main-detail' },
        { ref: 'list', selector: 'paypal-main-list' },
        { ref: 'shopCombo', selector: 'paypal-main-list [name=shopId]' },
        { ref: 'balance', selector: 'paypal-main-list field[name=balance]' },
        { ref: 'transactions', selector: 'paypal-main-detail grid' }
    ],

    stores: [
        'main.List', 'main.Balance', 'main.Detail'
    ],
    models: [
        'main.List', 'main.Balance', 'main.Detail', 'main.Transaction'
    ],
    views: [
        'main.Window', 'main.List', 'main.Detail', 'main.Action'
    ],

    snippets:  {
        error: {
            title: '{s name="balanceErrorTitle"}Error{/s}',
            general: '{s name="errorMessageGeneral"}<b>Possible cause:</b><br>[0]<br><br><b>Actual error message:</b><br>[1]{/s}',
            10011: '{s name="invalidTransaction"}The transactionId you passed is not valid or not known to PayPal{/s}',
            10007: '{s name="permissionDenied"}The PayPal credentials you configured are not valid for this Transaction{/s}',
            10002: '{s name="securityError"}Your PayPal credentials are not valid.{/s}'
        }
    },

    /**
     * The main window instance
     * @object
     */
    mainWindow: null,

    init: function () {
        var me = this;

        // Init main window
        me.mainWindow = me.getView('main.Window').create({
            autoShow: true,
            scope: me
        });

        me.detailStore = me.getStore('main.Detail');

        me.getStore('main.Balance').load({
            callback: me.onLoadBalance,
            scope: this
        });

        // Register events
        me.control({
            'paypal-main-list': {
                selectionchange: me.onSelectionChange,
                shopSelectionChanged: me.onShopSelectionChanged
            },
            'paypal-main-detail button': {
                click: me.onClickDetailButton
            },
            'paypal-main-detail': {
                refund: me.onClickDetailButton
            },
            'paypal-main-list [name=searchfield]': {
                change: me.onSearchForm
            }
        });
    },

    /**
     * Returns the currently selected shop.
     *
     * If there is no vaild shop selected, the first shop is returned, if that fails, 0 is returned.
     * In the later case, the controller should select the shop via getActiveDefault()
     *
     * @returns int
     */
    getSelectedShop: function() {
        var me = this,
            shopCombo = me.getShopCombo(),
            shopId = shopCombo.getValue(),
            first = shopCombo.store.first();

        if (typeof(shopId) != "number") {
            if (first && first.get('id')) {
                return first.get('id');
            }
            return 0;
        }

        return shopId;
    },

    /**
     * Callback function called when the user changed the shop selection combo
     *
     * @param shopId
     */
    onShopSelectionChanged: function(shopId) {
        var me = this,
            grid = me.getList(),
            store = grid.store;

        if (typeof(shopId) != "number" && shopId != '' && shopId != null) {
            return;
        }
        store.clearFilter(true);
        store.filter('shopId', shopId);

        me.getStore('main.Balance').getProxy().extraParams['shopId'] = shopId;
        me.getStore('main.Balance').load({
            callback: me.onLoadBalance,
            scope: this
        });
    },

    /**
     * Callback function triggered when the user enters something into the search field
     *
     * @param field
     * @param value
     */
    onSearchForm: function(field, value) {
        var me = this;
        var store = me.getStore('main.List');
        if (value.length === 0 ) {
            store.load();
        } else {
            store.load({
                filters : [{
                    property: 'search',
                    value: '%' + value + '%'
                }]
            });
        }
    },

    /**
     * Callback function triggered when the user clicks one of the action buttons in the detail form
     *
     * @param button
     */
    onClickDetailButton: function(button) {
        var me = this,
            detail = me.getDetail(),
            detailData = detail.getForm().getFieldValues(),
            action;
        detailData.transactionId = button.transactionId || detailData.transactionId;
        detailData.paymentAmount = button.paymentAmount || detailData.paymentAmount;
        action = me.getView('main.Action').create({
            paymentAction: button.action,
            paymentActionName: button.text,
            detailData: detailData
        });

        action.on('destroy', function() {
            me.getList().getStore().load();
        })
    },

    /**
     * Callback function triggered when the user clicks on an entry in the list
     *
     * @param table
     * @param records
     */
    onSelectionChange: function(table, records) {
        var me = this,
            formPanel = me.getDetail(),
            record = records.length ? records[0] : null;

        var shopId = me.getSelectedShop();


        if(record) {
            formPanel.setLoading(true);
            formPanel.loadRecord(record);
            me.detailStore.load({
                extraParams: {
                    'shopId': shopId
                },
                filters : [{
                    property: 'transactionId',
                    value: record.get('transactionId')
                }],
                callback: me.onLoadDetail,
                scope: me
            });
            formPanel.enable();
        } else {
            formPanel.disable();
        }
    },

    /**
     * Displays a sticky notification if available. Else the default growlmessage is shown
     *
     * @param title
     * @param message
     */
    showGrowlMessage: function(title, message) {
        if (typeof Shopware.Notification.createStickyGrowlMessage == 'function') {
            Shopware.Notification.createStickyGrowlMessage({
                title: title,
                text:  message
            });
        } else {
            Shopware.Notification.createGrowlMessage(title, message);
        }
    },

    /**
     * Convenience function which will look up any given error code in order to give the user some more
     * info about what happened and what he can do about it.
     *
     * @param title
     * @param error
     * @param code
     */
    showPayPalErrorMessage: function(title, error, code) {
        var me = this,
            message;

        if (!code || !me.snippets.error[code]) {
            message = error;
        } else {
            message = Ext.String.format(me.snippets.error.general, me.snippets.error[code], error);
        }

        me.showGrowlMessage(title, message);
    },

    /**
     * Callback function for the "load paypal balance" ajax request
     *
     * @param records
     * @param operation
     * @param success
     */
    onLoadBalance: function(records, operation, success) {
        var me = this,
            error, errorCode;

        if (!success) {
            error = operation.request.proxy.reader.rawData.error;
            errorCode = operation.request.proxy.reader.rawData.errorCode;

            me.showPayPalErrorMessage(me.snippets.error.title, error, errorCode);
        }else if(records.length) {
            var record = records[0];
            me.getBalance().setValue(record.get('balanceFormat'));
        }
    },

    /**
     * Callback function for the "load details" ajax request
     *
     * @param records
     * @param operation
     * @param success
     */
    onLoadDetail: function(records, operation, success) {
        var me = this,
            formPanel = me.getDetail(),
            detail = (records && records.length) ? records[0] : null,
            status, pending, fields,
            error, errorCode;


        if(!detail) {
            formPanel.disable();
            formPanel.setLoading(false);
            if (!success) {
                error = operation.request.proxy.reader.rawData.message;
                errorCode = operation.request.proxy.reader.rawData.errorCode;
                me.showPayPalErrorMessage(me.snippets.error.title, error, errorCode);
            }
            return;
        }
        formPanel.loadRecord(detail);
        me.getTransactions().reconfigure(
            detail.getTransactions()
        );
        status = detail.get('paymentStatus');
        pending = detail.get('pendingReason');
        fields = formPanel.query('button');
        Ext.each(fields, function(field) {
            field.hide();
        });
        switch(status) {
            case 'Expired':
                formPanel.down('[action=auth]').show();
                break;
            case 'Completed':
            case 'PartiallyRefunded':
            case 'Canceled-Reversal ':
                formPanel.down('[action=refund]').show();
                break;
            case 'Pending':
            case 'In-Progress':
                if(pending == 'order') {
                    formPanel.down('[action=book]').show();
                } else {
                    formPanel.down('[action=capture]').show();
                }
                formPanel.down('[action=void]').show();
                break;
        }
        formPanel.setLoading(false);
    }
});
//{/block}
