<?php
namespace Bread\BreadCheckout\Block\Payment;

class Form extends \Magento\Payment\Block\Form
{
    /** Admin checkout form template @var string */
    public $_template = 'Bread_BreadCheckout::breadcheckout/info.phtml';

    /** @var \Bread\BreadCheckout\Helper\Data */
    public $helper;

    /**
     * Constructor
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Bread\BreadCheckout\Helper\Catalog $helper,
        array $data = []
    ) {
    
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Get controller URL for quote data retrieval
     *
     * @return string
     */
    public function getQuoteDataUrl()
    {
        return $this->helper->getQuoteDataUrl();
    }

    /**
     * Get controller URL for cart generation
     *
     * @return string
     */
    public function getGenerateCartUrl()
    {
        return $this->helper->getGenerateCartUrl();
    }

    /**
     * Get controller URL for email sending
     *
     * @return string
     */
    public function getSendMailUrl()
    {
        return $this->helper->getSendMailUrl();
    }

    /**
     * Get button size config setting
     *
     * @return string
     */
    public function getIsDefaultSize()
    {
        return (string) $this->helper->getDefaultButtonSizeHtml();
    }
}
