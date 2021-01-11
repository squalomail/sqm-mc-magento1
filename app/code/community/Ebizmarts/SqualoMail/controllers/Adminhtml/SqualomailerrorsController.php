<?php

/**
 * #REPO_NAME# Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     6/10/16 12:35 PM
 * @file:     SqualomailerrorsController.php
 */
class Ebizmarts_SqualoMail_Adminhtml_SqualomailerrorsController extends Mage_Adminhtml_Controller_Action
{
    const MAX_RETRIES = 5;

    public function indexAction()
    {
        $this->_title($this->__('Newsletter'))
            ->_title($this->__('SqualoMail'));

        $this->loadLayout();
        $this->_setActiveMenu('newsletter/squalomail');
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }

    public function downloadresponseAction()
    {
        $helper = $this->makeHelper();
        $errorId = $this->getRequest()->getParam('id');
        $error = $this->getSqualomailerrorsModel()->load($errorId);
        $apiBatches = $this->getApiBatches();
        $batchId = $error->getBatchId();
        $storeId = $error->getStoreId();
        $squalomailStoreId = $error->getSqualomailStoreId();

        if ($squalomailStoreId) {
            $enabled = $helper->isEcomSyncDataEnabled($storeId);
        } else {
            $enabled = $helper->isSubscriptionEnabled($storeId);
        }

        if ($enabled) {
            $response = $this->getResponse();
            $response->setHeader('Content-disposition', 'attachment; filename=' . $batchId . '.json');
            $response->setHeader('Content-type', 'application/json');
            $counter = 0;

            do {
                $counter++;
                $files = $apiBatches->getBatchResponse($batchId, $storeId);
                $fileContent = array();
                if (array_key_exists('error', $files)) {
                    $fileContent = $this->__("Response was deleted from SqualoMail server.");
                    break;
                }

                foreach ($files as $file) {
                    $items = $this->getFileContent($file);

                    foreach ($items as $item) {
                        $fileContent[] = array(
                            'status_code' => $item['status_code'],
                            'operation_id' => $item['operation_id'],
                            'response' => json_decode($item['response'])
                        );
                    }

                    $this->unlink($file);
                }

                $baseDir = $apiBatches->getMagentoBaseDir();

                if ($apiBatches->batchDirExists($baseDir, $batchId)) {
                    $apiBatches->removeBatchDir($baseDir, $batchId);
                }
            } while (!count($fileContent) && $counter < self::MAX_RETRIES);

            $response->setBody(json_encode($fileContent, JSON_PRETTY_PRINT));
        }

        return;
    }

    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
        case 'index':
        case 'grid':
        case 'downloadresponse':
            $acl = 'newsletter/squalomail/squalomailerrors';
            break;
        }

        return Mage::getSingleton('admin/session')->isAllowed($acl);
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('squalomail');
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Squalomailerrors
     */
    protected function getSqualomailerrorsModel()
    {
        return Mage::getModel('squalomail/squalomailerrors');
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_Batches
     */
    protected function getApiBatches()
    {
        return Mage::getModel('squalomail/api_batches');
    }

    /**
     * @param string $file
     * @return stdClass
     */
    protected function getFileContent($file)
    {
        $fileContent = $this->getFileHelper()->read($file);

        return json_decode($fileContent, true);
    }

    /**
     * @param string $file
     */
    protected function unlink($file)
    {
        return $this->getFileHelper()->unlink($file);
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_File
     */
    protected function getFileHelper()
    {
        return Mage::helper('squalomail/file');
    }
}
