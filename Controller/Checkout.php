<?php
/**
 * Handles Checking Out From The Product Page
 *
 * @author  Bread   copyright 2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller;

abstract class Checkout extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Catalog\Model\ResourceModel\ProductFactory */
    protected $catalogResourceModelProductFactory;
    
    /** @var \Magento\Framework\DataObjectFactory */
    protected $dataObjectFactory;

    /** @var \Magento\Checkout\Model\Cart */
    protected $checkoutSession;

    /** @var \Magento\Quote\Model\QuoteFactory */
    protected $quoteFactory;

    /** @var \Magento\Catalog\Model\ProductFactory */
    protected $catalogProductFactory;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Bread\BreadCheckout\Helper\Data */
    protected $helper;

    /** @var \Magento\Framework\Controller\ResultFactory */
    protected $resultFactory;

    /** @var \Magento\Quote\Model\Quote\TotalsCollector */
    protected $totalsCollector;

    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    protected $quoteRepository;

    protected $customerSession;
    protected $quoteManagement;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\ResourceModel\ProductFactory $catalogResourceModelProductFactory,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Psr\Log\LoggerInterface $logger,
        \Bread\BreadCheckout\Helper\Data $helper,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Model\QuoteManagement $quoteManagement
    )
    {
        $this->catalogResourceModelProductFactory = $catalogResourceModelProductFactory;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->checkoutSession = $checkoutSession;
        $this->quoteFactory = $quoteFactory;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->totalsCollector = $totalsCollector;
        $this->quoteRepository = $quoteRepository;
        $this->customerSession = $customerSession;
        $this->quoteManagement = $quoteManagement;
        $this->resultFactory = $context->getResultFactory();
        parent::__construct($context);
    }
    
    /**
     * Add Item To Quote
     *
     * @param \Magento\Quote\Model\Quote       $quote
     * @param \Magento\Catalog\Model\Product   $product
     * @param \Magento\Catalog\Model\Product   $baseProduct
     * @param array                            $customOptionPieces
     * @param int                              $quantity
     */
    protected function addItemToQuote(\Magento\Quote\Model\Quote $quote,
                                      \Magento\Catalog\Model\Product $product,
                                      \Magento\Catalog\Model\Product $baseProduct,
                                      array $customOptionPieces,
                                      $quantity)
    {
        $productId          = $product->getId();
        $baseProductId      = $baseProduct->getId();
        $buyInfo            = ['qty' =>  $quantity];

        if ($baseProductId != $productId)
        {
            /** @var $catalogResource \Magento\Catalog\Model\ResourceModel\ProductFactory */
            $catalogResource            = $this->catalogResourceModelProductFactory->create();
            $options                    = [];
            $productAttributeOptions    = $baseProduct->getTypeInstance(true)->getConfigurableAttributesAsArray($baseProduct);
            foreach ($productAttributeOptions as $option){
                $options[$option['attribute_id']]   =
                    $catalogResource->getAttributeRawValue($productId, $option['attribute_id'], null);
            }

            $buyInfo['super_attribute']     = $options;
        }

        $counter    = 0;
        if (count($customOptionPieces) > 1) {
            $customOptionConfig     = [];
            foreach ($customOptionPieces as $customOption) {
                $counter++;
                if ($counter == 1) continue;

                $optionKeyValue     = explode('===', $customOption);
                $found              = false;

                foreach ($baseProduct->getOptions() as $o) {
                    if ($found) break;

                    $values     = $o->getValues();
                    if (count($values) > 0) {
                        foreach ($values as $v) {
                            if ($v['sku'] == $optionKeyValue[0]) {
                                if (array_key_exists($v->getOptionId(), $customOptionConfig)) {
                                    $customOptionConfig[$v->getOptionId()]  =
                                        $customOptionConfig[$v->getOptionId()].','.$v->getOptionTypeId();
                                }
                                else {
                                    $customOptionConfig[$v->getOptionId()]  = $v->getOptionTypeId();
                                }

                                $found      = true;
                                break;
                            }
                        }
                    } else {
                        if ($o['sku'] == $optionKeyValue[0]) {
                            $customOptionConfig[$o->getOptionId()]  = $optionKeyValue[1];
                            $found      = true;
                        }
                    }
                }
            }

            $buyInfo['options']     = $customOptionConfig;
        }

        $buyRequest = $this->dataObjectFactory->create();
        $buyRequest->addData($buyInfo);

        $quote->addProduct($baseProduct, $buyRequest);

       return $quote;
    }

    /**
     * Collect Totals Tax and Shipping Estimate Actions
     *
     * @param array $data
     * @return \Magento\Quote\Model\Quote\Address
     */
    protected function getShippingAddressForQuote(array $data)
    {
        try {
            $quote      = $this->getQuote($data);
            $address    = $quote->getShippingAddress();

            $address->setCountryId($this->helper->getDefaultCountry())
                ->setCity($data['city'])
                ->setPostcode($data['zip'])
                ->setRegion($data['state'])
                ->setCollectShippingRates(true);

            if (isset($data['selectedShippingOption']) && isset($data['selectedShippingOption']['typeId'])) {
                $address->setShippingMethod($data['selectedShippingOption']['typeId']);
            }

            $this->totalsCollector->collectAddressTotals($quote, $address);
            $quote->collectTotals();
            $this->quoteRepository->save($quote);

            return $address;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData([
                'result' => ['error' => 1,
                'text'  => 'Internal error']]);
        }
    }

    /**
     * Get existing or generate new quote from supplied data
     *
     * @param array $data
     * @return \Magento\Quote\Model\Quote
     */
    protected function getQuote(array $data)
    {
        $requestCode    = $data['block_key'];

        switch ($requestCode) {
            case \Bread\BreadCheckout\Helper\Data::BLOCK_CODE_CHECKOUT_OVERVIEW :
                $quote      = $this->checkoutSession->getQuote();
                break;

            case \Bread\BreadCheckout\Helper\Data::BLOCK_CODE_PRODUCT_VIEW :
                if ( !$this->checkoutSession->getQuoteId() ) {
                    if ($this->customerSession->isLoggedIn()) {
                        $quoteId = $this->quoteManagement->createEmptyCartForCustomer($this->customerSession->getCustomerId());
                    } else {
                        $quoteId = $this->quoteManagement->createEmptyCart();
                    }
                    $this->checkoutSession->setQuoteId($quoteId);
                }

                $quote = $this->checkoutSession->getQuote();

                if (!$this->checkoutSession->getBreadItemAddedToQuote() || !$quote->getAllVisibleItems()) {
                    $this->processOrderItem($quote, $data);
                }
                break;
        }

        return $quote;
    }
    
    protected function processOrderItem($quote, $data)
    {
        $quote->removeAllItems(); // Reset items in quote
        $selectedProductId = $data['selected_simple_product_id'];
        $mainProductId = $data['main_product_id'];
        $customOptionPieces = explode('***', $data['selected_sku']);
        $mainProduct = $this->catalogProductFactory->create()->load($mainProductId);
        $simpleProduct = $this->catalogProductFactory->create()->load($selectedProductId);
        $this->addItemToQuote($quote, $simpleProduct, $mainProduct, $customOptionPieces, 1); // Qty always 1 when checking out from product view
        $this->checkoutSession->setBreadItemAddedToQuote(true);
    }
}