<?php
/**
 * Helps Integration With Session Quote
 *
 * @author      Bread   copyright   2016
 * @author      Joel    @Mediotype
 * @author      Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Helper;

class Quote extends Data
{
    const BREAD_SESSION_QUOTE_RESULT_KEY  = "bread_quote_result";
    const BREAD_SESSION_QUOTE_UPDATED_KEY = "bread_quote_updated_at";


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
    /**
     * @var \Bread\BreadCheckout\Model\Payment\Api\Client
     */
    protected $paymentApiClient;

    /**
     * Quote constructor.
     *
     * @param \Magento\Framework\App\Helper\Context             $helperContext
     * @param \Magento\Framework\Model\Context                  $context
     * @param \Magento\Framework\App\Request\Http\Proxy         $request
     * @param \Magento\Framework\Encryption\Encryptor           $encryptor
     * @param \Magento\Framework\UrlInterfaceFactory            $urlInterfaceFactory
     * @param \Magento\Checkout\Model\Session\Proxy             $checkoutSession
     * @param Catalog                                           $helperCatalog
     * @param \Magento\Sales\Model\AdminOrder\Create            $orderCreateModel
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Bread\BreadCheckout\Model\Payment\Api\Client     $paymentApiClient
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $helperContext,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\App\Request\Http\Proxy $request,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\UrlInterfaceFactory $urlInterfaceFactory,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Bread\BreadCheckout\Helper\Catalog $helperCatalog,
        \Magento\Sales\Model\AdminOrder\Create $orderCreateModel,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->helperCatalog = $helperCatalog;
        $this->orderCreateModel = $orderCreateModel;
        $this->priceCurrency = $priceCurrency;
        $this->paymentApiClient = $paymentApiClient;

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
     * @param bool $collectRates
     *
     * @return float
     */
    public function getTaxValue($collectRates = true)
    {
        if ($this->isInAdmin()) {
            if ($collectRates) {
                $this->orderCreateModel->collectRates();
            }
            $taxAmount = $this->orderCreateModel->getShippingAddress()->getTaxAmount();
        } else {
            $quote = $this->getSessionQuote();
            if ($collectRates) {
                $quote->collectTotals();
            }
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

        if ($discount > 0) {
            $discount = [
                'amount'      => (int)($this->priceCurrency->round($discount) * 100),
                'description' => ($couponTitle) ? $couponTitle : __('Discount')
            ];
        } else {
            return [];
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

            $itemsData[]   = $this->helperCatalog
                ->getProductDataArray($thisProduct, $baseProduct, $item->getQty(), null);
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
            $quoteItems = $this->orderCreateModel->getQuote()->getAllVisibleItems();
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

            $itemsData[]       = $this->helperCatalog
                ->getProductDataArray($thisProduct, $baseProduct, $item->getQty(), $price);
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

        if (!$billingAddress->getStreetLine(1)) {
            return false;
        }

        return [
            'address'       => $billingAddress->getStreetLine(1) .
                ($billingAddress->getStreetLine(2) == '' ? '' : (' ' . $billingAddress->getStreetLine(2))),
            'address2'      => $billingAddress->getStreetLine(3) .
                ($billingAddress->getStreetLine(4) == '' ? '' : (' ' . $billingAddress->getStreetLine(4))),
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

        if (!$shippingAddress->getStreetLine(1)) {
            return false;
        }

        return [
            'fullName'      => $shippingAddress->getName(),
            'address'       => $shippingAddress->getStreetLine(1) .
                ($shippingAddress->getStreetLine(2) == '' ? '' : (' ' . $shippingAddress->getStreetLine(2))),
            'address2'      => $shippingAddress->getStreetLine(3) .
                ($shippingAddress->getStreetLine(4) == '' ? '' : (' ' . $shippingAddress->getStreetLine(4))),
            'city'          => $shippingAddress->getCity(),
            'email'         => $shippingAddress->getEmail(),
            'state'         => $shippingAddress->getRegionCode(),
            'zip'           => $shippingAddress->getPostcode(),
            'phone'         => substr(preg_replace('/[^0-9]+/', '', $shippingAddress->getTelephone()), -10)
        ];
    }

    /**
     * Get Bread Formatted Shipping Address Data From Address Model For API
     *
     * @return array
     */
    public function getShippingAddressAPIData()
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
            'firstName'     => $shippingAddress->getFirstname(),
            'lastName'      => $shippingAddress->getLastname(),
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

        if (!$shippingAddress->getShippingMethod()) {
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
            }else{
                $this->quote = $this->checkoutSession->getQuote();
            }
        }

        return $this->quote;
    }

    /**
     * @return \Magento\Checkout\Model\Session|\Magento\Sales\Model\AdminOrder\Create
     */
    public function getSession()
    {
        if ($this->isInAdmin()) {
            return $this->orderCreateModel;
        } else {
            return $this->checkoutSession;
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return array|string
     */
    public function submitQuote($quote = null)
    {
        if (!$quote) {
            $quote = $this->getSessionQuote();
        }

        $session = $this->getSession();
        if (strtotime($session->getData(self::BREAD_SESSION_QUOTE_UPDATED_KEY)) < strtotime($quote->getUpdatedAt())) {
            $arr = [];
            $arr["expiration"]                 = date('Y-m-d', strtotime("+" . $this->getQuoteExpiration() . "days"));
            $arr["options"]                    = [];
            $arr["options"]["orderRef"]        = $quote->getId();
            $arr["options"]["shippingOptions"] = [$this->getShippingOptions()];
            $arr["options"]["shippingContact"] = $this->getShippingAddressData();
            $arr["options"]["billingContact"]  = $this->getBillingAddressData();
            $arr["options"]["items"]           = $this->getQuoteItemsData();
            $arr["options"]["discounts"]       = $this->getDiscountData() ? $this->getDiscountData() : [];
            $arr["options"]["tax"]             = $this->getTaxValue(false);

            try {
                $result = $this->paymentApiClient->submitCartData($arr);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $result = [];
            }

            $session->setData(self::BREAD_SESSION_QUOTE_RESULT_KEY, $result);
            $session->setData(self::BREAD_SESSION_QUOTE_UPDATED_KEY, $quote->getUpdatedAt());
        }
        return $session->getData(self::BREAD_SESSION_QUOTE_RESULT_KEY);
    }

    /**
     * Check if Product Type is allowed in the Cart
     *
     * @return bool
     */
    public function isProductsAllowedInCart()
    {
        $quote = $this->getSessionQuote();
        $notAllowedProductTypes = array(
            \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE,
        );

        foreach ($quote->getAllVisibleItems() as $cartItem) {
            if (in_array($cartItem->getProduct()->getTypeId(), $notAllowedProductTypes)) {
                return false;
            }
        }

        return true;
    }
}
