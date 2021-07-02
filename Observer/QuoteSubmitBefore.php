<?php

namespace Bread\BreadCheckout\Observer;

/**
 * Class QuoteSubmitBefore
 *
 * @package Bread\BreadCheckout\Observer
 */
class QuoteSubmitBefore implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var \Bread\BreadCheckout\Model\Payment\Method\Bread
     */
    private $breadPayment;

    /**
     * QuoteSubmitBefore constructor.
     *
     * @param \Bread\BreadCheckout\Model\Payment\Method\Bread $breadPayment
     */
    public function __construct(
        \Bread\BreadCheckout\Model\Payment\Method\Bread $breadPayment
    ) {
        $this->breadPayment = $breadPayment;
    }

    /**
     * @param  \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $observer->getData('order');

        /**
         * @var \Magento\Sales\Model\Order\Payment $payment
         */
        $payment = $order->getPayment();

        if ($payment && $payment->getMethod() == $this->breadPayment->getMethodCode()) {
            $payment->setAdditionalInformation('method_title', $this->breadPayment->getBaseTitle());
        }

        return $this;
    }
}
