<?php

namespace Bread\BreadCheckout\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Cache\TypeListInterface;


class UpgradeData implements UpgradeDataInterface
{
    public function __construct(TypeListInterface $cacheTypeList)
    {
        $this->cacheTypeList = $cacheTypeList;
    }
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '2.3.0') < 0) {
            $connection = $setup->getConnection();
            $tablename = $setup->getTable('core_config_data');

            $mapping = [
                'classic_api_public_key' => 'api_public_key',
                'classic_api_secret_key' => 'api_secret_key',
                'classic_api_sandbox_public_key' => 'api_sandbox_public_key',
                'classic_api_sandbox_secret_key' => 'api_sandbox_secret_key',

            ];
            foreach ($mapping as $classic => $platform) {
                $row = $setup->getTableRow(
                    'core_config_data',
                    'path',
                    'payment/breadcheckout/' . $classic
                );
                $this->saveConfig($connection, $tablename, 'payment/breadcheckout/' . $platform, $row['value']);
            }
            //Clear config cache
            $this->cacheTypeList->cleanType(Config::TYPE_IDENTIFIER);
        }
        $setup->endSetup();
    }

    public function saveConfig($conn, $table, $path, $value, $scope = 'default', $scopeId = 0)
    {
        $select = $conn->select()->from(
            $table
        )->where(
            'path = ?',
            $path
        )->where(
            'scope = ?',
            $scope
        )->where(
            'scope_id = ?',
            $scopeId
        );
        $row = $conn->fetchRow($select);

        $newData = ['scope' => $scope, 'scope_id' => $scopeId, 'path' => $path, 'value' => $value];

        if (!$row) {
            $conn->insert($table, $newData);
        }
        return $this;
    }
}
