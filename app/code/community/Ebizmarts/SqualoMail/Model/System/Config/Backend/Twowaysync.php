<?php

/**
 * sqm-mc-magento1 Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     8/4/16 5:56 PM
 * @file:     Apikey.php
 */
class Ebizmarts_SqualoMail_Model_System_Config_Backend_Twowaysync extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        $helper = $this->makeHelper();
        $groups = $this->getData('groups');
        $moduleIsActive = (isset($groups['general']['fields']['active']['value']))
            ? $groups['general']['fields']['active']['value']
            : $helper->isSqualoMailEnabled($this->getScopeId(), $this->getScope());
        $apiKey = (isset($groups['general']['fields']['apikey']['value']))
            ? $groups['general']['fields']['apikey']['value']
            : $helper->getApiKey($this->getScopeId(), $this->getScope());
        $listId = $helper->getGeneralList($this->getScopeId(), $this->getScope());
        if ($apiKey && $moduleIsActive && $listId && $this->isValueChanged()) {
            $helper->handleWebhookChange($this->getScopeId(), $this->getScope());
        }
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('squalomail');
    }
}
