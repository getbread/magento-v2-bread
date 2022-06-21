define([], function () {

    var splitPayPromo = function (opts, selector, includeInstallments) {
        if (window.bread === undefined) {
            return;
        }

        var total = null;

        if (opts.hasOwnProperty('customTotal')) {
            total = opts.customTotal;
        } else if (opts.hasOwnProperty('items')) {
            total = opts.items.reduce(function(sum, i) {
                return Math.round(i.price * i.quantity) + sum;
            }, 0);
        } else {
            console.warn('[Bread-SplitPay] failed to calculate total');
            return;
        }

        if (total > 100000) {
            return;
        }

        bread.showSplitPayPromo({
            selector: selector,
            total: total,
            includeInstallments: includeInstallments,
            openModalOnClick: true,
            opts: opts
        });
    };

    return {
        setupSplitPay: function (breadConfig, selector, includeInstallments) {
            splitPayPromo.bind(null, breadConfig, selector, includeInstallments);
            //this.waitForFlagsToLoad(boundSplitPayPromo);
        },
        waitForFlagsToLoad: function (cb) {
            var MAX_SECS_BEFORE_ABORT = 10;
            var TIMEOUT_INTERVAL = 100;
            var RETRIES = MAX_SECS_BEFORE_ABORT * 1000 / TIMEOUT_INTERVAL;
            var ERROR_PREFIX = 'Bread Integration Error: Could not setup promotional label for SplitPay. Reason: ';
            var INTEGRATION_ERROR = 'Could not create Bread SplitPay Promotional Label within 5 seconds. Please verify that the provided selector is valid';

            var retryCount = 0;

            var retry = window.setInterval(function() {
                try {
                    if (window.bread && window.bread.ldflags && window.bread.ldflags._isReady) {
                        window.clearInterval(retry);
                        cb();
                    }
                    if (retryCount < RETRIES) {
                        retryCount += 1;
                    } else {
                        throw new Error(INTEGRATION_ERROR);
                    }
                } catch (err) {
                    console.error(ERROR_PREFIX + err);
                    window.clearInterval(retry);
                    // May also want to do something in this error case so its never "broken"
                }
            }, TIMEOUT_INTERVAL);
        }
    };
});