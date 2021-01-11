<?php

class Ebizmarts_SqualoMail_Model_Adminhtml_Resendsubscribers_Comment
{
    /**
     * @var Ebizmarts_SqualoMail_Helper_Data
     */
    protected $_sqmHelper;

    public function __construct()
    {
        $this->setMcHelper();
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    public function getMcHelper()
    {
        return $this->_sqmHelper;
    }

    /**
     * @param Ebizmarts_SqualoMail_Helper_Data $sqmHelper
     */
    public function setMcHelper()
    {
        $this->_sqmHelper = Mage::helper('squalomail');
    }

    /**
     * @return string
     */
    public function getCommentText()
    {
        $helper = $this->getMcHelper();
        $scopeArray = $helper->getCurrentScope();
        $scope = $scopeArray['scope'];

        if ($scope === "default"){
            $comment = $helper->__("This will resend the subscribers "
            ."for all Websites and Store Views.");
        } else {
            $websiteOrStoreViewScope = $this->_getScope($scopeArray);
            $comment = $helper->__("This will resend the subscribers "
            ."for %s only.", $websiteOrStoreViewScope);
        }

        return $comment;
    }

    /**
     * @param $scopeArray
     * @return string
     */
    protected function _getScope($scopeArray)
    {
        $scope = $scopeArray['scope'];
        if ($scope == "websites"){
            $result = "this Website";
        } else {
            $result = "this Store View";
        }

        return $result;
    }
}
