define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'imranweb7_authorizedotnet',
                component: 'Imranweb7_AuthorizeDotNet/js/view/payment/method-renderer/authorizedotnet'
            }
        );

        return Component.extend({});
    });
