<?php
/**
 *
 * @author  Bread   copyright   2016
 */
namespace Bread\BreadCheckout\Model;

class Session extends \Magento\Checkout\Model\Session
{
    /**
     * Init namespace
     */
    public function __construct()
    {
        $this->init('breadcheckout');
    }
}