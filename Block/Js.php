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

        $breadJsScript = sprintf(
            '<script data-api-key="%s">
                const script = document.createElement("script");
                script.async = false;
                script.onload = () => {
                    if (BreadPayments) {
                        BreadPayments.setInitMode("manual");
                    } else {
                     RBCPayPlan.setInitMode("manual");
                    }
                };
                script.src = "%s";
                document.head.appendChild(script);
            </script>',
            $this->getPublicApiKey(),
            $this->getJsLibLocation()
        );

        return $moduleVersionComment . $breadJsScript;
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
     * Get current module version
     *
     * @return string
     */
    private function getModuleVersion()
    {
        return $this->packageInfo->getVersion('Bread_BreadCheckout');
    }

}
