define([
        'jquery',
        'Magento_Payment/js/view/payment/cc-form'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Imranweb7_AuthorizeDotNet/payment/authorizedotnet'
            },

            context: function() {
                return this;
            },

            getCode: function() {
                return 'imranweb7_authorizedotnet';
            },

            isActive: function() {
                return true;
            }
        });
    }
);