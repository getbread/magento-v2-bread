<?php
/**
 * Validate Payment Method In Admin
 *
 * @author Bread   copyright 2016
 * @author Joel    @Mediotype
 * @author Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Adminhtml\Bread;

class ValidatePaymentMethod extends \Magento\Backend\App\Action
{
    /**
     * @var \Bread\BreadCheckout\Model\Payment\Api\Client
     */
    public $paymentApiClient;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    public $resultJsonFactory;

    /**
     * @var \Bread\BreadCheckout\Helper\Log
     */
    public $logger;

    /**
     * @var \Bread\BreadCheckout\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Sales\Model\AdminOrder\Create
     */
    public $orderCreateModel;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Bread\BreadCheckout\Helper\Log $log,
        \Bread\BreadCheckout\Helper\Data $helper,
        \Magento\Sales\Model\AdminOrder\Create $orderCreateModel
    ) {
        $this->paymentApiClient = $paymentApiClient;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $log;
        $this->helper = $helper;
        $this->orderCreateModel = $orderCreateModel;
        parent::__construct($context);
    }

    /**
     * Save bread transaction ID to quote session
     *
     * @return \Magento\Framework\Controller\Result\Json
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $result = false;

        try {
            $token = $this->getRequest()->getParam('token');
            if ($token) {
                $data   = $this->paymentApiClient->getInfo($token);
                $breadVersion = $this->helper->getApiVersion();
                $trxId = null;
                
                if($breadVersion === 'bread_2') {
                    if(isset($data['transactionID'])) {
                        $trxId = $data['transactionID'];
                    }
                } else {
                    if (isset($data['breadTransactionId'])) {
                        $trxId = $data['breadTransactionId'];
                    }
                }     
                
                if(!is_null($trxId)) {
                    $this->orderCreateModel
                            ->getSession()
                            ->setBreadTransactionId($trxId);
                    $result = true;
                }                                                      
            }
            $response = ['result' => $result];
        } catch (\Throwable $e) {
            $this->logger->log(['EXCEPTION IN VALIDATE PAYMENT IN ADMIN CONTROLLER'=>$e->getMessage()]);

            $response = ['error' => 'Something went wrong processing the Bread payment. '
                . 'Please select a different payment method to complete checkout.'];
        }

        return $this->resultJsonFactory->create()->setData($response);
    }
}
