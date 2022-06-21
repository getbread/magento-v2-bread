<?php

/**
 * Config provider for the payment method
 *
 * @author Bread   copyright
 * @author Miranda @Mediotype
 * @author Kip, Maritim @BreadFinancial
 */

namespace Bread\BreadCheckout\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface {

    const CODE = 'breadcheckout';

    /**
     * @var \Bread\BreadCheckout\Helper\Quote
     */
    public $helper;

    /**
     * @var \Bread\BreadCheckout\Helper\Data
     */
    public $helperData;

    /**
     * @var \Bread\BreadCheckout\Model\Payment\Method\Bread
     */
    public $breadMethod;

    /**
     *
     * @var \Bread\BreadCheckout\Helper\Catalog
     */
    public $catalog;

    /**
     * 
     * @param \Bread\BreadCheckout\Helper\Quote $helper
     * @param \Bread\BreadCheckout\Helper\Data $helperData
     * @param \Bread\BreadCheckout\Model\Payment\Method\Bread $breadMethod
     */
    public function __construct(
            \Bread\BreadCheckout\Helper\Quote $helper,
            \Bread\BreadCheckout\Helper\Data $helperData,
            \Bread\BreadCheckout\Model\Payment\Method\Bread $breadMethod,
            \Bread\BreadCheckout\Helper\Catalog $catalog
    ) {
        $this->helper = $helper;
        $this->helperData = $helperData;
        $this->breadMethod = $breadMethod;
        $this->catalog = $catalog;
    }

    /**
     * Retrieve assoc array of checkout configuration;
     * populates window.checkoutConfig.payment variable
     *
     * @return array
     */
    public function getConfig() {
        return [
            'payment' => [
                self::CODE => [
                    'active' => $this->helper->isPaymentMethodAtCheckout(),
                    'apiVersion' => $this->helper->getApiVersion(),
                    'integrationKey' => $this->helper->getIntegrationKey(),
                    'defaultSize' => $this->helper->useDefaultButtonSize(),
                    'buttonCss' => $this->helper->getButtonDesign(),
                    'configDataUrl' => $this->helper->getConfigDataUrl(),
                    'shippingOptionUrl' => $this->helper->getShippingOptionUrl(),
                    'transactionId' => $this->helper->getBreadTransactionId(),
                    'validateTotalsUrl' => $this->helper->getValidateTotalsUrl(),
                    'isHealthcare' => $this->helper->isHealthcare(),
                    'country' => $this->helper->getMerchantCountry(),
                    'client' => $this->helperData->getConfigClient(),
                    'breadConfig' => [
                        'buttonId' => 'bread-checkout-btn',
                        'formId' => 'bread-checkout-embedded',
                        'embeddedCheckout' => $this->helperData->embeddedCheckoutEnabled(),
                        'blockCode' => \Bread\BreadCheckout\Helper\Data::BLOCK_CODE_CHECKOUT_OVERVIEW,
                        'items' => $this->helper->getQuoteItemsData(),
                        'discounts' => $this->helper->getDiscountData(),
                        'targetedFinancingStatus' => $this->helper->getTargetedFinancingStatus(),
                        'asLowAs' => $this->helper->isAsLowAs(),
                        'paymentUrl' => $this->helper->getPaymentUrl(),
                        'validateOrderUrl' => $this->helper->getValidateOrderURL(),
                        'additionalData' => '',
                        'taxEstimationUrl' => $this->helper->getTaxEstimateUrl(),
                        'shippingEstimationUrl' => $this->helper->getShippingEstimateUrl(),
                        'shippingOptions' => $this->helper->getShippingOptions(),
                        'buttonLocation' => $this->helperData->getCheckoutLocation(),
                        'methodTooltip' => $this->helper->getMethodTooltip(),
                        'productTypeMessage' => $this->helperData->getProductTypeMessage(),
                        'cartValidation' => $this->helper->validateAllowedProductTypes(),
                        'methodTitle' => $this->breadMethod->getTitle(),
                        'showSplitpayLabel' => $this->helper->showSplitpayLabelOnCheckout(),
                        'currencyCode' => $this->catalog->getCurrentCurrencyCode()
                    ]
                ]
            ]
        ];
    }

}
