<?php

/**
 * API version options
 *
 * @copyright (c) Bread Financial
 * @author Kiprotich, Maritim <kip.maritim@breadfinancial.com>
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
            ['value' => 'classic', 'label' => __('Bread Classic')],
            ['value' => 'bread_2', 'label' => __('Bread 2.0')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray() {
        return [
            'classic' => __('Bread Classic'),
            'bread_2' => __('Bread 2.0')
        ];
    }

}
