# SqualoMail For Magento 1

## SqualoMail Integration

Integration to sync all the Magento data (Newsletter subscriber, Customers, Orders, Products) with SqualoMail. It allows to use all the SqualoMail potential for email Marketing such as sending Campaigns, Automations and more.

## Main Features

* Two way sync between a SqualoMail list and Magento’s newsletter

## Prerequisities

Magento Community Edition (1.7 or above) or Magento Enterprise (1.11 or above)

SqualoMail account

## Step Installation

To get a copy of the project up and running on your local machine for development and testing purposes, just clone this repository on your Magento’s root directory and flush the Magento’s cache.

Alternatively, use modman to install this module.

``modman clone https://github.com/squalomail/sqm-mc-magento1.git -b 'master'``

## Module Configuration

To enable SqualoMail For Magento:

1. Go to System -> Configuration -> SQUALOMAIL -> SqualoMail Configuration -> Select scope on your Magento’s back end.<br />
2. Click the <b>Get API credentials</b> and place your SqualoMail credentials, then an API Key will be shown.<br />
3. Paste the API Key on SqualoMail For Magento’s configuration and click <b>Save Config</b><br />
4. When the page is loaded again select the desired audience to sync with the Magento’s newsletter audience. At this point your Magento subscribers will start being sent to the configured SqualoMail audience.<br />
5. If you have a paid SqualoMail account and want to use SqualoMail Automations go to "<b>Default Config</b>" scope and to the Ecommerce section and set it to Enabled. Now all your store information (Products, orders, customers and carts) will start being sent to SqualoMail's associated audience at your "<b>Default Config</b>" scope.

## License

[Open Software License (OSL 3.0)](http://opensource.org/licenses/osl-3.0.php)
