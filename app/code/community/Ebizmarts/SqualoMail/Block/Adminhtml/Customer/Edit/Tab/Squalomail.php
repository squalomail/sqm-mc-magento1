<?php
/**
 * sqm-mc-magento1 Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     6/10/16 12:38 AM
 * @file:     Grid.php
 */
class Ebizmarts_SqualoMail_Block_Adminhtml_Customer_Edit_Tab_Squalomail extends Mage_Adminhtml_Block_Widget_Grid
{

    protected $_lists = array();
    protected $_info = array();
    protected $_myLists = array();
    protected $_generalList = array();
    protected $_form;
    protected $_api;
    protected $_customer;
    /**
     * @var Ebizmarts_SqualoMail_Helper_Data
     */
    protected $_helper;
    protected $_storeId;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('ebizmarts/squalomail/customer/tab/squalomail.phtml');
        $this->_helper = $this->makeHelper();
        $customerId = (int) $this->getRequest()->getParam('id');
        if ($customerId) {
            $this->_customer = $this->getCustomerModel()->load($customerId);
            $this->_storeId = $this->getCustomer()->getStoreId();
        }
    }

    public function getInterest()
    {
        $customer = $this->getCustomer();
        $subscriber = $this->getSubscriberModel();
        $subscriber->loadByEmail($customer->getEmail());
        $subscriberId = $subscriber->getSubscriberId();
        $customerId = $customer->getId();
        $storeId = $this->getStoreId();
        $interest = $this->_helper->getInterestGroups($customerId, $subscriberId, $storeId);

        return $interest;
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
     * @return Mage_Newsletter_Model_Subscriber
     */
    protected function getSubscriberModel()
    {
        return Mage::getModel('newsletter/subscriber');
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('squalomail');
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function getCustomerModel()
    {
        return Mage::getModel('customer/customer');
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function getCustomer()
    {
        return $this->_customer;
    }

    /**
     * If customer was created in admin panel use the store view selected for SqualoMail.
     *
     * @return mixed
     */
    protected function getStoreId()
    {
        $storeId = $this->_storeId;
        if (!$storeId) {
            $storeId = $this->_customer->getSqualomailStoreView();
        }

        return $storeId;
    }
}
