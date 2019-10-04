<?php
declare(strict_types=1);

namespace Bread\BreadCheckout\Log;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class BreadLogger
 *
 * @package Bread\BreadCheckout\Log
 */
class Logger extends \Monolog\Logger
{

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * BreadLogger constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $name,
        array $handlers = [],
        array $processors = []
    )
    {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * Check if logging is enabled
     *
     * @return bool
     */
    private function logEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag('payment/breadcheckout/log_enabled');
    }

    /**
     * Wrapper for debug log, if different log level is needed call parent log() method instead
     *
     * @param $data
     * @param array $context
     */
    public function write($data,  array $context = array())
    {
        if ($this->logEnabled()) {
            if (!is_string($data)) {
                $data = print_r($data, true);
            }
            $this->debug($data, $context);
        }
    }
}
