<?php

namespace Bread\BreadCheckout\Log;

class File extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/bread-payment.log';
}
