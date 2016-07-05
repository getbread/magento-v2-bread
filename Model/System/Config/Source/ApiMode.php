<?php
/**
 * API Mode Options
 *
 * @author  Bread   copyright   2016
 * @author  Joel    @Mediotype
 */
namespace ;

class  {

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label'=>__('Live')),
            array('value' => 0, 'label'=>__('Sandbox')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            0 => __('Sandbox'),
            1 => __('Live'),
        );
    }

}