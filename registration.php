<?php
\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Bread_BreadCheckout',
    __DIR__
);

use Bread\BreadCheckout\Log\SentryLogger;

SentryLogger::init();
