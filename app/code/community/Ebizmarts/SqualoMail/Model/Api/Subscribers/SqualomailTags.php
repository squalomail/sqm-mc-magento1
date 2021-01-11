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

class Ebizmarts_SqualoMail_Model_Api_Subscribers_SqualomailTags
{
    const GENDER_VALUE_MALE = 1;
    const GENDER_VALUE_FEMALE = 2;

    /**
     * @var int
     */
    protected $_storeId;
    /**
     * @var array
     */
    protected $_squaloMailTags;
    /**
     * @var Mage_Newsletter_Model_Subscriber
     */
    protected $_subscriber;
    /**
     * @var Mage_Customer_Model_Customer
     */
    protected $_customer;
    /**
     * @var Ebizmarts_SqualoMail_Helper_Data
     */
    protected $_mcHelper;
    /**
     * @var Ebizmarts_SqualoMail_Helper_Date
     */
    protected $_mcDateHelper;
    /**
     * @var Ebizmarts_SqualoMail_Helper_Webhook
     */
    protected $_mcWebhookHelper;
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_lastOrder;

    /**
     * @var Ebizmarts_SqualoMail_Model_Api_Subscribers_InterestGroupHandle
     */
    protected $_interestGroupHandle;

    public function __construct()
    {
        $this->setSqualoMailHelper();
        $this->setSqualoMailDateHelper();
        $this->setSqualoMailWebhookHelper();

        $this->_interestGroupHandle = Mage::getModel('squalomail/api_subscribers_InterestGroupHandle');
    }

    /**
     * @param $storeId
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeId;
    }

    /**
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     */
    public function setSubscriber($subscriber)
    {
        $this->_subscriber = $subscriber;
    }

    /**
     * @return Mage_Newsletter_Model_Subscriber
     */
    public function getSubscriber()
    {
        return $this->_subscriber;
    }

    /**
     * @param Mage_Customer_Model_Customer $customer
     */
    public function setCustomer($customer)
    {
        $this->_customer = $customer;
    }

    /**
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        return $this->_customer;
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
     * @return array
     */
    public function getSqualoMailTags()
    {
        return $this->_squaloMailTags;
    }

    /**
     * @param $key
     * @param $value
     */
    public function addSqualoMailTag($key, $value)
    {
        $this->_squaloMailTags[$key] = $value;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getSqualoMailTagValue($key)
    {
        $squalomailTagValue = null;

        if (isset($this->_squaloMailTags[$key])) {
            $squalomailTagValue = $this->_squaloMailTags[$key];
        }

        return $squalomailTagValue;
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    public function getLastOrder()
    {
        return $this->_lastOrder;
    }

    /**
     * @param Mage_Sales_Model_Order $lastOrder
     */
    public function setLastOrder($lastOrder)
    {
        $this->_lastOrder = $lastOrder;
    }

    /**
     * @throws Mage_Core_Exception
     */
    public function buildSqualoMailTags()
    {
        $helper = $this->getSqualomailHelper();
        $storeId = $this->getStoreId();
        $mapFields = $helper->getMapFields($storeId);
        $maps = $this->unserializeMapFields($mapFields);

        $attrSetId = $this->getEntityAttributeCollection()
            ->setEntityTypeFilter(1)
            ->addSetInfo()
            ->getData();

        foreach ($maps as $map) {
            $customAtt = $map['magento'];
            $chimpTag = $map['squalomail'];
            if ($chimpTag && $customAtt) {
                $key = strtoupper($chimpTag);

                if (is_numeric($customAtt)) {
                    $this->buildCustomerAttributes($attrSetId, $customAtt, $key);
                } else {
                    $this->buildCustomizedAttributes($customAtt, $key);
                }
            }
        }

        $newVars = $this->getNewVarienObject();
        $this->dispatchEventMergeVarAfter($newVars);

        if ($newVars->hasData()) {
            $this->mergeSqualomailTags($newVars->getData());
        }
    }

    /**
     * @param $data
     * @param bool $subscribe
     * @throws Mage_Core_Exception
     */
    public function processMergeFields($data, $subscribe = false)
    {
        $helper = $this->getSqualomailHelper();
        $email = $data['email'];
        $listId = $data['list_id'];
        $storeId = $helper->getMagentoStoreIdsByListId($listId)[0];

        $this->_squaloMailTags = $helper->getMapFields($storeId);
        $this->_squaloMailTags = $this->unserializeMapFields($this->_squaloMailTags);

        $customer = $helper->loadListCustomer($listId, $email);

        if ($customer) {
            $this->setCustomer($customer);
            $this->_setSqualomailTagsToCustomer($data);
        } else {
            $subscriber = $helper->loadListSubscriber($listId, $email);
            $fname = $this->_getFName($data);
            $lname = $this->_getLName($data);

            if ($subscriber->getId()) {
                $subscriber->setSubscriberFirstname($fname);
                $subscriber->setSubscriberLastname($lname);
            } else {
                /**
                 * Squalomail subscriber not currently in magento newsletter subscribers.
                 * Get squalomail subscriber status and add missing newsletter subscriber.
                 */
                $this->_addSubscriberData($subscriber, $fname, $lname, $email, $listId);

                if ($subscribe) {
                    $helper->subscribeMember($subscriber);
                }
            }

            $subscriber->save();
            $this->setSubscriber($subscriber);
        }

        if (isset($data['merges']['GROUPINGS'])) {
            $interestGroupHandle = $this->_getInterestGroupHandleModel();

            if ($this->getSubscriber() === null) {
                $interestGroupHandle->setCustomer($this->getCustomer());
            } else {
                $interestGroupHandle->setSubscriber($this->getSubscriber());
            }

            $interestGroupHandle->setGroupings($data['merges']['GROUPINGS'])
                ->setListId($listId)
                ->processGroupsData();
        }
    }

    /**
     * @param $subscriber
     * @param $fname
     * @param $lname
     * @param $email
     * @param $listId
     * @throws Exception
     */
    protected function _addSubscriberData($subscriber, $fname, $lname, $email, $listId)
    {
        $helper = $this->getSqualomailHelper();
        $webhookHelper = $this->getSqualomailWebhookHelper();
        $scopeArray = $helper->getFirstScopeFromConfig(
            Ebizmarts_SqualoMail_Model_Config::GENERAL_LIST,
            $listId
        );
        $api = $helper->getApi($scopeArray['scope_id'], $scopeArray['scope']);

        try {
            $subscriber->setSubscriberFirstname($fname);
            $subscriber->setSubscriberLastname($lname);
            $md5HashEmail = hash('md5', strtolower($email));
            $member = $api->getLists()->getMembers()->get(
                $listId,
                $md5HashEmail,
                null,
                null
            );

            if ($member['status'] == 'subscribed') {
                $helper->subscribeMember($subscriber);
            } else if ($member['status'] == 'unsubscribed') {
                if (!$webhookHelper->getWebhookDeleteAction($subscriber->getStoreId())) {
                    $helper->unsubscribeMember($subscriber);
                }
            }
        } catch (SqualoMail_Error $e) {
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $helper->logError($e->getMessage());
        }
    }

    /**
     * @return Varien_Object
     */
    protected function getNewVarienObject()
    {
        return new Varien_Object;
    }

    /**
     * @param $attributeCode
     * @param $key
     * @param $attribute
     * @return |null
     */
    protected function customerAttributes($attributeCode, $key, $attribute)
    {
        $subscriber = $this->getSubscriber();
        $customer   = $this->getCustomer();

        $eventValue = null;

        if ($attributeCode != 'email') {
            $this->_addTags($attributeCode, $subscriber, $customer, $key, $attribute);
        }

        if ($this->getSqualoMailTagValue($key) !== null) {
            $eventValue = $this->getSqualoMailTagValue($key);
        }

        return $eventValue;
    }

    /**
     * @param $attributeCode
     * @param $subscriber
     * @param $customer
     * @param $key
     * @param $attribute
     */
    protected function _addTags($attributeCode, $subscriber, $customer, $key, $attribute)
    {
        if ($attributeCode == 'default_billing' || $attributeCode == 'default_shipping') {
            $this->addDefaultShipping($attributeCode, $key, $customer);
        } elseif ($attributeCode == 'gender') {
            $this->addGender($attributeCode, $key, $customer);
        } elseif ($attributeCode == 'group_id') {
            $this->addGroupId($attributeCode, $key, $customer);
        } elseif ($attributeCode == 'firstname') {
            $this->addFirstName($key, $subscriber, $customer);
        } elseif ($attributeCode == 'lastname') {
            $this->addLastName($key, $subscriber, $customer);
        } elseif ($attributeCode == 'store_id') {
            $this->addSqualoMailTag($key, $this->getStoreId());
        } elseif ($attributeCode == 'website_id') {
            $this->addWebsiteId($key);
        } elseif ($attributeCode == 'created_in') {
            $this->addCreatedIn($key);
        } elseif ($attributeCode == 'dob') {
            $this->addDob($attributeCode, $key, $customer);
        } else {
            $this->addUnknownMergeField($attributeCode, $key, $attribute, $customer);
        }
    }

    /**
     * @param $mapFields
     * @return mixed
     */
    protected function unserializeMapFields($mapFields)
    {
        return $this->_mcHelper->unserialize($mapFields);
    }

    /**
     * @return Object
     */
    protected function getEntityAttributeCollection()
    {
        return Mage::getResourceModel('eav/entity_attribute_collection');
    }

    /**
     * Add possibility to change value on certain merge tag
     *
     * @param $attributeCode
     * @param $eventValue
     */
    protected function dispatchMergeVarBefore($attributeCode, &$eventValue)
    {
        Mage::dispatchEvent(
            'squalomail_merge_field_send_before',
            array(
                'customer_id' => $this->getCustomer()->getId(),
                'subscriber_email' => $this->getSubscriber()->getSubscriberEmail(),
                'merge_field_tag' => $attributeCode,
                'merge_field_value' => &$eventValue
            )
        );
    }

    /**
     * Allow possibility to add new vars in 'new_vars' array
     *
     * @param $newVars
     */
    protected function dispatchEventMergeVarAfter( &$newVars)
    {
        Mage::dispatchEvent(
            'squalomail_merge_field_send_after',
            array(
                'subscriber' => $this->getSubscriber(),
                'vars' => $this->getSqualoMailTags(),
                'new_vars' => &$newVars
            )
        );
    }

    /**
     * @return mixed
     */
    protected function toArray()
    {
        return $this->_squaloMailTags;
    }

    /**
     * @param $squalomailTags
     * @return bool
     */
    protected function mergeSqualomailTags($squalomailTags)
    {
        if (is_array($squalomailTags)) {
            $this->_squaloMailTags = array_merge($this->_squaloMailTags, $squalomailTags);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $storeId
     * @return mixed
     */
    protected function getWebSiteByStoreId($storeId)
    {
        return Mage::getModel('core/store')->load($storeId)->getWebsiteId();
    }

    /**
     * @param $address
     * @return array | returns an array with the address data of the customer.
     */
    protected function getAddressData($address)
    {
        $lastOrder = $this->getLastOrderByEmail();
        $addressData = $this->getAddressFromLastOrder($lastOrder);
        if (!empty($addressData)) {
            if ($address) {
                $street = $address->getStreet();
                if (count($street) > 1) {
                    $addressData["addr1"] = $street[0];
                    $addressData["addr2"] = $street[1];
                } else {
                    if (!empty($street[0])) {
                        $addressData["addr1"] = $street[0];
                    }
                }

                if ($address->getCity()) {
                    $addressData["city"] = $address->getCity();
                }

                if ($address->getRegion()) {
                    $addressData["state"] = $address->getRegion();
                }

                if ($address->getPostcode()) {
                    $addressData["zip"] = $address->getPostcode();
                }

                if ($address->getCountry()) {
                    $addressData["country"] = Mage::getModel('directory/country')
                        ->loadByCode($address->getCountry())
                        ->getName();
                }
            }
        }

        return $addressData;
    }

    /**
     * @param $attributeCode
     * @param $customer
     * @return string | returns the data of the attribute code.
     */
    protected function getCustomerGroupLabel($attributeCode, $customer)
    {
        return $customer->getData($attributeCode);
    }

    /**
     * @param $mergeVars
     * @param $key
     * @param $genderValue
     * @return string | return a string with the gender of the customer.
     */
    protected function getGenderLabel($mergeVars, $key, $genderValue)
    {
        if ($genderValue == self::GENDER_VALUE_MALE) {
            $mergeVars[$key] = 'Male';
        } elseif ($genderValue == self::GENDER_VALUE_FEMALE) {
            $mergeVars[$key] = 'Female';
        }

        return $mergeVars[$key];
    }

    /**
     * @param $genderLabel
     * @return int
     */
    protected function getGenderValue($genderLabel)
    {
        $genderValue = 0;

        if ($genderLabel == 'Male') {
            $genderValue = self::GENDER_VALUE_MALE;
        } elseif ($genderLabel == 'Female') {
            $genderValue = self::GENDER_VALUE_FEMALE;
        }

        return $genderValue;
    }

    /**
     * @param $subscriber
     * @param $customer
     * @return string | returns the first name of the customer.
     */
    protected function getFirstName($subscriber, $customer)
    {
        $lastOrder = $this->getLastOrderByEmail();
        $firstName = $customer->getFirstname();

        if (!$firstName) {
            if ($subscriber->getSubscriberFirstname()) {
                $firstName = $subscriber->getSubscriberFirstname();
            } elseif ($lastOrder && $lastOrder->getCustomerFirstname()) {
                $firstName = $lastOrder->getCustomerFirstname();
            }
        }

        return $firstName;
    }

    /**
     * @param $subscriber
     * @param $customer
     * @return string | return the last name of the customer.
     */
    protected function getLastName($subscriber, $customer)
    {
        $lastOrder = $this->getLastOrderByEmail();
        $lastName = $customer->getLastname();

        if (!$lastName) {
            if ($subscriber->getSubscriberLastname()) {
                $lastName = $subscriber->getSubscriberLastname();
            } elseif ($lastOrder && $lastOrder->getCustomerLastname()) {
                $lastName = $lastOrder->getCustomerLastname();
            }
        }

        return $lastName;
    }

    /**
     * @param $lastOrder
     * @return array
     */
    protected function getAddressFromLastOrder($lastOrder)
    {
        $addressData = array();
        if ($lastOrder && $lastOrder->getShippingAddress()) {
            $addressData = $lastOrder->getShippingAddress();
        }

        return $addressData;
    }

    /**
     * @param $customAtt
     * @param $customer
     * @return array | returns an array with the address if it exists
     */
    protected function getAddressForCustomizedAttributes($customAtt, $customer)
    {
        $lastOrder = $this->getLastOrderByEmail();
        $address = $this->getAddressFromLastOrder($lastOrder);
        if (!empty($address)) {
            $addr = explode('_', $customAtt);
            $address = $customer->getPrimaryAddress('default_' . $addr[0]);
        }

        return $address;
    }

    /**
     * @param $customAtt
     * @param $key
     * @return mixed | null
     */
    protected function customizedAttributes($customAtt, $key)
    {
        $eventValue = null;
        $customer = $this->getCustomer();

        if ($customAtt == 'billing_company' || $customAtt == 'shipping_company') {
            $this->addCompany($customAtt, $customer, $key);
        } elseif ($customAtt == 'billing_telephone' || $customAtt == 'shipping_telephone') {
            $this->addTelephoneFromCustomizedAttribute($customAtt, $key, $customer);
        } elseif ($customAtt == 'billing_country' || $customAtt == 'shipping_country') {
            $this->addCountryFromCustomizedAttribute($customAtt, $key, $customer);
        } elseif ($customAtt == 'billing_zipcode' || $customAtt == 'shipping_zipcode') {
            $this->addZipCodeFromCustomizedAttribute($customAtt, $key, $customer);
        } elseif ($customAtt == 'billing_state' || $customAtt == 'shipping_state') {
            $this->addStateFromCustomizedAttribute($customAtt, $key, $customer);
        } elseif ($customAtt == 'dop') {
            $this->addDopFromCustomizedAttribute($key);
        } elseif ($customAtt == 'store_code') {
            $this->addStoreCodeFromCustomizedAttribute($key);
        }

        if ((string)$this->getSqualoMailTagValue($key) != '') {
            $eventValue = $this->getSqualoMailTagValue($key);
        }

        return $eventValue;
    }

    /**
     * @param $attributeCode
     * @param $customer
     * @param $attribute
     * @return mixed
     */
    protected function getUnknownMergeField($attributeCode, $customer, $attribute)
    {
        $optionValue = null;

        $attrValue = $this->getCustomerGroupLabel($attributeCode, $customer);
        if ($attrValue !== null) {
            if ($attribute['frontend_input'] == 'select' && $attrValue) {
                $attr = $customer->getResource()->getAttribute($attributeCode);
                $optionValue = $attr->getSource()->getOptionText($attrValue);
            } elseif ($attrValue) {
                $optionValue = $attrValue;
            }
        }

        return $optionValue;
    }


    /**
     * @param $attributeCode
     * @param $customer
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function getDateOfBirth($attributeCode, $customer)
    {
        return $this->getSqualomailDateHelper()->formatDate(
            $this->getCustomerGroupLabel($attributeCode, $customer),
            'm/d', 1
        );
    }

    /**
     * If orders with the given email exists, returns the date of the last order made.
     *
     * @param  $subscriberEmail
     * @return null
     */
    protected function getLastDateOfPurchase()
    {
        $lastDateOfPurchase = null;
        $lastOrder = $this->getLastOrderByEmail();
        if ($lastOrder !== null) {
            $lastDateOfPurchase = $lastOrder->getCreatedAt();
        }

        return $lastDateOfPurchase;
    }

    /**
     * @param $customAtt
     * @param $customer
     * @param $mergeVars
     * @param $key
     * @return mixed
     */
    protected function addCompany($customAtt, $customer, $key)
    {
        $address = $this->getAddressForCustomizedAttributes($customAtt, $customer);
        if ($address) {
            $company = $address->getCompany();
            if ($company) {
                $this->addSqualoMailTag($key, $company);
            }
        }

    }

    /**
     * return the latest order for this subscriber
     *
     * @return Mage_Sales_Model_Order
     */
    protected function getLastOrderByEmail()
    {
        $lastOrder = $this->getLastOrder();

        if ($lastOrder === null) {
            $helper = $this->getSqualomailHelper();
            $orderCollection = $helper->getOrderCollectionByCustomerEmail($this->getSubscriber()->getSubscriberEmail())
                ->setOrder('created_at', 'DESC')
                ->setPageSize(1);

            if ($this->isNotEmptyOrderCollection($orderCollection)) {
                $lastOrder = $orderCollection->getLastItem();
                $this->setLastOrder($lastOrder);
            }
        }

        return $lastOrder;
    }

    /**
     * @param $orderCollection
     * @return bool | returns true if the size of the orderCollection have at least one element.
     */
    protected function isNotEmptyOrderCollection($orderCollection)
    {
        return $orderCollection->getSize() > 0;
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    public function getSqualomailHelper()
    {
        return $this->_mcHelper;
    }

    /**
     * @param $mageMCHelper
     */
    protected function setSqualoMailHelper()
    {
        $this->_mcHelper = Mage::helper('squalomail');
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Date
     */
    public function getSqualomailDateHelper()
    {
        return $this->_mcDateHelper;
    }

    /**
     * @param $mageMCDateHelper
     */
    protected function setSqualoMailDateHelper()
    {
        $this->_mcDateHelper = Mage::helper('squalomail/date');
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Webhook
     */
    public function getSqualomailWebhookHelper()
    {
        return $this->_mcWebhookHelper;
    }

    /**
     * @param $mageMCWebhookHelper
     */
    protected function setSqualoMailWebhookHelper()
    {
        $this->_mcWebhookHelper = Mage::helper('squalomail/webhook');
    }

    /**
     * @param $key
     * @return bool
     */
    protected function squaloMailTagIsSet($key)
    {
        return isset($this->_squaloMailTags[$key]);
    }

    /**
     * @param $attrSetId
     * @param $customAtt
     * @param $key
     */
    protected function buildCustomerAttributes($attrSetId, $customAtt, $key)
    {
        $eventValue = null;
        foreach ($attrSetId as $attribute) {
            if ($attribute['attribute_id'] == $customAtt) {
                $attributeCode = $attribute['attribute_code'];
                $eventValue = $this->customerAttributes(
                    $attributeCode, $key, $attribute
                );

                $this->dispatchMergeVarBefore($attributeCode, $eventValue);
                if ($eventValue !== null) {
                    $this->addSqualoMailTag($key, $eventValue);
                }
            }
        }
    }

    /**
     * @param $customAtt
     * @param $key
     */
    protected function buildCustomizedAttributes($customAtt, $key)
    {
        $eventValue = null;
        $eventValue = $this->customizedAttributes(
            $customAtt, $key
        );

        $this->dispatchMergeVarBefore($customAtt, $eventValue);
        if ($eventValue !== null) {
            $this->addSqualoMailTag($key, $eventValue);
        }
    }

    /**
     * @param $attributeCode
     * @param $key
     * @param $customer
     */
    protected function addDefaultShipping($attributeCode, $key, $customer)
    {
        $address = $customer->getPrimaryAddress($attributeCode);
        $addressData = $this->getAddressData($address);

        if (!empty($addressData)) {
            $this->addSqualoMailTag($key, $addressData);
        }
    }

    /**
     * @param $attributeCode
     * @param $key
     * @param $customer
     */
    protected function addGender($attributeCode, $key, $customer)
    {
        if ($this->getCustomerGroupLabel($attributeCode, $customer)) {
            $genderValue = $this->getCustomerGroupLabel($attributeCode, $customer);
            $this->addSqualoMailTag($key, $this->getGenderLabel($this->_squaloMailTags, $key, $genderValue));
        }
    }

    /**
     * @param $attributeCode
     * @param $key
     * @param $customer
     */
    protected function addGroupId($attributeCode, $key, $customer)
    {
        if ($this->getCustomerGroupLabel($attributeCode, $customer)) {
            $groupId = (int)$this->getCustomerGroupLabel($attributeCode, $customer);
            $customerGroup = Mage::helper('customer')->getGroups()->toOptionHash();
            $this->addSqualoMailTag($key, $customerGroup[$groupId]);
        } else {
            $this->addSqualoMailTag($key, 'NOT LOGGED IN');
        }
    }

    /**
     * @param $key
     * @param $subscriber
     * @param $customer
     */
    protected function addFirstName($key, $subscriber, $customer)
    {
        $firstName = $this->getFirstName($subscriber, $customer);

        if ($firstName) {
            $this->addSqualoMailTag($key, $firstName);
        }
    }

    /**
     * @param $key
     * @param $subscriber
     * @param $customer
     */
    protected function addLastName($key, $subscriber, $customer)
    {
        $lastName = $this->getLastName($subscriber, $customer);

        if ($lastName) {
            $this->addSqualoMailTag($key, $lastName);
        }
    }

    /**
     * @param $key
     */
    protected function addWebsiteId($key)
    {
        $websiteId = $this->getWebSiteByStoreId($this->getStoreId());
        $this->addSqualoMailTag($key, $websiteId);
    }

    /**
     * @param $key
     */
    protected function addCreatedIn($key)
    {
        $storeName = Mage::getModel('core/store')->load($this->getStoreId())->getName();
        $this->addSqualoMailTag($key, $storeName);
    }

    /**
     * @param $attributeCode
     * @param $key
     * @param $customer
     */
    protected function addDob($attributeCode, $key, $customer)
    {
        if ($this->getCustomerGroupLabel($attributeCode, $customer)) {
            $this->addSqualoMailTag($key, $this->getDateOfBirth($attributeCode, $customer));
        }
    }

    /**
     * @param $attributeCode
     * @param $key
     * @param $attribute
     * @param $customer
     */
    protected function addUnknownMergeField($attributeCode, $key, $attribute, $customer)
    {
        $mergeValue = $this->getUnknownMergeField($attributeCode, $customer, $attribute);
        if ($mergeValue !== null) {
            $this->addSqualoMailTag($key, $mergeValue);
        }
    }

    /**
     * @param $customAtt
     * @param $key
     * @param $customer
     */
    protected function addTelephoneFromCustomizedAttribute($customAtt, $key, $customer)
    {
        $address = $this->getAddressForCustomizedAttributes($customAtt, $customer);
        if ($address) {
            $telephone = $address->getTelephone();
            if ($telephone) {
                $this->addSqualoMailTag($key, $telephone);
            }
        }
    }

    /**
     * @param $customAtt
     * @param $key
     * @param $customer
     */
    protected function addCountryFromCustomizedAttribute($customAtt, $key, $customer)
    {
        $address = $this->getAddressForCustomizedAttributes($customAtt, $customer);
        if ($address) {
            $countryCode = $address->getCountry();
            if ($countryCode) {
                $countryName = Mage::getModel('directory/country')->loadByCode($countryCode)->getName();
                $this->addSqualoMailTag($key, $countryName);
            }
        }
    }

    /**
     * @param $customAtt
     * @param $key
     * @param $customer
     */
    protected function addZipCodeFromCustomizedAttribute($customAtt, $key, $customer)
    {
        $address = $this->getAddressForCustomizedAttributes($customAtt, $customer);
        if ($address) {
            $zipCode = $address->getPostcode();
            if ($zipCode) {
                $this->addSqualoMailTag($key, $zipCode);
            }
        }
    }

    /**
     * @param $customAtt
     * @param $key
     * @param $customer
     */
    protected function addStateFromCustomizedAttribute($customAtt, $key, $customer)
    {
        $address = $this->getAddressForCustomizedAttributes($customAtt, $customer);
        if ($address) {
            $state = $address->getRegion();
            if ($state) {
                $this->addSqualoMailTag($key, $state);
            }
        }
    }

    /**
     * @param $key
     * @param $subscriberEmail
     */
    protected function addDopFromCustomizedAttribute($key)
    {
        $dop = $this->getLastDateOfPurchase();
        if ($dop) {
            $this->addSqualoMailTag($key, $dop);
        }
    }

    /**
     * @param $key
     */
    protected function addStoreCodeFromCustomizedAttribute($key)
    {
        $storeCode = Mage::getModel('core/store')->load($this->getStoreId())->getCode();
        $this->addSqualoMailTag($key, $storeCode);
    }

    /**
     * Iterates the squalomail tags.
     *
     * @param $data
     * @param $listId
     * @throws Mage_Core_Exception
     */
    protected function _setSqualomailTagsToCustomer($data)
    {
        $customer = $this->getCustomer();

        foreach($data['merges'] as $key => $value) {
            if (!empty($value)) {
                if (is_array($this->_squaloMailTags)) {
                    if ($key !== 'GROUPINGS') {
                        $this->_setSqualomailTagToCustomer($key, $value, $this->_squaloMailTags, $customer);
                    }
                }
            }
        }

        $customer->save();
    }

    /**
     * Sets the squalomail tag value for tue customer.
     *
     * @param $key
     * @param $value
     * @param $mapFields
     * @param $customer
     */
    protected function _setSqualomailTagToCustomer($key, $value, $mapFields, $customer)
    {
        $ignore = array(
            'billing_company', 'billing_country', 'billing_zipcode', 'billing_state', 'billing_telephone',
            'shipping_company', 'shipping_telephone', 'shipping_country', 'shipping_zipcode', 'shipping_state',
            'dop', 'store_code');

        foreach ($mapFields as $map) {
            if ($map['squalomail'] == $key) {
                if (!in_array($map['magento'], $ignore) && !$this->_isAddress($map['magento'])) {
                    if ($key != 'GENDER') {
                        $customer->setData($map['magento'], $value);
                    } else {
                        $customer->setData('gender', $this->getGenderValue($value));
                    }
                }
            }
        }
    }

    /**
     * @param $attrId
     * @return bool
     */
    protected function _isAddress($attrId)
    {
        if (is_numeric($attrId)) {
            // Gets the magento attr_code.
            $attributeCode = $this->_getAttrbuteCode($attrId);

            if ($attributeCode == 'default_billing' || $attributeCode == 'default_shipping') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $attrId
     * @return string
     */
    protected function _getAttrbuteCode($attrId)
    {
        $attributeCode = Mage::getModel('eav/entity_attribute')->load($attrId)->getAttributeCode();

        return $attributeCode;
    }

    /**
     * @param $attrCode
     * @return int
     */
    protected function _getAttrbuteId($attrCode)
    {
        $attribute = Mage::getModel('eav/entity_attribute')
            ->getCollection()
            ->addFieldToFilter('attribute_code', $attrCode)
            ->getFirstItem();
        $attrId = $attribute->getId();

        return $attrId;
    }

    /**
     * @param $data
     * @return string
     */
    protected function _getFName($data)
    {
        $attrId = $this->_getAttrbuteId('firstname');
        $magentoTag = '';

        foreach ($this->_squaloMailTags as $tag) {
            if ($tag['magento'] == $attrId) {
                $magentoTag = $tag['squalomail'];
                break;
            }
        }

        return $data['merges'][$magentoTag];
    }

    /**
     * @param $data
     * @return string
     */
    protected function _getLName($data)
    {
        $attrId = $this->_getAttrbuteId('lastname');
        $magentoTag = '';

        foreach ($this->_squaloMailTags as $tag) {
            if ($tag['magento'] == $attrId) {
                $magentoTag = $tag['squalomail'];
                break;
            }
        }

        return $data['merges'][$magentoTag];
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function _getInterestGroupHandleModel()
    {
        return $this->_interestGroupHandle;
    }
}

