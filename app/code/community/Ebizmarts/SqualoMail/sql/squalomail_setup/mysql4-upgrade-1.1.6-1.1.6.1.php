<?php

$installer = $this;

try {
    $installer->run(
        "
ALTER TABLE `{$this->getTable('squalomail_ecommerce_sync_data')}`
ADD INDEX `squalomail_store_id` (`squalomail_store_id`), ADD INDEX `related_id` (`related_id`);
"
    );
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'MailChimp_Errors.log', true);
}

$installer->endSetup();
