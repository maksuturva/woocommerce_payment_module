## Changelog

### 2.6.8 - 2024-08-28
* 274259: Display error message when payment methods are not available

### 2.6.7 - 2024-06-25
* 270005: Polylang for WooCommerce 1.9.5 compatibility

### 2.6.6 - 2024-06-16
* 269041: Prevent redundant delivery info requests
* 264329: Replace deprecated cancel_order method
* Fixed the refund not to be completed when there is communication problems with Svea API
* Fixed addDeliveryInfo event to fire only once per status change

### 2.6.5 - 2024-06-08
* Fixed a strtime warning in the status query
* Fixed logic for checking amount if the refund is full or partial refund

### 2.6.4 - 2024-04-21
* 256062: Fetch response body before parsing.
* 264592: Remove double slash from request URL

### 2.6.3 - 2024-04-15
* 263599: Reduce payment method API calls
* 262608: Apply PHPCBF fixes
* 260782: Load part payment calculator script with WP functions.
* 260781: Replace text domains with static strings
* 260779: Replace Curl with WordPress HTTP API.

### 2.6.2 - 2024-03-07
* 260350: Combine user settings with part payments plans fetched from the API.

### 2.6.1 - 2024-02-27
* 259691: Display part payment widget according to the part payment plans fetched from the API.
* 259691: Add settings field for part payment minimum price.

### 2.6.0 - 2024-02-05
* New WooCommerce Gift Cards plugin support

### 2.5.3 - 2024-01-25
* Fixed the meta box on the admin page when creating a new order and HPOS mode enabled

### 2.5.2 - 2024-01-17
* Added "After add to cart form" location option for the Part Payment Widget

### 2.5.1 - 2024-01-04
* Added feature flag to describe incompatiblity with new WooCommerce blocks mode checkout page theme.

### 2.5.0 - 2024-01-03
* Support for WooCommerce High-Performance Order Storage (HPOS) mode. See Svea_Payment_Gateway_Manual.pdf section 11.
* Note! Version 2.5.0 and below has issues with new WooCommerce >8.3 installations with new upgraded checkout
  experience. See https://developer.woo.com/2023/11/16/woocommerce-8-3-0-released/

### 2.4.4 - 2023-10-10
* Fixed to show user error text when payment returns error

### 2.4.3 - 2023-08-27
* Changed default payment method order when initializing the module first time
* Changed pmt_row_desc to be "-" always

### 2.4.2 - 2023-07-17
* Fixed payment method query not to unintentionally flood requests  
* Product name sanitation changed to convert special characters to underscore characters

### 2.4.1 - 2023-06-11
* Mini part payment widget option added
* Optional locations for part payment widget

### 2.4.0 - 2023-04-18
* New Collated payment methods view
* Sandbox mode removed, see documentation for testing instructions

### 2.3.10 - 2023-03-28
* Part Payment widget changed to use action html output instead of price filtering

### 2.3.9 - 2023-03-19
* Extra fees support fixed
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
