<?php
/**
 * Validate Payment Method In Admin
 *
 * @author  Bread   copyright 2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Adminhtml\Validate;

class PaymentMethod extends \Magento\Backend\App\Action
{
    /** @var \Bread\BreadCheckout\Model\Payment\Api\Client */
    protected $paymentApiClient;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Backend\Model\Session\Quote */
    protected $backendSessionQuote;

    /** @var \Magento\Checkout\Model\Cart */
    protected $cart;

    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Bread\BreadCheckout\Helper\Data */
    protected $helper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Model\Session\Quote $backendSessionQuote,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Psr\Log\LoggerInterface $logger,
        \Bread\BreadCheckout\Helper\Data $helper
    ) {
        $this->paymentApiClient = $paymentApiClient;
        $this->storeManager = $storeManager;
        $this->backendSessionQuote = $backendSessionQuote;
        $this->cart = $cart;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
		$result     = false;

        try {
            $token      = $this->getRequest()->getParam('token');
            if ($token) {
                $data   = $this->paymentApiClient->getInfo($token);
                if (isset($data['breadTransactionId'])) {

                    if ($this->storeManager->getStore()->isAdmin()) {
                        $quote      = $this->backendSessionQuote->getQuote();
                    } else {
                        $quote      = $this->cart->getQuote();
                    }

                    $quote->setBreadTransactionId($data['breadTransactionId'])->save();
                    $result     = true;
                }
            }
        } catch (\Exception $e) {
            $this->helper->log(['EXCEPTION IN VALIDATE PAYMENT IN ADMIN CONTROLLER'=>$e->getMessage()], 'bread-exception.log');
	        $this->logger->critical($e);
	        throw new \Magento\Framework\Exception\LocalizedException($e);
        }

        return $this->resultJsonFactory->create()->setData( ['result' => $result] );
    }
}