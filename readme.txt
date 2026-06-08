=== Svea Payments Finland for WooCommerce ===
Contributors: sveamaintainer
Tags: svea, payment gateway, finland, woocommerce
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 3.0.0
Requires PHP: 7.4
License: LGPLv2.1
License URI: https://www.gnu.org/licenses/lgpl-2.1.html

Svea Payments Finland for WooCommerce provides intelligent online payment services for the Finnish market.

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

== External Services ==

This plugin is designed for the Svea Payments payment gateway in Finland on the WooCommerce platform. It connects to external API services hosted by Svea Payments Oy (on secure domains such as `maksuturva.fi` and `svea.fi`) to process transactions.

The plugin processes WooCommerce orders on the cart and checkout pages, securely transferring order information to Svea Payments, and features full integration with WooCommerce payment and order management.

The plugin integrates with the following external APIs:
* **Payment API**: Initiates and handles checkout payment transactions.
* **Payment Status Query API**: Queries the final status of a payment.
* **Delivery Info API**: Submits shipping and delivery updates to Svea.
* **Refunds and Cancellation API**: Allows processing refunds and cancellations directly from the WooCommerce admin dashboard.
* **Part Payment Calculator**: Connects to the Svea API to dynamically calculate and display monthly payment installments for the customer on the product, cart, and checkout pages.

**Data Sent**:
During checkout and order processing, the plugin securely transmits transaction-relevant data to Svea Payments Oy, which includes:
* Order information (amounts, currencies, items, quantities, and tax rates).
* Buyer details (name, billing and shipping addresses, email, and phone number).
* Selected payment method.

**Conditions & Environments**:
* The plugin supports both test (sandbox) and production environments.
* More comprehensive documentation can be found in the `docs` directory.

**Service Links**:
* Terms of Service: [Svea Payments Terms of Service](https://www.svea.com/globalassets/finland/documents/maksupalvelut/Yleiset_sopimusehdot_kauppias_EN.pdf)
* Privacy Policy: [Svea Payments Privacy Policy](https://www.svea.com/fi-fi/tietoa-meista/tietosuoja/privacy-policy-svea-payments-consumer)
* API Service Documentation: [Svea Payments API Documentation](https://sveapayments.atlassian.net/wiki/spaces/DOCS/pages/1657012281/API)

== Installation ==

For detailed installation and configuration instructions, please refer to the [Svea Payment Gateway Manual (PDF)](https://github.com/maksuturva/woocommerce_payment_module/blob/master/docs/Svea_Payment_Gateway_Manual.pdf).

1. Upload the plugin files to the `/wp-content/plugins/svea-payment-gateway` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the plugin settings under WooCommerce > Settings > Payments > Svea.

== Changelog ==

* See [CHANGELOG.md](https://github.com/maksuturva/woocommerce_payment_module/blob/master/CHANGELOG.md) for full history.
