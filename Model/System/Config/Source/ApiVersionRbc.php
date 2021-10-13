<?php

/**
 * API version options
 *
 * @copyright (c) Bread
 * @author Kiprotich
 */

namespace Bread\BreadCheckout\Model\System\Config\Source;

class ApiVersion implements \Magento\Framework\Option\ArrayInterface {

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {
        return [
            ['value' => 'bread_2', 'label' => __('RBC platform')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray() {
        return [
            'bread_2' => __('RBC platform'),
        ];
    }

}
