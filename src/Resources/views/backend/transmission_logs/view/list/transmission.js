
Ext.define('Shopware.apps.TransmissionLogs.view.list.Transmission', {
    extend: 'Shopware.grid.Panel',
    alias: 'widget.transmission-listing-grid',
    region: 'center',

    configure: function() {
        return {
           detailWindow: 'Shopware.apps.TransmissionLogs.view.detail.Window',
            columns: {
                orderNumber: {},
                productNumber: {},
		customerId: {},
                origine: {},
                destination: {},
                status: {},
                requestType: {},
                creationDate: {}
            }
        };
    }
});
