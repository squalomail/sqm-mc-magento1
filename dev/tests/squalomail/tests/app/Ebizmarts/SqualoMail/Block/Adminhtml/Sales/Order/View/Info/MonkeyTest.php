<?php

class Ebizmarts_SqualoMail_Block_Adminhtml_Sales_Order_View_Info_MonkeyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Ebizmarts_SqualoMail_Block_Adminhtml_Sales_Order_View_Info_Monkey $_block
     */
    protected $_block;
    /**
     * @var \Mage_Sales_Model_Order $_orderMock
     */
    protected $_orderMock;


    public function setUp()
    {
        $app = Mage::app('default');
        $layout = $app->getLayout();
        $this->_block = new Ebizmarts_SqualoMail_Block_Adminhtml_Sales_Order_View_Info_Monkey;
        $this->_orderMock = $this->getMockBuilder(Mage_Sales_Model_Order::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getStoreId', 'getSqualomailAbandonedcartFlag', 'getSqualomailCampaignId'))
            ->getMock();
        if (!Mage::registry('current_order')) {
            Mage::register('current_order', $this->_orderMock);
        }

        /* We are required to set layouts before we can do anything with blocks */
        $this->_block->setLayout($layout);
    }

    public function testIsReferred()
    {
        /**
         * @var \Ebizmarts_SqualoMail_Block_Adminhtml_Sales_Order_View_Info_Monkey $monkeyBlock
         */
        $monkeyBlockMock = $this->getMockBuilder(
            Ebizmarts_SqualoMail_Block_Adminhtml_Sales_Order_View_Info_Monkey::class
        )
            ->disableOriginalConstructor()
            ->setMethods(array('getSqualoMailHelper', 'getCampaignId', 'getCurrentOrder'))
            ->getMock();
        $orderMock = $this->_orderMock;

        $monkeyBlockMock->expects($this->once())->method('getCurrentOrder')->willReturn($orderMock);
        $orderMock->expects($this->exactly(1))->method('getSqualomailAbandonedcartFlag')->willReturn(false);
        $orderMock->expects($this->exactly(1))->method('getSqualomailCampaignId')->willReturn(true);

        $monkeyBlockMock->isReferred();
    }


    public function testIsDataAvailable()
    {

        $campaignName = 'campaignName';
        /**
         * @var \Ebizmarts_SqualoMail_Block_Adminhtml_Sales_Order_View_Info_Monkey $monkeyBlock
         */
        $monkeyBlockMock = $this->getMockBuilder(
            Ebizmarts_SqualoMail_Block_Adminhtml_Sales_Order_View_Info_Monkey::class
        )
            ->disableOriginalConstructor()
            ->setMethods(array('getCampaignName'))
            ->getMock();

        $monkeyBlockMock->expects($this->once())->method('getCampaignName')->willReturn($campaignName);

        $result = $monkeyBlockMock->isDataAvailable();

        $this->assertEquals($result, true);
    }

    public function testGetCampaignName()
    {
        $campaignId = '1111111';
        $campaignName = 'campaignName';
        $storeId = 1;

        $monkeyBlockMock = $this->getMockBuilder(
            Ebizmarts_SqualoMail_Block_Adminhtml_Sales_Order_View_Info_Monkey::class
        )
            ->disableOriginalConstructor()
            ->setMethods(array('getCampaignId', 'getCurrentOrder', 'getSqualoMailHelper'))
            ->getMock();
        /**
         * @var \Ebizmarts_SqualoMail_Helper_Data $helperMock
         */
        $helperMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSqualoMailCampaignNameById', 'isEcomSyncDataEnabled'))
            ->getMock();

        $orderMock = $this->_orderMock;

        $monkeyBlockMock->expects($this->once())->method('getCampaignId')->willReturn($campaignId);
        $monkeyBlockMock->expects($this->once())->method('getCurrentOrder')->willReturn($orderMock);

        $orderMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $monkeyBlockMock->expects($this->once())->method('getSqualoMailHelper')->willReturn($helperMock);

        $helperMock->expects($this->once())->method('isEcomSyncDataEnabled')->with($storeId)->willReturn(true);
        $helperMock
            ->expects($this->once())
            ->method('getSqualoMailCampaignNameById')
            ->with($campaignId, $storeId)
            ->willReturn($campaignName);

        $result = $monkeyBlockMock->getCampaignName();

        $this->assertEquals($result, $campaignName);
    }
}
