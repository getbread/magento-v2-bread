(function() {
    'use strict';

    var BreadCheckoutPayment = {
        config: null,
        isApproved: false,
        transactionId: null,
        initialized: false,
        sdkReady: false,
        originalPlaceOrder: null,

        init: function() {
            if (this.initialized) return;
            
            var container = document.getElementById('bread-payment-container');
            if (!container) {
                this.watchForContainer();
                return;
            }

            this.initialized = true;
            console.log('[Bread] Initializing Bread checkout payment');
            
            var configAttr = container.getAttribute('data-bread-config');
            if (configAttr) {
                this.config = JSON.parse(configAttr);
                this.isApproved = this.config.isApproved || false;
                this.transactionId = this.config.transactionId || null;
                console.log('[Bread] Config loaded, isApproved:', this.isApproved, 'transactionId:', this.transactionId);
            }

            this.waitForSdk();
            this.bindEvents();
            this.hookIntoHyvaCheckout();
        },

        watchForContainer: function() {
            var self = this;
            var observer = new MutationObserver(function(mutations) {
                var container = document.getElementById('bread-payment-container');
                if (container && !self.initialized) {
                    observer.disconnect();
                    self.init();
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        },

        bindEvents: function() {
            var self = this;
            window.addEventListener('bread:approved', function(e) {
                self.showApproved();
            });
        },

        /**
         * Hook into Hyva Checkout's order.place method
         */
        hookIntoHyvaCheckout: function() {
            var self = this;
            
            // Wait for hyvaCheckout to be available
            var checkHyvaCheckout = function() {
                if (typeof hyvaCheckout !== 'undefined' && hyvaCheckout.order && hyvaCheckout.order.place) {
                    console.log('[Bread] Found hyvaCheckout.order.place, hooking into it');
                    
                    self.originalPlaceOrder = hyvaCheckout.order.place.bind(hyvaCheckout.order);

                    hyvaCheckout.order.place = function() {
                        return self.handlePlaceOrder();
                    };
                    
                    console.log('[Bread] Successfully hooked into hyvaCheckout.order.place');
                } else {
                    console.log('[Bread] hyvaCheckout not ready, waiting...');
                    setTimeout(checkHyvaCheckout, 100);
                }
            };
            
            checkHyvaCheckout();
        },
        handlePlaceOrder: function() {
            var self = this;
            
            console.log('[Bread] handlePlaceOrder called');
            console.log('[Bread] isBreadSelected:', this.isBreadSelected());
            console.log('[Bread] isApproved:', this.isApproved);
            console.log('[Bread] transactionId:', this.transactionId);
            
            if (!this.isBreadSelected()) {
                console.log('[Bread] Bread not selected, calling original place order');
                return this.originalPlaceOrder();
            }
            
            if (this.isApproved && this.transactionId) {
                console.log('[Bread] Already approved, calling original place order');
                return this.originalPlaceOrder();
            }
            
            console.log('[Bread] Need Bread approval, opening modal');
            
            return new Promise(function(resolve, reject) {
                self.pendingResolve = resolve;
                self.pendingReject = reject;
                self.openBreadModal();
            });
        },

        isBreadSelected: function() {
            var breadRadio = document.querySelector('input[name="payment-method-option"][value="breadcheckout"]:checked');
            if (breadRadio) {
                console.log('[Bread] Found checked breadcheckout radio');
                return true;
            }
            
            var methodList = document.getElementById('payment-method-list');
            if (methodList) {
                var selectedMethod = methodList.getAttribute('data-method');
                if (selectedMethod === 'breadcheckout') {
                    console.log('[Bread] Found breadcheckout in data-method');
                    return true;
                }
            }
            
            var container = document.getElementById('bread-payment-container');
            if (container) {
                var paymentView = container.closest('[id^="payment-method-view-"]');
                if (paymentView && paymentView.offsetParent !== null) {
                    console.log('[Bread] Found visible bread container');
                    return true;
                }
            }
            
            return false;
        },

        waitForSdk: function() {
            var self = this;
            var sdkName = this.config.sdkName;
            
            var checkSdk = function() {
                if (window[sdkName]) {
                    self.sdkReady = true;
                    self.setupBreadSdk();
                } else {
                    setTimeout(checkSdk, 100);
                }
            };
            checkSdk();
        },

        setupBreadSdk: function() {
            var sdk = window[this.config.sdkName];
            var self = this;

            var shippingContact = this.config.shippingContact;
            var shippingAddress = shippingContact && shippingContact.address ? {
                address1: shippingContact.address.address1 || '',
                address2: shippingContact.address.address2 || '',
                country: shippingContact.address.country || 'US',
                locality: shippingContact.address.locality || '',
                region: shippingContact.address.region || '',
                postalCode: shippingContact.address.postalCode || ''
            } : null;

            var setupConfig = {
                integrationKey: this.config.integrationKey
            };

            if (shippingAddress && shippingAddress.address1) {
                setupConfig.buyer = {
                    shippingAddress: shippingAddress
                };
            }

            console.log('[Bread] SDK setup config:', setupConfig);
            sdk.setup(setupConfig);
            sdk.setInitMode('manual');
            sdk.on('INSTALLMENT:APPLICATION_DECISIONED', function(application) {
                console.log('[Bread] Application decisioned:', application);
            });
            sdk.on('INSTALLMENT:APPLICATION_CHECKOUT', function(application) {
                console.log('[Bread] Application checkout:', application);
                if (application && application.transactionID) {
                    self.handleCheckoutComplete(application.transactionID);
                }
            });

            console.log('[Bread] SDK setup complete');
        },

        getShippingAddressFromCheckout: function() {
            // Try to get from Hyva checkout state first
            if (typeof hyvaCheckout !== 'undefined' && hyvaCheckout.shipping) {
                var shippingData = hyvaCheckout.shipping.getAddress ? hyvaCheckout.shipping.getAddress() : null;
                if (shippingData && shippingData.street && shippingData.city) {
                    console.log('[Bread] Got shipping from hyvaCheckout.shipping:', shippingData);
                    return {
                        firstName: shippingData.firstname || '',
                        lastName: shippingData.lastname || '',
                        email: shippingData.email || '',
                        phone: shippingData.telephone || '',
                        address: {
                            address1: Array.isArray(shippingData.street) ? shippingData.street[0] : (shippingData.street || ''),
                            address2: Array.isArray(shippingData.street) ? (shippingData.street[1] || '') : '',
                            locality: shippingData.city || '',
                            region: shippingData.region_code || shippingData.region || '',
                            postalCode: shippingData.postcode || '',
                            country: shippingData.country_id || 'US'
                        }
                    };
                }
            }

            // Try to get from Alpine.js component data
            var shippingForm = document.querySelector('[x-data*="shipping"]');
            if (shippingForm && shippingForm.__x) {
                var alpineData = shippingForm.__x.$data;
                if (alpineData && alpineData.address) {
                    console.log('[Bread] Got shipping from Alpine component:', alpineData.address);
                    var addr = alpineData.address;
                    return {
                        firstName: addr.firstname || '',
                        lastName: addr.lastname || '',
                        email: addr.email || '',
                        phone: addr.telephone || '',
                        address: {
                            address1: Array.isArray(addr.street) ? addr.street[0] : (addr.street || ''),
                            address2: Array.isArray(addr.street) ? (addr.street[1] || '') : '',
                            locality: addr.city || '',
                            region: addr.region_code || addr.region || '',
                            postalCode: addr.postcode || '',
                            country: addr.country_id || 'US'
                        }
                    };
                }
            }
            return this.config.shippingContact || null;
        },
        validateShippingAddress: function(shippingContact) {
            if (!shippingContact || !shippingContact.address) {
                return false;
            }
            var addr = shippingContact.address;
            return addr.address1 && addr.locality && addr.region && addr.postalCode && addr.country;
        },

        openBreadModal: function() {
            if (!this.sdkReady) {
                console.log('[Bread] SDK not ready, waiting...');
                var self = this;
                setTimeout(function() { self.openBreadModal(); }, 100);
                return;
            }

            var sdk = window[this.config.sdkName];
            var self = this;

            var shippingContact = this.getShippingAddressFromCheckout();
            var billingContact = this.config.billingContact;

            console.log('[Bread] Opening checkout modal');
            console.log('[Bread] Shipping contact:', shippingContact);
            console.log('[Bread] Billing contact:', billingContact);
            console.log('[Bread] Checkout data:', this.config.checkoutData);

            if (!this.validateShippingAddress(shippingContact)) {
                console.error('[Bread] Invalid shipping address:', shippingContact);
                alert('Please complete your shipping address before proceeding with Bread checkout.');
                if (this.pendingReject) {
                    this.pendingReject('Shipping address required');
                }
                return;
            }

            var shippingAddr = shippingContact.address;
            sdk.setup({
                integrationKey: this.config.integrationKey,
                buyer: {
                    shippingAddress: {
                        address1: shippingAddr.address1,
                        address2: shippingAddr.address2 || '',
                        country: shippingAddr.country || 'US',
                        locality: shippingAddr.locality,
                        region: shippingAddr.region,
                        postalCode: shippingAddr.postalCode
                    }
                }
            });

            var placementObject = {
                allowCheckout: true,
                financingType: 'installment',
                locationType: 'checkout',
                domID: 'bread-checkout-btn-hyva',
                order: {
                    currency: this.config.currency,
                    items: this.config.checkoutData.items || [],
                    subTotal: this.config.checkoutData.subTotal,
                    totalPrice: this.config.checkoutData.totalPrice,
                    totalDiscounts: this.config.checkoutData.totalDiscounts,
                    totalShipping: this.config.checkoutData.totalShipping,
                    totalTax: this.config.checkoutData.totalTax
                },
                billingContact: billingContact,
                shippingContact: shippingContact
            };

            console.log('[Bread] Placement object:', placementObject);

            sdk.setInitMode('manual');
            sdk.registerPlacements([placementObject]);
            sdk.on('INSTALLMENT:APPLICATION_DECISIONED', function(application) {
                console.log('[Bread] Application decisioned:', application);
            });

            sdk.on('INSTALLMENT:APPLICATION_CHECKOUT', function(application) {
                console.log('[Bread] Application checkout:', application);
                if (application && application.transactionID) {
                    self.handleCheckoutComplete(application.transactionID);
                }
            });

            sdk.init();
            sdk.openExperienceForPlacement([placementObject]);
        },

        handleCheckoutComplete: function(txnId) {
            console.log('[Bread] Checkout complete, transaction ID:', txnId);
            var self = this;
            
            this.validatePaymentMethod(txnId)
                .then(function(response) {
                    console.log('[Bread] validatePaymentMethod response:', response);
                    
                    if (response.error) {
                        console.error('[Bread] Payment validation error:', response.error);
                        alert(response.error);
                        if (self.pendingReject) {
                            self.pendingReject(response.error);
                        }
                        return Promise.reject(response.error);
                    }
                    
                    console.log('[Bread] Payment method validated');
                    
                    return self.validateTotals(txnId);
                })
                .then(function(response) {
                    console.log('[Bread] validateTotals response:', response);
                    
                    if (!response.valid) {
                        console.error('[Bread] Totals validation failed');
                        if (self.pendingReject) {
                            self.pendingReject('Totals validation failed');
                        }
                        return Promise.reject('Totals validation failed');
                    }
                    
                    console.log('[Bread] Totals validated, proceeding with order');
                    
                    self.transactionId = txnId;
                    self.isApproved = true;
                    
                    var input = document.getElementById('bread-transaction-id-input');
                    if (input) {
                        input.value = txnId;
                    }
                    
                    if (typeof Livewire !== 'undefined') {
                        Livewire.emit('bread_transaction_complete', txnId);
                    }
                    
                    self.showApproved();
                    
                    window.dispatchEvent(new CustomEvent('bread:approved', {
                        detail: { transactionId: txnId }
                    }));
                    
                    console.log('[Bread] Calling original place order');
                    return self.originalPlaceOrder();
                })
                .then(function(result) {
                    console.log('[Bread] Original place order result:', result);
                    if (self.pendingResolve) {
                        self.pendingResolve(result);
                    }
                })
                .catch(function(error) {
                    console.error('[Bread] Error in checkout flow:', error);
                    if (self.pendingReject) {
                        self.pendingReject(error);
                    }
                });
        },
        validatePaymentMethod: function(txnId) {
            var paymentUrl = this.config.paymentUrl;
            
            console.log('[Bread] Calling validatePaymentMethod:', paymentUrl, 'with txnId:', txnId);
            
            return fetch(paymentUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'token=' + encodeURIComponent(txnId) + 
                      '&currency=' + encodeURIComponent(this.config.currency)
            })
            .then(function(response) {
                console.log('[Bread] validatePaymentMethod HTTP status:', response.status);
                return response.json();
            })
            .catch(function(error) {
                console.error('[Bread] validatePaymentMethod fetch error:', error);
                return { error: error.message };
            });
        },

        validateTotals: function(txnId) {
            var validateTotalsUrl = this.config.validateTotalsUrl;
            
            console.log('[Bread] Calling validateTotals:', validateTotalsUrl, 'with txnId:', txnId);
            
            return fetch(validateTotalsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'bread_transaction_id=' + encodeURIComponent(txnId)
            })
            .then(function(response) {
                console.log('[Bread] validateTotals HTTP status:', response.status);
                return response.json();
            })
            .catch(function(error) {
                console.error('[Bread] validateTotals fetch error:', error);
                return { valid: false, error: error.message };
            });
        },

        showApproved: function() {
            var approvedMsg = document.getElementById('bread-approved-message');
            var buttonContainer = document.getElementById('bread-button-container');
            
            if (approvedMsg) approvedMsg.style.display = 'flex';
            if (buttonContainer) buttonContainer.style.display = 'none';
        }
    };

    window.BreadCheckoutPayment = BreadCheckoutPayment;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            BreadCheckoutPayment.init();
        });
    } else {
        BreadCheckoutPayment.init();
    }

    window.addEventListener('checkout:init', function() {
        console.log('[Bread] checkout:init event received');
        if (!BreadCheckoutPayment.initialized) {
            BreadCheckoutPayment.init();
        }
    });
})();
