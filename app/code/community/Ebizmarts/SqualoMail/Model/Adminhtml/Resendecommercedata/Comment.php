<?php

class Ebizmarts_SqualoMail_Model_Adminhtml_Resendecommercedata_Comment
{
    /**
     * @var Ebizmarts_SqualoMail_Helper_Data
     */
    protected $_sqmHelper;

    public function __construct()
    {
        $this->setSqmHelper();
    }

    /**
     * @return Ebizmarts_SqualoMail_Helper_Data
     */
    public function getSqmHelper()
    {
        return $this->_sqmHelper;
    }

    /**
     * @param Ebizmarts_SqualoMail_Helper_Data $sqmHelper
     */
    public function setSqmHelper()
    {
        $this->_sqmHelper = Mage::helper('squalomail');
    }

    /**
     * @return string
     */
    public function getCommentText()
    {
        $helper = $this->getSqmHelper();
        $scopeArray = $helper->getCurrentScope();
        $scope = $scopeArray['scope'];

        if ($scope === "default"){
            $comment = $helper->__("This will resend the ecommerce data "
                ."for all Websites and Store Views.");
        } else {
            $websiteOrStoreViewScope = $this->_getScope($scopeArray);
            $comment = $helper->__("This will resend the ecommerce data "
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
