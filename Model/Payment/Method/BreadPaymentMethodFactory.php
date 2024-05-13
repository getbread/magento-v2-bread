<?php 
          
namespace Bread\BreadCheckout\Model\Payment\Method;
class BreadPaymentMethodFactory
{
    protected $objectManager;
    protected $helper;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Bread\BreadCheckout\Helper\Data $helper
    ) {
        $this->objectManager = $objectManager;
        $this->helper = $helper;
    }
    public function create(array $data = array())
    {
        if ($this->helper->getCurrentCurrencyCode() === "CAD") {
            $instanceName =  'Bread\BreadCheckout\Model\Payment\Method\Rbc';
        } else {
            $instanceName = 'Bread\BreadCheckout\Model\Payment\Method\Bread';
        }
        return $this->objectManager->create($instanceName, $data);
    }
}
