<?php

/**
 * squalomail-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Ebizmarts_SqualoMail_Model_Api_PromoRules extends Ebizmarts_SqualoMail_Model_Api_ItemSynchronizer
{
    const BATCH_LIMIT = 50;
    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENTAGE = 'percentage';
    const TARGET_PER_ITEM = 'per_item';
    const TARGET_TOTAL = 'total';

    protected $_batchId;

    /**
     * @var $_ecommercePromoRulesCollection Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata_PromoRules_Collection
     */
    protected $_ecommercePromoRulesCollection;

    /**
     * @var Ebizmarts_SqualoMail_Model_Api_PromoCodes
     */
    protected $_promoCodes;

    public function __construct()
    {
        parent::__construct();
        $this->_promoCodes = Mage::getModel('squalomail/api_promoCodes');
    }

    /**
     * @return array
     */
    public function createBatchJson()
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $magentoStoreId = $this->getMagentoStoreId();

        $this->_ecommercePromoRulesCollection = $this->createEcommercePromoRulesCollection();
        $this->_ecommercePromoRulesCollection->setSqualomailStoreId($squalomailStoreId);
        $this->_ecommercePromoRulesCollection->setStoreId($magentoStoreId);

        $batchArray = array();
        $this->_batchId = 'storeid-'
            . $magentoStoreId . '_'
            . Ebizmarts_SqualoMail_Model_Config::IS_PROMO_RULE . '_'
            . $this->getDateHelper()->getDateMicrotime();
        $batchArray = array_merge($batchArray, $this->_getModifiedAndDeletedPromoRules());

        return $batchArray;
    }

    /**
     * @return array
     */
    protected function _getModifiedAndDeletedPromoRules()
    {
        $squalomailStoreId = $this->getSqualomailStoreId();
        $batchArray = array();
        $deletedPromoRules = $this->makeModifiedAndDeletedPromoRulesCollection();
        $counter = 0;

        foreach ($deletedPromoRules as $promoRule) {
            $ruleId = $promoRule->getRelatedId();
            $batchArray[$counter]['method'] = "DELETE";
            $batchArray[$counter]['path'] = '/ecommerce/stores/' . $squalomailStoreId . '/promo-rules/' . $ruleId;
            $batchArray[$counter]['operation_id'] = $this->_batchId . '_' . $ruleId;
            $batchArray[$counter]['body'] = '';
            $this->getPromoCodes()->deletePromoCodesSyncDataByRule($promoRule);
            $this->deletePromoRuleSyncData($ruleId);
            $counter++;
        }

        return $batchArray;
    }

    /**
     * @param $ruleId
     * @param $squalomailStoreId
     * @param $magentoStoreId
     * @return array
     */
    public function getNewPromoRule($ruleId, $squalomailStoreId, $magentoStoreId)
    {
        $promoData = array();
        $promoRule = $this->getPromoRule($ruleId);
        $helper = $this->getHelper();
        $dateHelper = $this->getDateHelper();

        try {
            $ruleData = $this->generateRuleData($promoRule);
            $promoRuleJson = json_encode($ruleData);

            if ($promoRuleJson !== false) {
                if (!empty($ruleData)) {
                    $promoData['method'] = "POST";
                    $promoData['path'] = '/ecommerce/stores/' . $squalomailStoreId . '/promo-rules';
                    $promoData['operation_id'] = 'storeid-'
                        . $magentoStoreId . '_'
                        . Ebizmarts_SqualoMail_Model_Config::IS_PROMO_RULE . '_'
                        . $dateHelper->getDateMicrotime() . '_' . $ruleId;
                    $promoData['body'] = $promoRuleJson;
                    //update promo rule delta
                    $this->addSyncData($ruleId);
                } else {
                    $error = $promoRule->getSqualomailSyncError();

                    if (!$error) {
                        $error = $helper->__('Something went wrong when retrieving the information.');
                    }

                    $this->addSyncDataError(
                        $ruleId,
                        $error,
                        null,
                        false,
                        $dateHelper->formatDate(null, "Y-m-d H:i:s")
                    );
                }
            } else {
                $jsonErrorMsg = json_last_error_msg();
                $this->logSyncError(
                    $jsonErrorMsg,
                    Ebizmarts_SqualoMail_Model_Config::IS_PROMO_RULE,
                    $magentoStoreId,
                    'magento_side_error',
                    'Json Encode Failure',
                    0,
                    $ruleId,
                    0
                );

                $this->addSyncDataError(
                    $ruleId,
                    $jsonErrorMsg,
                    null,
                    false,
                    $dateHelper->formatDate(null, "Y-m-d H:i:s")
                );
            }
        } catch (Exception $e) {
            $this->logSyncError(
                $e->getMessage(),
                Ebizmarts_SqualoMail_Model_Config::IS_PROMO_RULE,
                $magentoStoreId,
                'magento_side_error',
                'Json Encode Failure',
                0,
                $ruleId,
                0
            );
        }

        return $promoData;
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
     * @param $ruleId
     * @return Mage_Core_Model_Abstract
     */
    protected function getPromoRule($ruleId)
    {
        return Mage::getModel('salesrule/rule')->load($ruleId);
    }

    /**
     * @return Mage_SalesRule_Model_Resource_Rule_Collection
     */
    protected function getPromoRuleResourceCollection()
    {
        return Mage::getResourceModel('salesrule/rule_collection');
    }

    /**
     * @param $magentoStoreId
     * @return Mage_SalesRule_Model_Resource_Rule_Collection
     */
    public function makePromoRulesCollection($magentoStoreId)
    {
        /**
         * @var Mage_SalesRule_Model_Resource_Rule_Collection $collection
         */
        $collection = $this->getPromoRuleResourceCollection();
        $websiteId = $this->getWebsiteIdByStoreId($magentoStoreId);
        $collection->addWebsiteFilter($websiteId);

        return $collection;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata_Collection
     */
    protected function makeModifiedAndDeletedPromoRulesCollection()
    {
        $deletedPromoRules = $this->getSqualomailEcommerceSyncDataModel()->getCollection();

        $this->_ecommercePromoRulesCollection->addWhere(
            $deletedPromoRules,
            "squalomail_store_id = '" . $this->getSqualomailStoreId()
            . "' AND type = '" . Ebizmarts_SqualoMail_Model_Config::IS_PROMO_RULE
            . "' AND (squalomail_sync_modified = 1 OR squalomail_sync_deleted = 1)",
            $this->getBatchLimitFromConfig()
        );

        return $deletedPromoRules;
    }

    /**
     * @param $ruleId
     */
    protected function deletePromoRuleSyncData($ruleId)
    {
        $ruleSyncDataItem = $this->getSqualomailEcommerceSyncDataModel()->getEcommerceSyncDataItem(
            $ruleId,
            Ebizmarts_SqualoMail_Model_Config::IS_PROMO_RULE,
            $this->getSqualomailStoreId()
        );

        $ruleSyncDataItem->delete();
    }

    /**
     * @param $promoRule
     * @return array
     */
    protected function generateRuleData($promoRule)
    {
        $error = null;
        $data = array();
        $data['id'] = $promoRule->getRuleId();
        $data['title'] = $promoRule->getName();

        //Set title as description if description null
        $data['description'] = ($promoRule->getDescription()) ? $promoRule->getDescription() : $promoRule->getName();

        $fromDate = $promoRule->getFromDate();
        if ($fromDate !== null) {
            $data['starts_at'] = $fromDate;
        }

        $toDate = $promoRule->getToDate();
        if ($toDate !== null) {
            $data['ends_at'] = $toDate;
        }

        $data['amount'] = $this->getSqualoMailDiscountAmount($promoRule);
        $promoAction = $promoRule->getSimpleAction();
        $data['type'] = $this->getSqualoMailType($promoAction);
        $data['target'] = $this->getSqualoMailTarget($promoAction);

        $data['enabled'] = (bool)$promoRule->getIsActive();

        if ($this->ruleIsNotCompatible($data)) {
            $error = 'The rule type is not supported by the SqualoMail schema.';
        }

        if (!$error && $this->ruleHasMissingInformation($data)) {
            $error = 'There is required information by the SqualoMail schema missing.';
        }

        if ($error) {
            $data = array();
            $promoRule->setSqualomailSyncError($error);
        }

        return $data;
    }

    /**
     * @param $promoAction
     * @return string|null
     */
    protected function getSqualoMailType($promoAction)
    {
        $squaloMailType = null;
        switch ($promoAction) {
        case Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION:
            $squaloMailType = self::TYPE_PERCENTAGE;
            break;
        case Mage_SalesRule_Model_Rule::BY_FIXED_ACTION:
        case Mage_SalesRule_Model_Rule::CART_FIXED_ACTION:
            $squaloMailType = self::TYPE_FIXED;
            break;
        }

        return $squaloMailType;
    }

    /**
     * @param $promoAction
     * @return string|null
     */
    protected function getSqualoMailTarget($promoAction)
    {
        $squaloMailTarget = null;

        switch ($promoAction) {
        case Mage_SalesRule_Model_Rule::CART_FIXED_ACTION:
        case Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION:
            $squaloMailTarget = self::TARGET_TOTAL;
            break;
        case Mage_SalesRule_Model_Rule::BY_FIXED_ACTION:
            $squaloMailTarget = self::TARGET_PER_ITEM;
            break;
        }

        return $squaloMailTarget;
    }

    /**
     * @param $ruleId
     */
    public function update($ruleId)
    {
        $this->_setModified($ruleId);
    }

    /**
     * @param $ruleId
     */
    protected function _setModified($ruleId)
    {
        $promoRules = $this->getSqualomailEcommerceSyncDataModel()->getAllEcommerceSyncDataItemsPerId(
            $ruleId,
            Ebizmarts_SqualoMail_Model_Config::IS_PROMO_RULE
        );

        foreach ($promoRules as $promoRule) {
            $squalomailStoreId = $promoRule->getSqualomailStoreId();
            $this->setSqualomailStoreId($squalomailStoreId);
            $this->markSyncDataAsModified($ruleId);
        }
    }

    /**
     * @param $ruleId
     */
    public function markAsDeleted($ruleId)
    {
        $this->_setDeleted($ruleId);
    }

    /**
     * @param $ruleId
     */
    protected function _setDeleted($ruleId)
    {
        $promoRules = $this->getSqualomailEcommerceSyncDataModel()
            ->getAllEcommerceSyncDataItemsPerId($ruleId, Ebizmarts_SqualoMail_Model_Config::IS_PROMO_RULE);

        foreach ($promoRules as $promoRule) {
            $this->setSqualomailStoreId($promoRule->getSqualomailStoreId());
            $this->markSyncDataAsDeleted($ruleId);
        }
    }

    /**
     * @param $promoRule
     * @return float|int
     */
    protected function getSqualoMailDiscountAmount($promoRule)
    {
        $action = $promoRule->getSimpleAction();

        if ($action == Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION) {
            $squaloMailDiscount = ($promoRule->getDiscountAmount() / 100);
        } else {
            $squaloMailDiscount = $promoRule->getDiscountAmount();
        }

        return $squaloMailDiscount;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Api_PromoCodes
     */
    protected function getPromoCodes()
    {
        return $this->_promoCodes;
    }

    /**
     * @param $magentoStoreId
     * @return mixed
     */
    protected function getWebsiteIdByStoreId($magentoStoreId)
    {
        return Mage::getModel('core/store')->load($magentoStoreId)->getWebsiteId();
    }

    /**
     * @param $data
     * @return bool
     */
    protected function ruleIsNotCompatible($data)
    {
        $isNotCompatible = null;

        if ($data['target'] === null || $data['type'] === null) {
            $isNotCompatible = true;
        } else {
            $isNotCompatible = false;
        }

        return $isNotCompatible;
    }

    /**
     * @param $data
     * @return bool
     */
    protected function ruleHasMissingInformation($data)
    {
        $hasMissingInformation = null;

        if ($data['amount'] === null || $data['description'] === null || $data['id'] === null) {
            $hasMissingInformation = true;
        } else {
            $hasMissingInformation = false;
        }

        return $hasMissingInformation;
    }

    /**
     * @return string
     */
    protected function getItemType()
    {
        return Ebizmarts_SqualoMail_Model_Config::IS_PROMO_RULE;
    }

    /**
     * @return Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata_PromoRules_Collection
     */
    public function createEcommercePromoRulesCollection()
    {
        /**
         * @var $collection Ebizmarts_SqualoMail_Model_Resource_Ecommercesyncdata_PromoRules_Collection
         */
        $collection = Mage::getResourceModel('squalomail/ecommercesyncdata_promoRules_collection');

        return $collection;
    }
}
