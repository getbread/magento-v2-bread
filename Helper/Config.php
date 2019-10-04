<?php
declare(strict_types=1);

namespace Bread\BreadCheckout\Helper;

use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Downloadable\Model\Product\Type as Downloadable;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\UrlInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\ScopeInterface;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    const DEFAULT_COUNTRY                           = 'US';

    const API_SANDBOX_URI                           = 'https://api-sandbox.getbread.com/';
    const API_LIVE_URI                              = 'https://api.getbread.com/';

    const JS_SANDBOX_URI                            = 'https://checkout-sandbox.getbread.com/bread.js';
    const JS_LIVE_URI                               = 'https://checkout.getbread.com/bread.js';

    const URL_LAMBDA_SENTRY_DSN                     = 'https://oapavh9uvh.execute-api.us-east-1.amazonaws.com/prod/sentrydsn?platform=magento2';

    const URL_VALIDATE_PAYMENT                      = 'bread/checkout/validatepaymentmethod';
    const URL_VALIDATE_ORDER                        = 'bread/checkout/validateorder';
    const URL_VALIDATE_TOTALS                       = 'bread/checkout/validatetotals';
    const URL_SHIPPING_ESTIMATE                     = 'bread/checkout/estimateshipping';
    const URL_TAX_ESTIMATE                          = 'bread/checkout/estimatetax';
    const URL_CONFIG_DATA                           = 'bread/checkout/configdata';
    const URL_DISCOUNTS_DATA                        = 'bread/checkout/discountsdata';
    // OVO NE KORISTIMO const URL_CLEAR_QUOTE                           = 'bread/checkout/clearquote';
    const URL_GROUPED_ITEMS                         = 'bread/checkout/groupeditems';
    const URL_LANDING_PAGE                          = 'bread/checkout/landingpage';
    const URL_SHIPPING_OPTION                       = 'bread/checkout/shippingoption';
    const URL_ADMIN_QUOTE_DATA                      = 'breadadmin/bread/quotedata';
    const URL_ADMIN_VALIDATE_PAYMENT                = 'breadadmin/bread/validatepaymentmethod';
    const URL_ADMIN_GENERATE_CART                   = 'breadadmin/bread/generatecart';
    const URL_ADMIN_SEND_MAIL                       = 'breadadmin/bread/sendmail';
    const URL_ADMIN_SEND_MAIL_BREAD                 = 'breadadmin/bread/sendbreademail';
    const URL_ADMIN_SEND_SMS                        = 'breadadmin/bread/sendsms';

    const XML_CONFIG_MODULE_ACTIVE                  = 'payment/breadcheckout/active';

    const XML_CONFIG_AS_LOW_AS                      = 'payment/breadcheckout/as_low_as';
    const XML_CONFIG_PAYMENT_ACTION                 = 'payment/breadcheckout/payment_action';
    const XML_CONFIG_HEALTHCARE_MODE                = 'payment/breadcheckout/healthcare_mode';
    const XML_CONFIG_ACTIVE_ON_PRODUCT_VIEW         = 'payment/breadcheckout/enabled_on_product_page';
    const XML_CONFIG_ACTIVE_ON_CAT                  = 'payment/breadcheckout/bread_category/enabled_on_category_page';
    const XML_CONFIG_ACTIVE_ON_CART_VIEW            = 'payment/breadcheckout/enabled_on_cart_page';
    const XML_CONGIG_MINICART_CHECKOUT              = 'payment/breadcheckout/allowminicartcheckout';
    const XML_CONFIG_ENABLE_AS_PAYMENT_METHOD       = 'payment/breadcheckout/display_as_payment_method';
    const XML_CONFIG_CHECKOUT_TITLE                 = 'payment/breadcheckout/title';
    const XML_CONFIG_CHECKOUT_PER_MONTH             = 'payment/breadcheckout/per_month';
    const XML_CONFIG_INCOMPLETE_MSG                 = 'payment/breadcheckout/incomplete_checkout_message';
    const XML_CONFIG_API_PUB_KEY                    = 'payment/breadcheckout/api_public_key';
    const XML_CONFIG_API_SECRET_KEY                 = 'payment/breadcheckout/api_secret_key';
    const XML_CONFIG_API_SANDBOX_PUB_KEY            = 'payment/breadcheckout/api_sandbox_public_key';
    const XML_CONFIG_API_SANDBOX_SECRET_KEY         = 'payment/breadcheckout/api_sandbox_secret_key';
    const XML_CONFIG_JS_LIB_LOCATION                = 'payment/breadcheckout/js_location';
    const XML_CONFIG_BUTTON_ON_PRODUCTS             = 'payment/breadcheckout/button_on_products';
    const XML_CONFIG_BUTTON_DESIGN                  = 'payment/breadcheckout/button_design';
    const XML_CONFIG_API_MODE                       = 'payment/breadcheckout/api_mode';
    const XML_CONFIG_DEFAULT_BUTTON_SIZE            = 'payment/breadcheckout/use_default_button_size';
    const XML_CONFIG_CREATE_CUSTOMER                = 'payment/breadcheckout/bread_advanced/create_customer_account';
    const XML_CONFIG_ALLOW_CHECKOUT_PRODUCT_VIEW    = 'payment/breadcheckout/allowcheckoutpdp';
    const XML_CONFIG_ALLOW_CHECKOUT_CART            = 'payment/breadcheckout/allowcheckoutcart';
    const XML_CONFIG_EMBEDDED_CHECKOUT              = 'payment/breadcheckout/embedded';
    const XML_CONFIG_PRODUCT_TYPE_MSG               = 'payment/breadcheckout/product_type_msg';
    const XML_CONFIG_ORDER_SHIPPED                  = 'payment/breadcheckout/bread_advanced/order_shipped';
    const XML_CONFIG_THRESHOLD_AMOUNT               = 'payment/breadcheckout/bread_advanced/threshold_amount';
    const XML_CONFIG_AUTO_CANCEL                    = 'payment/breadcheckout/split_auto_cancel';

    const XML_CONFIG_ENABLE_TARGETED_FINANCING      = 'payment/breadcheckout/bread_advanced/targeted_financing';
    const XML_CONFIG_TARGETED_FINANCING_ID          = 'payment/breadcheckout/bread_advanced/financing_program_id';
    const XML_CONFIG_FINANCING_THRESHOLD            = 'payment/breadcheckout/bread_advanced/financing_threshold';
    const XML_CONFIG_FINANCING_SKU                  = 'payment/breadcheckout/bread_advanced/financing_sku';
    const XML_CONFIG_DISABLED_FOR_SKUS              = 'payment/breadcheckout/bread_advanced/disabled_skus';

    const XML_CONFIG_CATEGORY_GROUP                 = 'payment/breadcheckout/bread_category';
    const XML_CONFIG_CAT_AS_LOW_AS                  = 'payment/breadcheckout/bread_category/as_low_as';
    const XML_CONFIG_CAT_LABEL_ONLY                 = 'payment/breadcheckout/bread_category/label_only';
    const XML_CONFIG_CAT_BUTTON_DESIGN              = 'payment/breadcheckout/bread_category/button_design';
    const XML_CONFIG_CAT_WINDOW                     = 'payment/breadcheckout/bread_category/display_new_window';
    const XML_CONFIG_DEFAULT_BS_CAT                 = 'payment/breadcheckout/bread_category/use_default_button_size';
    const XML_CONFIG_SELECT_CATEGORIES              = 'payment/breadcheckout/bread_category/categories';

    const XML_CONFIG_CART_BUTTON_DESIGN             = 'payment/breadcheckout/bread_cartpage/button_design';
    const XML_CONFIG_PRODUCT_VIEW_BUTTON_DESIGN     = 'payment/breadcheckout/bread_productdetail/button_design';

    const XML_SENTRY_LOG_ENABLED                    = 'payment/breadcheckout/bread_advanced/sentry_enabled';

    const BLOCK_CODE_PRODUCT_VIEW                   = 'product_view';
    const BLOCK_CODE_CHECKOUT_OVERVIEW              = 'checkout_overview';

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var bool
     */
    private $isSecure;


    public function __construct(
        \Magento\Framework\App\Helper\Context $helperContext,
        Context $context,
        Http $request,
        UrlInterface $urlInterface,
        EncryptorInterface $encryptor
    ) {
        $this->context      = $context;
        $this->encryptor    = $encryptor;
        $this->isSecure     = $request->isSecure();
        parent::__construct(
            $helperContext
        );
    }

    /**
     * Is module active
     *
     * @param string $store
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getConfigData('active', true);
    }

    /**
     * Check product type against allowed product type list
     *
     * @param  string $typeId
     * @return bool
     */
    public function allowedProductType($typeId): bool
    {
        $allowedProductTypes = [
            Type::TYPE_SIMPLE,
            Type::TYPE_BUNDLE,
            Type::TYPE_VIRTUAL,
            Configurable::TYPE_CODE,
            Downloadable::TYPE_DOWNLOADABLE,
            Grouped::TYPE_CODE
        ];

        return in_array($typeId, $allowedProductTypes);
    }

    /**
     * Retrieve information from method configuration
     *
     * @param $field
     * @param bool $returnFlag
     * @return bool|string
     */
    public function getConfigData($field ,$returnFlag = false)
    {
        $path = 'payment/breadcheckout/' . $field;

        if($returnFlag){
            $value = $this->scopeConfig->isSetFlag(
                $path,
                ScopeInterface::SCOPE_STORE
            );
        } else {
            $value = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE
            );
        }

        return $value;
    }

    /**
     * Wrapper for url build method
     * @todo REPLACE ALL PREVIOUSLY USED WRAPPERS WITH THIS ONE!!!
     *
     * @param string $url
     * @return string
     */
    public function createUrl($url): string
    {
        return $this->_urlBuilder->getUrl($url, ['_secure'=> $this->isSecure]);
    }

    /**
     * Return custom message for non supported product type
     * @TODO REMOVED!!!
     *
     * @param  string $store
     * @return string
     */
    /*    public function getProductTypeMessage($store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE): string
        {
            return $this->scopeConfig->getValue(self::XML_CONFIG_PRODUCT_TYPE_MSG, $store);
        }*/

    /**
     * Get API Pub Key
     *
     * @return string
     */
    public function getApiPublicKey(): string
    {
        if ($this->getConfigData('api_mode',true)) {
            $output = $this->getConfigData('api_public_key');
        } else {
            $output = $this->getConfigData('api_sandbox_public_key');
        }

        return $output;
    }

    /**
     * Get API Secret Key
     *
     * @return string
     */
    public function getApiSecretKey(): string
    {
        if ($this->getConfigData('api_mode',true)) {
            $output = $this->encryptor->decrypt(
                $this->getConfigData('api_secret_key')
            );
        } else {
            $output = $this->encryptor->decrypt(
                $this->getConfigData('api_sandbox_secret_key')
            );
        }

        return $output;
    }

    /**
     * Get JS Lib Location
     *
     * @return string
     */
    public function getJsLibLocation(): string
    {
        if ($this->getConfigData('api_mode', true)) {
            return self::JS_LIVE_URI;
        } else {
            return self::JS_SANDBOX_URI;
        }
    }

    /**
     * Get API Url
     *
     * @return string
     */
    public function getTransactionApiEndpoint(): string
    {
        if ($this->getConfigData('api_mode', true)) {
            return self::API_LIVE_URI;
        } else {
            return self::API_SANDBOX_URI;
        }
    }

    /**
     * Get URL for controller which clears quote
     * after shopping cart rules pre calculation
     * @todo this is not used, some alternative needs to be figured out!
     *
     * @return string
     */
    public function getClearQuoteUrl()
    {
        return $this->_urlBuilder->getUrl(self::URL_CLEAR_QUOTE, ['_secure'=> $this->isSecure]);
    }

    /**
     * Payment Method Title During Checkout
     * @todo FIX THIS NOT BEING USED!!!
     *
     * @param  null $store
     * @return string
     */
    public function getPaymentMethodTitle($store)
    {
        return __($this->scopeConfig->getValue(self::XML_CONFIG_CHECKOUT_TITLE, $store));
    }

    /**
     * Show per month calculation next to payment method title on checkout
     * @todo check what exactly is this
     *
     * @param string $store
     * @return bool
     */
    public function showPerMonthCalculation($store): string
    {
        return $this->scopeConfig->getValue(self::XML_CONFIG_CHECKOUT_PER_MONTH, $store);
    }

    /**
     * Is Customer Account Created During Bread Work Flow?
     * @todo REMOVE AND REPLACE BY CHECK FOR GUEST CHECKOUT ALLOWED
     *
     * @param  null $store
     * @return bool
     */
    public function isAutoCreateCustomerAccountEnabled($store)
    {
        return (bool) ($this->isActive($store)
            && $this->scopeConfig->getValue(self::XML_CONFIG_CREATE_CUSTOMER, $store));
    }


    /**
     * Use Bread As Payment Method In Checkout?
     * @todo IF ENABLED IT SHOULD BE ACTIVE ON CHECKOUT BY DEFAULT || OR NOT :D
     *
     * @param  null $store
     * @return bool
     */
    public function isPaymentMethodAtCheckout($store): bool
    {
        return $this->scopeConfig->getValue(self::XML_CONFIG_ENABLE_AS_PAYMENT_METHOD, $store);
    }

    /**
     * Allow checkout from popup on product view page
     *
     * @return bool
     */
    public function isCheckoutAllowedProductView()
    {
        return !!$this->getConfigData('healthcare_mode',true)
            && $this->getConfigData('allowcheckoutpdp');
    }

    /**
     * Allow checkout from popup on cart view page
     *
     * @return bool
     */
    public function getAllowCheckoutCartView()
    {
        return !$this->getConfigData('healthcare_mode',true)
            && $this->getConfigData('allowcheckoutcart');
    }

    /**
     * Get cart size over which targeted financing is enabled
     *
     * @return float
     */
    public function getTargetedFinancingThreshold(): float
    {
        return round($this->getConfigData('bread_advanced/financing_threshold'), 2);
    }

    /**
     * Return list of SKU's for which financing is enabled
     *
     * @return array
     */
    public function getTargetedFinancingSkus(): array
    {
        $list = $this->getConfigData('financing_sku');
        $list = preg_replace('/\s/', '', $list);

        return explode(',', $list);
    }

    /**
     * Return financing mode
     *
     * @param string $mode
     * @return bool
     */
    public function checkFinancingMode($mode): bool
    {
        $configVal = (int)$this->getConfigData('targeted_financing');
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
     * Get cart size financing configuration
     *
     * @return array
     */
    public function getFinancingData(): array
    {
        return [
            'enabled' => $this->getConfigData('bread_advanced/targeted_financing', true),
            'mode' => ['cart'=>$this->checkFinancingMode('cart'), 'sku'=>$this->checkFinancingMode('sku')],
            'id' => $this->getConfigData('bread_advanced/financing_program_id'),
            'threshold' => $this->getTargetedFinancingThreshold(),
            'sku_limit' => $this->getTargetedFinancingSkus()
        ];
    }

    /**
     * Check if called from admin
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isInAdmin(): bool
    {
        return (\Magento\Backend\App\Area\FrontNameResolver::AREA_CODE
            === $this->context->getAppState()->getAreaCode());
    }

    /**
     * Check if cost of item/cart total is equal or greater than set threshold amount
     *
     * @param float $cost
     * @return bool
     */
    public function aboveThreshold($cost): bool
    {
        $aboveThreshold = true;
        $thresholdAmount = (int)$this->scopeConfig->getValue(self::XML_CONFIG_THRESHOLD_AMOUNT);

        if ($thresholdAmount) {
            $aboveThreshold = (int)$cost >= $thresholdAmount;
        }

        return $aboveThreshold;
    }

}