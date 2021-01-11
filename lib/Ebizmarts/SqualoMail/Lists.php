<?php

/**
 * squalomail-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   #PAC4#
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/27/16 5:00 PM
 * @file:     Lists.php
 */
class SqualoMail_Lists extends SqualoMail_Abstract
{
    /**
     * @var SqualoMail_ListsSegments
     */
    public $segments;
    /**
     * @var SqualoMail_ListsAbuseReports
     */
    public $abuseReports;
    /**
     * @var SqualoMail_ListsActivity
     */
    public $activity;
    /**
     * @var SqualoMail_ListsClients
     */
    public $clients;
    /**
     * @var SqualoMail_ListsGrowthHistory
     */
    public $growthHistory;
    /**
     * @var SqualoMail_ListsInterestCategory
     */
    public $interestCategory;
    /**
     * @var SqualoMail_ListsMembers
     */
    public $members;
    /**
     * @var SqualoMail_ListsMergeFields
     */
    public $mergeFields;
    /**
     * @var SqualoMail_ListsWebhooks
     */
    public $webhooks;

    /**
     * @return SqualoMail_ListsWebhooks
     */
    public function getWebhooks()
    {
        return $this->webhooks;
    }

    /**
     * @return SqualoMail_ListsMergeFields
     */
    public function getMergeFields()
    {
        return $this->mergeFields;
    }

    /**
     * @return SqualoMail_ListsInterestCategory
     */
    public function getInterestCategory()
    {
        return $this->interestCategory;
    }

    /**
     * @return SqualoMail_ListsMembers
     */
    public function getMembers()
    {
        return $this->members;
    }
    /**
     * @param $name
     * @param $contact
     *          company *   (The company name for the list)
     *          address1 *  (The street address for the list contact)
     *          address2    (The street address for the list contact)
     *          city *      (The city for the list contact)
     *          state *     (The state for the list contact)
     *          zip *       (The postal or zip code for the list contact)
     *          country *   (A two-character ISO3166 country code. Defaults to US if invalid.)
     * @param $permissionRemanider
     * @param bool                $useArchiveBar
     * @param $campaingDefaults
     *          fromName * (The default from name for campaigns sent to this list)
     *          fromEmail * (The email address to send unsubscribe notifications to)
     *          subject * (The default subject line for campaigns sent to this list)
     *          language *(The default language for this lists’s forms)
     * @param bool                $notifyOnSubscribe
     * @param $notifyOnUnsubscribe
     * @param $emailTypeOption
     * @param string              $visibility
     * @return mixed
     * @throws SqualoMail_Error
     * @throws SqualoMail_HttpError
     */
    public function add(
        $name,
        $contact,
        $permissionRemanider,
        $campaingDefaults,
        $notifyOnUnsubscribe,
        $emailTypeOption,
        $useArchiveBar = false,
        $notifyOnSubscribe = false,
        $visibility = 'pub'
    ) {

        $_params = array('name' => $name, 'contact' => $contact, 'permission_reminder' => $permissionRemanider,
            'use_archive_bar' => $useArchiveBar, 'campaign_defaults' => $campaingDefaults,
            'notify_on_subscribe' => $notifyOnSubscribe, 'notify_on_unsubscribe' => $notifyOnUnsubscribe,
            'email_type_option' => $emailTypeOption, 'visibility' => $visibility);

        return $this->_master->call('lists', $_params, Ebizmarts_SqualoMail::POST);
    }

    public function getLists(
        $id = null,
        $fields = null,
        $excludeFields = null,
        $count = 100,
        $offset = null,
        $beforeDateCreated = null,
        $sinceDateCreated = null,
        $beforeCampaignLastSent = null,
        $sinceCampaignLastSent = null,
        $email = null
    ) {

        $_params = array();

        if ($fields) {
            $_params['fields'] = $fields;
        }

        if ($excludeFields) {
            $_params['exclude_fields'] = $excludeFields;
        }

        if ($count) {
            $_params['count'] = $count;
        }

        if ($offset) {
            $_params['offset'] = $offset;
        }

        if ($beforeDateCreated) {
            $_params['before_date_created'] = $beforeDateCreated;
        }

        if ($sinceDateCreated) {
            $_params['since_date_created'] = $sinceDateCreated;
        }

        if ($beforeCampaignLastSent) {
            $_params['before_campaigns_last_sent'] = $beforeCampaignLastSent;
        }

        if ($sinceCampaignLastSent) {
            $_params['since_campaign_last_sent'] = $sinceCampaignLastSent;
        }

        if ($email) {
            $_params['email'] = $email;
        }

        if ($id) {
            return $this->_master->call('lists/' . $id, $_params, Ebizmarts_SqualoMail::GET);
        } else {
            return $this->_master->call('lists', $_params, Ebizmarts_SqualoMail::GET);
        }
    }

    /**
     * @param $listId                   The unique id for the list.
     * @param $name                     The name of the list.
     * @param $contact                  Contact information displayed in campaign footers to comply with international
     *                                      spam laws.
     * @param $permissionReminder       The permission reminder for the list.
     * @param null $useArchiveBar       Whether campaigns for this list use the Archive Bar in archives by default.
     * @param null $campaignDefaults    Default values for campaigns created for this list.
     * @param null $notifyOnSubscribe   The email address to send subscribe notifications to.
     * @param null $notifyOnUnsubscribe The email address to send unsubscribe notifications to.
     * @param $emailTypeOption          Whether the list supports multiple formats for emails.
     *                                      When set to true, subscribers can choose whether they want to receive HTML or
     *                                      plain-text emails.
     *                                      When set to false, subscribers will receive HTML emails, with a plain-text
     *                                      alternative backup.
     * @param null $visibility          Whether this list is public or private. (pub/prv)
     * @return mixed
     * @throws SqualoMail_Error
     * @throws SqualoMail_HttpError
     */
    public function edit(
        $listId,
        $name,
        $contact,
        $permissionReminder,
        $emailTypeOption,
        $useArchiveBar = null,
        $campaignDefaults = null,
        $notifyOnSubscribe = null,
        $notifyOnUnsubscribe = null,
        $visibility = null
    ) {

        $_params = array('name' => $name, 'contact' => $contact, 'permission_reminder' => $permissionReminder,
            'email_type_option' => $emailTypeOption);

        if ($useArchiveBar) {
            $_params['use_archive_bar'] = $useArchiveBar;
        }

        if ($campaignDefaults) {
            $_params['campaign_defaults'] = $campaignDefaults;
        }

        if ($notifyOnSubscribe) {
            $_params['notify_on_subscribe'] = $notifyOnSubscribe;
        }

        if ($notifyOnUnsubscribe) {
            $_params['notify_on_unsubscribe'] = $notifyOnUnsubscribe;
        }

        if ($visibility) {
            $_params['visibility'] = $visibility;
        }

        return $this->_master->call('lists/' . $listId, $_params, Ebizmarts_SqualoMail::PATCH);
    }

    /**
     * @param $listId                   The unique id for the list.
     * @return mixed
     * @throws SqualoMail_Error
     * @throws SqualoMail_HttpError
     */
    public function delete($listId)
    {
        return $this->_master->call('lists/' . $listId, null, Ebizmarts_SqualoMail::DELETE);
    }
}
