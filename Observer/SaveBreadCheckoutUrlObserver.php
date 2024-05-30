<?php

/**
 * Save checkout url when creating cart. Checkout URL will be shown in adminhtml sales order
 *
 * @since 2.4.2
 */

namespace Bread\BreadCheckout\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class SaveBreadCheckoutUrlObserver implements ObserverInterface {

    /**
     * @var \Bread\BreadCheckout\Helper\Log $logger
     */
    protected $logger;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Flat\State
     */
    protected $_state;

    /**
     * @var \Magento\Sales\Model\AdminOrder\Create
     */
    protected $orderCreateModel;

    /**
     *
     * @param \Bread\BreadCheckout\Helper\Log $logger
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
            \Bread\BreadCheckout\Helper\Log $logger,
            \Magento\Framework\App\State $state,
            \Magento\Sales\Model\AdminOrder\Create $orderCreateModel
    ) {
        $this->logger = $logger;
        $this->_state = $state;
        $this->orderCreateModel = $orderCreateModel;
    }

    /**
     *
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer) {

        if ($this->_state->getAreaCode() === \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $this->logger->log("SaveBreadCheckoutUrlObserver observer executed");
            $order = $observer->getEvent()->getOrder();
            $paymentOrder = $order->getPayment();
            $checkoutUrl = $this->orderCreateModel->getSession()->getCheckoutUrl();
            $paymentOrder->setData('checkout_url', $checkoutUrl);
            $this->logger->log("saved checkout_url: " . $checkoutUrl);
        }
    }
}
