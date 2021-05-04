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
            ['value' => 1, 'label' => __('Bread 2.0')],
            ['value' => 0, 'label' => __('Bread Classic')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray() {
        return [
            0 => __('Bread 2.0'),
            1 => __('Bread Classic'),
        ];
    }

}
