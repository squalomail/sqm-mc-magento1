<?php

$installer = $this;

Mage::helper('squalomail')
    ->saveSqualoMailConfig(
        array(
            array(
                Ebizmarts_SqualoMail_Model_Config::GENERAL_MIGRATE_FROM_116,
                1)
        ),
        0,
        'default'
    );

$installer->endSetup();
