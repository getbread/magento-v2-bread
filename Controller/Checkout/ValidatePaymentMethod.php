<?php
/**
 * Add Token To Session Once Approved
 *
 * @author  Bread   copyright 2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout;

class ValidatePaymentMethod extends \Bread\BreadCheckout\Controller\Checkout
{
    /** @var \Bread\BreadCheckout\Model\Payment\Api\Client */
    protected $paymentApiClient;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var \Magento\Framework\Controller\ResultFactory */
    protected $resultFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient,
        \Magento\Catalog\Model\ResourceModel\ProductFactory $catalogResourceModelProductFactory,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Psr\Log\LoggerInterface $logger,
        \Bread\BreadCheckout\Helper\Data $helper,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    )
    {
        $this->paymentApiClient = $paymentApiClient;
        $this->checkoutSession = $checkoutSession;
        $this->resultFactory = $context->getResultFactory();
        parent::__construct($context,
            $catalogResourceModelProductFactory,
            $dataObjectFactory,
            $checkoutSession,
            $quoteFactory,
            $catalogProductFactory,
            $logger,
            $helper,
            $totalsCollector,
            $quoteRepository);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        try {
            $token = $this->getRequest()->getParam('token');
            if ($token) {
                $data = $this->paymentApiClient->getInfo($token);
                if ($data['breadTransactionId']) {
                    $this->checkoutSession->setBreadTransactionId($token);
                    $newData = $this->updateQuote($token);
                }
            }

            $result = $newData;
        } catch (\Exception $e) {
            $result = false;
        }

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData(['result' => $result]);
    }

    /**
     * Update quote to reflect options selected in Bread checkout
     */
    protected function updateQuote($token)
    {
        $data = $this->paymentApiClient->getInfo($token);
        $billingData = $this->getFormattedAddress($data['billingContact']);

        $quote = $this->checkoutSession->getQuote();

        $quote->getBillingAddress()->addData($billingData);

        if (!$quote->getIsVirtual()) {
            $shippingData = $this->getFormattedAddress($data['shippingContact']);
            $quote->getShippingAddress()
                ->addData($shippingData)
                ->setShippingAmount($data['shippingCost'] / 100)
                ->setShippingMethod($data['shippingMethodCode']);
        }

        $quote->collectTotals();
        $this->quoteRepository->save($quote);

        return $quote->getShippingAddress()->getStreetLine(1);
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