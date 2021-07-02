<?php
/**
 * Class Bread_BreadCheckout_Block_Js
 *
 * @author Bread   copyright   2016
 * @author Joel    @Mediotype
 * @author Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Block;

class Js extends \Magento\Framework\View\Element\Text
{
    /**
     * @var \Bread\BreadCheckout\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Framework\Module\PackageInfo
     */
    private $packageInfo;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    public $cache;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Bread\BreadCheckout\Helper\Data $helper,
        \Magento\Framework\Module\PackageInfo $packageInfo,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Bread\BreadCheckout\Helper\Log $logger,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->packageInfo = $packageInfo;
        $this->cache = $cache;
        $this->curl = $curl;
        $this->logger = $logger;

        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Inject integration if module is active
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->isActive()) {
            return $this->getJsScriptsString();
        }

        return '';
    }

    /**
     * Create JS Include HTML
     *
     * @return string
     */
    protected function getJsScriptsString()
    {
        $moduleVersionComment = sprintf('<!-- BreadCheckout Module Version: %s -->', $this->getModuleVersion());

        $sentryConfigScript =
            '<script type="text/x-magento-init">
                {
                    "*": {
                        "Bread_BreadCheckout/js/sentry/sentry-config": {
                            "dsn": "%s",
                            "pluginVersion": "%s",
                            "apiKey": "%s",
                            "isSentryEnabled": %b
                        }
                    }
                }
            </script>';

        $dsn = $this->getSentryDSN();

        // Don't enable Sentry if dsn can't be retrieved
        $isSentryEnabled = $this->isSentryEnabled() && $dsn;

        $sentryConfigScript = sprintf(
            $sentryConfigScript,
            $dsn,
            $this->getModuleVersion(),
            $this->getPublicApiKey(),
            $isSentryEnabled
        );

        $breadJsScript = sprintf(
            '<script src="%s" data-api-key="%s"></script>',
            $this->getJsLibLocation(),
            $this->getPublicApiKey()
        );

        return $moduleVersionComment . $sentryConfigScript . $breadJsScript;
    }

    /**
     * Check if extension is active
     *
     * @return bool
     */
    protected function isActive()
    {
        return (bool) $this->helper->isActive();
    }

    /**
     * Get API Key
     *
     * @return mixed
     */
    protected function getPublicApiKey()
    {
        return $this->helper->getApiPublicKey();
    }

    /**
     * Get JS URI
     *
     * @return mixed
     */
    protected function getJsLibLocation()
    {
        return $this->helper->getJsLibLocation();
    }

    /**
     * Get Sentry Enabled
     *
     * @return boolean
     */
    protected function isSentryEnabled()
    {
        return $this->helper->isSentryEnabled();
    }

    /**
     * Get current module version
     *
     * @return string
     */
    private function getModuleVersion()
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
            $this->curl->setCredentials($this->getUsername(), $this->getPassword());
            $this->curl->get($this->helper::URL_LAMBDA_SENTRY_DSN);

            $response = json_decode($this->curl->getBody(), true);

            if (isset($response['error']) || !isset($response['dsn'])) {
                $errorMessage = isset($response['error']) ? $response['error']
                    : 'Incorrect Response Format: ' . json_encode($response);
                $this->logger->log(['ERROR WHEN GETTING SENTRY DSN' => $errorMessage]);
                return null;
            }

            $dsn = $response['dsn'];
            $this->cache->save($dsn, $sentryDSNIdentifier, [], 60 * 60);

            return $dsn;
        } catch (\Throwable $e) {
            $this->logger->log(['EXCEPTION WHEN GETTING SENTRY DSN' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get public api key to use as username for dsn request
     *
     * @return string
     */
    private function getUsername()
    {
        return $this->helper->getApiPublicKey();
    }

    /**
     * Get private api key to use as password for dsn request
     *
     * @return string
     */
    private function getPassword()
    {
        return $this->helper->getApiSecretKey();
    }
}
