/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

Ext.define('Shopware.apps.PaymentPaypal.model.main.List', {
	extend: 'Ext.data.Model',
	fields: [
		{ name: 'id', type: 'int' },
		{ name: 'userId',  type: 'string' },
		{ name: 'transactionId', type: 'string' },

		{ name: 'clearedId', type: 'int' },
		{ name: 'statusId', type: 'int' },
		{ name: 'clearedDescription', type: 'string' },
		{ name: 'statusDescription', type: 'string' },

		{ name: 'currency', type: 'string' },
		{ name: 'amount', type: 'float' },
		{ name: 'amountFormat', type: 'string' },
		{ name: 'customer', type: 'string' },
        { name: 'customerId', type: 'string' },
		{ name: 'orderDate', type: 'date' },
		{ name: 'clearedDate', type: 'date' },
		{ name: 'orderNumber', type: 'string' },
		{ name: 'shopId', type: 'int' },
		{ name: 'shopName', type: 'string' },
		{ name: 'paymentDescription', type: 'string' },
		{ name: 'paymentKey', type: 'string' },
		{ name: 'comment', type: 'string' },

		{ name: 'invoiceId', type: 'string' },
		{ name: 'invoiceHash', type: 'string' },
		{ name: 'trackingId', type: 'string' },
		{ name: 'dispatchId', type: 'int' },
		{ name: 'dispatchDescription', type: 'string' },
        { name: 'express', type: 'int'}
	]
});
