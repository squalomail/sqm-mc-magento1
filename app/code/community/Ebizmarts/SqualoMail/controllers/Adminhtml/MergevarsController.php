<?php

/**
 * sqm-mc-magento1 Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @file:     MergevarsController.php
 */
class Ebizmarts_SqualoMail_Adminhtml_MergevarsController extends Mage_Adminhtml_Controller_Action
{

    public function addmergevarAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function saveaddAction()
    {
        $postData = $this->getRequest()->getPost('mergevar', array());
        $label = $postData['label'];
        $value = $postData['value'];
        $fieldType = $postData['fieldtype'];
        $helper = $this->makeHelper();
        $scopeArray = $helper->getCurrentScope();
        $blankSpacesAmount = (count(explode(' ', $value)) - 1);

        if (is_numeric($value)) {
            Mage::getSingleton('adminhtml/session')
                ->addError(
                    $this->__(
                        'There was an error processing the new field. '
                        . 'SqualoMail tag value can not be numeric.'
                    )
                );
        } elseif ($helper->customMergeFieldAlreadyExists($value, $scopeArray['scope_id'], $scopeArray['scope'])) {
            Mage::getSingleton('adminhtml/session')
                ->addError(
                    $this->__(
                        'There was an error processing the new field. '
                        . 'SqualoMail tag value already exists.'
                    )
                );
        } elseif ($blankSpacesAmount > 0) {
            Mage::getSingleton('adminhtml/session')
                ->addError(
                    $this->__(
                        'There was an error processing the new field. '
                        . 'SqualoMail tag value can not contain blank spaces.'
                    )
                );
        } else {
            $customMergeFields = $helper->getCustomMergeFields($scopeArray['scope_id'], $scopeArray['scope']);
            $customMergeFields[] = array('label' => $label, 'value' => $value, 'field_type' => $fieldType);
            $configValues = array(
                array(
                    Ebizmarts_SqualoMail_Model_Config::GENERAL_CUSTOM_MAP_FIELDS, $helper->serialize($customMergeFields)
                )
            );
            $helper->saveSqualomailConfig($configValues, $scopeArray['scope_id'], $scopeArray['scope']);
            Mage::getSingleton('core/session')->setSqualoMailValue($value);
            Mage::getSingleton('core/session')->setSqualoMailLabel($label);
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The custom value was added successfully.'));
        }

        $this->_redirect("*/*/addmergevar");
    }

    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
        case 'addmergevar':
        case 'saveadd':
            $acl = 'system/config/squalomail';
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
}
