<?php
/**
 * Config provider for the payment method
 *
 * @author  Bread   copyright   2016
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Model\Ui;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'breadcheckout';

    /** @var \Bread\BreadCheckout\Helper\Quote */
    protected $helper;

    public function __construct(
        \Bread\BreadCheckout\Helper\Quote $helper
    ) {
        $this->helper = $helper;
    }

    /**
    * Retrieve assoc array of checkout configuration;
    * populates window.checkoutConfig.payment variable
    *
    * @return array
    */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'active' => $this->helper->isPaymentMethodAtCheckout(),
                    'defaultSize' => $this->helper->useDefaultButtonSize(),
                    'buttonCss' => $this->helper->getButtonDesign(),
                    'configDataUrl' => $this->helper->getConfigDataUrl(),
                    'transactionId' => $this->helper->getBreadTransactionId(),
                    'validateTotalsUrl' => $this->helper->getValidateTotalsUrl(),
                    'isHealthcare' => $this->helper->isHealthcare(),
                    'breadConfig' => [
                        'buttonId' => 'bread-checkout-btn',
                        'blockCode' => \Bread\BreadCheckout\Helper\Data::BLOCK_CODE_CHECKOUT_OVERVIEW,
                        'items' => $this->helper->getQuoteItemsData(),
                        'discounts' => $this->helper->getDiscountData(),
                        'asLowAs' => $this->helper->isAsLowAs(),
                        'paymentUrl' => $this->helper->getPaymentUrl(),
                        'validateOrderUrl' => $this->helper->getValidateOrderURL(),
                        'additionalData' => '',
                        'taxEstimationUrl' => $this->helper->getTaxEstimateUrl(),
                        'shippingEstimationUrl' => $this->helper->getShippingEstimateUrl()
                    ]
                ]
            ]
        ];
    }
}
