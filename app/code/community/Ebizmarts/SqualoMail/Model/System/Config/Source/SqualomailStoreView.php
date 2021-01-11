<?php

/**
 * Cron Process available count limits options source
 *
 * @category Ebizmarts
 * @package  #PAC2#
 * @author   Ebizmarts Team <info@ebizmarts.com>
 * @license  http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_MailChimp_Model_System_Config_Source_SqualomailStoreView
    extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{

    public function getAllOptions()
    {
        return Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true);
    }
}
