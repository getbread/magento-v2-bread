<?php
namespace Bread\BreadCheckout\Block\Payment;

class Form extends \Magento\Payment\Block\Form
{
    /**
     * Admin checkout form template @var string
     */
    public $_template = 'Bread_BreadCheckout::breadcheckout/info.phtml';

    /**
     * @var \Bread\BreadCheckout\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    public $sessionQuote;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    public $cache;

    /**
     * Constructor
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Bread\BreadCheckout\Helper\Catalog $helper,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Framework\App\CacheInterface $cache,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->sessionQuote = $sessionQuote;
        $this->cache = $cache;
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
     * Get controller URL for email sending via bread API
     */
    public function getSendBreadMail()
    {
        return $this->helper->getSendMailBreadUrl();
    }

    /**
     * Get controller URL for sms sending
     */
    public function getSendSmsUrl()
    {
        return $this->helper->getSendSmsUrl();
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

    /**
     * Saves the Store Id in the Magento Cache
     */
    public function saveAdminStoreId()
    {
        $storeId = $this->sessionQuote->getStoreId();
        $this->cache->save($storeId, 'admin_store_id');
        return $storeId;
    }
}
