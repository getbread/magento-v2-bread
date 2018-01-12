/**
 * Configure payment data and init bread checkout
 */
define(['jquery',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data'], function($, fullScreenLoader, quote, checkout){
    return {
        breadConfig: undefined,

        configure: function(data, context) {
            this.breadConfig = {
                buttonId: data.buttonId,
                actAsLabel: false,
                asLowAs: data.asLowAs,
                shippingOptions: [data.shippingOptions],
                tax: this.round(quote.getTotals()._latestValue.base_tax_amount),
                customTotal: this.round(quote.getTotals()._latestValue.base_grand_total),
                buttonLocation: window.checkoutConfig.payment.breadcheckout.breadConfig.buttonLocation,
                
                done: function (err, tx_token) {
                    if (tx_token !== undefined) {
                        $.ajax({
                            url: data.paymentUrl,
                            data: {token: tx_token},
                            type: 'post',
                            context: context,
                            beforeSend: function() {
                                fullScreenLoader.startLoader();
                            }
                        }).done(function (response) {
                            try {
                                if (response !== null && typeof response === 'object') {
                                    if (response.error) {
                                        console.log(response);
                                        alert(response.error);
                                    } else {
                                        this.updateAddress(response, tx_token);
                                    }
                                    fullScreenLoader.stopLoader();
                                    if (document.getElementById("bread-checkout-submit")) {
                                        document.getElementById("bread-checkout-submit").disabled = false;
                                    }
                                }
                            } catch (e) {
                                console.log(e);
                            }
                        });
                    }
                }
            };


            /**
             * Optional params
             */

            if (!window.checkoutConfig.payment.breadcheckout.isHealthcare) {
                this.breadConfig.items = data.items;
            }

            if (window.checkoutConfig.payment.breadcheckout.buttonCss !== null) {
                this.breadConfig.customCSS = window.checkoutConfig.payment.breadcheckout.buttonCss + ' .bread-amt, .bread-dur { display:none; } .bread-text::after{ content: "Finance Application"; }';
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

            if (window.checkoutConfig.payment.breadcheckout.isCartSizeTargetedFinancing) {
                var cartSizeFinancingId = window.checkoutConfig.payment.breadcheckout.financingProgramId;
                var cartSizeThreshold = window.checkoutConfig.payment.breadcheckout.cartSizeThreshold;
                var itemsPriceSum = data.items.reduce(function (sum, item) {
                        return sum + item.price * item.quantity
                    }, 0) / 100;
                this.breadConfig.financingProgramId = (itemsPriceSum >= cartSizeThreshold) ? cartSizeFinancingId : 'null';
            }


            this.setShippingInformation();
        },

        /**
         * Call the checkout method from bread.js
         */
        init: function() {
            if (window.checkoutConfig.payment.breadcheckout.transactionId === null) {
                bread.checkout(this.breadConfig);
            } else {
                $('#' + this.breadConfig.buttonId).hide();
                $('#bread_transaction_id').val(window.checkoutConfig.payment.breadcheckout.transactionId);
                var approved = "<span><strong>You have been approved for financing.<br/>"+
                    "Please continue with the checkout to complete your order.</strong></span>";
                $('#bread_feedback').html(approved);
                $('#bread-checkout-submit').removeAttr('disabled');
            }
            fullScreenLoader.stopLoader();
        },

        /**
         * Get updated quote data
         */
        setShippingInformation: function() {
            if (window.checkoutConfig.payment.breadcheckout.transactionId !== null) {
                return this.init();
            }

            $.ajax({
                url: window.checkoutConfig.payment.breadcheckout.configDataUrl,
                type: 'post',
                context: this,
                beforeSend: function() {
                    fullScreenLoader.startLoader();
                }
            }).done(function(data) {
                if (data.shippingContact != false && !window.checkoutConfig.payment.breadcheckout.isHealthcare) {
                    this.breadConfig.shippingContact = data.shippingContact;
                }

                if (data.billingContact != false && !window.checkoutConfig.payment.breadcheckout.isHealthcare) {
                    this.breadConfig.billingContact = data.billingContact;
                    this.breadConfig.billingContact.email = (data.billingContact.email) ?
                        data.billingContact.email :
                        checkout.getValidatedEmailValue();
                }

                this.init();
            });
        },

        /**
         * Round float to 2 decimal places then convert to integer
         */
        round: function(value) {
            return parseInt(
                Number(Math.round(parseFloat(value)+'e'+2)+'e-'+2)
                * 100
            );
        }
    };
});