<?php
/**
 * Handles Checkout Overview Block
 *
 * @copyright   Bread   2016
 * @author      Joel    @Mediotype
 * @author      Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Block\Checkout;

class Overview extends \Magento\Framework\View\Element\Template
{
    /** @var \Bread\BreadCheckout\Helper\Data */
    protected $helper;

    /** @var \Bread\BreadCheckout\Helper\Quote */
    protected $quoteHelper;

    /** @var \Bread\BreadCheckout\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Framework\Json\Helper\Data */
    protected $jsonHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Bread\BreadCheckout\Helper\Data $helper,
        \Bread\BreadCheckout\Helper\Quote $quoteHelper,
        \Bread\BreadCheckout\Helper\Customer $customerHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->quoteHelper = $quoteHelper;
        $this->customerHelper = $customerHelper;
        $this->jsonHelper = $jsonHelper;

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
        $this->setAdditionalData([]);
    }

    /**
     * Get Discount Data
     *
     * @return string
     */
    public function getDiscountDataJson()
    {
        $discountData   = $this->quoteHelper->getDiscountData();
        return $this->jsonHelper->jsonEncode($discountData);
    }

    /**
     * Get Product Data From Quote Items
     *
     * @return string
     */
    public function getProductDataJson()
    {
        $itemsData      = $this->quoteHelper->getCartOverviewItemsData();
        return $this->jsonHelper->jsonEncode($itemsData);
    }

    /**
     * Checks Settings For Show On Checkout Overview Page During Output
     *
     * @return string
     */
    protected function _toHtml()
    {
        if( $this->helper->isEnabledOnCOP() ) {
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
        return (string) $this->helper->getBlockCodeCheckoutOverview();
    }

    /**
     * Get Shipping Estimate Url
     *
     * @return string
     */
    public function getShippingAddressEstimationUrl()
    {
        return $this->helper->getShippingEstimateUrl();
    }

    /**
     * Get Tax Estimate URL
     *
     * @return string
     */
    public function getTaxEstimationUrl()
    {
        return $this->helper->getTaxEstimateUrl();
    }

    /**
     * Get Validate Order URL
     *
     * @return string
     */
    public function getValidateOrderUrl()
    {
        return $this->helper->getValidateOrderURL();
    }

    /**
     * Get Default Customer Shipping Address If It Exists
     *
     * @return string
     */
    public function getShippingAddressData()
    {
        return $this->customerHelper->getShippingAddressData();
    }

    /**
     * Get Billing Address Default Data
     *
     * @return string
     */
    public function getBillingAddressData()
    {
        return $this->customerHelper->getBillingAddressData();
    }

    /**
     * Get As Low As Option Value
     *
     * @return string
     */
    protected function getAsLowAs()
    {
        return ( $this->helper->isAsLowAs() ) ? 'true' : 'false';
    }

    /**
     * Get Extra Button Design CSS
     *
     * @return mixed
     */
    public function getButtonDesign()
    {
        return $this->helper->getButtonDesign();
    }

    /**
     * Check if checkout through Bread interaction is allowed
     *
     * @return mixed
     */
    public function getAllowCheckout()
    {
        return ($this->helper->getAllowCheckoutCP()) ? 'true' : 'false';
    }

}