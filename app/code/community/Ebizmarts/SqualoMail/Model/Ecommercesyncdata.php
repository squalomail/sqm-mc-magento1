<?php
/**
 * sqm-mc-magento1 Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/16/16 6:23 PM
 * @file:     SqualomailSychBatches.php
 */

class Ebizmarts_SqualoMail_Model_Ecommercesyncdata extends Mage_Core_Model_Abstract
{
    /**
     * Initialize model
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('squalomail/ecommercesyncdata');
    }

    /**
     * Save entry for ecommerce_sync_data table overwriting old item if exists or creating a new one if it does not.
     *
     * @param       $itemId
     * @param       $itemType
     * @param       $squalomailStoreId
     * @param null  $syncDelta
     * @param null  $syncError
     * @param int   $syncModified
     * @param null  $syncDeleted
     * @param null  $token
     * @param null  $syncedFlag
     * @param bool  $saveOnlyIfexists
     * @param null  $deletedRelatedId
     * @param bool  $allowBatchRemoval
     */
    public function saveEcommerceSyncData(
        $itemId,
        $itemType,
        $squalomailStoreId,
        $syncDelta = null,
        $syncError = null,
        $syncModified = 0,
        $syncDeleted = null,
        $token = null,
        $syncedFlag = null,
        $saveOnlyIfexists = false,
        $deletedRelatedId = null,
        $allowBatchRemoval = true
    ) {
        $ecommerceSyncDataItem = $this->getEcommerceSyncDataItem($itemId, $itemType, $squalomailStoreId);

        if (!$saveOnlyIfexists || $ecommerceSyncDataItem->getSqualomailSyncDelta()) {
            $this->setEcommerceSyncDataItemValues(
                $itemId, $itemType, $syncDelta, $syncError, $syncModified, $syncDeleted,
                $token, $syncedFlag, $deletedRelatedId, $allowBatchRemoval, $ecommerceSyncDataItem
            );

            $ecommerceSyncDataItem->save();
        }
    }

    /**
     *  Load Ecommerce Sync Data Item if exists or set the values for a new one and return it.
     *
     * @param  $itemId
     * @param  $itemType
     * @param  $squalomailStoreId
     * @return Ebizmarts_SqualoMail_Model_Ecommercesyncdata
     */
    public function getEcommerceSyncDataItem($itemId, $itemType, $squalomailStoreId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('related_id', array('eq' => $itemId))
            ->addFieldToFilter('type', array('eq' => $itemType))
            ->addFieldToFilter('squalomail_store_id', array('eq' => $squalomailStoreId))
            ->setCurPage(1)
            ->setPageSize(1);

        if ($collection->getSize()) {
            $ecommerceSyndDataItem = $collection->getLastItem();
        } else {
            $ecommerceSyndDataItem = $this->setData("related_id", $itemId)
                ->setData("type", $itemType)
                ->setData("squalomail_store_id", $squalomailStoreId);
        }

        return $ecommerceSyndDataItem;
    }

    /**
     * @param $itemId
     * @param $itemType
     * @return Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata_Collection
     */
    public function getAllEcommerceSyncDataItemsPerId($itemId, $itemType)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('related_id', array('eq' => $itemId))
            ->addFieldToFilter('type', array('eq' => $itemType));

        return $collection;
    }

    /**
     * @param $itemId
     * @param $itemType
     * @param $syncDelta
     * @param $syncError
     * @param $syncModified
     * @param $syncDeleted
     * @param $token
     * @param $syncedFlag
     * @param $deletedRelatedId
     * @param $allowBatchRemoval
     * @param Ebizmarts_SqualoMail_Model_Ecommercesyncdata $ecommerceSyncDataItem
     */
    protected function setEcommerceSyncDataItemValues(
        $itemId,
        $itemType,
        $syncDelta,
        $syncError,
        $syncModified,
        $syncDeleted,
        $token,
        $syncedFlag,
        $deletedRelatedId,
        $allowBatchRemoval,
        Ebizmarts_SqualoMail_Model_Ecommercesyncdata $ecommerceSyncDataItem
    ) {
        if ($itemId) {
            $ecommerceSyncDataItem->setData("related_id", $itemId);
        }

        if ($syncDelta) {
            $ecommerceSyncDataItem->setData("squalomail_sync_delta", $syncDelta);
        } elseif ($allowBatchRemoval === true) {
            $ecommerceSyncDataItem->setData("batch_id", null);
        }

        if ($allowBatchRemoval === -1) {
            $ecommerceSyncDataItem->setData("batch_id", '-1');
        }

        if ($syncError) {
            $ecommerceSyncDataItem->setData("squalomail_sync_error", $syncError);
        }

        //Always set modified value to 0 when saving sync delta or errors.
        $ecommerceSyncDataItem->setData("squalomail_sync_modified", $syncModified);

        if ($syncDeleted !== null) {
            $ecommerceSyncDataItem->setData("squalomail_sync_deleted", $syncDeleted);

            if ($itemType == Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT && $syncError == '') {
                $ecommerceSyncDataItem->setData("squalomail_sync_error", $syncError);
            }
        }

        if ($token) {
            $ecommerceSyncDataItem->setData("squalomail_token", $token);
        }

        if ($deletedRelatedId) {
            $ecommerceSyncDataItem->setData("deleted_related_id", $deletedRelatedId);
        }

        if ($syncedFlag !== null) {
            $ecommerceSyncDataItem->setData("squalomail_synced_flag", $syncedFlag);
        }
    }
}
