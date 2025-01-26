<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce
 * Plugin developed for Svea Payments Oy
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

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'class-wc-gateway-implementation-maksuturva.php';
require_once 'class-wc-payment-checker-maksuturva.php';
require_once 'class-wc-utils-maksuturva.php';

/**
 * Class WC_Meta_Box_Maksuturva.
 *
 * Adds a meta box to the order page.
 *
 * @since 2.0.0
 */
class WC_Meta_Box_Maksuturva {

	/**
	 * Output the meta box.
	 *
	 * @param \WP_Post $post The post.
	 * @param array    $args Arguments passed to the output function.
	 */
	public static function output( $post, $args ) {
		try {
			if ( ! isset( $args['args']['gateway'] ) ) {
				throw new WC_Gateway_Maksuturva_Exception( 'No gateway given to meta-box.' );
			}
			$gateway = $args['args']['gateway'];

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				// if creating a new order, the id is not yet set and no need to render the meta box
				if ( 'new' == wc_clean( $_GET['action'] ) ) {
					return;
				}

				$order_id = wc_clean( $_GET['id'] );
			} else {
				// legacy
				$order_id = $post->ID;
			}
			$order = wc_get_order( $order_id );

			if ( ! empty( $order->get_payment_method() ) ) {
				$payment = new WC_Payment_Maksuturva( $order->get_id() );
				if ( ! empty( $payment->get_payment_id() ) ) {
					$gateway->render(
						'meta-box',
						'admin',
						array(
							'message'              => self::get_messages( $payment ),
							'extranet_payment_url' => self::get_extranet_payment_url( $payment, $gateway ),
							'payment_id'           => $payment->get_payment_id(),
						)
					);
				}
			} else {
				// _log("Not a Svea payment method...");
			}
		} catch ( WC_Gateway_Maksuturva_Exception $e ) {
			// If the payment was not found, it probably means that the order was not paid with Svea.
			wc_maksuturva_log( $e->getMessage() );
			return;
		}
	}

	/**
	 * Get messages.
	 *
	 * Returns the messages for the given payment.
	 *
	 * @param WC_Payment_Maksuturva $payment The Svea payment object.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private static function get_messages( $payment ) {
		if ( ! $payment instanceof WC_Payment_Maksuturva ) {
			wc_maksuturva_log( 'Not a Svea payment method, skipping status check' );
		}

		/**
		 * query current status from Svea Payments if payment is not yet completed
		 */
		if ( $payment->is_delayed() || $payment->is_pending() ) {
			wc_maksuturva_log( 'Manual pending payment check for order ' . $payment->get_order_id() );
			( new WC_Payment_Checker_Maksuturva() )->check_payment( $payment );
		}

		switch ( $payment->get_status() ) {
			case WC_Payment_Maksuturva::STATUS_COMPLETED:
				$msg = __( 'The payment is confirmed by Svea Payments', 'wc-maksuturva' );
				break;

			case WC_Payment_Maksuturva::STATUS_CANCELLED:
				$msg = __( 'The payment is canceled by Svea Payments', 'wc-maksuturva' );
				break;

			case WC_Payment_Maksuturva::STATUS_ERROR:
				$msg = __( 'The payment could not be confirmed by Svea Payments, please check manually', 'wc-maksuturva' );
				break;

			case WC_Payment_Maksuturva::STATUS_DELAYED:
			case WC_Payment_Maksuturva::STATUS_PENDING:
			default:
				$msg = __( 'The payment is still waiting for confirmation by Svea Payments', 'wc-maksuturva' );
				break;
		}

		return $msg;
	}

	/**
	 * Get extranet payment url.
	 *
	 * Returns the extranet payment url for the given payment.
	 *
	 * @param WC_Payment_Maksuturva $payment The Svea payment object.
	 * @param WC_Gateway_Maksuturva $gateway The gateway object.
	 *
	 * @return string
	 */
	private static function get_extranet_payment_url( $payment, $gateway ) {
		return $gateway->get_gateway_url() . '/dashboard/PaymentEvent.db?pmt_id=' . $payment->get_payment_id();
	}
}
