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
                shippingContact: data.shippingContact,
                billingContact: data.billingContact,
                done: function (err, tx_token) {
                    console.log(err);
                    console.log(tx_token);
                    if (tx_token !== undefined) {
                        $.ajax({
                            url: data.paymentUrl,
                            data: {token: tx_token},
                            type: 'post'
                        }).done(function (transport) {
                            console.log('success');
                            try {
                                if (transport.responseText.isJSON()) {
                                    if (response.error) {
                                        alert(response.message);
                                    } else {
                                        $('bread_transaction_id').value = tx_token;
                                        var approved = "<span><strong>You have been approved for financing.<br/>" +
                                            "Please continue with the checkout to complete your order.</strong></span>";
                                        $('payment_form_breadcheckout').insert(approved);
                                    }
                                }
                            } catch (e) {
                                console.log(e);
                            }
                        });
                    }
                }
            };

            var taxValue = parseInt(window.checkoutConfig.totalsData.tax_amount);
            if (taxValue >= 0) {
                breadConfig.tax = taxValue * 100;
            }

            if (window.checkoutConfig.payment.breadcheckout.buttonCss !== null) {
                breadConfig.customCSS = window.checkoutConfig.payment.breadcheckout.buttonCss + ' .bread-amt, .bread-dur { display:none; } .bread-text::after{ content: "Finance Application"; }';
            }

            bread.checkout(breadConfig);
        }
    };
});