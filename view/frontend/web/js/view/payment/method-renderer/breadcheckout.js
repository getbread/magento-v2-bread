define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Bread_BreadCheckout/breadcheckout/form'
            },

            /**
             * Payment code
             */
            getCode: function () {
                return 'breadcheckout';
            },
        });
    }
);