/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

Ext.define('Shopware.apps.PaymentPaypal.store.main.List', {
	extend: 'Ext.data.Store',
	model: 'Shopware.apps.PaymentPaypal.model.main.List',
	proxy: {
        type: 'ajax',
        url : '{url action=getList}',
        reader: {
            type: 'json',
            root: 'data'
        }
    },
    remoteSort: true,
    remoteFilter: true
});
