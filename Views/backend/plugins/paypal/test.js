var pnl = btn.up('panel');
var url = document.location.pathname + 'paymentPaypal/testClient';
var els = pnl.query('[isFormField]'),
    vls = {};

Ext.Array.each(els, function(el, i) {
    var v = el.getSubmitValue();
    if(v === false) { v = 0; }
    if(v === true) { v = 1; }
    vls[el.elementName] = v;
});

Ext.Ajax.request({
    url: url,
    params: vls,
    success: function (response) {
        var data = Ext.decode(response.responseText);
        if (data.ACK && data.ACK == 'Success') {
            data.ACK = '<span style=\"color: green;font-weight: bold;\">' + data.ACK + '</span>';
        }
        if (data.ACK && data.ACK != 'Success') {
            data.ACK = '<span style=\"color: red;font-weight: bold;\">' + data.ACK + '</span>';
        }
        if (data.message && data.message == 'OK') {
            data.message = '<span style=\"color: green;font-weight: bold;\">' + data.message + '</span>';
        }
        if (data.message && data.message != 'OK') {
            data.message = '<span style=\"color: red;font-weight: bold;\">' + data.message + '</span>';
        }
        var title = '<span style=\"font-weight: bold;\">' + btn.text + '</span>';
        var text = '';
        Ext.iterate(data, function (key, value) {
            text += '<strong>' + key + ':</strong> ' + value + '<br>';
        });
        Shopware.Notification.createStickyGrowlMessage({
            title: title,
            text: text,
            width: 440,
            log: false,
            btnDetail: {
                link: 'http://wiki.shopware.com/PayPal_detail_984.html'
            }
        });
    }
});