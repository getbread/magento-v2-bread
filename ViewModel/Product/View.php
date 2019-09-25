<?php

namespace Bread\BreadCheckout\ViewModel\Product;

use Bread\BreadCheckout\Helper\Catalog;
use Bread\BreadCheckout\Helper\Customer;
use Bread\BreadCheckout\Helper\Data;
use Bread\BreadCheckout\Helper\Quote;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class View extends DataObject implements ArgumentInterface
{

    /**
     * @var Json
     */
    public $serializer;

    /**
     * @var Catalog
     */
    public $catalogHelper;

    /**
     * @var Data
     */
    public $dataHelper;

    /**
     * @var Customer
     */
    public $customerHelper;

    /**
     * @var Quote
     */
    public $quoteHelper;

    /**
     * View constructor.
     * @param Catalog $catalogHelper
     * @param Customer $customerHelper
     * @param Quote $quoteHelper
     * @param Data $dataHelper
     * @param Json $serializer
     */
    public function __construct(
        Catalog $catalogHelper,
        Customer $customerHelper,
        Quote $quoteHelper,
        Data $dataHelper,
        Json $serializer,
        array $data = []
    ) {
        $this->catalogHelper = $catalogHelper;
        $this->customerHelper = $customerHelper;
        $this->quoteHelper = $quoteHelper;
        $this->dataHelper = $dataHelper;
        $this->serializer = $serializer;

        parent::__construct($data);
    }

    /**
     * Return product data in json format
     *
     * @param $product
     * @return bool|false|string
     */
    public function getProductDataJson($product)
    {
        return $this->serializer->serialize([$this->catalogHelper->getProductDataArray($product, null)]);
    }

    /**
     * Get initial grouped item data
     *
     * @return string
     */
    public function getGroupedDataJson($product)
    {
        $data    = [$this->catalogHelper->getGroupedProductDataArray($product)];
        return $this->serializer->serialize($data);
    }

    /**
     * Get grouped product update url
     *
     * @return string
     */
    public function getGroupedButtonUpdate()
    {
        return $this->dataHelper->getGroupedProductItemsUrl();
    }

    /**
     * Returns empty values so that the page can work the same as the cart page.
     *
     * @return string
     */
    public function getDiscountDataJson()
    {
        $data     = [];
        return $this->serializer->serialize($data);
    }

    /**
     * Get targeted financing configuration
     *
     * @return string
     */
    public function getFinancingJson()
    {
        $data     = $this->catalogHelper->getFinancingData();
        return $this->serializer->serialize($data);
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
        return ($this->catalogHelper->isAsLowAs()) ? 'true' : 'false';
    }

    /**
     * Check if template should return output
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isAllowedRender($product)
    {
        $isAllowed = false;

        if ($this->catalogHelper->isEnabledOnPDP()
            && $this->catalogHelper->allowedProductType($product->getTypeId())
            && $this->dataHelper->aboveThreshold(
                $product->getPriceInfo()->getPrice('final_price')->getValue()
            )
            && !$this->quoteHelper->checkDisabledForSku($product)
        ) {
            $isAllowed = true;
        }

        return $isAllowed;
    }

    /**
     * Get Shipping Estimate Url
     *
     * @return string
     */
    public function getShippingAddressEstimationUrl()
    {
        return $this->catalogHelper->getShippingEstimateUrl();
    }

    /**
     * Get Tax Estimate URL
     *
     * @return string
     */
    public function getTaxEstimationUrl()
    {
        return $this->catalogHelper->getTaxEstimateUrl();
    }

    /**
     * Get Validate Order URL
     *
     * @return string
     */
    public function getValidateOrderUrl()
    {
        return $this->catalogHelper->getValidateOrderURL();
    }

    /**
     * Get Config Data URL
     *
     * @return string
     */
    public function getConfigDataUrl()
    {
        return $this->catalogHelper->getConfigDataUrl();
    }

    /**
     * Get Discounts Data URL
     *
     * @return string
     */
    public function getDiscountsDataUrl()
    {
        return $this->catalogHelper->getDiscountsDataUrl();
    }

    /**
     * Get Clear Quote Data URL
     *
     * @return string
     */
    public function getClearQuoteUrl()
    {
        return $this->catalogHelper->getClearQuoteUrl();
    }

    /**
     * Get Extra Button Design CSS
     *
     * @return mixed
     */
    public function getButtonDesign()
    {
        return $this->dataHelper->escapeCustomCSS($this->catalogHelper->getPDPButtonDesign());
    }

    /**
     * Get Is Button On Product
     *
     * @return string
     */
    public function getIsButtonOnProduct()
    {
        return ($this->catalogHelper->isButtonOnProducts()) ? 'true' : 'false';
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
        return ($this->catalogHelper->getAllowCheckoutPDP()) ? 'true' : 'false';
    }

    /**
     * Is Healthcare mode?
     *
     * @return bool
     */
    public function isHealthcare()
    {
        return (bool) ($this->dataHelper->isHealthcare());
    }

    /**
     * Return Block View Product Code
     *
     * @return string
     */
    public function getBlockCode()
    {
        return (string) $this->catalogHelper->getBlockCodeProductView();
    }

    /**
     * Check if Cart Size financing is enabled
     *
     * @return bool
     */
    public function isTargetedFinancing()
    {
        return $this->dataHelper->isTargetedFinancing();
    }

    /**
     * Get cart size over which targeted financing is enabled
     *
     * @return string
     */
    public function getTargetedFinancingThreshold()
    {
        return $this->dataHelper->getTargetedFinancingThreshold();
    }

    /**
     * Get financing ID associated with cart size threshold
     *
     * @return string
     */
    public function getFinancingId()
    {
        return $this->dataHelper->getFinancingId();
    }

    /**
     * Wrapper for get financing mode
     *
     * @param string $mode
     * @return int
     */
    public function checkFinancingMode($mode)
    {
        return $this->dataHelper->checkFinancingMode($mode);
    }

    /**
     * Get product IDs from related products collection
     * @todo separate view model for configurable
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getChildProductIds(\Magento\Catalog\Model\Product $product)
    {
        $configurableProduct    = $this->configurableProductFactory->create();
        $usedChildrenProduct    = $configurableProduct->getUsedProductCollection($product)
            ->addAttributeToSelect('sku')
            ->addFilterByRequiredOptions();

        $itemIds         = [];
        foreach ($usedChildrenProduct as $simpleProduct) {
            $itemIds[]   = [ $simpleProduct->getId() => $simpleProduct->getSku() ];
        }

        return $itemIds;
    }

    /**
     * Get SKU and price data for custom options on product
     *
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterface[] $options
     * @return string
     */
    public function getCustomOptionsData($options)
    {
        $optionsData = [];

        foreach ($options as $option) {
            if ($option->getValues()) {
                foreach ($option->getValues() as $k => $v) {
                    $optionsData[$option->getId()][$k] = [
                        'sku' => $v->getSku(),
                        'price' => (int)($v->getPrice() * 100)
                    ];
                }
            } else {
                $optionsData[$option->getId()] = [
                    'sku' => $option->getSku(),
                    'price' => (int)($option->getPrice() * 100)
                ];
            }
        }

        return $this->serializer->serialize($optionsData);
    }

    /**
     * Get button location string for product page
     *
     * @return string
     */
    public function getProductViewLocation()
    {
        return $this->dataHelper->getProductViewLocation();
    }

    /**
     * Get button location string for cart summary page
     *
     * @return string
     */
    public function getCartSummaryLocation()
    {
        return $this->dataHelper->getCartSummaryLocation();
    }

    /**
     * Get button location string for checkout page
     *
     * @return string
     */
    public function getCheckoutLocation()
    {
        return $this->dataHelper->getCheckoutLocation();
    }

    /**
     * Get button location string for financing page
     *
     * @return string
     */
    public function getFinancingLocation()
    {
        return $this->dataHelper->getFinancingLocation();
    }

    /**
     * Get button location string for marketing page
     *
     * @return string
     */
    public function getMarketingLocation()
    {
        return $this->dataHelper->getMarketingLocation();
    }

    /**
     * Get button location string for category page
     *
     * @return string
     */
    public function getCategoryPageLocation()
    {
        return $this->dataHelper->getCategoryPageLocation();
    }

    /**
     * Get button location string for other purposes
     *
     * @return string
     */
    public function getOtherLocation()
    {
        return $this->dataHelper->getOtherLocation();
    }

    /**
     * Is downloadable type
     *
     * @return bool
     */
    public function isDownloadable($typeId)
    {
        return $this->getProduct()->getTypeId() === \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE;
    }
}
