/**
 * Configure payment data and init bread checkout
 */
define(['jquery',
    'Magento_Checkout/js/model/full-screen-loader'], function($, fullScreenLoader){
    return {
        configure: function(data, context) {
            var breadConfig = {
                buttonId: data.buttonId,
                items: data.items,
                billingContact: (typeof data.billingContact !== 'undefined') ?
                    data.billingContact :
                    data.shippingContact,
                shippingContact: data.shippingContact,
                actAsLabel: false,
                asLowAs: data.asLowAs,
                
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
                                        this.updateAddresses(response, tx_token);
                                    }
                                }
                            } catch (e) {
                                console.log(e);
                            }
                        });
                    }
                },

                /**
                 * Calculate tax value callback
                 *
                 * @param shippingAddress
                 * @param callback
                 */
                calculateTax: function (shippingAddress, callback) {
                    shippingAddress.block_key = window.checkoutConfig.payment.breadcheckout.breadConfig.blockCode;

                    $.ajax({
                        url: window.checkoutConfig.payment.breadcheckout.breadConfig.taxEstimationUrl,
                        data: {shippingInfo: JSON.stringify(shippingAddress)},
                        type: 'post'
                    }).done(function (response) {
                        try {
                            if (typeof response == 'object') {
                                if (response.error) {
                                    alert(response.error);
                                } else {
                                    callback(null, response.result);
                                }
                            }
                        }
                        catch (e) {
                            console.log(e);
                        }
                    });
                },

                /**
                 * Calculate shipping cost callback
                 *
                 * @param shippingAddress
                 * @param callback
                 */
                calculateShipping: function (shippingAddress, callback) {
                    shippingAddress.block_key = window.checkoutConfig.payment.breadcheckout.breadConfig.blockCode;

                    $.ajax({
                        url: window.checkoutConfig.payment.breadcheckout.breadConfig.shippingEstimationUrl,
                        data: shippingAddress,
                        type: 'post',
                        context: context
                    }).done(function (response) {
                        try {
                            if (typeof response == 'object') {
                                if (response.error) {
                                    alert(response.error);
                                } else {
                                    this.setShippingRates(response.result);
                                    callback(null, response.result);
                                }
                            }
                        }
                        catch (e) {
                            console.log(e);
                        }
                    });
                }
            };

            /**
             * Optional params
             */
            if (window.checkoutConfig.payment.breadcheckout.buttonCss !== null) {
                breadConfig.customCSS = window.checkoutConfig.payment.breadcheckout.buttonCss + ' .bread-amt, .bread-dur { display:none; } .bread-text::after{ content: "Finance Application"; }';
            }

            var discountAmount =- parseInt(window.checkoutConfig.totalsData.discount_amount);
            if (discountAmount > 0) {
                breadConfig.discounts = [{
                    amount: discountAmount * 100,
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
        }
    };
});