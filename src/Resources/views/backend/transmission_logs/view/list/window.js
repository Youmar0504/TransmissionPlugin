Ext.define('Shopware.apps.TransmissionLogs.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.transmission-list-window',
    height: 450,
    title: 'Logs for the connection between Exact & Shopware',

    configure: function () {
        return {
            listingGrid: 'Shopware.apps.TransmissionLogs.view.list.Transmission',
            listingStore: 'Shopware.apps.TransmissionLogs.store.Transmission'
        };
    }
});
