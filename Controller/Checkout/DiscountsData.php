<?php
/**
 * Get updated shipping, billing, and shipping option data
 *
 * @author Bread   copyright 2016
 * @author Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout;

class DiscountsData extends \Bread\BreadCheckout\Controller\Checkout
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
        \Magento\Catalog\Model\ResourceModel\ProductFactory $catalogResourceModelProductFactory,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Bread\BreadCheckout\Helper\Log $logger,
        \Bread\BreadCheckout\Helper\Checkout $helper,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Bread\BreadCheckout\Helper\Quote $quoteHelper,
        \Bread\BreadCheckout\Helper\Customer $customerHelper,
        \Bread\BreadCheckout\Helper\Data $dataHelper
    ) {
    
        $this->resultFactory = $context->getResultFactory();
        $this->quoteHelper = $quoteHelper;
        $this->customerHelper = $customerHelper;
        $this->dataHelper = $dataHelper;
        parent::__construct(
            $context,
            $catalogResourceModelProductFactory,
            $dataObjectFactory,
            $checkoutSession,
            $quoteFactory,
            $catalogProductFactory,
            $logger,
            $helper,
            $totalsCollector,
            $quoteRepository,
            $customerSession,
            $quoteManagement
        );
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
        $params = $this->getRequest()->getParams();
        
        $this->getQuote($params);
        $discounts = $this->quoteHelper->getDiscountData();
        
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData(
            [
            'discounts' =>  ($discounts)? $discounts : []
            ]
        );
    }
}
