
Ext.define('Shopware.apps.TransmissionLogs', {
    extend: 'Enlight.app.SubApplication',

    name: 'Shopware.apps.TransmissionLogs',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: [ 'Main' ],

    views: [
        'list.Window',
        'list.Transmission',

        
        'detail.Transmission',
        'detail.Log',
        'detail.Window'
    ],

    models: [ 'Transmission', 'Log' ],
    stores: [ 'Transmission' ],

    launch: function () {
        return this.getController('Main').mainWindow;
    }
});