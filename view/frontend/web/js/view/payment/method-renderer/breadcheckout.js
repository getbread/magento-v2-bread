define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'buttonConfig',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/create-billing-address',
        'Magento_Checkout/js/action/select-billing-address',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/action/set-billing-address',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/shipping-save-processor/default',
        'Magento_Checkout/js/model/shipping-service',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (Component,
              $,
              button,
              customer,
              createBillingAddress,
              selectBillingAddress,
              checkoutData,
              setBillingAddressAction,
              errorProcessor,
              defaultProcessor,
              shippingService,
              fullScreenLoader) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Bread_BreadCheckout/payment/breadcheckout'
            },

            initialize: function () {
                this._super();
                return this;
            },

            /**
             * Payment code
             */
            getCode: function() {
                return 'breadcheckout';
            },

            /**
             * Get if default button size enabled from config
             */
            getDefaultSize: function() {
                return window.checkoutConfig.payment.breadcheckout.defaultSize;
            },

            /**
             * Transaction ID from Ui\ConfigProvider
             */
            getBreadTransactionId: function() {
                return window.checkoutConfig.payment.breadcheckout.transactionId;
            },

            /**
             * Initialize the bread checkout button
             */
            initComplete: function() {
                var data = window.checkoutConfig.payment.breadcheckout.breadConfig;

                if (typeof bread != 'undefined') {
                    button.configure(data, this);
                }
                return true;
            },

            /**
             * Validate that Bread authorized amount matches
             * total in Magento quote, then call place order
             *
             * @return {boolean}
             */
            placeOrder: function(data, event) {
                this.data = data;
                this.event = event;

                $.ajax({
                    url: window.checkoutConfig.payment.breadcheckout.validateTotalsUrl,
                    data: { bread_transaction_id: this.getBreadTransactionId() },
                    type: 'post',
                    context: this,
                    beforeSend: function() {
                        fullScreenLoader.startLoader();
                    }
                }).done(function (response) {
                    fullScreenLoader.stopLoader();

                    if (response.valid) {
                        /** Call parent method */
                        return Component.prototype.placeOrder.call(this, this.data, this.event);
                    } else {
                        errorProcessor.process(response, this.messageContainer);
                        return false;
                    }
                });
            },

            /**
             * Save billing address data in quote
             *
             * @param data {object}
             * @param token {string}
             */
            updateAddress: function(data, token) {
                /**
                 * Billing address
                 */
                var billingAddressData = this.getAddressData(data.billingAddress);
                var newBillingAddress = createBillingAddress(billingAddressData);

                // New address must be selected as a billing address
                selectBillingAddress(newBillingAddress);
                checkoutData.setSelectedBillingAddress(newBillingAddress.getKey());
                checkoutData.setNewCustomerBillingAddress(billingAddressData);

                /**
                 * Reload checkout section & add bread token
                 */
                defaultProcessor.saveShippingInformation().done(function() {
                    window.checkoutConfig.payment.breadcheckout.transactionId = token;
                });
            },

            /**
             * Format street and set whether to save in address book
             *
             * @param address {object}
             * @return {object}
             */
            getAddressData: function(address) {
                if (typeof address.street == 'string') {
                    address.street = {
                        0: address.street,
                        1: ""
                    };
                }

                address.save_in_address_book = ((customer.isLoggedIn() &&
                                                 customer.getBillingAddressList().length < 1)) ?
                                                 1 : 0;
                return address;
            }

        });
    }
);