
Ext.define('Shopware.apps.TransmissionLogs.store.Transmission', {
    extend: 'Shopware.store.Listing',

    configure: function () {
        return {
            controller: 'TransmissionLogs'
        };
    },

    model: 'Shopware.apps.TransmissionLogs.model.Transmission'
});