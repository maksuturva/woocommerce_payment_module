## Changelog

### 3.0.0 - 2026-10-11
* Tested up to WooCommerce 11.2.1
* Fixed WooCommerce 3.0 deprecation warnings for `WC_Order::add_fee()` by migrating to `WC_Order_Item_Fee`
* Fixed legacy bug preventing admin scripts from loading (updated section check for `sveapafi_gateway`)
* Added JavaScript validation to prevent enabling Part Payment Widget on Cart/Checkout pages when Blocks Mode is active (Product page remains allowed)
* Optimized background status checks to automatically skip and cancel orders marked as 'cancelled' or 'failed' in WooCommerce
* Renamed scheduled cron event to `sveapafi_check_pending_payments` with automatic cleanup of legacy hooks
* Added developer documentation for background status query time windows (`docs/Status-Query-Windows.md`)
* Refined main admin method description to better reflect modern B2B/B2C features
* Namespaces and class names updated to be compliant with WordPress standards
* Added support for Wordpress Marketplace
* Estonia Payment method removed
* Added Apple Pay and Google Pay payment methods
* Added support for WooCommerce Extra Shipping Options by Ace plugin
* Added support for YITH WooCommerce Gift Cards plugin
* Added support for filter `svea_payment_filter_payment_methods` to modify payment methods
* Added support for filter `svea_payment_buyer_identification_code` to modify buyer identification code in the payment data
* Added payment methods FIIN, FIPP, and FIBI to the invoice and hire-purchase payment method group
* Fixed deprecated `WC_Order_Item` / `WC_Order_Item_Fee` array access methods for modern PHP compatibility
* Truncate product SKUs to max 100 characters to prevent Svea API validation errors
* Updated sub-gateway descriptions to clarify they are legacy views when Blocks mode is enabled
* Added validation to prevent enabling separate payment methods when Blocks Checkout mode is active
* Refactored codebase to remove legacy WooCommerce order compatibility handlers in favor of modern native getter methods
