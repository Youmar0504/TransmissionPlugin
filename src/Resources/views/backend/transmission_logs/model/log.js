
Ext.define('Shopware.apps.TransmissionLogs.model.Log', {
    extend: 'Shopware.data.Model',
    configure: function() {
        return {
            //Tell the grid where it sould display itself
            related: 'Shopware.apps.TransmissionLogs.view.detail.Log'
        };
    },
    fields: [
        { name: 'orderNumber', type: 'int' },
        { name: 'targetUrl', type: 'string' },
        { name: 'requestType', type: 'string' },
        { name: 'request', type: 'string' },
        { name: 'response', type: 'string' },
        { name: 'status', type: 'string' },
        { name: 'creationDae', type: 'datetime' }
    ]

});

