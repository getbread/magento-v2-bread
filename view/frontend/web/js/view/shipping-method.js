define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/set-shipping-information'
], function (
    $,
    quote,
    setShippingInformationAction
) {
    'use strict';

    return function (shippingMethod) {
        //Re-evaluate selected shippingMethod
        quote.shippingMethod(shippingMethod);
        if (shippingMethod != null) {
            //Invalidate existing transactionId if customer had already filled payment form 
            //and steps back
            if (window.checkoutConfig.payment.breadcheckout.transactionId !== null) {
                window.checkoutConfig.payment.breadcheckout.transactionId = null;
            }
            //Set back payment method(Pay Later Bread) to ensure it checked when customer selects next 
            quote.paymentMethod(null);
        }
    };
});