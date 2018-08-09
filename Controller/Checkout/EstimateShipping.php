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
    public $resultFactory;

    /** @var \Magento\Framework\Message\ManagerInterface */
    public $messageManager;

    /** @var \Psr\Log\LoggerInterface */
    public $logger;

    /** @var \Bread\BreadCheckout\Helper\Data */
    public $helper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\ResourceModel\ProductFactory $catalogResourceModelProductFactory,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Psr\Log\LoggerInterface $logger,
        \Bread\BreadCheckout\Helper\Checkout $helper,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Quote\Model\QuoteManagement $quoteManagement
    ) {
    
        $this->resultFactory = $context->getResultFactory();
        $this->messageManager = $context->getMessageManager();
        $this->logger = $logger;
        $this->helper = $helper;
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
     * Get available shipping options and prices for address
     * supplied through Bread checkout popup
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        try {
            $address    = $this->getShippingAddressForQuote($this->getRequest()->getParams());

            if (!$address instanceof \Magento\Quote\Model\Quote\Address) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    'Shipping address is not an instance of Magento\Quote\Model\Quote\Address'
                );
            }

            $address->collectShippingRates()->save();
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
                        'type'   => $rate->getCarrierTitle() . ' - ' . $rate->getMethodTitle(),
                        'typeId' => $rate->getCode(),
                        'cost'   => $rate->getPrice() * 100,
                    ];
                }
            }
            $response = $methods;
        } catch (\Exception $e) {
            $this->helper->log(["ERROR" => $e->getMessage(),
                                "PARAMS"=> $this->getRequest()->getParams()]);
            $this->logger->critical($e);
            $this->messageManager->addError(
                __(
                    "Internal Error, Please Contact Store Owner. You may checkout by adding to cart 
                    and providing a payment in the checkout process."
                )
            );
            $response = ['error' => 1,
                         'text'  => 'Internal error'];
        }

        return $this->resultFactory
                    ->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
                    ->setData(['result' => $response]);
    }
}
