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
class Ebizmarts_SqualoMail_Model_Api_Carts extends Ebizmarts_SqualoMail_Model_Api_ItemSynchronizer
{
    const BATCH_LIMIT = 100;

    protected $_firstDate;
    protected $_counter;
    protected $_batchId;

    protected $_api = null;
    protected $_token = null;

    /**
     * @var $_ecommerceQuotesCollection Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata_Quote_Collection
     */
    protected $_ecommerceQuotesCollection;


    /**
     * @return array
     */
    public function createBatchJson()
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $magentoStoreId = $this->getMagentoStoreId();

        $this->_ecommerceQuotesCollection = $this->createEcommerceQuoteCollection();
        $this->_ecommerceQuotesCollection->setStoreId($magentoStoreId);
        $this->_ecommerceQuotesCollection->setSqualomailStoreId($squalomailStoreId);

        $helper = $this->getHelper();
        $oldStore = $helper->getCurrentStoreId();
        $helper->setCurrentStore($magentoStoreId);

        $allCarts = array();

        if (!$helper->isAbandonedCartEnabled($magentoStoreId)) {
            return $allCarts;
        }

        $dateHelper = $this->getDateHelper();
        $this->_firstDate = $helper->getAbandonedCartFirstDate($magentoStoreId);
        $this->setCounter(0);

        $date = $dateHelper->getDateMicrotime();
        $this->setBatchId(
            'storeid-'
            . $magentoStoreId . '_'
            . Ebizmarts_SqualoMail_Model_Config::IS_QUOTE . '_'
            . $date
        );

        $resendTurn = $helper->getResendTurn($magentoStoreId);

        if (!$resendTurn) {
            // get all the carts converted in orders (must be deleted on squalomail)
            $allCarts = array_merge($allCarts, $this->_getConvertedQuotes());
            // get all the carts modified but not converted in orders
            $allCarts = array_merge($allCarts, $this->_getModifiedQuotes());
        }

        // get new carts
        $allCarts = array_merge($allCarts, $this->_getNewQuotes());
        $helper->setCurrentStore($oldStore);

        return $allCarts;
    }

    /**
     * @return array
     */
    public function _getConvertedQuotes()
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $magentoStoreId = $this->getMagentoStoreId();

        $batchId = $this->getBatchId();
        $allCarts = array();
        $convertedCarts = $this->getQuoteCollection();
        // get only the converted quotes
        $convertedCarts->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        $convertedCarts->addFieldToFilter('is_active', array('eq' => 0));
        //join with squalomail_ecommerce_sync_data table to filter by sync data.
        $this->joinLeftEcommerceSyncData($convertedCarts);
        // be sure that the quotes are already in squalomail and not deleted limit the collection
        $this->getEcommerceQuoteCollection()->addWhere(
            $convertedCarts, "m4m.squalomail_sync_deleted = 0", $this->getBatchLimitFromConfig()
        );

        foreach ($convertedCarts as $cart) {
            $cartId = $cart->getEntityId();
            // we need to delete all the carts associated with this email
            $allCartsForEmail = $this->getAllCartsByEmail($cart->getCustomerEmail());

            foreach ($allCartsForEmail as $cartForEmail) {
                $alreadySentCartId = $cartForEmail->getEntityId();
                $counter = $this->getCounter();

                if ($alreadySentCartId != $cartId) {
                    $allCarts[$counter]['method'] = 'DELETE';
                    $allCarts[$counter]['path'] = '/ecommerce/stores/'
                        . $squalomailStoreId . '/carts/'
                        . $alreadySentCartId;
                    $allCarts[$counter]['operation_id'] = $batchId . '_' . $alreadySentCartId;
                    $allCarts[$counter]['body'] = '';

                    $this->markSyncDataAsDeleted($alreadySentCartId);
                    $this->setCounter($this->getCounter() + 1);
                }
            }

            $allCartsForEmail->clear();
            $counter = $this->getCounter();
            $allCarts[$counter]['method'] = 'DELETE';
            $allCarts[$counter]['path'] = '/ecommerce/stores/' . $squalomailStoreId . '/carts/' . $cartId;
            $allCarts[$counter]['operation_id'] = $batchId . '_' . $cartId;
            $allCarts[$counter]['body'] = '';

            $this->markSyncDataAsDeleted($cartId);
            $this->setCounter($this->getCounter() + 1);
        }

        return $allCarts;
    }

    /**
     * @return array
     */
    public function _getModifiedQuotes()
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $magentoStoreId = $this->getMagentoStoreId();

        $helper = $this->getHelper();
        $batchId = $this->getBatchId();
        $allCarts = array();
        $modifiedCarts = $this->getQuoteCollection();
        // select carts with no orders
        $modifiedCarts->addFieldToFilter('is_active', array('eq' => 1));
        // select carts for the current Magento store id
        $modifiedCarts->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        //join with squalomail_ecommerce_sync_data table to filter by sync data.
        $this->joinLeftEcommerceSyncData($modifiedCarts);
        // be sure that the quotes are already in squalomail and not deleted limited
        $this->getEcommerceQuoteCollection()->addWhere(
            $modifiedCarts, "m4m.squalomail_sync_deleted = 0 AND m4m.squalomail_sync_delta < updated_at",
            $this->getBatchLimitFromConfig()
        );

        foreach ($modifiedCarts as $cart) {
            $cartId = $cart->getEntityId();
            /**
             * @var $customer Mage_Customer_Model_Customer
             */
            $customer = $this->getCustomerModel();
            $customer->setWebsiteId($this->getWebSiteIdFromMagentoStoreId($magentoStoreId));
            $cartCustomerEmail = $cart->getCustomerEmail();
            $customer->loadByEmail($cartCustomerEmail);

            $customerEmail = $customer->getEmail();
            if ($customerEmail != $cartCustomerEmail) {
                $allCartsForEmail = $this->getAllCartsByEmail($cartCustomerEmail);

                foreach ($allCartsForEmail as $cartForEmail) {
                    $alreadySentCartId = $cartForEmail->getEntityId();
                    $counter = $this->getCounter();

                    if ($alreadySentCartId != $cartId) {
                        $allCarts[$counter]['method'] = 'DELETE';
                        $allCarts[$counter]['path'] = '/ecommerce/stores/'
                            . $squalomailStoreId
                            . '/carts/'
                            . $alreadySentCartId;
                        $allCarts[$counter]['operation_id'] = $batchId . '_' . $alreadySentCartId;
                        $allCarts[$counter]['body'] = '';

                        $this->markSyncDataAsDeleted($cartId);
                        $this->setCounter($counter + 1);
                    }
                }

                $allCartsForEmail->clear();
            }

            // avoid carts abandoned as guests when customer email associated to a registered customer.
            if (!$cart->getCustomerId() && $customerEmail == $cartCustomerEmail) {
                $this->addSyncData($cartId);
                continue;
            }

            // send the products that not already sent
            $allCarts = $this->addProductNotSentData($cart, $allCarts);
            $cartJson = $this->makeCart($cart, true);

            if ($cartJson !== false) {
                if (!empty($cartJson)) {
                    $helper->modifyCounterSentPerBatch(Ebizmarts_SqualoMail_Helper_Data::QUO_MOD);

                    $counter = $this->getCounter();
                    $allCarts[$counter]['method'] = 'PATCH';
                    $allCarts[$counter]['path'] = '/ecommerce/stores/' . $squalomailStoreId . '/carts/' . $cartId;
                    $allCarts[$counter]['operation_id'] = $batchId . '_' . $cartId;
                    $allCarts[$counter]['body'] = $cartJson;
                    $this->setCounter($this->getCounter() + 1);

                    $this->addSyncDataToken($cartId, $this->getToken());
                } else {
                    $error = $helper->__('There is not supported products in this cart.');
                    $this->addSyncDataError($cartId, $error);
                }
            } else {
                $jsonErrorMessage = json_last_error_msg();
                $this->addSyncDataError($cartId, $jsonErrorMessage, $this->getToken());

                //json encode failed
                $this->logSyncError(
                    $jsonErrorMessage,
                    Ebizmarts_SqualoMail_Model_Config::IS_QUOTE,
                    $magentoStoreId,
                    'magento_side_error',
                    'Json Encode Failure',
                    0,
                    $cart->getId(),
                    0
                );
            }

            $this->setToken(null);
        }

        return $allCarts;
    }

    /**
     * @return array|mixed
     * @throws Mage_Core_Exception
     */
    public function _getNewQuotes()
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $magentoStoreId = $this->getMagentoStoreId();

        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();
        $batchId = $this->getBatchId();
        $allCarts = array();
        $newCarts = $this->getQuoteCollection();
        $newCarts->addFieldToFilter('is_active', array('eq' => 1));
        $newCarts->addFieldToFilter('customer_email', array('notnull' => true));
        $newCarts->addFieldToFilter('items_count', array('gt' => 0));
        // select carts for the current Magento store id
        $newCarts->addFieldToFilter('store_id', array('eq' => $magentoStoreId));
        $helper->addResendFilter($newCarts, $magentoStoreId, Ebizmarts_SqualoMail_Model_Config::IS_QUOTE);
        // filter by first date if exists.
        if ($this->getFirstDate()) {
            $newCarts->addFieldToFilter('updated_at', array('gt' => $this->getFirstDate()));
        }

        //join with squalomail_ecommerce_sync_data table to filter by sync data.
        $this->joinLeftEcommerceSyncData($newCarts);
        // be sure that the quotes are already in squalomail and not deleted
        $this->getEcommerceQuoteCollection()->addWhere(
            $newCarts, "m4m.squalomail_sync_delta IS NULL", $this->getBatchLimitFromConfig()
        );

        foreach ($newCarts as $cart) {
            $cartId = $cart->getEntityId();
            $orderCollection = $this->getOrderCollection();
            $cartCustomerEmail = $cart->getCustomerEmail();
            $orderCollection->addFieldToFilter(
                'main_table.customer_email', array('eq' => $cartCustomerEmail)
            );
            $orderCollection->addFieldToFilter('main_table.updated_at', array('from' => $cart->getUpdatedAt()));
            //if cart is empty or customer has an order made after the abandonment skip current cart.
            $allVisibleItems = $cart->getAllVisibleItems();

            if (empty($allVisibleItems) || $orderCollection->getSize()) {
                $this->addSyncData($cartId);
                continue;
            }

            $customer = $this->getCustomerModel();
            $customer->setWebsiteId($this->getWebSiteIdFromMagentoStoreId($magentoStoreId));
            $customer->loadByEmail($cartCustomerEmail);
            $customerEmail = $customer->getEmail();

            if ($customerEmail != $cartCustomerEmail) {
                $allCartsForEmail = $this->getAllCartsByEmail($cartCustomerEmail);

                foreach ($allCartsForEmail as $cartForEmail) {
                    $counter = $this->getCounter();
                    $alreadySentCartId = $cartForEmail->getEntityId();
                    $allCarts[$counter]['method'] = 'DELETE';
                    $allCarts[$counter]['path'] = '/ecommerce/stores/'
                        . $squalomailStoreId
                        . '/carts/'
                        . $alreadySentCartId;
                    $allCarts[$counter]['operation_id'] = $batchId . '_' . $alreadySentCartId;
                    $allCarts[$counter]['body'] = '';

                    $this->markSyncDataAsDeleted($alreadySentCartId);
                    $this->setCounter($counter + 1);
                }

                $allCartsForEmail->clear();
            }

            // don't send the carts for guest customers who are registered
            if (!$cart->getCustomerId() && $customerEmail == $cartCustomerEmail) {
                $this->addSyncData($cartId);
                continue;
            }

            // send the products that not already sent
            $allCarts = $this->addProductNotSentData($cart, $allCarts);
            $cartJson = $this->makeCart($cart);

            if ($cartJson !== false) {
                if (!empty($cartJson)) {
                    $helper->modifyCounterSentPerBatch(Ebizmarts_SqualoMail_Helper_Data::QUO_NEW);

                    $counter = $this->getCounter();
                    $allCarts[$counter]['method'] = 'POST';
                    $allCarts[$counter]['path'] = '/ecommerce/stores/' . $squalomailStoreId . '/carts';
                    $allCarts[$counter]['operation_id'] = $batchId . '_' . $cartId;
                    $allCarts[$counter]['body'] = $cartJson;
                    $this->setCounter($this->getCounter() + 1);

                    $this->addSyncDataToken($cartId, $this->getToken());
                } else {
                    $error = $helper->__('There is not supported products in this cart.');

                    $this->addSyncDataError(
                        $cartId,
                        $error,
                        null,
                        false,
                        $dateHelper->getCurrentDateTime()
                    );
                }
            } else {
                $jsonErrorMessage = json_last_error_msg();

                $this->addSyncDataError(
                    $cartId,
                    $jsonErrorMessage,
                    null,
                    false,
                    $dateHelper->getCurrentDateTime()
                );

                //json encode failed
                $this->logSyncError(
                    $jsonErrorMessage,
                    Ebizmarts_SqualoMail_Model_Config::IS_QUOTE,
                    $magentoStoreId,
                    'magento_side_error',
                    'Json Encode Failure',
                    0,
                    $cart->getId(),
                    0
                );
            }

            $this->setToken(null);
        }

        return $allCarts;
    }

    /**
     * Get all existing carts in the current store view for a given email address.
     *
     * @param  $email
     * @return object
     */
    public function getAllCartsByEmail($email)
    {
        $allCartsForEmail = $this->getQuoteCollection();
        $allCartsForEmail->addFieldToFilter('is_active', array('eq' => 1));
        $allCartsForEmail->addFieldToFilter('store_id', array('eq' => $this->getMagentoStoreId()));
        $allCartsForEmail->addFieldToFilter('customer_email', array('eq' => $email));
        $this->joinLeftEcommerceSyncData($allCartsForEmail);
        // be sure that the quotes are already in squalomail and not deleted
        $where = "m4m.squalomail_sync_deleted = 0 "
            . "AND m4m.squalomail_store_id = '"
            . $this->getSqualomailStoreId() . "'";
        $this->getEcommerceQuoteCollection()->addWhere($allCartsForEmail, $where);

        return $allCartsForEmail;
    }

    /**
     * @param $cart
     * @param $isModified
     * @return string
     */
    public function makeCart($cart, $isModified = false)
    {
        $magentoStoreId = $this->getMagentoStoreId();

        $apiProduct = $this->getApiProducts();
        $apiProduct->setMagentoStoreId($magentoStoreId);

        $campaignId = $cart->getSqualomailCampaignId();
        $oneCart = array();
        $oneCart['id'] = $cart->getEntityId();
        $customer = $this->_getCustomer($cart, $magentoStoreId);

        if (empty($customer)) {
            return "";
        }

        $oneCart['customer'] = $customer;

        if ($campaignId) {
            $oneCart['campaign_id'] = $campaignId;
        }

        $oneCart['checkout_url'] = $this->_getCheckoutUrl($cart, $isModified);
        $oneCart['currency_code'] = $cart->getQuoteCurrencyCode();
        $oneCart['order_total'] = $cart->getGrandTotal();
        $oneCart['tax_total'] = 0;
        // get all items on the cart
        $lines = $this->_processCartLines($cart->getAllVisibleItems(), $apiProduct);

        $jsonData = "";

        if ($lines['count']) {
            $oneCart['lines'] = $lines['lines'];
            //encode to JSON
            $jsonData = json_encode($oneCart);
        }

        return $jsonData;
    }

    /**
     * @param $items
     * @param $apiProduct Ebizmarts_SqualoMail_Model_Api_Products
     * @return array
     */
    protected function _processCartLines($items, $apiProduct)
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $magentoStoreId = $this->getMagentoStoreId();

        $lines = array();
        $itemCount = 0;

        foreach ($items as $item) {
            $productId = $item->getProductId();
            $isTypeProduct = $this->isTypeProduct();
            $productSyncData = $this->getSqualomailEcommerceSyncDataModel()
                ->getEcommerceSyncDataItem($productId, $isTypeProduct, $squalomailStoreId);
            $line = array();

            if ($item->getProductType() == 'bundle' || $item->getProductType() == 'grouped') {
                continue;
            }

            if ($this->isProductTypeConfigurable($item)) {
                $variant = null;

                if ($item->getOptionByCode('simple_product')) {
                    $variant = $item->getOptionByCode('simple_product')->getProduct();
                }

                if (!$variant) {
                    continue;
                }

                $variantId = $variant->getId();
            } else {
                $variantId = $item->getProductId();
            }

            //id can not be 0 so we add 1 to $itemCount before setting the id.
            $productSyncError = $productSyncData->getSqualomailSyncError();
            $isProductEnabled = $apiProduct->isProductEnabled($productId, $magentoStoreId);

            if (!$isProductEnabled || ($productSyncData->getSqualomailSyncDelta() && $productSyncError == '')) {
                $itemCount++;
                $line['id'] = (string)$itemCount;
                $line['product_id'] = $productId;
                $line['product_variant_id'] = $variantId;
                $line['quantity'] = (int)$item->getQty();
                $line['price'] = $item->getRowTotal();
                $lines[] = $line;

                if (!$isProductEnabled) {
                    // update disabled products to remove the product from squalomail after sending the order
                    $apiProduct->updateDisabledProducts($productId);
                }
            }
        }

        return array('lines' => $lines, 'count' => $itemCount);
    }

    /**
     * Get URL for the cart.
     *
     * @param  $cart
     * @param  $isModified
     * @return string
     */
    protected function _getCheckoutUrl($cart, $isModified)
    {
        if (!$isModified) {
            $token = hash('md5', rand(0, 9999999));
        } else {
            $token = $cart->getSqualomailToken();
        }

        $url = Mage::getModel('core/url')->setStore($cart->getStoreId())->getUrl(
            '',
            array('_nosid' => true, '_secure' => true)
        )
            . 'squalomail/cart/loadquote?id=' . $cart->getEntityId() . '&token=' . $token;
        $this->setToken($token);
        return $url;
    }

    /**
     * @return int
     */
    protected function getBatchLimitFromConfig()
    {
        $helper = $this->getHelper();
        return $helper->getCartAmountLimit();
    }

    /**
     * Get Customer data for the cart.
     *
     * @param  $cart
     * @param  $magentoStoreId
     * @return array
     */
    public function _getCustomer($cart, $magentoStoreId)
    {
        $customer = array(
            "id" => hash('md5', strtolower($cart->getCustomerEmail())),
            "email_address" => $cart->getCustomerEmail(),
            "opt_in_status" => $this->getApiCustomersOptIn($magentoStoreId)
        );

        $firstName = $cart->getCustomerFirstname();

        if ($firstName) {
            $customer["first_name"] = $firstName;
        }

        $lastName = $cart->getCustomerLastname();

        if ($lastName) {
            $customer["last_name"] = $lastName;
        }

        $billingAddress = $cart->getBillingAddress();

        if ($billingAddress) {
            $street = $billingAddress->getStreet();
            $address = array();

            if (isset($street[0])) {
                $address['address1'] = $street[0];

                if (count($street) > 1) {
                    $address['address2'] = $street[1];
                }
            }

            $address = $this->_addBillingAddress($address, $billingAddress);

            if (!empty($address)) {
                $customer['address'] = $address;
            }
        }

        //company
        if ($billingAddress->getCompany()) {
            $customer["company"] = $billingAddress->getCompany();
        }

        return $customer;
    }

    /**
     * @param $address
     * @param $billingAddress
     * @return array
     */
    protected function _addBillingAddress($address, $billingAddress)
    {
        if ($billingAddress->getCity()) {
            $address['city'] = $billingAddress->getCity();
        }

        if ($billingAddress->getRegion()) {
            $address['province'] = $billingAddress->getRegion();
        }

        if ($billingAddress->getRegionCode()) {
            $address['province_code'] = $billingAddress->getRegionCode();
        }

        if ($billingAddress->getPostcode()) {
            $address['postal_code'] = $billingAddress->getPostcode();
        }

        if ($billingAddress->getCountry()) {
            $address['country'] = $this->getCountryModel($billingAddress);
            $address['country_code'] = $billingAddress->getCountry();
        }

        return $address;
    }

    /**
     * @param $cart
     * @param $allCarts
     * @return mixed
     */
    public function addProductNotSentData($cart, $allCarts)
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $magentoStoreId = $this->getMagentoStoreId();

        $helper = $this->getHelper();
        $apiProducts = $this->getApiProducts();
        $apiProducts->setSqualomailStoreId($squalomailStoreId);
        $apiProducts->setMagentoStoreId($magentoStoreId);

        $productData = $apiProducts->sendModifiedProduct($cart);
        $productDataArray = $helper->addEntriesToArray($allCarts, $productData, $this->getCounter());
        $allCarts = $productDataArray[0];
        $this->setCounter($productDataArray[1]);

        return $allCarts;
    }

    /**
     * @param Mage_Sales_Model_Resource_Quote_Collection $preFilteredCollection
     */
    public function joinLeftEcommerceSyncData($preFilteredCollection)
    {
        $this->_ecommerceQuotesCollection->joinLeftEcommerceSyncData($preFilteredCollection);
    }

    /**
     * @return Mage_Sales_Model_Resource_Quote_Collection
     */
    public function getQuoteCollection()
    {
        return Mage::getResourceModel('sales/quote_collection');
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    public function getCustomerModel()
    {
        return Mage::getModel("customer/customer");
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
     * @return int
     */
    public function getCounter()
    {
        return $this->_counter;
    }

    /**
     * @param $counter
     */
    public function setCounter($counter)
    {
        $this->_counter = $counter;
    }

    /**
     * Return the batchId for the batchJson of the carts.
     *
     * @return string
     */
    public function getBatchId()
    {
        return $this->_batchId;
    }

    /**
     * @param $batchId
     */
    public function setBatchId($batchId)
    {
        $this->_batchId = $batchId;
    }

    /**
     * Token for cart validation.
     *
     * @return string|null
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {

        $this->_token = $token;
    }

    /**
     * Returns first date of abandoned cart if exists.
     *
     * @return string|null
     */
    protected function getFirstDate()
    {
        return $this->_firstDate;
    }

    /**
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    protected function getOrderCollection()
    {
        return Mage::getResourceModel('sales/order_collection');
    }

    /**
     * @param $magentoStoreId
     * @return mixed
     */
    protected function getApiCustomersOptIn($magentoStoreId)
    {
        return Mage::getModel('squalomail/api_customers')->getOptIn($magentoStoreId);
    }

    /**
     * @param $billingAddress
     * @return mixed
     */
    protected function getCountryModel($billingAddress)
    {
        return Mage::getModel('directory/country')->loadByCode($billingAddress->getCountry())->getName();
    }

    /**
     * @param $item
     * @return bool
     */
    protected function isProductTypeConfigurable($item)
    {
        return $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
    }

    /**
     * @return string
     */
    protected function isTypeProduct()
    {
        return Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_Products
     */
    protected function getApiProducts()
    {
        return Mage::getModel('squalomail/api_products');
    }

    /**
     * @return string
     */
    protected function getItemType()
    {
        return Ebizmarts_SqualoMail_Model_Config::IS_QUOTE;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata_Quote_Collection
     */
    public function createEcommerceQuoteCollection()
    {
        /**
         * @var $collection Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata_Quote_Collection
         */
        $collection = Mage::getResourceModel('squalomail/ecommercesyncdata_quote_collection');

        return $collection;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata_Quote_Collection
     */
    public function getEcommerceQuoteCollection()
    {
        return $this->_ecommerceQuotesCollection;
    }
}
