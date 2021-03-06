<?php

/**
 * SqualoMail For Magento
 *
 * @category  Ebizmarts_SqualoMail
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 3:55 PM
 * @file:     Account.php
 */
class Ebizmarts_SqualoMail_Model_System_Config_Source_List
{

    /**
     * Lists for API key will be stored here
     *
     * @access protected
     * @var    array Email lists for given API key
     */
    protected $_lists = array();

    /**
     * @var Ebizmarts_SqualoMail_Helper_Data
     */
    protected $_helper;


    /**
     * Ebizmarts_SqualoMail_Model_System_Config_Source_List constructor.
     *
     * @param  $params
     * @throws Exception
     */
    public function __construct($params)
    {
        $helper = $this->_helper = $this->makeHelper();
        $scopeArray = $helper->getCurrentScope();
        if (empty($this->_lists)) {
            $apiKey = (empty($params))
                ? $helper->getApiKey($scopeArray['scope_id'], $scopeArray['scope'])
                : $params['api_key'];
            if ($apiKey) {
                try {
                    $api = $helper->getApiByKey($apiKey);

                    //Add filter to only show the lists for the selected store when SQM store selected.
                    $sqmStoreId = (!empty($params))
                        ? $params['squalomail_store_id']
                        : $helper->getSQMStoreId($scopeArray['scope_id'], $scopeArray['scope']);
                    if ($sqmStoreId !== '' && $sqmStoreId !== null) {
                        $listId = $helper->getListIdByApiKeyAndSQMStoreId($apiKey, $sqmStoreId);
                        if ($listId !== false) {
                            $this->_lists['lists'][0] = $api->getLists()->getLists($listId);
                        }
                    } else {
                        $this->_lists = $api->getLists()->getLists(null, 'lists');
                    }

                    if (isset($this->_lists['lists']) && count($this->_lists['lists']) == 0) {
                        $message = 'Please create an audience in your SqualoMail application.';
                        Mage::getSingleton('adminhtml/session')->addWarning($message);
                    }
                } catch (Ebizmarts_SqualoMail_Helper_Data_ApiKeyException $e) {
                    $helper->logError($e->getMessage());
                } catch (SqualoMail_Error $e) {
                    $helper->logError($e->getFriendlyMessage());
                }
            }
        }
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = $this->getHelper();
        $lists = array();
        $sqmLists = $this->getSQMLists();
        if (isset($sqmLists['lists'])) {
            if (count($sqmLists['lists']) > 1) {
                $lists[] = array('value' => '', 'label' => $helper->__('--- Select a Squalomail Audience ---'));
            }

            foreach ($sqmLists['lists'] as $list) {
                $memberCount = $list['stats']['member_count'];
                $memberText = $helper->__('members');
                $label = $list['name'] . ' (' . $memberCount . ' ' . $memberText . ')';
                $lists[] = array('value' => $list['id'], 'label' => $label);
            }
        } else {
            $lists[] = array('value' => '', 'label' => $helper->__('--- No data ---'));
        }

        return $lists;
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    protected function getHelper()
    {
        return $this->_helper;
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('squalomail');
    }

    /**
     * @return array|mixed
     */
    protected function getSQMLists()
    {
        return $this->_lists;
    }
}
