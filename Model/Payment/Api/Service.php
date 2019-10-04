<?php

namespace Bread\BreadCheckout\Model\Payment\Api;

use Bread\BreadCheckout\Log\Logger;
use Bread\BreadCheckout\Helper\Config;
use InvalidArgumentException;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

class Service extends DataObject implements ServiceInterface
{

    public $order = null;

    /**
     * @var Json
     */
    public $jsonSerializer;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * Service constructor.
     * @param Json $jsonSerializer
     * @param StoreManagerInterface $storeManager
     * @param Logger $log
     * @param Config $configHelper
     * @param array $data
     */
    public function __construct(
        Json $jsonSerializer,
        StoreManagerInterface $storeManager,
        Logger $log,
        Config $configHelper,
        array $data = []
    ) {
        parent::__construct($data);
        $this->jsonSerializer = $jsonSerializer;
        $this->storeManager = $storeManager;
        $this->logger = $log;
        $this->configHelper = $configHelper;
    }

    /**
     * @param Order $order
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Cancel bread order
     *
     * @param $breadTransactionId
     * @param int $amount
     * @param array $lineItems
     * @return mixed
     * @throws LocalizedException
     * @throws \Throwable
     */
    public function cancel($breadTransactionId, $amount = 0, $lineItems = [])
    {
        /* Check if already canceled in bread */
        $transaction = $this->getInfo($breadTransactionId);
        if($transaction['status'] === self::STATUS_CANCELED){
            return $transaction;
        }

        $data = ['type' => 'cancel'];

        if (!$amount == 0) {
            $data['amount'] = $amount;
        }

        if (!empty($lineItems)) {
            $data['lineItems'] = $lineItems;
        }

        $result = $this->call($this->makeApiUrlFor('update_transaction', $breadTransactionId), $data);

        if ($result['status'] != self::STATUS_CANCELED) {
            $this->logger->write(['ERROR'=>'Transaction cancel failed', 'RESULT'=>$result]);
            throw new LocalizedException(
                __('Transaction cancel failed (current transaction status :' . $result->status . ')')
            );
        }

        return $result;
    }

    /**
     * Call API Authorize Method
     *
     * @param string $breadTransactionId
     * @param string $amount
     * @param string|null $merchantOrderId
     * @return array
     * @throws LocalizedException
     * @throws \Throwable
     */
    public function authorize($breadTransactionId, $amount, $merchantOrderId = null)
    {
        $validateAmount = $this->getInfo($breadTransactionId);

        if ($this->amountMismatch($validateAmount['total'], $amount)) {
            $this->logger->write(
                [
                    'ERROR'         => 'BREAD AMOUNT AND QUOTE AMOUNT MIS-MATCH',
                    'BREAD AMOUNT'  => $validateAmount['total'],
                    'QUOTE AMOUNT'  => $amount,
                    'RESULT'        => $validateAmount
                ]
            );
            throw new LocalizedException(
                __('Bread authorized amount ' . $validateAmount['total'] . ' but transaction expected ' . $amount)
            );
        }

        $data_array = ['type' => 'authorize'];
        if ($merchantOrderId != null) {
            $data_array['merchantOrderId'] = $merchantOrderId;
        }

        $result = $this->call(
            $this->makeApiUrlFor('update_transaction', $breadTransactionId), $data_array
        );

        if ($result['status'] != self::STATUS_AUTHORIZED) {
            $this->logger->write(['ERROR'=>'AUTHORIZATION FAILED', 'RESULT'=>$result]);
            throw new LocalizedException(
                __('Transaction authorize failed (current transaction status :' . $result->status . ')')
            );
        }

        return $result;
    }

    /**
     * Compares amount authorized on bread side versus amount on magento order
     *
     * @param string $breadAmount
     * @param string $magentoAmount
     * @return bool
     */
    private function amountMismatch($breadAmount,$magentoAmount) : bool
    {
        $amountMismatch = false;

        $breadAmount = (int)trim($breadAmount);
        $magentoAmount = (int)trim($magentoAmount);

        if(($breadAmount !=  $magentoAmount) && (abs($breadAmount - $magentoAmount) >= 2)){
            $amountMismatch = true;
        }

        return $amountMismatch;
    }

    /**
     * Call API update order id
     *
     * @param string $breadTransactionId
     * @param string $merchantOrderId
     * @return mixed
     * @throws \Throwable
     */
    public function updateOrderId($breadTransactionId, string $merchantOrderId)
    {
        $result = $this->call(
            $this->makeApiUrlFor('info', $breadTransactionId),
            ['merchantOrderId' => $merchantOrderId],
            \Magento\Framework\HTTP\ZendClient::PUT
        );

        return $result;
    }

    /**
     * Call API update order id capture authorized transaction
     *
     * @param string $breadTransactionId
     * @return mixed
     * @throws \Throwable
     */
    public function settle($breadTransactionId)
    {
        $result = $this->call(
            $this->makeApiUrlFor('update_transaction', $breadTransactionId), ['type' => 'settle']
        );

        if ($result['status'] != self::STATUS_SETTLED) {
            throw new LocalizedException(
                __('Transaction settle failed (current transaction status :' . $result['status'] . ')')
            );
        }

        return $result;
    }

    /**
     * Call API refund
     *
     * @param string $breadTransactionId
     * @param int $amount
     * @param array $lineItems
     * @return array
     * @throws \Throwable
     */
    public function refund($breadTransactionId, $amount = 0, $lineItems = [])
    {
        $data = ['type' => 'refund'];

        if (!$amount == 0) {
            $data['amount'] = $amount;
        }

        if (!empty($lineItems)) {
            $data['lineItems'] = $lineItems;
        }

        return $this->call($this->makeApiUrlFor('update_transaction', $breadTransactionId), $data);
    }

    /**
     * Call API get info
     *
     * @param $breadTransactionId
     * @return array
     * @throws \Throwable
     */
    public function getInfo($breadTransactionId)
    {
        return $this->call(
            $this->makeApiUrlFor('info', $breadTransactionId), [], \Magento\Framework\HTTP\ZendClient::GET
        );
    }

    /**
     * Submit cart data
     *
     * @param array $data
     * @return array
     * @throws \Throwable
     */
    public function submitCartData($data)
    {
        return $this->call(
            $this->makeApiUrlFor('cart_create'), $data, \Magento\Framework\HTTP\ZendClient::POST
        );
    }

    /**
     * Interact with the API
     *
     * @param $url
     * @param array $data
     * @param string $method
     * @return array
     * @throws \Throwable
     */
    protected function call($url, array $data, $method = \Zend_Http_Client::POST)
    {
        $username   = $this->configHelper->getApiPublicKey();
        $password   = $this->configHelper->getApiSecretKey();

        try {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_USERPWD, $username . ':' . $password);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);

            if ($method == \Magento\Framework\HTTP\ZendClient::POST) {
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($this->jsonSerializer->serialize($data))]);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $this->jsonSerializer->serialize($data));
            }

            if ($method == \Magento\Framework\HTTP\ZendClient::PUT) {
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $this->jsonSerializer->serialize($data));
            }

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($status != 200) {
                $this->logger->write(curl_error($curl));

                //TODO: rewrite this when API is updated to better handle errors, instead of searching through the description string
                $isSplitPayDecline = strpos($result, "There's an issue with authorizing the credit card portion") !== false;

                if ($isSplitPayDecline) {

                    if($this->configHelper->getConfigData('split_auto_cancel', true)){
                        $this->cancel($this->getBreadTransactionId());
                    }

                    $errorMessage = 'The credit/debit card portion of your transaction was declined. '
                        . 'Please use a different card or contact your bank. Otherwise, you can still check out with '
                        . 'an amount covered by your Bread loan capacity.';
                } else {
                    $errorMessage = 'Call to Bread API failed.';
                }

                throw new \Magento\Framework\Exception\LocalizedException(
                    __($errorMessage)
                );
            }
        } catch (\Throwable $e) {
            $this->logger->write([
                'USER'      => $username,
                'PASSWORD'  => $password,
                'URL'       => $url,
                'STATUS'    => $status,
                'DATA'      => $data,
                'RESULT'    => $result
            ]);

            curl_close($curl);
            throw $e;
        }

        curl_close($curl);

        $this->logger->write(
            [
                'USER'      => $username,
                'PASSWORD'  => $password,
                'URL'       => $url,
                'DATA'      => $data,
                'RESULT'    => $result
            ]
        );

        if (!$this->isJson($result)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('API Response Is Not Valid JSON.  Result: ' . $result)
            );
        }

        return $this->jsonSerializer->unserialize($result);
    }

    /**
     * Send cart sms
     *
     * @param string $cartId
     * @param string $phone
     * @return mixed
     * @throws \Throwable
     */
    public function sendSms($cartId, $phone)
    {
        $sendSmsUrl = $this->makeApiUrlFor('send_sms', $cartId);
        $data = array('phone' => $phone);
        return $this->call(
            $sendSmsUrl, $data, \Magento\Framework\HTTP\ZendClient::POST
        );
    }

    /**
     * Send cart email
     *
     * @param string $cartId
     * @param string $email
     * @param string $name
     * @return  mixed
     * @throws \Throwable
     */
    public function sendEmail($cartId, $email, $name)
    {
        $sendEmailUrl = $this->makeApiUrlFor('send_email', $cartId);
        $data = array('email' => $email, 'name' => $name);
        return $this->call(
            $sendEmailUrl, $data, \Magento\Framework\HTTP\ZendClient::POST
        );
    }

    /**
     * Call to set shipping details on bread order
     *
     * @param $transactionId
     * @param $trackingNumber
     * @param $carrierName
     * @return mixed
     * @throws \Throwable
     */
    public function setShippingDetails($transactionId, $trackingNumber, $carrierName)
    {
        $updateShippingDetailsUrl = $this->makeApiUrlFor('update_shipping', $transactionId);
        $data = array('trackingNumber' => $trackingNumber, 'carrierName' => $carrierName);
        return $this->call(
            $updateShippingDetailsUrl, $data, \Magento\Framework\HTTP\ZendClient::POST
        );
    }

    /**
     * Use the “As low as” endpoint to calculate an “as low as” amount with compliant
     * financing disclosure based on your default or alternate financing program.
     *
     * @param array $data
     * @return mixed
     * @throws \Throwable
     */
    public function getAsLowAs($data)
    {
        $asLowAsUrl = $this->makeApiUrlFor('aslowas');

        return $this->call(
            $asLowAsUrl,
            $data,
            \Magento\Framework\HTTP\ZendClient::POST
        );
    }


    /**
     * Makes full api url path for given action
     *
     * @param $value
     * @param null|string $breadTransactionId
     * @return string
     */
    public function makeApiUrlFor($value, $breadTransactionId = null): string
    {
        $requestedAction    = sprintf(self::API_ACTIONS[$value], $breadTransactionId);
        $transactionApiUrl  = $this->configHelper->getTransactionApiEndpoint();

        return $transactionApiUrl . $requestedAction;
    }

    /**
     * Check a string to verify JSON format is valid
     *
     * @param $string
     * @return bool
     */
    protected function isJson($string): bool
    {
        $isJson = true;

        try {
            $this->jsonSerializer->unserialize($string);
        } catch (InvalidArgumentException $e) {
            $isJson = false;
        }

        return $isJson;
    }

    /**
     * Wrapper for get store id
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getStoreId();
    }
}
