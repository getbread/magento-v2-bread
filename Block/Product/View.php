<?php
/**
 * Handles Product View Block
 *
 * @copyright   Bread   2016
 * @author      Joel    @Mediotype
 */
namespace ;

class  extends \Magento\Framework\View\Element\Template
{

    protected $_product;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct(
            $context,
            $data
        );
    }


    protected function _construct()
    {
        $this->setBlockCode($this->getBlockCode());
        $this->setAdditionalData(array(
            'product_id'    => $this->getProduct()->getId()
        ));

        parent::_construct();
    }

    /**
     * Get Current Product
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        if (null === $this->_product) {
            $this->_product     = $this->registry->registry('product');
        }

        return $this->_product;
    }

    /**
     * Get Product Data as JSON
     *
     * @return string
     */
    public function getProductDataJson()
    {
        $product    = $this->getProduct();
        $data       = array(
                        $this->helper('breadcheckout/catalog')->getProductDataArray($product, null)
                    );

        return $this->helper('core')->jsonEncode($data);
    }

    /**
     * Returns empty values so that the page can work the same as the cart page.
     *
     * @return string
     */
    public function getDiscountDataJson()
    {
        $result     = array();
        return $this->helper('core')->jsonEncode($result);
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
     * Checks Settings For Show On Product Detail Page During Output
     *
     * @return string
     */
    protected function _toHtml()
    {
        if( $this->helper('breadcheckout')->isEnabledOnPDP() ) {
            return parent::_toHtml();
        }

        return '';
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
     * Get Extra Button Design CSS
     *
     * @return mixed
     */
    public function getButtonDesign()
    {
        return $this->helper('breadcheckout')->getButtonDesign();
    }

    /**
     * Get Is Button On Product
     *
     * @return string
     */
    public function getIsButtonOnProduct()
    {
        return ( $this->helper('breadcheckout')->isButtonOnProducts() ) ? 'true' : 'false';
    }

    /**
     * Get Default Button Size String For The View
     *
     * @return string
     */
    public function getIsDefaultSize()
    {
        return (string) $this->helper('breadcheckout/Catalog')->getDefaultButtonSizeHtml();
    }

    /**
     * Check if checkout through Bread interaction is allowed
     *
     * @return mixed
     */
    public function getAllowCheckout()
    {
        return ($this->helper('breadcheckout')->getAllowCheckoutPDP()) ? 'true' : 'false';
    }

    /**
     * Return Block View Product Code
     *
     * @return string
     */
    public function getBlockCode()
    {
        return (string) $this->helper('breadcheckout')->getBlockCodeProductView();
    }
    
}