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
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\Product\Option $option,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Magento\Framework\Json\Helper\Data $jsonHelper = null,
        array $data = []
    ) {
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context, $pricingHelper, $catalogData, $jsonEncoder, $option, $registry, $arrayUtils, $data);
    }
}