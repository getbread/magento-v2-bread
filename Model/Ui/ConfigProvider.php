<?php
/**
 * Class ConfigProvider
 * Config provider for the payment method
 */

namespace Bread\BreadCheckout\Model\Ui;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'breadcheckout';

    /** @var \Bread\BreadCheckout\helper\Product\View */
    protected $helper;

    public function __construct(
        \Bread\BreadCheckout\Helper\Quote $helper
    ) {
        $this->helper = $helper;
    }

    /**
    * Retrieve assoc array of checkout configuration
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
                    'breadConfig' => [
                        'buttonId' => 'bread-checkout-btn',
                        'items' => $this->helper->getQuoteItemsData(),
                        'discounts' => $this->helper->getDiscountData(),
                        'shippingOptions' => $this->helper->getShippingOptions(),
                        'grandTotal' => $this->helper->getGrandTotal(),
                        'asLowAs' => $this->helper->isAsLowAs(),
                        'shippingContact' => $this->helper->getShippingAddressData(),
                        'billingContact' => $this->helper->getBillingAddressData(),
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
