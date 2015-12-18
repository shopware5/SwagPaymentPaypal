/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//{namespace name=backend/payment_paypal/view/main}

Ext.define('Shopware.apps.PaymentPaypal.view.main.List', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.paypal-main-list',

    store: 'main.List',

//    layout: 'fit',
//    viewConfig: {
//        stripeRows: true
//    },

    initComponent: function() {
        var me = this;
        Ext.applyIf(me, {
            dockedItems: [
                me.getPagingToolbar(),
                me.getToolbar()
            ],
            columns: me.getColumns()
        });


        me.addEvents('shopSelectionChanged');


        this.callParent();
        me.store.clearFilter(true);
        me.store.load();
    },

    getColumns: function() {
        var me = this;
        return [{
            text: '{s name=list/columns/date_text}Order date{/s}',
            flex: 3,
            xtype: 'datecolumn',
            format: Ext.Date.defaultFormat + ' H:i:s',
            dataIndex: 'orderDate'
        },{
            text: '{s name=list/columns/order_number_text}Order number{/s}',
            flex: 2,
            dataIndex: 'orderNumber'
        },{
            text: '{s name=list/columns/transaction_id_text}Transaction ID{/s}',
            flex: 3,
            dataIndex: 'transactionId'
        },{
            text: '{s name=list/columns/payment_method_text}Payment method{/s}',
            flex: 2,
            dataIndex: 'paymentDescription'
        },{
            text: '{s name=list/columns/customer_text}Customer{/s}',
            flex: 3,
            dataIndex: 'customer'
        }, {
            header: '{s name=list/columns/shop_text}Shop{/s}',
            dataIndex: 'shopName',
            flex:2
        },{
            text: '{s name=list/columns/currency_text}Currency{/s}',
            flex: 2,
            dataIndex: 'currency'
        },{
            text: '{s name=list/columns/amount_text}Amount{/s}',
            flex: 2,
            align: 'right',
            renderer : function(value, column, model) {
                return model.data.amountFormat;
            },
            dataIndex: 'amount'
        },{
            text: '{s name=list/columns/order_status_text}Order status{/s}',
            flex: 2,
            dataIndex: 'statusId',
            renderer : function(value, column, model) {
                return model.data.statusDescription;
            }
        },{
            text: '{s name=list/columns/payment_status_text}Payment status{/s}',
            flex: 2,
            dataIndex: 'cleared',
            renderer : function(value, column, model) {
                return model.data.clearedDescription;
            }
        },{
            xtype:'actioncolumn',
            width: 70,
            sortable: false,
            items: [{

                iconCls: 'sprite-user--pencil',
                tooltip: '{s name=list/actioncolumn/customer_tooltip}Open customer details{/s}',
                handler: function(grid, rowIndex, colIndex) {
                    var record = grid.getStore().getAt(rowIndex);
                    Shopware.app.Application.addSubApplication({
                        name: 'Shopware.apps.Customer',
                        action: 'detail',
                        params: {
                            customerId: record.get('customerId')
                        }
                    });
                }
            }, {
                iconCls: 'sprite-sticky-notes-pin',
                tooltip: '{s name=list/actioncolumn/order_tooltip}Open order details{/s}',
                handler: function(grid, rowIndex, colIndex) {
                    var record = grid.getStore().getAt(rowIndex);
                    Shopware.app.Application.addSubApplication({
                        name: 'Shopware.apps.Order',
                        params: {
                            orderId: record.get('id')
                        }
                    });
                }
            }, {
                iconCls: 'sprite-document-invoice',
                tooltip: '{s name=list/actioncolumn/invoice_tooltip}Open invoice{/s}',
                getClass: function(value, metadata, record) {
                    if(!record.get('invoiceId')) {
                        return 'x-hidden';
                    }
                },
                handler: function(grid, rowIndex, colIndex) {
                    var record = grid.getStore().getAt(rowIndex),
                        link = "{url controller=order action=openPdf}"
                            + "?id=" + record.get('invoiceHash');
                    window.open(link, '_blank');
                }
            }]
        }];
    },

    getPagingToolbar: function() {
        var me = this;
        return {
            xtype: 'pagingtoolbar',
            displayInfo: true,
            store: me.store,
            dock: 'bottom'
        };
    },

    getToolbar: function() {
        var me = this;
        return {
            xtype: 'toolbar',
            ui: 'shopware-ui',
            dock: 'top',
            border: false,
            items: me.getTopBar()
        };
    },

    getTopBar:function () {
        var me = this,
            items = [],
            shopStore = Ext.create('Shopware.apps.Base.store.Shop');

        shopStore.clearFilter();

        items.push({
            fieldLabel: '{s name=list/balance/label}Your PayPal balance{/s}',
            labelWidth: 150,
            xtype: 'textfield',
            flex: 3,
            readOnly: true,
            name: 'balance'
        }, {
            xtype: 'combo',
            fieldLabel: '{s name=list/shop/label}Select shop{/s}',
            store: shopStore,
            checkChangeBuffer: 100,
            allowEmpty: true,
            emptyText: '{s name=list/showAll}Show all{/s}',
            displayField: 'name',
            valueField: 'id',
            flex: 2,
            name: 'shopId',
            listeners: {
                change: function(combo, newValue, oldValue, eOpts) {
                    me.fireEvent('shopSelectionChanged', newValue);
                }
            }
        }, '->', {
            xtype: 'textfield',
            name:'searchfield',
            cls:'searchfield',
            width: 100,
            emptyText:'{s name=list/search/text}Search...{/s}',
            enableKeyEvents:true,
            checkChangeBuffer:500
        }, {
            xtype:'tbspacer', width:6
        });
        return items;
    }
});
