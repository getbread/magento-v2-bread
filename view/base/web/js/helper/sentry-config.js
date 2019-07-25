define([], function() {

    //todo setup lambda - need API Gateway access

    //todo test sentry config admin setting

    return function(config) {

        var TRACKED_TAG_KEYS = [
            'plugin_version',
            'merchant_api_key',
        ];

        var getConsoleFunc = function(level) {
            switch (level) {
                case 'fatal':
                    return console.error;
                case 'error':
                    return console.error;
                case 'warning':
                    return console.warn;
                case 'info':
                    return console.info;
                case 'debug':
                    return console.log;
            }
        };

        Sentry.init({
            dsn: 'https://a832bffb9c574aaa9e4a113b61f90e9e@sentry.io/1263830',
            beforeSend(event) {
                var isBreadIssue = event.extra && Object.values(event.extra).includes('BreadIssue');

                if (!isBreadIssue) {
                    return null;
                }
                return event;
            }
        });

        Sentry.configureScope(function(scope) {
            scope.setTag('plugin_version', config.pluginVersion);
            scope.setTag('merchant_api_key', config.apiKey);
        });

        document.logIssue = function(level, issueInfo, issue) {

            getConsoleFunc(level)(issue);

            if (!config.isSentryEnabled) {
                return;
            }

            Sentry.withScope(function(scope) {
                scope.setExtra('issue_type', 'BreadIssue');
                scope.setLevel(level);

                Object.keys(issueInfo).forEach(function(key) {
                    var value = JSON.stringify(issueInfo[key]);

                    if (TRACKED_TAG_KEYS.includes(key)) {
                        scope.setTag(key, value);
                    } else {
                        scope.setExtra(key, value);
                    }
                });

                if (typeof issue === 'string') {
                    Sentry.captureMessage(issue);
                } else {
                    Sentry.captureException(issue);
                }
            });

        };
    }
});
