<?php

namespace Bread\BreadCheckout\Observer;

use Magento\Framework\Event\ObserverInterface;

class AdminOrderObserver implements ObserverInterface
{
    protected $_session;

    public function __construct(\Magento\Backend\Model\Session\Quote $session)
    {
        $this->_session = $session;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $quote->setIsAdminOrder(true);
    }
}
