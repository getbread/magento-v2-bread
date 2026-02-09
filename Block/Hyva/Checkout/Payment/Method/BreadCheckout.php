<?php
/**
 * Bread BreadCheckout - Hyva Checkout Payment Method Block
 *
 * Safely provides Magewire component only when Magewire is installed.
 */
namespace Bread\BreadCheckout\Block\Hyva\Checkout\Payment\Method;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\ObjectManager;

class BreadCheckout extends Template
{
    /**
     * Return Magewire component instance when available.
     *
     * @return object|null
     */
    public function getMagewireComponent()
    {
        if (!class_exists(\Magewirephp\Magewire\Component::class)) {
            return null;
        }

        if (!class_exists(\Bread\BreadCheckout\Magewire\Checkout\Payment\Method\BreadCheckout::class)) {
            return null;
        }

        return ObjectManager::getInstance()->get(\Bread\BreadCheckout\Magewire\Checkout\Payment\Method\BreadCheckout::class);
    }
}
