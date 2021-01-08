<?php

/**
 * mc-magento Magento Component
 *
 * @category  Ebizmarts
 * @package   #PAC1#
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     2019-10-02 15:54
 * @file:     SychBatches.php
 */
class Ebizmarts_MailChimp_Model_Resource_SynchBatches extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Initialize
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('mailchimp/synchbatches', 'id');
    }
}
