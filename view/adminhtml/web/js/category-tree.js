define([
    'jquery',
    'uiComponent',
    'Magento_Catalog/js/category-tree'
], function ($, Component, categoryTree) {
    'use strict';

    return Component.extend({
        initialize: function () {
            this._super();
            categoryTree.initialize();
        }
    });
});
