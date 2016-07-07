<?php
/**
 * Handles Product View Options Block
 *
 * @copyright   Bread   2016
 * @author      Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Block\Product\View;

class Options extends \Magento\Catalog\Block\Product\View\Options
{
    /** @var \Magento\Framework\Json\Helper\Data */
    protected $jsonHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        array $data = []
    ) {
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context, $data);
    }
}