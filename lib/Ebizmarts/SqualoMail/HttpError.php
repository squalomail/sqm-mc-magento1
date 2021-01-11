<?php
/**
 * squalomail-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   #PAC4#
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/27/16 4:45 PM
 * @file:     Exceptions.php
 */

class MailChimp_HttpError extends MailChimp_Error
{
    /**
     * @var array
     */
    protected $_squalomailErrors;

    /**
     * @var string
     */
    protected $_squalomailTitleComplete;

    /**
     * @var string
     */
    protected $_squalomailDetails;

    /**
     * @var string
     */
    protected $_squalomailTitle;

    /**
     * @var string
     */
    protected $_squalomailUrl;

    /**
     * @var string
     */
    protected $_squalomailMethod;

    /**
     * @var string
     */
    protected $_squalomailParams;

    public function __construct($url = "", $method = "", $params = "", $title = "", $details = "", $errors = null)
    {
        $titleComplete = $title . " for Api Call: " . $url;
        parent::__construct($titleComplete . " - " . $details);
        $this->_squalomailTitleComplete = $titleComplete;
        $this->_squalomailDetails = $details;
        $this->_squalomailErrors = $errors;
        $this->_squalomailUrl = $url;
        $this->_squalomailTitle = $title;
        $this->_squalomailMethod = $method;
        $this->_squalomailParams = $params;
    }

    public function getFriendlyMessage()
    {
        $friendlyMessage = $this->_squalomailTitle . " for Api Call: ["
            . $this->_squalomailUrl. "] using method ["
            .$this->_squalomailMethod."]\n";
        $friendlyMessage .= "\tDetail: [".$this->_squalomailDetails."]\n";
        if (!empty($this->_squalomailErrors)) {
            $errorDetails = "";
            foreach ($this->_squalomailErrors as $error) {
                $field = array_key_exists('field', $error) ? $error['field'] : '';
                $message = array_key_exists('message', $error) ? $error['message'] : '';
                $line = "\t\t field [$field] : $message\n";
                $errorDetails .= $line;
            }

            $friendlyMessage .= "\tErrors:\n".$errorDetails;
        }

        if (!is_array($this->_squalomailParams)) {
            $friendlyMessage .= "\tParams:\n\t\t".$this->_squalomailParams;
        } elseif (!empty($this->_squalomailParams)) {
            $friendlyMessage .= "\tParams:\n\t\t" . json_encode($this->_squalomailParams) . "\n";
        }

        return $friendlyMessage;
    }

    /**
     * @return string
     */
    public function getMailchimpTitleComplete()
    {
        return $this->_squalomailTitleComplete;
    }

    /**
     * @return string
     */
    public function getMailchimpDetails()
    {
        return $this->_squalomailDetails;
    }

    /**
     * @return array|null
     */
    public function getMailchimpErrors()
    {
        return $this->_squalomailErrors;
    }

    /**
     * @return string
     */
    public function getMailchimpTitle()
    {
        return $this->_squalomailTitle;
    }

    /**
     * @return string
     */
    public function getMailchimpUrl()
    {
        return $this->_squalomailUrl;
    }

    /**
     * @return string
     */
    public function getMailchimpMethod()
    {
        return $this->_squalomailMethod;
    }

    /**
     * @return string
     */
    public function getMailchimpParams()
    {
        return $this->_squalomailParams;
    }
}
