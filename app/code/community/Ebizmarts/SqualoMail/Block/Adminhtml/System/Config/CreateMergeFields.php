<?php
/**
 * sqm-mc-magento1 Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     30/8/16 1:02 PM
 * @file:     CreateMergeFields.php
 */
class Ebizmarts_SqualoMail_Block_Adminhtml_System_Config_CreateMergeFields
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    const CREATE_MERGE_PATH = 'adminhtml/ecommerce/createMergeFields';

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ebizmarts/squalomail/system/config/createmergefields.phtml');
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(
                array(
                'id' => 'createmergefields_button',
                'label' => $this->helper('squalomail')->__('Create Merge Fields'),
                'onclick' => 'javascript:createMergeFields(); return false;'
                )
            );

        return $button->toHtml();
    }
    public function getAjaxCheckUrl()
    {
        $helper = $this->makeHelper();
        $scopeArray = $helper->getCurrentScope();
        return Mage::helper('adminhtml')->getUrl(self::CREATE_MERGE_PATH, $scopeArray);
    }

    /**
     * @return string
     */
    public function getMessageForSqualomailErrorLog()
    {
        $helper = $this->makeHelper();
        $message =
            'There was an error on the merge fields creation. '
            . 'Please check the SqualoMail_Errors.log file for more information.';
        if (!$helper->isErrorLogEnabled()) {
            $message =
                'There was an error on the merge fields creation. '
                . 'Please enable the error logs and try again for more information.';
        }

        return $helper->__($message);
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('squalomail');
    }
}
