# Payment Module for WooCommerce

Contributors: Svea Payments Oy and Svea Bank AB, filial i Finland  
Tags: svea, payment gateway, finland  

# System requirements

* Requires at least: 6.0    
* Tested up to: 7.1.0                  
* Stable tag: 2.7.6                  
* WC requires at least: 8.0  
* WC tested up to: 11.0.1    

There is no guarantee that the module is fully functional in any other environment which does not fulfill the requirements.

For WooCommerce versions >8.3, see Docs for new feature compatibility.

# Features

* All Finnish payment methods: bank payments, cards, mobile payments, Svea Invoice, Svea Part Payment and Svea B2B Invoice
* Customizable layout at checkout
* Refunds
* Send delivery info
* Svea's part payment calculator
* Delayed capture
* Support for multiple gift card plugins and extra delivery options. See docs/Svea_Payment_Gateway_Manual.pdf
  
# Documentation

* Changelog: [CHANGELOG.md](https://github.com/maksuturva/woocommerce_payment_module/blob/master/CHANGELOG.md)
* Installation and administration guide: [docs/Svea_Payment_Gateway_Manual.pdf](https://github.com/maksuturva/woocommerce_payment_module/blob/master/docs/Svea_Payment_Gateway_Manual.pdf)

## Filters

* `svea_payment_gateway_payment_method_error_message` - Filter for changing the error message when payment method is not available.
* `svea_payment_gateway_payment_error_return_url` - Filter for changing the return URL when payment is returned with error state.
* `svea_payment_filter_payment_methods` - Filter for modifying the payment methods array before it is returned.
* `svea_payment_buyer_identification_code` - Filter for providing or modifying the buyer identification code before payment request is sent to the API.

# Support

For General support, please contant info.payments@svea.fi    
For Technical support, please contact support.payments@svea.fi
