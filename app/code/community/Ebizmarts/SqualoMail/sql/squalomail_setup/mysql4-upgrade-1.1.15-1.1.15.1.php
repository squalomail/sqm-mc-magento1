<?php

$installer = $this;

try {
    $installer->run(
        "
ALTER TABLE `{$this->getTable('squalomail_sync_batches')}`
ADD INDEX `idx_status_store_id` (`status`,`store_id`);
"
    );
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'SqualoMail_Errors.log', true);
}


$installer->endSetup();
