import './page/transmission-module-list';
import './page/transmission-module-detail';

Shopware.Module.register('transmission-module', {
    type: 'plugin',
    name: 'Transmission',
    color: '#ff3d58',
    icon: 'default-shopping-paper-bag-product',
    title: 'Transmission Module',
    description: 'Shows logs related to the Transmission Plugin.',

    routes: {
        overview: {
            component: 'transmission-module-list',
            path: 'overview'
        },
        detail: {
            component: 'transmission-module-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'transmission.plugin.list'
            }
        },
    },
   
    navigation: [{
    	label: 'Transmission',
    	color: '#ff3d58',
    	path: 'transmission.plugin.overview',
    	icon: 'default-shopping-paper-bag-product',
    	position: 100
    }],
});
