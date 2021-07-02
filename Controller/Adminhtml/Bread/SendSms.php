<?php

namespace Bread\BreadCheckout\Controller\Adminhtml\Bread;

class SendSms extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    public $request;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    public $resultFactory;

    /**
     * @var \Bread\BreadCheckout\Helper\Quote
     */
    public $helper;

    /**
     * @var \Bread\BreadCheckout\Model\Payment\Api\Client
     */
    public $paymentApiClient;

    /**
     * SendSms constructor.
     *
     * @param \Magento\Framework\App\Request\Http           $request
     * @param \Magento\Backend\App\Action\Context           $context
     * @param \Bread\BreadCheckout\Helper\Quote             $helper
     * @param \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Backend\App\Action\Context $context,
        \Bread\BreadCheckout\Helper\Quote $helper,
        \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient
    ) {

        $this->request = $request;
        $this->resultFactory = $context->getResultFactory();
        $this->helper = $helper;
        $this->paymentApiClient = $paymentApiClient;
        parent::__construct($context);
    }

    public function execute()
    {
        $quote  = $this->helper->getSessionQuote();
        $cartId = $this->getRequest()->getParam('id');
        $phone  = $quote->getShippingAddress()->getTelephone();

        try {
            $this->paymentApiClient->sendSms($cartId, $phone);
            $ret['successRows'][] = __('SMS was successfully sent to your customer.');
        } catch (\Throwable $e) {
            $ret['error'] = true;
            $ret['errorRows'][] = __('An error occurred while sending sms:');
            $ret['errorRows'][] = $e->getMessage();
        }
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData($ret);
    }
}
