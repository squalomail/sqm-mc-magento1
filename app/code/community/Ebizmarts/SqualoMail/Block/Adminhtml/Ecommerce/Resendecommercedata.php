<?php

class Ebizmarts_SqualoMail_Block_Adminhtml_Ecommerce_Resendecommercedata
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    protected $_mode = 'resendecommercedata';
    public function __construct()
    {
        $this->_controller = 'adminhtml_ecommerce';
        $this->_blockGroup = 'squalomail';

        parent::__construct();
        $this->_removeButton("delete");
        $this->_removeButton("back");
        $this->_removeButton("reset");
    }

    public function getHeaderText()
    {
        return Mage::helper('squalomail')->__('Data to send');
    }
}
