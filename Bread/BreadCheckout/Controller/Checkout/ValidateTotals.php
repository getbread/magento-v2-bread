<?php
/**
 * Validate Transaction Total with Quote Total
 *
 * @author  Bread   copyright 2016
 * @author  Dale    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout;

class ValidateTotals extends \Bread\BreadCheckout\Controller\Checkout
{
    /** @var \Magento\Framework\Json\EncoderInterface */
    protected $jsonEncoder;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\ResourceModel\ProductFactory $catalogResourceModelProductFactory,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Psr\Log\LoggerInterface $logger,
        \Bread\BreadCheckout\Helper\Checkout $helper,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder
    ) {
    
        $this->jsonEncoder = $jsonEncoder;

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
     * Check whether Bread total & Magento total match
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $result = ['valid' => false];

        if (isset($params['bread_transaction_id'])) {
            if ($this->helper->validateTransactionAmount($params['bread_transaction_id'])) {
                $result['valid'] = true;
            } else {
                $errorMsg = __("Your order total does not match the amount authorized by Bread.
                Please complete checkout again before placing the order.");
            }
        } else {
            $errorMsg = __("Please complete the Bread checkout form before placing the order.");
        }

        if (isset($errorMsg)) {
            $result['responseText'] = $this->jsonEncoder->encode(['message' => (string) $errorMsg]);
        }

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData($result);
    }
}
