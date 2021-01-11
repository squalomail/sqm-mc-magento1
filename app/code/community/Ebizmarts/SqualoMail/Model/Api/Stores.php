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
class Ebizmarts_SqualoMail_Model_Api_Stores
{

    /**
     * Create Squalomail store.
     *
     * @param  $apiKey
     * @param  $listId
     * @param  $storeName
     * @param  $currencyCode
     * @param  $storeDomain
     * @param  $storeEmail
     * @param  $primaryLocale
     * @param  $timeZone
     * @param  $storePhone
     * @param  $address
     * @return mixed
     * @throws Exception
     */
    public function createSqualoMailStore(
        $apiKey,
        $listId,
        $storeName,
        $currencyCode,
        $storeDomain,
        $storeEmail,
        $primaryLocale,
        $timeZone,
        $storePhone,
        $address
    ) {
        $helper = $this->makeHelper();
        $dateHelper = $this->makeDateHelper();
        $date = $dateHelper->getDateMicrotime();
        $squalomailStoreId = hash('md5', $storeName . '_' . $date);

        try {
            $api = $helper->getApiByKey($apiKey);
            $isSyncing = true;
            $currencySymbol = $helper->getMageApp()->getLocale()->currency($currencyCode)->getSymbol();
            $response = $this->addStore(
                $api,
                $squalomailStoreId,
                $listId,
                $storeName,
                $currencyCode,
                $isSyncing,
                $storeDomain,
                $storeEmail,
                $currencySymbol,
                $primaryLocale,
                $timeZone,
                $storePhone,
                $address
            );
            $configValues = array(
                array(
                    Ebizmarts_SqualoMail_Model_Config::ECOMMERCE_SQM_JS_URL . "_$squalomailStoreId",
                    $response['connected_site']['site_script']['url']
                )
            );
            $helper->saveSqualomailConfig($configValues, 0, 'default');
            $successMessage = $helper->__("The Squalomail store was successfully created.");
            $adminSession = $this->getAdminSession();
            $adminSession->addSuccess($successMessage);
        } catch (Ebizmarts_SqualoMail_Helper_Data_ApiKeyException $e) {
            $response = $errorMessage = $e->getMessage();
            $helper->logError($errorMessage);
            $adminSession = $this->getAdminSession();
            $adminSession->addError($errorMessage);
        } catch (SqualoMail_Error $e) {
            $adminSession = $this->getAdminSession();
            $response = $errorMessage = $e->getFriendlyMessage();
            $helper->logError($errorMessage);
            $errorMessage = $this->getUserFriendlyMessage($e);
            $adminSession->addError($errorMessage);
        } catch (Exception $e) {
            $response = $errorMessage = $e->getMessage();
            $helper->logError($errorMessage);
            $adminSession = $this->getAdminSession();
            $adminSession->addError($errorMessage);
        }

        return $response;
    }

    /**
     * Edit Squalomail store.
     *
     * @param  $squalomailStoreId
     * @param  $apiKey
     * @param  $storeName
     * @param  $currencyCode
     * @param  $storeDomain
     * @param  $storeEmail
     * @param  $primaryLocale
     * @param  $timeZone
     * @param  $storePhone
     * @param  $address
     * @return mixed|string
     * @throws Mage_Core_Exception
     */
    public function editSqualoMailStore(
        $squalomailStoreId,
        $apiKey,
        $storeName,
        $currencyCode,
        $storeDomain,
        $storeEmail,
        $primaryLocale,
        $timeZone,
        $storePhone,
        $address
    ) {
        $helper = $this->makeHelper();

        try {
            $api = $helper->getApiByKey($apiKey);
            $currencySymbol = $helper->getMageApp()->getLocale()->currency($currencyCode)->getSymbol();
            $response = $api->getEcommerce()->getStores()->edit(
                $squalomailStoreId,
                $storeName,
                'Magento',
                $storeDomain,
                null,
                $storeEmail,
                $currencyCode,
                $currencySymbol,
                $primaryLocale,
                $timeZone,
                $storePhone,
                $address
            );
            $successMessage = $helper->__("The Squalomail store was successfully edited.");
            $adminSession = $this->getAdminSession();
            $adminSession->addSuccess($successMessage);
        } catch (Ebizmarts_SqualoMail_Helper_Data_ApiKeyException $e) {
            $response = $errorMessage = $e->getMessage();
            $helper->logError($errorMessage);
            $adminSession = $this->getAdminSession();
            $adminSession->addError($errorMessage);
        } catch (SqualoMail_Error $e) {
            $adminSession = $this->getAdminSession();
            $response = $errorMessage = $e->getFriendlyMessage();
            $helper->logError($errorMessage);
            $errorMessage = $this->getUserFriendlyMessage($e);
            $adminSession->addError($errorMessage);
        } catch (Exception $e) {
            $response = $errorMessage = $e->getMessage();
            $helper->logError($errorMessage);
            $adminSession = $this->getAdminSession();
            $adminSession->addError($errorMessage);
        }

        return $response;
    }

    /**
     * @param $e SqualoMail_Error
     * @return string
     */
    protected function getUserFriendlyMessage($e)
    {
        $helper = $this->makeHelper();
        $errorMessage = $e->getFriendlyMessage();

        if (strstr($errorMessage, 'A store with the domain')) {
            $errorMessage = $helper->__(
                'A Squalomail store with the same domain already exists in this account. '
                    . 'You need to have a different URLs for each scope you set up the ecommerce data. '
                    . 'Possible solutions '
            )
                . "<a href='https://docs.magento.com/m1/ce/user_guide/search_seo/seo-url-rewrite-configure.html'>"
                . "HERE</a> and "
                . "<a href='https://docs.magento.com/m1/ce/user_guide/configuration/url-secure-unsecure.html'>"
                . "HERE</a>";
        } else {
            if (is_array($e->getSqualomailErrors())) {
                $errorDetail = "";

                foreach ($e->getSqualomailErrors() as $error) {
                    if (isset($error['field'])) {
                        $errorDetail .= "<br />    Field: " . $error['field'];
                    }

                    if (isset($error['message'])) {
                        $errorDetail .= " Message: " . $error['message'];
                    }
                }

                if (!empty($errorDetail)) {
                    $errorMessage = "Error: $errorDetail";
                }
            }
        }

        return $errorMessage;
    }

    /**
     * Delete SqualoMail store.
     *
     * @param  $squalomailStoreId
     * @param  $apiKey
     * @return mixed|string
     * @throws Mage_Core_Exception
     * @throws Ebizmarts_SqualoMail_Helper_Data_ApiKeyException
     */
    public function deleteSqualoMailStore($squalomailStoreId, $apiKey)
    {
        $helper = $this->makeHelper();

        try {
            $api = $helper->getApiByKey($apiKey);
            $response = $api->getEcommerce()->getStores()->delete($squalomailStoreId);
            $helper->cancelAllPendingBatches($squalomailStoreId);
            $successMessage = $helper->__("The Squalomail store was successfully deleted.");
            $adminSession = $this->getAdminSession();
            $adminSession->addSuccess($successMessage);
        } catch (Ebizmarts_SqualoMail_Helper_Data_ApiKeyException $e) {
            $response = $errorMessage = $e->getMessage();
            $helper->logError($errorMessage);
            $adminSession = $this->getAdminSession();
            $adminSession->addError($errorMessage);
        } catch (SqualoMail_Error $e) {
            $response = $errorMessage = $e->getFriendlyMessage();
            $helper->logError($errorMessage);
            $adminSession = $this->getAdminSession();
            $adminSession->addError($errorMessage);
        } catch (Exception $e) {
            $response = $errorMessage = $e->getMessage();
            $helper->logError($errorMessage);
            $adminSession = $this->getAdminSession();
            $adminSession->addError($errorMessage);
        }

        return $response;
    }

    /**
     * Remove all data associated to the given Squalomail store id.
     *
     * @param $squalomailStoreId
     */
    protected function deleteLocalSQMStoreData($squalomailStoreId)
    {
        $helper = $this->makeHelper();
        $helper->deleteAllSQMStoreData($squalomailStoreId);
    }

    /**
     * Set is_syncing value for the given scope.
     *
     * @param  $squalomailApi Ebizmarts_SqualoMail
     * @param  $isSincingValue
     * @param  $squalomailStoreId
     * @throws SqualoMail_Error
     */
    public function editIsSyncing($squalomailApi, $isSincingValue, $squalomailStoreId)
    {
        $squalomailApi->getEcommerce()->getStores()->edit(
            $squalomailStoreId,
            null,
            null,
            null,
            $isSincingValue
        );
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    protected function makeHelper()
    {
        return Mage::helper('squalomail');
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Dat3
     */
    protected function makeDateHelper()
    {
        return Mage::helper('squalomail/date');
    }

    /**
     * @return Mage_Adminhtml_Model_Session
     */
    protected function getAdminSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * @param $api
     * @param $squalomailStoreId
     * @param $listId
     * @param $storeName
     * @param $currencyCode
     * @param $isSyncing
     * @param $storeDomain
     * @param $storeEmail
     * @param $currencySymbol
     * @param $primaryLocale
     * @param $timeZone
     * @param $storePhone
     * @param $address
     * @return mixed
     */
    protected function addStore(
        $api,
        $squalomailStoreId,
        $listId,
        $storeName,
        $currencyCode,
        $isSyncing,
        $storeDomain,
        $storeEmail,
        $currencySymbol,
        $primaryLocale,
        $timeZone,
        $storePhone,
        $address
    ) {
        return $api->getEcommerce()->getStores()->add(
            $squalomailStoreId,
            $listId,
            $storeName,
            $currencyCode,
            $isSyncing,
            'Magento',
            $storeDomain,
            $storeEmail,
            $currencySymbol,
            $primaryLocale,
            $timeZone,
            $storePhone,
            $address
        );
    }
}
