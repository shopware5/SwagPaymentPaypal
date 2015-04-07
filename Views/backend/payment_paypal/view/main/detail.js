/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//{namespace name=backend/payment_paypal/view/main}

//{block name="backend/payment_paypal/view/main/detail"}
Ext.define('Shopware.apps.PaymentPaypal.view.main.Detail', {
    extend: 'Ext.form.Panel',
    alias: 'widget.paypal-main-detail',

    layout: 'anchor',
    border: false,
    width: 500,

    title: '{s name=detail/title}Details{/s}',

    autoScroll: true,
    bodyPadding: 5,
    collapsible: true,
    disabled: true,

    defaults: {
        xtype: 'fieldset',
        layout: 'form',
        defaults: {
            anchor: '100%',
            labelWidth: 135,
            xtype: 'textfield',
            readOnly: true,
            hideEmptyLabel: false
        }
    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: me.getItems(),
            buttons: me.getButtons()
        });

        me.callParent(arguments);
    },

    /**
     * @return Array
     */
    getButtons: function() {
        var me = this;
        return [{
            text: '{s name=detail/button/void_text}Cancel booking{/s}',
            cls: 'secondary', //DoVoid
            hidden: true,
            action: 'void'
        },{
            text: '{s name=detail/button/auth_text}Extends booking{/s}',
            cls: 'primary',
            hidden: true,
            action: 'auth' // DoReauthorization
        },{
            text: '{s name=detail/button/refund_text}Refund{/s}',
            cls: 'secondary',
            action: 'refund' // Refund
        },{
            text: '{s name=detail/button/capture_text}Capture{/s}',
            cls: 'primary', //DoCapture
            hidden: true,
            action: 'capture'
        },{
            text: '{s name=detail/button/book_text}Authorize{/s}',
            cls: 'primary',
            action: 'book' // DoAuthorization
        }];
    },

    /**
     * @return Array
     */
    getItems: function() {
        var me = this;
        return [{
            title: '{s name=detail/order_data/title}Order data{/s}',
            items: [{
                name: 'orderNumber',
                fieldLabel: '{s name=detail/order_data/order_number}Order number{/s}'
            }, {
                xtype: 'base-element-datetime',
                name: 'orderDate',
                fieldLabel: '{s name=detail/order_data/order_date}Order date{/s}'
            }, {
                name: 'statusDescription',
                fieldLabel: '{s name=detail/order_data/order_status}Order status{/s}'
            }, {
                name: 'customer',
                fieldLabel: '{s name=detail/order_data/customer}Customer{/s}'
            }, {
                name: 'currency',
                fieldLabel: '{s name=detail/order_data/currency}Currency{/s}'
            }, {
                name: 'amountFormat',
                fieldLabel: '{s name=detail/order_data/amount}Amount{/s}'
            }, {
                name: 'express',
                fieldLabel: '{s name=detail/order_data/order_type}Order type{/s}',
                readOnly: true,
                store: [[0, 'Normal'], [1, 'Express'], [2, 'Plus']],
                xtype: 'combobox'
            }]
        },{
            title: '{s name=detail/payment_data/title}Payment data{/s}',
            items: [{
                name: 'transactionId',
                fieldLabel: '{s name=detail/payment_data/transaction_id}Transaction ID{/s}'
            }, {
                xtype: 'base-element-datetime',
                name: 'clearedDate',
                fieldLabel: '{s name=detail/payment_data/cleared_date}Book date{/s}'
            }, {
                xtype: 'base-element-datetime',
                name: 'paymentDate',
                fieldLabel: '{s name=detail/payment_data/date}Payment date{/s}'
            }, {
                name: 'paymentStatus',
                fieldLabel: '{s name=detail/payment_data/status}Payment status{/s}'
            }, {
                name: 'pendingReason',
                fieldLabel: '{s name=detail/payment_data/pending_reason}Pending reason{/s}'
            }, {
                name: 'accountName',
                fieldLabel: '{s name=detail/payment_data/name}Sender name{/s}'
            }, {
                name: 'accountEmail',
                fieldLabel: '{s name=detail/payment_data/mail}Sender mail{/s}'
            }, {
                name: 'paymentCurrency',
                fieldLabel: '{s name=detail/payment_data/currency}Currency{/s}'
            }, {
                name: 'paymentAmountFormat',
                fieldLabel: '{s name=detail/payment_data/amount}Amount{/s}'
            }, {
                xtype: 'hidden',
                hidden: true,
                name: 'paymentAmount'
            }]
        }, {
            title: '{s name=detail/address_data/title}Seller Protection Address{/s}',
            items: [{
                name: 'addressStatus',
                fieldLabel: '{s name=detail/address_data/status}Status{/s}'
            }, {
                name: 'addressName',
                fieldLabel: '{s name=detail/address_data/name}Name{/s}'
            }, {
                name: 'addressStreet',
                fieldLabel: '{s name=detail/address_data/street}Street{/s}'
            }, {
                name: 'addressCity',
                fieldLabel: '{s name=detail/address_data/city}Zip / City{/s}'
            }, {
                name: 'addressCountry',
                fieldLabel: '{s name=detail/address_data/country}Country{/s}'
            }, {
                name: 'addressPhone',
                fieldLabel: '{s name=detail/address_data/phone}Phone number{/s}'
            }]
        }, {
            xtype: 'grid',
            title: 'Transactions',
            margin: '10 0 0 0',
            bodyPadding: 0,
            border: false,
            columns: [{
                header: '{s name=detail/transactions/id}Id{/s}',
                dataIndex: 'id',
                hidden: true,
                flex: 2
            },{
                xtype: 'datecolumn',
                format: Ext.Date.defaultFormat + ' H:i:s',
                header: '{s name=detail/transactions/date}Date{/s}',
                dataIndex: 'date',
                flex: 2
            },{
                header: '{s name=detail/transactions/type}Type{/s}',
                dataIndex: 'type',
                flex: 1
            },{
                header: '{s name=detail/transactions/status}Status{/s}',
                dataIndex: 'status',
                flex: 1
            }, {
                header: '{s name=detail/transactions/currency}Currency{/s}',
                dataIndex: 'currency',
                flex: 1
            }, {
                header: '{s name=detail/transactions/amount}Amount{/s}',
                dataIndex: 'amountFormat',
                align: 'right',
                flex: 1
            }, {
                xtype:'actioncolumn',
                width: 35,
                sortable: false,
                items: [{
                    iconCls: 'sprite-minus-circle',
                    tooltip: '{s name=detail/button/refund_text}Refund{/s}',
                    getClass: function(value, metadata, record) {
                        if(record.get('type') != 'Payment' || record.get('status') == 'Refunded') {
                            return 'x-hidden';
                        }
                    },
                    handler: function (view, rowIndex, colIndex, item, opts, record) {
                        me.fireEvent('refund', {
                            action: 'refund',
                            text: this.items[0].tooltip,
                            transactionId: record.get('id'),
                            paymentAmount: record.get('amount')
                        });
                    }
                }]
            }]
        }];
    }
});
//{/block}
