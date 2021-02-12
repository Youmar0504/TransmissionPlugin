import template from './transmission-module-list.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Shopware.Component.register('transmission-module-list', {
    template,

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    created() {
    this.repository = this.repositoryFactory.create('transmission_module');

    this.repository
        .search(new Criteria(), Shopware.Context.api)
        .then((result) => {
            this.bundles = result;
        });
    },
  
    data() {
    return {
        repository: null,
        bundles: null
        };
    }, 

    computed: {
    columns() {
        return [{
            property: 'name',
            dataIndex: 'name',
            label: this.$tc('transmission-module.list.columnName'),
            routerLink: 'transmission.module.detail',
            inlineEdit: 'string',
            allowResize: true,
            primary: true
        }, {
            property: 'discount',
            dataIndex: 'discount',
            label: this.$tc('transmission-module.list.columnDiscount'),
            inlineEdit: 'number',
            allowResize: true
        }, {
            property: 'discountType',
            dataIndex: 'discountType',
            label: this.$tc('transmission-module.list.columnDiscountType'),
            allowResize: true
    },
});
