<?php
/**
 * Class Bread_BreadCheckout_Block_Js
 *
 * @author  Bread   copyright   2016
 * @author  Joel    @Mediotype
 */
namespace ;

class  extends \Magento\Framework\View\Element\Text
{
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        array $data = []
    ) {
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
        $code       = '<script src="%s" data-api-key="%s"></script>';
        $html       = sprintf($code, $this->getJsLibLocation(), $this->getPublicApiKey());

        return $html;
    }

    /**
     * Check if extension is active
     *
     * @return bool
     */
    protected function isActive()
    {
        return (bool) $this->helper('breadcheckout')->isActive();
    }

    /**
     * Get API Key
     *
     * @return mixed
     */
    protected function getPublicApiKey()
    {
        return $this->helper('breadcheckout')->getApiPublicKey();
    }

    /**
     * Get JS URI
     *
     * @return mixed
     */
    protected function getJsLibLocation()
    {
        return $this->helper('breadcheckout')->getJsLibLocation();
    }

}