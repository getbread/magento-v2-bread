<?php

/**
 * Handles Config & Basic Shared Helper Functionality
 *
 * @author Bread   copyright   2016
 * @author Joel    @Mediotype
 * @author Miranda @Mediotype
 */

namespace Bread\BreadCheckout\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {


    const API_SANDBOX_URI = 'https://api-sandbox.getbread.com/';
    const API_LIVE_URI = 'https://api.getbread.com/';
    
    const API_PLATFORM_URI_RBC_LIVE = "https://api.rbcpayplan.com/api";
    const API_PLATFORM_URI_RBC_SANDBOX = "https://api-preview.rbc.breadpayments.com/api";
    
    const API_PLATFORM_URI_CORE_LIVE = "https://api.platform.breadpayments.com/api";
    const API_PLATFORM_URI_CORE_SANDBOX = "https://api-preview.platform.breadpayments.com/api";
    
    
    const JS_SANDBOX_URI = 'https://checkout-sandbox.getbread.com/bread.js';
    const JS_LIVE_URI = 'https://checkout.getbread.com/bread.js';
    
    
    const URL_LAMBDA_SENTRY_DSN = 'https://oapavh9uvh.execute-api.us-east-1.amazonaws.com/prod/sentrydsn?platform=magento2';
    const URL_VALIDATE_PAYMENT = 'bread/checkout/validatepaymentmethod';
    const URL_VALIDATE_ORDER = 'bread/checkout/validateorder';
    const URL_VALIDATE_TOTALS = 'bread/checkout/validatetotals';
    const URL_SHIPPING_ESTIMATE = 'bread/checkout/estimateshipping';
    const URL_TAX_ESTIMATE = 'bread/checkout/estimatetax';
    const URL_CONFIG_DATA = 'bread/checkout/configdata';
    const URL_DISCOUNTS_DATA = 'bread/checkout/discountsdata';
    const URL_CLEAR_QUOTE = 'bread/checkout/clearquote';
    const URL_GROUPED_ITEMS = 'bread/checkout/groupeditems';
    const URL_LANDING_PAGE = 'bread/checkout/landingpage';
    const URL_SHIPPING_OPTION = 'bread/checkout/shippingoption';
    const URL_ADMIN_QUOTE_DATA = 'breadadmin/bread/quotedata';
    const URL_ADMIN_VALIDATE_PAYMENT = 'breadadmin/bread/validatepaymentmethod';
    const URL_ADMIN_GENERATE_CART = 'breadadmin/bread/generatecart';
    const URL_ADMIN_SEND_MAIL = 'breadadmin/bread/sendmail';
    const URL_ADMIN_SEND_MAIL_BREAD = 'breadadmin/bread/sendbreademail';
    const URL_ADMIN_SEND_SMS = 'breadadmin/bread/sendsms';
    const XML_CONFIG_MODULE_ACTIVE = 'payment/breadcheckout/active';
    const XML_CONFIG_AS_LOW_AS = 'payment/breadcheckout/as_low_as';
    const XML_CONFIG_PAYMENT_ACTION = 'payment/breadcheckout/payment_action';
    const XML_CONFIG_HEALTHCARE_MODE = 'payment/breadcheckout/healthcare_mode';
    const XML_CONFIG_SHOW_SPLITPAY_LABEL = 'payment/breadcheckout/show_splitpay_label';
    const XML_CONFIG_ACTIVE_ON_PDP = 'payment/breadcheckout/enabled_on_product_page';
    const XML_CONFIG_ACTIVE_ON_CAT = 'payment/breadcheckout/bread_category/enabled_on_category_page';
    const XML_CONFIG_ACTIVE_ON_CART_VIEW = 'payment/breadcheckout/enabled_on_cart_page';
    const XML_CONGIG_MINICART_CHECKOUT = 'payment/breadcheckout/allowminicartcheckout';
    const XML_CONFIG_SHOW_MINICART_LINK = 'payment/breadcheckout/enableonminicart';
    const XML_CONFIG_ENABLE_AS_PAYMENT_METHOD = 'payment/breadcheckout/display_as_payment_method';
    const XML_CONFIG_CHECKOUT_TITLE = 'payment/breadcheckout/title';
    const XML_CONFIG_CHECKOUT_PER_MONTH = 'payment/breadcheckout/per_month';
    const XML_CONFIG_INCOMPLETE_MSG = 'payment/breadcheckout/incomplete_checkout_message';
    const XML_CONFIG_API_PUB_KEY = 'payment/breadcheckout/api_public_key';
    const XML_CONFIG_API_SECRET_KEY = 'payment/breadcheckout/api_secret_key';
    const XML_CONFIG_API_SANDBOX_PUB_KEY = 'payment/breadcheckout/api_sandbox_public_key';
    const XML_CONFIG_API_SANDBOX_SECRET_KEY = 'payment/breadcheckout/api_sandbox_secret_key';
    const XML_CONFIG_CLASSIC_API_PUB_KEY = 'payment/breadcheckout/classic_api_public_key';
    const XML_CONFIG_CLASSIC_API_SECRET_KEY = 'payment/breadcheckout/classic_api_secret_key';
    const XML_CONFIG_CLASSIC_API_SANDBOX_PUB_KEY = 'payment/breadcheckout/classic_api_sandbox_public_key';
    const XML_CONFIG_CLASSIC_API_SANDBOX_SECRET_KEY = 'payment/breadcheckout/classic_api_sandbox_secret_key';
    const XML_CONFIG_JS_LIB_LOCATION = 'payment/breadcheckout/js_location';
    const XML_CONFIG_BUTTON_ON_PRODUCTS = 'payment/breadcheckout/button_on_products';
    const XML_CONFIG_BUTTON_DESIGN = 'payment/breadcheckout/button_design';
    const XML_CONFIG_API_MODE = 'payment/breadcheckout/api_mode';
    const XML_CONFIG_DEFAULT_BUTTON_SIZE = 'payment/breadcheckout/use_default_button_size';
    const XML_CONFIG_CREATE_CUSTOMER = 'payment/breadcheckout/bread_advanced/create_customer_account';
    const XML_CONFIG_ALLOW_CHECKOUT_PDP = 'payment/breadcheckout/allowcheckoutpdp';
    const XML_CONFIG_ALLOW_CHECKOUT_CART = 'payment/breadcheckout/allowcheckoutcart';
    const XML_CONFIG_EMBEDDED_CHECKOUT = 'payment/breadcheckout/embedded';
    const XML_CONFIG_PRODUCT_TYPE_MSG = 'payment/breadcheckout/product_type_msg';
    const XML_CONFIG_ORDER_SHIPPED = 'payment/breadcheckout/bread_advanced/order_shipped';
    const XML_CONFIG_DELETE_QUOTE_AFTER = "checkout/cart/delete_quote_after";
    const XML_CONFIG_THRESHOLD_AMOUNT = 'payment/breadcheckout/bread_advanced/threshold_amount';
    const XML_CONFIG_AUTO_CANCEL = 'payment/breadcheckout/split_auto_cancel';
    const XML_CONFIG_ENABLE_TARGETED_FINANCING = 'payment/breadcheckout/bread_advanced/targeted_financing';
    const XML_CONFIG_TARGETED_FINANCING_ID = 'payment/breadcheckout/bread_advanced/financing_program_id';
    const XML_CONFIG_FINANCING_THRESHOLD = 'payment/breadcheckout/bread_advanced/financing_threshold';
    const XML_CONFIG_FINANCING_SKU = 'payment/breadcheckout/bread_advanced/financing_sku';
    const XML_CONFIG_DISABLED_FOR_SKUS = 'payment/breadcheckout/bread_advanced/disabled_skus';
    const XML_CONFIG_CATEGORY_GROUP = 'payment/breadcheckout/bread_category';
    const XML_CONFIG_CAT_AS_LOW_AS = 'payment/breadcheckout/bread_category/as_low_as';
    const XML_CONFIG_CAT_LABEL_ONLY = 'payment/breadcheckout/bread_category/label_only';
    const XML_CONFIG_CAT_BUTTON_DESIGN = 'payment/breadcheckout/bread_category/button_design';
    const XML_CONFIG_CAT_WINDOW = 'payment/breadcheckout/bread_category/display_new_window';
    const XML_CONFIG_DEFAULT_BS_CAT = 'payment/breadcheckout/bread_category/use_default_button_size';
    const XML_CONFIG_SELECT_CATEGORIES = 'payment/breadcheckout/bread_category/categories';
    const XML_CONFIG_CP_BUTTON_DESIGN = 'payment/breadcheckout/bread_cartpage/button_design';
    const XML_CONFIG_PDP_BUTTON_DESIGN = 'payment/breadcheckout/bread_productdetail/button_design';
    const XML_SENTRY_LOG_ENABLED = 'payment/breadcheckout/bread_advanced/sentry_enabled';
    const XML_SANDBOX_MERCHANT_ID = 'payment/breadcheckout/bread_advanced/sandbox_merchant_id';
    const XML_PRODUCTION_MERCHANT_ID = 'payment/breadcheckout/bread_advanced/production_merchant_id';
    const XML_SANDBOX_PROGRAM_ID = 'payment/breadcheckout/bread_advanced/sandbox_program_id';
    const XML_PRODUCTION_PROGRAM_ID = 'payment/breadcheckout/bread_advanced/production_program_id';
    const BLOCK_CODE_PRODUCT_VIEW = 'product_view';
    const BLOCK_CODE_CHECKOUT_OVERVIEW = 'checkout_overview';
    // Bread button locations
    const BUTTON_LOCATION_PRODUCT_VIEW = 'product';
    const BUTTON_LOCATION_CART_SUMMARY = 'cart_summary';
    const BUTTON_LOCATION_CHECKOUT = 'checkout';
    const BUTTON_LOCATION_FINANCING = 'financing';
    const BUTTON_LOCATION_MARKETING = 'marketing';
    const BUTTON_LOCATION_CATEGORY = 'category';
    const BUTTON_LOCATION_OTHER = 'other';
    const API_CART_EXTENSION = 'carts/';
    
    const JS_SANDBOX_SDK_CORE = 'https://connect-preview.breadpayments.com/sdk.js';
    const JS_LIVE_SDK_CORE = 'https://connect.breadpayments.com/sdk.js';
    
    const JS_SANDBOX_SDK_RBC = 'https://connect-preview.rbc.breadpayments.com/sdk.js';
    const JS_LIVE_SDK_RBC = 'https://connect.rbcpayplan.com/sdk.js';
    
    //Bread 2.0 configurations
    const XML_CONFIG_CLIENT                         = 'payment/breadcheckout/tenant';
    const XML_CONFIG_API_VERSION                    = 'payment/breadcheckout/api_version';
    const XML_CONFIG_AUTH_TOKEN                     = 'payment/breadcheckout/bread_auth_token';
    
    const XML_CONFIG_BREAD_API_PUB_KEY              = 'payment/breadcheckout/bread_api_public_key';
    const XML_CONFIG_BREAD_API_SECRET_KEY           = 'payment/breadcheckout/bread_api_secret_key';
    const XML_CONFIG_BREAD_INTEGRATION_KEY          = 'payment/breadcheckout/api_integration_key';
    
    const XML_CONFIG_BREAD_API_SANDBOX_PUB_KEY      = 'payment/breadcheckout/api_sandbox_public_key';
    const XML_CONFIG_BREAD_API_SANDBOX_SECRET_KEY   = 'payment/breadcheckout/api_sandbox_secret_key';
    const XML_CONFIG_BREAD_API_SANDBOX_INTEGRATION_KEY    = 'payment/breadcheckout/api_sandbox_integration_key';

    /**
     * @var \Magento\Framework\Model\Context
     */
    public $context;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    public $request;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var \Magento\Framework\Encryption\Encryptor
     */
    public $encryptor;

    /**
     * @var \Magento\Framework\UrlInterfaceFactory
     */
    public $urlInterfaceFactory;
    
    /**
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * 
     * @param \Magento\Framework\App\Helper\Context $helperContext
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Framework\UrlInterfaceFactory $urlInterfaceFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
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
        parent::__construct(
                $helperContext
        );
    }

    /**
     * Is module active?
     *
     * @param  null $store
     * @param  null $storeCode
     * @return bool
     */
    public function isActive($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeCode = null) {
        return (bool) $this->scopeConfig->getValue(self::XML_CONFIG_MODULE_ACTIVE, $store, $storeCode);
    }

    /**
     * Check product type against allowed product type list
     *
     * @param  string $typeId
     * @return bool
     */
    public function allowedProductType($typeId) {
        $allowedProductTypes = [
            \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
            \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE,
            \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL,
            \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
            \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE,
            \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE
        ];
        return in_array($typeId, $allowedProductTypes);
    }

    /**
     * Return custom message for non supported product type
     *
     * @param  string $store
     * @return mixed
     */
    public function getProductTypeMessage($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return $this->scopeConfig->getValue(self::XML_CONFIG_PRODUCT_TYPE_MSG, $store);
    }

    /**
     * Is Logging Enabled
     *
     * @param  null $store
     * @return bool
     */
    /*    public function logEnabled($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
      {
      return (bool) $this->scopeConfig->getValue(self::XML_CONFIG_LOG_ENABLED, $store);
      } */

    /**
     * Get API Pub Key
     *
     * @param  null $store
     * @return mixed
     */
    public function getApiPublicKey($breadApiVersion = null,  $storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        $apiVersion = $this->getApiVersion();
        if(!is_null($breadApiVersion)) {
            $apiVersion = $breadApiVersion;
        }
        if($apiVersion === 'bread_2') {
            if ($this->scopeConfig->getValue(self::XML_CONFIG_API_MODE, $store)) {
                return $this->scopeConfig->getValue(self::XML_CONFIG_API_PUB_KEY, $store, $storeCode);
            } else {
                return $this->scopeConfig->getValue(self::XML_CONFIG_API_SANDBOX_PUB_KEY, $store, $storeCode);
            }
        } else {
            if ($this->scopeConfig->getValue(self::XML_CONFIG_API_MODE, $store)) {
                return $this->scopeConfig->getValue(self::XML_CONFIG_CLASSIC_API_PUB_KEY, $store, $storeCode);
            } else {
                return $this->scopeConfig->getValue(self::XML_CONFIG_CLASSIC_API_SANDBOX_PUB_KEY, $store, $storeCode);
            }
        }
        
    }

    /**
     * Get API Secret Key
     *
     * @param  null $store
     * @return string
     */
    public function getApiSecretKey($breadApiVersion = null, $storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        $apiVersion = $this->getApiVersion();
        if(!is_null($breadApiVersion)) {
            $apiVersion = $breadApiVersion;
        }
        if($apiVersion === 'bread_2') {
            if ($this->scopeConfig->getValue(self::XML_CONFIG_API_MODE, $store)) {
                return (string) $this->encryptor->decrypt(
                                $this->scopeConfig->getValue(self::XML_CONFIG_API_SECRET_KEY, $store, $storeCode)
                );
            } else {
                return (string) $this->encryptor->decrypt(
                                $this->scopeConfig->getValue(self::XML_CONFIG_API_SANDBOX_SECRET_KEY, $store, $storeCode)
                );
            }
        } else {
            if ($this->scopeConfig->getValue(self::XML_CONFIG_API_MODE, $store)) {
                return (string) $this->encryptor->decrypt(
                                $this->scopeConfig->getValue(self::XML_CONFIG_CLASSIC_API_SECRET_KEY, $store, $storeCode)
                );
            } else {
                return (string) $this->encryptor->decrypt(
                                $this->scopeConfig->getValue(self::XML_CONFIG_CLASSIC_API_SANDBOX_SECRET_KEY, $store, $storeCode)
                );
            }
        }
    }

    /**
     * Get JS Lib Location
     *
     * @param  null $store
     * @return mixed
     */
    public function getJsLibLocation($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        $apiVersion = $this->getApiVersion();
        
        if ($apiVersion === 'bread_2') {
            $client = $this->getConfigClient();
            if ($this->scopeConfig->getValue(self::XML_CONFIG_API_MODE, $store)) {               
                switch($client) {
                    case 'RBC':
                        return self::JS_LIVE_SDK_RBC;
                        break;
                    default:
                        return self::JS_LIVE_SDK_CORE;    
                }                
            } else {
                switch ($client) {
                    case 'RBC':
                        return self::JS_SANDBOX_SDK_RBC;
                        break;
                    default:
                        return self::JS_SANDBOX_SDK_CORE;
                }        
            }
        } else {
            if ($this->scopeConfig->getValue(self::XML_CONFIG_API_MODE, $store)) {
                return self::JS_LIVE_URI;
            } else {
                return self::JS_SANDBOX_URI;
            }
        }
    }

    /**
     * Get API Url
     *
     * @param  null $store
     * @return mixed
     */
    public function getTransactionApiUrl($breadApiVersion = null, $storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $apiVersion = null) {
        $apiVersion = $this->getApiVersion($storeCode, $store);
        if(!is_null($breadApiVersion)) {
            $apiVersion = $breadApiVersion;
        }
        if($apiVersion === 'bread_2') {
            $tenant = strtoupper($this->getConfigClient($storeCode, $store));
            if ($this->scopeConfig->getValue(self::XML_CONFIG_API_MODE, $store, $storeCode)) {
                return $this->getPlatformApiUri($tenant, 'LIVE');
            } else {
                return $this->getPlatformApiUri($tenant, 'SANDBOX');
            }
        } else {
            if ($this->scopeConfig->getValue(self::XML_CONFIG_API_MODE, $store, $storeCode)) {
                return self::API_LIVE_URI;
            } else {
                return self::API_SANDBOX_URI;
            }
        }        
    }

    /**
     * get Payment URL
     *
     * @return string
     */
    public function getPaymentUrl() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_VALIDATE_PAYMENT, ['_secure' => $isSecure]);
    }

    /**
     * Get The Validate Order URL
     *
     * @return string
     */
    public function getValidateOrderURL() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_VALIDATE_ORDER, ['_secure' => $isSecure]);
    }

    /**
     * Get The Validate Totals URL
     *
     * @return string
     */
    public function getValidateTotalsUrl() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_VALIDATE_TOTALS, ['_secure' => $isSecure]);
    }

    /**
     * Get Shipping Address Estimate URL
     */
    public function getShippingEstimateUrl() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_SHIPPING_ESTIMATE, ['_secure' => $isSecure]);
    }

    /**
     * Get The Tax Estimate URL
     *
     * @return string
     */
    public function getTaxEstimateUrl() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_TAX_ESTIMATE, ['_secure' => $isSecure]);
    }

    /**
     * Get URL for controller which populates
     * address data following shipping step in checkout
     *
     * @return string
     */
    public function getConfigDataUrl() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_CONFIG_DATA, ['_secure' => $isSecure]);
    }

    /**
     * Get URL for controller which populates
     * discounts following shipping step in checkout
     *
     * @return string
     */
    public function getDiscountsDataUrl() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_DISCOUNTS_DATA, ['_secure' => $isSecure]);
    }

    /**
     * Get URL for controller which clears quote
     * after shopping cart rules precalculation
     *
     * @return string
     */
    public function getClearQuoteUrl() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_CLEAR_QUOTE, ['_secure' => $isSecure]);
    }

    /**
     * Get URL for controller which returns data
     * for grouped product items
     *
     * @return string
     */
    public function getGroupedProductItemsUrl() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_GROUPED_ITEMS, ['_secure' => $isSecure]);
    }

    /**
     * Get URL for quote data retrieval in admin checkout
     *
     * @return string
     */
    public function getQuoteDataUrl() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_ADMIN_QUOTE_DATA, ['_secure' => $isSecure]);
    }

    /**
     * Get controller URL for cart generation
     *
     * @return string
     */
    public function getGenerateCartUrl() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_ADMIN_GENERATE_CART, ['_secure' => $isSecure]);
    }

    /**
     * Get controller URL for email sending
     *
     * @return string
     */
    public function getSendMailUrl() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_ADMIN_SEND_MAIL, ['_secure' => $isSecure]);
    }

    /**
     * Get controller URL for email sending via Bread
     *
     * @return string
     */
    public function getSendMailBreadUrl() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_ADMIN_SEND_MAIL_BREAD, ['_secure' => $isSecure]);
    }

    /**
     * Get controller URL for sms sending
     *
     * @return string
     */
    public function getSendSmsUrl() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_ADMIN_SEND_SMS, ['_secure' => $isSecure]);
    }

    /**
     * Get Admin URL Path for Block Context Url Call
     *
     * @return string
     */
    public function getAdminPaymentUrl() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_ADMIN_VALIDATE_PAYMENT, ['_secure' => $isSecure]);
    }

    /**
     * Get controller URL for shipping option selected
     *
     * @return string
     */
    public function getShippingOptionUrl() {
        $isSecure = $this->request->isSecure();
        return $this->urlInterfaceFactory->create()->getUrl(self::URL_SHIPPING_OPTION, ['_secure' => $isSecure]);
    }

    /**
     * Auth or Auth & Settle
     *
     * @param  null $store
     * @return string
     */
    public function getPaymentAction($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (string) $this->scopeConfig->getValue(self::XML_CONFIG_PAYMENT_ACTION, $store);
    }

    /**
     * Payment Method Title During Checkout
     *
     * @param  null $store
     * @return string
     */
    public function getPaymentMethodTitle($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (string) $this->__("" . $this->scopeConfig->getValue(self::XML_CONFIG_CHECKOUT_TITLE, $store));
    }

    /**
     * Show per month calculation next to payment method title on checkout
     *
     * @param  $store
     * @return string
     */
    public function showPerMonthCalculation($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) $this->scopeConfig->getValue(self::XML_CONFIG_CHECKOUT_PER_MONTH, $store);
    }

    /**
     * Is Customer Account Created During Bread Work Flow?
     *
     * @param  null $store
     * @return bool
     */
    public function isAutoCreateCustomerAccountEnabled($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) ($this->isActive($store) && $this->scopeConfig->getValue(self::XML_CONFIG_CREATE_CUSTOMER, $store));
    }

    /**
     * Is button on product page?
     *
     * @param  null $store
     * @return bool
     */
    public function isButtonOnProducts($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) $this->scopeConfig->getValue(self::XML_CONFIG_BUTTON_ON_PRODUCTS, $store);
    }

    /**
     * Is block enabled on product page?
     *
     * @param  null $store
     * @return bool
     */
    public function isEnabledOnPDP($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) ($this->isActive($store) && $this->scopeConfig->getValue(self::XML_CONFIG_ACTIVE_ON_PDP, $store));
    }

    /**
     * Enable button view on cart page
     *
     * @param  null $store
     * @return bool
     */
    public function isEnabledOnCOP($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) ($this->isActive($store) && $this->scopeConfig->getValue(self::XML_CONFIG_ACTIVE_ON_CART_VIEW, $store));
    }

    /**
     * Use Bread As Payment Method In Checkout?
     *
     * @param  null $store
     * @return bool
     */
    public function isPaymentMethodAtCheckout($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) ($this->isActive($store) && $this->scopeConfig->getValue(self::XML_CONFIG_ENABLE_AS_PAYMENT_METHOD, $store));
    }

    /**
     * Is Healthcare mode?
     *
     * @param  null $store
     * @return bool
     */
    public function isHealthcare($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) (
                $this->isActive($store, $storeCode) && $this->scopeConfig->getValue(self::XML_CONFIG_HEALTHCARE_MODE, $store, $storeCode)
                );
    }

    /**
     * Are we showing the split pay label
     * 
     * @param null $store
     * @return bool
     */
    public function showSplitpayLabelOnCheckout($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) (
                $this->isActive($store, $storeCode) && $this->scopeConfig->getValue(self::XML_CONFIG_SHOW_SPLITPAY_LABEL, $store, $storeCode)
                );
    }

    /**
     * Use As Low As Pricing View?
     *
     * @param  null $store
     * @return bool
     */
    public function isAsLowAs($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) ($this->isActive($store, $storeCode) && $this->scopeConfig->getValue(self::XML_CONFIG_AS_LOW_AS, $store, $storeCode));
    }

    /**
     * Allow Checkout From Bread Pop Up on PDP
     *
     * @param  null $store
     * @return bool
     */
    public function getAllowCheckoutPDP($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) ($this->isActive($store) && !$this->isHealthcare() && $this->scopeConfig->getValue(self::XML_CONFIG_ALLOW_CHECKOUT_PDP, $store));
    }

    /**
     * Allow Checkout From Bread On Cart Page
     *
     * @param  null $store
     * @return bool
     */
    public function getAllowCheckoutCP($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) ($this->isActive($store) &&
                !$this->isHealthcare() && $this->scopeConfig->getValue(self::XML_CONFIG_ALLOW_CHECKOUT_CART, $store));
    }

    /**
     * Check if cart size targeted financing is enabled
     *
     * @return bool
     */
    public function isTargetedFinancing($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) ($this->isActive($store, $storeCode) && $this->scopeConfig->getValue(self::XML_CONFIG_ENABLE_TARGETED_FINANCING, $store, $storeCode));
    }

    /**
     * Get financing ID associated with cart size threshold
     *
     * @return string
     */
    public function getFinancingId($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return $this->scopeConfig->getValue(self::XML_CONFIG_TARGETED_FINANCING_ID, $store, $storeCode);
    }

    /**
     * Get cart size over which targeted financing is enabled
     *
     * @return string
     */
    public function getTargetedFinancingThreshold($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        $financingThreshold = $this->scopeConfig->getValue(self::XML_CONFIG_FINANCING_THRESHOLD, $store, $storeCode);
        if(!is_null($financingThreshold)) {
            return round($financingThreshold, 2);
        } else {
            return 0;
        }       
    }
    /**
     * Return list of SKU's for which financing is enabled
     *
     * @param string $store
     * @return array
     */
    public function getTargetedFinancingSkus($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        $list = $this->scopeConfig->getValue(self::XML_CONFIG_FINANCING_SKU, $store, $storeCode);
        if(is_null($list)) {
            return array();
        } 
        $list = preg_replace('/\s/', '', $list);
        return explode(',', $list);
    }

    /**
     * Return financing mode
     *
     * @param string $mode
     * @param string $store
     * @return int
     */
    public function checkFinancingMode($mode, $storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        $configVal = (int) $this->scopeConfig->getValue(self::XML_CONFIG_ENABLE_TARGETED_FINANCING, $store, $storeCode);
        $output = null;

        switch ($mode) {
            case 'cart':
                $output = ($configVal === 1);
                break;
            case 'sku':
                $output = ($configVal === 2);
                break;
        }

        return $output;
    }

    /**
     * Get Product View Block Code
     *
     * @return string
     */
    public function getBlockCodeProductView() {
        return (string) self::BLOCK_CODE_PRODUCT_VIEW;
    }

    /**
     * Get Checkout Overview Block Code
     *
     * @return string
     */
    public function getBlockCodeCheckoutOverview() {
        return (string) self::BLOCK_CODE_CHECKOUT_OVERVIEW;
    }

    /**
     * Get Custom Button Design
     *
     * @param  null $store
     * @return mixed
     */
    public function getButtonDesign($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return $this->scopeConfig->getValue(self::XML_CONFIG_BUTTON_DESIGN, $store, $storeCode);
    }

    /**
     * Get Checkout Cart Button Design
     *
     * @param  null $store
     * @return mixed
     */
    public function getCartButtonDesign($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return $this->scopeConfig->getValue(self::XML_CONFIG_CP_BUTTON_DESIGN, $store);
    }

    /**
     * Get PDP Button Design
     *
     * @param  null $store
     * @return mixed
     */
    public function getPDPButtonDesign($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return $this->scopeConfig->getValue(self::XML_CONFIG_PDP_BUTTON_DESIGN, $store);
    }

    /**
     * Check If Default Button Size Is Used
     *
     * @param  null $store
     * @return bool
     */
    public function useDefaultButtonSize($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) ($this->isActive($store) &&
                $this->scopeConfig->getValue(self::XML_CONFIG_DEFAULT_BUTTON_SIZE, $store));
    }

    /**
     * Incomplete Checkout Message For Payment Method Form
     *
     * @param  string $store
     * @return string
     */
    public function getIncompleteCheckoutMsg($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (string) $this->scopeConfig->getValue(self::XML_CONFIG_INCOMPLETE_MSG, $store);
    }

    /**
     * Check if embedded checkout is enabled
     *
     * @param  string $store
     * @return int
     */
    public function embeddedCheckoutEnabled($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) $this->scopeConfig->getValue(self::XML_CONFIG_EMBEDDED_CHECKOUT, $store);
    }

    /**
     * Dispatch order shipment details to Bread
     *
     * @param  null $store
     * @return bool
     */
    public function dispatchShipmentData($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) $this->scopeConfig->getValue(self::XML_CONFIG_ORDER_SHIPPED, $store);
    }

    /**
     * Dispatch order shipment details to Bread
     *
     * @param  null $store
     * @return bool
     */
    public function isSentryEnabled($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) $this->scopeConfig->getValue(self::XML_SENTRY_LOG_ENABLED, $store);
    }

    /**
     * Incomplete Checkout Message For Payment Method Form
     *
     * @param string $store
     *
     * @return string
     */
    public function getQuoteExpiration($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return $this->scopeConfig->getValue(self::XML_CONFIG_DELETE_QUOTE_AFTER, $store);
    }

    /**
     * Get Default Country
     *
     * @return string
     */
    public function getDefaultCountry() {
        return 'US';
    }

    /**
     * Get cart size financing configuration
     *
     * @return array
     */
    public function getFinancingData($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return [
            "enabled" => $this->isTargetedFinancing($storeCode, $store),
            "mode" => [
                'cart' => $this->checkFinancingMode('cart', $storeCode, $store),
                'sku' => $this->checkFinancingMode('sku', $storeCode, $store)
            ],
            "id" => $this->getFinancingId($storeCode, $store),
            "threshold" => $this->getTargetedFinancingThreshold($storeCode, $store),
            "sku_limit" => $this->getTargetedFinancingSkus($storeCode, $store)
        ];
    }

    /**
     * Check if Called From Admin Or Not
     *
     * @return bool
     */
    public function isInAdmin() {
        return (bool) ($this->context->getAppState()->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE);
    }

    /**
     * Get button location string for product page
     *
     * @return string
     */
    public function getProductViewLocation() {
        return (string) self::BUTTON_LOCATION_PRODUCT_VIEW;
    }

    /**
     * Get button location string for cart summary page
     *
     * @return string
     */
    public function getCartSummaryLocation() {
        return (string) self::BUTTON_LOCATION_CART_SUMMARY;
    }

    /**
     * Get button location string for checkout page
     *
     * @return string
     */
    public function getCheckoutLocation() {
        return (string) self::BUTTON_LOCATION_CHECKOUT;
    }

    /**
     * Get button location string for financing page
     *
     * @return string
     */
    public function getFinancingLocation() {
        return (string) self::BUTTON_LOCATION_FINANCING;
    }

    /**
     * Get button location string for marketing page
     *
     * @return string
     */
    public function getMarketingLocation() {
        return (string) self::BUTTON_LOCATION_MARKETING;
    }

    /**
     * Get button location string for category page
     *
     * @return string
     */
    public function getCategoryPageLocation() {
        return (string) self::BUTTON_LOCATION_CATEGORY;
    }

    /**
     * Get button location string for other purposes
     *
     * @return string
     */
    public function getOtherLocation() {
        return (string) self::BUTTON_LOCATION_OTHER;
    }

    /**
     * Replaces single quotes with double quotes to prevent javascript syntax error
     *
     * @param $input
     *
     * @return string
     */
    public function escapeCustomCSS($input) {
        if(!is_null($input)) {
            return str_replace("'", '"', $input);
        }
        return "";
    }

    /**
     * Get cart API Url
     *
     * @param  null $store
     * @return mixed
     */
    public function getCartCreateApiUrl($store = null) {
        return $this->getTransactionApiUrl($store) . self::API_CART_EXTENSION;
    }

    /**
     * Get Allow Mini cart checkout
     *
     * @param  string $store
     * @return bool
     */
    public function allowMinicartCheckout($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) $this->scopeConfig->getValue(self::XML_CONGIG_MINICART_CHECKOUT, $store);
    }
    
    /**
     * Show link on minicart page for bread_2
     * 
     * @param  string $store
     * @return bool
     */
    public function showMinicartLink($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) $this->scopeConfig->getValue(self::XML_CONFIG_SHOW_MINICART_LINK, $store);
    }

    /**
     * Check if cost of item/cart total is equal or greater than set threshold amount
     *
     * @param  float $cost
     * @return bool
     */
    public function aboveThreshold($cost) {
        $aboveThreshold = true;
        $thresholdAmount = (int) $this->scopeConfig->getValue(self::XML_CONFIG_THRESHOLD_AMOUNT);

        if ($thresholdAmount) {
            $aboveThreshold = (int) $cost >= $thresholdAmount;
        }

        return $aboveThreshold;
    }

    /**
     * Get auto cancel on split payment declined order
     *
     * @param  string $store
     * @return bool
     */
    public function isSplitPayAutoDecline($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return (bool) $this->scopeConfig->getValue(self::XML_CONFIG_AUTO_CANCEL, $store);
    }

    /**
     * Return api url array
     *
     * @since 2.1.0
     * @param null 
     * @return arr 
     */
    public function getPlatformApiUri($tenant, $env) {
        switch ($tenant) {
            case 'RBC':
                if ($env === 'LIVE') {
                    return self::API_PLATFORM_URI_RBC_LIVE;
                } else {
                    return self::API_PLATFORM_URI_RBC_SANDBOX;
                }
                break;
            case 'CORE':
            default:
                if ($env === 'LIVE') {
                    return self::API_PLATFORM_URI_CORE_LIVE;
                } else {
                    return self::API_PLATFORM_URI_CORE_SANDBOX;
                }
        }
    }
    
    /**
     * @since 2.1.0
     * @param null $storeCode
     * @param $store
     * @return string
     */
    public function getApiVersion($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        $apiVersion = $this->scopeConfig->getValue(self::XML_CONFIG_API_VERSION, $store, $storeCode);
        if($apiVersion) {
            return (string) $apiVersion;
        } else {
            return 'bread_2';
        }
    }
    
    /**
     * Returns the tenant name from the database
     * 
     * @since 2.1.0
     * @return string
     */
    public function getConfigClient($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        $configClient = $this->scopeConfig->getValue(self::XML_CONFIG_CLIENT, $store, $storeCode);
        if(is_null($configClient)) {
            return strtoupper('core');
        } else {
            return strtoupper($configClient);
        }
    }
    
    /**
     * @since 2.1.0
     * @param null $storeCode
     * @param string $store
     * @return string
     */
    public function getIntegrationKey($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        if ($this->scopeConfig->getValue(self::XML_CONFIG_API_MODE, $store)) {
            return (string) $this->scopeConfig->getValue(self::XML_CONFIG_BREAD_INTEGRATION_KEY, $store, $storeCode);
        } else {
            return (string) $this->scopeConfig->getValue(self::XML_CONFIG_BREAD_API_SANDBOX_INTEGRATION_KEY, $store, $storeCode);
        }
    }
    
    /**
     * @since 2.3.4
     * @param null $storeCode
     * @param string $store
     * @return string
     */
    public function getMerchantId($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        if ($this->scopeConfig->getValue(self::XML_CONFIG_API_MODE, $store)) {
            return (string) $this->scopeConfig->getValue(self::XML_PRODUCTION_MERCHANT_ID, $store, $storeCode);
        } else {
            return (string) $this->scopeConfig->getValue(self::XML_SANDBOX_MERCHANT_ID, $store, $storeCode);
        }
    }
    
    /**
     * @since 2.3.4
     * @param null $storeCode
     * @param string $store
     * @return string
     */
    public function getProgramId($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        if ($this->scopeConfig->getValue(self::XML_CONFIG_API_MODE, $store)) {
            return (string) $this->scopeConfig->getValue(self::XML_PRODUCTION_PROGRAM_ID, $store, $storeCode);
        } else {
            return (string) $this->scopeConfig->getValue(self::XML_SANDBOX_PROGRAM_ID, $store, $storeCode);
        }
    }
    
    
    /**
     * @since 2.1.0
     * @param type $storeCode
     * @param type $store
     * @return type
     */
    public function getAuthToken($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        return $this->scopeConfig->getValue(self::XML_CONFIG_AUTH_TOKEN, $store, $storeCode);
    }
    
    /**
     * Get the merchant country code
     * 
     * @since 2.1.0
     * @return string
     */
    public function getMerchantCountry($storeCode = null, $store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        $client = $this->getConfigClient();
        switch($client) {
            case 'RBC':
                return 'CA';
            case 'CORE':
                return 'US';
            default:    
                return $this->scopeConfig->getValue('general/country/default', $store, $storeCode);
        }
    }
    
    /**
     * 
     * Get the current selected currency
     * 
     * @since 2.1.0
     * @return string
     */
    public function getCurrentCurrencyCode() {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

}
