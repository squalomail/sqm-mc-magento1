<?php

require_once BP . DS . 'app/code/community/Ebizmarts/SqualoMail/controllers/Adminhtml/SqualomailstoresController.php';

class Ebizmarts_SqualoMail_Adminhtml_SqualomailstoresControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ebizmarts_SqualoMail_Adminhtml_SqualomailstoresController $squalomailstoresController
     */
    protected $_squalomailstoresController;

    public function setUp()
    {
        Mage::app('default');
        $this->_squalomailstoresController = $this->getMockBuilder(
            Ebizmarts_SqualoMail_Adminhtml_SqualomailstoresController::class
        );
    }

    public function tearDown()
    {
        $this->_squalomailstoresController = null;
    }

    public function testIndexAction()
    {
        $squalomailstoresControllerMock = $this->_squalomailstoresController
            ->disableOriginalConstructor()
            ->setMethods(array('_loadStores', 'loadLayout', '_setActiveMenu', 'renderLayout'))
            ->getMock();

        $squalomailstoresControllerMock->expects($this->once())->method('_loadStores');
        $squalomailstoresControllerMock->expects($this->once())->method('loadLayout');
        $squalomailstoresControllerMock->expects($this->once())->method('_setActiveMenu')->with('newsletter/squalomail');
        $squalomailstoresControllerMock->expects($this->once())->method('renderLayout');

        $squalomailstoresControllerMock->indexAction();
    }

    public function testGridAction()
    {
        $squalomailstoresControllerMock = $this->_squalomailstoresController
            ->disableOriginalConstructor()
            ->setMethods(array('loadLayout', 'renderLayout'))
            ->getMock();

        $squalomailstoresControllerMock->expects($this->once())->method('loadLayout')->with(false);
        $squalomailstoresControllerMock->expects($this->once())->method('renderLayout');

        $squalomailstoresControllerMock->gridAction();
    }

    public function testEditAction()
    {
        $idParam = 'id';
        $id = 1;
        $urlPath = '*/*/save';
        $url = 'domain.com/squalomail/squalomailstores/save';

        $squalomailstoresControllerMock = $this->_squalomailstoresController
            ->disableOriginalConstructor()
            ->setMethods(
                array('_title', 'getRequest', 'loadSqualomailStore', 'sessionregisterStore', '_initAction',
                    '_addBreadcrumb', 'getLayout', 'getUrl', '_addContent', 'renderLayout')
            )
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $squalomailStoreModelMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Stores::class)
            ->disableOriginalConstructor()
            ->getMock();

        $layoutMock = $this->getMockBuilder(Mage_Core_Model_Layout::class)
            ->disableOriginalConstructor()
            ->setMethods(array('createBlock'))
            ->getMock();

        $blockMock = $this->getMockBuilder(Mage_Core_Block_Abstract::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setData'))
            ->getMock();

        $squalomailstoresControllerMock->expects($this->exactly(2))->method('_title')->withConsecutive(
            array('Squalomail'),
            array('Squalomail Store')
        )->willReturnOnConsecutiveCalls(
            $squalomailstoresControllerMock,
            $squalomailstoresControllerMock
        );
        $squalomailstoresControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParam')->with($idParam)->willReturn($id);

        $squalomailstoresControllerMock
            ->expects($this->once())
            ->method('loadSqualomailStore')
            ->with($id)
            ->willReturn($squalomailStoreModelMock);
        $squalomailstoresControllerMock
            ->expects($this->once())
            ->method('sessionregisterStore')
            ->with($squalomailStoreModelMock);
        $squalomailstoresControllerMock->expects($this->once())->method('_initAction')->willReturnSelf();
        $squalomailstoresControllerMock
            ->expects($this->once())
            ->method('_addBreadcrumb')
            ->with('Edit Store', 'Edit Store')
            ->willReturnSelf();
        $squalomailstoresControllerMock->expects($this->once())->method('getLayout')->willReturn($layoutMock);
        $squalomailstoresControllerMock->expects($this->once())->method('getUrl')->with($urlPath)->willReturn($url);

        $layoutMock
            ->expects($this->once())
            ->method('createBlock')
            ->with('squalomail/adminhtml_squalomailstores_edit')
            ->willReturn($blockMock);

        $blockMock->expects($this->once())->method('setData')->with('action', $url)->willReturnSelf();

        $squalomailstoresControllerMock
            ->expects($this->once())
            ->method('_addContent')
            ->with($blockMock)
            ->willReturnSelf();
        $squalomailstoresControllerMock->expects($this->once())->method('renderLayout')->willReturnSelf();

        $squalomailstoresControllerMock->editAction();
    }

    public function testNewAction()
    {
        $squalomailstoresControllerMock = $this->_squalomailstoresController
            ->disableOriginalConstructor()
            ->setMethods(array('_forward'))
            ->getMock();

        $squalomailstoresControllerMock->expects($this->once())->method('_forward')->with('edit');

        $squalomailstoresControllerMock->newAction();
    }

    public function testSaveAction()
    {
        $postData = array('address_address_one' => 'addressOne', 'address_address_two' => 'addressTwo',
            'address_city' => 'city', 'address_postal_code' => 'postCode', 'address_country_code' => 'countryCode',
            'email_address' => 'email@example.com', 'currency_code' => 'USD', 'primary_locale' => 'en_US',
            'phone' => '123456', 'name' => 'name', 'domain' => 'domain.com', 'storeid' => 1, 'apikey' => '');

        $squalomailstoresControllerMock = $this->_squalomailstoresController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', '_updateSqualomail', '_redirect', 'getHelper'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getPost'))
            ->getMock();

        $squalomailstoresControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getPost')->willReturn($postData);

        $squalomailstoresControllerMock->expects($this->once())->method('_updateSqualomail')->with($postData);
        $squalomailstoresControllerMock->expects($this->once())->method('_redirect')->with('*/*/index');

        $squalomailstoresControllerMock->saveAction();
    }

    public function testGetstoresAction()
    {
        $apiKeyParam = 'api_key';
        $apiKey = 'a1s2d3f4g5h6j7k8l9z1x2c3v4b5-us1';
        $apiKeyEncrypted = '4rGjyBo/uKChzvu0bF3hjaMwfM503N3/+2fdRjdlAGo=';
        $sqmLists = array(
            'lists' => array(array(
                'id' => 'a1s2d3f4g5',
                'name' => 'Newsletter',
                'stats' => array(
                    'member_count' => 18
                )
            ))
        );
        $jsonData = '{"a1s2d3f4g5":{"id":"a1s2d3f4g5","name":"Newsletter"}}';

        $squalomailstoresControllerMock = $this->_squalomailstoresController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getSqualomailHelper', 'getResponse'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApiByKey', 'decryptData'))
            ->getMock();

        $apiMock = $this->getMockBuilder(Ebizmarts_SqualoMail::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLists'))
            ->getMock();

        $listsMock = $this->getMockBuilder(SqualoMail_Lists::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getLists'))
            ->getMock();

        $responseMock = $this->getMockBuilder(Mage_Core_Controller_Response_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('setHeader', 'setBody'))
            ->getMock();

        $squalomailstoresControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParam')->with($apiKeyParam)->willReturn($apiKeyEncrypted);

        $squalomailstoresControllerMock->expects($this->once())->method('getSqualomailHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('decryptData')->with($apiKeyEncrypted)->willReturn($apiKey);
        $helperMock->expects($this->once())->method('getApiByKey')->with($apiKey)->willReturn($apiMock);

        $apiMock->expects($this->once())->method('getLists')->willReturn($listsMock);

        $listsMock->expects($this->once())->method('getLists')->willReturn($sqmLists);

        $squalomailstoresControllerMock->expects($this->once())->method('getResponse')->willReturn($responseMock);

        $responseMock->expects($this->once())->method('setHeader')->with('Content-type', 'application/json');
        $responseMock->expects($this->once())->method('setBody')->with($jsonData);

        $squalomailstoresControllerMock->getstoresAction();
    }

    public function testDeleteAction()
    {
        $idParam = 'id';
        $tableId = 1;
        $squalomailStoreId = 'a1s2d3f4g5h6j7k8l9p0';
        $apiKey = 'a1s2d3f4g5h6j7k8l9p0z1x2c3v4b5-us1';
        $apiKeyEncrypted = '4rGjyBo/uKChzvu0bF3hjaMwfM503N3/+2fdRjdlAGo=';

        $squalomailstoresControllerMock = $this->_squalomailstoresController
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'loadSqualomailStore', 'getSqualomailHelper', '_redirect'))
            ->getMock();

        $requestMock = $this->getMockBuilder(Mage_Core_Controller_Request_Http::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getParam'))
            ->getMock();

        $squalomailStoreModelMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Stores::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStoreid', 'getApikey', 'getId'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getApiStores', 'deleteAllSQMStoreData', 'decryptData'))
            ->getMock();

        $apiStoresMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Stores::class)
            ->disableOriginalConstructor()
            ->setMethods(array('deleteSqualoMailStore'))
            ->getMock();

        $squalomailstoresControllerMock->expects($this->once())->method('getRequest')->willReturn($requestMock);

        $requestMock->expects($this->once())->method('getParam')->with($idParam)->willReturn($tableId);

        $squalomailstoresControllerMock
            ->expects($this->once())
            ->method('loadSqualomailStore')
            ->with($tableId)
            ->willReturn($squalomailStoreModelMock);

        $squalomailStoreModelMock->expects($this->once())->method('getStoreid')->willReturn($squalomailStoreId);
        $squalomailStoreModelMock->expects($this->once())->method('getApikey')->willReturn($apiKeyEncrypted);

        $squalomailstoresControllerMock->expects($this->once())->method('getSqualomailHelper')->willReturn($helperMock);

        $squalomailStoreModelMock->expects($this->once())->method('getId')->willReturn($tableId);

        $helperMock->expects($this->once())->method('decryptData')->with($apiKeyEncrypted)->willReturn($apiKey);
        $helperMock->expects($this->once())->method('getApiStores')->willReturn($apiStoresMock);

        $apiStoresMock->expects($this->once())->method('deleteSqualoMailStore')->with($squalomailStoreId, $apiKey);

        $helperMock->expects($this->once())->method('deleteAllSQMStoreData')->with($squalomailStoreId);

        $squalomailstoresControllerMock->expects($this->once())->method('_redirect')->with('*/*/index');

        $squalomailstoresControllerMock->deleteAction();
    }
}
