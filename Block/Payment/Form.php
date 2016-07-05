<?php
/**
 * Handles Payment Form in Checkout
 *
 * @copyright   Bread   2016
 * @author      Joel    @Mediotype
 * Class Bread_BreadCheckout_Block_Payment_Form
 */
namespace ;

class  extends \Magento\Payment\Block\Form
{
    protected $_quote;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Backend\Model\UrlInterfaceFactory
     */
    protected $backendUrlInterfaceFactory;

    /** @var $_quote Mage_Sales_Model_Quote */

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Model\UrlInterfaceFactory $backendUrlInterfaceFactory
    )
    {
        $this->storeManager = $storeManager;
        $this->backendUrlInterfaceFactory = $backendUrlInterfaceFactory;
        parent::__construct();
        $this->setTemplate('breadcheckout/form.phtml');
    }

    /**
     * Get Bread Formatted Shipping Address Data
     *
     * @return string
     */
    public function getShippingAddressData()
    {
        $shippingAddress    = $this->_getQuote()->getShippingAddress();

        if( is_null($shippingAddress->getStreet(-1)) ){
            return 'false';
        }

        $breadAddressData   = $this->helper('breadcheckout/Quote')->getFormattedShippingAddressData($shippingAddress);

        return $this->helper('core')->jsonEncode($breadAddressData);
    }

    /**
     * Get Bread Formatted Billing Address Data
     *
     * @return string
     */
    public function getBillingAddressData()
    {
        $billingAddress     = $this->_getQuote()->getBillingAddress();

        if(is_null($billingAddress->getStreet(-1))){
            return 'false';
        }

        $breadAddressData   = $this->helper('breadcheckout/Quote')->getFormattedBillingAddressData($billingAddress);

        return $this->helper('core')->jsonEncode($breadAddressData);
    }

    /**
     * Get Tax Amount From Quote
     *
     * @return float
     */
    public function getTaxValue()
    {
        return $this->helper('breadcheckout/Quote')->getTaxValue();
    }

    /**
     * Get Grand Total From Quote
     *
     * @return mixed
     */
    public function getGrandTotal()
    {
        return $this->helper('breadcheckout/Quote')->getGrandTotal();
    }

    /**
     * Get Discount Data From Quote as JSON
     *
     * @return string
     */
    public function getDiscountDataJson()
    {
        $discountData   = $this->helper('breadcheckout/Quote')->getDiscountData();

        return $this->helper('core')->jsonEncode($discountData);
    }

    /**
     * Get Items Data From Quote As JSON
     *
     * @return string JSON String or Empty JSON Array String
     */
    public function getItemsData()
    {
        $itemsData      = $this->helper('breadcheckout/Quote')->getQuoteItemsData();

        return $this->helper('core')->jsonEncode($itemsData);
    }

    /**
     * Get Bread Formatted Shipping Options Information
     *
     * @return string
     */
    public function getShippingOptions()
    {
        $shippingAddress        = $this->_getQuote()->getShippingAddress();

        if(!$shippingAddress->getShippingMethod()){
            return 'false';
        }

        $data   = $this->helper('breadcheckout/quote')->getFormattedShippingOptionsData($shippingAddress);

        return $this->helper('core')->jsonEncode($data);
    }

    /**
     * Get Incomplete Checkout Messaging
     *
     * @return string
     */
    public function getIncompleteCheckoutMessage()
    {
        return $this->helper('breadcheckout')->getIncompleteCheckoutMsg();
    }

    /**
     * Add context URL based on frontend or admin
     *
     * @param $route
     * @return string
     */
    public function getContextUrl($route)
    {
        $isSecure = Mage::app()->getFrontController()->getRequest()->isSecure();
        if ($this->storeManager->getStore()->isAdmin()) {
            $adminUrl = $this->backendUrlInterfaceFactory->create()->getUrl($route, array('_secure'=>true));
            return substr($adminUrl, 0, strpos($adminUrl, 'index/key'));
        } else {
            return $this->getUrl($route, array('_secure'=>true));
        }
    }

    /**
     * Is Default Size Handling
     *
     * @return string
     */
    public function getIsDefaultSize()
    {
        return $this->helper('breadcheckout/Catalog')->getDefaultButtonSizeHtml();

    }

    /**
     * Get validate payment URL
     *
     * @return string
     */
    public function getPaymentUrl()
    {
        return $this->helper('breadcheckout')->getPaymentUrl();
    }

    /**
     * Get tx ID from Quote
     *
     * @return mixed
     */
    public function getBreadTransactionId()
    {
        return $this->_getQuote()->getBreadTransactionId();
    }

    /**
     * Get Additional Design (CSS) From the Admin
     *
     * @return mixed
     */
    public function getButtonDesign()
    {
        return $this->helper('breadcheckout')->getButtonDesign();
    }

    /**
     * Flag Indicative Pricing Or Not
     *
     * @return string
     */
    protected function getAsLowAs()
    {
        return ( $this->helper('breadcheckout')->isAsLowAs() ) ? 'true' : 'false';
    }

    /**
     * Get Session Quote Object for Frontend or Admin
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function _getQuote()
    {
        if ($this->_quote == null) {
                $this->_quote       = $this->helper('breadcheckout/Quote')->getSessionQuote();
        }

        return $this->_quote;
    }

}
