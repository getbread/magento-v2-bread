/**
 * Configure payment data and init bread checkout
 */
define(['jquery',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/quote'], function($, fullScreenLoader, quote){
    return {
        configure: function(data, context) {
            var breadConfig = {
                buttonId: data.buttonId,
                items: data.items,
                actAsLabel: false,
                asLowAs: data.asLowAs,
                shippingOptions: [data.shippingOptions],
                tax: this.round(quote.getTotals()._latestValue.base_tax_amount),
                customTotal: this.round(quote.getTotals()._latestValue.base_grand_total),
                
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
            if (window.checkoutConfig.payment.breadcheckout.buttonCss !== null) {
                breadConfig.customCSS = window.checkoutConfig.payment.breadcheckout.buttonCss + ' .bread-amt, .bread-dur { display:none; } .bread-text::after{ content: "Finance Application"; }';
            }

            if (typeof data.shippingContact !== 'undefined' && data.shippingContact != false) {
                breadConfig.shippingContact = data.shippingContact;
            }

            if (typeof data.billingContact !== 'undefined' && data.billingContact != false) {
                breadConfig.billingContact = data.billingContact;
            }

            var discountAmount =- this.round(window.checkoutConfig.totalsData.discount_amount);
            if (discountAmount > 0) {
                breadConfig.discounts = [{
                    amount: discountAmount,
                    description: (window.checkoutConfig.totalsData.coupon_code !== null) ?
                        window.checkoutConfig.totalsData.coupon_code :
                        "Discount"
                }];
            }

            /**
             * Call the checkout method from bread.js
             */
            if (window.checkoutConfig.payment.breadcheckout.transactionId === null) {
                bread.checkout(breadConfig);
            } else {
                fullScreenLoader.stopLoader();
                $('#' + data.buttonId).hide();
                $('#bread_transaction_id').val(window.checkoutConfig.payment.breadcheckout.transactionId);
                var approved = "<span><strong>You have been approved for financing.<br/>"+
                    "Please continue with the checkout to complete your order.</strong></span>";
                $('#bread_feedback').html(approved);
                $('#bread-checkout-submit').removeAttr('disabled');
            }
        },

        round: function(value) {
            return parseInt(
                Number(Math.round(parseFloat(value)+'e'+2)+'e-'+2)
                * 100
            );
        }
    };
});