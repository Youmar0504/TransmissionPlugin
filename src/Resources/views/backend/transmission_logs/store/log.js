
Ext.define('Shopware.apps.TransmissionLogs.store.Log', {
    extend: 'Shopware.store.Association',
    model: 'Shopware.apps.TransmissionLogs.model.Log',
    configure: function() {
        return {
            controller: 'TransmissionLogs'
        };
    }
});
