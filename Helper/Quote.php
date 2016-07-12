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

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Backend\Model\Session\Quote */
    protected $backendSessionQuote;

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
        \Magento\Checkout\Model\Cart $checkoutCart,
        \Bread\BreadCheckout\Helper\Catalog $helperCatalog
    ) {
        $this->storeManager = $storeManager;
        $this->backendSessionQuote = $backendSessionQuote;
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
     * Get Quote Items Data in JSON Format for checkout form
     *
     * @return string JSON formatted String
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
     * @param \Magento\Quote\Model\Quote\Address $billingAddress
     * @return array
     */
    public function getFormattedBillingAddressData(\Magento\Quote\Model\Quote\Address $billingAddress)
    {
        $data     = [
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

        return $data;
    }

    /**
     * Get Bread Formatted Shipping Address Data From Address Model
     *
     * @param \Magento\Quote\Model\Quote\Address $shippingAddress
     * @return array
     */
    public function getFormattedShippingAddressData(\Magento\Quote\Model\Quote\Address $shippingAddress)
    {
        $data     = [
            'fullName'      => $shippingAddress->getName(),
            'address'       => $shippingAddress->getStreetLine(1) . ($shippingAddress->getStreetLine(2) == '' ? '' : (' ' . $shippingAddress->getStreetLine(2))),
            'address2'      => $shippingAddress->getStreetLine(3) . ($shippingAddress->getStreetLine(4) == '' ? '' : (' ' . $shippingAddress->getStreetLine(4))),
            'city'          => $shippingAddress->getCity(),
            'state'         => $shippingAddress->getRegionCode(),
            'zip'           => $shippingAddress->getPostcode(),
            'phone'         => substr(preg_replace('/[^0-9]+/', '', $shippingAddress->getTelephone()), -10)
        ];

        return $data;
    }

    public function getFormattedShippingOptionsData(\Magento\Quote\Model\Quote\Address $shippingAddress)
    {
        $data       = ['type'   => $shippingAddress->getShippingDescription(),
                       'typeId' => $shippingAddress->getShippingMethod(),
                       'cost'   => $shippingAddress->getShippingAmount() * 100];
        return $data;
    }

    /**
     * Get Session Quote object for admin or frontend
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getSessionQuote()
    {
        if ($this->isInAdmin()) {
            return $this->backendSessionQuote->getQuote();
        }

        return $this->checkoutCart->getQuote();
    }

}