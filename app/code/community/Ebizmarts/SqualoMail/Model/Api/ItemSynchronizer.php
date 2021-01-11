<?php

/**
 * squalomail-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Ebizmarts_SqualoMail_Model_Api_ItemSynchronizer
{
    /**
     * @var Ebizmarts_SqualoMail_Helper_Data
     */
    protected $_squalomailHelper;

    /**
     * @var Ebizmarts_SqualoMail_Helper_Date
     */
    protected $_squalomailDateHelper;

    /**
     * @return mixed
     */
    public function getSqualomailStoreId()
    {
        return $this->_squalomailStoreId;
    }

    /**
     * @param mixed $squalomailStoreId
     */
    public function setSqualomailStoreId($squalomailStoreId)
    {
        $this->_squalomailStoreId = $squalomailStoreId;
    }

    protected $_squalomailStoreId;

    protected $_magentoStoreId;

    /**
     * @return mixed
     */
    public function getMagentoStoreId()
    {
        return $this->_magentoStoreId;
    }

    /**
     * @param mixed $magentoStoreId
     */
    public function setMagentoStoreId($magentoStoreId)
    {
        $this->_magentoStoreId = $magentoStoreId;
    }

    public function __construct()
    {
        $this->_squalomailHelper = Mage::helper('squalomail');
        $this->_squalomailDateHelper = Mage::helper('squalomail/date');
    }

    /**
     * @param $id
     * @param null $syncDelta
     * @param null $syncError
     * @param int $syncModified
     * @param null $syncedFlag
     * @param null $syncDeleted
     * @param null $token
     * @param bool $saveOnlyIfExists
     * @param bool $allowBatchRemoval
     * @param int $deletedRelatedId
     */
    protected function _updateSyncData(
        $id,
        $syncDelta = null,
        $syncError = null,
        $syncModified = 0,
        $syncDeleted = null,
        $syncedFlag = null,
        $token = null,
        $saveOnlyIfExists = false,
        $allowBatchRemoval = true,
        $deletedRelatedId = null
    ) {
        $type = $this->getItemType();

        if (!empty($type)) {
            $ecommerceSyncData = $this->getSqualomailEcommerceSyncDataModel();
            $ecommerceSyncData->saveEcommerceSyncData(
                $id,
                $type,
                $this->getSqualomailStoreId(),
                $syncDelta,
                $syncError,
                $syncModified,
                $syncDeleted,
                $token,
                $syncedFlag,
                $saveOnlyIfExists,
                $deletedRelatedId,
                $allowBatchRemoval
            );
        }
    }

    protected function addDeletedRelatedId($id, $relatedId)
    {
        $this->_updateSyncData(
            $id,
            null,
            null,
            0,
            1,
            null,
            null,
            true,
            false,
            $relatedId
        );
    }

    protected function addSyncDataError(
        $id,
        $error,
        $token = null,
        $saveOnlyIfExists = false,
        $syncDelta = null
    ) {
        $type = $this->getItemType();

        $this->logSyncError(
            $error,
            $type,
            $this->getMagentoStoreId(),
            'magento_side_error',
            'Invalid Magento Resource',
            0,
            $id,
            0
        );

        $this->_updateSyncData(
            $id,
            $syncDelta,
            $error,
            0,
            null,
            0,
            $token,
            $saveOnlyIfExists,
            -1
        );
    }

    protected function addSyncData($id)
    {
        $this->_updateSyncData($id);
    }

    protected function addSyncDataToken($id, $token)
    {
        $this->_updateSyncData(
            $id,
            null,
            null,
            0,
            null,
            null,
            $token
        );
    }

    protected function markSyncDataAsModified($id)
    {
        $this->_updateSyncData($id, null, null, 1, null, null, null, true);
    }

    protected function markSyncDataAsDeleted($id, $syncedFlag = null)
    {
        $this->_updateSyncData(
            $id,
            null,
            null,
            0,
            1,
            $syncedFlag
        );
    }

    /**
     * @param $error
     * @param $type
     * @param $title
     * @param $status
     * @param $originalId
     * @param $batchId
     * @param $storeId
     * @param $regType
     */
    protected function logSyncError(
        $error,
        $regType,
        $storeId,
        $type,
        $title,
        $status,
        $originalId,
        $batchId
    ) {
        $this->getHelper()->logError($error);

        try {
            $this->_logSqualomailError(
                $error, $type, $title,
                $status, $originalId, $batchId, $storeId, $regType
            );
        } catch (Exception $e) {
            $this->getHelper()->logError($e->getMessage());
        }
    }

    /**
     * @param $error
     * @param $type
     * @param $title
     * @param $status
     * @param $originalId
     * @param $batchId
     * @param $storeId
     * @param $regType
     *
     * @throws Exception
     */
    protected function _logSqualomailError(
        $error,
        $type,
        $title,
        $status,
        $originalId,
        $batchId,
        $storeId,
        $regType
    ) {
        $squalomailErrors = Mage::getModel('squalomail/squalomailerrors');

        $squalomailErrors->setType($type);
        $squalomailErrors->setTitle($title);
        $squalomailErrors->setStatus($status);
        $squalomailErrors->setErrors($error);
        $squalomailErrors->setRegtype($regType);
        $squalomailErrors->setOriginalId($originalId);
        $squalomailErrors->setBatchId($batchId);
        $squalomailErrors->setStoreId($storeId);
        $squalomailErrors->setSqualomailStoreId($this->getSqualomailStoreId());

        $squalomailErrors->save();
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    protected function getHelper()
    {
        return $this->_squalomailHelper;
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Date
     */
    protected function getDateHelper()
    {
        return $this->_squalomailDateHelper;
    }

    /**
     * @return mixed
     */
    public function getSqualomailEcommerceDataTableName()
    {
        return $this->getCoreResource()
            ->getTableName('squalomail/ecommercesyncdata');
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Ecommercesyncdata
     */
    public function getSqualomailEcommerceSyncDataModel()
    {
        return new Ebizmarts_SqualoMail_Model_Ecommercesyncdata();
    }

    /**
     * @param $magentoStoreId
     * @return mixed
     */
    public function getWebSiteIdFromMagentoStoreId($magentoStoreId)
    {
        return Mage::getModel('core/store')->load($magentoStoreId)->getWebsiteId();
    }

    /**
     * @return Mage_Core_Model_Resource
     */
    public function getCoreResource()
    {
        return Mage::getSingleton('core/resource');
    }

    /**
     * @return string
     */
    protected function getItemType()
    {
        return null;
    }
}
