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
class Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata_Orders_Collection extends
    Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata_Collection
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
     * @param Mage_Sales_Model_Resource_Order_Collection $preFilteredOrdersCollection
     */
    public function joinLeftEcommerceSyncData($preFilteredOrdersCollection)
    {
        $squalomailTableName = $this->getSqualomailEcommerceDataTableName();
        $preFilteredOrdersCollection->getSelect()->joinLeft(
            array('m4m' => $squalomailTableName),
            "m4m.related_id = main_table.entity_id AND m4m.type = '"
            . Ebizmarts_SqualoMail_Model_Config::IS_ORDER
            . "' AND m4m.squalomail_store_id = '" . $this->getSqualomailStoreId() . "'",
            array('m4m.*')
        );
    }
}
