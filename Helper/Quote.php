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

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Backend\Model\Session\Quote */
    protected $backendSessionQuote;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var \Magento\Checkout\Model\Cart */
    protected $checkoutCart;

    /** @var Bread\BreadCheckout\Helper\Catalog */
    protected $helperCatalog;

    public function __construct(
        \Magento\Framework\App\Helper\Context $helperContext,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\UrlInterfaceFactory $urlInterfaceFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Model\Session\Quote $backendSessionQuote,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Model\Cart $checkoutCart,
        \Bread\BreadCheckout\Helper\Catalog $helperCatalog
    ) {
        $this->storeManager = $storeManager;
        $this->backendSessionQuote = $backendSessionQuote;
        $this->checkoutSession = $checkoutSession;
        $this->checkoutCart = $checkoutCart;
        $this->helperCatalog = $helperCatalog;
        parent::__construct(
            $helperContext,
            $context,
            $request,
            $encryptor,
            $urlInterfaceFactory,
            $storeManager
        );
    }

    /**
     * Get Grand Total From Quote
     *
     * @return mixed
     */
    public function getGrandTotal()
    {
        $quote      = $this->getSessionQuote();
        $quote->collectTotals();

        $grandTotal = $quote->getGrandTotal();

        return $grandTotal * 100;
    }

    /**
     * get Tax Value from Quote
     *
     * @return float
     */
    public function getTaxValue()
    {
        $quote      = $this->getSessionQuote();
        $quote->collectTotals();

        $quoteAddress       = $quote->getShippingAddress();
        $taxAmount          = $quoteAddress->getTaxAmount();

        return $taxAmount * 100;
    }

    /**
     * get Discount Data From Quote
     *
     * @return array
     */
    public function getDiscountData()
    {
        $quote      = $this->getSessionQuote();
        $totals     = $quote->getTotals();
        $discountData     = [];
        if( isset($totals['discount']) && $totals['discount']->getValue() ) {
            $discount   = ['amount'        => $totals['discount']->getValue() * -100.0,
                           'description'   => $totals['discount']->getTitle()];
            $discountData[]   = $discount;
        }

        return $discountData;
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
        $quote      = $this->getSessionQuote();

        if($quote->hasItems() == false){
            return [];
        }

        $itemsData     = [];
        foreach ($quote->getAllVisibleItems() as $item) {
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
        $billingAddress     = $this->getSessionQuote()->getBillingAddress();

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
        $shippingAddress    = $this->getSessionQuote()->getShippingAddress();

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
        $shippingAddress        = $this->getSessionQuote()->getShippingAddress();

        if(!$shippingAddress->getShippingMethod()){
            return 'false';
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
        if ($this->quote == null) {

            if ($this->isInAdmin()) {
                $this->quote = $this->backendSessionQuote->getQuote();
            }

            $this->quote = $this->checkoutSession->getQuote();
        }

        return $this->quote;
    }

}