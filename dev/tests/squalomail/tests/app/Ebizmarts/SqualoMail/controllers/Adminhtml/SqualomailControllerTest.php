<?php

require_once BP . DS . 'app/code/community/Ebizmarts/MailChimp/controllers/Adminhtml/SqualomailController.php';

class Ebizmarts_MailChimp_Adminhtml_SqualomailControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ebizmarts_MailChimp_Adminhtml_SqualomailController $squalomailController
     */
    protected $_squalomailController;

    public function setUp()
    {
        Mage::app('default');
        $this->_squalomailController = $this->getMockBuilder(Ebizmarts_MailChimp_Adminhtml_SqualomailController::class);
    }

    public function tearDown()
    {
        $this->_squalomailController = null;
    }

    public function testIndexAction()
    {
        $customerId = 1;
        $type = 'squalomail/adminhtml_customer_edit_tab_squalomail';
        $name = 'admin.customer.squalomail';
        $result = '<body></body>';

        $squalomailControllerMock = $this->_squalomailController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getResponse', 'getLayout', 'getHtml'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setBody'))
            ->getMock();

        $layoutMock = $this->getMockBuilder(Mage_Core_Model_Layout::class)
            ->disableOriginalConstructor()
            ->setMethods(array('createBlock'))
            ->getMock();

        $blockMock = $this->getMockBuilder(Ebizmarts_MailChimp_Block_Adminhtml_Customer_Edit_Tab_Squalomail::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setCustomerId', 'setUseAjax', 'toHtml'))
            ->getMock();

        $squalomailControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParam')->with('id')->willReturn($customerId);

        $squalomailControllerMock->expects($this->once())->method('getLayout')->willReturn($layoutMock);

        $layoutMock->expects($this->once())->method('createBlock')->with($type, $name)->willReturn($blockMock);

        $blockMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $blockMock->expects($this->once())->method('setUseAjax')->with(true)->willReturnSelf();

        $squalomailControllerMock->expects($this->once())->method('getHtml')->with($blockMock)->willReturn($result);

        $squalomailControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);
        $responseMock->expects($this->once())->method('setBody')->with($result);

        $squalomailControllerMock->indexAction();
    }

    public function testResendSubscribersAction()
    {
        $paramScope = 'scope';
        $paramScopeId = 'scope_id';
        $scope = 'stores';
        $scopeId = 1;
        $result = 1;

        $squalomailControllerMock = $this->_squalomailController
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMageApp', 'resendSubscribers'))
            ->getMock();

        $mageAppMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getResponse'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setBody'))
            ->getMock();

        $squalomailControllerMock->expects($this->once())->method('getHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);

        $mageAppMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->exactly(2))->method('getParam')->withConsecutive(
            array($paramScope),
            array($paramScopeId)
        )
            ->willReturnOnConsecutiveCalls(
                $scope,
                $scopeId
            );

        $helperMock->expects($this->once())->method('resendSubscribers')->with($scopeId, $scope);
        $mageAppMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setBody')->with($result);

        $squalomailControllerMock->resendSubscribersAction();
    }

    public function testCreateWebhookAction()
    {
        $paramScope = 'scope';
        $paramScopeId = 'scope_id';
        $scope = 'stores';
        $scopeId = 1;
        $listId = 'ca841a1103';
        $message = 1;

        $squalomailControllerMock = $this->_squalomailController
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper', 'getWebhookHelper'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getMageApp', 'getGeneralList'))
            ->getMock();

        $webhookHelperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Webhook::class)
            ->disableOriginalConstructor()
            ->setMethods(array('createNewWebhook'))
            ->getMock();

        $mageAppMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getResponse'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setBody'))
            ->getMock();

        $squalomailControllerMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $squalomailControllerMock
            ->expects($this->once())
            ->method('getWebhookHelper')
            ->willReturn($webhookHelperMock);

        $helperMock->expects($this->once())->method('getGeneralList')->with($scopeId)->willReturn($listId);
        $helperMock->expects($this->once())->method('getMageApp')->willReturn($mageAppMock);

        $mageAppMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->exactly(2))->method('getParam')->withConsecutive(
            array($paramScope),
            array($paramScopeId)
        )
            ->willReturnOnConsecutiveCalls(
                $scope,
                $scopeId
            );

        $webhookHelperMock
            ->expects($this->once())
            ->method('createNewWebhook')
            ->with($scopeId, $scope, $listId)
            ->willReturn($message);
        $mageAppMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setBody')->with($message);

        $squalomailControllerMock->createWebhookAction();
    }

    public function testGetStoresAction()
    {
        $apiKeyParam = 'api_key';
        $apiKey = 'a1s2d3f4g5h6j7k8l9z1x2c3v4v4-us1';

        $data = array(
            array('id' => '', 'name' => '--- Select a Squalomail Store ---'),
            array('id' => 'a1s2d3f4g5h6j7k8l9p0', 'name' => 'Madison Island - English')
        );
        $jsonData = '[{"id":"","name":"--- Select a Squalomail Store ---"},'
            . '{"id":"a1s2d3f4g5h6j7k8l9p0","name":"Madison Island - English"}]';

        $squalomailControllerMock = $this->_squalomailController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getResponse', 'getSourceStoreOptions', 'getHelper'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setHeader', 'setBody'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isApiKeyObscure'))
            ->getMock();

        $squalomailControllerMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $squalomailControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParam')->with($apiKeyParam)->willReturn($apiKey);
        $helperMock->expects($this->once())->method('isApiKeyObscure')->with($apiKey)->willReturn(false);

        $squalomailControllerMock
            ->expects($this->once())
            ->method('getSourceStoreOptions')
            ->with($apiKey)
            ->willReturn($data);
        $squalomailControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setHeader')->with('Content-type', 'application/json');
        $responseMock->expects($this->once())->method('setBody')->with($jsonData);

        $squalomailControllerMock->getStoresAction();
    }

    public function testGetInfoAction()
    {
        $apiKeyParam = 'api_key';
        $apiKey = 'a1s2d3f4g5h6j7k8l9z1x2c3v4v4-us1';
        $storeIdParam = 'squalomail_store_id';
        $mcStoreId = 'q1w2e3r4t5y6u7i8o9p0';
        $syncDate = "2019-02-01 20:00:05";
        $optionSyncFlag = array(
            'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::SYNC_LABEL_KEY,
            'label' => 'Initial sync: ' . $syncDate
        );
        $liElement = "<li>Initial sync: <span style='color: forestgreen;font-weight: bold;'>$syncDate</span></li>";
        $liElementEscaped = "<li>Initial sync: <span style='color: forestgreen;font-weight: bold;'>"
            . "$syncDate<\/span><\/li>";
        $data = array(
            array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::USERNAME_KEY,
                'label' => 'Username: Ebizmarts Corp.'
            ), array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_ACCOUNT_SUB_KEY,
                'label' => 'Total Account Subscribers: 104'
            ), array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_LIST_SUB_KEY,
                'label' => 'Total List Subscribers: 18'
            ), array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::STORENAME_KEY,
                'label' => 'Ecommerce Data uploaded to MailChimp store Madison Island - English:'
            ),
            $optionSyncFlag,
            array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_CUS_KEY,
                'label' => '  Total Customers: 10'
            ), array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_PRO_KEY,
                'label' => '  Total Products: 10'
            ), array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_ORD_KEY,
                'label' => '  Total Orders: 10'
            ), array(
                'value' => Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_QUO_KEY,
                'label' => '  Total Carts: 10'
            )
        );
        $jsonData = '[{"value":' . Ebizmarts_MailChimp_Model_System_Config_Source_Account::USERNAME_KEY
            . ',"label":"Username: Ebizmarts Corp."},'
            . '{"value":' . Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_ACCOUNT_SUB_KEY
            . ',"label":"Total Account Subscribers: 104"},'
            . '{"value":' . Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_LIST_SUB_KEY
            . ',"label":"Total List Subscribers: 18"},'
            . '{"value":' . Ebizmarts_MailChimp_Model_System_Config_Source_Account::STORENAME_KEY
            . ',"label":"Ecommerce Data uploaded to MailChimp store Madison Island - English:"},'
            . '{"value":' . Ebizmarts_MailChimp_Model_System_Config_Source_Account::SYNC_LABEL_KEY
            . ',"label":"' . $liElementEscaped . '"},'
            . '{"value":' . Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_CUS_KEY
            . ',"label":"  Total Customers: 10"},'
            . '{"value":' . Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_PRO_KEY
            . ',"label":"  Total Products: 10"},'
            . '{"value":' . Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_ORD_KEY
            . ',"label":"  Total Orders: 10"},'
            . '{"value":' . Ebizmarts_MailChimp_Model_System_Config_Source_Account::TOTAL_QUO_KEY
            . ',"label":"  Total Carts: 10"}]';

        $squalomailControllerMock = $this->_squalomailController
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper', 'getRequest', 'getSourceAccountInfoOptions', 'getResponse'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSyncFlagDataHtml'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setHeader', 'setBody'))
            ->getMock();

        $squalomailControllerMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $squalomailControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->exactly(2))->method('getParam')->withConsecutive(
            array($storeIdParam),
            array($apiKeyParam)
        )->willReturnOnConsecutiveCalls(
            $mcStoreId,
            $apiKey
        );

        $squalomailControllerMock
            ->expects($this->once())
            ->method('getSourceAccountInfoOptions')
            ->with($apiKey, $mcStoreId)
            ->willReturn($data);

        $helperMock
            ->expects($this->once())
            ->method('getSyncFlagDataHtml')
            ->with($optionSyncFlag, "")
            ->willReturn($liElement);

        $squalomailControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setHeader')->with('Content-type', 'application/json');
        $responseMock->expects($this->once())->method('setBody')->with($jsonData);

        $squalomailControllerMock->getInfoAction();
    }

    public function testGetListAction()
    {
        $apiKeyParam = 'api_key';
        $apiKey = 'a1s2d3f4g5h6j7k8l9z1x2c3v4v4-us1';
        $storeIdParam = 'squalomail_store_id';
        $mcStoreId = 'q1w2e3r4t5y6u7i8o9p0';
        $listId = 'a1s2d3f4g5';

        $data = array(array('id' => $listId, 'name' => 'Newsletter'));
        $jsonData = '[{"id":"' . $listId . '","name":"Newsletter"}]';

        $squalomailControllerMock = $this->_squalomailController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getSourceListOptions', 'getResponse', 'getHelper'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setHeader', 'setBody'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isApiKeyObscure'))
            ->getMock();

        $squalomailControllerMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $squalomailControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->exactly(2))->method('getParam')->withConsecutive(
            array($apiKeyParam),
            array($storeIdParam)
        )->willReturnOnConsecutiveCalls(
            $apiKey,
            $mcStoreId
        );

        $helperMock->expects($this->once())->method('isApiKeyObscure')->with($apiKey)->willReturn(false);

        $squalomailControllerMock
            ->expects($this->once())
            ->method('getSourceListOptions')
            ->with($apiKey, $mcStoreId)
            ->willReturn($data);
        $squalomailControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setHeader')->with('Content-type', 'application/json');
        $responseMock->expects($this->once())->method('setBody')->with($jsonData);

        $squalomailControllerMock->getListAction();
    }

    public function testGetInterestAction()
    {
        $apiKeyParam = 'api_key';
        $apiKey = 'a1s2d3f4g5h6j7k8l9z1x2c3v4v4-us1';
        $listIdParam = 'list_id';
        $listId = 'a1s2d3f4g5';

        $data = array(
            array('value' => 'bc15dbe6a5', 'label' => 'Checkboxes'),
            array('value' => '2a2f23d671', 'label' => 'DropDown')
        );
        $jsonData = '[{"value":"bc15dbe6a5","label":"Checkboxes"},{"value":"2a2f23d671","label":"DropDown"}]';

        $squalomailControllerMock = $this->_squalomailController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getSourceInterestOptions', 'getResponse', 'getHelper'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setHeader', 'setBody'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_MailChimp_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('isApiKeyObscure'))
            ->getMock();

        $squalomailControllerMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $squalomailControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->exactly(2))->method('getParam')->withConsecutive(
            array($apiKeyParam),
            array($listIdParam)
        )->willReturnOnConsecutiveCalls(
            $apiKey,
            $listId
        );

        $helperMock->expects($this->once())->method('isApiKeyObscure')->with($apiKey)->willReturn(false);

        $squalomailControllerMock
            ->expects($this->once())
            ->method('getSourceInterestOptions')
            ->with($apiKey, $listId)
            ->willReturn($data);
        $squalomailControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setHeader')->with('Content-type', 'application/json');
        $responseMock->expects($this->once())->method('setBody')->with($jsonData);

        $squalomailControllerMock->getInterestAction();
    }
}
