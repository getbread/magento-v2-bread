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
            breadConfig: {},

            configure: function (data, context) {
                this.breadConfig = {
                    actAsLabel: false,
                    asLowAs: data.asLowAs,
                    customTotal: this.round(quote.getTotals()._latestValue.base_grand_total),
                    buttonLocation: window.checkoutConfig.payment.breadcheckout.breadConfig.buttonLocation,
                    disableEditShipping: true,
                    onShowCheckoutError: function (message) {
                        var errorInfo = {
                            bread_config: window.checkoutConfig.payment.breadcheckout.breadConfig
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
                            document.logBreadIssue('error', errorInfo,  'tx_token undefined in done callback');
                        }
                    }
                };

                /**
                 * Optional params
                 */

                if(!quote.isVirtual()) {
                    this.breadConfig.shippingOptions =  [data.shippingOptions];
                    this.breadConfig.tax = this.round(quote.getTotals()._latestValue.base_tax_amount);
                } else {
                    this.breadConfig.requireShippingContact = false;
                }

                if(data.embeddedCheckout) {
                    this.breadConfig.formId = data.formId;
                } else {
                    this.breadConfig.buttonId = data.buttonId;
                }

                if (!window.checkoutConfig.payment.breadcheckout.isHealthcare) {
                    this.breadConfig.items = data.items;
                }

                if (data.targetedFinancingStatus.shouldUseFinancingId) {
                    this.breadConfig.financingProgramId = data.targetedFinancingStatus.id;
                }

                if (typeof data.billingContact !== 'undefined' && data.billingContact != false) {
                    this.breadConfig.billingContact = data.billingContact;
                }

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
                        if (typeof bread !== 'undefined') {
                            bread.showCheckout(self.breadConfig);
                            fullScreenLoader.stopLoader();
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
                if(typeof this.breadConfig.shippingOptions === "undefined" && quote.isVirtual()) {
                    this.breadConfig.customTotal = this.round(quote.getTotals()._latestValue.base_grand_total);
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
                            self.breadConfig.customTotal = self.round(quote.getTotals()._latestValue.base_grand_total);
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
                        if (data.shippingContact != false) {
                            this.breadConfig.shippingContact = data.shippingContact;
                        }

                        if (data.billingContact != false) {
                            this.breadConfig.billingContact = data.billingContact;
                            this.breadConfig.billingContact.email = (data.billingContact.email) ?
                            data.billingContact.email :
                            checkout.getValidatedEmailValue();
                        }

                        if(quote.isVirtual()) {
                            this.breadConfig.shippingContact = data.billingContact;
                        }

                        if(isEmbedded === false) {
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
                
                var discountAmount =- this.round(quote.getTotals()._latestValue.discount_amount);
                if (discountAmount > 0) {
                    this.breadConfig.discounts = [{
                        amount: discountAmount,
                        description: (quote.getTotals()._latestValue.coupon_code !== null) ?
                        quote.getTotals()._latestValue.coupon_code :
                        "Discount"
                    }];
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