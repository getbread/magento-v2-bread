<?php
/**
 * Helps Integration With Session Quote
 *
 * @author      Bread   copyright   2016
 * @author      Joel    @Mediotype
 * @author      Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Helper;

class Quote extends Data {

    /** @var \Magento\Sales\Model\Quote */
    protected $quote = null;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var Bread\BreadCheckout\Helper\Catalog */
    protected $helperCatalog;

    /** @var \Magento\Sales\Model\AdminOrder\Create */
    protected $orderCreateModel;

    /** @var \Magento\Framework\Pricing\PriceCurrencyInterface */
    protected $priceCurrency;

    public function __construct(
        \Magento\Framework\App\Helper\Context $helperContext,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\UrlInterfaceFactory $urlInterfaceFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Bread\BreadCheckout\Helper\Catalog $helperCatalog,
        \Magento\Sales\Model\AdminOrder\Create $orderCreateModel,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->helperCatalog = $helperCatalog;
        $this->orderCreateModel = $orderCreateModel;
        $this->priceCurrency = $priceCurrency;

        parent::__construct(
            $helperContext,
            $context,
            $request,
            $encryptor,
            $urlInterfaceFactory
        );
    }

    /**
     * Get Grand Total From Quote
     *
     * @return mixed
     */
    public function getGrandTotal()
    {
        if ($this->isInAdmin()) {
            $this->orderCreateModel->collectRates();
            $grandTotal = $this->orderCreateModel->getQuote()->getGrandTotal();
        } else {
            $quote = $this->getSessionQuote();
            $quote->collectTotals();
            $grandTotal = $quote->getGrandTotal();
        }

        return $grandTotal * 100;
    }

    /**
     * get Tax Value from Quote
     *
     * @return float
     */
    public function getTaxValue()
    {
        if ($this->isInAdmin()) {
            $this->orderCreateModel->collectRates();
            $taxAmount = $this->orderCreateModel->getShippingAddress()->getTaxAmount();
        } else {
            $quote = $this->getSessionQuote();
            $quote->collectTotals();
            $taxAmount = $quote->getShippingAddress()->getTaxAmount();
        }

        return $taxAmount * 100;
    }

    /**
     * get Discount Data From Quote
     *
     * @return array
     */
    public function getDiscountData()
    {
        if ($this->isInAdmin()) {
            $discount = $this->orderCreateModel->getQuote()->getSubtotal() -
                        $this->orderCreateModel->getQuote()->getSubtotalWithDiscount();
            $couponTitle = $this->orderCreateModel->getQuote()->getCouponCode();
        } else {
            $quote = $this->getSessionQuote();
            $discount = $quote->getSubtotal() -
                        $quote->getSubtotalWithDiscount();
            $couponTitle = $quote->getCouponCode();
        }

        if( $discount > 0 ) {
            $discount   = ['amount'        => intval($this->priceCurrency->round($discount) * 100),
                           'description'   => ($couponTitle) ?
                                                $couponTitle : __('Discount')];
        }

        return [$discount];
    }

    /**
     * Get Quote Items Data in JSON Format for cart overview
     *
     * @return array
     */
    public function getCartOverviewItemsData()
    {
        $quote      = $this->getSessionQuote();
        $itemsData  = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $baseProduct            = $item->getProduct();
            $simpleProductItem      = $item->getOptionByCode('simple_product');
            $thisProduct            = null;
            if ($simpleProductItem == null) {
                $thisProduct    = $baseProduct;
                $baseProduct    = null;
            } else {
                $thisProduct    = $item->getOptionByCode('simple_product')->getProduct();
            }

            $itemsData[]   = $this->helperCatalog->getProductDataArray($thisProduct, $baseProduct, $item->getQty(), null);
        }

        return $itemsData;
    }

    /**
     * Get Quote Items Data for checkout form
     *
     * @return array
     */
    public function getQuoteItemsData()
    {

        if ($this->isInAdmin()) {
            $quoteItems = $this->orderCreateModel->getQuote()->getAllItems();
        } else {
            $quoteItems = $this->getSessionQuote()->getAllVisibleItems();
        }

        if (count($quoteItems) < 1) {
            return [];
        }

        $itemsData     = [];
        foreach ($quoteItems as $item) {
            $price                  = $item->getPrice();
            $baseProduct            = $item->getProduct();
            $simpleProductItem      = $item->getOptionByCode('simple_product');
            $thisProduct            = null;
            if ($simpleProductItem == null) {
                $thisProduct            = $baseProduct;
                $baseProduct            = null;
            } else {
                $thisProduct            = $item->getOptionByCode('simple_product')->getProduct();
            }

            $itemsData[]       = $this->helperCatalog->getProductDataArray($thisProduct, $baseProduct, $item->getQty(), $price);
        }

        return $itemsData;
    }

    /**
     * Get Bread Formatted Billing Address Data From Address Model
     *
     * @return array
     */
    public function getBillingAddressData()
    {
        if ($this->isInAdmin()) {
            $billingAddress = $this->orderCreateModel->getBillingAddress();
        } else {
            $billingAddress     = $this->getSessionQuote()->getBillingAddress();
        }


        if(!$billingAddress->getStreetLine(1)){
            return false;
        }

        return [
            'address'       => $billingAddress->getStreetLine(1) . ($billingAddress->getStreetLine(2) == '' ? '' : (' ' . $billingAddress->getStreetLine(2))),
            'address2'      => $billingAddress->getStreetLine(3) . ($billingAddress->getStreetLine(4) == '' ? '' : (' ' . $billingAddress->getStreetLine(4))),
            'city'          => $billingAddress->getCity(),
            'state'         => $billingAddress->getRegionCode(),
            'zip'           => $billingAddress->getPostcode(),
            'phone'         => substr(preg_replace('/[^0-9]+/', '', $billingAddress->getTelephone()), -10),
            'email'         => $billingAddress->getEmail(),
            'firstName'     => $billingAddress->getFirstname(),
            'lastName'      => $billingAddress->getLastname(),
        ];
    }

    /**
     * Get Bread Formatted Shipping Address Data From Address Model
     *
     * @return array
     */
    public function getShippingAddressData()
    {

        if ($this->isInAdmin()) {
            $shippingAddress = $this->orderCreateModel->getShippingAddress();
        } else {
            $shippingAddress = $this->getSessionQuote()->getShippingAddress();
        }

        if(!$shippingAddress->getStreetLine(1)){
            return false;
        }

        return [
            'fullName'      => $shippingAddress->getName(),
            'address'       => $shippingAddress->getStreetLine(1) . ($shippingAddress->getStreetLine(2) == '' ? '' : (' ' . $shippingAddress->getStreetLine(2))),
            'address2'      => $shippingAddress->getStreetLine(3) . ($shippingAddress->getStreetLine(4) == '' ? '' : (' ' . $shippingAddress->getStreetLine(4))),
            'city'          => $shippingAddress->getCity(),
            'state'         => $shippingAddress->getRegionCode(),
            'zip'           => $shippingAddress->getPostcode(),
            'phone'         => substr(preg_replace('/[^0-9]+/', '', $shippingAddress->getTelephone()), -10)
        ];
    }

    /**
     * Get Bread Formatted Shipping Options Information
     *
     * @return string
     */
    public function getShippingOptions()
    {
        if ($this->isInAdmin()) {
            $shippingAddress = $this->orderCreateModel->getShippingAddress();
        } else {
            $shippingAddress = $this->getSessionQuote()->getShippingAddress();
        }
        
        if(!$shippingAddress->getShippingMethod()){
            return false;
        }

        return ['type'   => $shippingAddress->getShippingDescription(),
                'typeId' => $shippingAddress->getShippingMethod(),
                'cost'   => $shippingAddress->getShippingAmount() * 100];
    }

    /**
     * Get stored bread transaction ID from quote
     *
     * @return string
     */
    public function getBreadTransactionId()
    {
        return $this->getSessionQuote()->getBreadTransactionId();
    }

    /**
     * Get Session Quote object for admin or frontend
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getSessionQuote()
    {
        if ($this->quote === null) {

            if ($this->isInAdmin()) {
                $this->quote = $this->orderCreateModel->getQuote();
            }

            $this->quote = $this->checkoutSession->getQuote();
        }

        return $this->quote;
    }
}