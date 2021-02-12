

Ext.define('Shopware.apps.TransmissionLogs.view.detail.Transmission', {
    extend: 'Shopware.model.Container',
    alias: 'widget.transmission-detail-log-container',
    configure: function () {
        return {
            controller: 'TransmissionLogs',
            associations: [ 'logs' ]
        };
    }
});
