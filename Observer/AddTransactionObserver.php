<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bread\BreadCheckout\Observer;

class AddTransactionObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     *
     * @var \Magento\Framework\Registry
     */
    protected $helper;

    /**
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Bread\BreadCheckout\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Save order into registry to use it in the overloaded controller.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment = $observer->getEvent()->getData('payment');

        $this->helper->log("PAYMENT ID: " . $payment->getId());
        $this->helper->log("ORDER ID: " . $payment->getOrder()->getId());

        return $this;
    }
}
