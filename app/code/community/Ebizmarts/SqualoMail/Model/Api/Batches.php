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
class Ebizmarts_SqualoMail_Model_Api_Batches
{
    const SEND_PROMO_ENABLED = 1;

    /**
     * @var Ebizmarts_SqualoMail_Helper_Data
     */
    protected $_squalomailHelper;

    /**
     * @var Ebizmarts_SqualoMail_Helper_Date
     */
    protected $_squalomailDateHelper;

    /**
     * @var Ebizmarts_SqualoMail_Helper_Curl
     */
    protected $_squalomailCurlHelper;

    /**
     * @var Ebizmarts_SqualoMail_Model_Api_Customers
     */
    protected $_apiCustomers;

    /**
     * @var Ebizmarts_SqualoMail_Model_Api_Products
     */
    protected $_apiProducts;

    /**
     * @var Ebizmarts_SqualoMail_Model_Api_Carts
     */
    protected $_apiCarts;

    /**
     * @var Ebizmarts_SqualoMail_Model_Api_Orders
     */
    protected $_apiOrders;

    /**
     * @var Ebizmarts_SqualoMail_Model_Api_PromoRules
     */
    protected $_apiPromoRules;

    /**
     * @var Ebizmarts_SqualoMail_Model_Api_PromoCodes
     */
    protected $_apiPromoCodes;

    /**
     * @var Ebizmarts_SqualoMail_Model_Api_Subscribers
     */
    protected $_apiSubscribers;

    public function __construct()
    {
        $this->_squalomailHelper = Mage::helper('squalomail');
        $this->_squalomailDateHelper = Mage::helper('squalomail/date');
        $this->_squalomailCurlHelper = Mage::helper('squalomail/curl');

        $this->_apiProducts = Mage::getModel('squalomail/api_products');
        $this->_apiCustomers = Mage::getModel('squalomail/api_customers');
        $this->_apiCarts = Mage::getModel('squalomail/api_carts');
        $this->_apiOrders = Mage::getModel('squalomail/api_orders');
        $this->_apiPromoRules = Mage::getModel('squalomail/api_promoRules');
        $this->_apiPromoCodes = Mage::getModel('squalomail/api_promoCodes');
        $this->_apiSubscribers = Mage::getModel('squalomail/api_subscribers');
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    protected function getHelper()
    {
        return $this->_squalomailHelper;
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Curl
     */
    protected function getSqualomailCurlHelper()
    {
        return $this->_squalomailCurlHelper;
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Date
     */
    protected function getDateHelper()
    {
        return $this->_squalomailDateHelper;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_Stores
     */
    protected function getApiStores()
    {
        return Mage::getModel('squalomail/api_stores');
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_Customers
     */
    protected function getApiCustomers()
    {
        return $this->_apiCustomers;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_Products
     */
    public function getApiProducts()
    {
        return $this->_apiProducts;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_Carts
     */
    public function getApiCarts()
    {
        return $this->_apiCarts;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_Orders
     */
    public function getApiOrders()
    {
        return $this->_apiOrders;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_PromoRules
     */
    public function getApiPromoRules()
    {
        return $this->_apiPromoRules;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_PromoCodes
     */
    public function getApiPromoCodes()
    {
        return $this->_apiPromoCodes;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_Subscribers
     */
    protected function getApiSubscribers()
    {
        return $this->_apiSubscribers;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Synchbatches
     */
    protected function getSyncBatchesModel()
    {
        return Mage::getModel('squalomail/synchbatches');
    }

    /**
     * @return array
     */
    protected function getStores()
    {
        return Mage::app()->getStores();
    }

    /**
     * @return string
     */
    public function getMagentoBaseDir()
    {
        return Mage::getBaseDir();
    }

    /**
     * @param $baseDir
     * @param $batchId
     * @return bool
     */
    public function batchDirExists($baseDir, $batchId)
    {
        return $this->getSqualomailFileHelper()
            ->fileExists($baseDir . DS . 'var' . DS . 'squalomail' . DS . $batchId, false);
    }

    /**
     * @param $baseDir
     * @param $batchId
     * @return bool
     */
    public function removeBatchDir($baseDir, $batchId)
    {
        return $this->getSqualomailFileHelper()
            ->rmDir($baseDir . DS . 'var' . DS . 'squalomail' . DS . $batchId);
    }

    /**
     * Get Results and send Ecommerce Batches.
     */
    public function handleEcommerceBatches()
    {
        $helper = $this->getHelper();
        $stores = $this->getStores();
        $helper->handleResendDataBefore();

        foreach ($stores as $store) {
            $storeId = $store->getId();

            if ($helper->isEcomSyncDataEnabled($storeId)) {
                if ($helper->ping($storeId)) {
                    $this->_getResults($storeId);
                    $this->_sendEcommerceBatch($storeId);
                } else {
                    $helper->logError(
                        "Could not connect to SqualoMail: Make sure the API Key is correct "
                        . "and there is an internet connection"
                    );
                    return;
                }
            }
        }

        $helper->handleResendDataAfter();
        $syncedDateArray = array();

        foreach ($stores as $store) {
            $storeId = $store->getId();
            $syncedDateArray = $this->addSyncValueToArray($storeId, $syncedDateArray);
        }

        $this->handleSyncingValue($syncedDateArray);
    }

    /**
     * Get Results and send Subscriber Batches.
     */
    public function handleSubscriberBatches()
    {
        $this->_sendSubscriberBatches();
    }

    /**
     * Get results of batch operations sent to SqualoMail.
     *
     * @param       $magentoStoreId
     * @param bool  $isEcommerceData
     * @throws Mage_Core_Exception
     */
    public function _getResults($magentoStoreId, $isEcommerceData = true, $status = Ebizmarts_SqualoMail_Helper_Data::BATCH_PENDING)
    {
        $helper = $this->getHelper();
        $squalomailStoreId = $helper->getSQMStoreId($magentoStoreId);
        $collection = $this->getSyncBatchesModel()->getCollection()->addFieldToFilter('status', array('eq' => $status));

        if ($isEcommerceData) {
            $collection->addFieldToFilter('store_id', array('eq' => $squalomailStoreId));
            $enabled = $helper->isEcomSyncDataEnabled($magentoStoreId);
        } else {
            $collection->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
            $enabled = $helper->isSubscriptionEnabled($magentoStoreId);
        }

        if ($enabled) {
            $helper->logBatchStatus('Get results from Squalomail for Magento store ' . $magentoStoreId);

            foreach ($collection as $item) {
                try {
                    $batchId = $item->getBatchId();
                    $files = $this->getBatchResponse($batchId, $magentoStoreId);
                    $this->_saveItemStatus($item, $files, $batchId, $squalomailStoreId, $magentoStoreId);
                    $baseDir = $this->getMagentoBaseDir();

                    if ($this->batchDirExists($baseDir, $batchId)) {
                        $this->removeBatchDir($baseDir, $batchId);
                    }
                } catch (Exception $e) {
                    Mage::log("Error with a response: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * @param $item
     * @param $files
     * @param $batchId
     * @param $squalomailStoreId
     * @param $magentoStoreId
     * @throws Mage_Core_Exception
     */
    protected function _saveItemStatus($item, $files, $batchId, $squalomailStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();

        if (!empty($files)) {
            if (isset($files['error'])) {
                $item->setStatus('error');
                $item->save();
                $helper->logBatchStatus('There was an error getting the result ');
            } else {
                $this->processEachResponseFile($files, $batchId, $squalomailStoreId, $magentoStoreId);
                $item->setStatus('completed');
                $item->save();
            }
        }
    }

    /**
     * Send Customers, Products, Orders, Carts to SqualoMail store for given scope.
     * Return true if SqualoMail store is reset in the process.
     *
     * @param  $magentoStoreId
     * @throws Mage_Core_Exception
     */
    public function _sendEcommerceBatch($magentoStoreId)
    {
        $helper = $this->getHelper();
        $squalomailStoreId = $helper->getSQMStoreId($magentoStoreId);

        try {
            $this->deleteUnsentItems();

            if ($helper->isEcomSyncDataEnabled($magentoStoreId)) {
                $helper->resetCountersSentPerBatch();
                $batchArray = array();
                //customer operations
                $helper->logBatchStatus('Generate Customers Payload');
                $apiCustomers = $this->getApiCustomers();
                $apiCustomers->setSqualomailStoreId($squalomailStoreId);
                $apiCustomers->setMagentoStoreId($magentoStoreId);

                $customersArray = $apiCustomers->createBatchJson();
                $customerAmount = count($customersArray);
                $batchArray['operations'] = $customersArray;

                //product operations
                $helper->logBatchStatus('Generate Products Payload');

                $apiProducts = $this->getApiProducts();
                $apiProducts->setSqualomailStoreId($squalomailStoreId);
                $apiProducts->setMagentoStoreId($magentoStoreId);

                $productsArray = $apiProducts->createBatchJson();
                $productAmount = count($productsArray);
                $batchArray['operations'] = array_merge($batchArray['operations'], $productsArray);

                if ($helper->getSQMIsSyncing($squalomailStoreId, $magentoStoreId) === 1) {
                    $helper->logBatchStatus('No Carts will be synced until the store is completely synced');
                } else {
                    //cart operations
                    $helper->logBatchStatus('Generate Carts Payload');
                    $apiCarts = $this->getApiCarts();
                    $apiCarts->setSqualomailStoreId($squalomailStoreId);
                    $apiCarts->setMagentoStoreId($magentoStoreId);

                    $cartsArray = $apiCarts->createBatchJson();
                    $batchArray['operations'] = array_merge($batchArray['operations'], $cartsArray);
                }

                //order operations
                $helper->logBatchStatus('Generate Orders Payload');
                $apiOrders = $this->getApiOrders();
                $apiOrders->setSqualomailStoreId($squalomailStoreId);
                $apiOrders->setMagentoStoreId($magentoStoreId);

                $ordersArray = $apiOrders->createBatchJson();
                $orderAmount = count($ordersArray);
                $batchArray['operations'] = array_merge($batchArray['operations'], $ordersArray);

                if ($helper->getPromoConfig($magentoStoreId) == self::SEND_PROMO_ENABLED) {
                    //promo rule operations
                    $helper->logBatchStatus('Generate Promo Rules Payload');
                    $apiPromoRules = $this->getApiPromoRules();
                    $apiPromoRules->setSqualomailStoreId($squalomailStoreId);
                    $apiPromoRules->setMagentoStoreId($magentoStoreId);

                    $promoRulesArray = $apiPromoRules->createBatchJson();
                    $batchArray['operations'] = array_merge($batchArray['operations'], $promoRulesArray);

                    //promo code operations
                    $helper->logBatchStatus('Generate Promo Codes Payload');
                    $apiPromoCodes = $this->getApiPromoCodes();
                    $apiPromoCodes->setSqualomailStoreId($squalomailStoreId);
                    $apiPromoCodes->setMagentoStoreId($magentoStoreId);

                    $promoCodesArray = $apiPromoCodes->createBatchJson();
                    $batchArray['operations'] = array_merge($batchArray['operations'], $promoCodesArray);
                }

                //deleted product operations
                $helper->logBatchStatus('Generate Deleted Products Payload');
                $deletedProductsArray = $apiProducts->createDeletedProductsBatchJson();
                $batchArray['operations'] = array_merge($batchArray['operations'], $deletedProductsArray);
                $batchJson = null;
                $batchResponse = null;

                try {
                    $this->_processBatchOperations($batchArray, $squalomailStoreId, $magentoStoreId);
                    $this->_updateSyncingFlag(
                        $customerAmount, $productAmount, $orderAmount,
                        $squalomailStoreId, $magentoStoreId
                    );
                } catch (Ebizmarts_SqualoMail_Helper_Data_ApiKeyException $e) {
                    $helper->logError($e->getMessage());
                } catch (SqualoMail_Error $e) {
                    $helper->logError($e->getFriendlyMessage());

                    if ($batchJson && !isset($batchResponse['id'])) {
                        $helper->logRequest($batchJson);
                    }
                } catch (Exception $e) {
                    $helper->logError($e->getMessage());
                    $helper->logError("Json encode fails");
                    $helper->logError($batchArray);
                }
            }
        } catch (SqualoMail_Error $e) {
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * @param $batchArray
     * @param $squalomailStoreId
     * @param $magentoStoreId
     * @throws Ebizmarts_SqualoMail_Helper_Data_ApiKeyException
     * @throws Mage_Core_Exception
     * @throws SqualoMail_Error
     * @throws SqualoMail_HttpError
     */

    protected function _processBatchOperations($batchArray, $squalomailStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $squalomailApi = $helper->getApi($magentoStoreId);

        if (!empty($batchArray['operations'])) {
            $batchJson = json_encode($batchArray);

            if ($batchJson === false) {
                $helper->logRequest('Json encode error ' . json_last_error_msg());
            } elseif (empty($batchJson)) {
                $helper->logRequest('An empty operation was detected');
            } else {
                $helper->logBatchStatus('Send batch operation');
                $batchResponse = $squalomailApi->getBatchOperation()->add($batchJson);
                $helper->logRequest($batchJson, $batchResponse['id']);
                //save batch id to db
                $batch = $this->getSyncBatchesModel();
                $batch->setStoreId($squalomailStoreId)->setBatchId($batchResponse['id'])->setStatus($batchResponse['status']);
                $batch->save();
                $this->markItemsAsSent($batchResponse['id'], $squalomailStoreId);
                $this->_showResumeEcommerce($batchResponse['id'], $magentoStoreId);
            }
        }
    }

    /**
     * @param $customerAmount
     * @param $productAmount
     * @param $orderAmount
     * @param $squalomailStoreId
     * @param $magentoStoreId
     * @throws Mage_Core_Exception
     */
    protected function _updateSyncingFlag(
        $customerAmount,
        $productAmount,
        $orderAmount,
        $squalomailStoreId,
        $magentoStoreId
    ) {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();
        $itemAmount = ($customerAmount + $productAmount + $orderAmount);
        $syncingFlag = $helper->getSQMIsSyncing($squalomailStoreId, $magentoStoreId);

        if ($this->shouldFlagAsSyncing($syncingFlag, $itemAmount, $helper)) {
            //Set is syncing per scope in 1 until sync finishes.
            $configValue = array(
                array(Ebizmarts_SqualoMail_Model_Config::GENERAL_SQMISSYNCING . "_$squalomailStoreId", 1)
            );
            $helper->saveSqualomailConfig($configValue, $magentoStoreId, 'stores');
        } else {
            if ($this->shouldFlagAsSynced($syncingFlag, $itemAmount)) {
                //Set is syncing per scope to a date because it is not sending any more items.
                $configValue = array(
                    array(
                        Ebizmarts_SqualoMail_Model_Config::GENERAL_SQMISSYNCING . "_$squalomailStoreId",
                        $dateHelper->formatDate(null, 'Y-m-d H:i:s')
                    )
                );
                $helper->saveSqualomailConfig($configValue, $magentoStoreId, 'stores');
            }
        }
    }

    /**
     * @param $batchId
     */
    protected function deleteBatchItems($batchId)
    {
        $helper = $this->getHelper();
        $resource = $helper->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('squalomail/ecommercesyncdata');
        $where = array("batch_id = '$batchId'");
        $connection->delete($tableName, $where);
    }

    protected function deleteUnsentItems()
    {
        $helper = $this->getHelper();
        $resource = $helper->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('squalomail/ecommercesyncdata');
        $where = array("batch_id IS NULL AND squalomail_sync_modified != 1");
        $connection->delete($tableName, $where);
    }

    public function ecommerceDeleteCallback($args)
    {
        $ecommerceData = Mage::getModel('squalomail/ecommercesyncdata');
        $ecommerceData->setData($args['row']);
        $ecommerceData->delete();
    }

    protected function markItemsAsSent($batchResponseId, $squalomailStoreId)
    {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();

        $resource = $helper->getCoreResource();
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('squalomail/ecommercesyncdata');
        $where = array("batch_id IS NULL AND squalomail_store_id = ?" => $squalomailStoreId);
        $connection->update(
            $tableName,
            array(
                'batch_id' => $batchResponseId,
                'squalomail_sync_delta' => $dateHelper->formatDate(null, 'Y-m-d H:i:s')
            ),
            $where
        );
    }

    public function ecommerceSentCallback($args)
    {
        $ecommerceData = Mage::getModel('squalomail/ecommercesyncdata');
        $ecommerceData->setData($args['row']); // map data to customer model
        $writeAdapter = Mage::getSingleton('core/resource')->getConnection('core_write');
        $insertData = array(
            'id' => $ecommerceData->getId(),
            'related_id' => $ecommerceData->getRelatedId(),
            'type' => $ecommerceData->getType(),
            'squalomail_store_id' => $ecommerceData->getSqualomailStoreId(),
            'squalomail_sync_error' => $ecommerceData->getSqualomailSyncError(),
            'squalomail_sync_delta' => $ecommerceData->getSqualomailSyncDelta(),
            'squalomail_sync_modified' => $ecommerceData->getSqualomailSyncModified(),
            'squalomail_sync_deleted' => $ecommerceData->getSqualomailSyncDeleted(),
            'squalomail_token' => $ecommerceData->getSqualomailToken(),
            'batch_id' => $ecommerceData->getBatchId()
        );
        $resource = Mage::getResourceModel('squalomail/ecommercesyncdata');
        $writeAdapter->insertOnDuplicate(
            $resource->getMainTable(),
            $insertData,
            array(
                'id',
                'related_id',
                'type',
                'squalomail_store_id',
                'squalomail_sync_error',
                'squalomail_sync_delta',
                'squalomail_sync_modified',
                'squalomail_sync_deleted',
                'squalomail_token',
                'batch_id'
            )
        );
    }

    /**
     * Send Subscribers batch on each store view, return array of batches responses.
     *
     * @return array
     */
    protected function _sendSubscriberBatches()
    {
        $helper = $this->getHelper();

        $subscriberLimit = $helper->getSubscriberAmountLimit();
        $stores = $this->getStores();
        $batchResponses = array();
        foreach ($stores as $store) {
            $storeId = $store->getId();
            $this->_getResults($storeId, false);
            if ($subscriberLimit > 0) {
                list($batchResponse, $subscriberLimit) = $this->sendStoreSubscriberBatch($storeId, $subscriberLimit);
                if ($batchResponse) {
                    $batchResponses[] = $batchResponse;
                }
            } else {
                break;
            }
        }

        $this->_getResults(0, false);
        if ($subscriberLimit > 0) {
            list($batchResponse, $subscriberLimit) = $this->sendStoreSubscriberBatch(0, $subscriberLimit);
            if ($batchResponse) {
                $batchResponses[] = $batchResponse;
            }
        }

        return $batchResponses;
    }

    /**
     * Send Subscribers batch on particular store view, return batch response.
     *
     * @param  $storeId
     * @param  $limit
     * @return array|null
     */
    public function sendStoreSubscriberBatch($storeId, $limit)
    {
        $helper = $this->getHelper();

        try {
            if ($helper->isSubscriptionEnabled($storeId)) {
                $helper->resetCountersSubscribers();

                $listId = $helper->getGeneralList($storeId);

                $batchArray = array();

                //subscriber operations
                $subscribersArray = $this->getApiSubscribers()->createBatchJson($listId, $storeId, $limit);
                $limit -= count($subscribersArray);

                $batchArray['operations'] = $subscribersArray;

                if (!empty($batchArray['operations'])) {
                    $batchJson = json_encode($batchArray);

                    if ($batchJson === false) {
                        $helper->logRequest('Json encode error ' . json_last_error_msg());
                    } elseif ($batchJson == '') {
                        $helper->logRequest('An empty operation was detected');
                    } else {
                        try {
                            $squalomailApi = $helper->getApi($storeId);
                            $batchResponse = $squalomailApi->getBatchOperation()->add($batchJson);
                            $helper->logRequest($batchJson, $batchResponse['id']);

                            //save batch id to db
                            $batch = $this->getSyncBatchesModel();
                            $batch->setStoreId($storeId)
                                ->setBatchId($batchResponse['id'])
                                ->setStatus($batchResponse['status']);
                            $batch->save();
                            $this->_showResumeSubscriber($batchResponse['id'], $storeId);

                            return array($batchResponse, $limit);
                        } catch (Ebizmarts_SqualoMail_Helper_Data_ApiKeyException $e) {
                            $helper->logError($e->getMessage());
                        } catch (SqualoMail_Error $e) {
                            $helper->logRequest($batchJson);
                            $helper->logError($e->getFriendlyMessage());
                        }
                    }
                }
            }
        } catch (SqualoMail_Error $e) {
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }

        return array(null, $limit);
    }

    /**
     * @param $batchId
     * @param $magentoStoreId
     * @return array
     */
    public function getBatchResponse($batchId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $fileHelper = $this->getSqualomailFileHelper();
        $files = array();

        try {
            $baseDir = $this->getMagentoBaseDir();
            $api = $helper->getApi($magentoStoreId);

            if ($api) {
                // check the status of the job
                $response = $api->batchOperation->status($batchId);

                if (isset($response['status']) && $response['status'] == 'finished') {
                    // get the tar.gz file with the results
                    $fileUrl = urldecode($response['response_body_url']);
                    $fileName = $baseDir . DS . 'var' . DS . 'squalomail' . DS . $batchId . '.tar.gz';
                    $fd = fopen($fileName, 'w');

                    $curlOptions = array(
                        CURLOPT_RETURNTRANSFER => 1,
                        CURLOPT_FILE => $fd,
                        CURLOPT_FOLLOWLOCATION => true, // this will follow redirects
                    );

                    $curlHelper = $this->getSqualomailCurlHelper();
                    $curlResult = $curlHelper->curlExec($fileUrl, Zend_Http_Client::GET, $curlOptions);

                    fclose($fd);
                    $fileHelper->mkDir($baseDir . DS . 'var' . DS . 'squalomail' . DS . $batchId, 0750, true);
                    $archive = new Mage_Archive();

                    if ($fileHelper->fileExists($fileName)) {
                        $files = $this->_unpackBatchFile($files, $batchId, $archive, $fileName, $baseDir);
                    }
                }
            }
        } catch (Ebizmarts_SqualoMail_Helper_Data_ApiKeyException $e) {
            $helper->logError($e->getMessage());
            $files['error'] = $e->getMessage();
        } catch (SqualoMail_Error $e) {
            $this->deleteBatchItems($batchId);
            $files['error'] = $e->getFriendlyMessage();
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $files['error'] = $e->getMessage();
            $helper->logError($e->getMessage());
        }

        return $files;
    }

    /**
     * @param $files
     * @param $batchId
     * @param $archive Mage_Archive
     * @param $fileName
     * @param $baseDir
     * @return array
     */
    protected function _unpackBatchFile($files, $batchId, $archive, $fileName, $baseDir)
    {
        $path = $baseDir . DS . 'var' . DS . 'squalomail' . DS . $batchId;
        $archive->unpack($fileName, $path);
        $archive->unpack($path . DS . $batchId . '.tar', $path);
        $fileHelper = $this->getSqualomailFileHelper();
        $dirItems = new DirectoryIterator($path);

        foreach ($dirItems as $index => $dirItem) {

            if ($dirItem->isFile() && $dirItem->getExtension() == 'json'){
                $files[] = $path . DS . $dirItem->getBasename();
            }
        }
        $fileHelper->rm($path . DS . $batchId . '.tar');
        $fileHelper->rm($fileName);

        return $files;
    }

    /**
     * @param $files
     * @param $batchId
     * @param $squalomailStoreId
     * @param $magentoStoreId
     * @throws Mage_Core_Exception
     */
    protected function processEachResponseFile($files, $batchId, $squalomailStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $helper->resetCountersDataSentToSqualomail();
        $fileHelper = $this->getSqualomailFileHelper();

        foreach ($files as $file) {
            $fileContent = $fileHelper->read($file);
            $items = json_decode($fileContent, true);

            if ($items !== false) {
                foreach ($items as $item) {
                    $line = explode('_', $item['operation_id']);
                    $store = explode('-', $line[0]);
                    $type = $line[1];
                    $id = $line[3];

                    if ($item['status_code'] != 200) {
                        $squalomailErrors = Mage::getModel('squalomail/squalomailerrors');
                        //parse error
                        $response = json_decode($item['response'], true);
                        $errorDetails = $this->_processFileErrors($response);

                        if (strstr($errorDetails, 'already exists')) {
                            $this->setItemAsModified($squalomailStoreId, $id, $type);
                            $helper->modifyCounterDataSentToSqualomail($type);
                            continue;
                        }

                        $error = $this->_getError($type, $squalomailStoreId, $id, $response);
                        $this->saveSyncData(
                            $id,
                            $type,
                            $squalomailStoreId,
                            null,
                            $error,
                            0,
                            null,
                            null,
                            0,
                            true
                        );

                        $squalomailErrors->setType($response['type']);
                        $squalomailErrors->setTitle($response['title']);
                        $squalomailErrors->setStatus($item['status_code']);
                        $squalomailErrors->setErrors($errorDetails);
                        $squalomailErrors->setRegtype($type);
                        $squalomailErrors->setOriginalId($id);
                        $squalomailErrors->setBatchId($batchId);
                        $squalomailErrors->setStoreId($store[1]);

                        if ($type != Ebizmarts_SqualoMail_Model_Config::IS_SUBSCRIBER) {
                            $squalomailErrors->setSqualomailStoreId($squalomailStoreId);
                        }

                        $squalomailErrors->save();
                        $helper->modifyCounterDataSentToSqualomail($type, true);
                        $helper->logError($error);
                    } else {
                        $syncDataItem = $this->getDataProduct($squalomailStoreId, $id, $type);

                        if (!$syncDataItem->getSqualomailSyncModified()) {
                            $syncModified = $this->enableMergeFieldsSending($type, $syncDataItem);

                            $this->saveSyncData(
                                $id,
                                $type,
                                $squalomailStoreId,
                                null,
                                '',
                                $syncModified,
                                null,
                                null,
                                1,
                                true
                            );
                            $helper->modifyCounterDataSentToSqualomail($type);
                        }
                    }
                }
            }

            $fileHelper->rm($file);
        }
        $this->_showResumeDataSentToSqualomail($magentoStoreId);
    }

    /**
     * @param $type
     * @param $squalomailStoreId
     * @param $id
     * @param $response
     * @return string
     */
    protected function _getError($type, $squalomailStoreId, $id, $response)
    {
        $error = $response['title'] . " : " . $response['detail'];

        if ($type == Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT) {
            $dataProduct = $this->getDataProduct($squalomailStoreId, $id, $type);
            $isProductDisabledInMagento = Ebizmarts_SqualoMail_Model_Api_Products::PRODUCT_DISABLED_IN_MAGENTO;

            if ($dataProduct->getSqualomailSyncDeleted()
                || $dataProduct->getSqualomailSyncError() == $isProductDisabledInMagento
            ) {
                $error = $isProductDisabledInMagento;
            }
        }

        return $error;
    }

    /**
     * @param $response
     * @return string
     */
    protected function _processFileErrors($response)
    {
        $errorDetails = "";

        if (!empty($response['errors'])) {
            foreach ($response['errors'] as $error) {
                if (isset($error['field']) && isset($error['message'])) {
                    $errorDetails .= $errorDetails != "" ? " / " : "";
                    $errorDetails .= $error['field'] . " : " . $error['message'];
                }
            }
        }

        if ($errorDetails == "") {
            $errorDetails = $response['detail'];
        }

        return $errorDetails;
    }

    /**
     * Handle batch for order id replacement with the increment id in SqualoMail.
     *
     * @param $initialTime
     * @param $magentoStoreId
     */
    public function replaceAllOrders($initialTime, $magentoStoreId)
    {
        $helper = $this->getHelper();
        try {
            $this->_getResults($magentoStoreId);

            //handle order replacement
            $squalomailStoreId = $helper->getSQMStoreId($magentoStoreId);

            $batchArray['operations'] = Mage::getModel('squalomail/api_orders')->replaceAllOrdersBatch(
                $initialTime,
                $squalomailStoreId,
                $magentoStoreId
            );
            try {
                /**
                 * @var $squalomailApi Ebizmarts_SqualoMail
                 */
                $squalomailApi = $helper->getApi($magentoStoreId);

                if (!empty($batchArray['operations'])) {
                    $batchJson = json_encode($batchArray);

                    if ($batchJson === false) {
                        $helper->logRequest('Json encode error: ' . json_last_error_msg());
                    } elseif ($batchJson == '') {
                        $helper->logRequest('An empty operation was detected');
                    } else {
                        $batchResponse = $squalomailApi->batchOperation->add($batchJson);
                        $helper->logRequest($batchJson, $batchResponse['id']);
                        //save batch id to db
                        $batch = $this->getSyncBatchesModel();
                        $batch->setStoreId($squalomailStoreId)
                            ->setBatchId($batchResponse['id'])
                            ->setStatus($batchResponse['status']);
                        $batch->save();
                    }
                }
            } catch (Ebizmarts_SqualoMail_Helper_Data_ApiKeyException $e) {
                $helper->logError($e->getMessage());
            } catch (SqualoMail_Error $e) {
                $helper->logError($e->getFriendlyMessage());
            } catch (Exception $e) {
                $helper->logError($e->getMessage());
                $helper->logError("Json encode fails");
                $helper->logError($batchArray);
            }
        } catch (SqualoMail_Error $e) {
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * @param       $itemId
     * @param       $itemType
     * @param       $squalomailStoreId
     * @param null  $syncDelta
     * @param null  $syncError
     * @param int   $syncModified
     * @param null  $syncDeleted
     * @param null  $token
     * @param null  $syncedFlag
     * @param bool  $saveOnlyIfExists
     */
    protected function saveSyncData(
        $itemId,
        $itemType,
        $squalomailStoreId,
        $syncDelta = null,
        $syncError = null,
        $syncModified = 0,
        $syncDeleted = null,
        $token = null,
        $syncedFlag = null,
        $saveOnlyIfExists = false
    ) {
        $helper = $this->getHelper();

        if ($itemType == Ebizmarts_SqualoMail_Model_Config::IS_SUBSCRIBER) {
            $helper->updateSubscriberSyndData($itemId, $syncDelta, $syncError, 0, null);
        } else {
            $ecommerceSyncData = $this->getSqualomailEcommerceSyncDataModel();
            $ecommerceSyncData->saveEcommerceSyncData(
                $itemId,
                $itemType,
                $squalomailStoreId,
                $syncDelta,
                $syncError,
                $syncModified,
                $syncDeleted,
                $token,
                $syncedFlag,
                $saveOnlyIfExists,
                null,
                false
            );
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
     * @param $storeId
     * @param $syncedDateArray
     * @return mixed
     */
    protected function addSyncValueToArray($storeId, $syncedDateArray)
    {
        $helper = $this->getHelper();
        $ecomEnabled = $helper->isEcomSyncDataEnabled($storeId);

        if ($ecomEnabled) {
            $squalomailStoreId = $helper->getSQMStoreId($storeId);
            $syncedDate = $helper->getSQMIsSyncing($squalomailStoreId, $storeId);

            // Check if $syncedDate is in date format to support previous versions.
            if (isset($syncedDateArray[$squalomailStoreId]) && $syncedDateArray[$squalomailStoreId]) {
                if ($helper->validateDate($syncedDate)) {
                    if ($syncedDate > $syncedDateArray[$squalomailStoreId]) {
                        $syncedDateArray[$squalomailStoreId] = array($storeId => $syncedDate);
                    }
                } elseif ((int)$syncedDate === 1) {
                    $syncedDateArray[$squalomailStoreId] = array($storeId => false);
                }
            } else {
                if ($helper->validateDate($syncedDate)) {
                    $syncedDateArray[$squalomailStoreId] = array($storeId => $syncedDate);
                } else {
                    if ((int)$syncedDate === 1 || $syncedDate === null) {
                        $syncedDateArray[$squalomailStoreId] = array($storeId => false);
                    } elseif (!isset($syncedDateArray[$squalomailStoreId])) {
                        $syncedDateArray[$squalomailStoreId] = array($storeId => true);
                    }
                }
            }
        }

        return $syncedDateArray;
    }

    /**
     * @param $syncedDateArray
     * @throws Mage_Core_Exception
     */
    public function handleSyncingValue($syncedDateArray)
    {
        $helper = $this->getHelper();
        foreach ($syncedDateArray as $squalomailStoreId => $val) {
            $magentoStoreId = key($val);
            $date = $val[$magentoStoreId];
            $ecomEnabled = $helper->isEcomSyncDataEnabled($magentoStoreId);
            if ($ecomEnabled && $date) {
                try {
                    $api = $helper->getApi($magentoStoreId);
                    $isSyncingDate = $helper->getDateSyncFinishBySqualoMailStoreId($squalomailStoreId);
                    if (!$isSyncingDate && $squalomailStoreId) {
                        $this->getApiStores()->editIsSyncing($api, false, $squalomailStoreId);
                        $config = array(
                            array(
                                Ebizmarts_SqualoMail_Model_Config::ECOMMERCE_SYNC_DATE . "_$squalomailStoreId",
                                $date
                            )
                        );
                        $helper->saveSqualomailConfig($config, 0, 'default');
                    }
                } catch (Ebizmarts_SqualoMail_Helper_Data_ApiKeyException $e) {
                    $helper->logError($e->getMessage());
                } catch (SqualoMail_Error $e) {
                    $helper->logError($e->getFriendlyMessage());
                } catch (Exception $e) {
                    $helper->logError($e->getMessage());
                }
            }
        }
    }

    /**
     * @param $squalomailStoreId
     * @param $id
     * @param $type
     */
    protected function setItemAsModified($squalomailStoreId, $id, $type)
    {
        $isMarkedAsDeleted = null;

        if ($type == Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT) {
            $dataProduct = $this->getDataProduct($squalomailStoreId, $id, $type);
            $isMarkedAsDeleted = $dataProduct->getSqualomailSyncDeleted();
            $isProductDisabledInMagento = Ebizmarts_SqualoMail_Model_Api_Products::PRODUCT_DISABLED_IN_MAGENTO;

            if (!$isMarkedAsDeleted || $dataProduct->getSqualomailSyncError() != $isProductDisabledInMagento) {
                $this->saveSyncData(
                    $id,
                    $type,
                    $squalomailStoreId,
                    null,
                    null,
                    1,
                    0,
                    null,
                    1,
                    true
                );
            } else {
                $this->saveSyncData(
                    $id,
                    $type,
                    $squalomailStoreId,
                    null,
                    $isProductDisabledInMagento,
                    0,
                    1,
                    null,
                    0,
                    true
                );
            }
        } else {
            $this->saveSyncData(
                $id,
                $type,
                $squalomailStoreId,
                null,
                null,
                1,
                0,
                null,
                1,
                true
            );
        }
    }

    /**
     * @param $syncingFlag
     * @param $itemAmount
     * @param $helper
     * @return bool
     */
    protected function shouldFlagAsSyncing($syncingFlag, $itemAmount, $helper)
    {
        return $syncingFlag === null && $itemAmount !== 0 || $helper->validateDate($syncingFlag);
    }

    /**
     * @param $syncingFlag
     * @param $itemAmount
     * @return bool
     */
    protected function shouldFlagAsSynced($syncingFlag, $itemAmount)
    {
        return ($syncingFlag === '1' || $syncingFlag === null) && $itemAmount === 0;
    }

    /**
     * @param $squalomailStoreId
     * @param $id
     * @param $type
     * @return Varien_Object
     */
    protected function getDataProduct($squalomailStoreId, $id, $type)
    {
        return $this->getSqualomailEcommerceSyncDataModel()->getEcommerceSyncDataItem($id, $type, $squalomailStoreId);
    }

    /**
     * @param $batchId
     * @param $storeId
     * @throws Mage_Core_Exception
     */
    protected function _showResumeEcommerce($batchId, $storeId)
    {
        $helper = $this->getHelper();
        $countersSentPerBatch = $helper->getCountersSentPerBatch();

        if (!empty($countersSentPerBatch) || $countersSentPerBatch != null) {
            $helper->logBatchStatus("Sent batch $batchId for Magento store $storeId");
            $helper->logBatchQuantity($helper->getCountersSentPerBatch());
        } else {
            $helper->logBatchStatus("Nothing to sync for store $storeId");
        }
    }

    /**
     * @param $batchId
     * @param $storeId
     * @throws Mage_Core_Exception
     */
    protected function _showResumeSubscriber($batchId, $storeId)
    {
        $helper = $this->getHelper();
        $countersSubscribers = $helper->getCountersSubscribers();

        if (!empty($countersSubscribers) || $helper->getCountersSubscribers() != null) {
            $helper->logBatchStatus("Sent batch $batchId for Magento store $storeId");
            $helper->logBatchQuantity($helper->getCountersSubscribers());
        } else {
            $helper->logBatchStatus("Nothing to sync for store $storeId");
        }
    }

    /**
     * @param $storeId
     * @throws Mage_Core_Exception
     */
    protected function _showResumeDataSentToSqualomail($storeId)
    {
        $helper = $this->getHelper();
        $countersDataSentToSqualomail = $helper->getCountersDataSentToSqualomail();

        if (!empty($countersDataSentToSqualomail) || $helper->getCountersDataSentToSqualomail() != null) {
            $helper->logBatchStatus("Processed data sent to Squalomail for store $storeId");
            $counter = $helper->getCountersDataSentToSqualomail();
            $helper->logBatchQuantity($counter);
            if ($this->isSetAnyCounterSubscriberOrEcommerceNotSent($counter)) {
                if ($helper->isErrorLogEnabled()) {
                    $helper->logBatchStatus(
                        'Please check Squalomail Errors grid or SqualoMail_Errors.log for more details.'
                    );
                } else {
                    $helper->logBatchStatus(
                        'Please check Squalomail Errors grid and enable SqualoMail_Errors.log for more details.'
                    );
                }
            }
        } else {
            $helper->logBatchStatus("Nothing was processed for store $storeId");
        }
    }

    /**
     * @param $counter
     * @return bool
     */
    protected function isSetAnyCounterSubscriberOrEcommerceNotSent($counter)
    {
        return isset($counter['SUB']['NOT SENT'])
            || isset($counter['CUS']['NOT SENT'])
            || isset($counter['ORD']['NOT SENT'])
            || isset($counter['PRO']['NOT SENT'])
            || isset($counter['QUO']['NOT SENT']);
    }

    /**
     * @param Varien_Object $syncDataItem
     * @return bool
     */
    protected function isFirstArrival(Varien_Object $syncDataItem)
    {
        return (int)$syncDataItem->getSqualomailSyncedFlag() !== 1;
    }

    /**
     * @param $type
     * @param Varien_Object $syncDataItem
     * @return int
     */
    protected function enableMergeFieldsSending($type, Varien_Object $syncDataItem)
    {
        $syncModified = 0;

        if ($type == Ebizmarts_SqualoMail_Model_Config::IS_CUSTOMER && $this->isFirstArrival($syncDataItem)) {
            $syncModified = 1;
        }

        return $syncModified;
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_File
     */
    protected function getSqualomailFileHelper()
    {
        return Mage::helper('squalomail/file');
    }
}