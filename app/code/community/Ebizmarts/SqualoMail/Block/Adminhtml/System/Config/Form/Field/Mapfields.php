<?php

/**
 * SqualoMail For Magento
 *
 * @category  Ebizmarts_SqualoMail
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     6/28/16 3:55 PM
 * @file:     Mapfields.php
 */
class Ebizmarts_SqualoMail_Block_Adminhtml_System_Config_Form_Field_Mapfields
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_customerAttributes;

    /**
     * @var Ebizmarts_SqualoMail_Helper_Data
     */
    protected $_helper;

    public function __construct()
    {
        $this->_helper = $this->makeHelper();

        $this->addColumn(
            'squalomail',
            array(
            'label' => $this->_helper->__('Squalomail'),
            'style' => 'width:120px',
            )
        );
        $this->addColumn(
            'magento',
            array(
            'label' => $this->_helper->__('Customer'),
            'style' => 'width:120px',
            )
        );
        $this->_addAfter = false;
        parent::__construct();
        $this->setTemplate('ebizmarts/squalomail/system/config/form/field/array_dropdown.phtml');

        $this->_customerAttributes = array();
        $attrSetId = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter(1)
            ->addSetInfo()
            ->getData();

        foreach ($attrSetId as $option) {
            if ($option['frontend_label']) {
                $this->_customerAttributes[$option['attribute_id']] = $option['frontend_label'];
            }
        }

        $scopeArray = $this->_helper->getCurrentScope();
        $mapFields = $this->_helper->getCustomMergeFieldsSerialized($scopeArray['scope_id'], $scopeArray['scope']);
        $customFieldTypes = $this->_helper->unserialize($mapFields);

        if (is_array($customFieldTypes)) {
            foreach ($customFieldTypes as $customFieldType) {
                $label = $customFieldType['label'];
                $value = $customFieldType['value'];
                $this->_customerAttributes[$value] = $label;
            }
        }

        ksort($this->_customerAttributes);
    }

    /**
     * @param $data
     * @return string
     */
    public function escapeQuote($data)
    {
        return $this->getHelper()->sqmEscapeQuote($data);
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    public function getHelper()
    {
        return $this->_helper;
    }

    /**
     * @param string $columnName
     * @return string
     */
    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            Mage::throwException('Wrong column name specified.');
        }

        $column = $this->_columns[$columnName];
        $inputName = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if ($columnName == 'magento') {
            $rendered = '<select name="' . $inputName . '">';
            foreach ($this->_customerAttributes as $att => $name) {
                $rendered .= '<option value="' . $att . '">' . $name . '</option>';
            }

            $rendered .= '</select>';
        } else {
            return
                '<input type="text" maxlength="10" name="'
                . $inputName . '" value="#{' . $columnName . '}" '
                . ($column['size'] ? 'size="' . $column['size'] . '"' : '') . '/>';
        }

        return $rendered;
    }

    /**
     * @return string
     */
    protected function _getSqualoMailValue()
    {
        return Mage::getSingleton('core/session')->getSqualomailValue();
    }

    /**
     * @return string
     */
    protected function _getSqualoMailLabel()
    {
        return Mage::getSingleton('core/session')->getSqualomailLabel();
    }

    /**
     * @return Mage_Core_Helper_Abstract
     */
    protected function makeHelper()
    {
        return Mage::helper('squalomail');
    }
}
