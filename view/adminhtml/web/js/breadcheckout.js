define(
    ['jquery', 'loadingPopup'],
    function ($) {
        'use strict';
        return {
            configureButton: function(data) {
                if (data.shippingOptions === false) {
                    $('#bread_feedback').html('<p><strong>Please select a shipping method first!</strong></p>');
                    return;
                } else {
                    $('#bread_feedback').empty();
                }

                var breadConfig = {
                    buttonId: 'bread-checkout-btn',
                    items: data.quoteItems,
                    shippingOptions: [data.shippingOptions],
                    tax: data.tax,
                    customTotal: data.grandTotal,
                    actAsLabel: false,
                    asLowAs: data.asLowAs,
                    shippingContact: data.shippingContact,
                    billingContact: data.billingContact,

                    done: function (err, tx_token) {
                        if (tx_token !== undefined) {
                            $.ajax({
                                url: data.paymentUrl,
                                data: {token: tx_token},
                                type: 'get',
                                context: this,
                                beforeSend: function() {
                                    $('body').loadingPopup({
                                        timeout: false
                                    });
                                }
                            }).done(function (response) {
                                try {
                                    if (typeof response.result !== 'undefined' && response.result === true) {
                                        $('#bread-checkout-btn').hide();
                                        var approved = "<p><strong>You have been approved for financing.<br/>"+
                                            "Please continue with the checkout to complete your order.</strong></p>";
                                        $('#bread_feedback').html(approved);
                                        $('body').trigger('hideLoadingPopup');
                                    }
                                } catch (e) {
                                    console.log(e);
                                    $('body').trigger('hideLoadingPopup');
                                }
                            });
                        }
                    }
                };

                /**
                 * Optional params
                 */
                if (data.buttonCss !== null) {
                    breadConfig.customCSS = data.buttonCss + ' .bread-amt, .bread-dur { display:none; } .bread-text::after{ content: "Finance Application"; }';
                }

                if (data.discounts > 0) {
                    breadConfig.discounts = data.discounts;
                }

                /**
                 * Call the checkout method from bread.js
                 */
                $('#bread-checkout-btn').show();
                bread.checkout(breadConfig);
            }
        }
    }
);