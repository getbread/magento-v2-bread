<?php
/**
 * Add Token To Session Once Approved
 *
 * @author  Bread   copyright 2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout\Validate;

class PaymentMethod extends \Bread\BreadCheckout\Controller\Checkout
{
    /** @var \Bread\BreadCheckout\Model\Payment\Api\Client */
    protected $paymentApiClient;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    )
    {
        $this->paymentApiClient = $paymentApiClient;
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        try {
            $token = $this->getRequest()->getParam('token');
            if ($token) {
                $data = $this->paymentApiClient->getInfo($token);
                if ($data->breadTransactionId) {
                    $this->checkoutSession
                        ->getQuote()
                        ->setBreadTransactionId($token)
                        ->save();
                }
            }

            $result = true;
        } catch (\Exception $e) {
            $result = false;
        }

        return $this->resultJsonFactory->create()->setData(['result' => $result]);
    }
}