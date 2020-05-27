<?php
/**
 * Add Token To Session Once Approved & Update Quote
 *
 * @author Bread   copyright 2016
 * @author Joel    @Mediotype
 * @author Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout;

class ValidatePaymentMethod extends \Bread\BreadCheckout\Controller\Checkout
{
    /**
     * @var \Bread\BreadCheckout\Model\Payment\Api\Client
     */
    public $paymentApiClient;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    public $checkoutSession;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    public $resultFactory;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    public $regionFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient,
        \Magento\Catalog\Model\ResourceModel\ProductFactory $catalogResourceModelProductFactory,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Bread\BreadCheckout\Helper\Log $logger,
        \Bread\BreadCheckout\Helper\Checkout $helper,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Directory\Model\RegionFactory $regionFactory
    ) {
    
        $this->paymentApiClient = $paymentApiClient;
        $this->checkoutSession = $checkoutSession;
        $this->resultFactory = $context->getResultFactory();
        $this->regionFactory = $regionFactory;
        parent::__construct(
            $context,
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
            $quoteManagement
        );
    }

    /**
     * Add bread transaction ID to session and update
     * address data in quote
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        try {
            $token = $this->getRequest()->getParam('token');
            $newData = [];

            if ($token) {
                $data = $this->paymentApiClient->getInfo($token);
                if ($data['breadTransactionId']) {
                    $this->checkoutSession->setBreadTransactionId($token);
                    $newData = $this->updateQuote($token);
                    $this->logger->log('UPDATE QUOTE FINISHED IN VALIDATE PAYMENT METHOD');
                }
                if ($data['adjustedTotal']) {
                    $this->logger->log('UPDATING ADJUSTED TOTAL IN VALIDATE PAYMENT METHOD');
                    $this->helper->setBreadTransactionAmount($data['adjustedTotal']);
                }
            }

            $this->logger->log('SETTING RESULT IN VALIDATE PAYMENT METHOD');
            $result = $newData;
        } catch (\Throwable $e) {
            $this->logger->log(['MESSAGE' => $e->getMessage(), 'TRACE' => $e->getTraceAsString()]);
            $result = ['error' => (string) __('Error: Unable to process transaction.')];
        }

        $this->logger->log('RETURNING RESULT IN VALIDATE PAYMENT METHOD');
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData($result);
    }

    /**
     * Update quote with billing address from Bread checkout popup
     *
     * @param  string $token
     * @return array
     */
    protected function updateQuote($token)
    {
        $this->logger->log('STARTING UPDATE QUOTE IN VALIDATE PAYMENT METHOD');
        $data = $this->paymentApiClient->getInfo($token);
        $billingData = $this->getFormattedAddress($data['billingContact']);

        $this->logger->log(['MESSAGE' => 'ADDING BILLING DATA TO QUOTE', 'DATA' => $billingData]);
        $quote = $this->checkoutSession->getQuote();
        $quote->getBillingAddress()->addData($billingData);

        $this->logger->log('ADDED BILLING DATA TO QUOTE');
        return ['billingAddress' => $quote->getBillingAddress()->getData()];
    }

    /**
     * Get address in correct format to add to Address object
     *
     * @param  array $data
     * @return array
     */
    protected function getFormattedAddress(array $data)
    {
        $name = explode(' ', trim($data['fullName']));
        $regionId = $this->regionFactory->create()->loadByCode($data['state'], 'US')->getId();

        return [
            'firstname' => $name[0],
            'lastname' => $name[1],
            'street' => $data['address'],
            'city' => $data['city'],
            'country_id' => 'US',
            'region' => $data['state'],
            'region_id' => $regionId,
            'postcode' => $data['zip'],
            'telephone' => $data['phone'],
            'save_in_address_book' => 1
        ];
    }
}
