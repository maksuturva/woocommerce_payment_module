# Svea Payments for WooCommerce
**Contributors:** Svea Payments  
**Tags:** maksuturva, payment gateway, svea, svea payments  
**Requires at least:** 4.0  
**Tested up to:** 5.5.3  
**Stable tag:** 2.1.5  
**WC requires at least:** 3.8.1  
**WC tested up to:** 4.8.0   
**License:** LGPL v. 2.1 or later  
**License URI:** https://www.gnu.org/licenses/lgpl-2.1.html  

Svea Payments payment module for WooCommerce.

## Description

Copyright (C) 2016-2021 Svea Development Oy

This library is free software; you can redistribute it and/or modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either version 2.1 of the License, or (at your option) any later
version. [GNU LGPL v. 2.1 @gnu.org] (https://www.gnu.org/licenses/lgpl-2.1.html)

This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public License for
more details.

> Contact information:
Svea Payments Oy
Mechelininkatu 1a
00180 Helsinki
Finland
e-mail: info.payments@svea.fi

Svea Development Oy, hereby disclaims all copyright interest in the library 'maksuturva-for-woocommerce'
written for Svea Payments Oy

[https://www.sveapayments.fi](https://www.sveapayments.fi/)

## Changelog

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
