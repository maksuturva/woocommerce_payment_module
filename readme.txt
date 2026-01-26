=== Svea Payment Gateway ===
Contributors: svea_payments
Tags: svea, payment gateway, finland, woocommerce
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 3.0.0
Requires PHP: 7.4
License: LGPLv2.1
License URI: https://www.gnu.org/licenses/lgpl-2.1.html

Svea Payments Gateway Plugin for WooCommerce provides intelligent online payment services for the Finnish market.

== Description ==

This is the official payment module for WooCommerce by Svea Payments.

There is no guarantee that the module is fully functional in any other environment which does not fulfill the requirements.

For WooCommerce versions >8.3, see Docs for new feature compatibility.

= Features =

* All Finnish payment methods: bank payments, cards, mobile payments, Svea Invoice, Svea Part Payment and Svea B2B Invoice
* Customizable layout at checkout
* Refunds
* Send delivery info
* Svea's part payment calculator
* Delayed capture

= Documentation =

* Changelog: [CHANGELOG.md](https://github.com/maksuturva/woocommerce_payment_module/blob/master/CHANGELOG.md)
* Installation and administration guide: [docs/Svea_Payment_Gateway_Manual.pdf](https://github.com/maksuturva/woocommerce_payment_module/blob/master/docs/Svea_Payment_Gateway_Manual.pdf)

= Filters =

* `svea_payment_gateway_payment_method_error_message` - Filter for changing the error message when payment method is not available.
* `svea_payment_gateway_payment_error_return_url` - Filter for changing the return URL when payment is returned with error state.

= Support =

For General support, please contact info.payments@svea.fi
For Technical support, please contact support.payments@svea.fi

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/svea-payment-gateway` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the plugin settings under WooCommerce > Settings > Payments > Svea.

== Changelog ==

* See [CHANGELOG.md](https://github.com/maksuturva/woocommerce_payment_module/blob/master/CHANGELOG.md) for full history.
