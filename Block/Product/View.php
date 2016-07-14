<?php
/**
 * Handles Product View Block
 *
 * @copyright   Bread   2016
 * @author      Joel    @Mediotype
 * @author      Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Block\Product;

class View extends \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable
{
    protected $_product;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /** @var Magento\Framework\Json\Helper\Data */
    protected $jsonHelper;

    /** @var \Bread\BreadCheckout\Helper\Data */
    protected $breadHelper;

    /** @var \Bread\BreadCheckout\Helper\Catalog */
    protected $catalogHelper;

    /** @var \Bread\BreadCheckout\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory */
    protected $configurableProductFactory;

    /** @var \Magento\ConfigurableProduct\Block\Product\View\Type\ConfigurableFactory */
    protected $configurableBlockFactory;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Bread\BreadCheckout\Helper\Data $breadHelper,
        \Bread\BreadCheckout\Helper\Catalog $catalogHelper,
        \Bread\BreadCheckout\Helper\Customer $customerHelper,
        \Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory $configurableProductFactory,
        \Magento\ConfigurableProduct\Block\Product\View\Type\ConfigurableFactory $configurableBlockFactory,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\ConfigurableProduct\Helper\Data $configurableHelper,
        \Magento\Catalog\Helper\Product $catalogProductHelper,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\ConfigurableProduct\Model\ConfigurableAttributeData $configurableAttributeData,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->jsonHelper = $jsonHelper;
        $this->breadHelper = $breadHelper;
        $this->catalogHelper = $catalogHelper;
        $this->customerHelper = $customerHelper;
        $this->configurableProductFactory = $configurableProductFactory;
        $this->configurableBlockFactory = $configurableBlockFactory;

        parent::__construct(
            $context,
            $arrayUtils,
            $jsonEncoder,
            $configurableHelper,
            $catalogProductHelper,
            $currentCustomer,
            $priceCurrency,
            $configurableAttributeData,
            $data
        );
    }

    protected function _construct($bypass = false)
    {
        if (!$bypass) {
            $this->setBlockCode($this->getBlockCode());
            $this->setAdditionalData(['product_id' => $this->getProduct()->getId()]);
        }
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
        $data       = [$this->catalogHelper->getProductDataArray($product, null)];

        return $this->jsonEncode($data);
    }

    /**
     * Returns empty values so that the page can work the same as the cart page.
     *
     * @return string
     */
    public function getDiscountDataJson()
    {
        $result     = array();
        return $this->jsonEncode($result);
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
    public function getAsLowAs()
    {
        return ($this->breadHelper->isAsLowAs()) ? 'true' : 'false';
    }

    /**
     * Checks Settings For Show On Product Detail Page During Output
     *
     * @return string
     */
    protected function _toHtml()
    {
        if( $this->breadHelper->isEnabledOnPDP() ) {
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
        return $this->breadHelper->getShippingEstimateUrl();
    }

    /**
     * Get Tax Estimate URL
     *
     * @return string
     */
    public function getTaxEstimationUrl()
    {
        return $this->breadHelper->getTaxEstimateUrl();
    }

    /**
     * Get Validate Order URL
     *
     * @return string
     */
    public function getValidateOrderUrl()
    {
        return $this->breadHelper->getValidateOrderURL();
    }

    /**
     * Get Extra Button Design CSS
     *
     * @return mixed
     */
    public function getButtonDesign()
    {
        return $this->breadHelper->getButtonDesign();
    }

    /**
     * Get Is Button On Product
     *
     * @return string
     */
    public function getIsButtonOnProduct()
    {
        return ( $this->breadHelper->isButtonOnProducts() ) ? 'true' : 'false';
    }

    /**
     * Get Default Button Size String For The View
     *
     * @return string
     */
    public function getIsDefaultSize()
    {
        return (string) $this->catalogHelper->getDefaultButtonSizeHtml();
    }

    /**
     * Check if checkout through Bread interaction is allowed
     *
     * @return mixed
     */
    public function getAllowCheckout()
    {
        return ($this->breadHelper->getAllowCheckoutPDP()) ? 'true' : 'false';
    }

    /**
     * Return Block View Product Code
     *
     * @return string
     */
    public function getBlockCode()
    {
        return (string) $this->breadHelper->getBlockCodeProductView();
    }

    /**
     * Get product IDs from related products collection
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getChildProductIds(\Magento\Catalog\Model\Product $product)
    {
        $configurableProduct    = $this->configurableProductFactory->create();
        $usedChildrenProduct    = $configurableProduct->getUsedProductCollection($product)
            ->addAttributeToSelect('sku')
            ->addFilterByRequiredOptions();

        $itemIds         = [];
        foreach($usedChildrenProduct as $simpleProduct){
            $itemIds[]   = [ $simpleProduct->getId() => $simpleProduct->getSku() ];
        }

        return $itemIds;
    }

    public function jsonEncode($data) {
        return $this->jsonHelper->jsonEncode($data);
    }
    
}