<?php

/**
 * Admin order management block info
 * 
 * @since 2.3.0
 * @author Maritim, Kip
 */

namespace Bread\BreadCheckout\Block\Adminhtml\Order\View;

class BreadApiVersionInfo extends \Magento\Sales\Block\Adminhtml\Order\View {

    /**
     * 
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Model\Config $salesConfig
     * @param \Magento\Sales\Helper\Reorder $reorderHelper
     * @param array $data
     */
    public function __construct(
            \Magento\Backend\Block\Widget\Context $context,
            \Magento\Framework\Registry $registry,
            \Magento\Sales\Model\Config $salesConfig,
            \Magento\Sales\Helper\Reorder $reorderHelper,
            array $data = []
    ) {
        $this->_reorderHelper = $reorderHelper;
        $this->_coreRegistry = $registry;
        $this->_salesConfig = $salesConfig;
        parent::__construct($context, $registry, $salesConfig, $reorderHelper, $data);
    }

}
