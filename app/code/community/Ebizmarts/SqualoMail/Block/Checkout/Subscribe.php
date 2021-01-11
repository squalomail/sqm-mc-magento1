<?php

/**
 * Checkout subscribe checkbox block renderer
 *
 * @category Ebizmarts
 * @package  Ebizmarts_MageMonkey
 * @author   Ebizmarts Team <info@ebizmarts.com>
 * @license  http://opensource.org/licenses/osl-3.0.php
 */
class Ebizmarts_SqualoMail_Block_Checkout_Subscribe extends Mage_Core_Block_Template
{

    protected $_lists = array();
    protected $_info = array();
    protected $_myLists = array();
    protected $_generalList = array();
    protected $_form;
    protected $_api;
    /**
     * @var Ebizmarts_SqualoMail_Helper_Data
     */
    protected $_helper;
    protected $_storeId;

    public function __construct()
    {
        parent::__construct();
        $this->_helper = Mage::helper('squalomail');
        $this->_storeId = Mage::app()->getStore()->getId();
    }

    /**
     * @param $data
     * @return string
     */
    public function escapeQuote($data)
    {
        return $this->getHelper()->sqmEscapeQuote($data);
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    public function getHelper()
    {
        return $this->_helper;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        $helper = $this->_helper;
        $storeId = $this->_storeId;

        $alreadySubscribed = Mage::getModel('newsletter/subscriber')
            ->loadByEmail($this->getQuote()->getCustomerEmail())
            ->isSubscribed();

        if ($helper->isCheckoutSubscribeEnabled($storeId) && !$alreadySubscribed) {
            return parent::_toHtml();
        } else {
            return '';
        }
    }

    /**
     * Retrieve current quote object from session
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function getQuote()
    {
        return Mage::getSingleton('checkout/session')
            ->getQuote();
    }

    protected function getCurrentCheckoutSubscribeValue()
    {
        return $this->_helper->getCheckoutSubscribeValue($this->_storeId);
    }

    protected function isForceHidden($currentValue = null)
    {
        if (!$currentValue) {
            $currentValue = $this->getCurrentCheckoutSubscribeValue();
        }

        return ($currentValue == Ebizmarts_SqualoMail_Model_System_Config_Source_Checkoutsubscribe::FORCE_HIDDEN);
    }

    protected function isForceVisible($currentValue)
    {
        return ($currentValue == Ebizmarts_SqualoMail_Model_System_Config_Source_Checkoutsubscribe::FORCE_VISIBLE);
    }

    protected function isCheckedByDefault($currentValue)
    {
        return ($currentValue == Ebizmarts_SqualoMail_Model_System_Config_Source_Checkoutsubscribe::CHECKED_BY_DEFAULT);
    }

    public function isForceEnabled()
    {
        $currentValue = $this->getCurrentCheckoutSubscribeValue();
        if ($this->isForceHidden($currentValue) || $this->isForceVisible($currentValue)) {
            return true;
        }

        return false;
    }

    public function isChecked()
    {
        $currentValue = $this->getCurrentCheckoutSubscribeValue();
        if ($this->isCheckedByDefault($currentValue) || $this->isForceVisible($currentValue)) {
            return true;
        }

        return false;
    }

    public function addToPostOnLoad()
    {
        return ($this->isChecked() || $this->isForceHidden());
    }

    /**
     * Get list data from MC
     *
     * @return array
     */
    public function getGeneralList()
    {
        $storeId = $this->_storeId;
        $helper = $this->_helper;
        $listId = $helper->getGeneralList($storeId);

        return $listId;
    }
}
