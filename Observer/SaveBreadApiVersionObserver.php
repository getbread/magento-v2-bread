<?php

/**
 * Save bread API version for transactions that belong to Bread payment
 * method
 * 
 * @since 2.3.0
 * @author Maritim, Kip
 */

namespace Bread\BreadCheckout\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class SaveBreadApiVersionObserver implements ObserverInterface {

    /**
     * 
     * @var type
     */
    protected $logger;

    /**
     * 
     * @var type
     */
    protected $_state;

    /**
     * @var object 
     */
    protected $_quoteRepository;
    protected $helper;

    /**
     * 
     * @param \Bread\BreadCheckout\Helper\Log $logger
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
            \Bread\BreadCheckout\Helper\Log $logger,
            \Magento\Framework\App\State $state,
            \Magento\Quote\Model\QuoteRepository $quoteRepository,
            \Bread\BreadCheckout\Helper\Quote $helper
    ) {
        $this->logger = $logger;
        $this->_state = $state;
        $this->_quoteRepository = $quoteRepository;
        $this->helper = $helper;
    }

    /**
     * 
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer) {

        if ($this->_state->getAreaCode() != \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $paymentOrder = $observer->getEvent()->getPayment();
            $order = $paymentOrder->getOrder();
            $quote = $this->_quoteRepository->get($order->getQuoteId());
            $paymentQuote = $quote->getPayment();
            $method = $paymentQuote->getMethodInstance()->getCode();
            if ($method === \Bread\BreadCheckout\Model\Ui\ConfigProvider::CODE) {
                $paymentOrder->setData('bread_api_version', $this->helper->getApiVersion());
                $paymentQuote->setData('bread_api_version', $this->helper->getApiVersion());
            }
        }
    }

}
