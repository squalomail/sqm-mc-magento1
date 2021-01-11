<?php
/**
 * #REPO_NAME# Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     6/9/16 4:05 PM
 * @file:     mysql4-upgrade-1.0.1-1.0.2.php
 */

$installer = $this;

$installer->startSetup();

$installer->run(
    "
    CREATE TABLE IF NOT EXISTS `{$this->getTable('squalomail_ecommerce_sync_data')}` (
        `id`     INT(10) unsigned NOT NULL auto_increment,
        `related_id` INT(10) DEFAULT 0,
        `type` VARCHAR(3) NOT NULL,
        `squalomail_store_id`  VARCHAR(50) NOT NULL DEFAULT '',
        `squalomail_sync_error` VARCHAR(255) NOT NULL DEFAULT '',
        `squalomail_sync_delta` DATETIME NOT NULL,
        `squalomail_sync_modified` INT(1) NOT NULL DEFAULT 0,
        `squalomail_sync_deleted` INT(1) NOT NULL DEFAULT 0,
        `squalomail_token` VARCHAR(32) NOT NULL DEFAULT '',
        PRIMARY KEY  (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
"
);

try {
    $installer->run(
        "
 ALTER TABLE `{$this->getTable('squalomail_errors')}`
 ADD column `store_id` INT(5) DEFAULT 0;
 "
    );
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'SqualoMail_Errors.log', true);
}

Mage::helper('squalomail')
    ->saveSqualoMailConfig(
        array(
            array(
                Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_FROM_115,
                1)
        ),
        0,
        'default'
    );


$installer->endSetup();
