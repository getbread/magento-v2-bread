define(
    ['jquery', 'loadingPopup'],
    function ($) {
        'use strict';
        return {
            configureButton: function (data) {
                if (data.shippingOptions === false) {
                    $('#bread_feedback').html('<p><strong>Please select a shipping method first!</strong></p>');
                    return;
                } else {
                    $('#bread_feedback').empty();
                }

                var breadConfig = {
                    buttonId: 'bread-checkout-btn',
                    allowSplitPayCheckout: false,
                    shippingOptions: [data.shippingOptions],
                    tax: data.tax,
                    customTotal: data.grandTotal,
                    actAsLabel: false,
                    asLowAs: data.asLowAs,
                    shippingContact: data.shippingContact,
                    billingContact: data.billingContact,
                    buttonLocation: data.buttonLocation,
                    disableEditShipping: true,
                    done: function (err, tx_token) {
                        if (tx_token !== undefined) {
                            $.ajax(
                                {
                                    url: data.paymentUrl,
                                    data: {
                                        token: tx_token,
                                        form_key: window.FORM_KEY
                                    },
                                    type: 'post',
                                    context: this,
                                    beforeSend: function () {
                                        $('body').loadingPopup(
                                            {
                                                timeout: false
                                            }
                                        );
                                    }
                                }
                            ).done(
                                function (response) {
                                    if (response.error) {
                                        errorInfo = {
                                            bread_config: breadConfig,
                                            response: response,
                                            tx_id: tx_token,
                                        };
                                        document.logBreadIssue('error', errorInfo, 'Error validating payment method');

                                        alert(response.error);
                                    } else if (response.result && response.result === true) {
                                        $('#bread-checkout-btn').hide();
                                        var approved = "<p><strong>You have been approved for financing.<br/>"+
                                        "Please continue with the checkout to complete your order.</strong></p>";
                                        $('#bread_feedback').html(approved);
                                        $('body').trigger('hideLoadingPopup');
                                    }
                                }
                            ).fail(
                                function (error) {
                                        var errorInfo = {
                                            bread_config: breadConfig,
                                            tx_id: tx_token,
                                    };
                                        document.logBreadIssue(
                                            'error', errorInfo,
                                            'Error code returned when calling ' + paymentUrl + ', with status: ' + error.statusText
                                        );
                                        $('body').trigger('hideLoadingPopup');
                                }
                            );
                        } else {
                            var errorInfo = {
                                bread_config: breadConfig,
                                err: err
                            };
                            document.logBreadIssue('error', errorInfo, 'tx_token undefined in done callback');
                        }
                    }
                };

                /**
                 * Optional params
                 */

                breadConfig.shippingContact = data.shippingContact;
                breadConfig.billingContact = data.billingContact;
                if (!data.isHealthcare) {
                    breadConfig.items = data.quoteItems;
                }

                if (data.buttonCss !== null) {
                    breadConfig.customCSS = data.buttonCss + ' .bread-amt, .bread-dur { display:none; } .bread-text::after{ content: "Finance Application"; }';
                }

                if (data.cartSizeFinancing.enabled) {
                    var cartSizeFinancingId = data.cartSizeFinancing.id;
                    var cartSizeThreshold = data.cartSizeFinancing.threshold;
                    var items = data.quoteItems;
                    var itemsPriceSum = items.reduce(
                        function (sum, item) {
                            return sum + item.price * item.quantity
                        }, 0
                    ) / 100;
                    breadConfig.financingProgramId = (itemsPriceSum >= cartSizeThreshold) ? cartSizeFinancingId : 'null';
                }

                if (data.discounts.length > 0) {
                    breadConfig.discounts = data.discounts;
                }

                /**
                 * Call the checkout method from bread.js
                 */
                $('#bread-checkout-btn').show();
                if (typeof bread !== 'undefined') {
                    bread.checkout(breadConfig);
                }
            }
        }
    }
);