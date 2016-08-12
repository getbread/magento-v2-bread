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
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/shipping-save-processor/default',
        'Magento_Checkout/js/model/shipping-service'
    ],
    function (Component,
              $,
              button,
              customer,
              createBillingAddress,
              selectBillingAddress,
              checkoutData,
              setBillingAddressAction,
              globalMessageList,
              defaultProcessor,
              shippingService) {
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
             * Update shipping rates in case shipping address
             * changed in Bread checkout form
             *
             * @param data object
             */
            setShippingRates: function(data) {
                var ratesData = {};
                $.each(data, function(i, rate) {
                    var cost = rate.cost / 100;
                    var method = rate.typeId.split('_');
                    var title = rate.type.split(' - ');
                    ratesData[i] = {
                        'amount': cost,
                        'available': true,
                        'base_amount': cost,
                        'carrier_code': method[0],
                        'carrier_title': title[0],
                        'error_message': "",
                        'method_code': method[1],
                        'method_title': title[1],
                        'price_excl_tax': cost,
                        'price_incl_tax': cost
                    }
                });
                window.checkoutConfig.shippingRates = ratesData;
                shippingService.setShippingRates(ratesData);
            },

            /**
             * Save billing address data in quote
             *
             * @param data object
             * @param token string
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
             * @param address object
             * @return object
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