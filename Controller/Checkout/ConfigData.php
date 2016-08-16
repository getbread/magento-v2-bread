<?php
/**
 * Get updated shipping, billing, and shipping option data
 *
 * @author  Bread   copyright 2016
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout;

class ConfigData extends \Magento\Framework\App\Action\Action
{
    /** @var \Bread\BreadCheckout\Helper\Quote */
    protected $quoteHelper;

    /** @var \Bread\BreadCheckout\Helper\Customer */
    protected $customerHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Bread\BreadCheckout\Helper\Quote $quoteHelper,
        \Bread\BreadCheckout\Helper\Customer $customerHelper
    )
    {
        $this->resultFactory = $context->getResultFactory();
        $this->quoteHelper = $quoteHelper;
        $this->customerHelper = $customerHelper;
        parent::__construct($context);
    }

    /**
     * Sends shipping & billing data to setShippingInformation()
     * which is called when "Next" is clicked from the shipping
     * information view in checkout
     * 
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData([
            'shippingContact' => $this->quoteHelper->getShippingAddressData(),
            'billingContact' => ($this->customerHelper->isUserLoggedIn()) ?
                $this->customerHelper->getFormattedDefaultBillingAddress() :
                $this->quoteHelper->getBillingAddressData()
        ]);
    }
}