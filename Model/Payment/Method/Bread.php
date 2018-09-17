<?php
/**
 * Bread Finance Payment Method
 *
 * @author  Bread   copyright   2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Model\Payment\Method;

class Bread extends \Magento\Payment\Model\Method\AbstractMethod
{

    /* internal action types */
    const ACTION_CAPTURE                = "capture";
    const ACTION_REFUND                 = "refund";
    const ACTION_VOID                   = "void";

    public $_code          = 'breadcheckout';
    public $_infoBlockType = 'Bread\BreadCheckout\Block\Payment\Info';
    public $_formBlockType = 'Bread\BreadCheckout\Block\Payment\Form';

    public $_isGateway               = true;
    public $_canAuthorize            = true;
    public $_canCapture              = true;
    public $_canCapturePartial       = false;
    public $_canOrder                = false;
    public $_canRefund               = true;
    public $_canRefundInvoicePartial = true;
    public $_canVoid                 = true;
    public $_canUseInternal          = true;
    public $_canUseCheckout          = true;
    public $_canUseForMultishipping  = false;
    public $_canFetchTransactionInfo = true;
    public $_canSaveCc               = false;
    public $_canReviewPayment        = true;
    public $_allowCurrencyCode       = ['USD'];

    /** @var \Bread\BreadCheckout\Model\Payment\Api\Client */
    public $apiClient;

    /** @var \Magento\Payment\Model\Method\Logger */
    public $logger;

    /** @var \Bread\BreadCheckout\Helper\Data */
    public $helper;

    /** @var \Magento\Framework\Json\Helper\Data */
    public $jsonHelper;

    /** @var \Magento\Checkout\Model\Session */
    public $checkoutSession;

    /** @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface */
    public $transactionBuilder;

    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    public $quoteRepository;

    /** @var \Magento\Sales\Api\TransactionRepositoryInterface */
    public $transactionRepository;

    /** @var \Magento\Sales\Model\AdminOrder\Create */
    public $orderCreateModel;

    /** @var \Magento\Framework\Pricing\PriceCurrencyInterface */
    public $priceCurrency;

    /** @var \Bread\BreadCheckout\Helper\Quote */
    private $quoteHelper;

    /**
     * Construct Sets API Client And Sets Available For Checkout Flag
     *
     * @param \Magento\Framework\Model\Context                                $context
     * @param \Bread\BreadCheckout\Model\Payment\Api\Client                   $apiClient
     * @param \Bread\BreadCheckout\Helper\Data                                $helper
     * @param \Bread\BreadCheckout\Helper\Quote                               $quoteHelper
     * @param \Magento\Framework\Json\Helper\Data                             $jsonHelper
     * @param \Magento\Framework\Registry                                     $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory               $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory                    $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                                    $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface              $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                            $logger
     * @param \Magento\Checkout\Model\Session\Proxy                           $checkoutSession
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
     * @param \Magento\Quote\Api\CartRepositoryInterface                      $quoteRepository
     * @param \Magento\Sales\Api\TransactionRepositoryInterface               $transactionRepository
     * @param \Magento\Sales\Model\AdminOrder\Create                          $orderCreateModel
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface               $priceCurrency
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Bread\BreadCheckout\Model\Payment\Api\Client $apiClient,
        \Bread\BreadCheckout\Helper\Data $helper,
        \Bread\BreadCheckout\Helper\Quote $quoteHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Sales\Model\AdminOrder\Create $orderCreateModel,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
    
        $this->apiClient = $apiClient;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->jsonHelper = $jsonHelper;
        $this->_canUseCheckout = $this->helper->isPaymentMethodAtCheckout();
        $this->checkoutSession = $checkoutSession;
        $this->transactionBuilder = $transactionBuilder;
        $this->quoteRepository = $quoteRepository;
        $this->transactionRepository = $transactionRepository;
        $this->orderCreateModel = $orderCreateModel;
        $this->priceCurrency = $priceCurrency;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );
        $this->quoteHelper = $quoteHelper;
    }

    /**
     * Fetch Payment Info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return array|mixed
     * @throws \Exception
     */
    public function fetchTransactionInfo(\Magento\Payment\Model\InfoInterface $payment, $transactionId)
    {
        $this->apiClient->setOrder($payment->getOrder());
        return $this->apiClient->getInfo($transactionId);
    }

    /**
     * Validate Payment Method before allowing next step in checkout
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate()
    {
        $paymentInfo   = $this->getInfoInstance();
        if ($paymentInfo instanceof \Magento\Sales\Model\Order\Payment) {
             $billingCountry    = $paymentInfo->getOrder()->getBillingAddress()->getCountryId();
        } else {
             $billingCountry    = $paymentInfo->getQuote()->getBillingAddress()->getCountryId();
        }

        if (!$this->canUseForCountry($billingCountry)) {
            $this->helper->log("ERROR IN METHOD VALIDATE, INVALID BILLING COUNTRY". $billingCountry);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('This financing program is available to US residents, please click the finance button 
                and complete the application in order to complete your purchase with the financing payment method.')
            );
        }

        $token = $this->getToken();
        if (empty($token)) {
            $this->helper->log("ERROR IN METHOD VALIDATE, MISSING BREAD TOKEN");
            throw new \Magento\Framework\Exception\LocalizedException(
                __('This financing program is unavailable, please complete the application. 
                If the problem persists, please contact us.')
            );
        }

        return $this;
    }

    /**
     * Process Cancel Payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this|Bread
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        return $this->void($payment);
    }

    /**
     * Process Void Payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \Bread\BreadCheckout\Model\Payment\Method\Bread
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        if (!$this->canVoid()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Void action is not available.'));
        }

        return $this->_place($payment, 0, self::ACTION_VOID);
    }

    /**
     * Process Authorize Payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float         $amount
     * @return \Bread\BreadCheckout\Model\Payment\Method\Bread
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Authorize action is not available.'));
        }

        $payment->setAmount($amount);
        $payment->setIsTransactionClosed(false);
        $payment->setTransactionId($this->getToken());

        $this->_place($payment, $amount, self::ACTION_AUTHORIZE);
        return $this;
    }

    /**
     * Set capture transaction ID to invoice for informational purposes
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return \Magento\Payment\Model\Method\AbstractMethod
     */
    public function processInvoice(
        \Magento\Sales\Model\Order\Invoice $invoice,
        \Magento\Sales\Model\Order\Payment $payment
    ) {
    
        $invoice->setTransactionId($payment->getLastTransId());
        return $this;
    }

    /**
     * Process Capture Payment
     *
     * @param \Magento\Framework\DataObject $payment
     * @param float         $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Capture action is not available.'));
        }

        if ($this->helper->getPaymentAction() == self::ACTION_AUTHORIZE_CAPTURE) {
            $this->apiClient->setOrder($payment->getOrder());

            if($this->_appState->getAreaCode() === \Magento\Framework\App\Area::AREA_ADMINHTML){
                $token = $this->orderCreateModel->getSession()->getBreadTransactionId();
            } else {
                $token  = $this->checkoutSession->getBreadTransactionId();
            }

            $result = $this->apiClient->authorize(
                $token,
                ($this->priceCurrency->round($amount) * 100),
                $payment->getOrder()->getIncrementId()
            );
            $payment->setTransactionId($result['breadTransactionId']);
        } else {
            $token  = $payment->getAuthorizationTransaction()->getTxnId();
        }

        $payment->setTransactionId($token);
        $payment->setAmount($amount);
        $this->_place($payment, $amount, self::ACTION_CAPTURE);

        return $this;
    }

    /**
     * Set transaction ID into creditmemo for informational purposes
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return \Magento\Payment\Model\Method\AbstractMethod
     */
    public function processCreditmemo(
        \Magento\Sales\Model\Order\Creditmemo $creditmemo,
        \Magento\Payment\Model\InfoInterface $payment
    ) {
    
        $creditmemo->setTransactionId($payment->getLastTransId());

        return $this;
    }

    /**
     * Process Refund Payment
     *
     * @param \Magento\Framework\DataObject $payment
     * @param float         $amount
     * @return \Bread\BreadCheckout\Model\Payment\Method\Bread
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Refund action is not available.'));
        }

        return $this->_place($payment, $amount, self::ACTION_REFUND);
    }

    /**
     * Order payment
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $transaction = $this->transactionBuilder->setPayment($payment)
            ->setOrder($payment->getOrder())
            ->setTransactionId($payment->getTransactionId())
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER);

        $transactionAdditionalInfo = $payment->getTransactionAdditionalInfo();
        if (array_key_exists('is_closed', $transactionAdditionalInfo)) {
            $transaction->setIsClosed((bool) $transactionAdditionalInfo['is_closed']);
        }
        if (array_key_exists('message', $transactionAdditionalInfo)) {
            $transaction->setMessage($transactionAdditionalInfo['message']);
        }
    }

    /**
     * Process API Call Based on Request Type And Add Normalized Magento Transaction Data To Orders
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @param $requestType
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _place(\Magento\Payment\Model\InfoInterface $payment, $amount, $requestType)
    {
        $this->apiClient->setOrder($payment->getOrder());
        switch ($requestType) {
            case self::ACTION_AUTHORIZE:
                    $result     = $this->apiClient->authorize(
                        $this->getValidatedTxId($payment),
                        ($this->priceCurrency->round($amount) * 100),
                        $payment->getOrder()->getIncrementId()
                    );
                    $payment->setTransactionId($result['breadTransactionId']);
                    $this->addTransactionInfo(
                        $payment,
                        ['is_closed' => false, 'authorize_result' => $this->jsonHelper->jsonEncode($result)],
                        [],
                        "Bread Finance Payment Authorized"
                    );
                break;
            case self::ACTION_CAPTURE:
                    $result     = $this->apiClient->settle($this->getValidatedTxId($payment));
                    $payment->setTransactionId($result['breadTransactionId'])
                            ->setAmount($amount);
                    $this->addTransactionInfo(
                        $payment,
                        ['is_closed' => false, 'settle_result' => $this->jsonHelper->jsonEncode($result)],
                        [],
                        "Bread Finance Payment Captured"
                    );
                break;
            case self::ACTION_REFUND:
                    $result     = $this->apiClient->refund(
                        $this->getValidatedTxId($payment),
                        ($this->priceCurrency->round($amount) * 100)
                    );
                    $payment->setTransactionId($payment->getTransactionId())
                            ->setAmount($amount)
                            ->setIsTransactionClosed(1)
                            ->setShouldCloseParentTransaction(1);
                    $this->addTransactionInfo(
                        $payment,
                        ['is_closed' => false, 'refund_result' => $this->jsonHelper->jsonEncode($result)],
                        [],
                        "Bread Finance Payment Refunded"
                    );
                break;
            case self::ACTION_VOID:
                    $result     = $this->apiClient->cancel($this->getValidatedTxId($payment));
                    $payment->setTransactionId($payment->getTransactionId())
                            ->setIsTransactionClosed(1)
                            ->setShouldCloseParentTransaction(1);
                    $this->addTransactionInfo(
                        $payment,
                        ['is_closed' => true, 'cancel_result' => $this->jsonHelper->jsonEncode($result)],
                        [],
                        "Bread Finance Payment Canceled"
                    );
                break;
            default:
                break;
        }

        return $result;
    }

    /**
     * Add payment transaction info to payment object
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param array $transactionAdditionalInfo
     * @param array $transactionDetails
     * @param null $message
     * @return \Magento\Payment\Model\InfoInterface
     */
    protected function addTransactionInfo(
        \Magento\Payment\Model\InfoInterface $payment,
        $transactionAdditionalInfo = [],
        $transactionDetails = [],
        $message = null
    ) {
        try {
            $payment->resetTransactionAdditionalInfo();

            foreach ($transactionAdditionalInfo as $key => $value) {
                $payment->setTransactionAdditionalInfo($key, $value);
            }

            $payment->setTransactionAdditionalInfo('message', $message);

            foreach ($transactionDetails as $key => $value) {
                $payment->unsetData($key);
            }

            $payment->unsLastTransId();

            return $payment;
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
            $this->helper->log($e->getTraceAsString());
        }
    }

    /**
     * Is the 'breadcheckout' payment method available
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote === null || !$quote->getBreadTransactionId()) {
            return true;
        }

        if (!parent::isAvailable($quote)) {
            return false;
        }

        return true;
    }

    /**
     * Get Bread transaction ID saved in session
     *
     * @return string
     */
    protected function getToken()
    {
        if ($this->helper->isInAdmin()) {
            $token = $this->orderCreateModel->getSession()->getBreadTransactionId();
        } else {
            $token = $this->checkoutSession->getBreadTransactionId();
        }

        return $token;
    }

    /**
     * Validates and sanitizes the given transaction ID for making
     * an API request to Bread
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getValidatedTxId(\Magento\Payment\Model\InfoInterface $payment)
    {
        $rawTransId = $payment->getTransactionId();

        if (preg_match('/^[a-z0-9]{8}-([a-z0-9]{4}-){3}[a-z0-9]{12}/', $rawTransId, $matches)) {
            return $matches[0];
        } else {
            $this->helper->log("INVALID TRANSACTION ID PROVIDED: ". $rawTransId);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Unable to process request because an invalid transaction ID was provided.')
            );
        }
    }

    /**
     * Returns payment method code
     * @return string
     */
    public function getMethodCode()
    {
        return $this->_code;
    }

    /**
     * Returns payment title with monthly estimate
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTitle()
    {
        $title = parent::getTitle();
        $showPerMonth = $this->helper->showPerMonthCalculation();

        if ($this->_appState->getAreaCode() == \Magento\Framework\App\Area::AREA_WEBAPI_REST && $showPerMonth) {
            $data = $this->quoteHelper->submitQuote();
            if (isset($data["asLowAs"]) && isset($data["asLowAs"]["amount"])) {
                $title .= " " . sprintf(__("as low as %s/month*"), $data["asLowAs"]["amount"]);
            }
        }
        return $title;
    }

    /**
     * Returns base payment title
     *
     * @return string
     */
    public function getBaseTitle()
    {
        return parent::getTitle();
    }
}
