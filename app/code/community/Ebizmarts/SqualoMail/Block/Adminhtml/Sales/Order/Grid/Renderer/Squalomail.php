<?php
/**
 * sqm-mc-magento1 Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     7/7/16 1:31 PM
 * @file:     Abandoned.php
 */
class Ebizmarts_SqualoMail_Block_Adminhtml_Sales_Order_Grid_Renderer_Squalomail
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $result = '';

        try {
            $order = Mage::getModel('sales/order')
                ->load($row->getData('entity_id'));

            if ($order->getSqualomailAbandonedcartFlag() || $order->getSqualomailCampaignId()) {
                $result = '<img src="'
                    . $this->getSkinUrl("ebizmarts/squalomail/images/logo-freddie-monocolor-200.png")
                    . '" width="40" title="hep hep thanks SqualoMail" />';
            }
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }

        return $result;
    }
}