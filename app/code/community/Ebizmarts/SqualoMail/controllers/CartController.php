<?php
/**
 * sqm-mc-magento1 Magento Component
 *
 * @category  Ebizmarts
 * @package   mc-magento
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     7/6/16 10:14 AM
 * @file:     CartController.php
 */

require_once Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'CartController.php';

class Ebizmarts_SqualoMail_CartController extends Mage_Checkout_CartController
{
    public function loadquoteAction()
    {
        $params = $this->getRequest()->getParams();

        if (isset($params['id'])) {
            //restore the quote
            $quote = Mage::getModel('sales/quote')->load($params['id']);
            $storeId = $quote->getStoreId();
            $squalomailStoreId = Mage::helper('squalomail')->getSQMStoreId($storeId);
            $quoteSyncData = $this->getSqualomailEcommerceSyncDataModel()
                ->getEcommerceSyncDataItem(
                    $params['id'],
                    Ebizmarts_SqualoMail_Model_Config::IS_QUOTE,
                    $squalomailStoreId
                );
            $url = Mage::getUrl(Mage::getStoreConfig(Ebizmarts_SqualoMail_Model_Config::ABANDONEDCART_PAGE, $storeId));

            if (isset($params['sqm_cid'])) {
                $url .= '?sqm_cid=' . $params['sqm_cid'];
            }

            if (!isset($params['token']) || $params['token'] != $quoteSyncData->getSqualomailToken()) {
                Mage::getSingleton('customer/session')->addNotice($this->_("Your token cart is incorrect"));
                $this->getResponse()
                    ->setRedirect($url);
            } else {
                $quote->setSqualomailAbandonedcartFlag(1);
                $quote->save();

                if (!$quote->getCustomerId()) {
                    $this->_getSession()->setQuoteId($quote->getId());
                    $newQuote = $this->_getSession()->getQuote();

                    if ($newQuote->getId() != $quote->getId()) {
                        $newQuote = $this->_getSession()->getQuote();
                        $newQuote->delete();
                        $newQuote->merge($quote)
                            ->save();
                    }

                    $this->getResponse()
                        ->setRedirect($url, 301);
                } else {
                    if (Mage::helper('customer')->isLoggedIn()) {
                        $this->getResponse()
                            ->setRedirect($url, 301);
                    } else {
                        Mage::getSingleton('customer/session')->addNotice($this->_("Login to complete your order"));
                        Mage::getSingleton('customer/session')->setAfterAuthUrl($url, $storeId);
                        $url = Mage::getUrl('customer/account/login');

                        if (isset($params['sqm_cid'])) {
                            $url .= '?sqm_cid=' . $params['sqm_cid'];
                        }

                        $this->getResponse()->setRedirect($url, 301);
                    }
                }
            }
        }
    }

    public function loadcouponAction()
    {
        $params = $this->getRequest()->getParams();

        if (isset($params['coupon_id']) && isset($params['coupon_token'])) {
            $helper = Mage::helper('squalomail');
            $id = $params['coupon_id'];
            $token = $params['coupon_token'];
            $storeId = Mage::app()->getStore()->getId();
            $squalomailStoreId = $helper->getSQMStoreId($storeId);
            $url = Mage::getUrl('checkout/cart');

            $promoCodeSyncData = $this->getSqualomailEcommerceSyncDataModel()->getEcommerceSyncDataItem(
                $id,
                Ebizmarts_SqualoMail_Model_Config::IS_PROMO_CODE,
                $squalomailStoreId
            );
            $couponId = $promoCodeSyncData->getRelatedId();

            if ($couponId && $promoCodeSyncData->getSqualomailToken() == $token) {
                $coupon = Mage::getModel('salesrule/coupon')->load($couponId);

                if ($coupon->getId()) {
                    $code = $coupon->getCode();
                    Mage::getSingleton("checkout/session")->setData("coupon_code", $code);
                    $quote = Mage::getSingleton('checkout/cart')->getQuote();
                    $quote->setCouponCode($code)->save();
                    Mage::getSingleton('core/session')->addSuccess($this->__('Coupon was automatically applied.'));

                    if (!$quote->getItemsCount()) {
                        Mage::getSingleton('core/session')
                            ->addWarning(
                                $this->__(
                                    'If you log in without adding any item to the cart, '
                                    . 'you will need to re-apply the coupon code manually.'
                                )
                            );
                    }
                } else {
                    Mage::getSingleton('core/session')
                        ->addError(
                            $this->__(
                                'Something went wrong when trying to apply the coupon code.'
                            )
                        );
                }

                $this->getResponse()->setRedirect($url, 301);
            } else {
                Mage::getSingleton('customer/session')
                    ->addNotice(
                        $this->__(
                            "The coupon code could not be applied for the current store. "
                            . "Please try to apply it manually."
                        )
                    );
                $this->getResponse()
                    ->setRedirect($url);
            }
        }
    }

    /**
     * @return mixed
     */
    public function getSqualomailEcommerceSyncDataModel()
    {
        return Mage::getModel('squalomail/ecommercesyncdata');
    }
}
