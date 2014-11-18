/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

Ext.define('Shopware.apps.PaymentPaypal.model.main.Balance', {
    extend: 'Ext.data.Model',
    fields: [
        { name: 'balance', type: 'float' },
        { name: 'balanceFormat', type: 'string' },
        { name: 'currency', type: 'string' },
        { name: 'default', type: 'boolean' }
    ]
});
