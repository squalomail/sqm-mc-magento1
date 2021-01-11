<?php

/**
 * squalomail-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   #PAC4#
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Ebizmarts_MailChimp_Model_Api_PromoCodes extends Ebizmarts_MailChimp_Model_Api_ItemSynchronizer
{
    const BATCH_LIMIT = 50;

    protected $_batchId;
    /**
     * @var Ebizmarts_MailChimp_Model_Api_PromoRules
     */
    protected $_apiPromoRules;

    /**
     * @var $_ecommercePromoCodesCollection Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_PromoCodes_Collection
     */
    protected $_ecommercePromoCodesCollection;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return array
     */
    public function createBatchJson()
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $magentoStoreId = $this->getMagentoStoreId();

        $this->_ecommercePromoCodesCollection = $this->createEcommercePromoCodesCollection();
        $this->_ecommercePromoCodesCollection->setSqualomailStoreId($squalomailStoreId);
        $this->_ecommercePromoCodesCollection->setStoreId($magentoStoreId);

        $batchArray = array();
        $this->_batchId = 'storeid-'
            . $magentoStoreId . '_'
            . Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE . '_'
            . $this->getDateHelper()->getDateMicrotime();
        $batchArray = array_merge($batchArray, $this->_getDeletedPromoCodes());
        $batchArray = array_merge($batchArray, $this->_getNewPromoCodes());

        return $batchArray;
    }

    /**
     * @return array
     */
    protected function _getDeletedPromoCodes()
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $batchArray = array();
        $deletedPromoCodes = $this->makeDeletedPromoCodesCollection();
        $counter = 0;

        foreach ($deletedPromoCodes as $promoCode) {
            $promoCodeId = $promoCode->getRelatedId();
            $promoRuleId = $promoCode->getDeletedRelatedId();
            $batchArray[$counter]['method'] = "DELETE";
            $batchArray[$counter]['path'] = '/ecommerce/stores/' . $squalomailStoreId
                . '/promo-rules/' . $promoRuleId
                . '/promo-codes/' . $promoCodeId;
            $batchArray[$counter]['operation_id'] = $this->_batchId . '_' . $promoCodeId;
            $batchArray[$counter]['body'] = '';
            $this->deletePromoCodeSyncData($promoCodeId);
            $counter++;
        }

        return $batchArray;
    }

    /**
     * @return array
     */
    protected function _getNewPromoCodes()
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $magentoStoreId = $this->getMagentoStoreId();
        $batchArray = array();
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();
        $newPromoCodes = $this->makePromoCodesCollection($magentoStoreId);

        $this->joinSqualomailSyncDataWithoutWhere($newPromoCodes);
        // be sure that the orders are not in squalomail
        $websiteId = Mage::getModel('core/store')->load($magentoStoreId)->getWebsiteId();
        $autoGeneratedCondition = "salesrule.use_auto_generation = 1 AND main_table.is_primary IS NULL";
        $notAutoGeneratedCondition = "salesrule.use_auto_generation = 0 AND main_table.is_primary = 1";

        $where = "m4m.squalomail_sync_delta IS NULL AND website.website_id = " . $websiteId
            . " AND ( " . $autoGeneratedCondition . " OR " . $notAutoGeneratedCondition . ")";

        $this->_ecommercePromoCodesCollection->addWhere($newPromoCodes, $where);
        // send most recently created first
        $newPromoCodes->getSelect()->order(array('salesrule.rule_id DESC'));
        // limit the collection
        $this->_ecommercePromoCodesCollection->limitCollection($newPromoCodes, $this->getBatchLimitFromConfig());

        $counter = 0;

        foreach ($newPromoCodes as $promoCode) {
            $codeId = $promoCode->getCouponId();
            $ruleId = $promoCode->getRuleId();

            try {
                $promoRuleSyncData = $this->getSqualomailEcommerceSyncDataModel()->getEcommerceSyncDataItem(
                    $ruleId,
                    Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE,
                    $squalomailStoreId
                );

                if (!$promoRuleSyncData->getId()) {
                    $promoRuleSqualomailData = $this->getApiPromoRules()->getNewPromoRule(
                        $ruleId,
                        $squalomailStoreId,
                        $magentoStoreId
                    );

                    if (!empty($promoRuleSqualomailData)) {
                        $batchArray[$counter] = $promoRuleSqualomailData;
                        $counter++;
                    } else {
                        $this->setCodeWithParentError($ruleId, $codeId);
                        continue;
                    }
                }

                if ($promoRuleSyncData->getSqualomailSyncError()) {
                    $this->setCodeWithParentError($ruleId, $codeId);
                    continue;
                }

                $promoCodeData = $this->generateCodeData($promoCode, $magentoStoreId);
                $promoCodeJson = json_encode($promoCodeData);

                if ($promoCodeJson !== false) {
                    if (!empty($promoCodeData)) {
                        $batchArray[$counter]['method'] = "POST";
                        $batchArray[$counter]['path'] = '/ecommerce/stores/' . $squalomailStoreId
                            . '/promo-rules/' . $ruleId . '/promo-codes';
                        $batchArray[$counter]['operation_id'] = $this->_batchId . '_' . $codeId;
                        $batchArray[$counter]['body'] = $promoCodeJson;

                        $this->addSyncDataToken($codeId, $promoCode->getToken());
                        $counter++;
                    } else {
                        $error = $helper->__('Something went wrong when retrieving the information.');
                        $this->addSyncDataError(
                            $codeId,
                            $error,
                            null,
                            false,
                            $dateHelper->formatDate(null, "Y-m-d H:i:s")
                        );
                        continue;
                    }
                } else {
                    $jsonErrorMsg = json_last_error_msg();
                    $this->logSyncError(
                        "Promo code" . $codeId . " json encode failed (".$jsonErrorMsg.")",
                        Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE,
                        $magentoStoreId,
                        'magento_side_error',
                        'Json Encode Failure',
                        0,
                        $codeId,
                        0
                    );

                    $this->addSyncDataError(
                        $codeId,
                        $jsonErrorMsg,
                        null,
                        false,
                        $dateHelper->formatDate(null, "Y-m-d H:i:s")
                    );
                }
            } catch (Exception $e) {
                $this->logSyncError(
                    $e->getMessage(),
                    Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE,
                    $magentoStoreId,
                    'magento_side_error',
                    'Json Encode Failure',
                    0,
                    $codeId,
                    0
                );
            }
        }

        return $batchArray;
    }

    /**
     * @return mixed
     */
    protected function getBatchLimitFromConfig()
    {
        $batchLimit = self::BATCH_LIMIT;
        return $batchLimit;
    }

    /**
     * @return Mage_SalesRule_Model_Resource_Coupon_Collection
     */
    protected function getPromoCodeResourceCollection()
    {
        return Mage::getResourceModel('salesrule/coupon_collection');
    }

    /**
     * @param $magentoStoreId
     * @return Mage_SalesRule_Model_Resource_Coupon_Collection
     */
    public function makePromoCodesCollection($magentoStoreId)
    {
        $helper = $this->getHelper();
        /**
         * @var Mage_SalesRule_Model_Resource_Coupon_Collection $collection
         */
        $collection = $this->getPromoCodeResourceCollection();
        $helper->addResendFilter($collection, $magentoStoreId, Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE);

        $promoCollectionResource = $this->getEcommercePromoCodesCollection();
        $promoCollectionResource->addWebsiteColumn($collection);
        $promoCollectionResource->joinPromoRuleData($collection);

        return $collection;
    }

    /**
     * @return object
     */
    protected function makeDeletedPromoCodesCollection()
    {
        $deletedPromoCodes = $this->getSqualomailEcommerceSyncDataModel()->getCollection();
        $where = "squalomail_store_id = '" . $this->getSqualomailStoreId()
            . "' AND type = '" . Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE
            . "' AND squalomail_sync_deleted = 1";

        $this->_ecommercePromoCodesCollection->addWhere($deletedPromoCodes, $where, $this->getBatchLimitFromConfig());

        return $deletedPromoCodes;
    }

    /**
     * @param $collection
     */
    public function joinSqualomailSyncDataWithoutWhere($collection)
    {
        $columns = array(
            "m4m.related_id",
            "m4m.type",
            "m4m.squalomail_store_id",
            "m4m.squalomail_sync_delta",
            "m4m.squalomail_sync_modified"
        );

        $this->_ecommercePromoCodesCollection->joinLeftEcommerceSyncData($collection, $columns);
    }

    protected function generateCodeData($promoCode, $magentoStoreId)
    {
        $data = array();
        $code = $promoCode->getCode();
        $data['id'] = $promoCode->getCouponId();
        $data['code'] = $code;

        //Set title as description if description null
        $data['redemption_url'] = $this->getRedemptionUrl($promoCode, $magentoStoreId);

        return $data;
    }

    protected function getRedemptionUrl($promoCode, $magentoStoreId)
    {
        $token = $this->getToken();
        $promoCode->setToken($token);
        $url = Mage::getModel('core/url')->setStore($magentoStoreId)->getUrl(
            'squalomail/cart/loadcoupon',
            array(
                    '_nosid' => true,
                    '_secure' => true,
                    'coupon_id' => $promoCode->getCouponId(),
                    'coupon_token' => $token
                )
        )
            . 'squalomail/cart/loadcoupon?coupon_id='
            . $promoCode->getCouponId()
            . '&coupon_token='
            . $token;

        return $url;
    }

    /**
     * @return string
     */
    protected function getToken()
    {
        $token = hash('md5', rand(0, 9999999));

        return $token;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Api_PromoRules|false|Mage_Core_Model_Abstract
     */
    public function getApiPromoRules()
    {
        if (!$this->_apiPromoRules) {
            $this->_apiPromoRules = Mage::getModel('squalomail/api_promoRules');
        }

        return $this->_apiPromoRules;
    }

    /**
     * @param $codeId
     * @param $promoRuleId
     */
    public function markAsDeleted($codeId, $promoRuleId)
    {
        $this->_setDeleted($codeId, $promoRuleId);
    }

    /**
     * @param $codeId
     * @param $promoRuleId
     */
    protected function _setDeleted($codeId, $promoRuleId)
    {
        $promoCodes = $this->getSqualomailEcommerceSyncDataModel()->getAllEcommerceSyncDataItemsPerId(
            $codeId,
            Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE
        );

        foreach ($promoCodes as $promoCode) {
            $squalomailStoreId = $promoCode->getSqualomailStoreId();
            $this->addDeletedRelatedId($codeId, $promoRuleId);
        }
    }

    /**
     * @param $promoRule
     * @throws Exception
     */
    public function deletePromoCodesSyncDataByRule($promoRule)
    {
        $promoCodeIds = $this->getPromoCodesForRule($promoRule->getRelatedId());

        foreach ($promoCodeIds as $promoCodeId) {
            $promoCodeSyncDataItems = $this->getSqualomailEcommerceSyncDataModel()->getAllEcommerceSyncDataItemsPerId(
                $promoCodeId,
                Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE
            );

            foreach ($promoCodeSyncDataItems as $promoCodeSyncDataItem) {
                $promoCodeSyncDataItem->delete();
            }
        }
    }

    /**
     * @param $promoCodeId
     */
    public function deletePromoCodeSyncData($promoCodeId)
    {
        $promoCodeSyncDataItem = $this->getSqualomailEcommerceSyncDataModel()->getEcommerceSyncDataItem(
            $promoCodeId,
            Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE,
            $this->getSqualomailStoreId()
        );
        $promoCodeSyncDataItem->delete();
    }

    /**
     * @param $promoRuleId
     * @return array
     */
    protected function getPromoCodesForRule($promoRuleId)
    {
        $promoCodes = array();
        $helper = $this->getHelper();
        $promoRules = $this->getSqualomailEcommerceSyncDataModel()->getAllEcommerceSyncDataItemsPerId(
            $promoRuleId,
            Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE
        );

        foreach ($promoRules as $promoRule) {
            $squalomailStoreId = $promoRule->getSqualomailStoreId();
            $api = $helper->getApiByMailChimpStoreId($squalomailStoreId);

            if ($api !== null) {
                try {
                    $mailChimpPromoCodes = $api->ecommerce->promoRules->promoCodes
                        ->getAll($squalomailStoreId, $promoRuleId);

                    foreach ($mailChimpPromoCodes['promo_codes'] as $promoCode) {
                        $this->deletePromoCodeSyncData($promoCode['id']);
                    }
                } catch (MailChimp_Error $e) {
                    $this->logSyncError(
                        $e->getFriendlyMessage(),
                        Ebizmarts_MailChimp_Model_Config::IS_PROMO_RULE,
                        $this->getMagentoStoreId(),
                        'magento_side_error',
                        'Problem retrieving object',
                        0,
                        $promoRuleId,
                        0
                    );
                }
            }
        }

        return $promoCodes;
    }

    /**
     * @param $promoCodeId
     * @return string
     */
    protected function getPromoRuleIdByCouponId($promoCodeId)
    {
        $coupon = Mage::getModel('salesrule/coupon')->load($promoCodeId);
        return $coupon->getRuleId();
    }

    /**
     * @param $ruleId
     * @param $codeId
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function setCodeWithParentError($ruleId, $codeId)
    {
        $dateHelper = $this->getDateHelper();
        $error = Mage::helper('squalomail')->__(
            'Parent rule with id ' . $ruleId . ' has not been correctly sent.'
        );
        $this->addSyncDataError(
            $codeId,
            $error,
            null,
            false,
            $dateHelper->formatDate(null, "Y-m-d H:i:s")
        );
    }

    /**
     * @return string
     */
    protected function getItemType()
    {
        return Ebizmarts_MailChimp_Model_Config::IS_PROMO_CODE;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_PromoCodes_Collection
     */
    public function createEcommercePromoCodesCollection()
    {
        /**
         * @var $collection Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_PromoCodes_Collection
         */
        $collection = Mage::getResourceModel('squalomail/ecommercesyncdata_promoCodes_collection');

        return $collection;
    }

    /**
     * @return Ebizmarts_MailChimp_Model_Resource_Ecommercesyncdata_PromoCodes_Collection
     */
    public function getEcommercePromoCodesCollection()
    {
        return $this->_ecommercePromoCodesCollection;
    }
}
