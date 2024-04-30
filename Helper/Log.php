<?php

namespace Bread\BreadCheckout\Helper;

/**
 * Class Log
 *
 * @package Bread\BreadCheckout\Helper
 */
class Log extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Bread\BreadCheckout\Log\BreadLogger
     */
    private $breadLogger;
    
    private $helper;

    /**
     * Log constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Bread\BreadCheckout\Log\BreadLogger  $breadLogger
     * @param \Bread\BreadCheckout\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Bread\BreadCheckout\Log\BreadLogger $breadLogger,
        \Bread\BreadCheckout\Helper\Data $helper
    ) {
        $this->breadLogger = $breadLogger;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Check if logging is enabled
     *
     * @return bool
     */
    private function logEnabled($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return $this->helper->logEnabled($store);
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
