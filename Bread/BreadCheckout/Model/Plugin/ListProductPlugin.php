<?php

namespace Bread\BreadCheckout\Model\Plugin;

/**
 * Class ListProductPlugin
 *
 * @package Bread\BreadCheckout\Model\Plugin
 */
class ListProductPlugin
{

    /**
     * @var \Bread\BreadCheckout\Helper\Category
     */
    protected $categoryHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * ListProductPlugin constructor.
     *
     * @param \Bread\BreadCheckout\Helper\Category $categoryHelper
     * @param \Magento\Framework\Registry          $registry
     */
    public function __construct(
        \Bread\BreadCheckout\Helper\Category $categoryHelper,
        \Magento\Framework\Registry $registry
    ) {

        $this->categoryHelper = $categoryHelper;
        $this->registry       = $registry;
    }

    /**
     * @param \Magento\Catalog\Block\Product\ListProduct $subject
     * @param callable                                   $proceed
     * @param \Magento\Catalog\Model\Product             $product
     *
     * @return string
     */
    public function aroundGetProductPrice($subject, callable $proceed, $product)
    {
        $result = $proceed($product);
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $this->registry->registry("current_category");

        /** @var \Magento\Framework\View\Layout $layout */
        $layout = $subject->getLayout();

        if ($this->categoryHelper->isEnabledForCategory($category)) {
            /** @var \Bread\BreadCheckout\Block\Product\Category $block */
            $block = $layout->createBlock(\Bread\BreadCheckout\Block\Product\Category::class);
            $block->setProduct($product);
            $result .= $block->toHtml();
        }

        return $result;
    }
}
