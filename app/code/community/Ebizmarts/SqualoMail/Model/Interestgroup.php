<?php
/**
 * #REPO_NAME# Magento Component
 *
 * @category  Ebizmarts
 * @package   #PAC1#
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/16/16 6:23 PM
 * @file:     Interestgroup.php
 */

class Ebizmarts_SqualoMail_Model_Interestgroup extends Mage_Core_Model_Abstract
{
    /**
     * Initialize model
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('squalomail/interestgroup');
    }

    public function getByRelatedIdStoreId($customerId, $subscriberId, $storeId)
    {
        $this->addData($this->getResource()->getByRelatedIdStoreId($customerId, $subscriberId, $storeId));
        return $this;
    }
}
