<?php

namespace Bread\BreadCheckout\Helper;

/**
 * Class Log
 *
 * @package Bread\BreadCheckout\Helper
 */
class Log extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_CONFIG_LOG_ENABLED = 'payment/breadcheckout/bread_advanced/log_enabled';

    /**
     * @var \Bread\BreadCheckout\Log\BreadLogger
     */
    private $breadLogger;

    /**
     * Log constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Bread\BreadCheckout\Log\BreadLogger  $breadLogger
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Bread\BreadCheckout\Log\BreadLogger $breadLogger
    ) {
        parent::__construct($context);
        $this->breadLogger = $breadLogger;
    }

    /**
     * Check if logging is enabled
     *
     * @return bool
     */
    private function logEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_CONFIG_LOG_ENABLED);
    }

    /**
     * @param $data
     */
    public function log($data)
    {
        if ($this->logEnabled()) {
            if (!is_string($data)) {
                $data = print_r($data, true);
            }
            $this->breadLogger->debug($data);
        }
    }

    /**
     * @param $data
     */
    public function info($data)
    {
        if ($this->logEnabled()) {
            if (!is_string($data)) {
                $data = print_r($data, true);
            }
            $this->breadLogger->info($data);
        }
    }
}
