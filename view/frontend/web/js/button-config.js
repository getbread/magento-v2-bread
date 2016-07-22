/**
 * Configure payment data and init bread checkout
 */
define(['jquery',
    'Magento_Checkout/js/view/payment/default'], function($){
    return {
        configure: function(data) {
            var breadConfig = {
                buttonId: data.buttonId,
                items: data.items,
                discounts: data.discounts,
                shippingOptions: data.shippingOptions,
                customTotal: data.grandTotal,
                actAsLabel: false,
                asLowAs: data.asLowAs,
                
                done: function (err, tx_token) {
                    console.log(err);
                    console.log(tx_token);
                    if (tx_token !== undefined) {
                        $.ajax({
                            url: data.paymentUrl,
                            data: {token: tx_token},
                            type: 'post',
                            beforeSend: function () {
                                $('#bread_feedback').html("<span><strong>Please wait...</strong></span>");
                            }
                        }).done(function (response) {
                            console.log(response);
                            try {
                                if (response !== null && typeof response === 'object') {
                                    if (response.error) {
                                        alert(response.message);
                                    } else {
                                        $('#bread_transaction_id').val(tx_token);
                                        var approved = "<span><strong>You have been approved for financing.<br/>"+
                                            "Please continue with the checkout to complete your order.</strong></span>";
                                        $('#bread_feedback').html(approved);
                                        $('#bread-checkout-submit').removeAttr('disabled');
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
                                    alert(response.message);
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
                        type: 'post'
                    }).done(function (response) {
                        try {
                            if (typeof response == 'object') {
                                if (response.error) {
                                    alert(response.message);
                                } else {
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
            if (typeof data.billingContact !== 'undefined') {
                breadConfig.billingContact = data.billingContact;
            }

            if (window.checkoutConfig.payment.breadcheckout.buttonCss !== null) {
                breadConfig.customCSS = window.checkoutConfig.payment.breadcheckout.buttonCss + ' .bread-amt, .bread-dur { display:none; } .bread-text::after{ content: "Finance Application"; }';
            }

            /**
             * Call the checkout method from bread.js
             */
            bread.checkout(breadConfig);
        }
    };
});