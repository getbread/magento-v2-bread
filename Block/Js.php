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
    /** @var \Bread\BreadCheckout\Helper\Data */
    public $helper;

    /** @var \Magento\Framework\Module\ModuleListInterface */
    private $moduleList;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Bread\BreadCheckout\Helper\Data $helper,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->moduleList = $moduleList;

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
            return $this->generateJsIncludeString();
        }

        return '';
    }

    /**
     * Create JS Include HTML
     *
     * @return string
     */
    protected function generateJsIncludeString()
    {
        $code       = '<script src="%s" data-api-key="%s" m2-module-version="%s"></script>';
        $html       = sprintf($code, $this->getJsLibLocation(), $this->getPublicApiKey(),$this->getModuleVersion());

        return $html;
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
        $data = $this->moduleList->getOne('Bread_BreadCheckout');
        return $data['setup_version'];
    }
}
