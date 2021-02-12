
Ext.define('Shopware.apps.TransmissionLogs.model.Transmission', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'TransmissionLogs',
            detail: 'Shopware.apps.TransmissionLogs.view.detail.Transmission'          
        };
    },

    fields: [
        { name: 'id', type:'int', useNull: true },
        { name: 'orderNumber', type:'string' },
        { name: 'productNumber', type:'string' },
	{ name: 'customerId', type:'integer'},
        { name: 'origine', type:'string' },
        { name: 'destination', type:'string' },
        { name: 'requestType', type:'string' },
        { name: 'status', type: 'string' },
        { name: 'creationDate', type:'datetime'},
        { name: 'lastModifiedDate', type: 'datetime' }

    ],
    associations: [
        { 
            relation: 'ManyToMany',
            //storeClass: 'Shopware.apps.TransmissionLogs.store.Log',
            //loadOnDemand: true,
            field: 'orderNumber',
            type: 'hasMany',
            model: 'Shopware.apps.TransmissionLogs.model.Log',
            name: 'getLogs',
            associationKey: 'logs'
        }
    ]
});

