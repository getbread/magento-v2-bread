<?php

namespace Bread\BreadCheckout\Log;

/**
 * Class SentryLogger
 * @package Bread\BreadCheckout\Log
 */
class SentryLogger {

    public static function init() {
        try {
            \Sentry\init([
                'dsn' => 'https://a832bffb9c574aaa9e4a113b61f90e9e@sentry.io/1263830',
                'before_send' => function(\Sentry\Event $event) {
                    $isBreadError = in_array('breadError', $event->getFingerprint());

                    if (!$isBreadError) {
                        return null;
                    }
                    return $event;
                }
            ]);
        } catch (\Throwable $e) {
            //error initializing Sentry
            //just fall through without error
        }
    }

    public static function sendError($error) {
        //todo figure out error/info level error reporting
        try {
            \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($error): void {
                $scope->setFingerprint(['breadError']);

                \Sentry\captureException($error);
            });
        } catch (\Throwable $e) {
            //Error with sentry, probably due to it not being installed
            //just fall through without error
        }
    }
}
