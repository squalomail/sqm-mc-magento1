<?php
/**
 * SqualoMail For Magento
 *
 * @category  Ebizmarts_SqualoMail
 * @author    Ebizmarts Team <info@ebizmarts.com>
 * @copyright Ebizmarts (http://ebizmarts.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date:     4/29/16 3:55 PM
 * @file:     Config.php
 */
class Ebizmarts_SqualoMail_Model_Config
{
    const GENERAL_ACTIVE                        = 'squalomail/general/active';
    const GENERAL_APIKEY                        = 'squalomail/general/apikey';
    const GENERAL_OAUTH_WIZARD                  = 'squalomail/general/oauth_wizard';
    const GENERAL_ACCOUNT_DETAILS               = 'squalomail/general/account_details';
    const GENERAL_LIST                          = 'squalomail/general/list';
    const GENERAL_OLD_LIST                      = 'squalomail/general/old_list';
    const GENERAL_LIST_CHANGED_SCOPES           = 'squalomail/general/list_changed_scopes';
    const GENERAL_CHECKOUT_SUBSCRIBE            = 'squalomail/general/checkout_subscribe';
    const GENERAL_MCSTOREID                     = 'squalomail/general/storeid';
    const GENERAL_MCISSYNCING                   = 'squalomail/general/is_syicing';
    const GENERAL_SUBMINSYNCDATEFLAG            = 'squalomail/general/subminsyncdateflag';
    const GENERAL_TWO_WAY_SYNC                  = 'squalomail/general/webhook_active';
    const GENERAL_UNSUBSCRIBE                   = 'squalomail/general/webhook_delete';
    const GENERAL_WEBHOOK_ID                    = 'squalomail/general/webhook_id';
    const GENERAL_LOG                           = 'squalomail/general/enable_log';
    const GENERAL_ORDER_GRID                    = 'squalomail/general/order_grid';
    const GENERAL_MAP_FIELDS                    = 'squalomail/general/map_fields';
    const GENERAL_CUSTOM_MAP_FIELDS             = 'squalomail/general/customer_map_fields';
    const GENERAL_MIGRATE_FROM_115              = 'squalomail/general/migrate_from_115';
    const GENERAL_MIGRATE_FROM_116              = 'squalomail/general/migrate_from_116';
    const GENERAL_MIGRATE_FROM_1164             = 'squalomail/general/migrate_from_1164';
    const GENERAL_MIGRATE_FROM_1120             = 'squalomail/general/migrate_from_1120';
    const GENERAL_MIGRATE_LAST_ORDER_ID         = 'squalomail/general/migrate_last_order_id';
    const GENERAL_SUBSCRIBER_AMOUNT             = 'squalomail/general/subscriber_batch_amount';
    const GENERAL_TIME_OUT                      = 'squalomail/general/connection_timeout';
    const GENERAL_INTEREST_CATEGORIES           = 'squalomail/general/interest_categories';
    const GENERAL_INTEREST_SUCCESS_BEFORE       = 'squalomail/general/interest_success_before';
    const GENERAL_INTEREST_SUCCESS_AFTER        = 'squalomail/general/interest_success_after';
    const GENERAL_INTEREST_SUCCESS_ACTIVE       = 'squalomail/general/interest_in_success';
    const GENERAL_MAGENTO_MAIL                  = 'squalomail/general/magento_mail';

    const ECOMMERCE_ACTIVE              = 'squalomail/ecommerce/active';
    const ECOMMERCE_CUSTOMERS_OPTIN     = 'squalomail/ecommerce/customers_optin';
    const ECOMMERCE_FIRSTDATE           = 'squalomail/ecommerce/firstdate';
    const ECOMMERCE_MC_JS_URL           = 'squalomail/ecommerce/mc_js_url';
    const ECOMMERCE_CUSTOMER_LAST_ID    = 'squalomail/ecommerce/customer_last_id';
    const ECOMMERCE_PRODUCT_LAST_ID     = 'squalomail/ecommerce/product_last_id';
    const ECOMMERCE_ORDER_LAST_ID       = 'squalomail/ecommerce/order_last_id';
    const ECOMMERCE_CART_LAST_ID        = 'squalomail/ecommerce/cart_last_id';
    const ECOMMERCE_PCD_LAST_ID         = 'squalomail/ecommerce/pcd_last_id';
    const ECOMMERCE_RESEND_ENABLED      = 'squalomail/ecommerce/resend_enabled';
    const ECOMMERCE_RESEND_TURN         = 'squalomail/ecommerce/resend_turn';
    const ECOMMERCE_CUSTOMER_AMOUNT     = 'squalomail/ecommerce/customer_batch_amount';
    const ECOMMERCE_PRODUCT_AMOUNT      = 'squalomail/ecommerce/product_batch_amount';
    const ECOMMERCE_ORDER_AMOUNT        = 'squalomail/ecommerce/order_batch_amount';
    const ECOMMERCE_IMAGE_SIZE          = 'squalomail/ecommerce/image_size';
    const ECOMMERCE_SYNC_DATE           = 'squalomail/ecommerce/sync_date';
    const ECOMMERCE_SEND_PROMO          = 'squalomail/ecommerce/send_promo';
    const ECOMMERCE_XML_INCLUDE_TAXES   = 'squalomail/ecommerce/include_taxes';

    const IMAGE_SIZE_DEFAULT            = 'image';
    const IMAGE_SIZE_SMALL              = 'small_image';
    const IMAGE_SIZE_THUMBNAIL          = 'thumbnail';
    const PRODUCT_IMAGE_CACHE_FLUSH     = 'squalomail/ecommerce/product_image_cache_flush';
    const ADD_SQUALOMAIL_LOGO_TO_GRID    = 1;
    const ADD_SYNC_STATUS_TO_GRID       = 2;
    const ADD_BOTH_TO_GRID              = 3;

    const ENABLE_POPUP                  = 'squalomail/emailcatcher/popup_general';
    const POPUP_HEADING                 = 'squalomail/emailcatcher/popup_heading';
    const POPUP_TEXT                    = 'squalomail/emailcatcher/popup_text';
    const POPUP_FNAME                   = 'squalomail/emailcatcher/popup_fname';
    const POPUP_LNAME                   = 'squalomail/emailcatcher/popup_lname';
    const POPUP_WIDTH                   = 'squalomail/emailcatcher/popup_width';
    const POPUP_HEIGHT                  = 'squalomail/emailcatcher/popup_height';
    const POPUP_SUBSCRIPTION            = 'squalomail/emailcatcher/popup_subscription';
    const POPUP_CAN_CANCEL              = 'squalomail/emailcatcher/popup_cancel';
    const POPUP_COOKIE_TIME             = 'squalomail/emailcatcher/popup_cookie_time';
    const POPUP_INSIST                  = 'squalomail/emailcatcher/popup_insist';

    const ABANDONEDCART_ACTIVE          = 'squalomail/abandonedcart/active';
    const ABANDONEDCART_FIRSTDATE       = 'squalomail/abandonedcart/firstdate';
    const ABANDONEDCART_PAGE            = 'squalomail/abandonedcart/page';
    const ABANDONEDCART_AMOUNT          = 'squalomail/abandonedcart/cart_batch_amount';

    const MANDRILL_APIKEY               = 'mandrill/general/apikey';
    const MANDRILL_ACTIVE               = 'mandrill/general/active';
    const MANDRILL_LOG                  = 'mandrill/general/enable_log';

    const IS_CUSTOMER                   = "CUS";
    const IS_PRODUCT                    = "PRO";
    const IS_ORDER                      = "ORD";
    const IS_QUOTE                      = "QUO";
    const IS_SUBSCRIBER                 = "SUB";
    const IS_PROMO_RULE                 = "PRL";
    const IS_PROMO_CODE                 = "PCD";
}
