<?php
/**
 * Get updated shipping, billing, and shipping option data
 *
 * @author Bread   copyright 2016
 * @author Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout;

class ClearQuote extends \Bread\BreadCheckout\Controller\Checkout
{
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
        \Magento\Quote\Model\QuoteManagement $quoteManagement
    ) {
    
        $this->resultFactory = $context->getResultFactory();
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
        $quote = $this->checkoutSession->getQuote();
        
        try {
            $this->customerSession->setBreadItemAddedToQuote(false);
            $quote->removeAllItems(); // Reset items in quote
            $this->quoteRepository->save($quote);
            $result = true;
        } catch (\Throwable $e) {
            $this->logger->log(['MESSAGE' => $e->getMessage(), 'TRACE' => $e->getTraceAsString()]);
            $result = false;
        }
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData(
            [
            'result' =>  $result
            ]
        );
    }
}
