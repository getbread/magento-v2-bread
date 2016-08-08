<?php
namespace Bread\BreadCheckout\Block\Payment;

class Form extends \Magento\Payment\Block\Form
{
    /**
     * Checkmo template
     *
     * @var string
     */
    protected $_template = 'Bread_BreadCheckout::breadcheckout/info.phtml';
    protected $quoteDataUrl = '';
    
    
    
}
