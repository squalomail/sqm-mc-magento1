<?php

$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('newsletter_subscriber'),
    'subscriber_firstname',
    'varchar(50)'
);

$installer->getConnection()->addColumn(
    $installer->getTable('newsletter_subscriber'),
    'subscriber_lastname',
    'varchar(50)'
);

$installer->run(
    "
    CREATE TABLE IF NOT EXISTS `{$this->getTable('squalomail_sync_batches')}` (
        `id` int(10) unsigned NOT NULL auto_increment,
        `store_id` int(11) NOT NULL,
        `batch_id` varchar(100) NOT NULL,
        `status` varchar(10) NOT NULL,
        PRIMARY KEY  (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
"
);

$baseDir = Mage::getBaseDir();

try {
    /**
     * @var $fileHelper Ebizmarts_SqualoMail_Helper_File
     */
    $fileHelper = Mage::helper('squalomail/file');
    $fileHelper->mkDir($baseDir . DS . 'var' . DS . 'squalomail');
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'SqualoMail_Errors.log', true);
}

$installer->endSetup();
