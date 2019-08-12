/**
 * Clears bread transaction token when changing coupon
 *
 * @author Bread   copyright   2016
 * @author Miranda @Mediotype
 */
define(
    [
        'jquery',
        'Magento_SalesRule/js/view/payment/discount'
    ],
    function ($, Discount) {
        'use strict';
        return Discount.extend(
            {
                /**
                 * Coupon form validation
                 *
                 * @returns {boolean}
                 */
                validate: function () {
                    if (Discount.prototype.validate.call(this)) { // Call parent method
                        if (window.checkoutConfig.payment.breadcheckout.transactionId !== null) {
                            window.checkoutConfig.payment.breadcheckout.transactionId = null;
                        }
                        return true;
                    }
                    return false;
                }
            }
        );
    }
);