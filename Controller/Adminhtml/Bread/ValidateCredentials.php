<?php

namespace Bread\BreadCheckout\Controller\Adminhtml\Bread;

/**
 * Class ValidateCredentials
 *
 * @package Bread\BreadCheckout\Controller\Adminhtml
 */
class ValidateCredentials extends \Magento\Backend\App\Action {

    const API_SANDBOX_URI = 'https://api-sandbox.getbread.com/carts/';
    const API_LIVE_URI = 'https://api.getbread.com/carts/';

    /**
     * @var \Bread\BreadCheckout\Helper\Log
     */
    public $logger;

    /**
     *
     * @var type \Magento\Framework\App\Config\Storage\WriterInterface
     */
    public $configWriter;

    public function __construct(
            \Magento\Backend\App\Action\Context $context,
            \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
            \Bread\BreadCheckout\Helper\Log $log
    ) {
        $this->logger = $log;
        $this->configWriter = $configWriter;
        parent::__construct($context);
    }

    public function execute() {

        $params = $this->getRequest()->getParams();
        $result = $this->testCredentials($params['apiMode'], $params['apiVersion'], $params['apiUrl'], $params['pubKey'], $params['secKey']);

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData($result);
    }

    private function testCredentials($apiMode, $apiVersion, $apiUrl, $username, $password) {
        if ($apiVersion === 'bread_2') {
            try {
                $data = array(
                    'apiKey' => "$username",
                    'secret' => "$password"
                );
                $curl = curl_init($apiUrl);
                curl_setopt($curl, CURLOPT_HEADER, 0);
                curl_setopt($curl, CURLOPT_TIMEOUT, 30);

                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt(
                    $curl,
                    CURLOPT_HTTPHEADER,
                    [
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen(json_encode($data))
                    ]
                );
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                $result = curl_exec($curl);
                $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                if ($status != 200) {
                    $this->logger->log('Failed keys validation');
                    return false;
                } else {
                    $response = json_decode($result);
                    if(isset($response['token'])) {
                        $this->configWriter->save('bread_auth_token', $response['token'],'store');
                    }
                    return true;
                }


            } catch (Exception $ex) {
                $this->logger->log(
                    [
                        'STATUS' => 'BACKEND API KEYS TEST',
                        'RESULT' => $ex->getMessage()
                    ]
                );

                curl_close($curl);
            }
        } else {
            $dummy = [];
            $dummy['expiration'] = date('Y-m-d');
            $dummy['options'] = [];
            $dummy['options']['cartName'] = 'API Key Validation';
            $dummy['options']['customTotal'] = 10000;

            $url = (bool) $apiMode ? self::API_LIVE_URI : self::API_SANDBOX_URI;

            try {
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_HEADER, 0);
                curl_setopt($curl, CURLOPT_USERPWD, $username . ':' . $password);
                curl_setopt($curl, CURLOPT_TIMEOUT, 30);

                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt(
                        $curl,
                        CURLOPT_HTTPHEADER,
                        [
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen(json_encode($dummy))
                        ]
                );
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($dummy));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                $result = curl_exec($curl);
                $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                if ($status != 200) {
                    $this->logger->log('Failed keys validation');
                    return false;
                } else {
                    return true;
                }
            } catch (\Exception $e) {
                $this->logger->log(
                        [
                            'STATUS' => 'BACKEND API KEYS TEST',
                            'RESULT' => $result
                        ]
                );

                curl_close($curl);
            }
        }
    }

}
