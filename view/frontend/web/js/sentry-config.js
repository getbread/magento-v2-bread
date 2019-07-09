Sentry.init({
    dsn: 'https://a832bffb9c574aaa9e4a113b61f90e9e@sentry.io/1263830',
    beforeSend(event) {
        var isBreadError = event.fingerprint && event.fingerprint.includes('breadError');

        if (!isBreadError) {
            return null;
        }
        return event;
    }
});

var handleError = function(message, extraContext) {
    Sentry.withScope(function(scope) {

        if (extraContext) {
            extraContext.forEach(function(context) {
                scope.setExtra(context.key, context.value);
            });
        }

        // add fingerprint to distinguish custom thrown errors from generic errors from outside Bread
        scope.setFingerprint(['breadError']);

        Sentry.captureMessage(message);
    });

    console.error(message);
};