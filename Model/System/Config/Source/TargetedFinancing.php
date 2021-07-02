<?php

namespace Bread\BreadCheckout\Model\System\Config\Source;

class TargetedFinancing implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 0, 'label' => __('No')], ['value' => 1, 'label' => __('By Cart Size')], ['value' => 2, 'label' => __('By SKU List')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [0 => __('No'), 1 => __('By Cart Size'), 2 => __('By SKU List')];
    }
}
