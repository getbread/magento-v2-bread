 define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'breadcheckout',
                component: 'Bread_BreadCheckout/js/view/payment/method-renderer/breadcheckout'
            }
        );
        /**
    * Add view logic here if needed 
    */
        return Component.extend({});
    }
);