<?php

/**
 * SqualoMail For Magento
 *
 * @category  Ebizmarts_SqualoMail
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 3:55 PM
 * @file:     Observer.php
 */
class Ebizmarts_SqualoMail_Model_Observer
{

    const PRODUCT_IS_ENABLED = 1;

    /**
     * @return Mage_Core_Model_Resource
     */
    protected function getCoreResource()
    {
        return Mage::getSingleton('core/resource');
    }

    /**
     * @return Mage_Core_Model_Config
     */
    protected function getConfig()
    {
        return Mage::getConfig();
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_Orders
     */
    protected function makeApiOrder()
    {
        return Mage::getModel('squalomail/api_orders');
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_PromoCodes
     */
    protected function makeApiPromoCode()
    {
        return Mage::getModel('squalomail/api_promoCodes');
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_PromoRules
     */
    protected function makeApiPromoRule()
    {
        return Mage::getModel('squalomail/api_promoRules');
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('squalomail');
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_Products
     */
    protected function makeApiProduct()
    {
        return Mage::getModel('squalomail/api_products');
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_Subscribers
     */
    protected function makeApiSubscriber()
    {
        return Mage::getModel('squalomail/api_subscribers');
    }

    /**
     * @return Mage_Newsletter_Model_Subscriber
     */
    protected function getSubscriberModel()
    {
        return Mage::getModel('newsletter/subscriber');
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_Customers
     */
    protected function makeApiCustomer()
    {
        return Mage::getModel('squalomail/api_customers');
    }

    /**
     * @return Mage_Customer_Model_Customer
     */
    protected function getCustomerModel()
    {
        return Mage::getModel('customer/customer');
    }

    /**
     * Handle save of System -> Configuration, section <squalomail>
     *
     * @param   Varien_Event_Observer $observer
     * @return  Varien_Event_Observer
     * @throws  Mage_Core_Exception
     */
    public function saveConfigBefore(Varien_Event_Observer $observer)
    {
        $config = $observer->getObject();

        if ($config->getSection() == "squalomail") {
            $configData = $config->getData();
            $configDataChanged = false;
            $helper = $this->makeHelper();
            $scopeArray = $helper->getCurrentScope();
            $squalomailStoreId = (isset($configData['groups']['general']['fields']['storeid']['value']))
                ? $configData['groups']['general']['fields']['storeid']['value']
                : null;
            $oldSqualomailStoreId = $helper->getSQMStoreId($scopeArray['scope_id'], $scopeArray['scope']);

            if (isset($configData['groups']['general']['fields']['apikey']['value'])
                && !$helper->isApiKeyObscure($configData['groups']['general']['fields']['apikey']['value'])
            ) {
                $apiKey = $configData['groups']['general']['fields']['apikey']['value'];
            } else {
                $helper->getApiKey($scopeArray['scope_id'], $scopeArray['scope']);
            }

            // If ecommerce data section is enabled only allow inheriting both entries
            // (list and SQM Store) at the same time.

            if ($this->isListXorStoreInherited($configData)) {
                if (isset($configData['groups']['general']['fields']['list']['inherit'])) {
                    unset($configData['groups']['general']['fields']['list']['inherit']);
                    $sqmStoreListId = $helper->getListIdByApiKeyAndSQMStoreId($apiKey, $squalomailStoreId);
                    $previouslyConfiguredListId = $helper->getGeneralList(
                        $scopeArray['scope_id'],
                        $scopeArray['scope']
                    );
                    $listId = (!empty($previouslyConfiguredListId)) ? $previouslyConfiguredListId : $sqmStoreListId;
                    $configData['groups']['general']['fields']['list']['value'] = $listId;
                    $configDataChanged = true;
                    $message = $helper->__(
                        'The audience configuration was automatically modified to show the audience '
                        . 'associated to the selected Squalomail store.'
                    );
                    $this->getAdminSession()->addError($message);
                } elseif (isset($configData['groups']['general']['fields']['storeid']['inherit'])) {
                    unset($configData['groups']['general']['fields']['storeid']['inherit']);
                    $configData['groups']['general']['fields']['storeid']['value'] = $oldSqualomailStoreId;
                    $configDataChanged = true;
                    $message = $helper->__(
                        'The Squalomail store configuration was not modified. There is a Squalomail audience configured '
                        . 'for this scope. Both must be set to inherit at the same time.'
                    );
                    $this->getAdminSession()->addError($message);
                }

                if ($configDataChanged) {
                    $config->setData($configData);
                }
            }
        }

        return $observer;
    }

    /**
     * return true if list or store is selected but the other one is inheriting.
     *
     * @param  $configData
     * @return bool
     */
    protected function isListXorStoreInherited($configData)
    {
        return (
            !isset($configData['groups']['general']['fields']['list']['inherit'])
            && isset($configData['groups']['general']['fields']['storeid']['value'])
            || !isset($configData['groups']['general']['fields']['storeid']['inherit'])
            && isset($configData['groups']['general']['fields']['list']['value']));
    }

    /**
     * Handle confirmation emails and subscription to Squalomail
     *
     * @param   Varien_Event_Observer $observer
     * @return  Varien_Event_Observer
     * @throws  Mage_Core_Exception
     */
    public function subscriberSaveBefore(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        $subscriberSource = $subscriber->getSubscriberSource();
        $storeId = $subscriber->getStoreId();
        $helper = $this->makeHelper();
        $isEnabled = $helper->isSubscriptionEnabled($storeId);

        if ($isEnabled && !$this->isSqualomailSave($subscriberSource)) {
            $statusChanged = $subscriber->getIsStatusChanged();

            //Override Magento status to always send double opt-in confirmation.
            if ($statusChanged && $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED
                && $helper->isSubscriptionConfirmationEnabled($storeId) && !$helper->isUseMagentoEmailsEnabled($storeId)
            ) {
                $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE);
                $this->addSuccessIfRequired($helper);
            }
        }

        return $observer;
    }

    /**
     * Handle interest groups for subscriber and allow Magento email to be sent if configured that way.
     *
     * @param   Varien_Event_Observer $observer
     * @return  Varien_Event_Observer
     * @throws  Mage_Core_Exception
     * @throws  Mage_Core_Model_Store_Exception
     */
    public function subscriberSaveAfter(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        $storeViewId = $this->getStoreViewIdBySubscriber($subscriber);
        $helper = $this->makeHelper();
        $isEnabled = $helper->isSubscriptionEnabled($storeViewId);
        $subscriberSource = $subscriber->getSubscriberSource();

        if ($isEnabled && !$this->isSqualomailSave($subscriberSource)) {
            $params = $this->getRequest()->getParams();
            $helper->saveInterestGroupData($params, $storeViewId, null, $subscriber);

            $this->createEmailCookie($subscriber);

            $apiSubscriber = $this->makeApiSubscriber();

            if ($helper->isUseMagentoEmailsEnabled($storeViewId) !== 1
                && $this->isMagentoSubscription($subscriberSource)
                || $this->isEmailConfirmationRequired($subscriberSource)
            ) {
                if ($subscriber->getIsStatusChanged()) {
                    $apiSubscriber->updateSubscriber($subscriber, true);
                } else {
                    $origData = $subscriber->getOrigData();

                    if (is_array($origData) && isset($origData['subscriber_status'])
                        && $origData['subscriber_status'] != $subscriber->getSubscriberStatus()
                    ) {
                        $apiSubscriber->updateSubscriber($subscriber, true);
                    }
                }
            } else {
                $subscriber->setImportMode(false);
            }
        }

        return $observer;
    }

    /**
     * Handle subscriber deletion from back end.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function handleSubscriberDeletion(Varien_Event_Observer $observer)
    {
        $subscriber = $observer->getEvent()->getSubscriber();
        $helper = $this->makeHelper();
        $isEnabled = $helper->isSubscriptionEnabled($subscriber->getStoreId());

        if ($isEnabled) {
            $this->makeApiSubscriber()->deleteSubscriber($subscriber);
        }

        return $observer;
    }

    /**
     * Add Subscriber first name and last name to Newsletter Grid.
     *
     * @param  Varien_Event_Observer $observer
     * @return $this|Varien_Event_Observer
     */
    public function alterNewsletterGrid(Varien_Event_Observer $observer)
    {

        $block = $observer->getEvent()->getBlock();
        if (!isset($block)) {
            return $this;
        }

        if ($block instanceof Mage_Adminhtml_Block_Newsletter_Subscriber_Grid) {
            $block->addColumnAfter(
                'firstname',
                array(
                    'header' => Mage::helper('newsletter')->__('Customer First Name'),
                    'index' => 'customer_firstname',
                    'renderer' => 'squalomail/adminhtml_newsletter_subscriber_renderer_firstname',
                ),
                'type'
            );

            $block->addColumnAfter(
                'lastname',
                array(
                    'header' => Mage::helper('newsletter')->__('Customer Last Name'),
                    'index' => 'customer_lastname',
                    'renderer' => 'squalomail/adminhtml_newsletter_subscriber_renderer_lastname'
                ),
                'firstname'
            );
        }

        return $observer;
    }

    /**
     * When Customer object is saved set it to be updated on SqualoMail if getSqualomailUpdateObserverRan() is false.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function customerSaveAfter(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $origEmail = $customer->getOrigData('email');
        $customerEmail = $customer->getEmail();
        $storeId = $customer->getStoreId();
        // if customer was created in admin, use store id selected for Squalomail.
        if (!$storeId) {
            $storeId = $customer->getSqualomailStoreView();
        }

        $helper = $this->makeHelper();
        $isEnabled = $helper->isSubscriptionEnabled($storeId);
        $params = $this->getRequest()->getParams();

        if ($isEnabled) {
            $customerId = $customer->getId();
            $subscriberEmail = ($origEmail) ? $origEmail : $customerEmail;
            $subscriber = $this->handleCustomerGroups($subscriberEmail, $params, $storeId, $customerId);
            $apiSubscriber = $this->makeApiSubscriber();

            if ($origEmail) {
                // check if customer has changed email address
                if ($origEmail != $customerEmail) {
                    if ($subscriber->getId()) {
                        // unsubscribe old email address
                        $apiSubscriber->deleteSubscriber($subscriber);

                        // subscribe new email address
                        $subscriberModel = $this->getSubscriberModel();
                        $subscriber = $subscriberModel->loadByCustomer($customer);
                        $subscriber->setSubscriberEmail($customerEmail); // make sure we set the new email address
                        $subscriber->save();
                    }
                }
            }

            //update subscriber data if a subscriber with the same email address exists and was not affected.
            if (!$origEmail || $origEmail == $customerEmail) {
                $apiSubscriber->update($customerEmail);
            }

            if ($helper->isEcomSyncDataEnabled($storeId)) {
                //update squalomail ecommerce data for that customer
                $apiCustomer = $this->makeApiCustomer();
                $apiCustomer->setSqualomailStoreId($helper->getSQMStoreId($storeId));
                $apiCustomer->setMagentoStoreId($storeId);
                $apiCustomer->update($customerId);
            }
        }

        return $observer;
    }

    public function customerAddressSaveBefore(Varien_Event_Observer $observer)
    {
        $customerId = $observer->getEvent()->getCustomerAddress()->getCustomerId();
        $customer = $this->getCustomerModel()->load($customerId);
        $storeId = $customer->getStoreId();
        $helper = $this->makeHelper();

        if ($helper->isSubscriptionEnabled($storeId)) {
            //update subscriber data if a subscriber with the same email address exists
            $this->makeApiSubscriber()->update($customer->getEmail());
        }

        if ($helper->isEcomSyncDataEnabled($storeId)) {
            //update squalomail ecommerce data for that customer
            $apiCustomer = $this->makeApiCustomer();
            $apiCustomer->setSqualomailStoreId($helper->getSQMStoreId($storeId));
            $apiCustomer->setMagentoStoreId($storeId);
            $apiCustomer->update($customerId);
        }

        return $observer;
    }

    /**
     * Set the products included the order to be updated on SqualoMail on the next cron job run.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * @throws Exception
     */
    public function newOrder(Varien_Event_Observer $observer)
    {
        $helper = $this->makeHelper();
        $post = $helper->getMageApp()->getRequest()->getPost('squalomail_subscribe');
        $order = $observer->getEvent()->getOrder();
        $storeId = $order->getStoreId();
        $ecommEnabled = $helper->isEcomSyncDataEnabled($storeId);
        $subEnabled = $helper->isSubscriptionEnabled($storeId);

        if ($subEnabled) {
            if (isset($post)) {
                $email = $order->getCustomerEmail();
                $subscriber = $helper->loadListSubscriber($post, $email);

                if ($subscriber) {
                    if (!$subscriber->getCustomerId()) {
                        $subscriber->setSubscriberFirstname($order->getCustomerFirstname());
                        $subscriber->setSubscriberLastname($order->getCustomerLastname());
                    }

                    $subscriber->subscribe($email);
                }
            }
        }

        if ($ecommEnabled) {
            $this->removeCampaignData();
            $items = $order->getAllItems();

            try {
                foreach ($items as $item) {
                    if ($this->isBundleItem($item) || $this->isConfigurableItem($item)) {
                        continue;
                    }

                    $squalomailStoreId = $helper->getSQMStoreId($storeId);
                    $productId = $item->getProductId();
                    $dataProduct = $this->getSqualomailEcommerceSyncDataModel()->getEcommerceSyncDataItem(
                        $productId,
                        Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT,
                        $squalomailStoreId
                    );

                    $isMarkedAsDeleted = $dataProduct->getSqualomailSyncDeleted();
                    $isMarkedAsModified = $dataProduct->getSqualomailSyncModified();

                    if (!$isMarkedAsDeleted && !$isMarkedAsModified) {
                        $apiProducts = $this->makeApiProduct();
                        $apiProducts->setSqualomailStoreId($squalomailStoreId);
                        $apiProducts->setMagentoStoreId($storeId);
                        $apiProducts->update($productId);
                    }
                }
            } catch (Exception $e) {
                $helper->logError($e->getMessage());
            }
        }

        return $observer;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata
     */
    public function getSqualomailEcommerceSyncDataModel()
    {
        return Mage::getModel('squalomail/ecommercesyncdata');
    }

    /**
     * Catch order save before event, mark it as modified and associate the landing page to the order data.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function orderSaveBefore(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $storeId = $order->getStoreId();
        $apiOrder = $this->makeApiOrder();
        $helper = $this->makeHelper();
        $ecommEnabled = $helper->isEcomSyncDataEnabled($storeId);

        if ($ecommEnabled) {
            $apiOrder->update($order->getId(), $storeId);
        }

        return $observer;
    }

    /**
     * When Order object is saved add the campaign id if available in the cookies.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function saveCampaignData(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $campaignCookie = $this->_getCampaignCookie();

        if ($campaignCookie) {
            $order->setSqualomailCampaignId($campaignCookie);
        }

        $landingCookie = $this->_getLandingCookie();

        if ($landingCookie && !$order->getSqualomailLandingPage()) {
            $order->setSqualomailLandingPage($landingCookie);
        }

        return $observer;
    }

    /**
     * Delete campaign and landing cookies.
     *
     * @return Varien_Event_Observer
     * @throws Exception
     */
    public function removeCampaignData()
    {
        if ($this->_getCampaignCookie()) {
            Mage::getModel('core/cookie')->delete('squalomail_campaign_id');
        }

        if ($this->_getLandingCookie()) {
            Mage::getModel('core/cookie')->delete('squalomail_landing_page');
        }
    }

    /**
     * Get campaign cookie if available.
     *
     * @return mixed
     */
    protected function _getCampaignCookie()
    {
        return Mage::getModel('core/cookie')->get('squalomail_campaign_id');
    }

    /**
     * Get landing_page cookie if exists.
     *
     * @return null
     */
    protected function _getLandingCookie()
    {
        return Mage::getModel('core/cookie')->get('squalomail_landing_page');
    }

    /**
     * Add section in order view with SqualoMail campaign data if available.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function addOrderViewMonkey(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        if (($block->getNameInLayout() == 'order_info')
            && ($child = $block->getChild('squalomail.order.info.monkey.block'))
        ) {
            $order = $block->getOrder();
            $storeId = $order->getStoreId();
            $helper = $this->makeHelper();
            $ecommEnabled = $helper->isEcomSyncDataEnabled($storeId);

            if ($ecommEnabled) {
                $transport = $observer->getTransport();
                if ($transport) {
                    $html = $transport->getHtml();
                    $html .= $child->toHtml();
                    $transport->setHtml($html);
                }
            }
        }

        return $observer;
    }

    /**
     * Add column to associate orders in grid gained from SqualoMail campaigns and automations.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * @throws Mage_Core_Exception
     */
    public function addColumnToSalesOrderGrid(Varien_Event_Observer $observer)
    {
        $helper = $this->makeHelper();
        $addColumnConfig = $helper->getMonkeyInGrid(0);
        $ecommEnabledAnyScope = $helper->isEcomSyncDataEnabledInAnyScope();
        $block = $observer->getEvent()->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid
            && $ecommEnabledAnyScope && $addColumnConfig
        ) {
            if ($addColumnConfig == Ebizmarts_SqualoMail_Model_Config::ADD_SQUALOMAIL_LOGO_TO_GRID
                || $addColumnConfig == Ebizmarts_SqualoMail_Model_Config::ADD_BOTH_TO_GRID
            ) {
                $block->addColumnAfter(
                    'squalomail_campaign_id',
                    array(
                        'header' => $helper->__('SqualoMail'),
                        'index' => 'squalomail_campaign_id',
                        'align' => 'center',
                        'filter' => false,
                        'renderer' => 'squalomail/adminhtml_sales_order_grid_renderer_squalomail',
                        'sortable' => false,
                        'width' => 70
                    ),
                    'created_at'
                );
            }

            if ($addColumnConfig == Ebizmarts_SqualoMail_Model_Config::ADD_SYNC_STATUS_TO_GRID
                || $addColumnConfig == Ebizmarts_SqualoMail_Model_Config::ADD_BOTH_TO_GRID
            ) {
                $block->addColumnAfter(
                    'squalomail_synced_flag',
                    array(
                        'header' => $helper->__('Synced to SqualoMail'),
                        'index' => 'squalomail_synced_flag',
                        'align' => 'center',
                        'filter' => false,
                        'renderer' => 'squalomail/adminhtml_sales_order_grid_renderer_squalomailOrder',
                        'sortable' => true,
                        'width' => 70
                    ),
                    'created_at'
                );

                $columnId = $block->getParam($block->getVarNameSort());
                $direction = $block->getParam($block->getVarNameDir());
                if ($columnId == 'squalomail_synced_flag') {
                    Mage::register('sort_column_dir', $direction);
                }
            }
        }

        return $observer;
    }


    public function addColumnToSalesOrderGridCollection(Varien_Event_Observer $observer)
    {
        $helper = $this->makeHelper();
        $addColumnConfig = $helper->getMonkeyInGrid(0);
        $ecommEnabledAnyScope = $helper->isEcomSyncDataEnabledInAnyScope();

        if ($ecommEnabledAnyScope && $addColumnConfig) {
            $collection = $observer->getOrderGridCollection();
            $select = $collection->getSelect();
            $fromClause = $select->getPart(Zend_Db_Select::FROM);
            //check if sqm alias is already defined, avoids possible conflicts
            if (array_key_exists('sqm', $fromClause)) {
                return;
            }

            $adapter = $this->getCoreResource()->getConnection('core_write');
            $select->joinLeft(
                array('sqm' => $collection->getTable('squalomail/ecommercesyncdata')),
                $adapter->quoteInto(
                    'sqm.related_id=main_table.entity_id AND type = ?',
                    Ebizmarts_SqualoMail_Model_Config::IS_ORDER
                ),
                array('sqm.squalomail_synced_flag', 'sqm.id')
            );
            $select->group("main_table.entity_id");
            $direction = $this->getRegistry();

            if ($direction) {
                $collection->addOrder('sqm.id', $direction);
                $this->removeRegistry();
            }
        }
    }

    /**
     * Add customer to the cart if it placed the email address in the popup or footer subscription form.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function loadCustomerToQuote(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $storeId = $quote->getStoreId();
        $helper = $this->makeHelper();
        $isEcomEnabled = $helper->isEcomSyncDataEnabled($storeId);
        $isAbandonedCartEnabled = $helper->isAbandonedCartEnabled($storeId);
        $email = null;

        if (!$this->isCustomerLoggedIn()
            && $isEcomEnabled && $isAbandonedCartEnabled
        ) {
            $action = $this->getRequestActionName();
            $onCheckout = ($action == 'saveOrder' || $action == 'savePayment' ||
                $action == 'saveShippingMethod' || $action == 'saveBilling');
            $emailCookie = $this->getEmailCookie();

            if ($emailCookie && $emailCookie != 'none' && !$onCheckout
            ) {
                $email = $this->getEmailFromPopUp($emailCookie);
            }

            if ($quote->getCustomerEmail() != $email && $email !== null) {
                $quote->setCustomerEmail($email);
            }
        }

        $campaignId = $this->_getCampaignCookie();
        if ($campaignId) {
            $quote->setSqualomailCampaignId($campaignId);
        }

        $landingCookie = $this->_getLandingCookie();
        if ($landingCookie) {
            $quote->setSqualomailLandingPage($landingCookie);
        }

        return $observer;
    }

    /**
     * Set the products included in the credit memo to be updated on SqualoMail on the next cron job run.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function newCreditMemo(Varien_Event_Observer $observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $order = $creditMemo->getOrder();
        $storeId = $order->getStoreId();
        $helper = $this->makeHelper();
        $ecomEnabled = $helper->isEcomSyncDataEnabled($storeId);
        $apiProduct = $this->makeApiProduct();
        $apiOrder = $this->makeApiOrder();

        if ($ecomEnabled) {
            $squalomailStoreId = $helper->getSQMStoreId($storeId);
            $apiProduct->setSqualomailStoreId($squalomailStoreId);
            $apiProduct->setMagentoStoreId($storeId);
            $items = $creditMemo->getAllItems();

            foreach ($items as $item) {
                if ($this->isBundleItem($item) || $this->isConfigurableItem($item)) {
                    continue;
                }

                $productId = $item->getProductId();
                $dataProduct = $this->getSqualomailEcommerceSyncDataModel()->getEcommerceSyncDataItem(
                    $productId,
                    Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT,
                    $squalomailStoreId
                );

                $isMarkedAsDeleted = $dataProduct->getSqualomailSyncDeleted();

                if (!$isMarkedAsDeleted) {
                    $apiProduct->update($productId);
                }
            }

            $apiOrder->update($order->getEntityId(), $storeId);
        }

        return $observer;
    }

    /**
     * If "unsubscribe" checkbox is checked, ubsubscribes the customer.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function createCreditmemo($observer)
    {
        $squalomailUnsubscribe = $this->getRequest()->getParam('squalomail_unsubscribe');

        if ($this->isUnsubscribeChecked($squalomailUnsubscribe)) {
            $creditMemo = $observer->getEvent()->getCreditmemo();
            $helper = $this->makeHelper();
            $order = $creditMemo->getOrder();
            $email = $order->getCustomerEmail();
            $subscriberModel = $this->getSubscriberModel();
            $subscriber = $subscriberModel->loadByEmail($email);
            $helper->unsubscribeMember($subscriber);
        }

        return $observer;
    }

    /**
     * Set the products included in the credit memo to be updated on SqualoMail on the next cron job run.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function cancelCreditMemo(Varien_Event_Observer $observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $order = $creditMemo->getOrder();
        $storeId = $order->getStoreId();
        $helper = $this->makeHelper();
        $ecomEnabled = $helper->isEcomSyncDataEnabled($storeId);
        $apiProduct = $this->makeApiProduct();
        $apiOrder = $this->makeApiOrder();

        if ($ecomEnabled) {
            $squalomailStoreId = $helper->getSQMStoreId($storeId);
            $apiProduct->setMagentoStoreId($storeId);
            $apiProduct->setSqualomailStoreId($squalomailStoreId);
            $items = $creditMemo->getAllItems();

            foreach ($items as $item) {
                if ($this->isBundleItem($item) || $this->isConfigurableItem($item)) {
                    continue;
                }

                $productId = $item->getProductId();
                $dataProduct = $this->getSqualomailEcommerceSyncDataModel()->getEcommerceSyncDataItem(
                    $productId,
                    Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT,
                    $squalomailStoreId
                );

                $isMarkedAsDeleted = $dataProduct->getSqualomailSyncDeleted();

                if (!$isMarkedAsDeleted) {
                    $apiProduct->update($productId);
                }
            }

            $apiOrder->update($order->getEntityId(), $storeId);
        }

        return $observer;
    }

    /**
     * Set the products canceled to be updated on SqualoMail on the next cron job run.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * @throws Exception
     */
    public function itemCancel(Varien_Event_Observer $observer)
    {
        $item = $observer->getEvent()->getItem();
        $helper = $this->makeHelper();
        $storeId = $item->getStoreId();
        $ecomEnabled = $helper->isEcomSyncDataEnabled($storeId);
        $apiProduct = $this->makeApiProduct();

        if ($ecomEnabled) {
            $squalomailStoreId = $helper->getSQMStoreId($storeId);
            $apiProduct->setSqualomailStoreId($squalomailStoreId);
            $apiProduct->setMagentoStoreId($storeId);

            $productId = $item->getProductId();
            $dataProduct = $this->getSqualomailEcommerceSyncDataModel()->getEcommerceSyncDataItem(
                $productId,
                Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT,
                $squalomailStoreId
            );

            $isMarkedAsDeleted = $dataProduct->getSqualomailSyncDeleted();

            if (!$this->isBundleItem($item) && !$this->isConfigurableItem($item) && !$isMarkedAsDeleted) {
                $apiProduct->update($productId);
            }
        }

        return $observer;
    }

    /**
     * When Product object is saved set it to be updated on SqualoMail if getSqualomailUpdateObserverRan() is false.
     *
     * @param  Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function productSaveAfter(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $helper = $this->makeHelper();
        $apiProduct = $this->makeApiProduct();
        $stores = $helper->getMageApp()->getStores();

        foreach ($stores as $storeId => $store) {
            $ecommEnabled = $helper->isEcommerceEnabled($storeId);

            if ($ecommEnabled) {
                $squalomailStoreId = $helper->getSQMStoreId($storeId);
                $apiProduct->setSqualomailStoreId($squalomailStoreId);
                $apiProduct->setMagentoStoreId($storeId);
                $status = $this->getCatalogProductStatusModel()->getProductStatus($product->getId(), $storeId);

                if ($status[$product->getId()] == self::PRODUCT_IS_ENABLED) {
                    $dataProduct = $this->getSqualomailEcommerceSyncDataModel()->getEcommerceSyncDataItem(
                        $product->getId(),
                        Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT,
                        $squalomailStoreId
                    );

                    $isMarkedAsDeleted = $dataProduct->getSqualomailSyncDeleted();
                    $errorMessage = $dataProduct->getSqualomailSyncError();

                    if ($isMarkedAsDeleted
                        || $errorMessage == Ebizmarts_SqualoMail_Model_Api_Products::PRODUCT_DISABLED_IN_MAGENTO
                    ) {
                        $dataProduct->delete();
                    } else {
                        $apiProduct->update($product->getId());
                    }
                } else {
                    $apiProduct->updateDisabledProducts($product->getId());
                }
            }
        }

        return $observer;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     */
    public function productAttributeUpdate(Varien_Event_Observer $observer)
    {
        $productIds = $observer->getEvent()->getProductIds();
        $helper = $this->makeHelper();
        $apiProduct = $this->makeApiProduct();
        $squalomailStoreIdsArray = $helper->getAllSqualoMailStoreIds();

        foreach ($squalomailStoreIdsArray as $scopeData => $squalomailStoreId) {
            $scopeArray = $this->getScopeArrayFromString($scopeData);
            $ecommEnabled = $helper->isEcommerceEnabled($scopeArray['scope_id'], $scopeArray['scope']);
            $apiProduct->setSqualomailStoreId($squalomailStoreId);

            if ($ecommEnabled) {
                foreach ($productIds as $productId) {
                    $dataProduct = $this->getSqualomailEcommerceSyncDataModel()->getEcommerceSyncDataItem(
                        $productId,
                        Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT,
                        $squalomailStoreId
                    );

                    $isMarkedAsDeleted = $dataProduct->getSqualomailSyncDeleted();

                    if (!$isMarkedAsDeleted) {
                        $apiProduct->update($productId);
                    }
                }
            }
        }

        return $observer;
    }

    /**
     * @param $emailCookie
     * @return mixed
     */
    protected function getEmailFromPopUp($emailCookie)
    {
        $emailCookieArr = explode('/', $emailCookie);
        $email = $emailCookieArr[0];
        $email = str_replace(' ', '+', $email);

        return $email;
    }

    /**
     * @param $order
     */
    protected function handleOrderUpdate($order)
    {
        $storeId = Mage::app()->getStore()->getStoreId();

        if ($storeId == 0) {
            $this->handleAdminOrderUpdate($order);
        } else {
            $helper = $this->makeHelper();
            $apiOrder =  $this->makeApiOrder();
            $apiOrder->setMagentoStoreId($storeId);
            $apiOrder->setSqualomailStoreId($helper->getSQMStoreId($storeId));
            $apiOrder->update($order->getId(), $storeId);
        }
    }

    public function salesruleSaveAfter(Varien_Event_Observer $observer)
    {
        $promoRulesApi = $this->makeApiPromoRule();
        $rule = $observer->getEvent()->getRule();
        $ruleId = $rule->getRuleId();
        $promoRulesApi->update($ruleId);

        return $observer;
    }

    public function salesruleDeleteAfter(Varien_Event_Observer $observer)
    {
        $promoRulesApi = $this->makeApiPromoRule();
        $rule = $observer->getEvent()->getRule();
        $ruleId = $rule->getRuleId();
        $promoRulesApi->markAsDeleted($ruleId);

        return $observer;
    }

    public function secondaryCouponsDelete(Varien_Event_Observer $observer)
    {
        $promoCodesApi = $this->makeApiPromoCode();
        $params = $this->getRequest()->getParams();

        if (isset($params['ids']) && isset($params['id'])) {
            $promoRuleId = $params['id'];
            $promoCodeIds = $params['ids'];

            foreach ($promoCodeIds as $promoCodeId) {
                $promoCodesApi->markAsDeleted($promoCodeId, $promoRuleId);
            }
        }

        return $observer;
    }

    public function cleanProductImagesCacheAfter(Varien_Event_Observer $observer)
    {
        $message = 'Image cache has been flushed please resend the products in order to update image URL.';
        $helper = $this->makeHelper();
        $configValues = array(array(Ebizmarts_SqualoMail_Model_Config::PRODUCT_IMAGE_CACHE_FLUSH, 1));
        $helper->saveSqualomailConfig($configValues, 0, 'default');
        $helper->addAdminWarning($message);

        return $observer;
    }

    protected function markProductsAsModified()
    {
        $tableName = $squalomailTableName = $this->getCoreResource()
            ->getTableName('squalomail/ecommercesyncdata');
        $sqlQuery = "UPDATE " . $tableName . " "
            . "SET squalomail_sync_modified = 1 "
            . "WHERE type = '" . Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT . "';";
        $connection = $this->getCoreResource()->getConnection('core_write');
        $connection->query($sqlQuery);
    }

    /**
     * @param $item
     * @return bool
     */
    protected function isBundleItem($item)
    {
        return $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE;
    }

    /**
     * @param $item
     * @return bool
     */
    protected function isConfigurableItem($item)
    {
        return $item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
    }

    /**
     * Transform array in format scope_scopeId to array.
     *
     * @param  $scopeData
     * @return array
     */
    protected function getScopeArrayFromString($scopeData)
    {
        $scopeArray = explode('_', $scopeData);
        return array('scope' => $scopeArray[0], 'scope_id' => $scopeArray[1]);
    }

    /**
     * @return string|null
     */
    protected function getRegistry()
    {
        return Mage::registry('sort_column_dir');
    }

    protected function removeRegistry()
    {
        return Mage::unregister('sort_column_dir');
    }

    /**
     * Add success message if subscribing from customer account.
     *
     * @param $helper
     */
    protected function addSuccessIfRequired($helper)
    {
        $request = Mage::app()->getRequest();
        $module = $request->getControllerModule();
        $moduleController = $request->getControllerName();
        $moduleControllerAction = $request->getActionName();
        $fullActionName = $module . '_' . $moduleController . '_' . $moduleControllerAction;

        if (strstr($fullActionName, 'Mage_Newsletter_manage_save')) {
            Mage::getSingleton('customer/session')->addSuccess(
                $helper->__('Confirmation request has been sent.')
            );
        }
    }

    /**
     * @param $subscriber
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function createEmailCookie($subscriber)
    {
        if (!$this->isCustomerLoggedIn() && !Mage::app()->getStore()->isAdmin()) {
            Mage::getModel('core/cookie')->set(
                'email',
                $subscriber->getSubscriberEmail(),
                null,
                null,
                null,
                null,
                false
            );
        }
    }

    public function addCustomerTab(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        $helper = $this->makeHelper();
        // add tab in customer edit page
        if ($block instanceof Mage_Adminhtml_Block_Customer_Edit_Tabs) {
            $customerId = (int)$this->getRequest()->getParam('id');
            $customer = Mage::getModel('customer/customer')->load($customerId);
            $storeId = $customer->getStoreId();
            //If the customer was created in the admin panel use the store view selected for SqualoMail.
            if (!$storeId) {
                $storeId = $customer->getSqualomailStoreView();
            }

            if ($helper->getLocalInterestCategories($storeId)
                && ($this->getRequest()->getActionName() == 'edit'
                || $this->getRequest()->getParam('type'))
            ) {
                $block->addTab(
                    'squalomail', array(
                        'label' => $helper->__('SqualoMail'),
                        'url' => $block->getUrl('adminhtml/squalomail/index', array('_current' => true)),
                        'class' => 'ajax'
                    )
                );
            }
        }

        return $observer;
    }

    protected function getRequest()
    {
        return Mage::app()->getRequest();
    }

    /**
     * Handle frontend customer interest groups only if is not subscribed and all admin customer groups.
     *
     * @param       $subscriberEmail
     * @param       $params
     * @param       $storeId
     * @param null  $customerId
     * @return Mage_Newsletter_Model_Subscriber
     * @throws Mage_Core_Model_Store_Exception
     */
    public function handleCustomerGroups($subscriberEmail, $params, $storeId, $customerId = null)
    {
        $helper = $this->makeHelper();
        $subscriberModel = $this->getSubscriberModel();
        $subscriber = $subscriberModel->loadByEmail($subscriberEmail);

        if ($subscriber->getId()) {
            $helper->saveInterestGroupData($params, $storeId, $customerId, $subscriber);
        } elseif (isset($params['customer_id'])) {
            $groups = $helper->getInterestGroupsIfAvailable($params);

            if ($groups) {
                $helper->saveInterestGroupData($params, $storeId, $customerId);
                $this->getWarningMessageAdminHtmlSession($helper);
            }
        } else {
            //save frontend groupdata when customer is not subscribed.
            $helper->saveInterestGroupData($params, $storeId, $customerId);
        }

        return $subscriber;
    }

    /**
     * @return boolean
     */
    protected function isUnsubscribeChecked($squalomailUnsubscribe)
    {
        if ($squalomailUnsubscribe === 'on') {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    protected function getEmailCookie()
    {
        $emailCookie = Mage::getModel('core/cookie')->get('email');

        return $emailCookie;
    }

    /**
     * @return mixed
     */
    protected function isCustomerLoggedIn()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    /**
     * @return string
     */
    protected function getRequestActionName()
    {
        return $this->getRequest()->getActionName();
    }

    /**
     * @param $helper
     * @return mixed
     */
    protected function getWarningMessageAdminHtmlSession($helper)
    {
        return Mage::getSingleton('adminhtml/session')->addWarning(
            $helper->__('The customer must be subscribed for this change to apply.')
        );
    }

    /**
     * @return Mage_Catalog_Model_Product_Status
     */
    protected function getCatalogProductStatusModel()
    {
        return Mage::getModel('catalog/product_status');
    }

    /**
     * @param int $customerId
     * @return int|null
     */
    protected function getStoreViewIdByCustomerId($customerId)
    {
        $storeViewId = null;
        $customer = Mage::getModel("customer/customer")->load($customerId);

        if ($customer->getId() !== null) {
            $storeViewId = $customer->getSqualomailStoreView();
        }

        return $storeViewId;
    }

    /**
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @return int|null
     */
    protected function getStoreViewIdBySubscriber($subscriber)
    {
        $storeViewId = $subscriber->getStoreId();

        if ($storeViewId == 0) {
            $storeViewIdByCustomerId = $this->getStoreViewIdByCustomerId($subscriber->getCustomerId());

            if ($storeViewIdByCustomerId !== null) {
                $storeViewId = $storeViewIdByCustomerId;
            }
        }

        return $storeViewId;
    }

    /**
     * @param string $subscriberSource
     * @return bool
     */
    protected function isEmailConfirmationRequired($subscriberSource)
    {
        return $subscriberSource === Ebizmarts_SqualoMail_Model_Subscriber::SUBSCRIBE_CONFIRMATION;
    }

    /**
     * @param string $subscriberSource
     * @return bool
     */
    protected function isMagentoSubscription($subscriberSource)
    {
        return empty($subscriberSource);
    }

    /**
     * @param string $subscriberSource
     * @return bool
     */
    protected function isSqualomailSave($subscriberSource)
    {
        return $subscriberSource === Ebizmarts_SqualoMail_Model_Subscriber::SQUALOMAIL_SUBSCRIBE;
    }
}
