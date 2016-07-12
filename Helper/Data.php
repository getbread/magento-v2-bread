<?php
/**
 * Handles Config & Basic Shared Helper Functionality
 *
 * @author  Bread   copyright   2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const API_SANDBOX_URI                           = "https://api-sandbox.getbread.com/";
    const API_LIVE_URI                              = "https://api.getbread.com/";

    const JS_SANDBOX_URI                            = "https://checkout-sandbox.getbread.com/bread.js";
    const JS_LIVE_URI                               = "https://checkout.getbread.com/bread.js";

    const URL_VALIDATE_PAYMENT                      = "bread/checkout/validatepaymentmethod";
    const URL_VALIDATE_ORDER                        = "bread/checkout/validateorder";
    const URL_SHIPPING_ESTIMATE                     = "bread/checkout/estimateshipping";
    const URL_TAX_ESTIMATE                          = "bread/checkout/estimatetax";
    const URL_ADMIN_VALIDATE_PAYMENT                 = "breadadmin/bread/validatepaymentmethod";

    const XML_CONFIG_MODULE_ACTIVE                  = 'payment/breadcheckout/active';
    const XML_CONFIG_LOG_ENABLED                    = 'payment/breadcheckout/log_enabled';
    const XML_CONFIG_AS_LOW_AS                      = 'payment/breadcheckout/as_low_as';
    const XML_CONFIG_PAYMENT_ACTION                 = 'payment/breadcheckout/payment_action';
    const XML_CONFIG_ACTIVE_ON_PDP                  = 'payment/breadcheckout/enabled_on_product_page';
    const XML_CONFIG_ACTIVE_ON_CART_VIEW            = 'payment/breadcheckout/enabled_on_cart_page';
    const XML_CONFIG_ENABLE_AS_PAYMENT_METHOD       = 'payment/breadcheckout/display_as_payment_method';
    const XML_CONFIG_CHECKOUT_TITLE                 = 'payment/breadcheckout/title';
    const XML_CONFIG_INCOMPLETE_MSG                 = 'payment/breadcheckout/incomplete_checkout_message';
    const XML_CONFIG_API_PUB_KEY                    = 'payment/breadcheckout/api_public_key';
    const XML_CONFIG_API_SECRET_KEY                 = 'payment/breadcheckout/api_secret_key';
    const XML_CONFIG_JS_LIB_LOCATION                = 'payment/breadcheckout/js_location';
    const XML_CONFIG_BUTTON_ON_PRODUCTS             = 'payment/breadcheckout/button_on_products';
    const XML_CONFIG_BUTTON_DESIGN                  = 'payment/breadcheckout/button_design';
    const XML_CONFIG_API_MODE                       = 'payment/breadcheckout/api_mode';
    const XML_CONFIG_DEFAULT_BUTTON_SIZE            = 'payment/breadcheckout/use_default_button_size';
    const XML_CONFIG_CREATE_CUSTOMER                = 'payment/breadcheckout/create_customer_account';
    const XML_CONFIG_LOGIN_CUSTOMER                 = 'payment/breadcheckout/login_customer_on_order';
    const XML_CONFIG_ALLOW_CHECKOUT_PDP             = 'payment/breadcheckout/allowcheckoutpdp';
    const XML_CONFIG_ALLOW_CHECKOUT_CART            = 'payment/breadcheckout/allowcheckoutcart';

    const BLOCK_CODE_PRODUCT_VIEW                   = 'product_view';
    const BLOCK_CODE_CHECKOUT_OVERVIEW              = 'checkout_overview';

    /** @var \Magento\Framework\Model\Context */
    protected $context;

    /** @var \Magento\Framework\App\Request\Http */
    protected $request;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    /** @var \Magento\Framework\Encryption\Encryptor */
    protected $encryptor;

    /** @var \Magento\Framework\UrlInterfaceFactory */
    protected $urlInterfaceFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    public function __construct(
        \Magento\Framework\App\Helper\Context $helperContext,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\UrlInterfaceFactory $urlInterfaceFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->context = $context;
        $this->request = $request;
        $this->scopeConfig = $helperContext->getScopeConfig();
        $this->encryptor = $encryptor;
        $this->urlInterfaceFactory = $urlInterfaceFactory;
        $this->storeManager = $storeManager;
        $this->logger = $helperContext->getLogger();
        parent::__construct(
            $helperContext
        );
    }


    /**
     * Is module active?
     *
     * @param null $store
     * @return bool
     */
    public function isActive($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->scopeConfig->getValue(self::XML_CONFIG_MODULE_ACTIVE, $store);
    }

    /**
     * Is Logging Enabled
     *
     * @param null $store
     * @return bool
     */
    public function logEnabled($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->scopeConfig->getValue(self::XML_CONFIG_LOG_ENABLED, $store);
    }

    /**
     * Get API Pub Key
     *
     * @param null $store
     * @return mixed
     */
    public function getApiPublicKey($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue(self::XML_CONFIG_API_PUB_KEY, $store);
    }

    /**
     * Get API Secret Key
     *
     * @param null $store
     * @return string
     */
    public function getApiSecretKey($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (string) $this->encryptor->decrypt(
            $this->scopeConfig->getValue(self::XML_CONFIG_API_SECRET_KEY, $store));
    }

    /**
     * Get JS Lib Location
     *
     * @param null $store
     * @return mixed
     */
    public function getJsLibLocation($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        if($this->scopeConfig->getValue(self::XML_CONFIG_API_MODE, $store)){
            return self::JS_LIVE_URI;
        } else {
            return self::JS_SANDBOX_URI;
        }
    }

    /**
     * Get API Url
     *
     * @param null $store
     * @return mixed
     */
    public function getTransactionApiUrl($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        if($this->scopeConfig->getValue(self::XML_CONFIG_API_MODE, $store)){
            return self::API_LIVE_URI;
        } else {
            return self::API_SANDBOX_URI;
        }
    }

    /**
     * get Payment URL
     *
     * @return string
     */
    public function getPaymentUrl()
    {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_VALIDATE_PAYMENT,['_secure'=>$isSecure]);
    }

    /**
     * Get The Validate Order URL
     *
     * @return string
     */
    public function getValidateOrderURL()
    {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_VALIDATE_ORDER,['_secure'=>$isSecure]);
    }

    /**
     * Get Shipping Address Estimate URL
     *
     */
    public function getShippingEstimateUrl()
    {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_SHIPPING_ESTIMATE,['_secure'=>$isSecure]);
    }

    /**
     * Get The Tax Estimate URL
     *
     * @return string
     */
    public function getTaxEstimateUrl()
    {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_TAX_ESTIMATE,array('_secure'=>true));
    }

    /**
     * Get Admin URL Path for Block Context Url Call
     *
     * @return string
     */
    public function getAdminFormUrlPath()
    {
        return self::URL_ADMIN_VALIDATE_PAYMENT;
    }

    /**
     * Auth or Auth & Settle
     *
     * @param null $store
     * @return string
     */
    public function getPaymentAction($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (string) $this->scopeConfig->getValue(self::XML_CONFIG_PAYMENT_ACTION, $store);
    }

    /**
     * Payment Method Title During Checkout
     *
     * @param null $store
     * @return string
     */
    public function getPaymentMethodTitle($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (string) $this->__($this->scopeConfig->getValue(self::XML_CONFIG_CHECKOUT_TITLE, $store));
    }

    /**
     * Is Customer Account Created During Bread Work Flow?
     *
     * @param null $store
     * @return bool
     */
    public function isAutoCreateCustomerAccountEnabled($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool) ($this->isActive($store) && $this->scopeConfig->getValue(self::XML_CONFIG_CREATE_CUSTOMER, $store));
    }

    /**
     * Login Customer After Order Created From Pop Up
     *
     * @param null $store
     * @return bool
     */
    public function isLoginAfterPopUpOrder($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool) ($this->isActive($store) && $this->scopeConfig->getValue(self::XML_CONFIG_LOGIN_CUSTOMER, $store));
    }

    /**
     * Is button on product page?
     *
     * @param null $store
     * @return bool
     */
    public function isButtonOnProducts($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->scopeConfig->getValue(self::XML_CONFIG_BUTTON_ON_PRODUCTS, $store);
    }

    /**
     * Is block enabled on product page?
     *
     * @param null $store
     * @return bool
     */
    public function isEnabledOnPDP($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool) ($this->isActive($store) && $this->scopeConfig->getValue(self::XML_CONFIG_ACTIVE_ON_PDP, $store));
    }

    /**
     * Enable button view on cart page
     *
     * @param null $store
     * @return bool
     */
    public function isEnabledOnCOP($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool) ($this->isActive($store) && $this->scopeConfig->getValue(self::XML_CONFIG_ACTIVE_ON_CART_VIEW, $store));
    }

    /**
     * Use Bread As Payment Method In Checkout?
     *
     * @param null $store
     * @return bool
     */
    public function isPaymentMethodAtCheckout($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool) ($this->isActive($store) && $this->scopeConfig->getValue(self::XML_CONFIG_ENABLE_AS_PAYMENT_METHOD, $store));
    }

    /**
     * Use As Low As Pricing View?
     *
     * @param null $store
     * @return bool
     */
    public function isAsLowAs($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool) ($this->isActive($store) && $this->scopeConfig->getValue(self::XML_CONFIG_AS_LOW_AS, $store));
    }

    /**
     * Allow Checkout From Bread Pop Up on PDP
     *
     * @param null $store
     * @return bool
     */
    public function getAllowCheckoutPDP($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool) ($this->isActive($store) && $this->scopeConfig->getValue(self::XML_CONFIG_ALLOW_CHECKOUT_PDP, $store));
    }

    /**
     * Allow Checkout From Bread On Cart Page
     *
     * @param null $store
     * @return bool
     */
    public function getAllowCheckoutCP($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool) ($this->isActive($store) && $this->scopeConfig->getValue(self::XML_CONFIG_ALLOW_CHECKOUT_CART, $store));
    }

    /**
     * Get Product View Block Code
     *
     * @return string
     */
    public function getBlockCodeProductView()
    {
        return  (string) self::BLOCK_CODE_PRODUCT_VIEW;
    }

    /**
     * Get Checkout Overview Block Code
     *
     * @return string
     */
    public function getBlockCodeCheckoutOverview()
    {
        return (string) self::BLOCK_CODE_CHECKOUT_OVERVIEW;
    }

    /**
     * Get Custom Button Design
     *
     * @param null $store
     * @return mixed
     */
    public function getButtonDesign($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue(self::XML_CONFIG_BUTTON_DESIGN, $store);
    }

    /**
     * Check If Default Button Size Is Used
     *
     * @param null $store
     * @return bool
     */
    public function useDefaultButtonSize($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (bool) ($this->isActive($store) &&
                       $this->scopeConfig->getValue(self::XML_CONFIG_DEFAULT_BUTTON_SIZE, $store));
    }

    /**
     * Incomplete Checkout Message For Payment Method Form
     *
     * @param null $store
     * @return string
     */
    public function getIncompleteCheckoutMsg($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return (string) $this->scopeConfig->getValue(self::XML_CONFIG_INCOMPLETE_MSG, $store);
    }

    /**
     * Get Default Country
     *
     * @param null $store
     * @return string
     */
    public function getDefaultCountry()
    {
        return 'US';
    }

    /**
     * Check if Called From Admin Or Not
     *
     * @return bool
     */
    public function isInAdmin()
    {
        return (bool) ($this->context->getAppState()->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE);
    }

    public function log($data, $context = 'Bread\BreadCheckout'){
        if( $this->logEnabled() ) {
            if ( !is_string($data) ) {
                $data = print_r($data, true);
            }
            $this->logger->debug($data, [$context]);
        }
    }
}
