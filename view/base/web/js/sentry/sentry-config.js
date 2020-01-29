define(
    ['sentryBundle'], function () {

        return function (config) {

            var TRACKED_TAG_KEYS = [
                'plugin_version',
                'merchant_api_key',
                'tx_id',
            ];

            var getConsoleFunc = function (level) {
                //don't print to console for info and debug level
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

            if (config.isSentryEnabled) {
                try {
                    Sentry.init(
                        {
                            dsn: config.dsn,
                            beforeSend(event) {
                                if (!event || !event.extra) {
                                    return null;
                                }

                                var isBreadIssue = null;

                                Object.keys(event.extra).map(function(key) {
                                    if (event.extra[key] === 'BreadIssue') {
                                        isBreadIssue = true;
                                    }
                                });

                                if (isBreadIssue) {
                                    return event;
                                }

                                return null;
                            }
                        }
                    );

                    Sentry.configureScope(
                        function (scope) {
                            scope.setTag('plugin_version', config.pluginVersion);
                            scope.setTag('merchant_api_key', config.apiKey);
                        }
                    );
                } catch (e) {
                    config.isSentryEnabled = false;
                }
            }

            document.logBreadIssue = function (level, issueInfo, issue) {

                getConsoleFunc(level)(issue);

                if (!config.isSentryEnabled) {
                    return;
                }

                Sentry.withScope(
                    function (scope) {
                        scope.setExtra('issue_type', 'BreadIssue');
                        scope.setLevel(level);

                        Object.keys(issueInfo).forEach(
                            function (key) {
                                var value = JSON.stringify(issueInfo[key]);

                                if (TRACKED_TAG_KEYS.indexOf(key) !== -1) {
                                    scope.setTag(key, value);
                                } else {
                                    scope.setExtra(key, value);
                                }
                            }
                        );

                        if (typeof issue === 'string') {
                            Sentry.captureMessage(issue);
                        } else {
                            Sentry.captureException(issue);
                        }
                    }
                );

            };
        }
    }
);
