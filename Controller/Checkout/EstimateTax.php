<?php
/**
 * Get Tax Estimate
 *
 * @author  Bread   copyright 2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout;

class EstimateTax extends \Bread\BreadCheckout\Controller\Checkout
{
    /** @var \Magento\Framework\Controller\ResultFactory  */
    protected $resultFactory;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Bread\BreadCheckout\Helper\Data */
    protected $helper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\ResourceModel\ProductFactory $catalogResourceModelProductFactory,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Psr\Log\LoggerInterface $logger,
        \Bread\BreadCheckout\Helper\Data $helper,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Model\QuoteManagement $quoteManagement
    )
    {
        $this->resultFactory = $context->getResultFactory();
        $this->logger = $logger;
        $this->helper = $helper;
        parent::__construct($context,
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
            $quoteManagement);
    }

    /**
     * Get tax amount for quote
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $this->helper->log( ["TAX ESTIMATE ACTION GET PARAMS" => $this->getRequest()->getParams()] );
        $data       = json_decode($this->getRequest()->getParams()['shippingInfo'], true);
        try {
            $shippingAddress    = $this->getShippingAddressForQuote($data);
            if (!$shippingAddress instanceof \Magento\Quote\Model\Quote\Address) {
                throw new \Exception('Shipping address is not an instance of Magento\Quote\Model\Quote\Address');
            }
            
            $result             = $shippingAddress->getTaxAmount() * 100;
            $response           = $result;
        } catch (\Exception $e) {
            $this->helper->log("EXCEPTION IN TAX ESTIMATE ACTION", 'bread-exception.log');
            $this->logger->critical($e);
            $response = ['error' => 1,
                         'text'  => 'Internal error'];
        }
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData(['result' => $response]);
    }
}