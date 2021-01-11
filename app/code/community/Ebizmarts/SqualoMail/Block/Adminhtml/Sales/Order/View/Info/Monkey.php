<?php

/**
 * #REPO_NAME# Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     12/8/16 2:04 PM
 * @file:     Monkey.php
 */
class Ebizmarts_SqualoMail_Block_Adminhtml_Sales_Order_View_Info_Monkey extends Mage_Core_Block_Template
{

    /**
     * @var string $campaignName
     */
    protected $_campaignName = null;

    /**
     * @var Mage_Sales_Model_Order $order
     */
    protected $_order = null;

    /**
     * @return bool
     */
    public function isReferred()
    {
        $order = $this->getCurrentOrder();
        $ret = false;
        if ($order->getSqualomailAbandonedcartFlag() || $order->getSqualomailCampaignId()) {
            $ret = true;
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function getCampaignId()
    {
        $order = $this->getCurrentOrder();
        return $order->getSqualomailCampaignId();
    }

    /**
     * @return string
     */
    public function getCampaignName()
    {
        if (!$this->_campaignName) {
            $campaignId = $this->getCampaignId();
            $order = $this->getCurrentOrder();
            $storeId = $order->getStoreId();
            $helper = $this->getSqualoMailHelper();

            if ($helper->isEcomSyncDataEnabled($storeId)) {
                $this->_campaignName = $helper->getSqualoMailCampaignNameById($campaignId, $storeId);
            }
        }

        return $this->_campaignName;
    }

    /**
     * @param $data
     * @return string
     */
    public function escapeQuote($data)
    {
        return $this->getSqualoMailHelper()->sqmEscapeQuote($data);
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    public function getSqualoMailHelper()
    {
        return Mage::helper('squalomail');
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    protected function getCurrentOrder()
    {
        if (!$this->_order) {
            $this->_order = Mage::registry('current_order');
        }

        return $this->_order;
    }

    /**
     * Return true if campaign data is available with the current api and list selected.
     *
     * @return bool
     */
    public function isDataAvailable()
    {
        $dataAvailable = false;
        $campaignName = $this->getCampaignName();

        if ($campaignName) {
            $dataAvailable = true;
        }

        return $dataAvailable;
    }

    /**
     * @return string | return the store code
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getStoreCodeFromOrder()
    {
        $helper = $this->getSqualoMailHelper();
        $order = $this->getCurrentOrder();
        $storeId = $order->getStoreId();
        $storeCode = $helper->getMageApp()->getStore($storeId)->getCode();

        return $storeCode;
    }
}
