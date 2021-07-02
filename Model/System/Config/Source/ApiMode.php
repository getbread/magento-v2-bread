<?php
/**
 * API Mode Options
 *
 * @author Bread   copyright   2016
 * @author Joel    @Mediotype
 * @author Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Model\System\Config\Source;

class ApiMode implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label'=>__('Live')],
            ['value' => 0, 'label'=>__('Sandbox')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            0 => __('Sandbox'),
            1 => __('Live'),
        ];
    }
}
