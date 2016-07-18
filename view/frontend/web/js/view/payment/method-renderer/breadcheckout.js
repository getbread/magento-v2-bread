define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'buttonConfig'
    ],
    function (Component, $, button) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Bread_BreadCheckout/payment/breadcheckout'
            },

            initialize: function () {
                this._super();
                return this;
            },

            /**
             * Payment code
             */
            getCode: function() {
                return 'breadcheckout';
            },

            getDefaultSize: function() {
                return window.checkoutConfig.payment.breadcheckout.defaultSize;
            },

            /**
             * Initialize the bread checkout button
             */
            initComplete: function() {
                /** @see Bread\BreadCheckout\Model\Ui\ConfigProvider */
                var data = window.checkoutConfig.payment.breadcheckout.breadConfig;

                if (typeof bread != 'undefined') {
                    button.configure(data);
                }
                return true;
            }

        });
    }
);