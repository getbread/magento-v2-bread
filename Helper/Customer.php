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
    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $customerFactory;

    /** @var \Magento\Customer\Model\AddressFactory */
    protected $customerAddressFactory;

    /** @var Magento\Framework\Json\Helper\Data */
    protected $jsonHelper;

    /** @var \Magento\Framework\Math\Random */
    protected $random;

    public function __construct(
        \Magento\Framework\App\Helper\Context $helperContext,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\UrlInterfaceFactory $urlInterfaceFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $customerAddressFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Math\Random $random
    ) {
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->customerFactory = $customerFactory;
        $this->customerAddressFactory = $customerAddressFactory;
        $this->jsonHelper = $jsonHelper;
        $this->random = $random;
        parent::__construct($helperContext, $context, $request, $encryptor, $urlInterfaceFactory);
    }
    /**
     * Pass Back Bread Formatted Default Customer Address If It Exists
     *
     * @return array
     */
    public function getFormattedDefaultShippingAddress()
    {
        $session                    = $this->customerSession;
        $customer                   = $session->getCustomer();

        if( empty($customer) ) {
            return [];
        }

        $defaultShippingAddress     = $customer->getPrimaryShippingAddress();

        if(!$defaultShippingAddress->getStreetLine(1)){
            return [];
        }

        $primaryData        = [
            'fullName'      => $defaultShippingAddress->getName(),
            'address'       => $defaultShippingAddress->getStreetLine(1) . ($defaultShippingAddress->getStreetLine(2) == '' ? '' : (' ' . $defaultShippingAddress->getStreetLine(2))),
            'address2'      => $defaultShippingAddress->getStreetLine(3) . ($defaultShippingAddress->getStreetLine(4) == '' ? '' : (' ' . $defaultShippingAddress->getStreetLine(4))),
            'city'          => $defaultShippingAddress->getCity(),
            'state'         => $defaultShippingAddress->getRegionCode(),
            'zip'           => $defaultShippingAddress->getPostcode(),
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
        $session                    = $this->customerSession;
        $customer                   = $session->getCustomer();

        if( empty($customer) ) {
            return [];
        }

        $defaultBillingAddress     = $customer->getPrimaryBillingAddress();
        if(!$defaultBillingAddress){
            return [];
        }

        $primaryData        = [
            'fullName'      => $defaultBillingAddress->getName(),
            'address'       => $defaultBillingAddress->getStreetLine(1) . ($defaultBillingAddress->getStreetLine(2) == '' ? '' : (' ' . $defaultBillingAddress->getStreetLine(2))),
            'address2'      => $defaultBillingAddress->getStreetLine(3) . ($defaultBillingAddress->getStreetLine(4) == '' ? '' : (' ' . $defaultBillingAddress->getStreetLine(4))),
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
        $session    = $this->customerSession;
        if ($session->isLoggedIn()) {
            return $session->getCustomer();
        }

        $quote->setCustomerLastname($billingContact['lastname']);
        $quote->setCustomerFirstname($billingContact['firstname']);

        if ($this->isAutoCreateCustomerAccountEnabled() == false) {
            return;
        }

        $customer   = $this->customerFactory->create(); /** @var \Magento\Customer\Model\CustomerFactory */
        $email      = $quote->getCustomerEmail();

        $customer->setWebsiteId($this->storeManager->getWebsite()->getId());

        // Don't create a new account if one already exists for this email
        $customer->loadByEmail($email);
        if( $customer->getId() ) {
            return;
        }

        $billingAddress     = $this->customerAddressFactory->create();
        $billingAddress->setData($billingContact);
        $shippingAddress    = $this->customerAddressFactory->create();
        $shippingAddress->setData($shippingContact);

        $customer->setEmail($email)
            ->setPassword($this->generatePassword(7));
        $quote->getBillingAddress()->setIsDefaultBilling(true)->setSaveInAddressBook(true);
        $quote->getShippingAddress()->setIsDefaultShipping(true)->setSaveInAddressBook(true);
        $customer->setDefaultBilling($billingAddress->getId())
            ->setDefaultShipping($shippingAddress->getId())
            ->setLastname($quote->getCustomerLastname())
            ->setFirstname($quote->getCustomerFirstname());

        try {
            $customer->save();
            $customer->setConfirmation(null);

            $session->loginById($customer->getId());
            $quote->setCustomerId($customer->getId());

            $billingAddress->setCustomerId($customer->getId());
            $customer->addAddress($billingAddress);
            $billingAddress->save();

            $shippingAddress->setCustomerId($customer->getId())
                ->setCustomer($customer);
            $customer->addAddress($shippingAddress);
            $shippingAddress->save();

            $customer->save()->sendNewAccountEmail();
        } catch (\Exception $e) {
            $this->log('Exception While Logging In Customer');
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
        if($this->customerSession->getCustomer()->getPrimaryBillingAddress() == false){
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
         return (bool) $this->customerSession->isLoggedIn();
     }

    /**
     * Generate random password during automatic customer account creation
     *
     * @param $length int
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function generatePassword($length)
    {
        return $this->encryptor->getHash($this->random->getRandomString($length), true);
    }

}