<?php
/**
 * Bread BreadCheckout - Hyva JS Block
 * 
 * Template-based JS block for Hyva theme compatibility
 */
namespace Bread\BreadCheckout\Block\Hyva;

use Magento\Framework\View\Element\Template;

class Js extends Template
{
    /**
     * @var \Bread\BreadCheckout\Helper\Data
     */
    public $helper;

    /**
     * @param Template\Context $context
     * @param \Bread\BreadCheckout\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Bread\BreadCheckout\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Check if extension is active
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->helper->isActive();
    }

    /**
     * Get API Key
     *
     * @return string
     */
    public function getPublicApiKey()
    {
        return $this->helper->getApiPublicKey();
    }

    /**
     * Get JS URI
     *
     * @return string
     */
    public function getJsLibLocation()
    {
        return $this->helper->getJsLibLocation();
    }
}
