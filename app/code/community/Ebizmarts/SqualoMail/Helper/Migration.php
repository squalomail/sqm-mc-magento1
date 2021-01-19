<?php

/**
 * SqualoMail For Magento
 *
 * @category  Ebizmarts_SqualoMail
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     3/20/2020 11:14 AM
 * @file:     Webhook.php
 */
class Ebizmarts_SqualoMail_Helper_Migration extends Mage_Core_Helper_Abstract
{
    /**
     * @var Ebizmarts_SqualoMail_Helper_Data
     */
    protected $_helper;

    /**
     * @var Ebizmarts_SqualoMail_Helper_Date
     */
    protected $_dateHelper;

    /**
     * @var Ebizmarts_SqualoMail_Helper_Webhook
     */
    protected $_webhookHelper;

    public function __construct()
    {
        $this->_helper = Mage::helper('squalomail');
        $this->_dateHelper = Mage::helper('squalomail/date');
        $this->_webhookHelper = Mage::helper('squalomail/webhook');
    }

    /**
     * Handle data migration for versions that require it.
     */
    public function handleMigrationUpdates()
    {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();

        $initialTime = $dateHelper->getTimestamp();
        $migrateFrom115 = $helper->getConfigValueForScope(
            Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_FROM_115,
            0,
            'default'
        );
        $migrateFrom116 = $helper->getConfigValueForScope(
            Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_FROM_116,
            0,
            'default'
        );
        $migrateFrom1164 = $helper->getConfigValueForScope(
            Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_FROM_1164,
            0,
            'default'
        );
        $migrateFrom1120 = $helper->getConfigValueForScope(
            Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_FROM_1120,
            0,
            'default'
        );

        if ($migrateFrom115) {
            $this->_migrateFrom115($initialTime);
        } elseif ($migrateFrom116 && !$dateHelper->timePassed($initialTime)) {
            $this->_migrateFrom116($initialTime);
        } elseif ($migrateFrom1164 && !$dateHelper->timePassed($initialTime)) {
            $this->_migrateFrom1164($initialTime);
        } elseif ($migrateFrom1120 && !$dateHelper->timePassed($initialTime)) {
            $this->_migrateFrom1120($initialTime);
        }
    }

    /**
     * Migrate data from version 1.1.5 to the squalomail_ecommerce_sync_data table.
     *
     * @param  $initialTime
     * @throws Mage_Core_Exception
     */
    protected function _migrateFrom115($initialTime)
    {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();
        $arrayMigrationConfigData = array('115' => true, '116' => false, '1164' => false);
        //migrate data from older version to the new schemma
        if ($helper->isEcommerceEnabled(0)) {
            $squalomailStoreId = $this->getSQMStoreId(0);

            //migrate customers
            $this->_migrateCustomersFrom115($squalomailStoreId, $initialTime);

            if (!$dateHelper->timePassed($initialTime)) {
                //migrate products
                $this->_migrateProductsFrom115($squalomailStoreId, $initialTime);

                if (!$dateHelper->timePassed($initialTime)) {
                    //migrate orders
                    $this->_migrateOrdersFrom115($squalomailStoreId, $initialTime);

                    if (!$dateHelper->timePassed($initialTime)) {
                        //migrate carts
                        $finished = $this->_migrateCartsFrom115($squalomailStoreId, $initialTime);

                        if ($finished) {
                            $this->_migrateFrom115dropColumn($arrayMigrationConfigData);
                        }
                    }
                }
            }
        } else {
            $this->handleDeleteMigrationConfigData($arrayMigrationConfigData);
        }
    }

    /**
     * Helper function for data migration from version 1.1.5.
     *
     * @param           $collection
     * @param           $squalomailStoreId
     * @param           $initialTime
     * @param Closure   $callback
     * @return bool
     */
    protected function _makeForCollectionItem($collection, $squalomailStoreId, $initialTime, Closure $callback)
    {
        $dateHelper = $this->getDateHelper();
        $finished = false;

        if (!$collection->getSize()) {
            $finished = true;
        }

        $collection->setPageSize(100);

        $pages = $collection->getLastPageNumber();
        $currentPage = 1;

        do {
            $collection->setCurPage($currentPage);
            $this->_loadItemCollection($collection);

            foreach ($collection as $collectionItem) {
                $callback($collectionItem, $squalomailStoreId);
            }

            $currentPage++;
            // clear collection,
            // if not done, the same page will be loaded each loop
            // - will also free memory
            $collection->clear();

            if ($dateHelper->timePassed($initialTime)) {
                break;
            }

            if ($currentPage == $pages) {
                $finished = true;
            }
        } while ($currentPage <= $pages);

        return $finished;
    }

    /**
     * @param $collection
     */
    protected function _loadItemCollection($collection)
    {
        $collection->load();
    }

    protected function _migrateFrom115dropColumn($arrayMigrationConfigData)
    {
        $helper = $this->getHelper();
        $this->handleDeleteMigrationConfigData($arrayMigrationConfigData);

        //Remove attributes no longer used
        $setup = Mage::getResourceModel('catalog/setup', 'catalog_setup');

        try {
            $setup->removeAttribute('catalog_product', 'squalomail_sync_delta');
            $setup->removeAttribute('catalog_product', 'squalomail_sync_error');
            $setup->removeAttribute('catalog_product', 'squalomail_sync_modified');
            $setup->removeAttribute('customer', 'squalomail_sync_delta');
            $setup->removeAttribute('customer', 'squalomail_sync_error');
            $setup->removeAttribute('customer', 'squalomail_sync_modified');
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }

        $coreResource = $helper->getCoreResource();

        try {
            $quoteTable = $coreResource->getTableName('sales/quote');
            $connectionQuote = $setup->getConnection();
            $connectionQuote->dropColumn($quoteTable, 'squalomail_sync_delta');
            $connectionQuote->dropColumn($quoteTable, 'squalomail_sync_error');
            $connectionQuote->dropColumn($quoteTable, 'squalomail_deleted');
            $connectionQuote->dropColumn($quoteTable, 'squalomail_token');
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }

        try {
            $orderTable = $coreResource->getTableName('sales/order');
            $connectionOrder = $setup->getConnection();
            $connectionOrder->dropColumn($orderTable, 'squalomail_sync_delta');
            $connectionOrder->dropColumn($orderTable, 'squalomail_sync_error');
            $connectionOrder->dropColumn($orderTable, 'squalomail_sync_modified');
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Ecommercesyncdata
     */
    protected function getSqualomailEcommerceSyncDataModel()
    {
        return Mage::getModel('squalomail/ecommercesyncdata');
    }

    /**
     * Migrate Customers from version 1.1.5 to the squalomail_ecommerce_sync_data table.
     *
     * @param  $squalomailStoreId
     * @param  $initialTime
     * @throws Mage_Core_Exception
     */
    protected function _migrateCustomersFrom115($squalomailStoreId, $initialTime)
    {
        $helper = $this->getHelper();

        try {
            $entityType = Mage::getSingleton('eav/config')->getEntityType('customer');
            $attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'squalomail_sync_delta');

            if ($attribute->getId()) {
                $squalomailTableName = $helper->getCoreResource()->getTableName('squalomail/ecommercesyncdata');
                $customerCollection = Mage::getResourceModel('customer/customer_collection');
                $customerCollection->addAttributeToFilter('squalomail_sync_delta', array('gt' => '0000-00-00 00:00:00'));
                $customerCollection->getSelect()->joinLeft(
                    array('m4m' => $squalomailTableName),
                    "m4m.related_id = e.entity_id AND m4m.type = '" . Ebizmarts_SqualoMail_Model_Config::IS_CUSTOMER
                    . "' AND m4m.squalomail_store_id = '" . $squalomailStoreId . "'",
                    array('m4m.*')
                );
                $customerCollection->getSelect()->where(
                    "m4m.squalomail_sync_delta IS null"
                );
                $this->_makeForCollectionItem(
                    $customerCollection,
                    $squalomailStoreId,
                    $initialTime,
                    function ($customer, $squalomailStoreId) {
                        $customerId = $customer->getEntityId();
                        $customerObject = Mage::getModel('customer/customer')->load($customerId);
                        $syncError = null;
                        $syncModified = null;
                        $syncDelta = $customerObject->getSqualomailSyncDelta();

                        if ($customer->getSqualomailSyncError()) {
                            $syncError = $customer->getSqualomailSyncError();
                        }

                        if ($customer->getSqualomailSyncModified()) {
                            $syncModified = $customer->getSqualomailSyncModified();
                        }

                        $ecommerceSyncData = $this->getSqualomailEcommerceSyncDataModel();
                        $ecommerceSyncData->saveEcommerceSyncData(
                            $customerId,
                            Ebizmarts_SqualoMail_Model_Config::IS_CUSTOMER,
                            $squalomailStoreId,
                            $syncDelta,
                            $syncError,
                            $syncModified
                        );
                    }
                );
            }
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * Migrate Products from version 1.1.5 to the squalomail_ecommerce_sync_data table.
     *
     * @param  $squalomailStoreId
     * @param  $initialTime
     * @throws Mage_Core_Exception
     */
    protected function _migrateProductsFrom115($squalomailStoreId, $initialTime)
    {
        $helper = $this->getHelper();

        try {
            $entityType = Mage_Catalog_Model_Product::ENTITY;
            $attributeCode = 'squalomail_sync_delta';
            $attribute = Mage::getModel('eav/entity_attribute')->loadByCode($entityType, $attributeCode);

            if ($attribute->getId()) {
                $squalomailTableName = $helper->getCoreResource()->getTableName('squalomail/ecommercesyncdata');
                $productCollection = Mage::getResourceModel('catalog/product_collection');
                $productCollection->addAttributeToFilter('squalomail_sync_delta', array('gt' => '0000-00-00 00:00:00'));
                $productCollection->getSelect()->joinLeft(
                    array('m4m' => $squalomailTableName),
                    "m4m.related_id = e.entity_id AND m4m.type = '" . Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT
                    . "' AND m4m.squalomail_store_id = '" . $squalomailStoreId . "'",
                    array('m4m.*')
                );
                $productCollection->getSelect()->where("m4m.squalomail_sync_delta IS null");
                $this->_makeForCollectionItem(
                    $productCollection,
                    $squalomailStoreId,
                    $initialTime,
                    function ($product, $squalomailStoreId) {
                        $productId = $product->getEntityId();
                        $_resource = Mage::getResourceSingleton('catalog/product');
                        $syncDelta = $_resource->getAttributeRawValue(
                            $productId,
                            'squalomail_sync_delta',
                            $helper->getMageApp()->getStore()
                        );
                        $syncError = null;
                        $syncModified = null;

                        if ($product->getSqualomailSyncError()) {
                            $syncError = $product->getSqualomailSyncError();
                        }

                        if ($product->getSqualomailSyncModified()) {
                            $syncModified = $product->getSqualomailSyncModified();
                        }

                        $ecommerceSyncData = $this->getSqualomailEcommerceSyncDataModel();
                        $ecommerceSyncData->saveEcommerceSyncData(
                            $productId,
                            Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT,
                            $squalomailStoreId,
                            $syncDelta,
                            $syncError,
                            $syncModified
                        );
                    }
                );
            }
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    protected function getSalesOrderModel()
    {
        return Mage::getModel('sales/order');
    }

    /**
     * Migrate Orders from version 1.1.5 to the squalomail_ecommerce_sync_data table.
     *
     * @param  $squalomailStoreId
     * @param  $initialTime
     * @throws Mage_Core_Exception
     */
    protected function _migrateOrdersFrom115($squalomailStoreId, $initialTime)
    {
        $helper = $this->getHelper();

        try {
            $resource = $helper->getCoreResource();
            $readConnection = $resource->getConnection('core_read');
            $tableName = $resource->getTableName('sales/order');
            $orderFields = $readConnection->describeTable($tableName);

            if (isset($orderFields['squalomail_sync_delta'])) {
                $squalomailTableName = $resource->getTableName('squalomail/ecommercesyncdata');
                $orderCollection = Mage::getResourceModel('sales/order_collection');

                $orderCollection->getSelect()->joinLeft(
                    array('m4m' => $squalomailTableName),
                    "m4m.related_id = main_table.entity_id AND m4m.type = '"
                    . Ebizmarts_SqualoMail_Model_Config::IS_ORDER .
                    "' AND m4m.squalomail_store_id = '" . $squalomailStoreId . "'",
                    array('m4m.*')
                );
                $orderCollection->getSelect()
                    ->where(
                        "m4m.squalomail_sync_delta IS NULL AND main_table.squalomail_sync_delta > '0000-00-00 00:00:00'"
                    );
                $this->_makeForCollectionItem(
                    $orderCollection,
                    $squalomailStoreId,
                    $initialTime,
                    function ($order, $squalomailStoreId) {
                        $orderId = $order->getEntityId();
                        $syncError = null;
                        $syncModified = null;
                        $orderObject = $this->getSalesOrderModel()->load($orderId);
                        $syncDelta = $orderObject->getSqualomailSyncDelta();

                        if ($order->getSqualomailSyncError()) {
                            $syncError = $order->getSqualomailSyncError();
                        }

                        if ($order->getSqualomailSyncModified()) {
                            $syncModified = $order->getSqualomailSyncModified();
                        }

                        $ecommerceSyncData = $this->getSqualomailEcommerceSyncDataModel();
                        $ecommerceSyncData->saveEcommerceSyncData(
                            $orderId,
                            Ebizmarts_SqualoMail_Model_Config::IS_ORDER,
                            $squalomailStoreId,
                            $syncDelta,
                            $syncError,
                            $syncModified
                        );
                    }
                );
            }
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * Migrate Carts from version 1.1.5 to the squalomail_ecommerce_sync_data table.
     *
     * @param  $squalomailStoreId
     * @param  $initialTime
     * @return bool
     * @throws Mage_Core_Exception
     */
    protected function _migrateCartsFrom115($squalomailStoreId, $initialTime)
    {
        $helper = $this->getHelper();

        try {
            $resource = $helper->getCoreResource();
            $readConnection = $resource->getConnection('core_read');
            $tableName = $resource->getTableName('sales/quote');
            $quoteFields = $readConnection->describeTable($tableName);

            if (isset($quoteFields['squalomail_sync_delta'])) {
                $squalomailTableName = $resource->getTableName('squalomail/ecommercesyncdata');
                $quoteCollection = Mage::getResourceModel('sales/quote_collection');
                $quoteCollection->getSelect()->joinLeft(
                    array('m4m' => $squalomailTableName),
                    "m4m.related_id = main_table.entity_id AND m4m.type = '"
                    . Ebizmarts_SqualoMail_Model_Config::IS_QUOTE
                    . "' AND m4m.squalomail_store_id = '" . $squalomailStoreId . "'",
                    array('m4m.*')
                );
                // be sure that the quotes are already in squalomail and not deleted
                $quoteCollection->getSelect()
                    ->where(
                        "m4m.squalomail_sync_delta IS NULL AND main_table.squalomail_sync_delta > '0000-00-00 00:00:00'"
                    );
                $finished = $this->_makeForCollectionItem(
                    $quoteCollection,
                    $squalomailStoreId,
                    $initialTime,
                    function ($quote, $squalomailStoreId) {
                        $quoteId = $quote->getEntityId();
                        $syncError = null;
                        $syncDeleted = null;
                        $token = null;
                        $quoteObject = $this->getSalesOrderModel()->load($quoteId);
                        $syncDelta = $quoteObject->getSqualomailSyncDelta();

                        if ($quote->getSqualomailSyncError()) {
                            $syncError = $quote->getSqualomailSyncError();
                        }

                        if ($quote->getSqualomailSyncDeleted()) {
                            $syncDeleted = $quote->getSqualomailSyncDeleted();
                        }

                        if ($quote->getSqualomailToken()) {
                            $token = $quote->getSqualomailToken();
                        }

                        $ecommerceSyncData = $this->getSqualomailEcommerceSyncDataModel();
                        $ecommerceSyncData->saveEcommerceSyncData(
                            $quoteId,
                            Ebizmarts_SqualoMail_Model_Config::IS_QUOTE,
                            $squalomailStoreId,
                            $syncDelta,
                            $syncError,
                            null,
                            $syncDeleted,
                            $token
                        );
                    }
                );
            } else {
                $finished = true;
            }

            return $finished;
        } catch (Exception $e) {
            $helper->logError(
                $helper->__(
                    'Unexpected error happened during migration from version 1.1.5 to 1.1.6.'
                    . 'Please contact our support at '
                ) . 'squalomail@ebizmarts-desk.zendesk.com'
                . $helper->__(' See error details below.')
            );
            $helper->logError($e->getMessage());

            return false;
        }
    }

    /**
     * Delete config data for migration from 1.1.5.
     */
    protected function delete115MigrationConfigData()
    {
        $helper = $this->getHelper();
        $helper->getConfig()->deleteConfig(
            Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_FROM_115,
            'default',
            0
        );
    }

    /**
     * Modify is_syncing value if initial sync finished for all stores.
     *
     * @param $syncValue
     */
    protected function _setIsSyncingIfFinishedInAllStores($syncValue)
    {
        $helper = $this->getHelper();
        $stores = $helper->getMageApp()->getStores();

        foreach ($stores as $storeId => $store) {
            $ecommEnabled = $this->isEcomSyncDataEnabled($storeId);

            if ($ecommEnabled) {
                $this->setIsSyncingIfFinishedPerScope($syncValue, $storeId);
            }
        }
    }

    /**
     * Migrate data from version 1.1.6.
     *
     * @param $initialTime
     */
    protected function _migrateFrom116($initialTime)
    {
        $this->_setIsSyncingIfFinishedInAllStores(true);
        $finished = $this->_migrateOrdersFrom116($initialTime);

        if ($finished) {
            $this->_setIsSyncingIfFinishedInAllStores(false);
            $arrayMigrationConfigData = array('115' => false, '116' => true, '1164' => false);
            $this->handleDeleteMigrationConfigData($arrayMigrationConfigData);
        }
    }

    /**
     * Update Order ids to the Increment id in SqualoMail.
     *
     * @param  $initialTime
     * @return bool
     */
    protected function _migrateOrdersFrom116($initialTime)
    {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();
        $finished = false;

        if (!$dateHelper->timePassed($initialTime)) {
            $finished = true;
            $stores = $helper->getMageApp()->getStores();

            foreach ($stores as $storeId => $store) {
                if ($helper->isEcomSyncDataEnabled($storeId)) {
                    Mage::getModel('squalomail/api_batches')->replaceAllOrders($initialTime, $storeId);
                }

                if ($dateHelper->timePassed($initialTime)) {
                    $finished = false;
                    break;
                }
            }
        }

        return $finished;
    }

    /**
     * Return if migration has finished checking the config values.
     *
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function migrationFinished()
    {
        $helper = $this->getHelper();
        $migrationFinished = false;

        $migrateFrom115 = $helper->getConfigValueForScope(
            Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_FROM_115,
            0,
            'default'
        );

        $migrateFrom116 = $helper->getConfigValueForScope(
            Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_FROM_116,
            0,
            'default'
        );

        $migrateFrom1164 = $helper->getConfigValueForScope(
            Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_FROM_1164,
            0,
            'default'
        );

        $migrateFrom1120 = $helper->getConfigValueForScope(
            Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_FROM_1120,
            0,
            'default'
        );

        if (!$migrateFrom115 && !$migrateFrom116 && !$migrateFrom1164 && !$migrateFrom1120) {
            $migrationFinished = true;
        }

        return $migrationFinished;
    }

    /**
     * Delete config data for migration from 1.1.6.
     */
    public function delete116MigrationConfigData()
    {
        $helper = $this->getHelper();
        $stores = $helper->getMageApp()->getStores();
        $helper->getConfig()->deleteConfig(
            Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_FROM_116,
            'default',
            0
        );

        foreach ($stores as $storeId => $store) {
            $helper->getConfig()->deleteConfig(
                Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_LAST_ORDER_ID,
                'stores',
                $storeId
            );
        }
    }

    /**
     * Migrate data from version 1.1.6.4.
     *
     * @param $initialTime
     */
    protected function _migrateFrom1164($initialTime)
    {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();

        if (!$dateHelper->timePassed($initialTime)) {
            $writeConnection = $helper->getCoreResource()->getConnection('core_write');
            $resource = Mage::getResourceModel('squalomail/ecommercesyncdata');
            $writeConnection->update($resource->getMainTable(), array('batch_id' => '1'), "batch_id = 0");
            $arrayMigrationConfigData = array('115' => false, '116' => false, '1164' => true);
            $this->handleDeleteMigrationConfigData($arrayMigrationConfigData);
        }
    }

    /**
     * Delete config data for migration from 1.1.6.4.
     */
    protected function delete1164MigrationConfigData()
    {
        $helper = $this->getHelper();

        $helper->getConfig()->deleteConfig(
            Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_FROM_1164,
            'default',
            0
        );
    }

    /**
     * Migrate data from version 1.1.21.
     *
     * @param $initialTime
     */
    protected function _migrateFrom1120($initialTime)
    {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();
        $webhookHelper = $this->getWebhookHelper();

        if (!$dateHelper->timePassed($initialTime)) {
            // Get all stores data.
            $stores = $helper->getMageApp()->getStores();

            $events = array(
                'subscribe' => true,
                'unsubscribe' => true,
                'profile' => true,
                'cleaned' => true,
                'upemail' => true,
                'campaign' => false
            );

            $sources = array(
                'user' => true,
                'admin' => true,
                'api' => false
            );

            foreach ($stores as $storeId => $store) {
                // Gets the ListId and WebhookId for the iterated store.
                $listId = $helper->getGeneralList($scopeId, $scope);
                $webhookId = $webhookHelper->getWebhookId($scopeId, $scope);

                // Edits the webhook with the new $event array.
                $helper
                    ->getApi($storeId, $store)
                    ->getLists()
                    ->getWebhooks()
                    ->edit($listId, $webhookId, null, $events, $sources);

                if ($dateHelper->timePassed($initialTime)) {
                    $finished = false;
                    break;
                }
            }

            $arrayMigrationConfigData = array('115' => false, '116' => false, '1164' => false, '1120' => true);
            $this->handleDeleteMigrationConfigData($arrayMigrationConfigData);
        }
    }

    /**
     * Delete config data for migration from 1.1.21.
     */
    protected function delete1120MigrationConfigData()
    {
        $helper = $this->getHelper();

        $helper->getConfig()->deleteConfig(
            Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_FROM_1120,
            'default',
            0
        );
    }

    /**
     * @param $arrayMigrationConfigData
     */
    public function handleDeleteMigrationConfigData($arrayMigrationConfigData)
    {
        $helper = $this->getHelper();
        foreach ($arrayMigrationConfigData as $migrationConfigData => $value) {
            if ($migrationConfigData == '115' && $value) {
                $this->delete115MigrationConfigData();
            }

            if ($migrationConfigData == '116' && $value) {
                $this->delete116MigrationConfigData();
            }

            if ($migrationConfigData == '1164' && $value) {
                $this->delete1164MigrationConfigData();
            }

            if ($migrationConfigData == '1120' && $value) {
                $this->delete1120MigrationConfigData();
            }
        }

        $helper->getConfig()->cleanCache();
    }

    /**
     * @var Ebizmarts_SqualoMail_Helper_Data
     */
    protected function getHelper()
    {
        return $this->_helper;
    }

    /**
     * @var Ebizmarts_SqualoMail_Helper_Date
     */
    protected function getDateHelper()
    {
        return $this->_dateHelper;
    }

    /**
     * @var Ebizmarts_SqualoMail_Helper_Webhook
     */
    protected function getWebhookHelper()
    {
        return $this->_webhookHelper;
    }
}
