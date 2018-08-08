<?php
namespace Bread\BreadCheckout\Controller\Adminhtml\Bread;

class SendMail extends \Magento\Backend\App\Action
{
    /** @var \Bread\BreadCheckout\Helper\Quote */
    public $request;
    public $helper;
    public $cart;
    public $config;
    public $paymentApiClient;
    public $customerHelper;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Backend\App\Action\Context $context,
        \Bread\BreadCheckout\Helper\Quote $helper,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient,
        \Bread\BreadCheckout\Helper\Customer $customerHelper
    ) {
    
        $this->request = $request;
        $this->resultFactory = $context->getResultFactory();
        $this->helper = $helper;
        $this->cart = $cart;
        $this->config = $scopeConfig;
        $this->paymentApiClient = $paymentApiClient;
        $this->customerHelper = $customerHelper;
        parent::__construct($context);
    }

    /**
     * Send confirmation email to customer
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $quote = $this->helper->getSessionQuote();

        $url = $this->request->getParam("url");

        $items = $this->helper->getQuoteItemsData();
        $ret = ["error"=>false,
                     "successRows"=> [],
                     "errorRows" => [],
        ];
        try {
            $this->customerHelper->sendCartActivationEmailToCustomer($quote->getCustomer(), $url, $items);
            $ret["successRows"][] = __("Email was successfully sent to your customer.");
        } catch (\Exception $e) {
            $ret["error"] = true;
            $ret["errorRows"][] = __("An error occurred while sending email:");
            $ret["errorRows"][] = $e->getMessage();
        }
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData($ret);
    }
}
