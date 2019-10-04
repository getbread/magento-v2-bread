<?php
declare(strict_types=1);

namespace Bread\BreadCheckout\ViewModel;

use Bread\BreadCheckout\Helper\Data;
use Bread\BreadCheckout\Log\Logger;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Module\PackageInfo;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Context;

class Js implements ArgumentInterface
{
    /**
     * @var Data
     */
    public $helper;

    /**
     * @var PackageInfo
     */
    private $packageInfo;

    /**
     * @var CacheInterface
     */
    public $cache;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Logger
     */
    public $logger;

    public function __construct(
        Data $helper,
        PackageInfo $packageInfo,
        CacheInterface $cache,
        Curl $curl,
        Logger $logger
    ) {
        $this->helper = $helper;
        $this->packageInfo = $packageInfo;
        $this->cache = $cache;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * Create JS Include HTML
     *
     * @return string
     */
    public function getJsScriptString(): string
    {
        $breadJsScript = sprintf(
            '<script src="%s" data-api-key="%s"></script>',
            $this->helper->getJsLibLocation(),
            $this->helper->getApiPublicKey()
        );

        return $breadJsScript;
    }

    /**
     * Returns sentry json configuration
     *
     * @return string
     */
    public function getSentryJsonConfiguration(): string
    {
        $dns = $this->getSentryDSN();
        return json_encode(
            [
                '*' => [
                    'Bread_BreadCheckout/js/sentry/sentry-config' => [
                        'dns' => $dns,
                        'pluginVersion' => $this->getModuleVersion(),
                        'apiKey' => $this->helper->getApiPublicKey(),
                        'isSentryEnabled' => $this->helper->isSentryEnabled() && $dns
                    ]
                ]
            ],
            JSON_HEX_TAG
        );
    }

    /**
     * Get current module version
     *
     * @return string
     */
    public function getModuleVersion(): string
    {
        return $this->packageInfo->getVersion('Bread_BreadCheckout');
    }

    /**
     * Get Sentry DSN for magento 2
     *
     * @return string
     */
    private function getSentryDSN()
    {
        $sentryDSNIdentifier = 'sentry_dsn';

        $dsn = $this->cache->load($sentryDSNIdentifier);

        if ($dsn) {
            return $dsn;
        }

        try {
            $this->curl->setCredentials($this->helper->getApiPublicKey(), $this->helper->getApiSecretKey());
            $this->curl->get($this->helper::URL_LAMBDA_SENTRY_DSN);

            $response = json_decode($this->curl->getBody(), true);

            if (isset($response['error']) || !isset($response['dsn'])) {
                $errorMessage = isset($response['error']) ? $response['error']
                    : 'Incorrect Response Format: ' . json_encode($response);
                $this->logger->write(['ERROR WHEN GETTING SENTRY DSN' => $errorMessage]);
                return null;
            }

            $dsn = $response['dsn'];
            $this->cache->save($dsn, $sentryDSNIdentifier, [], 60 * 60);

            return $dsn;
        } catch (\Throwable $e) {
            $this->logger->write(['EXCEPTION WHEN GETTING SENTRY DSN' => $e->getMessage()]);
            return null;
        }
    }
}