<?php
/**
 * Get updated shipping, billing, and shipping option data
 *
 * @author Bread   copyright 2016
 * @author Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Adminhtml\Bread;

class QuoteData extends \Magento\Backend\App\Action
{
    /**
     * @var \Bread\BreadCheckout\Helper\Quote
     */
    public $helper;

    /**
     * @var \Bread\BreadCheckout\Helper\Data
     */
    public $helperData;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Bread\BreadCheckout\Helper\Quote $helper,
        \Bread\BreadCheckout\Helper\Data $helperData
    ) {
    
        $this->resultFactory = $context->getResultFactory();
        $this->helper = $helper;
        $this->helperData = $helperData;
        parent::__construct($context);
    }

    /**
     * Get updated quote information to use to configure
     * bread button
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store_id');

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)->setData(
            [
            'quoteItems' => $this->helper->getQuoteItemsData(),
            'shippingContact' => $this->helper->getShippingAddressData(),
            'billingContact' => $this->helper->getBillingAddressData(),
            'shippingOptions' => $this->helper->getShippingOptions(),
            'tax' => $this->helper->getTaxValue(),
            'discounts' => $this->helper->getDiscountData(),
            'cartSizeFinancing' => $this->helper->getFinancingData($storeId),
            'grandTotal' => $this->helper->getGrandTotal(),
            'asLowAs' => $this->helper->isAsLowAs($storeId),
            'paymentUrl' => $this->helper->getAdminPaymentUrl(),
            'buttonCss' => $this->helper->getButtonDesign($storeId),
            'buttonLocation' => $this->helperData->getOtherLocation(),
            'isHealthcare' => $this->helper->isHealthcare($storeId)
            ]
        );
    }
}
