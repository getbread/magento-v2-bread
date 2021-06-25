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

        configure: function (data, context) {
            this.breadConfig = {
                actAsLabel: false,
                asLowAs: data.asLowAs,
                customTotal: this.round(quote.getTotals()._latestValue.base_grand_total),
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
                        
                    } else {
                        var errorInfo = {
                            err: err,
                            bread_config: window.checkoutConfig.payment.breadcheckout.breadConfig
                        };
                        document.logBreadIssue('error', errorInfo, 'tx_token undefined in done callback');
                    }
                },
                
                onCheckout: function(application) {
                    context.buttonCallback(application.transactionID);
                },
                
                onApproval: function(application) {
                    console.log('Approved');
                }
            };

            /**
             * Optional params
             */

            if (!quote.isVirtual()) {
                this.breadConfig.shippingOptions = [data.shippingOptions];
                this.breadConfig.tax = this.round(quote.getTotals()._latestValue.base_tax_amount);
            } else {
                this.breadConfig.requireShippingContact = false;
            }

            if (data.embeddedCheckout) {
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

                    if (window.checkoutConfig.payment.breadcheckout.breadVersion === 'bread_2') {
                        if (typeof window.BreadPayments !== 'undefined') {
                            let bread_sdk = window.BreadPayments;
                            let onApproved = function onApproved(application) {
                                // eslint-disable-next-line no-console
                                console.log({application});
                                //alert('Got approval in host: ', application.id);
                            };
                            let onCheckout = function onCheckout(application) {
                                // eslint-disable-next-line no-console
                                console.log('Checkout has been completed');
                                console.log({application});
                                self.breadButtonCallback(application.transactionID);
                            };
                            bread_sdk.setup({
                                integrationKey: window.checkoutConfig.payment.breadcheckout.breadConfigV2.integrationKey,
                                buyer: {
                                    shippingAddress: {
                                        address1: self.breadConfig.billingContact.address,
                                        address2: self.breadConfig.billingContact.address2,
                                        country: 'US',
                                        locality: self.breadConfig.billingContact.city,
                                        region: self.breadConfig.billingContact.state,
                                        postalCode: self.breadConfig.billingContact.zip
                                    }
                                }
                            });
                            bread_sdk.on('INSTALLMENT:APPLICATION_DECISIONED', self.breadConfig.onApproval);
                            bread_sdk.on('INSTALLMENT:APPLICATION_CHECKOUT', self.breadConfig.onCheckout);

                            let totalPrice = (self.breadConfig.customTotal + self.breadConfig.tax) - window.checkoutConfig.payment.breadcheckout.breadConfig.discounts.value;
                            bread_sdk.registerPlacements([{
                                    allowCheckout: true,
                                    domID: window.checkoutConfig.payment.breadcheckout.breadConfig.buttonId,
                                    order: {
                                        currency: 'USD',
                                        items: window.checkoutConfig.payment.breadcheckout.breadConfig.items,
                                        subTotal: {
                                            currency: 'USD',
                                            value: self.breadConfig.customTotal
                                        },
                                        totalPrice: {value: totalPrice, currency: 'USD'},
                                        totalDiscounts: window.checkoutConfig.payment.breadcheckout.breadConfig.discounts,
                                        totalShipping: {value: 0, currency: 'USD'},
                                        totalTax: {currency: 'USD', value: self.breadConfig.tax}
                                    }
                                }]);

                            bread_sdk.__internal__.setInitMode('manual');
                            //bread_sdk.__internal__.setRenderMode('modal');
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
        
        setBreadTransactionId: function(transactionId) {
            window.checkoutConfig.payment.breadcheckout.transactionId = transactionId;
        },
        
        breadButtonCallback: function (token) {
            console.log('Bread button callback');
            this.setBreadTransactionId(token);
            var paymentUrl = window.checkoutConfig.payment.breadcheckout.breadConfig.paymentUrl;
            var breadConfig = window.checkoutConfig.payment.breadcheckout.breadConfig;
            $.ajax(
                    {
                        url: paymentUrl,
                        data: {token: token},
                        type: 'post',
                        context: this,
                        beforeSend: function () {
                            fullScreenLoader.startLoader();
                        }
                    }
            ).done(function (response) {
                console.log('We are done pushing the token for Bread API validation');
                console.log(response);
                var errorInfo;
                try {
                    errorInfo = {
                        bread_config: breadConfig,
                        response: response,
                        tx_id: token,
                    };
                    if (response !== null && typeof response === 'object') {
                        if (response.error) {
                            document.logBreadIssue('error', errorInfo, 'Error validating payment method');
                            alert(response.error);
                        } else {
                            $.when(
                                    this.updateAddress(response, errorInfo),
                                    this.validateTotals()
                                    ).done(
                                    
                                    ).fail(
                                    
                                    );
                        }
                    } else {
                        document.logBreadIssue('error', errorInfo, 'Response from ' + paymentUrl + ' was not of type Object');
                    }
                } catch (e) {
                    errorInfo = {
                        response: response,
                        tx_id: token,
                        bread_config: breadConfig,
                    };
                    document.logBreadIssue('error', errorInfo, e);
                }
            });

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
            console.log('Set shipping information');
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

                        if (quote.isVirtual()) {
                            this.breadConfig.shippingContact = data.billingContact;
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
            if (discountAmount > 0) {
                
                if (window.checkoutConfig.payment.breadcheckout.breadVersion === 'bread_2') {
                    this.breadConfig.discounts = {
                        currency: 'USD',
                        value: discountAmount
                    };
                } else {

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
