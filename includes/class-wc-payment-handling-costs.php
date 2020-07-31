<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 2.x, 3.x
 * Plugin developed for Svea
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the GNU
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
			[
				[
					'payment_method_type'  => $this->gateway->get_option( 'payment_method_type' ),
					'handling_cost_amount' => $this->gateway->get_option( 'handling_cost_amount' )
				]
			]
		);
	}

	/**
	 * Get payment method handling cost
	 * 
	 * @return int
	 * 
	 * @since 2.1.3
	 */
	public function get_payment_method_handling_cost( $payment_method_type ) {
		foreach ( $this->get_handling_costs_by_payment_method() as $handling_cost ) {
			if ( $handling_cost['payment_method_type'] === $payment_method_type ) {
				return $handling_cost['handling_cost_amount'];
			}
		}

		return null;
	}

	/**
	 * Set handling cost in checkout page
	 *
	 * @param WC_Cart $cart The cart.
	 *
	 * @since 2.1.3
	 */
	public function set_handling_cost( WC_Cart $cart ) {
		if ( ! $_POST || ( is_admin() && ! is_ajax() ) ) {
			return;
		}

		if ( ! isset( $_POST['post_data']) ) {
			return;
		}

		$chosen_gateway = WC()->session->get( 'chosen_payment_method' );
		if ( $chosen_gateway !== WC_Gateway_Maksuturva::class ) {
			return;
		}

		$post_data_array = [];
		$post_data_string = $_POST['post_data'];
		parse_str( $post_data_string, $post_data_array );

		if ( !isset( $post_data_array[WC_Payment_Method_Select::PAYMENT_METHOD_SELECT_ID] ) ) {
			return;
		}

		$payment_method_handling_cost = $this->get_payment_method_handling_cost(
			$post_data_array[WC_Payment_Method_Select::PAYMENT_METHOD_SELECT_ID]
		);

		if ( $payment_method_handling_cost !== null ) {
			$tax_rate = $this->get_payment_method_handling_cost_tax_rate();
			$cart->add_fee(
				__( 'Payment handling fee', $this->gateway->td ),
				$payment_method_handling_cost / ( 1 + $tax_rate / 100 ),
				true,
				$this->get_payment_method_handling_cost_tax_class()
			);
		}
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

		foreach ( WC_Tax::get_rates($tax_class) as $tax_rate ) {
			$rate += $tax_rate['rate'];
		}

		return $rate;
	}
}
