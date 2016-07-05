<?php
/**
 * Bread Finance Payment Method
 *
 * @method Bread_BreadCheckout_Model_Payment_Api_Client getApiClient()
 * @method setApiClient($value)
 *
 * @author  Bread   copyright   2016
 * @author  Joel    @Mediotype
 */
namespace ;

class  extends \Magento\Payment\Model\Method\AbstractMethod
{
    const ACTION_AUTHORIZE              = "authorize";
    const ACTION_AUTHORIZE_CAPTURE      = "authorize_capture";

    /* internal action types */
    const ACTION_CAPTURE                = "capture";
    const ACTION_REFUND                 = "refund";
    const ACTION_VOID                   = "void";

    protected $_code          = 'breadcheckout';
    protected $_formBlockType = 'breadcheckout/payment_form';
    protected $_infoBlockType = 'breadcheckout/payment_info';

    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canOrder                = false;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_canFetchTransactionInfo = true;
    protected $_canSaveCc               = false;
    protected $_canReviewPayment        = true;
    protected $_allowCurrencyCode       = array('USD');

    protected $_apiClient = null;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Construct Sets API Client And Sets Available For Checkout Flag
     *
     * @param array $params
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        $params = array())
    {
        $this->logger = $logger;
        $this->setApiClient(Mage::getModel('breadcheckout/payment_api_client'));
        $this->_canUseCheckout      = Mage::helper('breadcheckout')->isPaymentMethodAtCheckout();
    }

    /**
     * Fetch Payment Info
     *
     * @param \Magento\Payment\Model\Info $payment
     * @param string                  $transactionId
     * @return mixed
     */
    public function fetchTransactionInfo(\Magento\Payment\Model\Info $payment, $transactionId)
    {
        return $this->getApiClient()->getInfo($transactionId);
    }

    /**
     * Validate Payment Method before allowing next step in checkout
     *
     * @return $this|Mage_Payment_Model_Abstract
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
            Mage::helper('breadcheckout')->log("ERROR IN METHOD VALIDATE, INVALID BILLING COUNTRY". $billingCountry);
            throw new \Magento\Framework\Exception\LocalizedException(__('This financing program is available to US residents, please click the finance button and complete the application in order to complete your purchase with the financing payment method.'));
        }

        if ($paymentInfo instanceof \Magento\Sales\Model\Order\Payment) {
             $token    = $paymentInfo->getOrder()->getQuote()->getBreadTransactionId();
        } else {
             $token    = $paymentInfo->getQuote()->getBreadTransactionId();
        }

        if( empty($token) ) {
            Mage::helper('breadcheckout')->log("ERROR IN METHOD VALIDATE, MISSING BREAD TOKEN");
            throw new \Magento\Framework\Exception\LocalizedException(__('This financing program is unavailable, please complete the application. If the problem persists, please contact us.'));
        }

        return $this;
    }

    /**
     * Process Cancel Payment
     *
     * @param \Magento\Framework\DataObject $payment
     * @return $this
     */
    public function cancel(\Magento\Framework\DataObject $payment)
    {
        return $this->void($payment);
    }

    /**
     * Process Void Payment
     *
     * @param \Magento\Framework\DataObject $payment
     * @return Bread_BreadCheckout_Model_Payment_Method_Bread
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function void(\Magento\Framework\DataObject $payment)
    {
        if (!$this->canVoid($payment)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Void action is not available.'));
        }

        return $this->_place($payment, 0, self::ACTION_VOID);
    }

    /**
     * Process Authorize Payment
     *
     * @param \Magento\Framework\DataObject $payment
     * @param float         $amount
     * @return Bread_BreadCheckout_Model_Payment_Method_Bread
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorize(\Magento\Framework\DataObject $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Authorize action is not available.'));
        }

        $payment->setAmount($amount);
        $payment->setIsTransactionClosed(false);
        $payment->setTransactionId($payment->getOrder()->getQuote()->getBreadTransactionId());

        $this->_place($payment, $amount, self::ACTION_AUTHORIZE);

        return $this;
    }

    /**
     * Set capture transaction ID to invoice for informational purposes
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return \Magento\Payment\Model\Method\AbstractMethod
     */
    public function processInvoice($invoice, $payment)
    {
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
    public function capture(\Magento\Framework\DataObject $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Capture action is not available.'));
        }

        if(Mage::helper('breadcheckout')->getPaymentAction() == self::ACTION_AUTHORIZE_CAPTURE){
            $token  = $payment->getOrder()->getQuote()->getBreadTransactionId();
            $result     = $this->getApiClient()->authorize($token, (int)round($amount * 100), $payment->getOrder()->getIncrementId() );
            $payment->setTransactionId($result->breadTransactionId);
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
    public function processCreditmemo($creditmemo, $payment)
    {
        $creditmemo->setTransactionId($payment->getLastTransId());

        return $this;
    }

    /**
     * Process Refund Payment
     *
     * @param \Magento\Framework\DataObject $payment
     * @param float         $amount
     * @return Bread_BreadCheckout_Model_Payment_Method_Bread
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Framework\DataObject $payment, $amount)
    {
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Refund action is not available.'));
        }

        return $this->_place($payment, $amount, self::ACTION_REFUND);
    }

    /**
     * Process API Call Based on Request Type And Add Normalized Magento Transaction Data To Orders
     *
     * @param $payment
     * @param $amount
     * @param $requestType
     */
    protected function _place($payment, $amount, $requestType)
    {
        switch ($requestType) {
            case self::ACTION_AUTHORIZE:
                    $result     = $this->getApiClient()->authorize($payment->getTransactionId(), (int)round($amount * 100), $payment->getOrder()->getIncrementId() );
                    $payment->setTransactionId($result->breadTransactionId);
                    $this->addTransaction($payment
                        , \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH
                        , $result->breadTransactionId
                        , array('is_closed' => false, 'authorize_result' => json_encode($result))
                        , array()
                        , "Bread Finance Payment Authorized");
                break;
            case self::ACTION_CAPTURE:
                    $result     = $this->getApiClient()->settle($payment->getTransactionId());
                    $payment->setTransactionId($result->breadTransactionId);
                    $this->addTransaction($payment
                        , \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE
                        , $result->breadTransactionId
                        , array('is_closed' => false, 'settle_result' => json_encode($result))
                        , array()
                        , "Bread Finance Payment Captured");
                break;
            case self::ACTION_REFUND:
                    $result     = $this->getApiClient()->refund($payment->getTransactionId(), (int)round($amount * 100));
                    $payment->setTransactionId($result->breadTransactionId);
                    $this->addTransaction($payment
                        , \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND
                        , $result->breadTransactionId
                        , array('is_closed' => false, 'refund_result' => json_encode($result))
                        , array()
                        , "Bread Finance Payment Refunded");
                break;
            case self::ACTION_VOID:
                $result     = $this->getApiClient()->cancel(str_replace('-void','',$payment->getTransactionId()));
                    $payment->setTransactionId($result->breadTransactionId);
                    $this->addTransaction($payment
                        , \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID
                        , $result->breadTransactionId
                        , array('is_closed' => true, 'cancel_result' => json_encode($result))
                        , array()
                        , "Bread Finance Payment Canceled");
                break;
            default:

                break;
        }

        $payment->setSkipTransactionCreation(true);

        return $result;
    }

    /**
     * Add payment transaction
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param string $transactionId
     * @param string $transactionType
     * @param array $breadTransactionId
     * @param array $transactionAdditionalInfo
     * @return null|\Magento\Sales\Model\Order\Payment\Transaction
     */
    protected function addTransaction(\Magento\Sales\Model\Order\Payment $payment, $transactionType,
                                       $breadTransactionId , $transactionAdditionalInfo = array(),
                                       $transactionDetails = array(), $message = false
    ) {
        $payment->resetTransactionAdditionalInfo();

        foreach ($transactionAdditionalInfo as $key => $value) {
            $payment->setTransactionAdditionalInfo($key, $value);
        }

        $transaction = $payment->addTransaction($transactionType, null, false , $message);
        $transaction->setOrderPaymentObject($payment);

        if(array_key_exists('is_closed', $transactionAdditionalInfo)){
            $transaction->setIsClosed( (bool) $transactionAdditionalInfo["is_closed"] );
        }

        foreach ($transactionDetails as $key => $value) {
            $payment->unsetData($key);
        }

        $payment->unsLastTransId();

        $transaction->setMessage($message);

        $transaction->save();

        return $transaction;
    }

    /**
     * Is the 'breadcheckout' payment method available
     *
     * @param null $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $this->logger->debug($quote->getBreadTransactionId());
        if( !$quote->getBreadTransactionId() ){
            return true;
        }

        if( !parent::isAvailable($quote) ){
            return false;
        }

        return true;
    }

}