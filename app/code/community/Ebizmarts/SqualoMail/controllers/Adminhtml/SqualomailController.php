<?php

/**
 * #REPO_NAME# Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/27/16 1:50 PM
 * @file:     EcommerceController.php
 */
class Ebizmarts_SqualoMail_Adminhtml_SqualomailController extends Mage_Adminhtml_Controller_Action
{

    protected $_helper;
    protected $_webhookHelper;

    public function preDispatch()
    {
        $this->_helper = Mage::helper('squalomail');
        $this->_webhookHelper = Mage::helper('squalomail/webhook');
        return parent::preDispatch();
    }

    public function indexAction()
    {
        $customerId = (int)$this->getRequest()->getParam('id');

        if ($customerId) {
            $block = $this->getLayout()
                ->createBlock(
                    'squalomail/adminhtml_customer_edit_tab_squalomail',
                    'admin.customer.squalomail'
                )
                ->setCustomerId($customerId)
                ->setUseAjax(true);
            $html = $this->getHtml($block);
            $this->getResponse()->setBody($html);
        }
    }

    public function resendSubscribersAction()
    {
        $helper = $this->getHelper();
        $mageApp = $helper->getMageApp();
        $request = $mageApp->getRequest();
        $scope = $request->getParam('scope');
        $scopeId = $request->getParam('scope_id');
        $success = 1;

        try {
            $helper->resendSubscribers($scopeId, $scope);
        } catch (Exception $e) {
            $success = 0;
        }

        $mageApp->getResponse()->setBody($success);
    }

    public function createWebhookAction()
    {
        $helper = $this->getHelper();
        $webhookHelper = $this->getWebhookHelper();
        $mageApp = $helper->getMageApp();
        $request = $mageApp->getRequest();
        $scope = $request->getParam('scope');
        $scopeId = $request->getParam('scope_id');
        $listId = $helper->getGeneralList($scopeId);

        $message = $webhookHelper->createNewWebhook($scopeId, $scope, $listId);

        $mageApp->getResponse()->setBody($message);
    }

    public function getStoresAction()
    {
        $helper = $this->getHelper();
        $apiKey = $this->getRequest()->getParam('api_key');

        if ($helper->isApiKeyObscure($apiKey)) {
            $apiKey = $this->getApiKeyValue();
        }

        $data = $this->getSourceStoreOptions($apiKey);
        $jsonData = json_encode($data);

        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody($jsonData);
    }

    public function getInfoAction()
    {
        $helper = $this->getHelper();
        $request = $this->getRequest();
        $sqmStoreId = $request->getParam('squalomail_store_id');
        $apiKey = $request->getParam('api_key');

        if ($helper->isApiKeyObscure($apiKey)) {
            $apiKey = $this->getApiKeyValue();
        }

        $data = $this->getSourceAccountInfoOptions($apiKey, $sqmStoreId);

        foreach ($data as $key => $element) {
            $liElement = '';

            if ($element['value'] == Ebizmarts_SqualoMail_Model_System_Config_Source_Account::SYNC_LABEL_KEY) {
                $liElement = $helper->getSyncFlagDataHtml($element, $liElement);
                $data[$key]['label'] = $liElement;
            }
        }

        $jsonData = json_encode($data);

        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody($jsonData);
    }

    public function getListAction()
    {
        $helper = $this->getHelper();
        $request = $this->getRequest();
        $apiKey = $request->getParam('api_key');
        $sqmStoreId = $request->getParam('squalomail_store_id');

        if ($helper->isApiKeyObscure($apiKey)) {
            $apiKey = $this->getApiKeyValue();
        }

        $data = $this->getSourceListOptions($apiKey, $sqmStoreId);
        $jsonData = json_encode($data);

        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody($jsonData);
    }

    public function getInterestAction()
    {
        $helper = $this->getHelper();
        $request = $this->getRequest();
        $apiKey = $request->getParam('api_key');
        $listId = $request->getParam('list_id');

        if ($helper->isApiKeyObscure($apiKey)) {
            $apiKey = $this->getApiKeyValue();
        }

        $data = $this->getSourceInterestOptions($apiKey, $listId);
        $jsonData = json_encode($data);

        $response = $this->getResponse();
        $response->setHeader('Content-type', 'application/json');
        $response->setBody($jsonData);
    }

    /**
     * @param $squalomailStoreId
     * @return mixed
     * @throws Mage_Core_Exception
     */
    protected function _getDateSync($squalomailStoreId)
    {
        return $this->makeHelper()
            ->getConfigValueForScope(
                Ebizmarts_SqualoMail_Model_Config::ECOMMERCE_SYNC_DATE . "_$squalomailStoreId",
                0,
                'default'
            );
    }

    /**
     * @return mixed
     */
    protected function _isAllowed()
    {
        $acl = null;
        switch ($this->getRequest()->getActionName()) {
        case 'index':
        case 'resendSubscribers':
        case 'createWebhook':
        case 'getStores':
        case 'getList':
        case 'getInfo':
        case 'getInterest':
            $acl = 'system/config/squalomail';
            break;
        }

        return Mage::getSingleton('admin/session')->isAllowed($acl);
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    protected function getHelper()
    {
        return $this->_helper;
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Webhook
     */
    protected function getWebhookHelper()
    {
        return $this->_webhookHelper;
    }

    /**
     * @param $block
     * @return mixed
     */
    protected function getHtml($block)
    {
        return $block->toHtml();
    }

    /**
     * @param $apiKey
     * @return Ebizmarts_SqualoMail_Model_System_Config_Source_Store
     */
    protected function getSourceStoreOptions($apiKey)
    {
        return Mage::getModel(
            'Ebizmarts_SqualoMail_Model_System_Config_Source_Store',
            array('api_key' => $apiKey)
        )->toOptionArray();
    }

    /**
     * @param $apiKey
     * @param $sqmStoreId
     * @return Ebizmarts_SqualoMail_Model_System_Config_Source_Account
     */
    protected function getSourceAccountInfoOptions($apiKey, $sqmStoreId)
    {
        return Mage::getModel(
            'Ebizmarts_SqualoMail_Model_System_Config_Source_Account',
            array('api_key' => $apiKey, 'squalomail_store_id' => $sqmStoreId)
        )->toOptionArray();
    }

    /**
     * @param $apiKey
     * @param $sqmStoreId
     * @return Ebizmarts_SqualoMail_Model_System_Config_Source_List
     */
    protected function getSourceListOptions($apiKey, $sqmStoreId)
    {
        return Mage::getModel(
            'Ebizmarts_SqualoMail_Model_System_Config_Source_List',
            array('api_key' => $apiKey, 'squalomail_store_id' => $sqmStoreId)
        )->toOptionArray();
    }

    /**
     * @param $apiKey
     * @param $listId
     * @return Ebizmarts_SqualoMail_Model_System_Config_Source_CustomerGroup
     */
    protected function getSourceInterestOptions($apiKey, $listId)
    {
        return Mage::getModel(
            'Ebizmarts_SqualoMail_Model_System_Config_Source_CustomerGroup',
            array('api_key' => $apiKey, 'list_id' => $listId)
        )->toOptionArray();
    }

    /**
     * @return string
     */
    protected function getApiKeyValue()
    {
        $helper = $this->getHelper();
        $scopeArray = $helper->getCurrentScope();

        return $helper->getApiKey($scopeArray['scope_id'], $scopeArray['scope']);
    }
}
