/**
 * Bread 2.0/platform configuration
 * 
 * @since 2.0.2
 */

define(
    [
        'jquery',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Ui/js/modal/alert'
    ],
    /**
     * 
     * @param {type} $
     * @param {type} fullScreenLoader
     * @param {type} quote
     * @param {type} checkout
     * @param {type} alert
     * @returns {button-config-platformL#18.button-config-platformAnonym$1}
     */
    function($, fullScreenLoader, quote, checkout, alert) {
        return {
            config: undefined,
            wasSetup: false,
            /* 
             * Fix issue in Magento < 2.6.0 where grand_total and base_grand_total don't match when using taxes or other extensions
             * grand_total is the total in selected currency, base_grand_total is in store's default currency   
             * Continue to use grand_total for multi-currency setups
             */
            getTotal: function () {
                let totals = quote.getTotals()._latestValue;
                let grandTotal = totals.base_grand_total;
                if (totals.quote_currency_code !== totals.base_currency_code) {
                    grandTotal = totals.grand_total;
                }
                return grandTotal;
            },

            configure: function(data, context) {
                document.logBreadIssue = function(level, issue) {
                    switch (level) {
                        case 'fatal':
                            return console.error;
                        case 'error':
                            return console.error;
                        case 'warning':
                            return console.warn;
                        case 'info':
                            return function (issue) {};
                        case 'debug':
                            return function (issue) {};
                    }
                };
                this.config = {
                    customTotal: this.round(this.getTotal()),
                    buttonLocation: window.checkoutConfig.payment.breadcheckout.breadConfig.buttonLocation,

                    //Bread SDK API onCheckout callback
                    onCheckout: function(application) {
                        context.buttonCallback(application.transactionID);
                    },

                    //Bread SDK API onApproval callback
                    onApproval: function(application) {
                        //@todo Indicate action to run when the script receives an approved callback
                    }

                };

                /**
                 * Populate set of optional params for the SDK
                 */
                if (!quote.isVirtual()) {
                    this.config.shippingOptions = [data.shippingOptions];
                    this.config.tax = this.round(quote.getTotals()._latestValue.tax_amount);
                } else {
                    this.config.requireShippingContact = false;
                }

                if (data.embeddedCheckout) {
                    this.config.buttonId = data.formId;
                } else {
                    this.config.buttonId = data.buttonId;
                }

                if (typeof data.billingContact !== 'undefined' && data.billingContact !== false) {
                    this.config.billingContact = data.billingContact;
                }

                //Populate the list of items
                let items = window.checkoutConfig.payment.breadcheckout.breadConfig.items;

                let itemsObject = [];
                //For Healthcare mode we are not sending cart items
                if (!window.checkoutConfig.payment.breadcheckout.isHealthcare) {
                    for (var i = 0; i < items.length; i++) {
                        let item = {
                            name: items[i].name,
                            quantity: items[i].quantity,
                            shippingCost: { value: 0, currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode },
                            shippingDescription: '',
                            unitTax: { value: 0, currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode },
                            unitPrice: {
                                currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode,
                                value: items[i].price
                            },
                            itemUrl: items[i].detailUrl,
                            sku: items[i].sku
                        };

                        itemsObject.push(item);
                    }
                }
                this.config.items = itemsObject;
            },

            init: function() {
                this.setShippingInformation();
            },
            initEmbedded() {
                this.setShippingInformation();
                fullScreenLoader.stopLoader();
            },
            _init: function() {
                var self = this;
                if (window.checkoutConfig.payment.breadcheckout.transactionId === null) {
                    this.checkShippingOptions(function() {
                        if (typeof window.BreadPayments !== 'undefined' || typeof window.RBCPayPlan !== 'undefined') {
                            let bread_sdk = null;
                            let shippingOptions = {
                                value: 0,
                                currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode
                            };
                            if (self.config.shippingOptions.length > 0) {
                                shippingOptions.value = self.config.shippingOptions[0].cost;
                            }

                            let subTotalPrice = (self.config.customTotal + self.config.discounts.value) - (shippingOptions.value + self.config.tax);
                            let fulfillmentType = self.config.shippingContact.fulfillmentType;
                            let bopisPayload = self.createBopisPayload(fulfillmentType);

                            let placementObject = {
                                allowCheckout: true,
                                financingType: "installment",
                                locationType: "checkout",
                                domID: self.config.buttonId,
                                order: {
                                    ...bopisPayload,
                                    currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode,
                                    items: self.config.items,
                                    subTotal: {
                                        currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode,
                                        value: subTotalPrice
                                    },
                                    totalPrice: { value: self.config.customTotal, currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode },
                                    totalDiscounts: self.config.discounts,
                                    totalShipping: shippingOptions,
                                    totalTax: { currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode, value: self.config.tax }
                                }
                            };
                            if (window.checkoutConfig.payment.breadcheckout.client === 'RBC') {
                                bread_sdk = window.RBCPayPlan;
                            } else {
                                bread_sdk = window.BreadPayments;
                            }
                            bread_sdk.setup({
                                integrationKey: window.checkoutConfig.payment.breadcheckout.integrationKey,
                                containerID: self.config.buttonId,
                                buyer: {
                                    shippingAddress: {
                                        address1: self.config.shippingContact.address,
                                        address2: self.config.shippingContact.address2,
                                        country: window.checkoutConfig.payment.breadcheckout.country,
                                        locality: self.config.shippingContact.city,
                                        region: self.config.shippingContact.state,
                                        postalCode: self.config.shippingContact.zip
                                    }
                                }
                            });

                            if (!this.wasSetup) {
                                bread_sdk.setInitMode('manual');
                                if (window.checkoutConfig.payment.breadcheckout.breadConfig.embeddedCheckout) {
                                    bread_sdk.setEmbedded(true);
                                }

                                bread_sdk.__internal__.setAutoRender(false);
                                bread_sdk.registerPlacements([placementObject]);

                                bread_sdk.on('INSTALLMENT:APPLICATION_DECISIONED', self.config.onApproval);
                                bread_sdk.on('INSTALLMENT:APPLICATION_CHECKOUT', self.config.onCheckout);
                                bread_sdk.init();
                                this.wasSetup = true;
                            } else {
                                bread_sdk.registerPlacements([placementObject]);
                                bread_sdk.openExperienceForPlacement([placementObject]);
                            }
                            fullScreenLoader.stopLoader();
                        }
                    });
                }
            },
            createBopisPayload: function(fulfillmentType, hasPickupInformation = true) {
                var self = this;
                if (fulfillmentType === 'PICKUP' && hasPickupInformation) {
                    return {
                        pickupInformation: {
                            address: {
                                address1: self.config.pickupInformation.address,
                                address2: self.config.pickupInformation.address2,
                                locality: self.config.pickupInformation.city,
                                postalCode: self.config.pickupInformation.zip,
                                region: self.config.pickupInformation.state,
                                country: window.checkoutConfig.payment.breadcheckout.country,
                            },
                        },
                        fulfillmentType: fulfillmentType,
                    };
                } else {
                    return { fulfillmentType: fulfillmentType };
                }
            },
            /**
             * Makes sure shipping options are up to date
             */
            checkShippingOptions: function(cb) {
                var self = this;

                /**
                 * This part assumed that the shipping options will never change
                 * if(typeof this.breadConfig.shippingOptions !== "undefined" && this.breadConfig.shippingOptions[0] !== false) {
                 cb();
                 } else */
                if (typeof this.config.shippingOptions === "undefined" && quote.isVirtual()) {
                    this.config.customTotal = this.round(this.getTotal());
                    cb();
                } else {
                    /* ocs save selected shipping method */
                    var shippingOptionUrl = window.checkoutConfig.payment.breadcheckout.shippingOptionUrl;
                    $.ajax({
                        url: shippingOptionUrl,
                        type: 'post',
                        context: this
                    }).done(
                        function(data) {
                            self.config.shippingOptions = [data];
                            self.config.customTotal = self.round(this.getTotal());
                            cb();
                        }
                    ).fail(
                        function(error) {
                            var errorInfo = {
                                bread_config: self.config
                            };
                            document.logBreadIssue(
                                'error', errorInfo,
                                'Error code returned when calling ' + shippingOptionUrl + ', with status: ' + error.statusText
                            );
                        }
                    );
                }
            },

            /**
             * Sets coupon discount
             */
            setCouponDiscounts: function() {
                let discountAmount = -this.round(quote.getTotals()._latestValue.discount_amount);
                this.config.discounts = {
                    currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode,
                    value: 0
                };
                if (discountAmount > 0) {
                    this.config.discounts.value = discountAmount;
                    this.config.discounts.description = (quote.getTotals()._latestValue.coupon_code !== null) ?
                        quote.getTotals()._latestValue.coupon_code :
                        "Discount";
                }
                /* this is needed if coupon is removed to update total price */
                this.config.customTotal = this.round(this.getTotal());
            },
            /**
             * Get updated quote data and initialize
             */
            setShippingInformation: function() {
                var configDataUrl = window.checkoutConfig.payment.breadcheckout.configDataUrl;
                $.ajax({
                    url: configDataUrl,
                    type: 'post',
                    context: this,
                    beforeSend: function() {
                        fullScreenLoader.startLoader();
                    }
                }).done(
                    function(data) {
                        if (data.shippingContact !== false) {
                            if (data.shippingContact.fulfillmentType === 'PICKUP') {
                                this.config.pickupInformation = data.shippingContact;
                            }
                            this.config.shippingContact = data.shippingContact;
                        }

                        if (data.billingContact !== false) {
                            this.config.billingContact = data.billingContact;

                            this.config.billingContact.email = (data.billingContact.email) ?
                                data.billingContact.email :
                                checkout.getValidatedEmailValue();
                        }
                        if (quote.isVirtual()) {
                            this.config.shippingContact = data.billingContact;
                        }

                        this._init();

                    }
                ).fail(
                    function(error) {
                        var errorInfo = {
                            bread_config: this.breadConfig
                        };
                        document.logBreadIssue(
                            'error', errorInfo,
                            'Error code returned when calling ' + configDataUrl + ', with status: ' + error.statusText
                        );
                    }
                );
            },
            /**
             * Round float to 2 decimal places then convert to integer
             */
            round: function(value) {
                return Math.round(value * 100);
            }
        };
    }
);