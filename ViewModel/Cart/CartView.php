<?php

namespace Bread\BreadCheckout\ViewModel\Cart;

use Bread\BreadCheckout\Helper\Catalog;
use Bread\BreadCheckout\Helper\Customer;
use Bread\BreadCheckout\Helper\Data;
use Bread\BreadCheckout\Helper\Quote;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class CartView implements ArgumentInterface
{
    /**
     * @var Quote
     */
    public $quoteHelper;

    /**
     * @var Customer
     */
    public $customerHelper;

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
     * CartView constructor.
     * @param Json $serializer
     * @param Catalog $catalogHelper
     * @param Customer $customerHelper
     * @param Data $dataHelper
     * @param Quote $quoteHelper
     */
    public function __construct(
        Json $serializer,
        Catalog $catalogHelper,
        Customer $customerHelper,
        Data $dataHelper,
        Quote $quoteHelper

    ) {
        $this->serializer = $serializer;
        $this->catalogHelper = $catalogHelper;
        $this->customerHelper = $customerHelper;
        $this->dataHelper = $dataHelper;
        $this->quoteHelper = $quoteHelper;
    }

    /**
     * Get Product Data From Quote Items
     *
     * @return string
     */
    public function getProductDataJson()
    {
        $itemsData      = $this->quoteHelper->getCartOverviewItemsData();
        return $this->serializer->serialize($itemsData);
    }

    /**
     * Get targeted financing status from quote items
     *
     * @return bool|false|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTargetedFinancingStatusJson()
    {
        $status = $this->quoteHelper->getTargetedFinancingStatus();
        return $this->serializer->serialize($status);
    }

    /**
     * Checks Settings For Show On Checkout Overview Page During Output
     *
     * @return string
     */
    public function isAllowedRender($quote)
    {
        $isAllowed = false;

        $aboveThreshold = $this->quoteHelper->aboveThreshold($this->quoteHelper->getSessionQuote()->getGrandTotal());
        $isDisabledSkus = !$this->quoteHelper->checkDisabledForSku();

        if ($this->quoteHelper->isEnabledOnCOP() && $aboveThreshold && $isDisabledSkus) {
            $isAllowed = true;
        }

        return $isAllowed;
    }

    /**
     * Return Block View Product Code
     *
     * @return string
     */
    public function getBlockCode()
    {
        return (string) $this->quoteHelper->getBlockCodeCheckoutOverview();
    }

    /**
     * Check if checkout through Bread interaction is allowed
     *
     * @return mixed
     */
    public function getAllowCheckout()
    {
        return ($this->quoteHelper->getAllowCheckoutCP()) ? 'true' : 'false';
    }

    /**
     * Get Extra Button Design CSS
     *
     * @return mixed
     */
    public function getButtonDesign()
    {
        $design = $this->dataHelper->escapeCustomCSS($this->catalogHelper->getCartButtonDesign());
        return $design ? $design : $this->catalogHelper->getPDPButtonDesign();
    }

    /**
     * Validate allowed products wrapper for block class
     *
     * @return bool
     */
    public function validateAllowedProductTypes()
    {
        return $this->quoteHelper->validateAllowedProductTypes();
    }

    /**
     * Custom product type error message
     *
     * @return string
     */
    public function productTypeErrorMessage()
    {
        return $this->catalogHelper->getProductTypeMessage();
    }

    /**
     * Check financing by sku
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isFinancingBySku()
    {
        return $this->quoteHelper->isFinancingBySku();
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
     * Get Validate Order URL
     *
     * @return string
     */
    public function getValidateOrderUrl()
    {
        return $this->catalogHelper->getValidateOrderURL();
    }
}
