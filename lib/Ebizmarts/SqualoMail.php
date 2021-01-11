<?php
/**
 * squalomail-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/27/16 4:36 PM
 * @file:     Squalomail.php
 */
if (defined("COMPILER_INCLUDE_PATH")) {
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/Abstract.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/Root.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/Automation.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/AutomationEmails.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/AutomationEmailsQueue.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/Exceptions.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/HttpError.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/AuthorizedApps.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/Automation.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/BatchOperations.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/CampaignFolders.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/Campaigns.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/CampaignsContent.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/CampaignsFeedback.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/CampaignsSendChecklist.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/Conversations.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ConversationsMessages.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/Ecommerce.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/EcommerceStores.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/EcommerceCarts.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/EcommerceCustomers.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/EcommerceOrders.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/EcommerceOrdersLines.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/EcommerceProducts.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/EcommerceProductsVariants.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/EcommercePromoRules.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/EcommercePromoRulesPromoCodes.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/FileManagerFiles.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/FileManagerFolders.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/Lists.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ListsAbuseReports.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ListsActivity.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ListsClients.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ListsGrowthHistory.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ListsInterestCategory.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ListsInterestCategoryInterests.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ListsMembers.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ListsMembersActivity.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ListsMembersGoals.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ListsMembersNotes.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ListsMergeFields.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ListsSegments.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ListsSegmentsMembers.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ListsWebhooks.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/Reports.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ReportsCampaignAdvice.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ReportsClickReports.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ReportsClickReportsMembers.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ReportsDomainPerformance.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ReportsEapURLReport.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ReportsEmailActivity.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ReportsLocation.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ReportsSentTo.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ReportsSubReports.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/ReportsUnsubscribes.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/TemplateFolders.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/Templates.php';
    include_once dirname(__FILE__) . '/Ebizmarts/SqualoMail/TemplatesDefaultContent.php';
} else {
    include_once dirname(__FILE__) . '/SqualoMail/Abstract.php';
    include_once dirname(__FILE__) . '/SqualoMail/Root.php';
    include_once dirname(__FILE__) . '/SqualoMail/Automation.php';
    include_once dirname(__FILE__) . '/SqualoMail/AutomationEmails.php';
    include_once dirname(__FILE__) . '/SqualoMail/AutomationEmailsQueue.php';
    include_once dirname(__FILE__) . '/SqualoMail/Exceptions.php';
    include_once dirname(__FILE__) . '/SqualoMail/HttpError.php';
    include_once dirname(__FILE__) . '/SqualoMail/AuthorizedApps.php';
    include_once dirname(__FILE__) . '/SqualoMail/Automation.php';
    include_once dirname(__FILE__) . '/SqualoMail/BatchOperations.php';
    include_once dirname(__FILE__) . '/SqualoMail/CampaignFolders.php';
    include_once dirname(__FILE__) . '/SqualoMail/Campaigns.php';
    include_once dirname(__FILE__) . '/SqualoMail/CampaignsContent.php';
    include_once dirname(__FILE__) . '/SqualoMail/CampaignsFeedback.php';
    include_once dirname(__FILE__) . '/SqualoMail/CampaignsSendChecklist.php';
    include_once dirname(__FILE__) . '/SqualoMail/Conversations.php';
    include_once dirname(__FILE__) . '/SqualoMail/ConversationsMessages.php';
    include_once dirname(__FILE__) . '/SqualoMail/Ecommerce.php';
    include_once dirname(__FILE__) . '/SqualoMail/EcommerceStores.php';
    include_once dirname(__FILE__) . '/SqualoMail/EcommerceCarts.php';
    include_once dirname(__FILE__) . '/SqualoMail/EcommerceCustomers.php';
    include_once dirname(__FILE__) . '/SqualoMail/EcommerceOrders.php';
    include_once dirname(__FILE__) . '/SqualoMail/EcommerceOrdersLines.php';
    include_once dirname(__FILE__) . '/SqualoMail/EcommerceProducts.php';
    include_once dirname(__FILE__) . '/SqualoMail/EcommerceProductsVariants.php';
    include_once dirname(__FILE__) . '/SqualoMail/EcommercePromoRules.php';
    include_once dirname(__FILE__) . '/SqualoMail/EcommercePromoRulesPromoCodes.php';
    include_once dirname(__FILE__) . '/SqualoMail/FileManagerFiles.php';
    include_once dirname(__FILE__) . '/SqualoMail/FileManagerFolders.php';
    include_once dirname(__FILE__) . '/SqualoMail/Lists.php';
    include_once dirname(__FILE__) . '/SqualoMail/ListsAbuseReports.php';
    include_once dirname(__FILE__) . '/SqualoMail/ListsActivity.php';
    include_once dirname(__FILE__) . '/SqualoMail/ListsClients.php';
    include_once dirname(__FILE__) . '/SqualoMail/ListsGrowthHistory.php';
    include_once dirname(__FILE__) . '/SqualoMail/ListsInterestCategory.php';
    include_once dirname(__FILE__) . '/SqualoMail/ListsInterestCategoryInterests.php';
    include_once dirname(__FILE__) . '/SqualoMail/ListsMembers.php';
    include_once dirname(__FILE__) . '/SqualoMail/ListsMembersActivity.php';
    include_once dirname(__FILE__) . '/SqualoMail/ListsMembersGoals.php';
    include_once dirname(__FILE__) . '/SqualoMail/ListsMembersNotes.php';
    include_once dirname(__FILE__) . '/SqualoMail/ListsMergeFields.php';
    include_once dirname(__FILE__) . '/SqualoMail/ListsSegments.php';
    include_once dirname(__FILE__) . '/SqualoMail/ListsSegmentsMembers.php';
    include_once dirname(__FILE__) . '/SqualoMail/ListsWebhooks.php';
    include_once dirname(__FILE__) . '/SqualoMail/Reports.php';
    include_once dirname(__FILE__) . '/SqualoMail/ReportsCampaignAdvice.php';
    include_once dirname(__FILE__) . '/SqualoMail/ReportsClickReports.php';
    include_once dirname(__FILE__) . '/SqualoMail/ReportsClickReportsMembers.php';
    include_once dirname(__FILE__) . '/SqualoMail/ReportsDomainPerformance.php';
    include_once dirname(__FILE__) . '/SqualoMail/ReportsEapURLReport.php';
    include_once dirname(__FILE__) . '/SqualoMail/ReportsEmailActivity.php';
    include_once dirname(__FILE__) . '/SqualoMail/ReportsLocation.php';
    include_once dirname(__FILE__) . '/SqualoMail/ReportsSentTo.php';
    include_once dirname(__FILE__) . '/SqualoMail/ReportsSubReports.php';
    include_once dirname(__FILE__) . '/SqualoMail/ReportsUnsubscribes.php';
    include_once dirname(__FILE__) . '/SqualoMail/TemplateFolders.php';
    include_once dirname(__FILE__) . '/SqualoMail/Templates.php';
    include_once dirname(__FILE__) . '/SqualoMail/TemplatesDefaultContent.php';
}

class Ebizmarts_SqualoMail
{
    /**
     * @var SqualoMail_BatchOperations
     */
    public $batchOperation;

    /**
     * @var SqualoMail_Root
     */
    public $root;

    /**
     * @var SqualoMail_AuthorizedApps
     */
    public $authorizedApps;

    /**
     * @var SqualoMail_Automation
     */
    public $automation;

    /**
     * @var SqualoMail_CampaignFolders
     */
    public $campaignFolders;

    /**
     * @var SqualoMail_Campaigns
     */
    public $campaigns;

    /**
     * @var SqualoMail_Conversations
     */
    public $conversations;

    /**
     * @var SqualoMail_Ecommerce
     */
    public $ecommerce;

    /**
     * @var SqualoMail_FileManagerFiles
     */
    public $fileManagerFiles;

    /**
     * @var SqualoMail_FileManagerFolders
     */
    public $fileManagerFolders;

    /**
     * @var SqualoMail_Lists
     */
    public $lists;

    /**
     * @var SqualoMail_Reports
     */
    public $reports;

    /**
     * @var SqualoMail_TemplateFolders
     */
    public $templateFolders;

    /**
     * @var SqualoMail_Templates
     */
    public $templates;

    protected $_apiKey;
    protected $_ch;
    protected $_root = 'http://host.docker.internal:61612';
    protected $_debug = false;

    const POST = 'POST';
    const GET = 'GET';
    const PATCH = 'PATCH';
    const DELETE = 'DELETE';
    const PUT = 'PUT';

    public function __construct($apiKey = null, $opts = array(), $userAgent = null)
    {
        if (!$apiKey) {
            throw new SqualoMail_Error('You must provide a SqualoMail API key');
        }

        $this->_apiKey = $apiKey;
        $this->_root = rtrim($this->_root, '/') . '/';

        if (!isset($opts['timeout']) || !is_int($opts['timeout'])) {
            $opts['timeout'] = 20;
        }

        if (isset($opts['debug'])) {
            $this->_debug = true;
        }


        $this->_ch = curl_init();

        if (isset($opts['CURLOPT_FOLLOWLOCATION']) && $opts['CURLOPT_FOLLOWLOCATION'] === true) {
            curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, true);
        }

        if ($userAgent) {
            curl_setopt($this->_ch, CURLOPT_USERAGENT, $userAgent);
        } else {
            curl_setopt($this->_ch, CURLOPT_USERAGENT, 'Ebizmart-SqualoMail-PHP/3.0.0');
        }

        curl_setopt($this->_ch, CURLOPT_HEADER, false);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, $opts['timeout']);
        curl_setopt($this->_ch, CURLOPT_TIMEOUT, $opts['timeout']);
        curl_setopt($this->_ch, CURLOPT_USERPWD, "noname:" . $this->_apiKey);

        $this->root = new SqualoMail_Root($this);
        $this->authorizedApps = new SqualoMail_AuthorizedApps($this);
        $this->automation = new SqualoMail_Automation($this);
        $this->automation->emails = new SqualoMail_AutomationEmails($this);
        $this->automation->emails->queue = new SqualoMail_AutomationEmailsQuque($this);
        $this->batchOperation = new SqualoMail_BatchOperations($this);
        $this->campaignFolders = new SqualoMail_CampaignFolders($this);
        $this->campaigns = new SqualoMail_Campaigns($this);
        $this->campaigns->content = new SqualoMail_CampaignsContent($this);
        $this->campaigns->feedback = new SqualoMail_CampaignsFeedback($this);
        $this->campaigns->sendChecklist = new SqualoMail_CampaignsSendChecklist($this);
        $this->conversations = new SqualoMail_Conversations($this);
        $this->conversations->messages = new SqualoMail_ConversationsMessages($this);
        $this->ecommerce = new SqualoMail_Ecommerce($this);
        $this->ecommerce->stores = new SqualoMail_EcommerceStore($this);
        $this->ecommerce->carts = new SqualoMail_EcommerceCarts($this);
        $this->ecommerce->customers = new SqualoMail_EcommerceCustomers($this);
        $this->ecommerce->orders = new SqualoMail_EcommerceOrders($this);
        $this->ecommerce->orders->lines = new SqualoMail_EcommerceOrdersLines($this);
        $this->ecommerce->products = new SqualoMail_EcommerceProducts($this);
        $this->ecommerce->products->variants = new SqualoMail_EcommerceProductsVariants($this);
        $this->ecommerce->promoRules = new SqualoMail_EcommercePromoRules($this);
        $this->ecommerce->promoRules->promoCodes = new SqualoMail_EcommercePromoRulesPromoCodes($this);
        $this->fileManagerFiles = new SqualoMail_FileManagerFiles($this);
        $this->fileManagerFolders = new SqualoMail_FileManagerFolders($this);
        $this->lists = new SqualoMail_Lists($this);
        $this->lists->abuseReports = new SqualoMail_ListsAbuseReports($this);
        $this->lists->activity = new SqualoMail_ListsActivity($this);
        $this->lists->clients = new SqualoMail_ListsClients($this);
        $this->lists->growthHistory = new SqualoMail_ListsGrowthHistory($this);
        $this->lists->interestCategory = new SqualoMail_ListsInterestCategory($this);
        $this->lists->interestCategory->interests = new SqualoMail_ListInterestCategoryInterests($this);
        $this->lists->members = new SqualoMail_ListsMembers($this);
        $this->lists->members->memberActivity = new SqualoMail_ListsMembersActivity($this);
        $this->lists->members->memberGoal = new SqualoMail_ListsMembersGoals($this);
        $this->lists->members->memberNotes = new SqualoMail_ListsMembersNotes($this);
        $this->lists->mergeFields = new SqualoMail_ListsMergeFields($this);
        $this->lists->segments = new SqualoMail_ListsSegments($this);
        $this->lists->segments->segmentMembers = new SqualoMail_ListsSegmentsMembers($this);
        $this->lists->webhooks = new SqualoMail_ListsWebhooks($this);
        $this->reports = new SqualoMail_Reports($this);
        $this->reports->campaignAdvice = new SqualoMail_ReportsCampaignAdvice($this);
        $this->reports->clickReports = new SqualoMail_ReportsClickReports($this);
        $this->reports->clickReports->clickReportMembers = new SqualoMail_ReportsClickReportsMembers($this);
        $this->reports->domainPerformance = new SqualoMail_ReportsDomainPerformance($this);
        $this->reports->eapURLReport = new SqualoMail_ReportsEapURLReport($this);
        $this->reports->emailActivity = new SqualoMail_ReportsEmailActivity($this);
        $this->reports->location = new SqualoMail_ReportsLocation($this);
        $this->reports->sentTo = new SqualoMail_ReportsSentTo($this);
        $this->reports->subReports = new SqualoMail_ReportsSubReports($this);
        $this->reports->unsubscribes = new SqualoMail_ReportsUnsubscribes($this);
        $this->templateFolders = new SqualoMail_TemplateFolders($this);
        $this->templates = new SqualoMail_Templates($this);
        $this->templates->defaultContent = new SqualoMail_TemplatesDefaultContent($this);
    }

    /**
     * @return SqualoMail_Root
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @return SqualoMail_Ecommerce
     */
    public function getEcommerce()
    {
        return $this->ecommerce;
    }

    /**
     * @return SqualoMail_BatchOperations
     */
    public function getBatchOperation()
    {
        return $this->batchOperation;
    }

    /**
     * @return SqualoMail_Lists
     */
    public function getLists()
    {
        return $this->lists;
    }

    /**
     * @return SqualoMail_Campaigns
     */
    public function getCampaign()
    {
        return $this->campaigns;
    }

    public function call($url, $params, $method = Ebizmarts_SqualoMail::GET, $encodeJson = true)
    {
        $paramsOrig = $params;

        $hasParams = true;
        if (is_array($params) && count($params) === 0 || $params == null) {
            $hasParams = false;
        }

        if ($hasParams && $encodeJson && $method != Ebizmarts_SqualoMail::GET) {
            $params = json_encode($params);
        }

        $headers = array(
            'Content-Type: application/json',
        );
        // check for language param to set as browser language
        // squalomail uses header/browser detection for the language rather than any profile setting
        if (is_array($paramsOrig) && array_key_exists('language', $paramsOrig) && !empty($paramsOrig['language'])) {
            $headers[] = 'Accept-Language: ' . $paramsOrig['language'];
        }

        $ch = $this->_ch;
        if ($hasParams && $method != Ebizmarts_SqualoMail::GET) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, null);
            if ($hasParams) {
                $_params = http_build_query($params);
                $url .= '?' . $_params;
            }
        }

        curl_setopt($ch, CURLOPT_URL, $this->_root . $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_VERBOSE, $this->_debug);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        $responseBody = curl_exec($ch);

        $info = curl_getinfo($ch);

        $result = json_decode($responseBody, true);

        $curlError = curl_error($ch);
        if (!empty($curlError)) {
            throw new SqualoMail_HttpError($url, $method, $params, '', "API call to $url failed: " . curl_error($ch));
        }

        if (floor($info['http_code'] / 100) >= 4) {
            if (is_array($result)) {
                $detail = array_key_exists('detail', $result) ? $result['detail'] : '';
                $errors = array_key_exists('errors', $result) ? $result['errors'] : null;
                $title = array_key_exists('title', $result) ? $result['title'] : '';

                throw new SqualoMail_HttpError($this->_root . $url, $method, $params, $title, $detail, $errors);
            } else {
                throw new SqualoMail_HttpError($this->_root . $url, $method, $params, $result);
            }
        }

        return $result;
    }
}
