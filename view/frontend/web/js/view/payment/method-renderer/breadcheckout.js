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
        'Magento_Checkout/js/view/billing-address',
        'Magento_Checkout/js/model/shipping-save-processor/default',
        'Magento_Checkout/js/model/shipping-service',
        'Magento_Checkout/js/action/select-shipping-method',
        'Magento_Checkout/js/action/create-shipping-address',
        'Magento_Checkout/js/action/select-shipping-address',
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
              globalMessageList,
              billingAddress,
              defaultProcessor,
              shippingService,
              selectShippingMethodAction,
              createShippingAddress,
              selectShippingAddress,
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
             * Update billing & shipping address data in quote
             *
             * @param data object
             * @param token string
             */
            updateAddresses: function(data, token) {
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
                 * Shipping address
                 */
                var shippingAddressData = this.getAddressData(data.shippingAddress);
                var newShippingAddress = createShippingAddress(shippingAddressData);

                // New address must be selected as a shipping address
                selectShippingAddress(newShippingAddress);
                checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                checkoutData.setNewCustomerShippingAddress(shippingAddressData);

                /**
                 * Shipping method
                 */
                var selectedRate;
                $.each(window.checkoutConfig.shippingRates, function(i, rate) {
                   var shippingCode = rate.carrier_code + '_' + rate.method_code;
                   if (data.shippingMethod == shippingCode) {
                       selectedRate = rate;
                       return false;
                   }
                });

                if (selectedRate) {
                    selectShippingMethodAction(selectedRate);
                    checkoutData.setSelectedShippingRate(data.shippingMethod);
                    defaultProcessor.saveShippingInformation().done(function() {
                        window.checkoutConfig.payment.breadcheckout.transactionId = token;
                    });
                } else {
                    fullScreenLoader.stopLoader();
                    globalMessageList.addErrorMessage({
                        message: 'Please select a shipping method in the Bread checkout window.'
                    });
                }
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
                address.save_in_address_book = ((customer.isLoggedIn() && !billingAddress.customerHasAddresses)) ? 1 : 0;

                return address;
            }

        });
    }
);