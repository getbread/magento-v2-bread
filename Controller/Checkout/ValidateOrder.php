<?php
/**
 * Create Magento Order From Bread Pop Up Order
 *
 * @author  Bread   copyright 2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout;

class ValidateOrder extends \Bread\BreadCheckout\Controller\Checkout
{
    /** @var \Bread\BreadCheckout\Model\Payment\Api\Client */
    protected $paymentApiClient;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var \Magento\Checkout\Helper\Cart */
    protected $cartHelper;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    /** @var \Magento\Framework\Controller\Result\RedirectFactory */
    protected $resultRedirectFactory;

    /** @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /** @var \Magento\Quote\Model\QuoteManagement */
    protected $quoteManagement;

    /** @var \Magento\Catalog\Model\ProductFactory */
    protected $catalogProductFactory;

    /** @var \Magento\Directory\Model\RegionFactory */
    protected $regionFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Bread\BreadCheckout\Helper\Data */
    protected $helper;

    /** @var \Bread\BreadCheckout\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Sales\Model\Order\Email\Sender\OrderSender */
    protected $orderSender;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Bread\BreadCheckout\Helper\Data $helper,
        \Bread\BreadCheckout\Helper\Customer $customerHelper,
        \Magento\Catalog\Model\ResourceModel\ProductFactory $catalogResourceModelProductFactory,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
    )
    {
        $this->paymentApiClient = $paymentApiClient;
        $this->checkoutSession = $checkoutSession;
        $this->cartHelper = $cartHelper;
        $this->logger = $logger;
        $this->messageManager = $context->getMessageManager();
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->customerSession = $customerSession;
        $this->quoteManagement = $quoteManagement;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->regionFactory = $regionFactory;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->customerHelper = $customerHelper;
        $this->orderSender = $orderSender;
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
     * Validate and process the order
     * 
     * @return void|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            $token = $this->getRequest()->getParam('token');
            if ($token) {
                $this->helper->log([
                    "VALIDATE ORDER TOKEN" => $token,
                ]);
                $data = $this->paymentApiClient->getInfo($token);
                $this->processOrder($data);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addError(__("Checkout With Financing On Product Page Error, Please Contact Store Owner. You may checkout by adding to cart and providing a payment in the checkout process."));

            $resultRedirect = $this->resultRedirectFactory
                ->create()
                ->setUrl($this->_redirect->getRefererUrl());

            return $resultRedirect;
        }
    }

    /**
     * Process Order Placed From Bread Pop Up
     *
     * @param $data
     * @throws \Exception
     */
    protected function processOrder($data)
    {
        $this->helper->log(["PROCESS ORDER DATA" => $data]);

        $quote = $this->checkoutSession->getQuote();
        /** @var $quote \Magento\Quote\Model\Quote */

        $storeId = $this->storeManager->getStore()->getId();
        $quote->setStoreId($storeId);

        if ($this->customerSession->isLoggedIn()) {
            $customerId = $this->customerSession->getCustomerId();
            $quote->setCustomerId($customerId);
        } else {
            $quote->setCustomerEmail($data['billingContact']['email']);
        }

        $this->checkoutSession->setBreadTransactionId($data['breadTransactionId']);

        if (isset($data['discounts']) && count($data['discounts']) > 0) {
            $discountDescription = $data['discounts'][0]['description'];
            $quote->setCouponCode(substr($discountDescription, 10, strlen($discountDescription) - 11));
        }

        $billingContact = $this->processAddress($data['billingContact']);
        $shippingContact = $this->processAddress($data['shippingContact']);

        if (!isset($shippingContact['email'])) {
            $shippingContact['email'] = $billingContact['email'];
        }

        if ($billingContact['city'] == null) {
            $billingContact['city'] = $shippingContact['city'];
            $billingContact['region_id'] = $shippingContact['region_id'];
        }

        $this->helper->log(["SHIPPING CONTACT" => $shippingContact, "BILLING CONTACT" => $billingContact]);

        $billingAddress = $quote->getBillingAddress()->addData($billingContact);
        $shippingAddress = $quote->getShippingAddress()->addData($shippingContact)->setCollectShippingRates(true);

        if (!isset($data['shippingMethodCode'])) {
            $this->helper->log("Shipping Method Code Is Not Set On The Response");
        }

        $shippingAddress->setShippingMethod($data['shippingMethodCode']);

        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }

        if (!$quote->getPayment()->getQuote()) {
            $quote->getPayment()->setQuote($quote);
        }
        $quote->getPayment()->setMethod('breadcheckout');

        $customer = $this->customerHelper->createCustomer($quote, $billingContact, $shippingContact);

        $quote->setTotalsCollectedFlag(false)->collectTotals()->save();

        $quote->getPayment()->importData(['method' => 'breadcheckout']);
        $quote->getPayment()->setTransactionId($data['breadTransactionId']);
        $quote->getPayment()->setAdditionalData("BREAD CHECKOUT DATA", json_encode($data));

        try {
            $this->quoteRepository->save($quote);
            $order = $this->quoteManagement->submit($quote);
        } catch (\Exception $e) {
            $this->helper->log(["ERROR SUBMITTING QUOTE IN PROCESS ORDER" => $e->getMessage()]);
            $this->logger->critical($e);
            throw $e;
        }

        $this->checkoutSession
            ->setLastQuoteId($quote->getId())
            ->setLastSuccessQuoteId($quote->getId())
            ->clearHelperData();

        try {
            $this->orderSender->send($order);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        if (!$this->customerSession->isLoggedIn()) {
            $this->customerSession->setCustomerAsLoggedIn($customer);
        }

        $this->checkoutSession->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus())
            ->setBreadItemAddedToQuote(false);

        // Empty shopping cart
        $cart = $this->cartHelper->getCart();
        $cart->truncate()->save();
        $cartItems = $cart->getItems();
        foreach ($cartItems as $item) {
            $quote->removeItem($item->getId())->save();
        }

        $this->_redirect('checkout/onepage/success');
    }

    /**
     * Format Address Data
     *
     * @param array $contactData
     * @return array
     */
    protected function processAddress($contactData)
    {
        $regionId   = null;
        if( isset($contactData['state']) ) {
            $region     = $this->regionFactory->create();      /** @var \Magento\Directory\Model\RegionFactory */
            $region->loadByCode($contactData['state'], $this->helper->getDefaultCountry());
            if ($region->getId()) {
                $regionId   = $region->getId();
            }
        }

        $fullName       = isset($contactData['fullName']) ? explode(' ', $contactData['fullName']) : '';
        $addressData    = [
            'firstname'     => isset($contactData['firstName']) ? $contactData['firstName'] : $fullName[0],
            'lastname'      => isset($contactData['lastName']) ? $contactData['lastName'] : (isset($fullName[1]) ? $fullName[1] : ''),
            'street'        => $contactData['address'] . (isset($contactData['address2']) ? (' ' .  $contactData['address2']) : ''),
            'city'          => $contactData['city'],
            'postcode'      => $contactData['zip'],
            'telephone'     => $contactData['phone'],
            'country_id'    => $this->helper->getDefaultCountry()
        ];

        if( null !== $regionId ) {
            $addressData['region']      = $contactData['state'];
            $addressData['region_id']   = $regionId;
        }

        if( isset($contactData['email']) ) {
            $addressData['email']   = $contactData['email'];
        }

        return $addressData;
    }
}