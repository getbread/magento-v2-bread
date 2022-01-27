<?php

/**
 * API version options
 *
 * @copyright (c) Bread
 * @author Kip
 */

namespace Bread\BreadCheckout\Model\System\Config\Source;

class Tenant implements \Magento\Framework\Option\ArrayInterface {

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {
        return [
            ['value' => 'core', 'label' => __('CORE (US)')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray() {
        return [
            'core' => __('CORE (US'),
        ];
    }

}
