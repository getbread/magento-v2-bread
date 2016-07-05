<?php
/**
 * Handles Checkout Overview Block
 *
 * @copyright   Bread   2016
 * @author      Joel    @Mediotype
 */
namespace ;

class  extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
    }


    /**
     * Set Block Extra In Construct Flow
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setAdditionalData(array());
    }

    /**
     * Get Discount Data
     *
     * @return string
     */
    public function getDiscountDataJson()
    {
        $discountData   = $this->helper('breadcheckout/Quote')->getDiscountData();

        return $this->helper('core')->jsonEncode($discountData);
    }

    /**
     * Get Product Data From Quote Items
     *
     * @return string
     */
    public function getProductDataJson()
    {
        $itemsData      = $this->helper('breadcheckout/Quote')->getCartOverviewItemsData();

        return $this->helper('core')->jsonEncode($itemsData);
    }

    /**
     * Checks Settings For Show On Checkout Overview Page During Output
     *
     * @return string
     */
    protected function _toHtml()
    {
        if( $this->helper('breadcheckout')->isEnabledOnCOP() ) {
            return parent::_toHtml();
        }

        return '';
    }

    /**
     * Return Block View Product Code
     *
     * @return string
     */
    public function getBlockCode()
    {
        return (string) Mage::helper('breadcheckout')->getBlockCodeCheckoutOverview();
    }

    /**
     * Get Shipping Estimate Url
     *
     * @return string
     */
    public function getShippingAddressEstimationUrl()
    {
        return $this->helper('breadcheckout')->getShippingEstimateUrl();
    }

    /**
     * Get Tax Estimate URL
     *
     * @return string
     */
    public function getTaxEstimationUrl()
    {
        return $this->helper('breadcheckout')->getTaxEstimateUrl();
    }

    /**
     * Get Validate Order URL
     *
     * @return string
     */
    public function getValidateOrderUrl()
    {
        return $this->helper('breadcheckout')->getValidateOrderURL();
    }

    /**
     * Get Default Customer Shipping Address If It Exists
     *
     * @return string
     */
    public function getShippingAddressData()
    {
        return $this->helper('breadcheckout/Customer')->getShippingAddressData();
    }

    /**
     * Get Billing Address Default Data
     *
     * @return string
     */
    public function getBillingAddressData()
    {
        return $this->helper('breadcheckout/Customer')->getBillingAddressData();
    }

    /**
     * Get As Low As Option Value
     *
     * @return string
     */
    protected function getAsLowAs()
    {
        return ( $this->helper('breadcheckout')->isAsLowAs() ) ? 'true' : 'false';
    }

    /**
     * Get Extra Button Design CSS
     *
     * @return mixed
     */
    public function getButtonDesign()
    {
        return $this->helper('breadcheckout')->getButtonDesign();
    }

    /**
     * Check if checkout through Bread interaction is allowed
     *
     * @return mixed
     */
    public function getAllowCheckout()
    {
        return ($this->helper('breadcheckout')->getAllowCheckoutCP()) ? 'true' : 'false';
    }

}