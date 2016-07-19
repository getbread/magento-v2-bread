/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'Magento_Checkout/js/view/shipping',
        'jquery',
        'Magento_Checkout/js/checkout-data'
    ],
    function (Shipping, $, checkout) {
        'use strict';
        return Shipping.extend({
            setShippingInformation: function() {
                /** Call parent method */
                Shipping.prototype.setShippingInformation.call(this);

                /** Get updated quote data */
                $.ajax({
                    url: window.checkoutConfig.payment.breadcheckout.configDataUrl,
                    type: 'post',
                    context: this
                }).done(function (response) {
                    this.updateConfigData(response);
                });
            },

            /**
             * Add updated quote data to window.checkoutConfig global variable
             *
             * @see Bread\BreadCheckout\Model\Ui\ConfigProvider
             */
            updateConfigData: function(data) {
                window.checkoutConfig.payment.breadcheckout.breadConfig.shippingContact = data.shippingContact;
                window.checkoutConfig.payment.breadcheckout.breadConfig.billingContact.email = checkout.getValidatedEmailValue();
                window.checkoutConfig.payment.breadcheckout.breadConfig.shippingOptions = data.shippingOptions;

                if (!$.isEmptyObject(data.billingContact)) {
                    window.checkoutConfig.payment.breadcheckout.breadConfig.billingContact = data.billingContact;
                }
            }
        });
    }
);
