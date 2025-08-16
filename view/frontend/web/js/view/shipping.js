/**
 * Populates checkout config data when shipping step
 * in checkout is completed
 *
 * @author Bread   copyright   2016
 * @author Miranda @Mediotype
 */
/*global define*/
define(
    [
        'Magento_Checkout/js/view/shipping',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'buttonConfig'
    ],
    function (Shipping, $, quote, fullScreenLoader, button) {
        'use strict';
        return Shipping.extend(
            {
                setShippingInformation: function () {
                    /**
                     * Call parent method
                     */
                    Shipping.prototype.setShippingInformation.call(this);

                    this.updateConfigData();
                    this.invalidateToken();
                    this.refreshBreadPaymentMethod();
                },

                /**
                 * Add updated shipping option data to window.checkoutConfig global variable
                 *
                 * @see Bread\BreadCheckout\Model\Ui\ConfigProvider
                 */
                updateConfigData: function () {
                    var shippingMethod = quote.shippingMethod();

                    if (shippingMethod && shippingMethod.carrier_title && shippingMethod.method_title) {
                        window.checkoutConfig.payment.breadcheckout.breadConfig.shippingOptions = {
                            type: shippingMethod.carrier_title + ' - ' + shippingMethod.method_title,
                            typeId: shippingMethod.carrier_code + '_' + shippingMethod.method_code,
                            cost: this.round(shippingMethod.base_amount)
                        };
                    } else {
                        window.checkoutConfig.payment.breadcheckout.breadConfig.shippingOptions = null;
                    }
                },

                /**
                 * Invalidate existing transaction ID (in case user filled out payment
                 * form and then went back a step)
                 */
                invalidateToken: function () {
                    if (window.checkoutConfig.payment.breadcheckout.transactionId !== null) {
                        window.checkoutConfig.payment.breadcheckout.transactionId = null;
                    }
                },

		/**
                 *
                 * Refresh the payment method section if transactionId is not set
                 */
                refreshBreadPaymentMethod: function () {
                    var paymentMethod = quote.paymentMethod();
                    if ( paymentMethod !== null && typeof paymentMethod.method !== 'undefined') {
                        if (quote.paymentMethod().method === 'breadcheckout' || quote.paymentMethod().method === 'rbccheckout'
                                && window.checkoutConfig.payment.breadcheckout.transactionId === null) {
                            //Check if embeddedCheckout is enabled
                            if(window.checkoutConfig.payment.breadcheckout.breadConfig.embeddedCheckout) {
                                button.embeddedCheckout();
                            } else {
                                button.init();
                            }
                        }
                    }
                },

                /**
                 * Round float to 2 decimal plates and convert to integer
                 *
                 * @param   value
                 * @returns {Number}
                 */
                round: function (value) {
                    if (isNaN(value)) {
                        return 0;
                    }
                    return parseInt(
                        Number(Math.round(parseFloat(value)+'e'+2)+'e-'+2)
                        * 100
                    );
                }
            }
        );
    }
);
