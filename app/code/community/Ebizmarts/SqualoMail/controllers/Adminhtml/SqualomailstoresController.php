<?php

/**
 * #REPO_NAME# Magento Component
 *
 * @category  Ebizmarts
 * @package   #PAC1#
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @file:     SqualomailstoresController.php
 */
class Ebizmarts_SqualoMail_Adminhtml_SqualomailstoresController extends Mage_Adminhtml_Controller_Action
{

    /**
     * @var Ebizmarts_SqualoMail_Helper_Data
     */
    protected $_helper;

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('newsletter')
            ->_addBreadcrumb($this->__('Newsletter'), $this->__('Squalomail Store'));

        return $this;
    }

    public function indexAction()
    {
        $this->_loadStores();
        $this->_title($this->__('Newsletter'))
            ->_title($this->__('Squalomail'));

        $this->loadLayout();
        $this->_setActiveMenu('newsletter/squalomail');
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }

    protected function _initStore($idFieldName = 'id')
    {
        $this->_title($this->__('Squalomail Stores'))->_title($this->__('Manage Squalomail Stores'));
        $storeId = (int)$this->getRequest()->getParam($idFieldName);

        if ($storeId) {
            $store = $this->loadSqualomailStore($storeId);
            $this->sessionregisterStore($store);
        }

        return $this;
    }

    public function editAction()
    {
        $this->_title($this->__('Squalomail'))->_title($this->__('Squalomail Store'));
        $id = $this->getRequest()->getParam('id');
        $squalomailStore = $this->loadSqualomailStore($id);
        $this->sessionregisterStore($squalomailStore);
        $title = $id ? $this->__('Edit Store') : $this->__('New Store');
        $this->_initAction();

        $block = $this->getLayout()
            ->createBlock('squalomail/adminhtml_squalomailstores_edit')
            ->setData('action', $this->getUrl('*/*/save'));

        $this->_addBreadcrumb($title, $title)
            ->_addContent($block)
            ->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function saveAction()
    {
        $isPost = $this->getRequest()->getPost();

        if ($isPost) {
            $isPost['apikey'] = $this->getSqualomailHelper()->decryptData($isPost['apikey']);
            $this->_updateSqualomail($isPost);
        }

        $this->_redirect('*/*/index');
    }

    protected function _updateSqualomail($formData)
    {
        $helper = $this->getSqualomailHelper();
        $address = $this->createAddressArray($formData);
        $emailAddress = $formData['email_address'];
        $currencyCode = $formData['currency_code'];
        $primaryLocale = $formData['primary_locale'];
        $timeZone = $formData['timezone'];
        $phone = $formData['phone'];
        $name = $formData['name'];
        $domain = $formData['domain'];
        $storeId = isset($formData['storeid']) ? $formData['storeid'] : null;
        $apiKey = $formData['apikey'];

        if ($helper->isApiKeyObscure($apiKey)) {
            $apiKey = $helper->getApiKey($storeId);
        }

        if ($storeId) {
            $apiStore = $helper->getApiStores();
            $apiStore->editSqualoMailStore(
                $storeId,
                $apiKey,
                $name,
                $currencyCode,
                $domain,
                $emailAddress,
                $primaryLocale,
                $timeZone,
                $phone,
                $address
            );
        } else {
            $apiStore = $helper->getApiStores();
            $apiStore->createSqualoMailStore(
                $apiKey,
                $formData['listid'],
                $name,
                $currencyCode,
                $domain,
                $emailAddress,
                $primaryLocale,
                $timeZone,
                $phone,
                $address
            );
        }
    }

    protected function _loadStores()
    {
        $helper = $this->getSqualomailHelper();
        $allApiKeys = $helper->getAllApiKeys();
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName('squalomail/stores');
        $connection->delete($tableName);

        foreach ($allApiKeys as $apiKey) {
            try {
                $api = $helper->getApiByKey($apiKey);
            } catch (Ebizmarts_SqualoMail_Helper_Data_ApiKeyException $e) {
                $helper->logError($e->getMessage());
                continue;
            }

            try {
                $root = $api->getRoot()->info();
                $stores = $api->getEcommerce()->getStores()->get(null, null, null, 100);
            } catch (SqualoMail_Error $e) {
                $helper->logError($e->getFriendlyMessage());
                continue;
            } catch (Exception $e) {
                $helper->logError($e->getMessage());
                continue;
            }

            $apiKey = $helper->encryptData($apiKey);

            foreach ($stores['stores'] as $store) {
                if ($store['platform'] == 'Magento') {
                    try {
                        $list = $api->getLists()->getLists($store['list_id']);
                    } catch (SqualoMail_Error $e) {
                        $helper->logError($e->getFriendlyMessage());
                        continue;
                    } catch (Exception $e) {
                        $helper->logError($e->getMessage());
                        continue;
                    }

                    $this->_saveStoreData($apiKey, $store, $root, $list);
                }
            }
        }
    }

    /**
     * @param $apiKey
     * @param $store
     * @param $root
     * @param $list
     */
    protected function _saveStoreData($apiKey, $store, $root, $list)
    {
        $storeData = Mage::getModel('squalomail/stores');
        $storeData->setApikey($apiKey)
            ->setStoreid($store['id'])
            ->setListid($store['list_id'])
            ->setName($store['name'])
            ->setPlatform($store['platform'])
            ->setIsSync($store['is_syncing'])
            ->setEmailAddress($store['email_address'])
            ->setCurrencyCode($store['currency_code'])
            ->setMoneyFormat($store['money_format'])
            ->setPrimaryLocale($store['primary_locale'])
            ->setTimezone($store['timezone'])
            ->setPhone($store['phone'])
            ->setAddressAddressOne($store['address']['address1'])
            ->setAddressAddressTwo($store['address']['address2'])
            ->setAddressCity($store['address']['city'])
            ->setAddressProvince($store['address']['province'])
            ->setAddressProvinceCode($store['address']['province_code'])
            ->setAddressPostalCode($store['address']['postal_code'])
            ->setAddressCountry($store['address']['country'])
            ->setAddressCountryCode($store['address']['country_code'])
            ->setDomain($store['domain'])
            ->setMcAccountName($root['account_name'])
            ->setListName(key_exists('name', $list) ? $list['name'] : '')
            ->save();
    }

    public function getstoresAction()
    {
        $helper = $this->getSqualomailHelper();
        $apiKey = $helper->decryptData($this->getRequest()->getParam('api_key'));

        try {
            $api = $helper->getApiByKey($apiKey);
            $lists = $api->getLists()->getLists();
            $data = array();

            foreach ($lists['lists'] as $list) {
                $data[$list['id']] = array('id' => $list['id'], 'name' => $list['name']);
            }
        } catch (Ebizmarts_SqualoMail_Helper_Data_ApiKeyException $e) {
            $data = array('error' => 1, 'message' => $e->getMessage());
            $helper->logError($e->getMessage());
        } catch (SqualoMail_Error $e) {
            $data = array('error' => 1, 'message' => $e->getFriendlyMessage());
            $helper->logError($e->getFriendlyMessage());
        } catch (Exception $e) {
            $data = array('error' => 1, 'message' => $e->getMessage());
            $helper->logError($e->getMessage());
        }

        $jsonData = json_encode($data);
        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody($jsonData);
    }

    public function deleteAction()
    {
        $helper = $this->getSqualomailHelper();
        $id = $this->getRequest()->getParam('id');
        $store = $this->loadSqualomailStore($id);
        $squalomailStoreId = $store->getStoreid();
        $apiKey = $helper->decryptData($store->getApikey());

        if ($store->getId()) {
            try {
                $apiStore = $helper->getApiStores();
                $apiStore->deleteSqualoMailStore($squalomailStoreId, $apiKey);
                $helper->deleteAllMCStoreData($squalomailStoreId);
            } catch (Ebizmarts_SqualoMail_Helper_Data_ApiKeyException $e) {
                $helper->logError($e->getMessage());
            } catch (SqualoMail_Error $e) {
                $helper->logError($e->getFriendlyMessage());
            } catch (Exception $e) {
                $helper->logError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    protected function _isAllowed()
    {
        $acl = '';
        switch ($this->getRequest()->getActionName()) {
        case 'index':
        case 'grid':
        case 'edit':
        case 'new':
        case 'save':
        case 'getstores':
        case 'delete':
            $acl = 'newsletter/squalomail/squalomailstores';
            break;
        }

        return Mage::getSingleton('admin/session')->isAllowed($acl);
    }

    /**
     * @param $store
     * @throws Mage_Core_Exception
     */
    protected function sessionregisterStore($store)
    {
        Mage::register('current_squalomailstore', $store);
    }

    /**
     * @param $id
     * @return Ebizmarts_SqualoMail_Model_Stores
     */
    protected function loadSqualomailStore($id)
    {
        return Mage::getModel('squalomail/stores')->load($id);
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    protected function getSqualomailHelper()
    {
        if ($this->_helper === null) {
            $this->_helper = Mage::helper('squalomail');
        }

        return $this->_helper;
    }

    /**
     * @param $formData
     * @return array
     */
    protected function createAddressArray($formData)
    {
        $address = array();
        $address['address1'] = $formData['address_address_one'];
        $address['address2'] = $formData['address_address_two'];
        $address['city'] = $formData['address_city'];
        $address['province'] = '';
        $address['province_code'] = '';
        $address['postal_code'] = $formData['address_postal_code'];
        $address['country'] = '';
        $address['country_code'] = $formData['address_country_code'];

        return $address;
    }
}
