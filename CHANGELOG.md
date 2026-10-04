## Changelog

### 3.0.0 - 2026-10-04
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
