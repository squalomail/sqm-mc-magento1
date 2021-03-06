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
class Ebizmarts_SqualoMail_Model_Api_Orders extends Ebizmarts_SqualoMail_Model_Api_ItemSynchronizer
{
    const BATCH_LIMIT = 50;
    const BATCH_LIMIT_ONLY_ORDERS = 500;
    const PAID = 'paid';
    const PARTIALLY_PAID = 'partially_paid';
    const SHIPPED = 'shipped';
    const PARTIALLY_SHIPPED = 'partially_shipped';
    const PENDING = 'pending';
    const REFUNDED = 'refunded';
    const PARTIALLY_REFUNDED = 'partially_refunded';
    const CANCELED = 'canceled';
    protected $_firstDate;
    protected $_counter;
    protected $_batchId;
    protected $_api = null;
    protected $_listsCampaignIds = array();

    /**
     * @var $_ecommerceOrdersCollection Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata_Orders_Collection
     */
    protected $_ecommerceOrdersCollection;

    /**
     * Set the request for orders to be created on SqualoMail
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    public function createBatchJson()
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $magentoStoreId = $this->getMagentoStoreId();

        $this->_ecommerceOrdersCollection = $this->createEcommerceOrdersCollection();
        $this->_ecommerceOrdersCollection->setSqualomailStoreId($squalomailStoreId);
        $this->_ecommerceOrdersCollection->setStoreId($magentoStoreId);

        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();
        $oldStore = $helper->getCurrentStoreId();
        $helper->setCurrentStore($magentoStoreId);

        $batchArray = array();
        $this->_firstDate = $helper->getEcommerceFirstDate($magentoStoreId);
        $this->_counter = 0;
        $this->_batchId = 'storeid-'
            . $magentoStoreId . '_'
            . Ebizmarts_SqualoMail_Model_Config::IS_ORDER
            . '_' . $dateHelper->getDateMicrotime();
        $resendTurn = $helper->getResendTurn($magentoStoreId);

        if (!$resendTurn) {
            // get all the orders modified
            $batchArray = array_merge($batchArray, $this->_getModifiedOrders());
        }

        // get new orders
        $batchArray = array_merge($batchArray, $this->_getNewOrders());
        $helper->setCurrentStore($oldStore);

        return $batchArray;
    }

    /**
     * @return array
     */
    protected function _getModifiedOrders()
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $magentoStoreId = $this->getMagentoStoreId();

        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();
        $batchArray = array();
        $modifiedOrders = $this->getResourceModelOrderCollection();
        // select orders for the current Magento store id
        $modifiedOrders->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        //join with squalomail_ecommerce_sync_data table to filter by sync data.
        $this->_ecommerceOrdersCollection->joinLeftEcommerceSyncData($modifiedOrders);
        // be sure that the order are already in squalomail and not deleted
        $this->_ecommerceOrdersCollection->addWhere(
            $modifiedOrders,
            "m4m.squalomail_sync_modified = 1",
            $this->getBatchLimitFromConfig()
        );

        foreach ($modifiedOrders as $item) {
                $orderId = $item->getEntityId();

            try {
                $order = $this->_getOrderById($orderId);
                $incrementId = $order->getIncrementId();
                //create missing products first
                $batchArray = $this->addProductNotSentData($order, $batchArray);
                $orderJson = $this->GeneratePOSTPayload($order);

                if ($orderJson !== false) {
                    if (!empty($orderJson)) {
                        $helper->modifyCounterSentPerBatch(Ebizmarts_SqualoMail_Helper_Data::ORD_MOD);

                        $batchArray[$this->_counter]['method'] = "PATCH";
                        $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $squalomailStoreId
                            . '/orders/' . $incrementId;
                        $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                        $batchArray[$this->_counter]['body'] = $orderJson;
                        //update order delta
                        $this->addSyncData($orderId);
                        $this->_counter++;
                    } else {
                        $error = $helper->__('Something went wrong when retrieving product information.');

                        $this->addSyncDataError(
                            $orderId,
                            $error,
                            null,
                            false,
                            $dateHelper->formatDate(null, "Y-m-d H:i:s")
                        );
                        continue;
                    }
                } else {
                    $jsonErrorMsg = json_last_error_msg();
                    $this->logSyncError(
                        $jsonErrorMsg,
                        Ebizmarts_SqualoMail_Model_Config::IS_ORDER,
                        $magentoStoreId,
                        'magento_side_error',
                        'Json Encode Failure',
                        0,
                        $orderId,
                        0

                    );

                    $this->addSyncDataError(
                        $orderId,
                        $jsonErrorMsg,
                        null,
                        false,
                        $dateHelper->formatDate(null, "Y-m-d H:i:s")
                    );
                }
            } catch (Exception $e) {
                $this->logSyncError(
                    $e->getMessage(),
                    Ebizmarts_SqualoMail_Model_Config::IS_ORDER,
                    $magentoStoreId,
                    'magento_side_error',
                    'Json Encode Failure',
                    0,
                    $orderId,
                    0
                );
            }
        }

        return $batchArray;
    }

    /**
     * @return array
     * @throws Mage_Core_Exception
     */
    protected function _getNewOrders()
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $magentoStoreId = $this->getMagentoStoreId();

        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();

        $batchArray = array();
        $newOrders = $this->getResourceModelOrderCollection();
        // select carts for the current Magento store id
        $newOrders->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        $helper->addResendFilter($newOrders, $magentoStoreId, Ebizmarts_SqualoMail_Model_Config::IS_ORDER);
        // filter by first date if exists.
        if ($this->_firstDate) {
            $newOrders->addFieldToFilter('created_at', array('gt' => $this->_firstDate));
        }

        $this->_ecommerceOrdersCollection->joinLeftEcommerceSyncData($newOrders);
        $this->_ecommerceOrdersCollection->addWhere(
            $newOrders,
            "m4m.squalomail_sync_delta IS NULL",
            $this->getBatchLimitFromConfig()
        );

        foreach ($newOrders as $item) {
                $orderId = $item->getEntityId();
            try {
                $order = $this->_getOrderById($orderId);
                //create missing products first
                $batchArray = $this->addProductNotSentData($order, $batchArray);

                $orderJson = $this->GeneratePOSTPayload($order);

                if ($orderJson !== false) {
                    if (!empty($orderJson)) {
                        $helper->modifyCounterSentPerBatch(Ebizmarts_SqualoMail_Helper_Data::ORD_NEW);

                        $batchArray[$this->_counter]['method'] = "POST";
                        $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $squalomailStoreId . '/orders';
                        $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                        $batchArray[$this->_counter]['body'] = $orderJson;
                        //update order delta
                        $this->addSyncData($orderId);
                        $this->_counter++;
                    } else {
                        $error = $helper->__('Something went wrong when retrieving product information.');

                        $this->addSyncDataError(
                            $orderId,
                            $error,
                            null,
                            false,
                            $dateHelper->formatDate(null, "Y-m-d H:i:s")
                        );
                        continue;
                    }
                } else {
                    $jsonErrorMsg = json_last_error_msg();
                    $this->logSyncError(
                        "Order " . $order->getEntityId() . " json encode failed (".$jsonErrorMsg.")",
                        Ebizmarts_SqualoMail_Model_Config::IS_ORDER,
                        $magentoStoreId,
                        'magento_side_error',
                        'Json Encode Failure',
                        0,
                        $orderId,
                        0
                    );

                    $this->addSyncDataError(
                        $orderId,
                        $jsonErrorMsg,
                        null,
                        false,
                        $dateHelper->formatDate(null, "Y-m-d H:i:s")
                    );
                }
            } catch (Exception $e) {
                $this->logSyncError(
                    $e->getMessage(),
                    Ebizmarts_SqualoMail_Model_Config::IS_ORDER,
                    $magentoStoreId,
                    'magento_side_error',
                    'Json Encode Failure',
                    0,
                    $orderId,
                    0
                );
            }
        }

        return $batchArray;
    }

    /**
     * @param $id
     * @return Mage_Core_Model_Abstract
     */
    protected function _getOrderById($id)
    {
        return Mage::getModel('sales/order')->load($id);
    }

    /**
     * Set all the data for each order to be sent
     *
     * @param $order
     * @return false|string
     * @throws Mage_Core_Model_Store_Exception
     */
    public function GeneratePOSTPayload($order)
    {
        $magentoStoreId = $this->getMagentoStoreId();

        $data = $this->_getPayloadData($order);
        $lines = $this->_getPayloadDataLines($order);
        $data['lines'] = $lines['lines'];

        if (!$lines['itemsCount']) {
            unset($data['lines']);
            return "";
        }

        //customer data
        $data["customer"]["id"] = hash('md5', strtolower($order->getCustomerEmail()));
        $data["customer"]["email_address"] = $order->getCustomerEmail();
        $data["customer"]["opt_in_status"] = false;

        $subscriber = $this->getSubscriberModel();

        if ($subscriber->getOptIn($magentoStoreId)) {
            $isSubscribed = $subscriber->loadByEmail($order->getCustomerEmail())->getSubscriberId();

            if (!$isSubscribed) {
                $subscriber->subscribe($order->getCustomerEmail());
            }
        }

        $subscriber = null;

        $store = $this->getStoreModelFromMagentoStoreId($magentoStoreId);
        $data['order_url'] = $store->getUrl(
            'sales/order/view/',
            array(
                'order_id' => $order->getId(),
                '_nosid' => true,
                '_secure' => true
            )
        );

        if ($order->getCustomerFirstname()) {
            $data["customer"]["first_name"] = $order->getCustomerFirstname();
        }

        if ($order->getCustomerLastname()) {
            $data["customer"]["last_name"] = $order->getCustomerLastname();
        }

        $billingAddress = $order->getBillingAddress();

        if ($billingAddress) {
            $street = $billingAddress->getStreet();
            $this->_getPayloadBilling($data, $billingAddress, $street);
        }

        $shippingAddress = $order->getShippingAddress();

        if ($shippingAddress) {
            $this->_getPayloadShipping($data, $shippingAddress);
        }

        $jsonData = "";
        //encode to JSON
        $jsonData = json_encode($data);

        return $jsonData;
    }

    /**
     * @param $order
     * @return array
     * @throws Exception
     */
    protected function _getPayloadData($order)
    {
        $data = array();
        $data['id'] = $order->getIncrementId();
        $dataPromo = $this->getPromoData($order);
        $squalomailCampaignId = $order->getSqualomailCampaignId();

        if ($this->shouldSendCampaignId($squalomailCampaignId, $order->getEntityId())) {
            $data['campaign_id'] = $squalomailCampaignId;
        }

        if ($order->getSqualomailLandingPage()) {
            $data['landing_site'] = $order->getSqualomailLandingPage();
        }

        $data['currency_code'] = $order->getOrderCurrencyCode();
        $data['order_total'] = $order->getGrandTotal();
        $data['tax_total'] = $this->returnZeroIfNull($order->getTaxAmount());
        $data['discount_total'] = abs($order->getDiscountAmount());
        $data['shipping_total'] = $this->returnZeroIfNull($order->getShippingAmount());

        if ($dataPromo !== null) {
            $data['promos'] = $dataPromo;
        }

        $statusArray = $this->_getSqualoMailStatus($order);

        if (isset($statusArray['financial_status'])) {
            $data['financial_status'] = $statusArray['financial_status'];
        }

        if (isset($statusArray['fulfillment_status'])) {
            $data['fulfillment_status'] = $statusArray['fulfillment_status'];
        }

        $data['processed_at_foreign'] = $order->getCreatedAt();
        $data['updated_at_foreign'] = $order->getUpdatedAt();

        if ($this->isOrderCanceled($order)) {
            $orderCancelDate = $this->_processCanceledOrder($order);

            if ($orderCancelDate) {
                $data['cancelled_at_foreign'] = $orderCancelDate;
            }
        }

        return $data;
    }

    /**
     * @param $order
     * @return array
     */
    protected function _getPayloadDataLines($order)
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $magentoStoreId = $this->getMagentoStoreId();

        $apiProduct = $this->getApiProduct();
        $apiProduct->setSqualomailStoreId($squalomailStoreId);
        $apiProduct->setMagentoStoreId($magentoStoreId);

        $lines = array();
        $items = $order->getAllVisibleItems();
        $itemCount = 0;

        foreach ($items as $item) {
            $productId = $item->getProductId();
            $isTypeProduct = $this->isTypeProduct();
            $productSyncData = $this->getSqualomailEcommerceSyncDataModel()
                ->getEcommerceSyncDataItem($productId, $isTypeProduct, $squalomailStoreId);

            if ($this->isItemConfigurable($item)) {
                $options = $item->getProductOptions();
                $sku = $options['simple_sku'];
                $variant = $this->_getProductIdBySku($sku);

                if (!$variant) {
                    continue;
                }
            } elseif ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
                || $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED
            ) {
                continue;
            } else {
                $variant = $productId;
            }

            $productSyncError = $productSyncData->getSqualomailSyncError();
            $isProductEnabled = $apiProduct->isProductEnabled($productId);

            if (!$isProductEnabled || ($productSyncData->getSqualomailSyncDelta() && $productSyncError == '')) {
                $itemCount++;
                $lines[] = array(
                    "id" => (string)$itemCount,
                    "product_id" => $productId,
                    "product_variant_id" => $variant,
                    "quantity" => (int)$item->getQtyOrdered(),
                    "price" => $item->getPrice(),
                    "discount" => abs($item->getDiscountAmount())
                );

                if (!$isProductEnabled) {
                    // update disabled products to remove the product from squalomail after sending the order
                    $apiProduct->updateDisabledProducts($productId);
                }
            }
        }

        return array('lines' => $lines, 'itemsCount' => $itemCount);
    }

    /**
     * @param $data
     * @param $billingAddress
     * @param $street
     */
    protected function _getPayloadBilling($data, $billingAddress, $street)
    {
        $address = array();

        $this->_getPayloadBillingStreet($data, $address, $street);

        if ($billingAddress->getCity()) {
            $address["city"] = $data['billing_address']["city"] = $billingAddress->getCity();
        }

        if ($billingAddress->getRegion()) {
            $address["province"] = $data['billing_address']["province"] = $billingAddress->getRegion();
        }

        if ($billingAddress->getRegionCode()) {
            $address["province_code"] =
            $data['billing_address']["province_code"] =
                $billingAddress->getRegionCode();
        }

        if ($billingAddress->getPostcode()) {
            $address["postal_code"] = $data['billing_address']["postal_code"] = $billingAddress->getPostcode();
        }

        if ($billingAddress->getCountry()) {
            $countryName = $this->getCountryModelNameFromBillingAddress($billingAddress);
            $address["country"] = $data['billing_address']["country"] = $countryName;
            $address["country_code"] = $data['billing_address']["country_code"] = $billingAddress->getCountry();
        }

        if (!empty($address)) {
            $data["customer"]["address"] = $address;
        }

        if ($billingAddress->getName()) {
            $data['billing_address']['name'] = $billingAddress->getName();
        }

        //company
        if ($billingAddress->getCompany()) {
            $data["customer"]["company"] = $data["billing_address"]["company"] = $billingAddress->getCompany();
        }
    }

    /**
     * @param $data
     * @param $address
     * @param $street
     */
    protected function _getPayloadBillingStreet($data, $address, $street)
    {
        if ($street[0]) {
            $address["address1"] = $data['billing_address']["address1"] = $street[0];
        }

        if (count($street) > 1) {
            $address["address2"] = $data['billing_address']["address2"] = $street[1];
        }
    }

    /**
     * @param $data
     * @param $shippingAddress
     */
    protected function _getPayloadShipping($data, $shippingAddress)
    {
        $street = $shippingAddress->getStreet();

        if ($shippingAddress->getName()) {
            $data['shipping_address']['name'] = $shippingAddress->getName();
        }

        if (isset($street[0]) && $street[0]) {
            $data['shipping_address']['address1'] = $street[0];
        }

        if (isset($street[1]) && $street[1]) {
            $data['shipping_address']['address2'] = $street[1];
        }

        if ($shippingAddress->getCity()) {
            $data['shipping_address']['city'] = $shippingAddress->getCity();
        }

        if ($shippingAddress->getRegion()) {
            $data['shipping_address']['province'] = $shippingAddress->getRegion();
        }

        if ($shippingAddress->getRegionCode()) {
            $data['shipping_address']['province_code'] = $shippingAddress->getRegionCode();
        }

        if ($shippingAddress->getPostcode()) {
            $data['shipping_address']['postal_code'] = $shippingAddress->getPostcode();
        }

        if ($shippingAddress->getCountry()) {
            $data['shipping_address']['country'] = $this->getCountryModelNameFromShippingAddress($shippingAddress);
            $data['shipping_address']['country_code'] = $shippingAddress->getCountry();
        }

        if ($shippingAddress->getCompamy()) {
            $data["shipping_address"]["company"] = $shippingAddress->getCompany();
        }
    }

    /**
     * @return mixed
     */
    protected function _processCanceledOrder($order)
    {
        $orderCancelDate = null;
        $commentCollection = $order->getStatusHistoryCollection();

        foreach ($commentCollection as $comment) {
            if ($this->isTheOrderCommentCanceled($comment)) {
                $orderCancelDate = $comment->getCreatedAt();
            }
        }

        return $orderCancelDate;
    }

    /**
     * @return mixed
     */
    protected function getBatchLimitFromConfig()
    {
        $helper = $this->getHelper();
        return $helper->getOrderAmountLimit();
    }

    /**
     * @param $value
     * @return int
     */
    protected function returnZeroIfNull($value)
    {
        $returnValue = $value;
        if ($value === null) {
            $returnValue = 0;
        }

        return $returnValue;
    }

    /**
     * @param $order
     * @return array
     */
    protected function _getSqualoMailStatus($order)
    {
        $totalItemsOrdered = $order->getData('total_qty_ordered');
        $squaloMailStatus = array();

        $financialFulfillment = $this->_getFinancialFulfillmentStatus(
            $order->getAllVisibleItems(), $totalItemsOrdered
        );

        if (!$financialFulfillment['financialStatus'] && $this->isOrderCanceled($order)) {
            $financialFulfillment['financialStatus'] = self::CANCELED;
        }

        if (!$financialFulfillment['financialStatus']) {
            $financialFulfillment['financialStatus'] = self::PENDING;
        }

        if ($financialFulfillment['financialStatus']) {
            $squaloMailStatus['financial_status'] = $financialFulfillment['financialStatus'];
        }

        if ($financialFulfillment['fulfillmentStatus']) {
            $squaloMailStatus['fulfillment_status'] = $financialFulfillment['fulfillmentStatus'];
        }

        return $squaloMailStatus;
    }

    /**
     * @param $orderItems
     * @param $totalItemsOrdered
     * @return array
     */
    protected function _getFinancialFulfillmentStatus($orderItems, $totalItemsOrdered)
    {
        $items = array(
            'shippedItemAmount' => 0,
            'invoicedItemAmount' => 0,
            'refundedItemAmount' => 0
        );
        $squalomailStatus = array(
            'financialStatus' => null,
            'fulfillmentStatus' => null
        );

        foreach ($orderItems as $item) {
            $items['invoicedItemAmount'] += $item->getQtyShipped();
            $items['invoicedItemAmount'] += $item->getQtyInvoiced();
            $items['refundedItemAmount'] += $item->getQtyRefunded();
        }

        if ($items['shippedItemAmount'] > 0) {
            if ($totalItemsOrdered > $items['shippedItemAmount']) {
                $squalomailStatus['fulfillmentStatus'] = self::PARTIALLY_SHIPPED;
            } else {
                $squalomailStatus['fulfillmentStatus'] = self::SHIPPED;
            }
        }

        if ($items['refundedItemAmount'] > 0) {
            if ($squalomailStatus > $items['refundedItemAmount']) {
                $mailchimStatus['financialStatus'] = self::PARTIALLY_REFUNDED;
            } else {
                $squalomailStatus['financialStatus'] = self::REFUNDED;
            }
        }

        if ($items['invoicedItemAmount'] > 0) {
            if ($items['refundedItemAmount'] == 0
                || $items['refundedItemAmount'] != $items['invoicedItemAmount']
            ) {
                if ($totalItemsOrdered > $items['invoicedItemAmount']) {
                    $squalomailStatus['financialStatus'] = self::PARTIALLY_PAID;
                } else {
                    $squalomailStatus['financialStatus'] = self::PAID;
                }
            }
        }

        return $squalomailStatus;
    }

    /**
     * @param $orderId
     * @param $magentoStoreId
     */
    public function update($orderId, $magentoStoreId)
    {
        $helper = $this->getHelper();

        if ($helper->isEcomSyncDataEnabled($magentoStoreId)) {
            $this->markSyncDataAsModified($orderId);
        }
    }

    /**
     * Replace all orders with old id with the increment id on SqualoMail.
     *
     * @param  $initialTime
     * @param  $squalomailStoreId
     * @param  $magentoStoreId
     * @return array
     */
    public function replaceAllOrdersBatch($initialTime, $squalomailStoreId, $magentoStoreId)
    {
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();
        $this->_counter = 0;
        $this->_batchId = 'storeid-'
            . $magentoStoreId . '_'
            . Ebizmarts_SqualoMail_Model_Config::IS_ORDER . '_'
            . $dateHelper->getDateMicrotime();
        $lastId = $helper->getConfigValueForScope(
            Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_LAST_ORDER_ID,
            $magentoStoreId,
            'stores'
        );
        $batchArray = array();
        $config = array();
        $orderCollection = $this->getResourceModelOrderCollection();
        // select carts for the current Magento store id
        $orderCollection->addFieldToFilter('store_id', array('eq' => $magentoStoreId));

        if ($lastId) {
            $orderCollection->addFieldToFilter('entity_id', array('gt' => $lastId));
        }

        if(empty($this->_ecommerceOrdersCollection)){
            $this->_ecommerceOrdersCollection = $this->createEcommerceOrdersCollection();
            $this->_ecommerceOrdersCollection->setSqualomailStoreId($squalomailStoreId);
            $this->_ecommerceOrdersCollection->setStoreId($magentoStoreId);
        }

        $this->_ecommerceOrdersCollection->joinLeftEcommerceSyncData($orderCollection);

        // be sure that the orders are not in squalomail
        $this->_ecommerceOrdersCollection->addWhere(
            $orderCollection,
            "m4m.squalomail_sync_delta IS NOT NULL AND m4m.squalomail_sync_error = ''",
            self::BATCH_LIMIT_ONLY_ORDERS
        );

        foreach ($orderCollection as $order) {
            //Delete order
            $orderId = $order->getEntityId();
            $config = array(array(Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_LAST_ORDER_ID, $orderId));

            if (!$dateHelper->timePassed($initialTime)) {
                $batchArray[$this->_counter]['method'] = "DELETE";
                $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $squalomailStoreId . '/orders/' . $orderId;
                $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                $batchArray[$this->_counter]['body'] = '';
                $this->_counter += 1;

                //Create order
                $orderJson = $this->GeneratePOSTPayload($order);

                if ($orderJson !== false) {
                    if (!empty($orderJson)) {
                        $batchArray[$this->_counter]['method'] = "POST";
                        $batchArray[$this->_counter]['path'] = '/ecommerce/stores/' . $squalomailStoreId . '/orders';
                        $batchArray[$this->_counter]['operation_id'] = $this->_batchId . '_' . $orderId;
                        $batchArray[$this->_counter]['body'] = $orderJson;
                        $this->_counter += 1;
                    } else {
                        $error = $helper->__(
                            'Something went wrong when retrieving product information during migration from 1.1.6.'
                        );
                        $this->addSyncDataError(
                            $orderId,
                            $error,
                            null,
                            false,
                            $dateHelper->formatDate(null, "Y-m-d H:i:s")
                        );
                        continue;
                    }
                } else {
                    $error = $helper->__("Json error during migration from 1.1.6");
                    $this->addSyncDataError(
                        $orderId,
                        $error,
                        null,
                        false,
                        $dateHelper->formatDate(null, "Y-m-d H:i:s")
                    );
                    continue;
                }
            } else {
                if (empty($batchArray)) {
                    $batchArray[] = $helper->__('Time passed.');
                }

                $helper->saveSqualomailConfig($config, $magentoStoreId, 'stores');
                break;
            }
        }

        $helper->saveSqualomailConfig($config, $magentoStoreId, 'stores');

        return $batchArray;
    }

    /**
     * @param $order
     * @param $batchArray
     * @return mixed
     */
    public function addProductNotSentData($order, $batchArray)
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $magentoStoreId = $this->getMagentoStoreId();

        $helper = $this->getHelper();
        $apiProduct = $this->getApiProduct();
        $apiProduct->setSqualomailStoreId($squalomailStoreId);
        $apiProduct->setMagentoStoreId($magentoStoreId);
        $productData = $apiProduct->sendModifiedProduct($order);

        $productDataArray = $helper->addEntriesToArray($batchArray, $productData, $this->_counter);
        $batchArray = $productDataArray[0];
        $this->_counter = $productDataArray[1];

        return $batchArray;
    }

    /**
     * @param $newOrders
     */
    public function joinSqualomailSyncDataWithoutWhere($newOrders)
    {
        $this->_ecommerceOrdersCollection->joinLeftEcommerceSyncData($newOrders);
    }

    /**
     * @param $order
     * @return array
     */

    public function getPromoData($order)
    {
        $promo = null;

        $couponCode = $order->getCouponCode();

        if ($couponCode !== null) {
            $code = $this->makeSalesRuleCoupon()->load($couponCode, 'code');

            if ($code->getCouponId() !== null) {
                $rule = $this->makeSalesRule()->load($code->getRuleId());

                if ($rule->getRuleId() !== null) {
                    $amountDiscounted = $order->getBaseDiscountAmount();
                    $type = $rule->getSimpleAction();

                    if ($type == 'by_percent') {
                        $type = 'percentage';
                    } else {
                        $type = 'fixed';
                    }

                    $promo = array(array(
                        'code' => $couponCode,
                        'amount_discounted' => abs($amountDiscounted),
                        'type' => $type
                    ));
                }
            }
        }

        return $promo;
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function makeSalesRuleCoupon()
    {
        return Mage::getModel('salesrule/coupon');
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function makeSalesRule()
    {
        return Mage::getModel('salesrule/rule');
    }

    /**
     * @param $orderId
     * @param $squalomailStoreId
     * @return array
     */
    public function getSyncedOrder($orderId, $squalomailStoreId)
    {
        $result = $this->getSqualomailEcommerceSyncDataModel()->getEcommerceSyncDataItem(
            $orderId,
            Ebizmarts_SqualoMail_Model_Config::IS_ORDER,
            $squalomailStoreId
        );

        $squalomailSyncedFlag = $result->getSqualomailSyncedFlag();
        $squalomailOrderId = $result->getId();

        return array('synced_status' => $squalomailSyncedFlag, 'order_id' => $squalomailOrderId);
    }

    /**
     * @param $order
     * @return bool
     */
    protected function isOrderCanceled($order)
    {
        return $order->getState() == Mage_Sales_Model_Order::STATE_CANCELED;
    }

    /**
     * @param $comment
     * @return bool
     */
    protected function isTheOrderCommentCanceled($comment)
    {
        return $comment->getStatus() === Mage_Sales_Model_Order::STATE_CANCELED;
    }

    /**
     * @param $item
     * @return bool
     */
    protected function isItemConfigurable($item)
    {
        return $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function getModelProduct()
    {
        return Mage::getModel('catalog/product');
    }

    /**
     * @param $sku
     * @return string
     */
    protected function _getProductIdBySku($sku)
    {
        return $this->getModelProduct()->getIdBySku($sku);
    }

    /**
     * @return string
     */
    protected function isTypeProduct()
    {
        return Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT;
    }

    /**
     * @return false|Ebizmarts_SqualoMail_Model_Api_Customers
     */
    protected function getCustomerModel()
    {
        return Mage::getModel('squalomail/api_customers');
    }

    /**
     * @param $magentoStoreId
     * @return Mage_Core_Model_Abstract
     */
    protected function getStoreModelFromMagentoStoreId($magentoStoreId)
    {
        return Mage::getModel('core/store')->load($magentoStoreId);
    }

    /**
     * @param $billingAddress
     * @return mixed
     */
    protected function getCountryModelNameFromBillingAddress($billingAddress)
    {
        return Mage::getModel('directory/country')->loadByCode($billingAddress->getCountry())->getName();
    }

    /**
     * @param $shippingAddress
     * @return mixed
     */
    protected function getCountryModelNameFromShippingAddress($shippingAddress)
    {
        return Mage::getModel('directory/country')->loadByCode($shippingAddress->getCountry())->getName();
    }

    /**
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    protected function getResourceModelOrderCollection()
    {
        return Mage::getResourceModel('sales/order_collection');
    }

    /**
     * @param $squalomailCampaignId
     * @param $orderId
     * @return bool return true if the campaign is from the current list.
     * @throws Mage_Core_Exception
     */
    public function shouldSendCampaignId($squalomailCampaignId, $orderId)
    {
        $magentoStoreId = $this->getMagentoStoreId();
        $isCampaingFromCurrentList = false;

        if ($squalomailCampaignId) {
            $helper = $this->getHelper();
            $listId = $helper->getGeneralList($magentoStoreId);

            try {
                $apiKey = $helper->getApiKey($magentoStoreId);

                if ($apiKey) {
                    if (isset($this->_listsCampaignIds[$apiKey][$listId][$squalomailCampaignId])) {
                        $isCampaingFromCurrentList = $this->_listsCampaignIds[$apiKey][$listId][$squalomailCampaignId];
                    } else {
                        $api = $helper->getApi($magentoStoreId);
                        $campaignData = $api->getCampaign()->get($squalomailCampaignId, 'recipients');

                        if (isset($campaignData['recipients']['list_id'])
                            && $campaignData['recipients']['list_id'] == $listId
                        ) {
                            $this->_listsCampaignIds[$apiKey][$listId][$squalomailCampaignId] =
                            $isCampaingFromCurrentList = true;
                        } else {
                            $this->_listsCampaignIds[$apiKey][$listId][$squalomailCampaignId] =
                            $isCampaingFromCurrentList = false;
                        }
                    }
                }
            } catch (Ebizmarts_SqualoMail_Helper_Data_ApiKeyException $e) {
                $this->_listsCampaignIds[$apiKey][$listId][$squalomailCampaignId] = $isCampaingFromCurrentList = true;
                $this->logSyncError(
                    $e->getMessage(),
                    Ebizmarts_SqualoMail_Model_Config::IS_ORDER,
                    $magentoStoreId,
                    'magento_side_error',
                    'Json Encode Failure',
                    0,
                    $orderId,
                    0
                );
            } catch (SqualoMail_Error $e) {
                $this->_listsCampaignIds[$apiKey][$listId][$squalomailCampaignId] = $isCampaingFromCurrentList = false;
                $this->logSyncError(
                    $e->getFriendlyMessage(),
                    Ebizmarts_SqualoMail_Model_Config::IS_ORDER,
                    $magentoStoreId,
                    'magento_side_error',
                    'Json Encode Failure',
                    0,
                    $orderId,
                    0
                );
            } catch (Exception $e) {
                $this->_listsCampaignIds[$apiKey][$listId][$squalomailCampaignId] = $isCampaingFromCurrentList = true;
                $this->logSyncError(
                    $e->getMessage(),
                    Ebizmarts_SqualoMail_Model_Config::IS_ORDER,
                    $magentoStoreId,
                    'magento_side_error',
                    'Json Encode Failure',
                    0,
                    $orderId,
                    0
                );
            }
        }

        return $isCampaingFromCurrentList;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_Products
     */
    protected function getApiProduct()
    {
        return Mage::getModel('squalomail/api_products');
    }

    /**
     * @return false|Mage_Newsletter_Model_Subscriber
     */
    protected function getSubscriberModel()
    {
        return Mage::getModel('newsletter/subscriber');
    }

    /**
     * @return string
     */
    protected function getItemType()
    {
        return Ebizmarts_SqualoMail_Model_Config::IS_ORDER;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata_Orders_Collection
     */
    public function getEcommerceOrdersCollection()
    {
        return $this->_ecommerceOrdersCollection;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata_Orders_Collection
     */
    public function createEcommerceOrdersCollection()
    {
        /**
         * @var $collection Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata_Orders_Collection
         */
        $collection = Mage::getResourceModel('squalomail/ecommercesyncdata_orders_collection');

        return $collection;
    }
}
