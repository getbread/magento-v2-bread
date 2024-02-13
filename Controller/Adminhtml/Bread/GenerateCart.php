<?php

namespace Bread\BreadCheckout\Controller\Adminhtml\Bread;

class GenerateCart extends \Magento\Backend\App\Action {

    /**
     * @var \Bread\BreadCheckout\Helper\Quote
     */
    public $helper;
    public $cart;
    public $config;
    public $paymentApiClient;
    public $customerHelper;
    public $breadMethod;
    public $urlHelper;

    /**
     * @var $logger
     */
    public $logger;

    /**
     * 
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Bread\BreadCheckout\Helper\Quote $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient
     * @param \Bread\BreadCheckout\Helper\Customer $customerHelper
     * @param \Bread\BreadCheckout\Model\Payment\Method\Bread $breadMethod
     * @param \Bread\BreadCheckout\Helper\Url $urlHelper
     * @param \Bread\BreadCheckout\Helper\Log $log
     */
    public function __construct(
            \Magento\Backend\App\Action\Context $context,
            \Bread\BreadCheckout\Helper\Quote $helper,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient,
            \Bread\BreadCheckout\Helper\Customer $customerHelper,
            \Bread\BreadCheckout\Model\Payment\Method\Bread $breadMethod,
            \Bread\BreadCheckout\Helper\Url $urlHelper,
            \Bread\BreadCheckout\Helper\Log $log
    ) {
        $this->resultFactory = $context->getResultFactory();
        $this->helper = $helper;
        $this->config = $scopeConfig;
        $this->paymentApiClient = $paymentApiClient;
        $this->customerHelper = $customerHelper;
        $this->breadMethod = $breadMethod;
        $this->urlHelper = $urlHelper;
        $this->logger = $log;
        parent::__construct($context);
    }

    /**
     * Generate cart from backend
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute() {
        $this->logger->info('Generate Bread Cart');
        $storeId = $this->getRequest()->getParam('store_id');
        $storeCode = $this->getRequest()->getParam('store_code');
        $this->logger->info('Store Code: ' . $this->getRequest()->getParam('store_code'));

        
        $ret = ['error' => false,
            'successRows' => [],
            'errorRows' => [],
            'cartUrl' => ''
        ];

        try {
            $apiVersion = $this->helper->getApiVersion();
            $this->logger->info('API version:: ' . $apiVersion);
            //Check for Errors
            $quote = $this->helper->getSessionQuote();

            if (!$quote || ($quote && $quote->getItemsQty() == 0)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Cart is empty'));
            }

            if ($quote->getPayment()->getMethodInstance()->getCode() != $this->breadMethod->getMethodCode()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('In order to checkout with bread you must choose bread as payment option.'));
            }

            if (!$this->helper->getShippingOptions()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please specify a shipping method.'));
            }
            
            //Build Request
            $request = [];
                      
            //Build 2.0 specific data
            if($apiVersion === 'bread_2') {    
                $orderRef = $quote->getId();
                $this->logger->info('Bread 2.0');
                //Callbacks
                $request['callbackURL'] = $this->urlHelper->getLandingPageURL() . '?orderRef=' . $orderRef . '&action=callback';
                $request['checkoutCompleteUrl'] = $this->urlHelper->getLandingPageURL(). '?orderRef=' . $orderRef . '&action=checkout-complete';
                $request['checkoutErrorUrl'] = $this->urlHelper->getLandingPageURL(). '?orderRef=' . $orderRef . '&action=checkout-error';
                
                //Hipaa Restriction
                $request['isHipaaRestricted'] = true;
                
                //Order Identifier
                $request['orderReference'] = $orderRef;
                
                //Format the date to RFC-3339
                $datetime = \DateTime::createFromFormat("Y-m-d H:i:s", date('Y-m-d H:i:s', strtotime('+7 days')));
                $request['expiresAt'] = $datetime->format(\DateTimeInterface::RFC3339);
                
                //Include MerchantID + programID
                $request['merchantID'] = $this->customerHelper->getMerchantId();
                $request['programID'] = $this->customerHelper->getProgramId();
                
                $merchantCountry = $this->customerHelper->getMerchantCountry();
                $currency = $this->customerHelper->getCurrentCurrencyCode();
                
                //Contact
                $billingAddress = $this->helper->getBillingAddressData();
                $request['contact']['name'] = [
                    'givenName' => $billingAddress['firstName'],
                    'familyName' => $billingAddress['lastName']
                ];
                $request['contact']['phone'] = $billingAddress['phone'];
                $request['contact']['billingAddress'] = [
                    'address1' => $billingAddress['address'],
                    'address2' => $billingAddress['address2'],
                    'locality' => $billingAddress['city'],
                    'postalCode' => $billingAddress['zip'],
                    'region' => $billingAddress['state'],
                    'country' => $merchantCountry
                ];
                $shippingAddress = $this->helper->getShippingAddressData();
                $request['contact']['shippingAddress'] = [
                    'address1' => $shippingAddress['address'],
                    'address2' => $shippingAddress['address2'],
                    'locality' => $shippingAddress['city'],
                    'postalCode' => $shippingAddress['zip'],
                    'region' => $shippingAddress['state'],
                    'country' => $merchantCountry
                ];
                $request['contact']['email'] = $billingAddress['email'];
                
                //Populate items
                $itemList = $this->helper->getQuoteItemsData();
                
                $items = array();
                $total = 0;
                foreach($itemList as $item) {
                    $items[] = [
                        'name' => "'" . $item['name'] . "'",
                        'category' => "",
                        'quantity' => $item['quantity'],
                        'unitPrice' => [
                            'currency' => "$currency",
                            'value' => $item['price']
                        ],
                        'unitTax' => [
                            'currency' => "$currency",
                            'value' => 0
                        ],
                        'sku' => "'".$item['sku']."'",
                        'itemUrl' => "'".$item['detailUrl']."'",
                        'imageUrl' => "",
                        'description' => "",
                        'shippingCost' => [
                            'currency' => "$currency",
                            'value' => 0
                        ],
                        'shippingProvider' => "",
                        'shippingDescription' => "",
                        'shippingTrackingNumber' => "",
                        'shippingTrackingUrl' => ""
                    ];
                    $total += $item['quantity'] * $item['price'];
                }
                
                //Order details
                $discountData = $this->helper->getDiscountData();
                $request['order'] = [
                  'subTotal' => [
                      'currency' => $currency,
                      'value' => $total
                  ],
                  'totalDiscounts' => [
                      'currency' => $currency,
                      'value' => isset($discountData['amount']) ? $discountData['amount'] : 0
                  ],
                  'totalPrice' => [
                      'currency' => $currency,
                      'value' => round($quote->getGrandTotal() * 100)
                  ],  
                  'totalShipping' => [
                      'currency' => $currency,
                      'value' => round($quote->getShippingAddress()->getShippingAmount() * 100)
                  ],
                  'totalTax' => [
                      'currency' => $currency,
                      'value' => $this->helper->getTaxValue()
                  ],  
                ];
                
                $request['order']['items'] = $items;
                
                $acceptedAt = \DateTime::createFromFormat("Y-m-d H:i:s", date('Y-m-d H:i:s'));
                $request['disclosures'][] = [
                  'name' => 'one-time',
                  'acceptedAt' => $acceptedAt->format(\DateTimeInterface::RFC3339)  
                ];
                
                $this->logger->log('Request: ' . json_encode($request));
                $result = $this->paymentApiClient->submitPlatformCartData($request);
                $this->logger->log('Response: ' . json_encode($result));

                $ret['successRows'] = [
                    __('Cart with Financing was successfully created.'),
                    __('Following link can be used by your customer to complete purchase.'),
                    sprintf('<a href="%1$s">%1$s</a>', $result["checkoutUrl"])
                ];

                $ret['cartUrl'] = $result["checkoutUrl"];
                $ret['id'] = $result['id'];
                
                
            } else {
                //Build other classic data
                $request['cartOrigin'] = 'magento_carts';
                
                $request['expiration'] = date(
                        'Y-m-d',
                        strtotime(
                                '+' . $this->config->getValue(
                                        'checkout/cart/delete_quote_after',
                                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                                ) . 'days'
                        )
                );
                
                $request['options'] = [];
                $request['options']['orderRef'] = $quote->getId();

                $request['options']['completeUrl'] = $this->urlHelper->getLandingPageURL();
                $request['options']['errorUrl'] = $this->urlHelper->getLandingPageURL(true);
                $request['options']['disableEditShipping'] = true;

                $request['options']['shippingOptions'] = [$this->helper->getShippingOptions()];
                $request['options']['shippingContact'] = $this->helper->getShippingAddressData();
                $request['options']['billingContact'] = $this->helper->getBillingAddressData();

                if (!$this->helper->isHealthcare($storeId) && !$quote->getUseRewardPoints()) {
                    $request['options']['items'] = $this->helper->getQuoteItemsData();
                } else {
                    $request['options']['customTotal'] = round($quote->getGrandTotal() * 100);
                }

                $request['options']['discounts'] = $this->helper->getDiscountData() ? $this->helper->getDiscountData() : [];
                if ($quote->getUseRewardPoints()) {
                    array_push($request['options']['discounts'],
                            array(
                                'amount' => round($quote->getRewardCurrencyAmount() * 100),
                                'description' => __('Reward Points')
                    ));
                }

                $request['options']['tax'] = $this->helper->getTaxValue();

                if ($this->helper->isTargetedFinancing($storeId) && $this->helper->checkFinancingMode('cart', $storeId)) {
                    $financingId = $this->helper->getFinancingId($storeId);
                    $threshold = $this->helper->getTargetedFinancingThreshold($storeId);

                    $request['options']['financingProgramId'] = $quote->getGrandTotal() >= $threshold ? $financingId : null;
                } elseif ($this->helper->isTargetedFinancing($storeId) && $this->helper->checkFinancingMode('sku', $storeId) && $this->helper->isFinancingBySku($storeId)
                ) {
                    $request['options']['financingProgramId'] = $this->helper->getFinancingId($storeId);
                }

                $this->logger->log('Request: ' . $request);
                $result = $this->paymentApiClient->submitCartData($request);
                $this->logger->log('Response: ' . $result);

                $ret['successRows'] = [
                    __('Cart with Financing was successfully created.'),
                    __('Following link can be used by your customer to complete purchase.'),
                    sprintf('<a href="%1$s">%1$s</a>', $result["url"])
                ];

                $ret['cartUrl'] = $result['url'];
                $ret['id'] = $result['id'];
            }
            
            
        } catch (\Throwable $e) {
            $ret['error'] = true;
            $ret['errorRows'][] = __('There was an error in cart creation:');
            $ret['errorRows'][] = $e->getMessage();
        }
        
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData($ret);
    }

}
