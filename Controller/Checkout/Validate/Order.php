<?php
/**
 * Create Magento Order From Bread Pop Up Order
 *
 * @author  Bread   copyright 2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout\Validate;

class Order extends \Bread\BreadCheckout\Controller\Checkout
{
    /** @var \Bread\BreadCheckout\Model\Payment\Api\Client */
    protected $paymentApiClient;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var \Bread\BreadCheckout\Model\Session */
    protected $breadCheckoutSession;

    /** @var \Magento\Checkout\Model\CartFactory */
    protected $checkoutCartFactory;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    /** @var \Magento\Framework\Controller\Result\RedirectFactory */
    protected $resultRedirectFactory;

    /** @var \Magento\Quote\Model\QuoteFactory */
    protected $quoteFactory;

    /** @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /** @var \Magento\Quote\Model\QuoteManagement */
    protected $quoteManagement;

    /** @var \Magento\Catalog\Model\ProductFactory */
    protected $catalogProductFactory;

    /** @var \Magento\Directory\Model\RegionFactory */
    protected $regionFactory;

    /** @var \Bread\BreadCheckout\Helper\Data */
    protected $helper;

    /** @var \Bread\BreadCheckout\Helper\Customer */
    protected $customerHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Bread\BreadCheckout\Model\Session $breadCheckoutSession,
        \Magento\Checkout\Model\CartFactory $checkoutCartFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Bread\BreadCheckout\Helper\Data $helper,
        \Bread\BreadCheckout\Helper\Customer $customerHelper
    )
    {
        $this->paymentApiClient = $paymentApiClient;
        $this->checkoutSession = $checkoutSession;
        $this->breadCheckoutSession = $breadCheckoutSession;
        $this->checkoutCartFactory = $checkoutCartFactory;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->quoteFactory = $quoteFactory;
        $this->customerSession = $customerSession;
        $this->quoteManagement = $quoteManagement;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->regionFactory = $regionFactory;
        $this->helper = $helper;
        $this->customerHelper = $customerHelper;
        parent::__construct($context);
    }

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

        $quote  = $this->quoteFactory->create(); /** @var $quote \Magento\Quote\Model\Quote */

        $storeId    = Mage::app()->getstore()->getId();
        $quote->setStoreId($storeId);

        if ($this->customerSession->isLoggedIn()) {
            $customer   = $this->customerSession->getCustomer(); /** @var $customer \Magento\Customer\Api\Data\CustomerInterface */
            $quote->assignCustomer($customer);
        } else {
            $quote->setCustomerEmail($data->billingContact->email);
        }

        $quote->setBreadTransactionId($data->breadTransactionId);
        $this->processOrderItems($quote, $data->lineItems);

        if (isset($data->discounts) && count($data->discounts) > 0){
            $discountDescription = $data->discounts[0]->description;
            $quote->setCouponCode(substr($discountDescription, 10, strlen($discountDescription) - 11));
        }

        $billingContact     = $this->processAddress($data->billingContact);
        $shippingContact    = $this->processAddress($data->shippingContact);

        if ($billingContact['city'] == null) {
            $billingContact['city']         = $shippingContact['city'];
            $billingContact['region_id']    = $shippingContact['region_id'];
        }

        $this->helper->log(["SHIPPING CONTACT"=>$shippingContact, "BILLING CONTACT"=>$billingContact]);

        $billingAddress     = $quote->getBillingAddress()->addData($billingContact);
        $shippingAddress    = $quote->getShippingAddress()->addData($shippingContact)->setCollectShippingRates(true);

        if (!isset($data->shippingMethodCode)) {
            $this->helper->log("Shipping Method Code Is Not Set On The Response");
        }

        $shippingAddress->setShippingMethod($data->shippingMethodCode);

        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }

        if ($quote->isVirtual()) {
            $quote->getBillingAddress()->setPaymentMethod('breadcheckout');
        } else {
            $quote->getShippingAddress()->setPaymentMethod('breadcheckout');
        }

        $customer   = $this->customerHelper->createCustomer($quote, $billingContact, $shippingContact);

        $quote->setTotalsCollectedFlag(false)->collectTotals()->save();

        $quote->getPayment()->importData(['method' => 'breadcheckout']);
        $quote->getPayment()->setTransactionId($data->breadTransactionId);
        $quote->getPayment()->setAdditionalData("BREAD CHECKOUT DATA", json_encode($data));

        try {
            $order = $this->quoteManagement->submit();
        } catch (\Exception $e) {
            $this->helper->log(["ERROR SUBMITTING QUOTE IN PROCESS ORDER"=>$e->getMessage()]), 'bread-exception.log');
            $this->logger->critical($e);
            throw $e;
        }

        $this->breadCheckoutSession->setLastRealOrderId($order->getId());
        $session    = $this->checkoutSession;
        $session->setLastSuccessQuoteId($quote->getId());
        $session->setLastQuoteId($quote->getId());
        $session->setLastOrderId($order->getId());

        try {
            $order->sendNewOrderEmail();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        $cart   = $this->checkoutCartFactory->create();
        $cart->truncate()->save();
        $cart->init();

        $cart       = $this->checkoutCartFactory->create();
        $cartItems  = $cart->getItems();
        foreach ($cartItems as $item) {
            $quote->removeItem($item->getId())->save();
        }

        $this->customerHelper->loginCustomer($customer);

        return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
    }

    /**
     * Process Order Items
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param array                      $data
     */
    protected function processOrderItems(\Magento\Quote\Model\Quote $quote, $data)
    {
        foreach ($data as $item) {
            if (!$item->product->sku) continue;

            $pieces         = explode('///', $item->product->sku);
            $productCount   = 0;
            $baseProduct    = null;
            if (count($pieces) > 1)
            {
                $baseProduct    = $this->catalogProductFactory->create();
                $baseProduct->load($baseProduct->getIdBySku($pieces[0]));
                $productCount++;
            }

            $product                = $this->catalogProductFactory->create();
            $customOptionPieces     = explode('***', $pieces[$productCount]);
            $product->load($product->getIdBySku($customOptionPieces[0]));

            if ($baseProduct == null){
                $baseProduct    = $product;
            }

            if ($product->getId()) {
                $this->addItemToQuote($quote, $product, $baseProduct, $customOptionPieces, isset($item->quantity) ? $item->quantity : 1);
            }
        }
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
        if( isset($contactData->state) ) {
            $region     = $this->regionFactory->create();      /** @var \Magento\Directory\Model\RegionFactory */
            $region->loadByCode($contactData->state, $this->helper->getDefaultCountry());
            if ($region->getId()) {
                $regionId   = $region->getId();
            }
        }

        $fullName       = isset($contactData->fullName) ? explode(' ', $contactData->fullName) : '';
        $addressData    = [
            'firstname'     => isset($contactData->firstName) ? $contactData->firstName : $fullName[0],
            'lastname'      => isset($contactData->lastName) ? $contactData->lastName : (isset($fullName[1]) ? $fullName[1] : ''),
            'street'        => $contactData->address . (isset($contactData->address2) ? (' ' .  $contactData->address2) : ''),
            'city'          => $contactData->city,
            'postcode'      => $contactData->zip,
            'telephone'     => $contactData->phone,
            'country_id'    => $this->helper->getDefaultCountry()
        ];

        if( null !== $regionId ) {
            $addressData['region']      = $contactData->state;
            $addressData['region_id']   = $regionId;
        }

        if( isset($contactData->email) ) {
            $addressData['email']   = $contactData->email;
        }

        return $addressData;
    }
}