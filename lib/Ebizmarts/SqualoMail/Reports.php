<?php
/**
 * squalomail-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     5/2/16 4:48 PM
 * @file:     Reports.php
 */
class SqualoMail_Reports extends SqualoMail_Abstract
{
    /**
     * @var SqualoMail_ReportsCampaignAdvice
     */
    public $campaignAdvice;
    /**
     * @var SqualoMail_ReportsClickReports
     */
    public $clickReports;
    /**
     * @var SqualoMail_ReportsDomainPerformance
     */
    public $domainPerformance;
    /**
     * @var ReportsEapURLReport
     */
    public $eapURLReport;
    /**
     * @var SqualoMail_ReportsEmailActivity
     */
    public $emailActivity;
    /**
     * @var ReportsLocation
     */
    public $location;
    /**
     * @var SqualoMail_ReportsSentTo
     */
    public $sentTo;
    /**
     * @var SqualoMail_ReportsSubReports
     */
    public $subReports;
    /**
     * @var SqualoMail_ReportsUnsubscribes
     */
    public $unsubscribes;
}
