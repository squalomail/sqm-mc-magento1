<?php

/**
 * Checkout subscribe interest groups block renderer
 *
 * @category Ebizmarts
 * @package  Ebizmarts_MailChimp
 * @author   Ebizmarts Team <info@ebizmarts.com>
 * @license  http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_SqualoMail_Block_Checkout_Success_Groups extends Mage_Core_Block_Template
{
    protected $_currentIntesrest;
    /**
     * @var Ebizmarts_SqualoMail_Helper_Data
     */
    protected $_helper;
    protected $_toreId;

    public function __construct()
    {
        parent::__construct();
        $this->_helper = Mage::helper('squalomail');
        $this->_storeId = Mage::app()->getStore()->getId();
    }

    /**
     * @return string
     */
    public function getFormUrl()
    {
        return $this->getSuccessInterestUrl();
    }

    /**
     * @return string
     * @throws Mage_Core_Model_Store_Exception
     */
    public function getSuccessInterestUrl()
    {
        $url = 'squalomail/group/index';
        return Mage::app()->getStore()->getUrl($url);
    }

    /**
     * @return array|null
     * @throws Mage_Core_Exception
     * @throws SqualoMail_Error
     */
    public function getInterest()
    {
        $subscriber = $this->getSubscriberModel();
        $order = $this->getSessionLastRealOrder();
        $subscriber->loadByEmail($order->getCustomerEmail());
        $subscriberId = $subscriber->getSubscriberId();
        $customerId = $order->getCustomerId();
        $helper = $this->getSqualoMailHelper();
        $interest = $helper->getInterestGroups($customerId, $subscriberId, $order->getStoreId());
        return $interest;
    }

    /**
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getMessageBefore()
    {
        $storeId = $this->_storeId;
        $message = $this->getSqualoMailHelper()->getCheckoutSuccessHtmlBefore($storeId);
        return $message;
    }

    /**
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getMessageAfter()
    {
        $storeId = $this->_storeId;
        $message = $this->getSqualoMailHelper()->getCheckoutSuccessHtmlAfter($storeId);
        return $message;
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
     * @return Ebizmarts_SqualoMail_Helper_Data|Mage_Core_Helper_Abstract
     */
    public function getSqualoMailHelper()
    {
        return $this->_helper;
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function getSubscriberModel()
    {
        return Mage::getModel('newsletter/subscriber');
    }

    protected function _getStoreId()
    {
        return Mage::app()->getStore()->getId();
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    protected function getSessionLastRealOrder()
    {
        return $this->getSqualoMailHelper()->getSessionLastRealOrder();
    }
}