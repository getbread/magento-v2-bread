<?php 
          
namespace Bread\BreadCheckout\Model\Payment\Method;
class BreadPaymentMethodFactory
{
    protected $objectManager;
    protected $helper;
    protected $quoteHelper;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Bread\BreadCheckout\Helper\Data $helper,
        \Bread\BreadCheckout\Helper\Quote $quoteHelper
    ) {
        $this->objectManager = $objectManager;
        $this->helper = $helper;
        $this->quoteHelper = $quoteHelper;
    }
    public function create($quote = null, array $data = array())
    {
        $currentCurrencyCode = $this->helper->getCurrentCurrencyCode();
        // Get currency from quote if it was passed in
        // Currenly only being passed from Admin Generate Cart
        if ($quote) {
            $currentCurrencyCode = $quote->getQuoteCurrencyCode();
        }

        $this->helper->setConfigClientByCurrency($currentCurrencyCode);
        if ($currentCurrencyCode === "CAD") {
            $instanceName =  'Bread\BreadCheckout\Model\Payment\Method\Rbc';
        } else {
            $instanceName = 'Bread\BreadCheckout\Model\Payment\Method\Bread';
        }
        return $this->objectManager->create($instanceName, $data);
    }
}
