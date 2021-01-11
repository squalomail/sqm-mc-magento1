<?php
/**
 * #REPO_NAME# Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @file:     Edit.php
 */
class Ebizmarts_SqualoMail_Block_Adminhtml_Squalomailstores_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_squalomailstores';
        $this->_blockGroup = 'squalomail';

        parent::__construct();

        $this->removeButton('reset');
        $this->updateButton(
            'delete', null, array(
            'label'     => Mage::helper('adminhtml')->__('Delete Store'),
            'class'     => 'delete',
            'onclick'   => 'deleteSQMStoreConfirm(\''
                . Mage::helper('core')->jsQuoteEscape(
                    Mage::helper('adminhtml')->__('Are you sure you want to delete this Squalomail store?')
                )
                .'\', \''
                . $this->getDeleteUrl()
                . '\')',
            'sort_order' => 0
            )
        );

        $scopeArray = $this->getScopeArrayIfValueExists();

        if ($scopeArray !== false) {
            $jsCondition = 'true';
        } else {
            $jsCondition = 'false';
        }

        $sqmInUseMessage = $this->getSQMInUseMessage($scopeArray);
        $this->_formScripts[] = "function deleteSQMStoreConfirm(message, url) {
            if ($jsCondition) {
                if (confirm(message)) {
                    deleteConfirm('$sqmInUseMessage', url);
                }
            } else {
                deleteConfirm(message, url);
            }
        }";
    }

    public function getStoreId()
    {
        return Mage::registry('current_store')->getId();
    }

    public function getHeaderText()
    {
        if (Mage::registry('current_squalomailstore')->getId()) {
            return $this->escapeHtml(Mage::registry('current_squalomailstore')->getName());
        } else {
            return Mage::helper('squalomail')->__('New Store');
        }
    }

    protected function _prepareLayout()
    {
        $headBlock = Mage::app()->getLayout()->getBlock('head');
        $headBlock->addJs('ebizmarts/squalomail/editstores.js');
        return parent::_prepareLayout();
    }

    /**
     * @param $scope
     * @return string
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function getSQMInUseMessage($scope)
    {
        $helper = $this->makeHelper();
        if ($scope !== false) {
            $scopeName = $helper->getScopeName($scope);
            $message = $helper->__(
                "This store is currently in use for this Magento store at %s scope. Do you want to proceed anyways?",
                $scopeName
            );
        } else {
            $message = $helper->__(
                "This store is currently in use for this Magento store. Do you want to proceed anyways?"
            );
        }

        return $message;
    }

    /**
     * @return array
     */
    protected function getScopeArrayIfValueExists()
    {
        $helper = $this->makeHelper();
        $currentSQMStoreId = Mage::registry('current_squalomailstore')->getStoreid();
        $keyIfExist = $helper->getScopeBySqualoMailStoreId($currentSQMStoreId);

        if ($keyIfExist === null) {
            $keyIfExist = false;
        }

        return $keyIfExist;
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('squalomail');
    }
}
