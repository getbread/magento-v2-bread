<?php
namespace Bread\BreadCheckout\Controller\Checkout;

class LandingPage extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    public $request;

    /**
     * @var \Bread\BreadCheckout\Model\Payment\Api\Client
     */
    public $paymentApiClient;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    public $customer;

    /**
     * @var \Magento\Customer\Model\Session
     */
    public $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    public $checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    public $quoteRepository;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    public $quoteManagement;

    /**
     * @var \Bread\BreadCheckout\Helper\Checkout
     */
    public $helper;

    /**
     * @var \Bread\BreadCheckout\Helper\Log
     */
    public $logger;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    public $customerFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    public $quoteFactory;

    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    public $cartHelper;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    public $orderSender;

    /**
     * @var \Bread\BreadCheckout\Helper\Quote
     */
    public $quoteHelper;

    /**
     * @var \Bread\BreadCheckout\Helper\Customer
     */
    public $customerHelper;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Bread\BreadCheckout\Helper\Checkout $helper,
        \Bread\BreadCheckout\Helper\Log $logger,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\App\Action\Context $context,
        \Bread\BreadCheckout\Helper\Quote $quoteHelper,
        \Bread\BreadCheckout\Helper\Customer $customerHelper
    ) {
    
        $this->request          = $request;
        $this->paymentApiClient = $paymentApiClient;
        $this->customer         = $customer;
        $this->customerSession  = $customerSession;
        $this->checkoutSession  = $checkoutSession;
        $this->quoteRepository  = $quoteRepository;
        $this->quoteManagement  = $quoteManagement;
        $this->helper           = $helper;
        $this->logger           = $logger;
        $this->customerFactory  = $customerFactory;
        $this->storeManager     = $storeManager;
        $this->quoteFactory     = $quoteFactory;
        $this->cartHelper       = $cartHelper;
        $this->orderSender      = $orderSender;
        $this->resultFactory    = $context->getResultFactory();
        $this->quoteHelper      = $quoteHelper;
        $this->customerHelper   = $customerHelper;
        parent::__construct($context);
    }

    /**
     * Convert cart to order
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $transactionId = $this->request->getParam("transactionId");
        $orderRef = $this->request->getParam("orderRef");

        if ($transactionId && $orderRef && !$this->request->getParam("error")) {
            $this->validateBackendOrder($transactionId, $orderRef);
        } else {
            $this->_redirect("/");
        }
    }

    /**
     * Create Magento Order From Backend Quote
     */
    public function validateBackendOrder($transactionId, $orderRef)
    {
        try {
            if ($transactionId) {
                $data       = $this->paymentApiClient->getInfo($transactionId);

                $customer   = $this->customerFactory->create();

                $customer->setWebsiteId($this->storeManager->getWebsite()->getId());
                $customer->loadByEmail($data["billingContact"]["email"]);

                if ($customer->getId()) {
                    $this->customerSession->setCustomerAsLoggedIn($customer);
                }

                $this->processBackendOrder($orderRef, $data);

                $this->_redirect('checkout/onepage/success');
            }
        } catch (\Throwable $e) {
            $this->logger->log(['ERROR' => $e->getMessage(), 'TRACE' => $e->getTraceAsString()]);
            $this->customerHelper->sendCustomerErrorReportToMerchant($e, "", $orderRef, $transactionId);
            $this->messageManager->addErrorMessage(
                __('There was an error with your financing program. Notification was sent to merchant.')
            );
            $this->_redirect("/");
        }
    }

    /**
     * Process Order Placed From Bread Pop Up
     *
     * @param  $orderRef
     * @param  $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function processBackendOrder($orderRef, $data)
    {
        $quote = $this->quoteFactory->create()->loadByIdWithoutStore($orderRef);

        $billingAddress = $this->customerHelper->processAddress($data['billingContact']);
        $shippingAddress = $this->customerHelper->processAddress($data['shippingContact']);

        if (!isset($shippingAddress['email'])) {
            $shippingAddress['email'] = $billingAddress['email'];
        }

        $customer = $this->customerHelper->createCustomer($quote, $billingAddress, $shippingAddress, true);

        $this->checkoutSession->setBreadTransactionId($data['breadTransactionId']);

        if (!$quote->getPayment()->getQuote()) {
            $quote->getPayment()->setQuote($quote);
        }
        $quote->getPayment()->setMethod('breadcheckout');

        if (!$customer->getId()) {
            $quote->setCustomerIsGuest(true);
        }

        $quote->setTotalsCollectedFlag(false)->collectTotals()->save();

        $quote->getPayment()->importData(['method' => 'breadcheckout']);
        $quote->getPayment()->setTransactionId($data['breadTransactionId']);
        $quote->getPayment()->setAdditionalData("BREAD CHECKOUT DATA", json_encode($data));

        try {
            $order = $this->quoteManagement->submit($quote);
        } catch (\Throwable $e) {
            $this->logger->log(
                [
                'ERROR SUBMITTING QUOTE IN PROCESS ORDER' => $e->getMessage(),
                'TRACE' => $e->getTraceAsString()
                ]
            );
            throw $e;
        }

        $this->checkoutSession
            ->setLastQuoteId($quote->getId())
            ->setLastSuccessQuoteId($quote->getId())
            ->clearHelperData();

        try {
            $this->orderSender->send($order);
        } catch (\Throwable $e) {
            $this->logger->critical($e);
            $this->customerSession->setBreadItemAddedToQuote(false);
        }

        if ($customer->getId()) {
            $this->customerSession->setCustomer($customer);
            $this->customerSession->setCustomerAsLoggedIn($customer);
        }

        $this->checkoutSession->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());
        $this->customerSession->setBreadItemAddedToQuote(false);

        $cart = $this->cartHelper->getCart();
        $cart->truncate()->save();
        $cartItems = $cart->getItems();
        // @codingStandardsIgnoreStart
        foreach ($cartItems as $item) {
            $quote->removeItem($item->getId())->save();
        }
        // @codingStandardsIgnoreEnd

        $this->_redirect('checkout/onepage/success');
    }
}
