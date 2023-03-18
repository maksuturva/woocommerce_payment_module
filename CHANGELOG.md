## Changelog

### 2.3.9 - 2023-03-18
* Modified cart fee filtering to allow more fee types
* New release scripts
* Documentation update, for example Delivery confirmation is now documented
* Tested on WooCommerce 7.5

### 2.3.8 - 2023-02-21
* Fixed Part Payment Widget was not activated correctly for the production environment

### 2.3.7 - 2023-02-18
* Fixed error on the admin order pager when the payment method is not Svea payment method

### 2.3.6 - 2023-02-08
* SD2Q-10 Display payment method on order page

### 2.3.5 - 2023-01-18
* Fixed Pay for Order payment method selection

### 2.3.4 - 2022-12-30
* Cleaned outbound payment method texts on the checkout page

### 2.3.3 - 2022-12-13
* Added hook support for external payment ids

### 2.3.2 - 2022-11-29
* New, if only one payment method is in the group, select automatically the only one

### 2.3.1 - 2022-11-27
* New, payment method group titles on the checkout page are customizable on the admin page

### 2.3.0 - 2022-11-21
* Added configuration fields to the admin panel for the Part Payment Widget

### 2.2.4 - 2022-10-04
* Fixed, empty the shopping cart only when the payment was successful

### 2.2.3 - 2022-09-22
* Fixed, filter problematic product names before hash calculation

### 2.2.2 - 2022-09-08
* Fixed double fee error when using payment handling fees

### 2.2.1 - 2022-08-31
* Changed the locale handling. For payment, use get_user_locale() instead get_locale()

### 2.2.0 - 2022-08-16
* MAK-14 Add option to send delivery confirmation only for specific payment methods (#42)
* SD2Q-5 Modernized look & feel for payment method radio buttons
* SD2Q-5 Move style & scripts to separate file to avoid duplicate content
* SD2Q-5 align payment methods w/o handling fee
* Update translations
* S2DQ-8 Add payment method to payment class & db
* SD2Q-8 Add option to send delivery confirmation only for specific payment methods
* SD2Q-8 Add translations for specific payment delivery notification
* SD2Q-6 (WIP) Add outbound payments feature for Svea gateway
* SD2Q-6 (WIP) Hide Svea sub-payment methods if outbound payment is enabled
* SD2Q-6 (WIP) Hide handling cost settings if outbound payment is enabled
* Changed pmt_buyeremail field length 40 -> 320

### 2.1.25 - 2022-06-21
* Fixed the part payment widget to work with included or excluded tax catalog prices

### 2.1.24 - 2022-04-25
* Fixed the handling fee display in the checkout payment selection page PR#40

### 2.1.23 - 2022-04-21
* Quantities with decimals fixed to use comma as a separator

### 2.1.22 - 2022-04-05
* Changed Delivery info code from UNRDL -> ODLVR

### 2.1.21 - 2022-03-27
* Fixed the database truncate function call (#38) when upgrading from a very old version

### 2.1.20 - 2022-03-13
* Fixed the handling fee display in the checkout payment selection page

### 2.1.19 - 2022-03-10
* Fixed, the admin panel crashed if there was no additional tax classes available

### 2.1.18 - 2022-03-06
* Changed, more counter and time window checks to the status query to avoid unneccessary queries

### 2.1.17 - 2022-02-13
* Fixed, recompiled the language localization files
* Fixed, the part payment widget locale
* Changed, the activated part payment widget is visible only when the product price is equal or greater than 50.00
* Changed, status query is skipped if the order is older than 7 days

### 2.1.16
* Changed Pivo and Siirto to Card Payments
* Added Svea Part Payment widget configuration switch to the admin page

### 2.1.15
* Added a template implementation for Svea Part Payment widget on the product page, see docs/Part Payment Widget.md for more information

### 2.1.14
* Changed, the cart is saved when the payment is not completed
* Changed Maksuturva branding in UI messages

### 2.1.13
* Fixed the payment method listing when only one payment method is available

### 2.1.12
* Changed to skip status query check when in sandbox mode

### 2.1.11
* Added status query check to validate the order data to match the requested order
 
### 2.1.10
* Fixed the checkout page was broken if the module was unable to communicate the backend API
* Changed the admin page fields order
 
### 2.1.9
* Fixed secret key version parameter on APIs

### 2.1.8
* Fixed selected payment method undefined index -error after payment is successful
* Fixed deprecated item product data access 

### 2.1.7
* Fixed woocommerce available payment gateways -hook added only when request is not for admin page (reported issue #23)

### 2.1.6
* Fixed some bugs seen on the logfile and meta box is not rendered when payment method is not yet selected.

### 2.1.5
* Added support for the special delivery and billing information for Svea Payments Estonia partners 

### 2.1.4
* Added a new payment method class for Svea Payments Estonia partners  
* Fixed checkout page when there is only one payment method available

### 2.1.3
* Fixed Admin settings for Payment handling fees. Empty table and row removal functionality fixed. Allow comma separated
fee values.  

### 2.1.2
* Added refund functionality to the Woocommerce order management and link to open the event in Svea Extranet.
* Added functionality for sending delivery info without tracking to Svea API
* Added possibility to define additional costs for payment methods

### 2.1.1
* Fixed hanging order status queries. If order is deleted and trashed, remove payment from status query queue in next 
status check.
* Changed static 5 minute payment status query interval to more dynamic implementation. If order age is below 2 hours
check once in ten minutes. If order age is 2 to 24 hours check every other hour. After that check twice a day.

### 2.1.0
* Brand Maksuturva changed to Svea Payments

### 2.0.9
* Added timestamp and platform information to status query API request
* Changed sales_tax ALV rounding to nearest .5 decimal

### 2.0.8
* Removed usage of deprecated class WC_Order_Item_Meta if WooCommerce version is 3 or higher

### 2.0.7
* Fix issue WooCommerce compatibility classes not loading in Linux environment

### 2.0.6
* Not using deprecated magic order and product properties when WooCommerce major version is 3 or higher
* Notifications on successful and unsuccessful payments removed for WooCommerce 3.x as they are no longer implicitly rendered and no good way to fix it exists.

### 2.0.5
* Fix issue when payment status log table size increases. There will only be one status log per payment.
* IMPORTANT: The plugin update will truncate the payment status log table, so it's essential that you back
up your database table before updating the plugin, if you want to preserve the logs.

### 2.0.4
* Fix order total discount issue, by forcing the order total discount value to be float.
* Fix issue when no fee payment rows are added to the order. Re-calculate totals to match fees.

### 2.0.3
* Fix db update between plugin versions

### 2.0.2
* Fix order status after successful payment status query to Maksuturva
* Improve order processing to allow for double-submissions from gateway
* Add cron script to check for payment status periodically

### 2.0.1
* Fix JavaScript redirect to payment gateway on receipt page
* Fix payment table update on plugin re-activation
* Fix issue with paying an already pending order

### 2.0.0
* Complete re-factoring of the plugin surce code to follow WordPress and WooCommerce standards and best practices.
* Issue #1: on_uninstall, do_background_checks is too generic global functions.
The plugin now uses classes and the function names have been renamed.
* Removed the eMaksut option from plugin administration, and always send "Y" as value for the "escrow" field.
Maksuturva handles this mode internally, and the setting in the plugin has no effect.
* The plugin now creates a separate row for additional fees (e.g. invoicing fee) added to orders in Maksuturva service.
The fee is shown as an extra row in the order both on the receipt and the administration interface.

### 1.0.0
* Initial release