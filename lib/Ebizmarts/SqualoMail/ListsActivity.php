<?php
/**
 * squalomail-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/2/16 3:44 PM
 * @file:     ListsActivity.php
 */
class SqualoMail_ListsActivity extends SqualoMail_Abstract
{
    /**
     * @param $listId               The unique id for the list.
     * @param null $fields          A comma-separated list of fields to return. Reference parameters of sub-objects
     *                                       with dot notation.
     * @param null $excludeFields   A comma-separated list of fields to exclude. Reference parameters of sub-objects
     *                                       with dot notation.
     * @return mixed
     * @throws SqualoMail_Error
     * @throws SqualoMail_HttpError
     */
    public function get($listId, $fields = null, $excludeFields = null)
    {
        $_params = array();

        if ($fields) {
            $_params['fields'] = $fields;
        }

        if ($excludeFields) {
            $_params['exclude_fields'] = $excludeFields;
        }

        return $this->_master->call('lists/' . $listId . '/activity', $_params, Ebizmarts_SqualoMail::GET);
    }
}
