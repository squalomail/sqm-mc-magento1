<?php

class Ebizmarts_SqualoMail_Model_ProcessWebhookTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mage::app('default');
    }

    public function testWebhookProfileCustomerExists()
    {
        $data = array('list_id' => 'a1s2d3f4t5', 'email' => 'pepe@ebizmarts.com');

        $processWebhookMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_ProcessWebhook::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getSqualomailTagsModel'))
            ->getMock();

        $squalomailTagsApiMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_Api_Subscribers_SqualomailTags::class)
            ->disableOriginalConstructor()
            ->setMethods(array('processMergeFields'))
            ->getMock();

        $processWebhookMock->expects($this->once())
            ->method('getSqualomailTagsModel')
            ->willReturn($squalomailTagsApiMock);

        $squalomailTagsApiMock->expects($this->once())
            ->method('processMergeFields')
            ->with($data)
            ->willReturnSelf();

        $processWebhookMock->_profile($data);
    }
}
