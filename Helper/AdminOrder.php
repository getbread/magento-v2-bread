<?php

namespace Bread\BreadCheckout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class AdminOrder extends AbstractHelper
{
    protected $_session;

    public function __construct(Context $context, \Magento\Backend\Model\Session\Quote $session)
    {
        $this->_session = $session;
        parent::__construct($context);
    }

    public function isAdminOrder()
    {
        return $this->_session->getQuote()->getIsAdminOrder();
    }
}
