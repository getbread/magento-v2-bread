<?php
/**
 * Payment Info Block
 *
 * @author Bread   copyright   2016
 * @author Joel    @Mediotype
 * @author Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Block\Payment;

class Info extends \Magento\Payment\Block\Info
{
    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    public $dataObjectFactory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        $data = []
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        parent::__construct($context, $data);
    }

    /**
     * Display Information For Admin View
     *
     * @param  null $transport
     * @return null|\Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }

        $info = $this->getInfo();
        $transport = $this->dataObjectFactory->create();
        $transport = parent::_prepareSpecificInformation($transport);
        $label = __('Financing Tx Id');

        if ($info->getTransactionId()) {
            $transId = $info->getTransactionId();
        } else {
            $transId = $info->getLastTransId();
        }
        $transport->addData([(string)$label => $transId]);
        return $transport;
    }
}
