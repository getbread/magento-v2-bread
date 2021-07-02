<?php
/**
 * Payment Action Options (Magento Canonical)
 *
 * @author Bread   copyright   2016
 * @author Joel    @Mediotype
 * @author Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Model\System\Config\Source;

class PaymentAction extends \Magento\Framework\Model\AbstractModel
{
    public function toOptionArray()
    {
        return [
            [   'value' => \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE,
                'label' => __('Authorize') ],
            [   'value' => \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize & Capture') ],
        ];
    }
}
