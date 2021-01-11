<?php
/**
 * #REPO_NAME# Magento Component
 *
 * @category  Ebizmarts
 * @package   #PAC1#
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
    CREATE TABLE IF NOT EXISTS `{$this->getTable('squalomail_errors')}` (
        `id`     INT(10) unsigned NOT NULL auto_increment,
        `type`   VARCHAR(256) DEFAULT '',
        `title`  VARCHAR(128) DEFAULT '',
        `status` INT(5) DEFAULT 0,
        `errors` TEXT,
        `regtype` CHAR(3) DEFAULT '',
        `original_id` INT(10) DEFAULT 0,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
"
);

try {
    $installer->run(
        "
      ALTER TABLE `{$this->getTable('squalomail_sync_batches')}` MODIFY `store_id` VARCHAR(50) NOT NULL;
    "
    );
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}

$installer->endSetup();
