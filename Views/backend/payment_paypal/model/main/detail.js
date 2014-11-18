/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

Ext.define('Shopware.apps.PaymentPaypal.model.main.Detail', {
    extend: 'Ext.data.Model',
	fields: [
        { name: 'transactionId', type: 'string' },
        //{ name: 'orderNumber', type: 'string' },

		{ name: 'addressStatus',  type: 'string' },
        { name: 'addressName',  type: 'string' },
        { name: 'addressStreet',  type: 'string' },
        { name: 'addressCity',  type: 'string' },
        { name: 'addressCountry',  type: 'string' },
        { name: 'addressPhone',  type: 'string' },

        { name: 'accountEmail',  type: 'string' },
        { name: 'accountName',  type: 'string' },
        { name: 'accountStatus',  type: 'string' },

        { name: 'protectionStatus',  type: 'string' },
        { name: 'paymentStatus',  type: 'string' },
        { name: 'pendingReason',  type: 'string' },

        { name: 'paymentDate',  type: 'date' },
        { name: 'paymentType',  type: 'string' },
        { name: 'paymentAmount',  type: 'string' },
        { name: 'paymentCurrency',  type: 'string' },
        { name: 'paymentAmountFormat', type: 'string' }
	],

    associations: [{
        type: 'hasMany', model: 'Shopware.apps.PaymentPaypal.model.main.Transaction',
        name: 'getTransactions', associationKey: 'transactions'
    }]
});
