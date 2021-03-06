<?php

/**
 * SqualoMail For Magento
 *
 * @category  Ebizmarts_SqualoMail
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/19/16 3:55 PM
 * @file:     WebhookDelete.php
 */
class Ebizmarts_SqualoMail_Model_System_Config_Source_WebhookDelete
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $squalomailHelper = Mage::helper('squalomail');

        return array(
            array('value' => 0, 'label' => $squalomailHelper->__('Unsubscribe')),
            array('value' => 1, 'label' => $squalomailHelper->__('Delete subscriber'))
        );
    }
}
