<?php
/**
 * squalomail-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   #PAC4#
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/2/16 3:59 PM
 * @file:     Ecommerce.php
 */
class SqualoMail_Ecommerce extends SqualoMail_Abstract
{
    /**
     * @var SqualoMail_EcommerceStore
     */
    public $stores;
    /**
     * @var SqualoMail_EcommerceCarts
     */
    public $carts;
    /**
     * @var SqualoMail_EcommerceCustomers
     */
    public $customers;
    /**
     * @var SqualoMail_EcommerceOrders
     */
    public $orders;
    /**
     * @var SqualoMail_EcommerceProducts
     */
    public $products;
    /**
     * @var SqualoMail_EcommercePromoRules
     */
    public $promoRules;

    /**
     * @return SqualoMail_EcommerceStore
     */
    public function getStores()
    {
        return $this->stores;
    }

    /**
     * @return SqualoMail_EcommerceCarts
     */
    public function getCarts()
    {
        return $this->carts;
    }

    /**
     * @return SqualoMail_EcommerceCustomers
     */
    public function getCustomers()
    {
        return $this->customers;
    }

    /**
     * @return SqualoMail_EcommerceOrders
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * @return SqualoMail_EcommerceProducts
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @return SqualoMail_EcommercePromoRules
     */
    public function getPromoRules()
    {
        return $this->promoRules;
    }
}
