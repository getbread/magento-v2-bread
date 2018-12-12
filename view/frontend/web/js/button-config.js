/**
 * Configure payment data and init bread checkout
 */
define([
    'jquery',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_Ui/js/modal/alert'
], function ($, fullScreenLoader, quote, checkout,alert) {

    return {
        breadConfig: undefined,

        configure: function (data, context) {
            this.breadConfig = {
                items: data.items,
                actAsLabel: false,
                asLowAs: data.asLowAs,
                shippingOptions: [data.shippingOptions],
                tax: this.round(quote.getTotals()._latestValue.base_tax_amount),
                customTotal: this.round(quote.getTotals()._latestValue.base_grand_total),
                buttonLocation: window.checkoutConfig.payment.breadcheckout.breadConfig.buttonLocation,
                disableEditShipping: true,
                onShowCheckoutError: function(message){

                    alert({
                        content: message.data
                    });

                },

                done: function (err, tx_token) {
                    if (tx_token !== undefined) {
                        context.buttonCallback(tx_token);
                    }
                }
            };

            /**
             * Optional params
             */

            if(data.embeddedCheckout){
                this.breadConfig.formId = data.formId;
            } else {
                this.breadConfig.buttonId = data.buttonId;
            }

            if (!window.checkoutConfig.payment.breadcheckout.isHealthcare) {
                this.breadConfig.items = data.items;
            }

            if (data.cartSizeFinancing.enabled) {
                var cartSizeFinancingId = data.cartSizeFinancing.id;
                var cartSizeThreshold = data.cartSizeFinancing.threshold;
                var items = data.items;
                var itemsPriceSum = items.reduce(function (sum, item) {
                    return sum + item.price * item.quantity
                }, 0) / 100;
                this.breadConfig.financingProgramId = (itemsPriceSum >= cartSizeThreshold) ? cartSizeFinancingId : 'null';
            }

            if (typeof data.billingContact !== 'undefined' && data.billingContact != false && !window.checkoutConfig.payment.breadcheckout.isHealthcare) {
                this.breadConfig.billingContact = data.billingContact;
            }

            var discountAmount =- this.round(quote.getTotals()._latestValue.discount_amount);
            if (discountAmount > 0) {
                this.breadConfig.discounts = [{
                    amount: discountAmount,
                    description: (quote.getTotals()._latestValue.coupon_code !== null) ?
                        quote.getTotals()._latestValue.coupon_code :
                        "Discount"
                }];
            }

        },

        /**
         * Call the checkout method from bread.js
         */
        init: function () {

            this.setShippingInformation();

            if (window.checkoutConfig.payment.breadcheckout.transactionId === null) {

                var self = this;

                if(this.breadConfig.shippingOptions[0] !== false){
                    bread.showCheckout(this.breadConfig);
                } else {
                    /* ocs save selected shipping method */
                    $.ajax({
                        url: window.checkoutConfig.payment.breadcheckout.shippingOptionUrl,
                        type: 'post',
                        context: this
                    }).done(function (data) {
                        self.breadConfig.shippingOptions = [data];
                        self.breadConfig.customTotal =  this.round(quote.getTotals()._latestValue.base_grand_total);
                        bread.showCheckout(self.breadConfig);
                    });
                }

            }
            fullScreenLoader.stopLoader();
        },

        /**
         * Initialize embedded checkout
         */
        embeddedCheckout: function() {
            if (window.checkoutConfig.payment.breadcheckout.transactionId === null) {
                bread.checkout(this.breadConfig);
            }
            fullScreenLoader.stopLoader();
        },

        /**
         * Get updated quote data
         */
        setShippingInformation: function () {
            
            $.ajax({
                url: window.checkoutConfig.payment.breadcheckout.configDataUrl,
                type: 'post',
                context: this,
                beforeSend: function () {
                    fullScreenLoader.startLoader();
                }
            }).done(function (data) {
                if (data.shippingContact != false && !window.checkoutConfig.payment.breadcheckout.isHealthcare) {
                    this.breadConfig.shippingContact = data.shippingContact;
                }

                if (data.billingContact != false && !window.checkoutConfig.payment.breadcheckout.isHealthcare) {
                    this.breadConfig.billingContact = data.billingContact;
                    this.breadConfig.billingContact.email = (data.billingContact.email) ?
                        data.billingContact.email :
                        checkout.getValidatedEmailValue();
                }
                fullScreenLoader.stopLoader();
            });
        },

        /**
         * Round float to 2 decimal places then convert to integer
         */
        round: function (value) {
            return parseInt(
                Number(Math.round(parseFloat(value)+'e'+2)+'e-'+2)
                * 100
            );
        }
    };
});