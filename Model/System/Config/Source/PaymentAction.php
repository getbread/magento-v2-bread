<?php
/**
 * Payment Action Options (Magento Canonical)
 *
 * @author  Bread   copyright   2016
 * @author  Joel    @Mediotype
 */
namespace ;

class 
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE,
                'label' => Mage::helper('breadcheckout')->__('Authorize')
            ),
            array(
                'value' => \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => Mage::helper('breadcheckout')->__('Authorize & Capture')
            ),
        );
    }
}
