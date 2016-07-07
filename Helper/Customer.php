<?php
/**
 * Helps Integration With Customer
 *
 * @copyright   Bread   copyright   2016
 * @author      Joel    @Mediotype
 * @author      Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Helper;

class Customer extends Data
{
    /** @var \Magento\Customer\Model\SessionFactory */
    protected $customerSessionFactory;

    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $customerFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Customer\Model\AddressFactory */
    protected $customerAddressFactory;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var Bread\BreadCheckout\Helper */
    protected $helper;

    /** @var Magento\Framework\Json\Helper\Data */
    protected $jsonHelper;

    public function __construct(
        \Magento\Customer\Model\SessionFactory $customerSessionFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\AddressFactory $customerAddressFactory,
        \Psr\Log\LoggerInterface $logger,
        \Bread\BreadCheckout\Helper\Data $helper,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->customerSessionFactory = $customerSessionFactory;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->customerAddressFactory = $customerAddressFactory;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->jsonHelper = $jsonHelper;
    }
    /**
     * Pass Back Bread Formatted Default Customer Address If It Exists
     *
     * @return array
     */
    public function getFormattedDefaultShippingAddress()
    {
        $session                    = $this->getCustomerSession();
        $customer                   = $session->getCustomer();

        if( empty($customer) ) {
            return array();
        }

        $defaultShippingAddress     = $customer->getPrimaryShippingAddress();

        if( empty($defaultShippingAddress) ) {
            return array();
        }

        $primaryData        = [
            'fullName'      => $defaultShippingAddress->getName(),
            'address'       => $defaultShippingAddress->getStreet1() . ($defaultShippingAddress->getStreet2() == '' ? '' : (' ' . $defaultShippingAddress->getStreet2())),
            'address2'      => $defaultShippingAddress->getStreet3() . ($defaultShippingAddress->getStreet4() == '' ? '' : (' ' . $defaultShippingAddress->getStreet4())),
            'city'          => $defaultShippingAddress->getCity(),
            'state'         => $defaultShippingAddress->getRegionCode(),
            'zip'           => $defaultShippingAddress->getPostcode(),
            'email'         => $customer->getEmail(),
            'phone'         => substr(preg_replace('/[^0-9]+/', '', $defaultShippingAddress->getTelephone()), -10)
        ];

        return $primaryData;
    }

    /**
     * Pass Back Bread Formatted Default Customer Address If It Exists
     *
     * @return array
     */
    public function getFormattedDefaultBillingAddress()
    {
        $session                    = $this->getCustomerSession();
        $customer                   = $session->getCustomer();

        if( empty($customer) ) {
            return [];
        }

        $defaultBillingAddress     = $customer->getPrimaryBillingAddress();

        if( empty($defaultBillingAddress) ) {
            return [];
        }

        $primaryData        = [
            'fullName'      => $defaultBillingAddress->getName(),
            'address'       => $defaultBillingAddress->getStreet1() . ($defaultBillingAddress->getStreet2() == '' ? '' : (' ' . $defaultBillingAddress->getStreet2())),
            'address2'      => $defaultBillingAddress->getStreet3() . ($defaultBillingAddress->getStreet4() == '' ? '' : (' ' . $defaultBillingAddress->getStreet4())),
            'city'          => $defaultBillingAddress->getCity(),
            'state'         => $defaultBillingAddress->getRegionCode(),
            'zip'           => $defaultBillingAddress->getPostcode(),
            'email'         => $customer->getEmail(),
            'phone'         => substr(preg_replace('/[^0-9]+/', '', $defaultBillingAddress->getTelephone()), -10)
        ];

        return $primaryData;
    }

    /**
     * Create Customer Called From Order Place Process
     *
     * @param $quote
     * @param $billingContact
     * @param $shippingContact
     * @return \Magento\Customer\Model\Customer|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createCustomer($quote, $billingContact, $shippingContact)
    {
        $session    = $this->customerSessionFactory;
        if ($session->isLoggedIn()) {
            return $session->getCustomer();
        }

        $quote->setCustomerLastname($billingContact['lastname']);
        $quote->setCustomerFirstname($billingContact['firstname']);

        if ($this->isAutoCreateCustomerAccountEnabled() == false) {
            return;
        }

        $customer   = $this->customerFactory->create();
        $email      = $quote->getCustomerEmail();

        $customer->setWebsiteId($this->storeManager->getWebsite()->getId());
        $customer->loadByEmail($email);
        $isNewCustomer      = false;

        $billingAddress     = $this->customerAddressFactory->create();
        $billingAddress->setData($billingContact);
        $shippingAddress    = $this->customerAddressFactory->create();
        $shippingAddress->setData($shippingContact);

        if( !$customer->getId() ) {
            $isNewCustomer      = true;
            $customer->setEmail($email);
            $customer->setPassword($customer->generatePassword(7));
            $billingAddress->setIsDefaultBilling('1');
            $billingAddress->setSaveInAddressBook('1');
            $shippingAddress->setIsDefaultShipping('1');
            $shippingAddress->setSaveInAddressBook('1');
            $customer->setPrimaryBillingAddress($billingAddress);
            $customer->setPrimaryShippingAddress($shippingAddress);
            $customer->setLastname($quote->getCustomerLastname());
            $customer->setFirstname($quote->getCustomerFirstname());
        }

        try {
            $customer->save();
            $customer->setConfirmation(null);

            $this->customerSessionFactory->loginById($customer->getId());
            $quote->setCustomerId($customer->getId());
            $quote->setCustomer($customer);

            if($isNewCustomer) {
                $billingAddress->setCustomerId($customer->getId());
                $billingAddress->setCustomer($customer);
                $customer->addAddress($billingAddress);
                $billingAddress->save();
                $shippingAddress->setCustomerId($customer->getId());
                $shippingAddress->setCustomer($customer);
                $customer->addAddress($shippingAddress);
                $shippingAddress->save();
            }

            $customer->save();

            if($isNewCustomer) {
                $customer->sendNewAccountEmail();
            }
        } catch (Exception $e) {
            $this->helper->log('Exception While Logging In Customer');
            $this->logger->critical($e);
        }

        return $customer;
    }

     /**
     * Get Default Customer Shipping Address If It Exists
     *
     * @return string
     */
    public function getShippingAddressData()
    {
        if( $this->isUserLoggedIn() == false ){
            return 'false';
        }

        if( $this->hasBillingAddress() == false ){
            return 'false';
        }

        $primaryAddressData     = $this->getFormattedDefaultShippingAddress();
        return $this->jsonHelper->jsonEncode($primaryAddressData);
    }

    /**
     * Get Billing Address Default Data
     *
     * @return string
     */
    public function getBillingAddressData()
    {
        if( $this->isUserLoggedIn() == false ){
            return 'false';
        }

        if( $this->hasBillingAddress() == false ){
            return 'false';
        }

        $primaryAddressData     = $this->getFormattedDefaultBillingAddress();
        return $this->jsonHelper->jsonEncode($primaryAddressData);
    }


    /**
     * Check if Customer has associated addresses
     *
     * @return bool
     */
    public function hasBillingAddress()
    {
        if($this->getCustomerSession()->getCustomer()->getPrimaryBillingAddress() == false){
            return false;
        }

        return true;
    }

     /**
     * Check if current visitor is logged in
     *
     * @return bool
     */
     public function isUserLoggedIn()
     {
         return (bool) $this->getCustomerSession()->isLoggedIn();
     }

    /**
     * Get Current Customer Session
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function getCustomerSession()
    {
        return $this->customerSessionFactory->create();
    }

}