<?php
/**
 * Handles Payment Form in Checkout
 *
 * @copyright   Bread   2016
 * @author      Joel    @Mediotype
 * @author      Miranda @Mediotype
 * Class Bread\BreadCheckout\Block\Payment\Form
 */
namespace Bread\BreadCheckout\Block\Payment;

class Form extends \Magento\Payment\Block\Form
{
    /** @var $_quote \Magento\Sales\Model\Quote */
    protected $quote;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Backend\Model\UrlInterfaceFactory */
    protected $backendUrlInterfaceFactory;

    /** @var \Bread\BreadCheckout\Helper\Data */
    protected $helper;

    /** @var \Bread\BreadCheckout\Helper\Quote */
    protected $quoteHelper;

    /** @var \Bread\BreadCheckout\Helper\Catalog */
    protected $catalogHelper;

    /** @var \Magento\Framework\Json\Helper\Data */
    protected $jsonHelper;

    /** @var \Magento\Framework\View\Context */
    protected $viewContext;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Model\UrlInterfaceFactory $backendUrlInterfaceFactory,
        \Bread\BreadCheckout\Helper\Data $helper,
        \Bread\BreadCheckout\Helper\Quote $quoteHelper,
        \Bread\BreadCheckout\Helper\Catalog $catalogHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\View\Context $viewContext
    )
    {
        $this->storeManager = $storeManager;
        $this->backendUrlInterfaceFactory = $backendUrlInterfaceFactory;
        $this->helper = $helper;
        $this->quoteHelper = $quoteHelper;
        $this->catalogHelper = $catalogHelper;
        $this->jsonHelper = $jsonHelper;
        $this->viewContext = $viewContext;
        $this->setTemplate('breadcheckout/form.phtml');
        parent::__construct();
    }

    /**
     * Get Bread Formatted Shipping Address Data
     *
     * @return string
     */
    public function getShippingAddressData()
    {
        $shippingAddress    = $this->getQuote()->getShippingAddress();

        if( is_null($shippingAddress->getStreet(-1)) ){
            return 'false';
        }

        $breadAddressData   = $this->quoteHelper->getFormattedShippingAddressData($shippingAddress);

        return $this->jsonHelper->jsonEncode($breadAddressData);
    }

    /**
     * Get Bread Formatted Billing Address Data
     *
     * @return string
     */
    public function getBillingAddressData()
    {
        $billingAddress     = $this->getQuote()->getBillingAddress();

        if(is_null($billingAddress->getStreet(-1))){
            return 'false';
        }

        $breadAddressData   = $this->quoteHelper->getFormattedBillingAddressData($billingAddress);

        return $this->jsonHelper->jsonEncode($breadAddressData);
    }

    /**
     * Get Tax Amount From Quote
     *
     * @return float
     */
    public function getTaxValue()
    {
        return $this->quoteHelper->getTaxValue();
    }

    /**
     * Get Grand Total From Quote
     *
     * @return mixed
     */
    public function getGrandTotal()
    {
        return $this->quoteHelper->getGrandTotal();
    }

    /**
     * Get Discount Data From Quote as JSON
     *
     * @return string
     */
    public function getDiscountDataJson()
    {
        $discountData   = $this->quoteHelper->getDiscountData();

        return $this->jsonHelper->jsonEncode($discountData);
    }

    /**
     * Get Items Data From Quote As JSON
     *
     * @return string JSON String or Empty JSON Array String
     */
    public function getItemsData()
    {
        $itemsData      = $this->quoteHelper->getQuoteItemsData();

        return $this->jsonHelper->jsonEncode($itemsData);
    }

    /**
     * Get Bread Formatted Shipping Options Information
     *
     * @return string
     */
    public function getShippingOptions()
    {
        $shippingAddress        = $this->getQuote()->getShippingAddress();

        if(!$shippingAddress->getShippingMethod()){
            return 'false';
        }

        $data   = $this->quoteHelper->getFormattedShippingOptionsData($shippingAddress);
        return $this->jsonHelper->jsonEncode($data);
    }

    /**
     * Get Incomplete Checkout Messaging
     *
     * @return string
     */
    public function getIncompleteCheckoutMessage()
    {
        return $this->helper->getIncompleteCheckoutMsg();
    }

    /**
     * Add context URL based on frontend or admin
     *
     * @param $route
     * @return string
     */
    public function getContextUrl($route)
    {
        $isSecure = $this->viewContext->getFrontController()->getRequest()->isSecure();
        if ($this->storeManager->getStore()->isAdmin()) {
            $adminUrl = $this->backendUrlInterfaceFactory->create()->getUrl($route, ['_secure'=>true]);
            return substr($adminUrl, 0, strpos($adminUrl, 'index/key'));
        } else {
            return $this->getUrl($route, ['_secure'=>true]);
        }
    }

    /**
     * Is Default Size Handling
     *
     * @return string
     */
    public function getIsDefaultSize()
    {
        return $this->catalogHelper->getDefaultButtonSizeHtml();
    }

    /**
     * Get validate payment URL
     *
     * @return string
     */
    public function getPaymentUrl()
    {
        return $this->helper->getPaymentUrl();
    }

    /**
     * Get tx ID from Quote
     *
     * @return mixed
     */
    public function getBreadTransactionId()
    {
        return $this->getQuote()->getBreadTransactionId();
    }

    /**
     * Get Additional Design (CSS) From the Admin
     *
     * @return mixed
     */
    public function getButtonDesign()
    {
        return $this->helper->getButtonDesign();
    }

    /**
     * Flag Indicative Pricing Or Not
     *
     * @return string
     */
    protected function getAsLowAs()
    {
        return ( $this->helper->isAsLowAs() ) ? 'true' : 'false';
    }

    /**
     * Get Session Quote Object for Frontend or Admin
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function getQuote()
    {
        if ($this->quote == null) {
                $this->quote       = $this->quoteHelper->getSessionQuote();
        }
        return $this->quote;
    }

}
