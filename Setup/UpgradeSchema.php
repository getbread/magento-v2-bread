<?php

/**
 * Add bread api_version to quote and order info
 *
 * @since 2.3.0
 * @author Maritim, Kip
 */

namespace Bread\BreadCheckout\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface {

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {

        $setup->startSetup();

        //Extension setup before 2.2.0 did not have the bread_api_version tracking
        //column againt quote and order object
        if (version_compare($context->getVersion(), '2.3.0', '<')) {
            $setup->getConnection()->addColumn(
                    $setup->getTable('quote_payment'),
                    'bread_api_version',
                    [
                        'type' => 'text',
                        'nullable' => true,
                        'comment' => 'Bread version'
                    ]
            );

            $setup->getConnection()->addColumn(
                    $setup->getTable('sales_order_payment'),
                    'bread_api_version',
                    [
                        'type' => 'text',
                        'nullable' => true,
                        'comment' => 'Bread version'
                    ]
            );
        }

        $setup->endSetup();
    }

}