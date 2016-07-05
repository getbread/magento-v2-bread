<?php
/**
 * Get Tax Estimate
 *
 * @author  Bread   copyright 2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout\Estimate;

class Tax extends \Bread\BreadCheckout\Controller\Checkout
{
    /** @var \Magento\Framework\Controller\Result\JsonFactory  */
    protected $resultJsonFactory;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Bread\BreadCheckout\Helper\Data */
    protected $helper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Psr\Log\LoggerInterface $logger,
        \Bread\BreadCheckout\Helper\Data $helper
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->helper->log( ["TAX ESTIMATE ACTION GET PARAMS" => $this->getRequest()->getParams()] );
        $data       = json_decode($this->getRequest()->getParams()['shippingInfo'], true);
        try {
            $shippingAddress    = $this->getShippingAddressForQuote($data);
            $result             = $shippingAddress->getTaxAmount();
            $response           = $result;
        } catch (\Exception $e) {
            $this->helper->log("EXCEPTION IN TAX ESTIMATE ACTION", 'bread-exception.log');
            $this->logger->critical($e);
            $response = ['error' => 1,
                         'text'  => 'Internal error'];
        }
        return $this->resultJsonFactory->create()->setData(['result' => $response]);
    }
}