<?php
/**
 * squalomail-lib Magento Component
 *
 * @category  Ebizmarts
 * @package   mailchimp-lib
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/27/16 4:45 PM
 * @file:     Exceptions.php
 */

class SqualoMail_Error extends Exception
{

    /**
     * @var string
     */
    protected $_squalomailMessage;

    public function __construct($message = "")
    {
        $this->_squalomailMessage = $message;
        parent::__construct($message);
    }

    public function getFriendlyMessage()
    {
        $friendlyMessage = "Squalomail error with the next message: " . $this->_squalomailMessage;

        return $friendlyMessage;
    }
}
