<?php

/**
 * #REPO_NAME# Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     3/6/18 2:22 PM
 * @file:     Squalomailstores.php
 */
class Ebizmarts_SqualoMail_Block_Adminhtml_Squalomailstores extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'squalomail';
        $this->_controller = 'adminhtml_squalomailstores';
        $this->_headerText = $this->__('Squalomail stores');

        parent::__construct();
    }
}
