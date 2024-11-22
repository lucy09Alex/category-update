define(['jquery', 'Magento_Ui/js/modal/modal'], function($, modal) {
    'use strict';
    return function() {
        var popupHtml = $('#category-update-form').html();
        var modalOptions = {
            type: 'popup',
            title: 'Update Category',
            responsive: true,
            innerScroll: true,
            buttons: [{
                text: $.mage.__('Cancel'),
                class: 'action-secondary action-dismiss',
                click: function () {
                    this.closeModal();
                }
            }]
        };
        var popup = modal(modalOptions, $('<div />').html(popupHtml).modal());
        popup.modal('openModal');
    };
});
