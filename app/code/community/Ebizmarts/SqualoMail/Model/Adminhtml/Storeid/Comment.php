<?php

class Ebizmarts_MailChimp_Model_Adminhtml_Storeid_Comment
{
    public function getCommentText()
    {
        $helper = Mage::helper('squalomail');
        return $helper->__(
            'Select the Squalomail store you want to associate with this scope. '
            . 'You can create a new store at '
        )
        . '<a target="_blank" href="'
        . Mage::helper('adminhtml')->getUrl('adminhtml/squalomailstores/index')
        .'">'.$helper->__('Newsletter -> Squalomail -> Squalomail Stores').'</a>';
    }
}
