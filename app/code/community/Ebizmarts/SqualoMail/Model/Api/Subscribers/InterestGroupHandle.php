<?php
/**
 * squalomail-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   #PAC4#
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Ebizmarts_SqualoMail_Model_Api_Subscribers_InterestGroupHandle
{
    /**
     * @var Ebizmarts_SqualoMail_Helper_Data
     */
    protected $_helper;
    /**
     * @var Ebizmarts_SqualoMail_Helper_Date
     */
    protected $_dateHelper;

    /**
     * @var Array
     */
    protected $_groupings;

    /**
     * @var Mage_Customer_Model_Customer
     */
    protected $_customer;

    /**
     * @var Mage_Newsletter_Model_Subscriber
     */
    protected $_subscriber;

    /**
     * @var String
     */
    protected $_listId;

    public function __construct()
    {
        $this->_helper = Mage::helper('squalomail');
        $this->_dateHelper = Mage::helper('squalomail/date');
    }

    /**
     * @throws Ebizmarts_SqualoMail_Helper_Data_ApiKeyException
     * @throws SqualoMail_Error
     * @throws SqualoMail_HttpError
     */
    public function processGroupsData()
    {
        $groups = array();
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();

        $subscriber = $this->getSubscriber();
        $storeId = $subscriber->getStoreId();

        try {
            $api = $helper->getApi($storeId);
        } catch (Ebizmarts_SqualoMail_Helper_Data_ApiKeyException $e) {
            $helper->logError($e->getMessage());
            return;
        }

        $groups = $this->_getSubscribedGroups($api);

        $customerId = $this->_getCustomerId();
        $interestGroup = $this->getInterestGroupModel();

        $subscriberId = $subscriber->getSubscriberId();
        $interestGroup->getByRelatedIdStoreId($customerId, $subscriberId, $storeId);
        $encodedGroups = $helper->arrayEncode($groups);

        $interestGroup->setGroupdata($encodedGroups);
        $interestGroup->setSubscriberId($subscriberId);
        $interestGroup->setCustomerId($customerId);
        $interestGroup->setStoreId($storeId);
        $interestGroup->setUpdatedAt($dateHelper->getCurrentDateTime());
        $interestGroup->save();

        return $this;
    }

    /**
     * @return Mage_Newsletter_Model_Subscriber
     */
    public function getSubscriber()
    {
        if ($this->_subscriber === null) {
            $customerEmail = $this->_customer->getEmail();
            $this->setSubscriber($this->getSubscriberModel()->loadByEmail($customerEmail));
        }

        return $this->_subscriber;
    }

    /**
     * @return int
     */
    protected function _getCustomerId()
    {
        if ($this->_subscriber === null) {
            $customerId = $this->_customer->getId();
        } else {
            $customerId = $this->_subscriber->getCustomerId();
        }

        return $customerId;
    }

    /**
     * @param $interests
     * @param $grouping
     * @return array
     */
    protected function _getCustomerGroups($interests, $grouping)
    {
        $groups = array();
        $groupsSave = array();

        foreach ($interests['interests'] as $sqmGroup) {
            if (strpos($grouping['groups'], $sqmGroup['name']) !== false) {
                $groupsSave [$sqmGroup['id']] = $sqmGroup['id'];
            }
        }

        $groups [$grouping['unique_id']]= $groupsSave;

        return $groups;
    }

    /**
     * @param $api
     * @return array
     */
    protected function _getSubscribedGroups($api)
    {
        $groups = array();
        $helper = $this->getHelper();

        try
        {
            $apiInterests = $api->getLists()->getInterestCategory()->getInterests();

            foreach ($this->_groupings as $grouping) {
                $interests = $apiInterests->getAll($this->_listId, $grouping['unique_id']);
                $groups = $this->_getCustomerGroups($interests, $grouping);
            }
        } catch (SqualoMail_Error $e) {
            $helper->logError($e->getFriendlyMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }

        return $groups;
    }

    /**
     * @param $groupings
     * @return $this
     */
    public function setGroupings($groupings)
    {
        $this->_groupings = $groupings;
        return $this;
    }

    /**
     * @param $customer
     * @return $this
     */
    public function setCustomer($customer)
    {
        $this->_customer = $customer;
        return $this;
    }

    /**
     * @param $subscriber
     * @return $this
     */
    public function setSubscriber($subscriber)
    {
        $this->_subscriber = $subscriber;
        return $this;
    }

    /**
     * @param $listId
     * @return $this
     */
    public function setListId($listId)
    {
        $this->_listId = $listId;
        return $this;
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function getSubscriberModel()
    {
        return Mage::getModel('newsletter/subscriber');
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Interestgroup
     */
    protected function getInterestGroupModel()
    {
        return Mage::getModel('squalomail/interestgroup');
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    protected function getHelper()
    {
        return $this->_helper;
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Date|Mage_Core_Helper_Abstract
     */
    protected function getDateHelper()
    {
        return $this->_dateHelper;
    }
}
