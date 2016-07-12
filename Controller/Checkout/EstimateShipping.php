<?php
/**
 * Get Shipping Method Prices
 *
 * @author  Bread   copyright 2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout;

class EstimateShipping extends \Bread\BreadCheckout\Controller\Checkout
{
    /** @var \Magento\Framework\Controller\ResultFactory */
    protected $resultFactory;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Bread\BreadCheckout\Helper\Data */
    protected $helper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\ResourceModel\ProductFactory $catalogResourceModelProductFactory,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Psr\Log\LoggerInterface $logger,
        \Bread\BreadCheckout\Helper\Data $helper,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    )
    {
        $this->resultFactory = $context->getResultFactory();
        $this->messageManager = $context->getMessageManager();
        $this->logger = $logger;
        $this->helper = $helper;
        parent::__construct($context,
            $catalogResourceModelProductFactory,
            $dataObjectFactory,
            $cart,
            $quoteFactory,
            $catalogProductFactory,
            $logger,
            $helper,
            $totalsCollector,
            $quoteRepository);
    }

    public function execute()
    {
        try {
            $address    = $this->getShippingAddressForQuote($this->getRequest()->getParams());

            if (!$address instanceof \Magento\Quote\Model\Quote\Address) {
                throw new \Exception('Shipping address is not an instance of Magento\Quote\Model\Quote\Address');
            }

            $data       = $address->getGroupedAllShippingRates();
            $methods    = [];
            $code       = [];
            foreach ($data as $method) {
                foreach ($method as $rate) {
                    if (array_key_exists($rate->getCode(), $code)) {
                        continue;
                    }
                    $code[$rate->getCode()] = true;
                    $methods[] = [
                        'type'   => $rate->getCarrierTitle(),
                        'typeId' => $rate->getCode(),
                        'cost'   => $rate->getPrice() * 100,
                    ];
                }
            }
            $response = $methods;
        } catch (\Exception $e) {
            $this->helper->log(["ERROR" => "Exception in shipping estimate action",
                                "PARAMS"=> $this->getRequest()->getParams()]);
            $this->logger->critical($e);
            $this->messageManager->addError( __("Internal Error, Please Contact Store Owner. You may checkout by adding to cart and providing a payment in the checkout process.") );
            $response = ['error' => 1,
                         'text'  => 'Internal error'];
        }

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData(['result' => $response]);
    }
}