<?php
/**
 * Get updated shipping, billing, and shipping option data
 *
 * @author Bread   copyright 2016
 * @author Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout;

class ConfigData extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Bread\BreadCheckout\Helper\Quote
     */
    public $quoteHelper;

    /**
     * @var \Bread\BreadCheckout\Helper\Customer
     */
    public $customerHelper;

    /**
     * @var \Bread\BreadCheckout\Helper\Data
     */
    public $dataHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Bread\BreadCheckout\Helper\Quote $quoteHelper,
        \Bread\BreadCheckout\Helper\Customer $customerHelper,
        \Bread\BreadCheckout\Helper\Data $dataHelper
    ) {
    
        $this->resultFactory = $context->getResultFactory();
        $this->quoteHelper = $quoteHelper;
        $this->customerHelper = $customerHelper;
        $this->dataHelper = $dataHelper;
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
        // @codingStandardsIgnoreStart
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData([
            'shippingContact' => $this->quoteHelper->getShippingAddressData(),
            'billingContact' => $this->getBillingAddressData()
        ]);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Get saved address from quote or customer default
     * billing address, if one exists
     *
     * @return array|bool
     */
    protected function getBillingAddressData()
    {
        if ($data = $this->quoteHelper->getBillingAddressData()) {
            return $data;
        }

        if (!$this->customerHelper->isUserLoggedIn()) {
            return false;
        }

        $data = $this->customerHelper->getFormattedDefaultBillingAddress();
        if (empty($data)) {
            return false;
        }

        return $data;
    }
}
