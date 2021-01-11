<?php

class Ebizmarts_SqualoMail_Model_ObserverTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mage::app('default');
    }

    public function testProductAttributeUpdateIsUsingCorrectStoreId()
    {
        $scopeId = 1;
        $scope = 'stores';
        $squalomailStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $squalomailStoreIdsArray = array('stores_1' => $squalomailStoreId);
        $isMarkedAsDeleted = 0;
        $type = Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT;
        $productIds [1]= 12;
        $productIds [2]= 34;

        /**
         * @var \Ebizmarts_SqualoMail_Model_Observer $modelMock
         */
        $modelMock = $this->getSqualomailObserverMock();

        $apiProductsMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Products::class)
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();

        $helperMock = $this->getHelperMock();

        $eventMock = $this->getEventObserverMock();

        $dataProductMock = $this->getEcommerceModelMock();

        $eventMock->expects($this->once())->method('getProductIds')->willReturn(array(12, 34));

        $modelMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);
        $modelMock->expects($this->once())->method('makeApiProduct')->willReturn($apiProductsMock);

        $helperMock->expects($this->once())->method('getAllSqualoMailStoreIds')->willReturn($squalomailStoreIdsArray);
        $helperMock->expects($this->once())->method('isEcommerceEnabled')->with($scopeId, $scope)->willReturn(true);

        $modelMock->expects($this->exactly(2))
            ->method('getSqualomailEcommerceSyncDataModel')
            ->willReturn($dataProductMock);

        $dataProductMock->expects($this->exactly(2))->method('getEcommerceSyncDataItem')
            ->withConsecutive(
                array($productIds[1], $type, $squalomailStoreId),
                array($productIds[2], $type, $squalomailStoreId)
            )->willReturnOnConsecutiveCalls(
                $dataProductMock,
                $dataProductMock
            );

        $apiProductsMock->expects($this->exactly(2))->method('update')->withConsecutive(
            array($productIds[1]),
            array($productIds[2])
        );

        $dataProductMock->expects($this->exactly(2))->method('getSqualomailSyncDeleted')->willReturnOnConsecutiveCalls(
            $isMarkedAsDeleted,
            $isMarkedAsDeleted
        );

        $eventObserverMock = $this->makeEventObserverMock($eventMock, 1);
        $modelMock->productAttributeUpdate($eventObserverMock);
    }

    public function testSaveCampaignDataCallsCorrectFunctions()
    {
        /**
         * @var \Ebizmarts_SqualoMail_Model_Observer $modelMock
         */
        $modelMock = $this->getSqualomailObserverMock();

        $modelMock->expects($this->once())->method("_getCampaignCookie")->willReturn("abcd123");
        $modelMock->expects($this->once())->method("_getLandingCookie")->willReturn("abcd");

        $orderMock = $this->getOrderMock();
        $orderMock->expects($this->once())->method("setSqualomailCampaignId")->with("abcd123");
        $orderMock->expects($this->once())->method("getSqualomailLandingPage")->willReturn(null);
        $orderMock->expects($this->once())->method("setSqualomailLandingPage")->with("abcd");

        $eventMock = $this->getEventObserverMock();
        $eventMock->expects($this->once())->method("getOrder")->willReturn($orderMock);

        $eventObserverMock = $this->makeEventObserverMock($eventMock, 1);

        $modelMock->saveCampaignData($eventObserverMock);
    }

    /**
     * @param $eventMock
     * @param $callCount
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function makeEventObserverMock($eventMock, $callCount)
    {
        $eventObserverMock = $this->getObserverMock();

        $eventObserverMock->expects($this->exactly($callCount))->method('getEvent')->willReturn($eventMock);

        return $eventObserverMock;
    }

    public function testHandleSubscriberDeletion()
    {
        $storeId = 1;

        $eventObserverMock = $this->getObserverMock();

        $eventMock = $this->getEventObserverMock();

        $observerMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $subscriberMock = $this->getSubscriberMock();

        $apiSubscriberMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('deleteSubscriber'))
            ->getMock();

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getSubscriber')->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeId)->willReturn(true);

        $observerMock->expects($this->once())->method('makeApiSubscriber')->willReturn($apiSubscriberMock);

        $apiSubscriberMock->expects($this->once())->method('deleteSubscriber')->with($subscriberMock);

        $observerMock->handleSubscriberDeletion($eventObserverMock);
    }

    public function testCustomerSaveAfter()
    {
        $adminStoreId = 0;
        $storeId = 1;
        $oldEmailAddress = 'oldEmail@example.com';
        $newEmailAddress = 'newEmail@example.com';
        $subscriberEmail = ($oldEmailAddress) ? $oldEmailAddress : $newEmailAddress;
        $subscriberId = 1;
        $customerId = 1;
        $params = array();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getId', 'getOrigData', 'getEmail', 'getStoreId', 'getSqualomailStoreView'))
            ->getMock();

        $eventObserverMock = $this->getObserverMock();

        $eventMock = $this->getEventObserverMock();

        $requestMock = $this->getRequestMock();

        $observerMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'makeHelper', 'makeApiSubscriber', 'getSubscriberModel',
                    'makeApiCustomer', 'getRequest', 'handleCustomerGroups'
                )
            )
            ->getMock();

        $helperMock = $this->getHelperMock();

        $apiSubscriberMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('deleteSubscriber', 'updateSubscriber', 'update'))
            ->getMock();

        $subscriberMock = $this->getSubscriberMock();

        $subscriberMockTwo = $this->getSubscriberMock();

        $apiCustomerMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Customers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('update', 'setSqualomailStoreId', 'setMagentoStoreId'))
            ->getMock();

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);

        $customerMock->expects($this->once())->method('getOrigData')->with('email')->willReturn($oldEmailAddress);
        $customerMock->expects($this->once())->method('getEmail')->willReturn($newEmailAddress);
        $customerMock->expects($this->once())->method('getStoreId')->willReturn($adminStoreId);
        $customerMock->expects($this->once())->method('getSqualomailStoreView')->willReturn($storeId);

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeId)->willReturn(true);

        $observerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParams')->willReturn($params);

        $customerMock->expects($this->once())->method('getId')->willReturn($customerId);

        $observerMock
            ->expects($this->once())
            ->method('handleCustomerGroups')
            ->with($subscriberEmail, $params, $storeId, $customerId)
            ->willReturn($subscriberMock);
        $observerMock->expects($this->once())->method('makeApiSubscriber')->willReturn($apiSubscriberMock);

        $subscriberMock->expects($this->once())->method('getId')->willReturn($subscriberId);

        $apiSubscriberMock->expects($this->once())->method('deleteSubscriber')->with($subscriberMock);

        $observerMock->expects($this->once())->method('getSubscriberModel')->willReturn($subscriberMockTwo);

        $subscriberMockTwo->expects($this->once())->method('loadByCustomer')->with($customerMock)->willReturnSelf();
        $subscriberMockTwo->expects($this->once())->method('setSubscriberEmail')->with($newEmailAddress);
        $subscriberMockTwo->expects($this->once())->method('save')->willReturnSelf();

        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn(true);

        $observerMock->expects($this->once())->method('makeApiCustomer')->willReturn($apiCustomerMock);


        $squalomailStoreId = 1;
        $helperMock->expects($this->once())->method('getSQMStoreId')->with($storeId)->willReturn($squalomailStoreId);

        $apiCustomerMock->expects($this->once())->method('setSqualomailStoreId')->with($squalomailStoreId);
        $apiCustomerMock->expects($this->once())->method('setMagentoStoreId')->with($storeId);
        $apiCustomerMock->expects($this->once())->method('update')->with($customerId);

        $observerMock->customerSaveAfter($eventObserverMock);
    }

    public function testCustomerAddressSaveBefore()
    {
        $storeId = 1;
        $customerId = 1;
        $customerEmail = 'customer@email.com';

        $eventObserverMock = $this->getObserverMock();

        $eventMock = $this->getEventObserverMock();

        $observerMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $customerMock = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(array('load', 'getStoreId', 'getEmail'))
            ->getMock();

        $customerAddressMock = $this->getMockBuilder(Mage_Customer_Model_Address::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCustomerId'))
            ->getMock();

        $apiCustomerMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Customers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();

        $apiSubscriberMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getCustomerAddress')->willReturn($customerAddressMock);

        $customerAddressMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);

        $observerMock->expects($this->once())->method('getCustomerModel')->willReturn($customerMock);

        $customerMock->expects($this->once())->method('load')->with($customerId)->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getStoreId')->willReturn($customerId);

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeId)->willReturn(true);

        $customerMock->expects($this->once())->method('getEmail')->willReturn($customerEmail);

        $observerMock->expects($this->once())->method('makeApiSubscriber')->willReturn($apiSubscriberMock);

        $apiSubscriberMock->expects($this->once())->method('update')->with($customerEmail);

        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn(true);

        $observerMock->expects($this->once())->method('makeApiCustomer')->willReturn($apiCustomerMock);

        $apiCustomerMock->expects($this->once())->method('update')->with($customerId);

        $observerMock->customerAddressSaveBefore($eventObserverMock);
    }

    public function testNewOrder()
    {
        $storeId = 1;
        $post = 1;
        $productId = 1;
        $squalomailStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $customerEmail = 'email@example.com';
        $customerFirstname = 'John';
        $customerLastname = 'Smith';
        $isMarkedAsDeleted = 0;
        $isMarkedAsDeleted = 0;
        $type = Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT;

        $itemMock = $this->getOrderItemMock();
        $orderMock = $this->getOrderMock();
        $eventObserverMock = $this->getObserverMock();
        $eventMock = $this->getEventObserverMock();
        $observerMock = $this->getSqualomailObserverMock();
        $helperMock = $this->getHelperMock();
        $mageAppMock = $this->getMageAppMock();
        $requestMock = $this->getRequestMock();
        $subscriberMock = $this->getSubscriberMock();

        $apiProductsMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Products::class)
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();

        $dataProductMock = $this->getEcommerceModelMock();

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);
        $mageAppMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getPost')->with('squalomail_subscribe')->willReturn($post);

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $orderMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn(true);

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeId)->willReturn(true);

        $orderMock->expects($this->once())->method('getCustomerEmail')->willReturn($customerEmail);

        $helperMock
            ->expects($this->once())
            ->method('loadListSubscriber')
            ->with($post, $customerEmail)
            ->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('getCustomerId')->willReturn(false);

        $orderMock->expects($this->once())->method('getCustomerFirstname')->willReturn($customerFirstname);

        $subscriberMock->expects($this->once())->method('setSubscriberFirstname')->with($customerFirstname);

        $orderMock->expects($this->once())->method('getCustomerLastname')->willReturn($customerLastname);

        $subscriberMock->expects($this->once())->method('setSubscriberLastname')->with($customerLastname);

        $subscriberMock->expects($this->once())->method('subscribe')->with($customerEmail);

        $observerMock->expects($this->once())->method('removeCampaignData');

        $orderMock->expects($this->once())->method('getAllItems')->willReturn(array($itemMock));

        $observerMock->expects($this->once())->method('isBundleItem')->with($itemMock)->willReturn(false);
        $observerMock->expects($this->once())->method('isConfigurableItem')->with($itemMock)->willReturn(false);

        $itemMock->expects($this->once())->method('getProductId')->willReturn($productId);

        $helperMock->expects($this->once())->method('getSQMStoreId')->with($storeId)->willReturn($squalomailStoreId);

        $observerMock->expects($this->once())->method('makeApiProduct')->willReturn($apiProductsMock);

        $apiProductsMock->expects($this->once())->method('update')->with($productId);

        $dataProductMock->expects($this->once())
            ->method('getSqualomailSyncDeleted')
            ->willReturn($isMarkedAsDeleted);

        $dataProductMock->expects($this->once())
            ->method('getSqualomailSyncModified')
            ->willReturn($isMarkedAsDeleted);

        $observerMock->expects($this->once())
            ->method('getSqualomailEcommerceSyncDataModel')
            ->willReturn($dataProductMock);

        $dataProductMock->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->with($productId, $type, $squalomailStoreId)
            ->willReturn($dataProductMock);

        $observerMock->newOrder($eventObserverMock);
    }

    public function testNewOrderNotModified()
    {
        $storeId = 1;
        $post = 1;
        $productId = 1;
        $squalomailStoreId = 'a1s2d3f4g5h6j7k8l9n0';
        $customerEmail = 'email@example.com';
        $customerFirstname = 'John';
        $customerLastname = 'Smith';
        $isMarkedAsDeleted = 0;
        $isMarkedAsDeleted = 1;
        $type = Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT;

        $itemMock = $this->getOrderItemMock();
        $orderMock = $this->getOrderMock();
        $eventObserverMock = $this->getObserverMock();
        $eventMock = $this->getEventObserverMock();
        $observerMock = $this->getSqualomailObserverMock();
        $helperMock = $this->getHelperMock();
        $mageAppMock = $this->getMageAppMock();
        $requestMock = $this->getRequestMock();
        $subscriberMock = $this->getSubscriberMock();
        $dataProductMock = $this->getEcommerceModelMock();

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);
        $mageAppMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getPost')->with('squalomail_subscribe')->willReturn($post);

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $orderMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn(true);

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeId)->willReturn(true);

        $orderMock->expects($this->once())->method('getCustomerEmail')->willReturn($customerEmail);

        $helperMock
            ->expects($this->once())
            ->method('loadListSubscriber')
            ->with($post, $customerEmail)
            ->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('getCustomerId')->willReturn(false);

        $orderMock->expects($this->once())->method('getCustomerFirstname')->willReturn($customerFirstname);

        $subscriberMock->expects($this->once())->method('setSubscriberFirstname')->with($customerFirstname);

        $orderMock->expects($this->once())->method('getCustomerLastname')->willReturn($customerLastname);

        $subscriberMock->expects($this->once())->method('setSubscriberLastname')->with($customerLastname);

        $subscriberMock->expects($this->once())->method('subscribe')->with($customerEmail);

        $observerMock->expects($this->once())->method('removeCampaignData');

        $orderMock->expects($this->once())->method('getAllItems')->willReturn(array($itemMock));

        $observerMock->expects($this->once())->method('isBundleItem')->with($itemMock)->willReturn(false);
        $observerMock->expects($this->once())->method('isConfigurableItem')->with($itemMock)->willReturn(false);

        $itemMock->expects($this->once())->method('getProductId')->willReturn($productId);

        $helperMock->expects($this->once())->method('getSQMStoreId')->with($storeId)->willReturn($squalomailStoreId);

        $dataProductMock->expects($this->once())
            ->method('getSqualomailSyncDeleted')
            ->willReturn($isMarkedAsDeleted);

        $dataProductMock->expects($this->once())
            ->method('getSqualomailSyncModified')
            ->willReturn($isMarkedAsDeleted);

        $observerMock->expects($this->once())
            ->method('getSqualomailEcommerceSyncDataModel')
            ->willReturn($dataProductMock);

        $dataProductMock->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->with($productId, $type, $squalomailStoreId)
            ->willReturn($dataProductMock);

        $observerMock->newOrder($eventObserverMock);
    }

    public function testAddColumnToSalesOrderGridCollection()
    {
        $addColumnConfig = 1;
        $scopeId = 0;
        $fromCond = array(
            'main_table' => array(
                'joinType' => 'from',
                'schema' => '',
                'tableName' => 'sales_flat_order_grid',
                'joinCondition' => ''
            )
        );
        $sqmTableName = 'squalomail_ecommerce_sync_data';
        $condition = 'sqm.related_id=main_table.entity_id AND type = ' . Ebizmarts_SqualoMail_Model_Config::IS_ORDER;
        $direction = 'ASC';

        $eventObserverMock = $this->getObserverMock();

        $observerMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $orderGridCollectionMock = $this->getMockBuilder(Mage_Sales_Model_Resource_Order_Grid_Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSelect', 'getTable', 'addOrder'))
            ->getMock();

        $selectMock = $this->getMockBuilder(Varien_Db_Select::class)
            ->disableOriginalConstructor()
            ->setMethods(array('joinLeft', 'group', 'getPart'))
            ->getMock();

        $coreResourceMock = $this->getMockBuilder(Mage_Core_Model_Resource::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getConnection'))
            ->getMock();

        $writeAdapterMock = $this->getMockBuilder(Varien_Db_Adapter_Pdo_Mysql::class)
            ->disableOriginalConstructor()
            ->setMethods(array('quoteInto'))
            ->getMock();

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getMonkeyInGrid')->with($scopeId)->willReturn($addColumnConfig);
        $helperMock->expects($this->once())->method('isEcomSyncDataEnabledInAnyScope')->willReturn(true);

        $eventObserverMock
            ->expects($this->once())
            ->method('getOrderGridCollection')
            ->willReturn($orderGridCollectionMock);

        $orderGridCollectionMock->expects($this->once())->method('getSelect')->willReturn($selectMock);

        $selectMock->expects($this->once())->method('getPart')->with(Zend_Db_Select::FROM)->willReturn($fromCond);

        $orderGridCollectionMock
            ->expects($this->once())
            ->method('getTable')
            ->with('squalomail/ecommercesyncdata')
            ->willReturn($sqmTableName);

        $selectMock
            ->expects($this->once())
            ->method('joinLeft')
            ->with(array('sqm' => $sqmTableName), $condition, array('sqm.squalomail_synced_flag', 'sqm.id'));

        $observerMock->expects($this->once())->method('getCoreResource')->willReturn($coreResourceMock);

        $coreResourceMock
            ->expects($this->once())
            ->method('getConnection')
            ->with('core_write')
            ->willReturn($writeAdapterMock);

        $writeAdapterMock
            ->expects($this->once())
            ->method('quoteInto')
            ->with('sqm.related_id=main_table.entity_id AND type = ?', Ebizmarts_SqualoMail_Model_Config::IS_ORDER)
            ->willReturn($condition);

        $selectMock->expects($this->once())->method('group');

        $observerMock->expects($this->once())->method('getRegistry')->willReturn($direction);

        $orderGridCollectionMock->expects($this->once())->method('addOrder')->with('sqm.id', $direction);

        $observerMock->expects($this->once())->method('removeRegistry');

        $observerMock->addColumnToSalesOrderGridCollection($eventObserverMock);
    }


    /**
     * @param array $data
     * @dataProvider subscriberSaveBeforeDataProvider
     */
    public function testSubscriberSaveBefore($data)
    {
        $getStoreId = $data['getStoreId'];
        $storeId = 1;
        $subscriberSource = null;

        $eventObserverMock = $this->getObserverMock();
        $observerMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $eventMock = $this->getEventObserverMock();

        $subscriberMock = $this->getSubscriberMock();

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getSubscriber')->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('getSubscriberSource')->willReturn($subscriberSource);

        $subscriberMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeId)->willReturn(true);

        $subscriberMock->expects($this->once())->method('getStatus')
            ->willReturn(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);

        $observerMock->expects($this->once())->method('isSqualomailSave')->with($subscriberSource)->willReturn(false);

        $subscriberMock->expects($this->once())->method('getIsStatusChanged')->willReturn(true);

        $helperMock->expects($this->once())->method('isSubscriptionConfirmationEnabled')->with($storeId)
            ->willReturn(true);

        $helperMock->expects($this->once())->method('isUseMagentoEmailsEnabled')->with($storeId)->willReturn(false);

        $subscriberMock->expects($this->once())->method('setStatus')
            ->with(Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE);

        $observerMock->expects($this->once())->method('addSuccessIfRequired')->with($helperMock);

        $subscriberMock->expects($this->exactly($getStoreId))->method('getStoreId')->willReturn($storeId);

        $observerMock->subscriberSaveBefore($eventObserverMock);
    }

    public function subscriberSaveBeforeDataProvider()
    {
        return array(
            array(array('magentoMail' => 0, 'subscriberUpdatedAmount' => 1, 'getStoreId' => 1)),
            array(array('magentoMail' => 1, 'subscriberUpdatedAmount' => 0, 'getStoreId' => 1))
        );
    }

    public function testSubscriberSaveAfterUseMagentoEmail()
    {
        $storeViewId = 1;
        $params = array();
        $subscriberSource = null;

        $eventObserverMock = $this->getObserverMock();
        $observerMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $eventMock = $this->getEventObserverMock();

        $subscriberMock = $this->getSubscriberMock();

        $requestMock = $this->getRequestMock();

        $apiSubscriberMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getSubscriber')->willReturn($subscriberMock);

        $observerMock->expects($this->once())->method('getStoreViewIdBySubscriber')
            ->with($subscriberMock)->willReturn($storeViewId);

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $subscriberMock->expects($this->once())->method('getSubscriberSource')->willReturn($subscriberSource);

        $observerMock->expects($this->once())->method('isSqualomailSave')->with($subscriberSource)->willReturn(false);

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeViewId)->willReturn(true);

        $observerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParams')->willReturn($params);

        $helperMock->expects($this->once())->method('saveInterestGroupData')
            ->with($params, $storeViewId, null, $subscriberMock);

        $observerMock->expects($this->once())->method('isEmailConfirmationRequired')
            ->with($subscriberSource)->willReturn(false);

        $observerMock->expects($this->once())->method('createEmailCookie')->with($subscriberMock);

        $helperMock->expects($this->once())->method('isUseMagentoEmailsEnabled')->with($storeViewId)->willReturn(1);

        $observerMock->expects($this->once())->method('makeApiSubscriber')->willReturn($apiSubscriberMock);

        $observerMock->subscriberSaveAfter($eventObserverMock);
    }

    public function testSubscriberSaveAfterEmailConfirmation()
    {
        $storeViewId = 1;
        $params = array();
        $subscriberSource = Ebizmarts_SqualoMail_Model_Subscriber::SUBSCRIBE_CONFIRMATION;

        $eventObserverMock = $this->getObserverMock();

        $observerMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $eventMock = $this->getEventObserverMock();

        $subscriberMock = $this->getSubscriberMock();

        $requestMock = $this->getRequestMock();

        $apiSubscriberMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Subscribers::class)
            ->disableOriginalConstructor()
            ->setMethods(array('updateSubscriber'))
            ->getMock();

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $eventMock->expects($this->once())->method('getSubscriber')->willReturn($subscriberMock);

        $observerMock->expects($this->once())->method('getStoreViewIdBySubscriber')
            ->with($subscriberMock)->willReturn($storeViewId);

        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $subscriberMock->expects($this->once())->method('getSubscriberSource')->willReturn($subscriberSource);

        $observerMock->expects($this->once())->method('isSqualomailSave')->with($subscriberSource)->willReturn(false);

        $helperMock->expects($this->once())->method('isSubscriptionEnabled')->with($storeViewId)->willReturn(true);

        $observerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParams')->willReturn($params);

        $helperMock->expects($this->once())->method('saveInterestGroupData')
            ->with($params, $storeViewId, null, $subscriberMock);

        $observerMock->expects($this->once())->method('isMagentoSubscription')
            ->with($subscriberSource)->willReturn(false);

        $observerMock->expects($this->once())->method('isEmailConfirmationRequired')
            ->with($subscriberSource)->willReturn(true);

        $observerMock->expects($this->once())->method('createEmailCookie')->with($subscriberMock);

        $helperMock->expects($this->once())->method('isUseMagentoEmailsEnabled')->with($storeViewId)->willReturn(0);

        $subscriberMock->expects($this->once())->method('getIsStatusChanged')->willReturn(true);

        $observerMock->expects($this->once())->method('makeApiSubscriber')->willReturn($apiSubscriberMock);

        $apiSubscriberMock->expects($this->once())->method('updateSubscriber')->with($subscriberMock, true);

        $observerMock->subscriberSaveAfter($eventObserverMock);
    }

    public function testLoadCustomerToQuoteOnCheckout()
    {

        $storeId = 1;
        $ecomSyncEnabled = 1;
        $abandonedCartEnabled = 1;
        $isLoggedIn = 0;
        $actionName = null;
        $emailCookie = 'keller%2Bpopup%40ebizmarts.com';
        $customerEmail = 'customer@ebizmarts.com';
        $email = 'keller@ebizmarts.com';
        $campaignId = 'gf45f4gg';
        $landingCookie = 'http%3A//127.0.0.1/MASTER1939m4m/%3Fsqm_cid%3Dgf45f4gg%26sqm_eid%3D7dgasydg';

        $eventObserverMock = $this->getObserverMock();

        $eventMock = $this->getEventObserverMock();

        $observerMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $quoteMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getStoreId', 'getCustomerEmail', 'setCustomerEmail',
                    'setSqualomailCampaignId', 'setSqualomailLandingPage'
                )
            )
            ->getMock();

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $eventMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);
        $quoteMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);
        $helperMock
            ->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn($ecomSyncEnabled);
        $helperMock
            ->expects($this->once())
            ->method('isAbandonedCartEnabled')
            ->with($storeId)
            ->willReturn($abandonedCartEnabled);
        $observerMock->expects($this->once())->method('isCustomerLoggedIn')->willReturn($isLoggedIn);
        $observerMock->expects($this->once())->method('getRequestActionName')->willReturn($actionName);
        $observerMock->expects($this->once())->method('getEmailCookie')->willReturn($emailCookie);
        $observerMock->expects($this->any())->method('getEmailFromPopUp')->with($emailCookie)->willReturn($email);
        $quoteMock->expects($this->once())->method('getCustomerEmail')->willReturn($customerEmail);
        $quoteMock->expects($this->once())->method('setCustomerEmail')->with($email);
        $observerMock->expects($this->once())->method('_getCampaignCookie')->willReturn($campaignId);
        $quoteMock->expects($this->once())->method('setSqualomailCampaignId')->with($campaignId);
        $observerMock->expects($this->once())->method('_getLandingCookie')->willReturn($landingCookie);
        $quoteMock->expects($this->once())->method('setSqualomailLandingPage')->with($landingCookie);

        $observerMock->loadCustomerToQuote($eventObserverMock);
    }

    /**
     * @param array $cookieData
     * @dataProvider loadCustomerToQuoteDataProvider
     */
    public function testLoadCustomerToQuote($cookieData)
    {

        $storeId = 1;
        $ecomSyncEnabled = 1;
        $abandonedCartEnabled = 1;
        $isLoggedIn = 0;
        $actionName = $cookieData['actionName'];
        $emailCookie = 'keller%2Bpopup%40ebizmarts.com';
        $customerEmail = 'customer@ebizmarts.com';
        $email = 'keller@ebizmarts.com';
        $campaignId = 'gf45f4gg';
        $landingCookie = 'http%3A//127.0.0.1/MASTER1939m4m/%3Fsqm_cid%3Dgf45f4gg%26sqm_eid%3D7dgasydg';

        $eventObserverMock = $this->getObserverMock();

        $eventMock = $this->getEventObserverMock();

        $observerMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $quoteMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getStoreId', 'getCustomerEmail', 'setCustomerEmail',
                    'setSqualomailCampaignId', 'setSqualomailLandingPage'
                )
            )
            ->getMock();

        $eventObserverMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $eventMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);
        $quoteMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $observerMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);
        $helperMock
            ->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn($ecomSyncEnabled);
        $helperMock
            ->expects($this->once())
            ->method('isAbandonedCartEnabled')
            ->with($storeId)
            ->willReturn($abandonedCartEnabled);
        $observerMock->expects($this->once())->method('isCustomerLoggedIn')->willReturn($isLoggedIn);
        $observerMock->expects($this->once())->method('getRequestActionName')->willReturn($actionName);
        $observerMock->expects($this->once())->method('getEmailCookie')->willReturn($emailCookie);
        $observerMock->expects($this->any())->method('getEmailFromPopUp')->with($emailCookie)->willReturn($email);
        $quoteMock->expects($this->once())->method('getCustomerEmail')->willReturn($customerEmail);
        $quoteMock->expects($this->never())->method('setCustomerEmail')->with($email);
        $observerMock->expects($this->once())->method('_getCampaignCookie')->willReturn($campaignId);
        $quoteMock->expects($this->once())->method('setSqualomailCampaignId')->with($campaignId);
        $observerMock->expects($this->once())->method('_getLandingCookie')->willReturn($landingCookie);
        $quoteMock->expects($this->once())->method('setSqualomailLandingPage')->with($landingCookie);

        $observerMock->loadCustomerToQuote($eventObserverMock);
    }

    public function loadCustomerToQuoteDataProvider()
    {

        return array(
            array(array('actionName' => 'saveOrder')),
            array(array('actionName' => 'savePayment')),
            array(array('actionName' => 'saveShippingMethod')),
            array(array('actionName' => 'saveBilling'))
        );
    }

    public function testItemCancel()
    {
        $isBundle = false;
        $isConf = false;
        $isEcomEnabled = 1;
        $storeId = 1;
        $productId = 1;
        $squalomailStoreId = '6167259961c475fef8523e39ef1784e8';
        $isMarkedAsDeleted = 0;
        $type = Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT;

        $observerMock = $this->getObserverMock();

        $eventObserverMock = $this->getEventObserverMock();

        $squalomailObserverMock = $this->getSqualomailObserverMock();

        $itemMock = $this->getOrderItemMock();

        $helperMock = $this->getHelperMock();

        $apiProductsMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Products::class)
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();

        $dataProductMock = $this->getEcommerceModelMock();

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventObserverMock);

        $eventObserverMock->expects($this->once())->method('getItem')->willReturn($itemMock);

        $squalomailObserverMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $itemMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $helperMock
            ->expects($this->once())
            ->method('isEcomSyncDataEnabled')
            ->with($storeId)
            ->willReturn($isEcomEnabled);

        $squalomailObserverMock->expects($this->once())->method('makeApiProduct')->willReturn($apiProductsMock);
        $squalomailObserverMock->expects($this->once())->method('isBundleItem')->with($itemMock)->willReturn($isBundle);
        $squalomailObserverMock
            ->expects($this->once())
            ->method('isConfigurableItem')
            ->with($itemMock)
            ->willReturn($isConf);

        $itemMock->expects($this->once())->method('getProductId')->willReturn($productId);

        $helperMock->expects($this->once())->method('getSQMStoreId')->with($storeId)->willReturn($squalomailStoreId);

        $apiProductsMock->expects($this->once())->method('update')->with($productId);

        $squalomailObserverMock->expects($this->once())
            ->method('getSqualomailEcommerceSyncDataModel')
            ->willReturn($dataProductMock);

        $dataProductMock
            ->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->with($productId, $type, $squalomailStoreId)
            ->willReturn($dataProductMock);
        $dataProductMock->expects($this->once())->method('getSqualomailSyncDeleted')->willReturn($isMarkedAsDeleted);

        $squalomailObserverMock->itemCancel($observerMock);
    }

    public function testHandleCustomerGroupsIsSubscribed()
    {
        $params = array();
        $storeId = 1;
        $customerId = 15;
        $subscriberEmail = 'luciaines+testHandelCustomerGroups@ebizmarts.com';

        $squalomailObserverMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $subbscriberModelMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Subscriber::class)
            ->setMethods(array('loadByEmail'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Subscriber::class)
            ->setMethods(array('getId'))
            ->getMock();

        $squalomailObserverMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);
        $squalomailObserverMock->expects($this->once())->method('getSubscriberModel')->willReturn($subbscriberModelMock);

        $helperMock
            ->expects($this->once())
            ->method('saveInterestGroupData')
            ->with($params, $storeId, $customerId, $subscriberMock);

        $subbscriberModelMock
            ->expects($this->once())
            ->method('loadByEmail')
            ->with($subscriberEmail)
            ->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('getId')->willReturn(true);

        $squalomailObserverMock->handleCustomerGroups($subscriberEmail, $params, $storeId, $customerId);
    }

    public function testHandleCustomerGroupsIsNotSubcribedFromAdmin()
    {
        $customerId = 15;
        $subscriberEmail = 'luciaines+testHandelCustomerGroups@ebizmarts.com';
        $params = array(
            'form_key' => 'Pm5pxh17N9Z9AINN',
            'customer_id' => $customerId,
            'group' => array(
                'e939299a7d' => 'e939299a7d',
                '3dd23446e4' => '3dd23446e4',
                'a6c3c332bf' => 'a6c3c332bf'
            )
        );
        $storeId = 1;
        $groups = array('d46296f47c' => array('3dd23446e4' => '3dd23446e4', 'a6c3c332bf' => 'a6c3c332bf'));

        $squalomailObserverMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $subbscriberModelMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Subscriber::class)
            ->setMethods(array('loadByEmail'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Subscriber::class)
            ->setMethods(array('getId'))
            ->getMock();

        $squalomailObserverMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);
        $squalomailObserverMock->expects($this->once())->method('getSubscriberModel')->willReturn($subbscriberModelMock);
        $squalomailObserverMock->expects($this->once())->method('getWarningMessageAdminHtmlSession')->with($helperMock);

        $helperMock->expects($this->once())->method('saveInterestGroupData')->with($params, $storeId, $customerId);
        $helperMock->expects($this->once())->method('getInterestGroupsIfAvailable')->with($params)->willReturn($groups);

        $subbscriberModelMock
            ->expects($this->once())
            ->method('loadByEmail')
            ->with($subscriberEmail)
            ->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('getId')->willReturn(false);

        $squalomailObserverMock->handleCustomerGroups($subscriberEmail, $params, $storeId, $customerId);
    }

    public function testHandleCustomerGroupsIsNotSubcribedFromFrontEnd()
    {
        $params = array();
        $storeId = 1;
        $customerId = 15;
        $subscriberEmail = 'luciaines+testHandelCustomerGroups@ebizmarts.com';

        $squalomailObserverMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $subbscriberModelMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Subscriber::class)
            ->setMethods(array('loadByEmail'))
            ->getMock();

        $subscriberMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Subscriber::class)
            ->setMethods(array('getId'))
            ->getMock();

        $squalomailObserverMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);
        $squalomailObserverMock->expects($this->once())->method('getSubscriberModel')->willReturn($subbscriberModelMock);

        $helperMock->expects($this->once())->method('saveInterestGroupData')->with($params, $storeId, $customerId);

        $subbscriberModelMock
            ->expects($this->once())
            ->method('loadByEmail')
            ->with($subscriberEmail)
            ->willReturn($subscriberMock);

        $subscriberMock->expects($this->once())->method('getId')->willReturn(false);

        $squalomailObserverMock->handleCustomerGroups($subscriberEmail, $params, $storeId, $customerId);
    }

    public function testAddOrderViewMonkey()
    {
        $html = '';
        $storeId = 1;

        $squalomailObserverMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $observerMock = $this->getObserverMock();

        $blockMock = $this->getMockBuilder(Mage_Core_Block_Abstract::class)
            ->setMethods(array('getNameInLayout', 'getOrder', 'getChild'))
            ->getMock();

        $transportMock = $this->getEventObserverMock();

        $orderMock = $this->getOrderMock();

        $childMock = $this->getMockBuilder(Mage_Core_Helper_String::class)
            ->setMethods(array('toHtml'))
            ->getMock();

        $squalomailObserverMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn(true);

        $observerMock->expects($this->once())->method('getBlock')->willReturn($blockMock);
        $observerMock->expects($this->once())->method('getTransport')->willReturn($transportMock);

        $blockMock->expects($this->once())->method('getNameInLayout')->willReturn('order_info');
        $blockMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $blockMock
            ->expects($this->once())
            ->method('getChild')
            ->with('squalomail.order.info.monkey.block')
            ->willReturn($childMock);

        $transportMock->expects($this->once())->method('getHtml')->willReturn($html);
        $transportMock->expects($this->once())->method('setHtml')->with($html);

        $orderMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $childMock->expects($this->once())->method('toHtml')->willReturn($html);

        $squalomailObserverMock->addOrderViewMonkey($observerMock);
    }

    public function testCleanProductImagesCacheAfter()
    {
        $message = 'Image cache has been flushed please resend the products in order to update image URL.';
        $configValues = array(array(Ebizmarts_SqualoMail_Model_Config::PRODUCT_IMAGE_CACHE_FLUSH, 1));
        $default = 'default';

        $squalomailObserverMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $observerMock = $this->getObserverMock();


        $squalomailObserverMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('saveSqualomailConfig')->with($configValues, 0, $default);
        $helperMock->expects($this->once())->method('addAdminWarning')->with($message);

        $squalomailObserverMock->cleanProductImagesCacheAfter($observerMock);
    }

    public function testSaveConfigBeforeInheritList()
    {
        $apiKey = 'q1w2e3r4t5y6u7i8o9p0-us1';
        $squalomailStoreId = 'z1x2c3v4b5n6m7i8o9p0';
        $listId = 'a1s2d3f4g5';
        $dataArray = array('groups' => array('general' => array('fields' => array(
            'list' => array('inherit' => true),
            'storeid' => array('value' => $squalomailStoreId),
            'apikey' => array('value' => $apiKey)
        ))));
        $dataArrayModified = array('groups' => array('general' => array('fields' => array(
            'list' => array('value' => $listId),
            'storeid' => array('value' => $squalomailStoreId),
            'apikey' => array('value' => $apiKey)
        ))));
        $scopeArray = array('scope_id' => '1', 'scope' => 'stores');
        $oldSqualomailStoreId = 'a1s2d3f4g5h6j7k8l9p0';
        $storeListId = 'g5f4d3s2a1';
        $message = 'The audience configuration was automatically modified to show the audience associated '
            . 'to the selected Squalomail store.';

        $squalomailObserverMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $observerMock = $this->getObserverMock();

        $configMock = $this->getMockBuilder(Mage_Adminhtml_Model_Config::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSection', 'getData', 'setData'))
            ->getMock();

        $adminSessionMock = $this->getMockBuilder(Mage_Adminhtml_Model_Session::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addError'))
            ->getMock();


        $observerMock->expects($this->once())->method('getObject')->willReturn($configMock);

        $configMock->expects($this->once())->method('getSection')->willReturn('squalomail');
        $configMock->expects($this->once())->method('getData')->willReturn($dataArray);

        $squalomailObserverMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getCurrentScope')->willReturn($scopeArray);
        $helperMock
            ->expects($this->once())
            ->method('getSQMStoreId')
            ->with($scopeArray['scope_id'], $scopeArray['scope'])
            ->willReturn($oldSqualomailStoreId);

        $squalomailObserverMock
            ->expects($this->once())
            ->method('isListXorStoreInherited')
            ->with($dataArray)
            ->willReturn(true);

        $helperMock
            ->expects($this->once())
            ->method('getListIdByApiKeyAndSQMStoreId')
            ->with($apiKey, $squalomailStoreId)
            ->willReturn($storeListId);
        $helperMock
            ->expects($this->once())
            ->method('getGeneralList')
            ->with($scopeArray['scope_id'], $scopeArray['scope'])
            ->willReturn($listId);

        $squalomailObserverMock->expects($this->once())->method('getAdminSession')->willReturn($adminSessionMock);

        $adminSessionMock->expects($this->once())->method('addError')->with($message);

        $configMock->expects($this->once())->method('setData')->with($dataArrayModified);

        $squalomailObserverMock->saveConfigBefore($observerMock);
    }

    public function testSaveConfigBeforeInheritStore()
    {
        $apiKey = 'q1w2e3r4t5y6u7i8o9p0-us1';
        $listId = 'a1s2d3f4g5';
        $oldSqualomailStoreId = 'a1s2d3f4g5h6j7k8l9p0';
        $dataArray = array('groups' => array('general' => array('fields' => array(
            'list' => array('value' => $listId),
            'storeid' => array('inherit' => true),
            'apikey' => array('value' => $apiKey)
        ))));
        $dataArrayModified = array('groups' => array('general' => array('fields' => array(
            'list' => array('value' => $listId),
            'storeid' => array('value' => $oldSqualomailStoreId),
            'apikey' => array('value' => $apiKey)
        ))));
        $scopeArray = array('scope_id' => '1', 'scope' => 'stores');
        $message = 'The Squalomail store configuration was not modified. There is a Squalomail audience configured '
            . 'for this scope. Both must be set to inherit at the same time.';

        $squalomailObserverMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $observerMock = $this->getObserverMock();

        $configMock = $this->getMockBuilder(Mage_Adminhtml_Model_Config::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSection', 'getData', 'setData'))
            ->getMock();

        $adminSessionMock = $this->getMockBuilder(Mage_Adminhtml_Model_Session::class)
            ->disableOriginalConstructor()
            ->setMethods(array('addError'))
            ->getMock();


        $observerMock->expects($this->once())->method('getObject')->willReturn($configMock);

        $configMock->expects($this->once())->method('getSection')->willReturn('squalomail');
        $configMock->expects($this->once())->method('getData')->willReturn($dataArray);

        $squalomailObserverMock->expects($this->once())->method('makeHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getCurrentScope')->willReturn($scopeArray);
        $helperMock
            ->expects($this->once())
            ->method('getSQMStoreId')
            ->with($scopeArray['scope_id'], $scopeArray['scope'])
            ->willReturn($oldSqualomailStoreId);

        $squalomailObserverMock
            ->expects($this->once())
            ->method('isListXorStoreInherited')
            ->with($dataArray)
            ->willReturn(true);

        $squalomailObserverMock->expects($this->once())->method('getAdminSession')->willReturn($adminSessionMock);

        $adminSessionMock->expects($this->once())->method('addError')->with($message);

        $configMock->expects($this->once())->method('setData')->with($dataArrayModified);

        $squalomailObserverMock->saveConfigBefore($observerMock);
    }

    public function testProductSaveAfter()
    {
        $productId = 907;
        $type = Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT;
        $squalomailStoreId = '19d457ff95f1f1e710b502f35041e05f';
        $ecommEnabled = true;
        $storeId = 1;
        $isMarkedAsDeleted = 1;
        $status = array($productId => 1);

        $squalomailObserverMock = $this->getSqualomailObserverMock();

        $helperMock = $this->getHelperMock();

        $productMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Products::class)
            ->setMethods(array('getId'))
            ->getMock();

        $productApiMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Products::class)
            ->setMethods(array())
            ->getMock();

        $mageCoreModelAppMock = $this->getMageAppMock();

        $dataProductMock = $this->getEcommerceModelMock();

        $productStatusMock = $this->getMockBuilder(Mage_Catalog_Model_Resource_Product_Status::class)
            ->setMethods(array('getProductStatus'))
            ->getMock();

        $observerMock = $this->getObserverMock();

        $storeMock = $this->getMockBuilder(ArrayObject::class)
            ->setMethods(array('getIterator'))
            ->getMock();

        $eventObserverMock = $this->getEventObserverMock();

        $squalomailObserverMock->expects($this->once())
            ->method('makeHelper')
            ->willReturn($helperMock);
        $squalomailObserverMock->expects($this->once())
            ->method('makeApiProduct')
            ->willReturn($productApiMock);
        $squalomailObserverMock->expects($this->once())
            ->method('getCatalogProductStatusModel')
            ->willReturn($productStatusMock);

        $observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventObserverMock);

        $eventObserverMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $productStatusMock->expects($this->once())
            ->method('getProductStatus')
            ->with($productId, $storeId)
            ->willReturn($status);

        $helperMock->expects($this->once())
            ->method('getMageApp')
            ->willReturn($mageCoreModelAppMock);
        $helperMock->expects($this->once())
            ->method('isEcommerceEnabled')
            ->willReturn($ecommEnabled);

        $squalomailObserverMock->expects($this->once())
            ->method('getSqualomailEcommerceSyncDataModel')
            ->willReturn($dataProductMock);

        $dataProductMock->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->with($productId, $type, $squalomailStoreId)
            ->willReturn($dataProductMock);

        $helperMock->expects($this->once())
            ->method('getSQMStoreId')
            ->with($storeId)
            ->willReturn($squalomailStoreId);

        $dataProductMock->expects($this->once())
            ->method('getSqualomailSyncDeleted')
            ->willReturn($isMarkedAsDeleted);
        $dataProductMock->expects($this->once())
            ->method('delete');

        $storeArray = array($storeId => $storeMock);
        $mageCoreModelAppMock->expects($this->once())
            ->method('getStores')
            ->willReturn($storeArray);

        $productMock->expects($this->exactly(3))
            ->method('getId')
            ->willReturnOnConsecutiveCalls(
                $productId,
                $productId,
                $productId
            );

        $squalomailObserverMock->productSaveAfter($observerMock);
    }

    public function testNewCreditMemo()
    {
        $isBundle = false;
        $isConf = false;
        $storeId = 1;
        $orderId = 10;
        $ecomEnabled = true;
        $squalomailStoreId = '19d457ff95f1f1e710b502f35041e05f';
        $isMarkedAsDeleted = 0;
        $type = Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT;
        $productId = 910;

        $squalomailObserverMock = $this->getSqualomailObserverMock();

        $observerMock = $this->getObserverMock();

        $eventObserverMock = $this->getEventObserverMock();

        $creditMemoMock = $this->getMockBuilder(Mage_Sales_Model_Order_Creditmemo::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getOrder', 'getAllItems'))
            ->getMock();

        $orderMock = $this->getOrderMock();

        $helperMock = $this->getHelperMock();

        $productApiMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Products::class)
            ->setMethods(array('update'))
            ->getMock();

        $orderApiMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Products::class)
            ->setMethods(array('update'))
            ->getMock();

        $itemMock = $this->getOrderItemMock();

        $dataProductMock = $this->getEcommerceModelMock();

        $squalomailObserverMock->expects($this->once())
            ->method('makeHelper')
            ->willReturn($helperMock);
        $squalomailObserverMock->expects($this->once())
            ->method('makeApiProduct')
            ->willReturn($productApiMock);
        $squalomailObserverMock->expects($this->once())
            ->method('makeApiOrder')
            ->willReturn($orderApiMock);
        $squalomailObserverMock->expects($this->once())
            ->method('isBundleItem')
            ->with($itemMock)
            ->willReturn($isBundle);
        $squalomailObserverMock->expects($this->once())
            ->method('isConfigurableItem')
            ->with($itemMock)
            ->willReturn($isConf);

        $observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventObserverMock);

        $eventObserverMock->expects($this->once())
            ->method('getCreditmemo')
            ->willReturn($creditMemoMock);

        $creditMemoMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);
        $creditMemoMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn(array($itemMock));

        $orderMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);
        $orderMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn($orderId);

        $helperMock->expects($this->once())
            ->method('isEcomSyncDataEnabled')
            ->with($storeId)
            ->willReturn($ecomEnabled);
        $helperMock->expects($this->once())
            ->method('getSQMStoreId')
            ->with($storeId)
            ->willReturn($squalomailStoreId);

        $squalomailObserverMock->expects($this->once())
            ->method('getSqualomailEcommerceSyncDataModel')
            ->willReturn($dataProductMock);

        $dataProductMock->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->with($productId, $type, $squalomailStoreId)
            ->willReturn($dataProductMock);

        $productApiMock->expects($this->once())
            ->method('update')
            ->with($productId);

        $orderApiMock->expects($this->once())
            ->method('update')
            ->with($orderId, $storeId);

        $itemMock->expects($this->once())
            ->method('getProductId')
            ->willReturn($productId);

        $dataProductMock->expects($this->once())
            ->method('getSqualomailSyncDeleted')
            ->willReturn($isMarkedAsDeleted);

        $squalomailObserverMock->newCreditMemo($observerMock);
    }

    public function testCreateCreditmemoUbsubscribe()
    {
        $customerEmail = 'customer@squalomail.com';
        $squalomailUnsubscribe = 'on';

        $squalomailObserverMock = $this->getSqualomailObserverMock();
        $observerMock = $this->getObserverMock();
        $eventObserverMock = $this->getEventObserverMock();
        $creditMemoMock = $this->getCreditMemoMock();
        $helperMock = $this->getHelperMock();
        $requestMock = $this->getRequestMock();

        $orderMock = $this->getOrderMock();
        $subscriberMock = $this->getSubscriberMock();

        $observerMock
            ->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventObserverMock);
        $eventObserverMock
            ->expects($this->once())
            ->method('getCreditmemo')
            ->willReturn($creditMemoMock);
        $creditMemoMock
            ->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);
        $squalomailObserverMock
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($requestMock);
        $squalomailObserverMock
            ->expects($this->once())
            ->method('makeHelper')
            ->willReturn($helperMock);
        $requestMock
            ->expects($this->once())
            ->method('getParam')
            ->with('squalomail_unsubscribe')
            ->willReturn($squalomailUnsubscribe);

        // Inside squalomailUnsubscribe if:
        $orderMock->expects($this->once())->method('getCustomerEmail')->willReturn($customerEmail);
        $squalomailObserverMock
            ->expects($this->once())
            ->method('getSubscriberModel')
            ->willReturn($subscriberMock);
        $subscriberMock
            ->expects($this->once())
            ->method('loadByEmail')
            ->with($customerEmail)
            ->willReturnSelf();
        $helperMock->expects($this->once())
            ->method('unsubscribeMember')
            ->with($subscriberMock)
            ->willReturnSelf();

        $squalomailObserverMock->createCreditmemo($observerMock);
    }

    public function testCreateCreditmemo()
    {
        $squalomailUnsubscribe = '';

        $squalomailObserverMock = $this->getSqualomailObserverMock();
        $observerMock = $this->getObserverMock();
        $eventObserverMock = $this->getEventObserverMock();
        $creditMemoMock = $this->getCreditMemoMock();
        $helperMock = $this->getHelperMock();
        $requestMock = $this->getRequestMock();
        $orderMock = $this->getOrderMock();
        $subscriberMock = $this->getSubscriberMock();

        $observerMock
            ->expects($this->never())
            ->method('getEvent')
            ->willReturn($eventObserverMock);
        $eventObserverMock
            ->expects($this->never())
            ->method('getCreditmemo')
            ->willReturn($creditMemoMock);
        $creditMemoMock
            ->expects($this->never())
            ->method('getOrder')
            ->willReturn($orderMock);
        $squalomailObserverMock
            ->expects($this->never())
            ->method('makeHelper')
            ->willReturn($helperMock);

        $squalomailObserverMock
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($requestMock);
        $requestMock
            ->expects($this->once())
            ->method('getParam')
            ->with('squalomail_unsubscribe')
            ->willReturn($squalomailUnsubscribe);

        $squalomailObserverMock->createCreditmemo($observerMock);
    }

    public function testCancelCreditMemo()
    {
        $isBundle = false;
        $isConf = false;
        $storeId = 1;
        $orderId = 10;
        $ecomEnabled = true;
        $squalomailStoreId = '19d457ff95f1f1e710b502f35041e05f';
        $isMarkedAsDeleted = 0;
        $type = Ebizmarts_SqualoMail_Model_Config::IS_PRODUCT;
        $productId = 910;

        $squalomailObserverMock = $this->getSqualomailObserverMock();

        $observerMock = $this->getObserverMock();

        $eventObserverMock = $this->getEventObserverMock();

        $creditMemoMock = $this->getMockBuilder(Mage_Sales_Model_Order_Creditmemo::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getOrder', 'getAllItems'))
            ->getMock();

        $orderMock = $this->getOrderMock();

        $helperMock = $this->getHelperMock();

        $productApiMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Products::class)
            ->setMethods(array('update'))
            ->getMock();

        $orderApiMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Products::class)
            ->setMethods(array('update'))
            ->getMock();

        $itemMock = $this->getMockBuilder(Mage_Sales_Model_Order_Item::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getProductId'))
            ->getMock();

        $dataProductMock = $this->getEcommerceModelMock();

        $squalomailObserverMock->expects($this->once())
            ->method('makeHelper')
            ->willReturn($helperMock);
        $squalomailObserverMock->expects($this->once())
            ->method('makeApiProduct')
            ->willReturn($productApiMock);
        $squalomailObserverMock->expects($this->once())
            ->method('makeApiOrder')
            ->willReturn($orderApiMock);
        $squalomailObserverMock->expects($this->once())
            ->method('isBundleItem')
            ->with($itemMock)
            ->willReturn($isBundle);
        $squalomailObserverMock->expects($this->once())
            ->method('isConfigurableItem')
            ->with($itemMock)
            ->willReturn($isConf);

        $observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventObserverMock);

        $eventObserverMock->expects($this->once())
            ->method('getCreditmemo')
            ->willReturn($creditMemoMock);

        $creditMemoMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);
        $creditMemoMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn(array($itemMock));

        $orderMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);
        $orderMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn($orderId);

        $helperMock->expects($this->once())
            ->method('isEcomSyncDataEnabled')
            ->with($storeId)
            ->willReturn($ecomEnabled);
        $helperMock->expects($this->once())
            ->method('getSQMStoreId')
            ->with($storeId)
            ->willReturn($squalomailStoreId);

        $squalomailObserverMock->expects($this->once())
            ->method('getSqualomailEcommerceSyncDataModel')
            ->willReturn($dataProductMock);

        $dataProductMock->expects($this->once())
            ->method('getEcommerceSyncDataItem')
            ->with($productId, $type, $squalomailStoreId)
            ->willReturn($dataProductMock);

        $productApiMock->expects($this->once())
            ->method('update')
            ->with($productId);

        $orderApiMock->expects($this->once())
            ->method('update')
            ->with($orderId, $storeId);

        $itemMock->expects($this->once())
            ->method('getProductId')
            ->willReturn($productId);

        $dataProductMock->expects($this->once())
            ->method('getSqualomailSyncDeleted')
            ->willReturn($isMarkedAsDeleted);

        $squalomailObserverMock->cancelCreditMemo($observerMock);
    }

    protected function getSqualomailObserverMock()
    {
        return $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getRequest', 'getSubscriberModel', 'makeHelper', 'makeApiProduct',
                    'removeCampaignData', 'getSqualomailEcommerceSyncDataModel', 'isBundleItem',
                    'isConfigurableItem', 'getRegistry', 'removeRegistry', 'getCoreResource',
                    'addSuccessIfRequired', 'isSqualomailSave', 'createEmailCookie', 'makeApiSubscriber',
                    'getStoreViewIdBySubscriber', 'isEmailConfirmationRequired', 'isMagentoSubscription',
                    'isCustomerLoggedIn', 'getRequestActionName', 'getEmailFromPopUp', 'getEmailCookie',
                    '_getCampaignCookie', '_getLandingCookie', 'getWarningMessageAdminHtmlSession',
                    'getAdminSession', 'isListXorStoreInherited', 'getCatalogProductStatusModel',
                    'makeApiOrder', 'makeApiCustomer', 'getCustomerModel'
                )
            )
            ->getMock();
    }

    protected function getObserverMock()
    {
        return $this->getMockBuilder(Varien_Event_Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getEvent', 'getOrderGridCollection', 'getBlock', 'getTransport',
                    'getObject'
                )
            )
            ->getMock();
    }

    protected function getEventObserverMock()
    {
        return $this->_eventObserverMock = $this->getMockBuilder(Varien_Event::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                        'getCreditmemo', 'getOrder', 'getSubscriber', 'getQuote', 'getItem',
                        'getHtml', 'setHtml', 'getProduct', 'getProductIds', 'getCustomer',
                        'getCustomerAddress'
                )
            )
            ->getMock();
    }

    protected function getCreditMemoMock()
    {
        return $this->_creditMemoMock = $this->getMockBuilder(Mage_Sales_Model_Order_Creditmemo::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getOrder', 'getAllItems'))
            ->getMock();
    }

    protected function getOrderMock()
    {
        return $this->_orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getCustomerEmail', 'getStoreId', 'getCustomerFirstname',
                    'getCustomerLastname', 'getAllItems', 'getEntityId', 'setStoreId',
                    "setSqualomailCampaignId", "getSqualomailLandingPage", "setSqualomailLandingPage"
                )
            )
            ->getMock();
    }

    protected function getRequestMock()
    {
        return $this->_requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam', 'getPost', 'getParams'))
            ->getMock();
    }

    protected function getSubscriberMock()
    {
        return $this->_subscriberMock = $this->getMockBuilder(Mage_Newsletter_Model_Subscriber::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'loadByEmail', 'getCustomerId', 'setSubscriberFirstname', 'setSubscriberLastname',
                    'subscribe', 'getSubscriberSource', 'getIsStatusChanged', 'getStatus', 'setStatus',
                    'getStoreId', 'getId', 'loadByCustomer', 'setSubscriberEmail', 'save'
                )
            )
            ->getMock();
    }

    protected function getHelperMock()
    {
        return $this->_helperMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'unsubscribeMember', 'getMageApp', 'isEcomSyncDataEnabled', 'isSubscriptionEnabled',
                    'loadListSubscriber', 'saveEcommerceSyncData', 'getSQMStoreId', 'getMonkeyInGrid',
                    'isEcomSyncDataEnabledInAnyScope','getAllSqualoMailStoreIds', 'isEcommerceEnabled',
                    'isSubscriptionConfirmationEnabled', 'getStoreId', 'isUseMagentoEmailsEnabled',
                    'saveInterestGroupData', 'isAbandonedCartEnabled', 'getInterestGroupsIfAvailable',
                    'saveSqualomailConfig', 'addAdminWarning', 'getCurrentScope', 'getIfConfigExistsForScope',
                    'getGeneralList', 'getListIdByApiKeyAndSQMStoreId'
                )
            )
            ->getMock();
    }

    protected function getOrderItemMock()
    {
        return $this->getMockBuilder(Mage_Sales_Model_Order_Item::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getProductType', 'getProductId', 'getStoreId'))
            ->getMock();
    }

    protected function getMageAppMock()
    {
        return $this->getMockBuilder(Mage_Core_Model_App::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getStores'))
            ->getMock();
    }

    protected function getEcommerceModelMock()
    {
        return $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Ecommercesyncdata::class)
            ->setMethods(
                array(
                    'getSqualomailSyncDeleted', 'getEcommerceSyncDataItem', 'getSqualomailSyncModified',
                    'delete'
                )
            )
            ->getMock();
    }
}
