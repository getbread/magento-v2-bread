<?php

namespace Bread\BreadCheckout\ViewModel\Product;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class Options extends View implements ArgumentInterface
{

    /**
     * Get SKU and price data for custom options on product
     *
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterface[] $options
     * @return string
     */
    public function getCustomOptionsData($options)
    {
        $optionsData = [];

        foreach ($options as $option) {
            if ($option->getValues()) {
                foreach ($option->getValues() as $k => $v) {
                    $optionsData[$option->getId()][$k] = [
                        'sku' => $v->getSku(),
                        'price' => (int)($v->getPrice() * 100)
                    ];
                }
            } else {
                $optionsData[$option->getId()] = [
                    'sku' => $option->getSku(),
                    'price' => (int)($option->getPrice() * 100)
                ];
            }
        }

        return $this->serializer->serialize($optionsData);
    }
}