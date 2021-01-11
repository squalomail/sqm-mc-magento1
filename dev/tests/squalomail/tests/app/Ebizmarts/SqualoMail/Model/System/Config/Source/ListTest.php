<?php

class Ebizmarts_SqualoMail_Model_System_Config_Source_ListTest extends PHPUnit_Framework_TestCase
{
    const DEFAULT_STORE_ID = 1;

    public function setUp()
    {
        Mage::app('default');
    }

    public function testToOptionArray()
    {
        $scopeId = 1;
        $scope = 'stores';
        $scopeArray = array('scope_id' => $scopeId, 'scope' => $scope);
        $listId = 'a1s2d3f4g5';
        $sqmLists = array(
            'lists' => array(array(
                'id' => $listId,
                'name' => 'Newsletter',
                'stats' => array(
                    'member_count' => 18
                )
            ))
        );

        $listMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Model_System_Config_Source_List::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getHelper', 'getSQMLists'))
            ->getMock();

        $helperMock = $this->getMockBuilder(Ebizmarts_SqualoMail_Helper_Data::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getCurrentScope', 'getGeneralList'))
            ->getMock();

        $listMock->expects($this->once())->method('getHelper')->willReturn($helperMock);
        $listMock->expects($this->once())->method('getSQMLists')->willReturn($sqmLists);

        $expectedResult = array(array(
            'value' => $listId,
            'label' => 'Newsletter (18 members)'
        ));

        $result = $listMock->toOptionArray();
        $this->assertEquals($expectedResult, $result);
    }
}
