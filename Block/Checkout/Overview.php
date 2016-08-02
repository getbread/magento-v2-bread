<?php
/**
 * Handles Checkout Overview Block
 *
 * @copyright   Bread   2016
 * @author      Joel    @Mediotype
 * @author      Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Block\Checkout;

class Overview extends \Bread\BreadCheckout\Block\Product\View
{
    /** @var \Bread\BreadCheckout\Helper\Data */
    protected $breadHelper;

    /** @var \Bread\BreadCheckout\Helper\Quote */
    protected $quoteHelper;

    /** @var \Bread\BreadCheckout\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Framework\Json\Helper\Data */
    protected $jsonHelper;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Bread\BreadCheckout\Helper\Data $breadHelper,
        \Bread\BreadCheckout\Helper\Catalog $catalogHelper,
        \Bread\BreadCheckout\Helper\Customer $customerHelper,
        \Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory $configurableProductFactory,
        \Magento\ConfigurableProduct\Block\Product\View\Type\ConfigurableFactory $configurableBlockFactory,
        \Bread\BreadCheckout\Helper\Quote $quoteHelper,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\ConfigurableProduct\Helper\Data $configurableHelper,
        \Magento\Catalog\Helper\Product $catalogProductHelper,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\ConfigurableProduct\Model\ConfigurableAttributeData $configurableAttributeData,
        array $data = []
    ) {
        $this->breadHelper = $breadHelper;
        $this->quoteHelper = $quoteHelper;
        $this->customerHelper = $customerHelper;
        $this->jsonHelper = $jsonHelper;

        parent::__construct(
            $context,
            $jsonHelper,
            $breadHelper,
            $catalogHelper,
            $customerHelper,
            $configurableProductFactory,
            $configurableBlockFactory,
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

    /**
     * Set Block Extra In Construct Flow
     */
    protected function _construct($bypass = false)
    {
        parent::_construct(true);
        if (!$bypass) {
            $this->setAdditionalData([]);
        }
    }

    /**
     * Get Discount Data
     *
     * @return string
     */
    public function getDiscountDataJson()
    {
        $discountData   = $this->quoteHelper->getDiscountData();
        return $this->jsonEncode($discountData);
    }

    /**
     * Get Product Data From Quote Items
     *
     * @return string
     */
    public function getProductDataJson()
    {
        $itemsData      = $this->quoteHelper->getCartOverviewItemsData();
        return $this->jsonEncode($itemsData);
    }

    /**
     * Checks Settings For Show On Checkout Overview Page During Output
     *
     * @return string
     */
    protected function _toHtml()
    {
        if( $this->breadHelper->isEnabledOnCOP() ) {
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
        return (string) $this->breadHelper->getBlockCodeCheckoutOverview();
    }

    /**
     * Check if checkout through Bread interaction is allowed
     *
     * @return mixed
     */
    public function getAllowCheckout()
    {
        return ($this->breadHelper->getAllowCheckoutCP()) ? 'true' : 'false';
    }

}