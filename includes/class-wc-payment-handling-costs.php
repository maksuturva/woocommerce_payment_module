<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 8.x
 * Plugin developed for Svea Payments Oy
 * Last update: 3/4/2020
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * [GNU LGPL v. 2.1 @gnu.org] (https://www.gnu.org/licenses/lgpl-2.1.html)
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'class-wc-gateway-maksuturva.php';
require_once 'class-wc-payment-method-select.php';

/**
 * Class WC_Payment_Handling_Costs.
 *
 * Handles handling costs
 */
class WC_Payment_Handling_Costs {

	/**
	 * Gateway.
	 *
	 * @var WC_Gateway_Maksuturva $gateway The gateway.
	 *
	 * @since 2.1.3
	 */
	private $gateway;

	/**
	 * WC_Payment_Handling_Costs constructor.
	 *
	 * @param WC_Gateway_Maksuturva $gateway The gateway.
	 *
	 * @since 2.1.3
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Get handling costs by payment method
	 *
	 * @return array
	 *
	 * @since 2.1.3
	 */
	public function get_handling_costs_by_payment_method() {
		return get_option(
			'payment_method_handling_costs',
			array(
				array(
					'payment_method_type'  => $this->gateway->get_option( 'payment_method_type' ),
					'handling_cost_amount' => $this->gateway->get_option( 'handling_cost_amount' ),
				),
			)
		);
	}

	/**
	 * Get payment method handling cost
	 *
	 * @param string $payment_method_type The payment method type.
	 *
	 * @return int
	 *
	 * @since 2.1.3
	 */
	public function get_payment_method_handling_cost( $payment_method_type ) {
		if ( wc_prices_include_tax() ) {
			return $this->get_payment_method_handling_base_cost( $payment_method_type );
		}

		return $this->get_payment_method_handling_cost_without_tax( $payment_method_type );
	}

	/**
	 * Set handling cost in checkout page
	 *
	 * @param \WC_Cart $cart The cart.
	 *
	 * @since 2.1.3
	 */
	public function set_handling_cost( \WC_Cart $cart ) {
		if ( ! $_POST || ( is_admin() && ! is_ajax() ) ) {
			return;
		}

		$payment_method_select_id = $this->get_payment_method_select_id();
		if ( $payment_method_select_id === null ) {
			return;
		}

		$gateways_with_handling_costs = array(
			WC_Gateway_Svea_Credit_Card_And_Mobile::class,
			WC_Gateway_Svea_Invoice_And_Hire_Purchase::class,
			WC_Gateway_Svea_Online_Bank_Payments::class,
			WC_Gateway_Svea_Other_Payments::class,
			WC_Gateway_Svea_Estonia_Payments::class,
			WC_Gateway_Svea_Collated::class,
		);

		$chosen_gateway = WC()->session->get( 'chosen_payment_method' );
		if ( ! in_array( $chosen_gateway, $gateways_with_handling_costs ) ) {
			return;
		}

		$payment_method_handling_cost_without_tax = $this->get_payment_method_handling_cost_without_tax(
			$payment_method_select_id
		);

		if ( $payment_method_handling_cost_without_tax !== null ) {
			$cart->add_fee(
				__( 'Payment handling fee', 'wc-maksuturva' ),
				$payment_method_handling_cost_without_tax,
				true,
				$this->get_payment_method_handling_cost_tax_class()
			);
		}
	}

	/**
	 * Get payment method base cost
	 *
	 * @param string $payment_method_type The payment method type.
	 *
	 * @return int
	 *
	 * @since 2.1.3
	 */
	public function get_payment_method_handling_base_cost( $payment_method_type ) {
		foreach ( $this->get_handling_costs_by_payment_method() as $handling_cost ) {
			if ( $handling_cost['payment_method_type'] === $payment_method_type ) {
				return $handling_cost['handling_cost_amount'];
			}
		}

		return null;
	}

	/**
	 * Get payment method handling cost tax class
	 *
	 * @return string
	 *
	 * @since 2.1.3
	 */
	public function get_payment_method_handling_cost_tax_class() {
		return $this->gateway->get_option(
			'payment_method_handling_cost_tax_class'
		);
	}

	/**
	 * Get payment method handling cost tax rate
	 *
	 * @return int
	 *
	 * @since 2.1.3
	 */
	public function get_payment_method_handling_cost_tax_rate() {
		$tax_class = $this->get_payment_method_handling_cost_tax_class();

		$rate = 0;

		foreach ( \WC_Tax::get_rates( $tax_class ) as $tax_rate ) {
			$rate += $tax_rate['rate'];
		}

		return $rate;
	}

	/**
	 * Update payment handling fee
	 *
	 * @param \WC_Order $order The order.
	 *
	 * @since 2.1.3
	 */
	public function update_payment_handling_cost_fee( $order ) {

		$payment_handling_cost_fee = $this->get_payment_method_handling_cost_without_tax(
			$_GET[ WC_Payment_Method_Select::PAYMENT_METHOD_SELECT_ID ]
		);

		if ( $payment_handling_cost_fee === null ) {
			foreach ( $order->get_fees() as $fee ) {
				if ( $fee['name'] === __( 'Payment handling fee', 'wc-maksuturva' ) ) {
					$fee['total'] = 0;
					$order->calculate_totals();
					return;
				}
			}

			return;
		}

		$fee_already_exists = false;

		foreach ( $order->get_fees() as $fee ) {
			if ( $fee['name'] === __( 'Payment handling fee', 'wc-maksuturva' ) ) {
				$fee['total']       = $payment_handling_cost_fee;
				$fee_already_exists = true;
			}
		}

		if ( ! $fee_already_exists ) {
			$fee          = new \stdClass();
			$fee->name    = __( 'Payment handling fee', 'wc-maksuturva' );
			$fee->amount  = $payment_handling_cost_fee;
			$fee->taxable = true;
			$order->add_fee( $fee );
		}

		$order->calculate_totals();
	}

	/**
	 * Get payment method handling cost without tax
	 *
	 * @param string $payment_method_type The payment method type.
	 *
	 * @return int
	 *
	 * @since 2.1.3
	 */
	private function get_payment_method_handling_cost_without_tax( $payment_method_type ) {

		$payment_method_handling_cost = $this->get_payment_method_handling_base_cost(
			$payment_method_type
		);

		if ( $payment_method_handling_cost === null ) {
			return null;
		}

		$tax_rate = $this->get_payment_method_handling_cost_tax_rate();
		return $payment_method_handling_cost / ( 1 + $tax_rate / 100 );
	}

	/**
	 * Get the payment method select id
	 *
	 * @return string
	 *
	 * @since 2.1.3
	 */
	private function get_payment_method_select_id() {

		if ( isset( $_POST['post_data'] ) ) {

			$post_data_array  = array();
			$post_data_string = $_POST['post_data'];
			parse_str( $post_data_string, $post_data_array );

			if ( isset( $post_data_array[ WC_Payment_Method_Select::PAYMENT_METHOD_SELECT_ID ] ) ) {
				return $post_data_array[ WC_Payment_Method_Select::PAYMENT_METHOD_SELECT_ID ];
			}
		}

		if ( isset( $_POST[ WC_Payment_Method_Select::PAYMENT_METHOD_SELECT_ID ] ) ) {
			return $_POST[ WC_Payment_Method_Select::PAYMENT_METHOD_SELECT_ID ];
		}

		return null;
	}
}
