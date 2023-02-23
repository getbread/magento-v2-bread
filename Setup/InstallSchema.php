<?php 

/**
 * InstallSchema for v2.0
 *
 * @since 2.3.0
 * @author Maritim, Kip
 *
 */

namespace Bread\BreadCheckout\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface {

    /**
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {

        $setup->startSetup();

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

        $setup->endSetup();
    }

}
