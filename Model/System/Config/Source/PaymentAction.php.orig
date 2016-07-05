<?php
/**
 * Payment Action Options (Magento Canonical)
 *
 * @author  Bread   copyright   2016
 * @author  Joel    @Mediotype
 */
class Bread_BreadCheckout_Model_System_Config_Source_PaymentAction
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE,
                'label' => Mage::helper('breadcheckout')->__('Authorize')
            ),
            array(
                'value' => Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE,
                'label' => Mage::helper('breadcheckout')->__('Authorize & Capture')
            ),
        );
    }
}
