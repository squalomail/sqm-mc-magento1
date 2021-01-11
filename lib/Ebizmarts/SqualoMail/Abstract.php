<?php
/**
 * squalomail-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   #PAC4#
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 4:22 PM
 * @file:     Ecommerce.php
 */
class SqualoMail_Abstract
{
    /**
     * @var Squalomail
     */
    protected $_master;

    /**
     * SqualoMail_Abstract constructor.
     *
     * @param Ebizmarts_SqualoMail $m
     */
    public function __construct(Ebizmarts_SqualoMail $m)
    {
        $this->_master = $m;
    }
}
