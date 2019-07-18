<?php
/**
 * Add Token To Session Once Approved & Update Quote
 *
 * @author  Bread   copyright 2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout;

class ValidatePaymentMethod extends \Bread\BreadCheckout\Controller\Checkout
{
    /** @var \Bread\BreadCheckout\Model\Payment\Api\Client */
    public $paymentApiClient;

    /** @var \Magento\Checkout\Model\Session */
    public $checkoutSession;

    /** @var \Magento\Framework\Controller\ResultFactory */
    public $resultFactory;

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
        \Magento\Quote\Model\QuoteManagement $quoteManagement
    ) {
    
        $this->paymentApiClient = $paymentApiClient;
        $this->checkoutSession = $checkoutSession;
        $this->resultFactory = $context->getResultFactory();
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
                }
                if ($data['adjustedTotal']) {
                    $this->helper->setBreadTransactionAmount($data['adjustedTotal']);
                }
            }

            $result = $newData;
        } catch (\Exception $e) {
            $this->logger->log(['MESSAGE' => $e->getMessage(), 'TRACE' => $e->getTraceAsString()]);
            $result = ['error' => (string) __('Error: Unable to process transaction.')];
        }

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData($result);
    }

    /**
     * Update quote with billing address from Bread checkout popup
     *
     * @param string $token
     * @return array
     */
    protected function updateQuote($token)
    {
        $data = $this->paymentApiClient->getInfo($token);
        $billingData = $this->getFormattedAddress($data['billingContact']);

        $quote = $this->checkoutSession->getQuote();
        $quote->getBillingAddress()->addData($billingData);
        $quote->collectTotals();
        $this->quoteRepository->save($quote);

        return ['billingAddress' => $quote->getBillingAddress()->getData()];
    }

    /**
     * Get address in correct format to add to Address object
     *
     * @param array $data
     * @return array
     */
    protected function getFormattedAddress(array $data)
    {
        $name = explode(' ', trim($data['fullName']));
        return [
            'firstname' => $name[0],
            'lastname' => $name[1],
            'street' => $data['address'],
            'city' => $data['city'],
            'country_id' => 'US',
            'region' => $data['state'],
            'postcode' => $data['zip'],
            'telephone' => $data['phone'],
            'save_in_address_book' => 1
        ];
    }
}
