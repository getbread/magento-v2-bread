<?php
/**
 * Helps Integration With Customer
 *
 * @copyright   Bread   copyright   2016
 * @author      Joel    @Mediotype
 */
namespace ;

class  extends Bread_BreadCheckout_Helper_Data{

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerCustomerFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $customerAddressFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\AddressFactory $customerAddressFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->customerCustomerFactory = $customerCustomerFactory;
        $this->storeManager = $storeManager;
        $this->customerAddressFactory = $customerAddressFactory;
        $this->logger = $logger;
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

        $primaryData        = array(
            'fullName'      => $defaultShippingAddress->getName(),
            'address'       => $defaultShippingAddress->getStreet1() . ($defaultShippingAddress->getStreet2() == '' ? '' : (' ' . $defaultShippingAddress->getStreet2())),
            'address2'      => $defaultShippingAddress->getStreet3() . ($defaultShippingAddress->getStreet4() == '' ? '' : (' ' . $defaultShippingAddress->getStreet4())),
            'city'          => $defaultShippingAddress->getCity(),
            'state'         => $defaultShippingAddress->getRegionCode(),
            'zip'           => $defaultShippingAddress->getPostcode(),
            'email'         => $customer->getEmail(),
            'phone'         => substr(preg_replace('/[^0-9]+/', '', $defaultShippingAddress->getTelephone()), -10)
        );

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
            return array();
        }

        $defaultBillingAddress     = $customer->getPrimaryBillingAddress();

        if( empty($defaultBillingAddress) ) {
            return array();
        }

        $primaryData        = array(
            'fullName'      => $defaultBillingAddress->getName(),
            'address'       => $defaultBillingAddress->getStreet1() . ($defaultBillingAddress->getStreet2() == '' ? '' : (' ' . $defaultBillingAddress->getStreet2())),
            'address2'      => $defaultBillingAddress->getStreet3() . ($defaultBillingAddress->getStreet4() == '' ? '' : (' ' . $defaultBillingAddress->getStreet4())),
            'city'          => $defaultBillingAddress->getCity(),
            'state'         => $defaultBillingAddress->getRegionCode(),
            'zip'           => $defaultBillingAddress->getPostcode(),
            'email'         => $customer->getEmail(),
            'phone'         => substr(preg_replace('/[^0-9]+/', '', $defaultBillingAddress->getTelephone()), -10)
        );

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
        $session    = $this->customerSession;
        if ($session->isLoggedIn()) {
            return $session->getCustomer();
        }

        $quote->setCustomerLastname($billingContact['lastname']);
        $quote->setCustomerFirstname($billingContact['firstname']);

        if ($this->isAutoCreateCustomerAccountEnabled() == false) {
            return;
        }

        $customer   = $this->customerCustomerFactory->create();

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

            $this->customerSession->loginById($customer->getId());
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
        } catch (Exception $ex) {
            Mage::helper('breadcheckout')->log('Exception While Logging In Customer', 'bread-exception.log');
            $this->logger->critical($ex);
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

        return Mage::helper('core')->jsonEncode($primaryAddressData);
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

        return Mage::helper('core')->jsonEncode($primaryAddressData);
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
        return Mage::getSingleton('customer/session');
    }

}