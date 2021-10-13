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
     * @var \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     */
    public $configWriter;
    
    /**
     *
     * @var \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public $jsonHelper;

    /**
     * ValidateCredentials constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Bread\BreadCheckout\Helper\Log $log
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Bread\BreadCheckout\Helper\Log $log,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\Json\Helper\Data $jsonHelper    
    ) {
        $this->logger = $log;
        $this->configWriter = $configWriter;
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context);
    }

    public function execute()
    {

        $params = $this->getRequest()->getParams();
        $this->logger->log($params);
        $result = $this->testCredentials($params['apiMode'], $params['pubKey'], $params['secKey'], $params['apiVersion']);

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData($result);
    }

    private function testCredentials($apiMode, $username, $password, $apiVersion)
    {
        if($apiVersion === 'bread_2') {
            try {
                $data = array(
                    'apiKey' => "$username",
                    'secret' => "$password"
                );
                $apiUrls = $this->jsonHelper->getApiUrls();
                
                foreach ($apiUrls as $apiUrl => $tenant) {
                    $url = join('/', [ trim($apiUrl, '/'), 'auth/sa/authenticate' ]);
                    $curl = curl_init($url);
                    curl_setopt($curl, CURLOPT_HEADER, 0);
                    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt(
                        $curl,
                        CURLOPT_HTTPHEADER,
                        [
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($this->jsonHelper->jsonEncode($data))
                        ]
                    );
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
                    $result = curl_exec($curl);
                    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    curl_close($curl);
                    if ($status != 200) {
                        $this->logger->log('Failed keys validation for: ', $apiUrl);
                        continue;
                    }
                    $this->configWriter->save('payment/breadcheckout/tenant', $tenant, "default");
                    $response = (array) $this->jsonHelper->jsonDecode($result);
                    if(isset($response['token'])) {
                        $this->configWriter->save('payment/breadcheckout/bread_auth_token', $response['token'],'default');
                        return true;
                    }
                    break;  
                }
                // Case for all api urls calls returning status != 200 or token is not set in response
                $this->configWriter->save('payment/breadcheckout/bread_auth_token', "0",'default');
                $this->configWriter->save('payment/breadcheckout/tenant', "CORE", "default");
                return false;

            } catch (Exception $ex) {
                $this->logger->log(
                    [
                        'STATUS' => 'BACKEND API KEYS TEST',
                        'RESULT' => $ex->getMessage()
                    ]
                );

                curl_close($curl);
                return false;
            }
        } else {
            $dummy = [];
            $dummy['expiration'] = date('Y-m-d');
            $dummy['options'] = [];
            $dummy['options']['cartName'] = 'API Key Validation';
            $dummy['options']['customTotal'] = 10000;

            $url = (bool)$apiMode ? self::API_LIVE_URI : self::API_SANDBOX_URI;

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
                        'STATUS'    => 'BACKEND API KEYS TEST',
                        'RESULT'    => $result

                    ]
                );

                curl_close($curl);
            }
        }
    }
}
