<?php
/**
 * Get Shipping Method Prices
 *
 * @author  Bread   copyright 2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout;

class ConfigData extends \Magento\Framework\App\Action\Action
{
    /** @var \Bread\BreadCheckout\Helper\Quote */
    protected $helper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Bread\BreadCheckout\Helper\Quote $helper
    )
    {
        $this->resultFactory = $context->getResultFactory();
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData([
            'shippingContact' => $this->helper->getShippingAddressData(),
            'billingContact' => $this->helper->getBillingAddressData(),
            'shippingOptions' => $this->helper->getShippingOptions()
        ]);
    }
}