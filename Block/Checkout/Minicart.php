<?php

namespace Bread\BreadCheckout\Block\Checkout;

use Bread\BreadCheckout\Helper\Catalog;
use Bread\BreadCheckout\Helper\Customer;
use Bread\BreadCheckout\Helper\Quote;
use Bread\BreadCheckout\Model\Payment\Method\BreadPaymentMethodFactory;
use Magento\Catalog\Block\ShortcutInterface;
use Magento\Catalog\Helper\Product;
use Magento\Checkout\Model\Session;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\ArrayUtils;
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
     * @param \Magento\Catalog\Block\Product\Context                                   $context
     * @param \Magento\Framework\Json\Helper\Data                                      $jsonHelper
     * @param Catalog                                                                  $catalogHelper
     * @param Customer                                                                 $customerHelper
     * @param Data                                                                     $dataHelper
     * @param ConfigurableFactory                                                      $configurableProductFactory
     * @param \Magento\ConfigurableProduct\Block\Product\View\Type\ConfigurableFactory $configurableBlockFactory
     * @param Quote                                                                    $quoteHelper
     * @param ArrayUtils                                                               $arrayUtils
     * @param EncoderInterface                                                         $jsonEncoder
     * @param \Magento\ConfigurableProduct\Helper\Data                                 $configurableHelper
     * @param Product                                                                  $catalogProductHelper
     * @param CurrentCustomer                                                          $currentCustomer
     * @param PriceCurrencyInterface                                                   $priceCurrency
     * @param ConfigurableAttributeData                                                $configurableAttributeData
     * @param Session                                                                  $checkoutSession
     * @param BreadPaymentMethodFactory                                                $paymentFactory
     * @param Data                                                                     $helperData
     * @param array                                                                    $data
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
        private BreadPaymentMethodFactory $paymentFactory,
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
     * Check if the block should be displayed
     *
     * @return bool
     * @throws \Exception
     */
    public function isActive()
    {
        $show = ($this->isApiVersionIsBread2())
            ? $this->helperData->showMinicartLink()
            : $this->helperData->allowMinicartCheckout();
        if (!$show) {
            return false;
        }

        if (!$this->payment) {
            $this->payment = $this->paymentFactory->create();
        }

        return $this->payment->isAvailable($this->checkoutSession->getQuote()) &&
            !$this->isCartView() &&
            $this->quoteHelper->aboveThreshold($this->quoteHelper->getGrandTotal()/100);
    }

    /**
     * Check if API version is bread_2
     *
     * @return bool
     */
    private function isApiVersionIsBread2(): bool
    {
        return $this->helperData->getApiVersion() === 'bread_2';
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
