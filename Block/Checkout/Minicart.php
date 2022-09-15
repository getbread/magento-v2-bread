<?php

namespace Bread\BreadCheckout\Block\Checkout;

use Magento\Catalog\Block\ShortcutInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Model\MethodInterface;
use Bread\BreadCheckout\Helper\Data;

class Minicart extends Overview implements ShortcutInterface
{
    const ALIAS_ELEMENT_INDEX = 'alias';

    const BUTTON_ELEMENT_INDEX = 'button_id';

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var MethodInterface
     */
    private $payment;

    /**
     * @var Data
     */
    private $helperData;

    /**
     * @var \Bread\BreadCheckout\Helper\Quote
     */
    public $quoteHelper;

    /**
     * Minicart constructor.
     *
     * @param Context           $context
     * @param ResolverInterface $localeResolver
     * @param Session           $checkoutSession
     * @param MethodInterface   $payment
     * @param Data              $helperData
     * @param array             $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Bread\BreadCheckout\Helper\Catalog $catalogHelper,
        \Bread\BreadCheckout\Helper\Customer $customerHelper,
        \Bread\BreadCheckout\Helper\Data $dataHelper,
        \Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory $configurableProductFactory,
        \Magento\ConfigurableProduct\Block\Product\View\Type\ConfigurableFactory $configurableBlockFactory,
        \Bread\BreadCheckout\Helper\Quote $quoteHelper,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\ConfigurableProduct\Helper\Data $configurableHelper,
        \Magento\Catalog\Helper\Product $catalogProductHelper,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\ConfigurableProduct\Model\ConfigurableAttributeData $configurableAttributeData,
        Session $checkoutSession,
        MethodInterface $payment,
        Data $helperData,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $jsonHelper,
            $catalogHelper,
            $customerHelper,
            $dataHelper,
            $configurableProductFactory,
            $configurableBlockFactory,
            $quoteHelper,
            $arrayUtils,
            $jsonEncoder,
            $configurableHelper,
            $catalogProductHelper,
            $currentCustomer,
            $priceCurrency,
            $configurableAttributeData,
            $data
        );

        $this->checkoutSession = $checkoutSession;
        $this->payment = $payment;
        $this->helperData = $helperData;
        $this->quoteHelper = $quoteHelper;
    }

    /**
     * @return string
     */
    public function _toHtml()
    {
        if ($this->isActive()) {
            return parent::_toHtml();
        }
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getAlias()
    {
        return $this->getData(self::ALIAS_ELEMENT_INDEX);
    }

    /**
     * @return string
     */
    public function getContainerId()
    {
        return $this->getData(self::BUTTON_ELEMENT_INDEX);
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isActive()
    {

        $aboveThreshold = $this->quoteHelper->aboveThreshold($this->quoteHelper->getGrandTotal()/100);
        $apiVersion = $this->helperData->getApiVersion();
        if($apiVersion === 'bread_2') {
            return $this->payment->isAvailable($this->checkoutSession->getQuote()) &&
                    $this->helperData->showMinicartLink() &&
                    !$this->isCartView() &&
                    $aboveThreshold;
        } else {
            return $this->payment->isAvailable($this->checkoutSession->getQuote()) &&
                    $this->helperData->allowMinicartCheckout() &&
                    !$this->isCartView() &&
                    $aboveThreshold;
        }       
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isCartView()
    {
        return in_array('checkout_cart_index', $this->getLayout()->getUpdate()->getHandles());
    }
}
