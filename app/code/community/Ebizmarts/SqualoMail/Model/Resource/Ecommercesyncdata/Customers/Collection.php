<?php

/**
 * #REPO_NAME# Magento Component
 *
 * @category  Ebizmarts
 * @package   #PAC1#
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     2019-11-04 17:32
 */
class Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Customers_Collection extends
    Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_Collection
{

    /**
     * Set resource type
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
    }

    /**
     * @param Mage_Customer_Model_Resource_Customer_Collection $preFilteredCustomersCollection
     */
    public function joinLeftEcommerceSyncData($preFilteredCustomersCollection)
    {
        $squalomailTableName = $this->getMailchimpEcommerceDataTableName();
        $joinCondition      = "m4m.related_id = e.entity_id AND m4m.type = '%s' AND m4m.squalomail_store_id = '%s'";
        $preFilteredCustomersCollection->getSelect()->joinLeft(
            array("m4m" => $squalomailTableName),
            sprintf($joinCondition, Ebizmarts_MailChimp_Model_Config::IS_CUSTOMER, $this->getMailchimpStoreId())
        );

        $preFilteredCustomersCollection->getSelect()->where("m4m.squalomail_sync_delta IS null OR m4m.squalomail_sync_modified = 1");
    }
}
