<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 3.x, 4.x
 * Plugin developed for Svea
 * Last update: 30/11/2020
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * [GNU LGPL v. 2.1 @gnu.org] (https://www.gnu.org/licenses/lgpl-2.1.html)
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'class-wc-gateway-implementation-maksuturva.php';
require_once 'class-wc-payment-handling-costs.php';
require_once 'class-wc-svea-api-request-handler.php';
require_once 'class-wc-utils-maksuturva.php';

/**
 * Class WC_Payment_Method_Select.
 *
 * Handles functionalities related to the payment method selection.
 *
 * @since 2.1.3
 */
class WC_Payment_Method_Select {

	/**
	 * Id for payment method select
	 *
	 * @var string PAYMENT_METHOD_SELECT_ID
	 */
	public const PAYMENT_METHOD_SELECT_ID = 'svea_payment_method';

	/**
	 * Payment cancellation route.
	 *
	 * @var string ROUTE_ADD_DELIVERY_INFO
	 */
	private const ROUTE_RETRIEVE_AVAILABLE_PAYMENT_METHODS = '/GetPaymentMethods.pmt';

	/**
	 * Gateway.
	 *
	 * @var WC_Gateway_Maksuturva $gateway The gateway.
	 */
	private $gateway;

	/**
	 * Seller id.
	 *
	 * @var int $seller_id The seller id.
	 */
	private $seller_id;

	/**
	 * Available payment methods.
	 *
	 * @var array $available_payment_methods The available payment methods.
	 */
	private static $available_payment_methods;

	/**
	 * WC_Payment_Method_Select constructor.
	 * 
	 * @param WC_Gateway_Maksuturva $gateway The gateway.
	 * 
	 * @since 2.1.3
	 */
	public function __construct( WC_Gateway_Maksuturva $gateway ) {
		$this->gateway = $gateway;
		$this->seller_id = $gateway->get_seller_id();
	}

	/**
	 * Initialize payment method select box content
	 *
	 * @param int $price The price
	 *
	 * @since 2.1.3
	 */
	public function initialize_payment_method_select( $payment_type, $price ) {

		$payment_handling_costs_handler = new WC_Payment_Handling_Costs( $this->gateway );

		$this->gateway->render(
			'payment-method-' . $payment_type . '-form',
			'frontend',
			[
				'currency_symbol' => get_woocommerce_currency_symbol(),
				'payment_method_handling_costs' => $payment_handling_costs_handler->get_handling_costs_by_payment_method(),
				'payment_method_select_id' => self::PAYMENT_METHOD_SELECT_ID,
				'payment_methods' => $this->get_payment_type_payment_methods( $payment_type, $price ),
				'terms' => [
					'text' => $this->get_terms_text( $price ),
					'url' => $this->get_terms_url( $price )
				]
			]
		);
	}

	/**
	 * Validates that the payment method is selected.
	 *
	 * @since 2.1.3
	 *
	 * @return bool
	 */
	public function validate_payment_method_select() {

		if ( !isset( $_POST[WC_Payment_Method_Select::PAYMENT_METHOD_SELECT_ID] ) ) {
			wc_add_notice( __( 'Payment method not selected', $this->gateway->td ), 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Sorts payment methods to categories.
	 *
	 * @since 2.1.3
	 *
	 * @return array
	 */
	public function get_payment_type_payment_methods( $payment_type, $price ) {

		$available_payment_methods = $this->get_available_payment_methods( $price );

		$payment_type_payment_methods = [
			'credit-card-and-mobile' => [],
			'invoice-and-hire-purchase' => [],
			'online-bank-payments' => [],
			'other-payments' => [],
		];

		if ( isset( $available_payment_methods['ERROR'] ) ) {
			return $payment_type_payment_methods;
		}

		foreach ( $available_payment_methods['paymentmethod'] as $key => $payment_method ) {
			if ( in_array( substr( $payment_method['code'], 0, 3 ), ['FI0', 'FI1'] ) ) {
				$payment_type_payment_methods['online-bank-payments'][] = $payment_method;
				unset( $available_payment_methods['paymentmethod'][$key] );
			}
		}

		foreach ( $available_payment_methods['paymentmethod'] as $key => $payment_method ) {
			if ( in_array( substr($payment_method['code'], 0, 3 ), ['FI5']) ) {
				$payment_type_payment_methods['credit-card-and-mobile'][] = $payment_method;
				unset( $available_payment_methods['paymentmethod'][$key] );
			}
		}

		foreach ( $available_payment_methods['paymentmethod'] as $key => $payment_method ) {
			if ( in_array(substr($payment_method['code'], 0, 3), ['FI6', 'FI7']) ) {
				$payment_type_payment_methods['invoice-and-hire-purchase'][] = $payment_method;
				unset( $available_payment_methods['paymentmethod'][$key] );
			}
		}

		foreach ( $available_payment_methods['paymentmethod'] as $payment_method ) {
			$payment_type_payment_methods['other-payments'][] = $payment_method;
		}

		return $payment_type_payment_methods[$payment_type];
	}

	/**
	 * Fetches terms text
	 *
	 * @since 2.1.3
	 *
	 * @return string
	 */
	private function get_terms_text( $price ) {
		$available_payment_methods = $this->get_available_payment_methods( $price );
		return $available_payment_methods['termstext'];
	}

	/**
	 * Fetches terms url
	 *
	 * @since 2.1.3
	 *
	 * @return string
	 */
	private function get_terms_url( $price ) {
		$available_payment_methods = $this->get_available_payment_methods( $price );
		return $available_payment_methods['termsurl'];
	}

	/**
	 *	Fetches payment type payment methods from Svea api.
	 *
	 * @since 2.1.3
	 *
	 * @param int $price The price
	 *
	 * @return array
	 */
	private function get_available_payment_methods( $price ) {

		if ( isset( self::$available_payment_methods ) ) {
			return self::$available_payment_methods;
		}

		$post_fields = [
			'request_locale' => explode( '_', get_user_locale() )[0],
			'sellerid' => $this->seller_id,
			'totalamount' => WC_Utils_Maksuturva::filter_price( $price )
		];

		$api = new WC_Svea_Api_Request_Handler( $this->gateway );

		self::$available_payment_methods = $api->post(
			self::ROUTE_RETRIEVE_AVAILABLE_PAYMENT_METHODS,
			$post_fields
		);

		return self::$available_payment_methods;
	}
}
