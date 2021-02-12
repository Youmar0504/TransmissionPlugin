
Ext.define('Shopware.apps.TransmissionLogs.view.detail.Log', {
    extend: 'Shopware.grid.Association',
    alias: 'widget.transmission-detail-log-grid',
    title: 'order Logs',
    height: 300, 
    configure: function() {
        return {
            controller: 'TransmissionLogs',
            columns: {
                orderNumber: {},
                requestType: {},
                targetUrl: {},
                request: {},
                response: {},
                status: {}
            }
        }
    }
 });
