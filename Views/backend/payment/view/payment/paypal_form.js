//{namespace name="backend/payment_paypal/view/main"}
//{block name="backend/payment/view/payment/formpanel" append}
Ext.define('Shopware.apps.payment.view.payment.PaypalForm', {
    override: 'Shopware.apps.Payment.view.payment.FormPanel',
    /**
     * This function creates form items
     * @return Array
     */
    getItems: function(){
        var me = this, result;
        result = me.callParent(arguments);
        result.push({
            xtype: 'checkbox',
            fieldLabel: 'Zahlungsart ist bei „PayPal PLUS“ auswählbar',
            inputValue: 1,
            uncheckedValue: 0,
            name: 'attribute[paypalPlusActive]'
        });
        result.push({
            xtype: 'mediafield',
            multiSelect: false,
            fieldLabel: 'Bild welches bei „PayPal PLUS“ angezeigt wird',
            name: 'attribute[paypalPlusMedia]'
        });
        return result;
    }
});
//{/block}