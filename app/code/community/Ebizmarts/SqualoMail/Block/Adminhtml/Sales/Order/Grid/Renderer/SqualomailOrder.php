<?php

/**
 * sqm-mc-magento1 Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     12/12/17 3:28 PM
 * @file:     Abandoned.php
 */
class Ebizmarts_SqualoMail_Block_Adminhtml_Sales_Order_Grid_Renderer_SqualomailOrder
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    const SYNCED = 1;

    public function render(Varien_Object $row)
    {
        $storeId = $row->getStoreId();
        $orderId = $row->getEntityId();
        $orderDate = $row->getCreatedAt();
        $helper = $this->makeHelper();
        if ($helper->isEcomSyncDataEnabled($storeId)) {
            $squalomailStoreId = $helper->getSQMStoreId($storeId);
            $resultArray = $this->makeApiOrders()->getSyncedOrder($orderId, $squalomailStoreId);
            $id = $resultArray['order_id'];
            $status = $resultArray['synced_status'];

            if ($status == self::SYNCED) {
                $result = '<div style ="color:green">' . $helper->__("Yes") . '</div>';
            } elseif ($status === null && $id !== null) {
                $result = '<div style ="color:#ed6502">' . $helper->__("Processing") . '</div>';
            } elseif ($status === null && $orderDate > $helper->getEcommerceFirstDate($storeId)) {
                $result = '<div style ="color:mediumblue">' . $helper->__("In queue") . '</div>';
            } else {
                $result = '<div style ="color:red">' . $helper->__("No") . '</div>';
            }
        } else {
            $result = '<div style ="color:red">' . $helper->__("No") . '</div>';
        }

        return $result;
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('squalomail');
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_Orders
     */
    protected function makeApiOrders()
    {
        return Mage::getModel('squalomail/api_orders');
    }
}
