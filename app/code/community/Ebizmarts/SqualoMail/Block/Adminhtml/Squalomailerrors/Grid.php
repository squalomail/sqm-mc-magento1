<?php
/**
 * #REPO_NAME# Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     6/10/16 12:38 AM
 * @file:     Grid.php
 */
class Ebizmarts_SqualoMail_Block_Adminhtml_Squalomailerrors_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('squalomail_squalomailerrors_grid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('squalomail/squalomailerrors')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'title',
            array(
            'header' => Mage::helper('squalomail')->__('Title'),
            'index' => 'title',
            'sortable' => true
            )
        );
        $this->addColumn(
            'status',
            array(
            'header' => Mage::helper('squalomail')->__('Status'),
            'index' => 'status',
            'width' => '100px',
            'sortable' => true
            )
        );
        $this->addColumn(
            'regtype',
            array(
            'header' => Mage::helper('squalomail')->__('Reg Type'),
            'index' => 'regtype',
            'width' => '100px',
            'sortable' => true
            )
        );
        $this->addColumn(
            'store_id',
            array(
                'header' => Mage::helper('squalomail')->__('Store Id'),
                'index' => 'store_id',
                'sortable' => false
            )
        );
        $this->addColumn(
            'errors',
            array(
                'header' => Mage::helper('squalomail')->__('Error'),
                'index'  => 'errors',
                'sortable' => false
            )
        );
        $this->addColumn(
            'batch_id',
            array(
                'header' => Mage::helper('squalomail')->__('Batch ID'),
                'index'  => 'batch_id',
                'sortable' => false
            )
        );
        $this->addColumn(
            'action_donwload',
            array(
                'header'   => $this->helper('squalomail')->__('Download Response'),
                'width'    => 15,
                'sortable' => false,
                'filter'   => false,
                'type'     => 'action',
                'getter'   => 'getId',
                'actions'  => array(
                    array(
                        'url'     => array('base'=> '*/*/downloadresponse'),
                        'caption' => $this->helper('squalomail')->__('Download'),
                        'field'   => 'id'
                    ),
                )
            )
        );
        $this->addColumn(
            'original_id',
            array(
            'header' => Mage::helper('squalomail')->__('Original'),
            'index' => 'original_id',
            'sortable' => false,
            'renderer' => 'squalomail/adminhtml_squalomailerrors_link'
            )
        );
        $this->addColumn(
            'created_at',
            array(
                'header' => Mage::helper('squalomail')->__('Created At'),
                'index' => 'created_at',
                'sortable' => true,
            )
        );

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return false;
    }
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
}
