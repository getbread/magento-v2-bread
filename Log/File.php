<?php

namespace Bread\BreadCheckout\Log;

class File extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = BreadLogger::DEBUG;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/bread-payment.log';
}
