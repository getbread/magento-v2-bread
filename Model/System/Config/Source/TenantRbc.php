<?php

/**
 * API version options
 *
 * @copyright (c) Bread
 * @author Kip
 */

namespace Bread\BreadCheckout\Model\System\Config\Source;

class TenantRbc implements \Magento\Framework\Option\ArrayInterface {

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {
        return [
            ['value' => 'rbc', 'label' => __('Payplan by RBC')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray() {
        return [
            'rbc' => __('Payplan by RBC'),
        ];
    }

}
