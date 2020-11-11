<?php

namespace Bread\BreadCheckout\Controller\Adminhtml\Bread;

class GenerateCart extends \Magento\Backend\App\Action
{
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

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Bread\BreadCheckout\Helper\Quote $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Bread\BreadCheckout\Model\Payment\Api\Client $paymentApiClient,
        \Bread\BreadCheckout\Helper\Customer $customerHelper,
        \Bread\BreadCheckout\Model\Payment\Method\Bread $breadMethod,
        \Bread\BreadCheckout\Helper\Url $urlHelper
    ) {
        $this->resultFactory = $context->getResultFactory();
        $this->helper = $helper;
        $this->config = $scopeConfig;
        $this->paymentApiClient = $paymentApiClient;
        $this->customerHelper = $customerHelper;
        $this->breadMethod = $breadMethod;
        $this->urlHelper = $urlHelper;
        parent::__construct($context);
    }

    /**
     * Generate cart from backend
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store_id');

        try {
            $quote = $this->helper->getSessionQuote();

            $ret = [ 'error'       => false,
                     'successRows' => [],
                     'errorRows'   => [],
                     'cartUrl'     => ''
            ];

            if (!$quote || ($quote && $quote->getItemsQty() == 0)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Cart is empty'));
            }

            if ($quote->getPayment()->getMethodInstance()->getCode() != $this->breadMethod->getMethodCode()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('In order to checkout with bread you must choose bread as payment option.'));
            }

            if (!$this->helper->getShippingOptions()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please specify a shipping method.'));
            }

            $arr = [];

            $arr['expiration'] = date(
                'Y-m-d',
                strtotime(
                    '+' . $this->config->getValue(
                        'checkout/cart/delete_quote_after',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    ) . 'days'
                )
            );

            $arr['cartOrigin'] = 'magento_carts';

            $arr['options'] = [];
            $arr['options']['orderRef'] = $quote->getId();

            $arr['options']['completeUrl'] = $this->urlHelper->getLandingPageURL();
            $arr['options']['errorUrl'] = $this->urlHelper->getLandingPageURL(true);
            $arr['options']['disableEditShipping'] = true;

            $arr['options']['shippingOptions'] = [ $this->helper->getShippingOptions() ];
            $arr['options']['shippingContact'] = $this->helper->getShippingAddressData();
            $arr['options']['billingContact'] = $this->helper->getBillingAddressData();

            if (!$this->helper->isHealthcare($storeId) && !$quote->getUseRewardPoints()) {
                $arr['options']['items'] = $this->helper->getQuoteItemsData();
            } else {
                $arr['options']['customTotal'] = round($quote->getGrandTotal() * 100);
            }

            $arr['options']['discounts'] = $this->helper->getDiscountData() ? $this->helper->getDiscountData() : [];
            if($quote->getUseRewardPoints()){
                array_push($arr['options']['discounts'],
                    array(
                        'amount' => round($quote->getRewardCurrencyAmount() * 100),
                        'description' => __('Reward Points')
                    ));
            }

            $arr['options']['tax'] = $this->helper->getTaxValue();

            if ($this->helper->isTargetedFinancing($storeId) && $this->helper->checkFinancingMode('cart', $storeId)) {
                $financingId = $this->helper->getFinancingId($storeId);
                $threshold = $this->helper->getTargetedFinancingThreshold($storeId);

                $arr['options']['financingProgramId'] = $quote->getGrandTotal() >= $threshold ? $financingId : null;
            } elseif ($this->helper->isTargetedFinancing($storeId)
                && $this->helper->checkFinancingMode('sku', $storeId)
                && $this->helper->isFinancingBySku($storeId)
            ) {
                $arr['options']['financingProgramId'] = $this->helper->getFinancingId($storeId);
            }

            $result = $this->paymentApiClient->submitCartData($arr);

            $ret['successRows'] = [
                __('Cart with Financing was successfully created.'),
                __('Following link can be used by your customer to complete purchase.'),
                sprintf('<a href="%1$s">%1$s</a>', $result["url"])
            ];

            $ret['cartUrl'] = $result['url'];
            $ret['id'] = $result['id'];
        } catch (\Throwable $e) {
            $ret['error'] = true;
            $ret['errorRows'][] = __('There was an error in cart creation:');
            $ret['errorRows'][] = $e->getMessage();
        }
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData($ret);
    }
}
