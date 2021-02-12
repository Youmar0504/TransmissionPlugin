Ext.define('Shopware.apps.TransmissionLogs.view.detail.Window', {
    extend: 'Shopware.window.Detail',
    alias: 'widget.transmission-detail-window',
    title : 'Transmission Logs for Orders details between Exact & Shopware',
    height: 270,
    width: 680,
    configure: function() {
        return {
            associations: [ 'logs' ] ,   
            controller: 'TransmissionLogs',
            detail: 'Shopware.apps.TransmissionLogs.view.detail.Log'
        }
    }
});
