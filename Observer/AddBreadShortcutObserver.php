<?php

namespace Bread\BreadCheckout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class AddBreadShortcutObserver
 *
 * @package Bread\BreadCheckout\Observer
 */
class AddBreadShortcutObserver implements ObserverInterface
{

    const BREAD_SHORTCUT_BLOCK = \Bread\BreadCheckout\Block\Checkout\Minicart::class;

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {

        if ($observer->getData('is_catalog_product')) {
            return;
        }

        $shortcutButtons = $observer->getEvent()->getContainer();

        $shortcut = $shortcutButtons->getLayout()->createBlock(self::BREAD_SHORTCUT_BLOCK);

        $shortcutButtons->addShortcut($shortcut);
    }
}
