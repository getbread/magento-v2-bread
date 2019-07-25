<?php
/**
 * Class Bread_BreadCheckout_Block_Js
 *
 * @author  Bread   copyright   2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
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

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Bread\BreadCheckout\Helper\Data $helper,
        \Magento\Framework\Module\PackageInfo $packageInfo,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->packageInfo = $packageInfo;

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

        $sentrySdkScript = '<script src="https://browser.sentry-cdn.com/5.4.3/bundle.min.js" crossorigin="anonymous"></script>';
        $sentryConfigScript =
            '<script type="text/x-magento-init">
                {
                    "*": {
                        "Bread_BreadCheckout/js/helper/sentry-config": {
                            "pluginVersion": "%s",
                            "apiKey": "%s",
                            "isSentryEnabled": %b
                        }
                    }
                }
            </script>';

        $sentryScript = sprintf($sentrySdkScript . $sentryConfigScript, $this->getModuleVersion(), $this->getPublicApiKey(), $this->isSentryEnabled());

        $breadJsScript = sprintf('<script src="%s" data-api-key="%s"></script>', $this->getJsLibLocation(), $this->getPublicApiKey());

        return $moduleVersionComment . $sentryScript . $breadJsScript;
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
     * @return mixed
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
}
