/**
 * Configure payment data and init bread checkout
 */
define(
        [
            'jquery',
            'Magento_Checkout/js/model/full-screen-loader',
            'Magento_Checkout/js/model/quote',
            'Magento_Checkout/js/checkout-data',
            'Magento_Ui/js/modal/alert'
        ], function ($, fullScreenLoader, quote, checkout, alert) {

    return {
        breadConfig: undefined,

        breadConfigV2: undefined,

        configure: function (data, context) {
            this.breadConfig = {
                actAsLabel: false,
                asLowAs: data.asLowAs,
                customTotal: this.round(quote.getTotals()._latestValue.base_grand_total),
                embeddedCheckout: window.checkoutConfig.payment.breadcheckout.breadConfig.embeddedCheckout,
                buttonLocation: window.checkoutConfig.payment.breadcheckout.breadConfig.buttonLocation,
                disableEditShipping: true,
                onShowCheckoutError: function (message) {
                    var errorInfo = {
                        bread_config: window.checkoutConfig.payment.breadcheckout.breadConfig,
                    };
                    document.logBreadIssue('error', errorInfo, 'onShowCheckoutError triggered');

                    alert(
                            {
                                content: message.data
                            }
                    );
                },

                done: function (err, tx_token) {
                    if (tx_token !== undefined) {
                        context.buttonCallback(tx_token);
                    } else {
                        var errorInfo = {
                            err: err,
                            bread_config: window.checkoutConfig.payment.breadcheckout.breadConfig
                        };
                        document.logBreadIssue('error', errorInfo, 'tx_token undefined in done callback');
                    }
                }
            };

            this.breadConfigV2 = {
                customTotal: this.round(quote.getTotals()._latestValue.base_grand_total),

                onCheckout: function (application) {
                    context.buttonCallback(application.transactionID);
                },

                onApproval: function (application) {}
            };

            /**
             * Optional params
             */

            if (!quote.isVirtual()) {
                this.breadConfig.shippingOptions = [data.shippingOptions];
                this.breadConfig.tax = this.round(quote.getTotals()._latestValue.base_tax_amount);
                //
                this.breadConfigV2.shippingOptions = [data.shippingOptions];
                this.breadConfigV2.tax = this.round(quote.getTotals()._latestValue.base_tax_amount);
            } else {
                this.breadConfig.requireShippingContact = false;
            }

            if (data.embeddedCheckout) {
                this.breadConfig.formId = data.formId;
                //
                this.breadConfigV2.formId = data.formId;
            } else {
                this.breadConfig.buttonId = data.buttonId;
                //
                this.breadConfigV2.buttonId = data.buttonId;
            }

            if (!window.checkoutConfig.payment.breadcheckout.isHealthcare) {
                this.breadConfig.items = data.items;
            }

            if (data.targetedFinancingStatus.shouldUseFinancingId) {
                this.breadConfig.financingProgramId = data.targetedFinancingStatus.id;
            }

            if (typeof data.billingContact !== 'undefined' && data.billingContact !== false) {
                this.breadConfig.billingContact = data.billingContact;
                //
                this.breadConfigV2.billingContact = data.billingContact;
            }

            //Configure items for Bread V2
            let items = window.checkoutConfig.payment.breadcheckout.breadConfig.items;

            let itemsObject = [];
            for (var i = 0; i < items.length; i++) {
                let item = {
                    name: items[i].name,
                    quantity: items[i].quantity,
                    shippingCost: {value: 0, currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode},
                    shippingDescription: '',
                    unitTax: {value: 0, currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode},
                    unitPrice: {
                        currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode,
                        value: items[i].price
                    }
                };

                itemsObject.push(item);
            }
            this.breadConfigV2.items = itemsObject;

        },

        /**
         * Public init method, sets shipping information
         */
        init: function () {
            this.setShippingInformation(false);
        },

        /**
         * Bread modal init
         *
         * @private
         */
        _init: function () {
            var self = this;
            if (window.checkoutConfig.payment.breadcheckout.transactionId === null) {
                this.checkShippingOptions(function () {
                    if (window.checkoutConfig.payment.breadcheckout.apiVersion === 'bread_2') {
                        if (typeof window.BreadPayments !== 'undefined' || typeof window.RBCPayPlan !== 'undefined') {
                            let bread_sdk = null;
                            if(window.checkoutConfig.payment.breadcheckout.client === 'RBC') {
                                bread_sdk = window.RBCPayPlan;
                            } else {
                                bread_sdk = window.BreadPayments;
                            }                         
                            bread_sdk.setup({
                                integrationKey: window.checkoutConfig.payment.breadcheckout.integrationKey,
                                buyer: {
                                    shippingAddress: {
                                        address1: self.breadConfigV2.billingContact.address,
                                        address2: self.breadConfigV2.billingContact.address2,
                                        country: window.checkoutConfig.payment.breadcheckout.country,
                                        locality: self.breadConfigV2.billingContact.city,
                                        region: self.breadConfigV2.billingContact.state,
                                        postalCode: self.breadConfigV2.billingContact.zip
                                    }
                                }
                            });

                            bread_sdk.on('INSTALLMENT:APPLICATION_DECISIONED', self.breadConfigV2.onApproval);
                            bread_sdk.on('INSTALLMENT:APPLICATION_CHECKOUT', self.breadConfigV2.onCheckout);

                            let shippingOptions = {
                                value: 0,
                                currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode
                            };
                            if (self.breadConfigV2.shippingOptions.length > 0) {
                                shippingOptions.value = self.breadConfig.shippingOptions[0].cost;
                            }

                            let subTotalPrice = (self.breadConfigV2.customTotal + self.breadConfigV2.discounts.value) - (shippingOptions.value + self.breadConfigV2.tax);

                            let placementObject = {
                                allowCheckout: true,
                                domID: 'bread-checkout-btn', //window.checkoutConfig.payment.breadcheckout.breadConfig.buttonId,
                                order: {
                                    currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode,
                                    items: self.breadConfigV2.items,
                                    subTotal: {
                                        currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode,
                                        value: subTotalPrice
                                    },
                                    totalPrice: {value: self.breadConfigV2.customTotal, currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode},
                                    totalDiscounts: self.breadConfigV2.discounts,
                                    totalShipping: shippingOptions,
                                    totalTax: {currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode, value: self.breadConfig.tax}
                                }
                            };
                            if (self.breadConfig.embeddedCheckout) {
                                bread_sdk.__internal__.setEmbedded(true);
                            }
                            bread_sdk.__internal__.setAutoRender(false);
                            bread_sdk.registerPlacements([placementObject]);
                            bread_sdk.__internal__.setInitMode('manual');
                            bread_sdk.__internal__.init();
                            fullScreenLoader.stopLoader();
                        }
                    } else {
                        if (typeof bread !== 'undefined') {
                            bread.showCheckout(self.breadConfig);
                            fullScreenLoader.stopLoader();
                        }
                    }

                });
            }
        },

        /**
         * Makes sure shipping options are up to date
         */
        checkShippingOptions: function (cb) {
            var self = this;

            /**
             * This part assumed that the shipping options will never change
             * if(typeof this.breadConfig.shippingOptions !== "undefined" && this.breadConfig.shippingOptions[0] !== false) {
             cb();
             } else */
            if (typeof this.breadConfig.shippingOptions === "undefined" && quote.isVirtual()) {
                this.breadConfig.customTotal = this.round(quote.getTotals()._latestValue.base_grand_total);
                this.breadConfigV2.customTotal = this.round(quote.getTotals()._latestValue.base_grand_total);
                cb();
            } else {
                /* ocs save selected shipping method */
                var shippingOptionUrl = window.checkoutConfig.payment.breadcheckout.shippingOptionUrl;
                $.ajax(
                        {
                            url: shippingOptionUrl,
                            type: 'post',
                            context: this
                        }
                ).done(
                        function (data) {
                            self.breadConfig.shippingOptions = [data];
                            self.breadConfigV2.shippingOptions = [data];
                            self.breadConfig.customTotal = self.round(quote.getTotals()._latestValue.base_grand_total);
                            self.breadConfigV2.customTotal = self.round(quote.getTotals()._latestValue.base_grand_total);

                            cb();
                        }
                ).fail(
                        function (error) {
                            var errorInfo = {
                                bread_config: self.breadConfig
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
         * Public init for embedded checkout, sets shipping information
         */
        embeddedCheckout: function () {
            this.setShippingInformation(true);
            fullScreenLoader.stopLoader();
        },

        /**
         * Bread Embedded init
         *
         * @private
         */
        _initEmbedded: function () {
            var self = this;
            if (window.checkoutConfig.payment.breadcheckout.transactionId === null) {
                this.checkShippingOptions(function () {
                    if (typeof bread !== 'undefined') {
                        bread.checkout(self.breadConfig);
                        fullScreenLoader.stopLoader();
                    }
                });
            }
        },

        /**
         * Get updated quote data and initialize
         */
        setShippingInformation: function (isEmbedded) {
            var configDataUrl = window.checkoutConfig.payment.breadcheckout.configDataUrl;
            $.ajax(
                    {
                        url: configDataUrl,
                        type: 'post',
                        context: this,
                        beforeSend: function () {
                            fullScreenLoader.startLoader();
                        }
                    }
            ).done(
                    function (data) {
                        if (data.shippingContact !== false) {
                            this.breadConfig.shippingContact = data.shippingContact;
                            this.breadConfigV2.shippingContact = data.shippingContact;
                        }

                        if (data.billingContact !== false) {
                            this.breadConfig.billingContact = data.billingContact;
                            //
                            this.breadConfigV2.billingContact = data.billingContact;
                            this.breadConfig.billingContact.email = (data.billingContact.email) ?
                                    data.billingContact.email :
                                    checkout.getValidatedEmailValue();
                            //
                            this.breadConfigV2.billingContact.email = (data.billingContact.email) ?
                                    data.billingContact.email :
                                    checkout.getValidatedEmailValue();

                        }

                        if (quote.isVirtual()) {
                            this.breadConfig.shippingContact = data.billingContact;
                            //
                            this.breadConfigV2.shippingContact = data.billingContact;
                        }
                        if (isEmbedded === false) {
                            this._init();
                        } else {
                            this._initEmbedded();
                        }

                    }
            ).fail(
                    function (error) {
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
         * Sets coupon discount
         */
        setCouponDiscounts: function () {
            var discountAmount = -this.round(quote.getTotals()._latestValue.discount_amount);
            this.breadConfigV2.discounts = {
                currency: window.checkoutConfig.payment.breadcheckout.breadConfig.currencyCode,
                value: 0
            };

            if (window.checkoutConfig.payment.breadcheckout.apiVersion === 'bread_2') {
                if (discountAmount > 0) {
                    this.breadConfig.discounts = [{
                            amount: discountAmount,
                            description: (quote.getTotals()._latestValue.coupon_code !== null) ?
                                    quote.getTotals()._latestValue.coupon_code :
                                    "Discount"
                        }];
                    this.breadConfigV2.discounts.value = discountAmount;
                }
            } else {
                if (discountAmount > 0) {
                    this.breadConfig.discounts = [{
                            amount: discountAmount,
                            description: (quote.getTotals()._latestValue.coupon_code !== null) ?
                                    quote.getTotals()._latestValue.coupon_code :
                                    "Discount"
                        }];
                }
            }
            /* this is needed if coupon is removed to update total price */
            this.breadConfig.customTotal = this.round(quote.getTotals()._latestValue.base_grand_total);
        },

        /**
         * Round float to 2 decimal places then convert to integer
         */
        round: function (value) {
            return Math.round(value * 100);
        }
    };
}
);