<?php

/**
 * API version options
 *
 * @copyright (c) Bread Financial
 * @author Kiprotich, Maritim <kip.maritim@breadfinancial.com>
 */

namespace Bread\BreadCheckout\Model\System\Config\Source;

class ApiVersionRbc implements \Magento\Framework\Option\ArrayInterface {

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
