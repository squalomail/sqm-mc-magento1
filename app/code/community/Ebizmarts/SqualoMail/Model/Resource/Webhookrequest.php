<?php

/**
 * sqm-mc-magento1 Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     2019-10-02 15:56
 * @file:     Squalomailerrors.php
 */
class Ebizmarts_SqualoMail_Model_Resource_Webhookrequest extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Initialize
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('squalomail/webhookrequest', 'id');
    }
}
