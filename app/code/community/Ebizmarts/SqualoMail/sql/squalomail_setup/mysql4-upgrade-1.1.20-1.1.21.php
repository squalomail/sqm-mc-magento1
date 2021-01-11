<?php

$installer = $this;

try {
    $webhookData = array();

    /* Check if webhook is created */
    $configDataCollection = Mage::getModel('core/config_data')
        ->getCollection()
        ->addFieldToFilter('path', 'squalomail/general/webhook_id');

    /* If webhook is created, edites it and place the new "event" variable */
    if ($configDataCollection->getSize()) {
        // Sets the migration flag to edit webhooks.
        Mage::helper('squalomail')
            ->saveSqualoMailConfig(
                array(
                    array(
                        Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_FROM_1120,
                        1)
                ),
                0,
                'default'
            );
    }
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'SqualoMail_Errors.log', true);
}

$installer->endSetup();
