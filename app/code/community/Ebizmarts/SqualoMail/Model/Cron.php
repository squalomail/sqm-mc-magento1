<?php
/**
 * @category Ebizmarts
 * @package #PAC3#
 * @author Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Cron processor class
 */
class Ebizmarts_MailChimp_Model_Cron
{
    /**
     * @var Ebizmarts_MailChimp_Helper_Data
     */
    protected $_squaloMailHelper;
    /**
     * @var Ebizmarts_MailChimp_Helper_Migration
     */
    protected $_squaloMailMigrationHelper;

    public function __construct()
    {
        $this->_squaloMailHelper = Mage::helper('squalomail');
        $this->_squaloMailMigrationHelper = Mage::helper('squalomail/migration');
    }

    public function syncEcommerceBatchData()
    {
        if ($this->getMigrationHelper()->migrationFinished()) {
            Mage::getModel('squalomail/api_batches')->handleEcommerceBatches();
        } else {
            $this->getMigrationHelper()->handleMigrationUpdates();
        }
    }

    public function syncSubscriberBatchData()
    {
        Mage::getModel('squalomail/api_batches')->handleSubscriberBatches();
    }

    public function processWebhookData()
    {
        Mage::getModel('squalomail/processWebhook')->processWebhookData();
    }

    public function deleteWebhookRequests()
    {
        Mage::getModel('squalomail/processWebhook')->deleteProcessed();
    }

    public function clearEcommerceData()
    {
        Mage::getModel('squalomail/clearEcommerce')->clearEcommerceData();
    }

    protected function getHelper()
    {
        return $this->_squaloMailHelper;
    }

    protected function getMigrationHelper()
    {
        return $this->_squaloMailMigrationHelper;
    }
}
