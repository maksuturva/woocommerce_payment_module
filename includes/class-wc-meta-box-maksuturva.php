<?php
/**
 * WooCommerce Maksuturva Payment Gateway
 *
 * @package WooCommerce Maksuturva Payment Gateway
 */

/**
 * Maksuturva Payment Gateway Plugin for WooCommerce 2.x
 * Plugin developed for Maksuturva
 * Last update: 08/03/2016
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
	 * The Maksuturva gateway.
	 *
	 * @since 2.0.0
	 *
	 * @var WC_Gateway_Maksuturva $gateway The gateway object.
	 */
	private static $gateway;

	/**
	 * Output the meta box.
	 *
	 * @param WP_Post $post The post.
	 * @param array   $args Arguments passed to the output function.
	 */
	public static function output( $post, $args ) {
		if ( isset( $args['args']['gateway'] ) ) {
			self::$gateway = $args['args']['gateway'];
		}
		$order   = wc_get_order( $post );
		try {
			$payment = new WC_Payment_Maksuturva( $order->id );
		} catch (WC_Gateway_Maksuturva_Exception $e) {
			// If the payment was not found, it probably means that the order was not paid with Maksuturva.
			return;
		}

		$message = self::get_messages( $order, $payment );
		self::$gateway->render( 'meta-box', 'admin', array( 'message' => $message ) );
	}

	/**
	 * Get messages.
	 *
	 * Returns the messages for the given order.
	 *
	 * @param WC_Order              $order   The order.
	 * @param WC_Payment_Maksuturva $payment The Maksuturva payment object.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private static function get_messages( $order, $payment ) {
		$comment = '';
		switch ( $payment->get_status() ) {
			case WC_Payment_Maksuturva::STATUS_COMPLETED:
				$msg = __( 'The payment was confirmed by Maksuturva', self::$gateway->td );
				break;
			case WC_Payment_Maksuturva::STATUS_CANCELLED:
				if ( $order->get_status() !== WC_Payment_Maksuturva::STATUS_CANCELLED ) {
					$msg = __( 'The payment could not be tracked by Maksuturva, please check manually',
					self::$gateway->td );
				} else {
					$msg = __( 'The payment was canceled by the customer', self::$gateway->td );
				}
				break;
			case WC_Payment_Maksuturva::STATUS_ERROR:
				$msg = __( 'An error occurred and the payment was not confirmed, please check manually',
				self::$gateway->td );
				break;
			case WC_Payment_Maksuturva::STATUS_DELAYED:
			case WC_Payment_Maksuturva::STATUS_PENDING:
			default:
				if ($order->get_status() === WC_Payment_Maksuturva::STATUS_CANCELLED) {
					$payment->cancel();
					$msg = __('The payment is pending or delayed, but the order is already canceled. Canceling payment.',
					self::$gateway->td);
				} else {
					try {
						$response = self::status_request( $order );
						if ( isset( $response['pmtq_returntext'] ) ) {
							$comment = ' (' . $response['pmtq_returntext'] . ')';
						}
						switch ( $response['pmtq_returncode'] ) {
							case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAID:
							case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAID_DELIVERY:
							case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_COMPENSATED:
								if ( ! self::is_order_paid($order) ) {
									$order->payment_complete( $payment->get_payment_id() );
								}
								$payment->complete();
								$msg = __( 'The payment confirmation was received - payment accepted',
								self::$gateway->td );
								break;
							case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAYER_CANCELLED:
							case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAYER_CANCELLED_PARTIAL:
							case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAYER_CANCELLED_PARTIAL_RETURN:
							case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAYER_RECLAMATION:
							case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_CANCELLED:
								$order->cancel_order( __( 'The payment was canceled in Maksuturva',
								self::$gateway->td ) );

								$payment->cancel();
								$msg = __( 'The payment was canceled in Maksuturva', self::$gateway->td );
								break;
							case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_NOT_FOUND:
								if ( $order->get_status() !== WC_Payment_Maksuturva::STATUS_PENDING ) {
									$payment->cancel();
									$msg = __( 'The payment could not be tracked by Maksuturva, please check manually',
									self::$gateway->td );
								} else {
									$payment->pending();
									$msg = __( 'Payment is still waiting for confirmation', self::$gateway->td );
								}

								break;
							case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_FAILED:
							case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_WAITING:
							case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_UNPAID:
							case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_UNPAID_DELIVERY:
							default:
								$msg = __( 'The payment is still waiting for confirmation', self::$gateway->td );
								break;
						}
					} catch ( WC_Gateway_Maksuturva_Exception $e ) {
						$msg = __( 'Error while communicating with maksuturva: Invalid hash or network error',
						self::$gateway->td );
					}
				}
				break;
		}

		return trim( sprintf( '%s %s', $msg, $comment ) );
	}

	/**
	 * Check if the order is already paid.
	 *
	 * Returns if the order has already been paid.
	 *
	 * @param WC_Order $order the order
	 *
	 * @since 2.0.2
	 *
	 * @return bool
	 */
	private static function is_order_paid( WC_Order $order ) {
		if ( method_exists( $order, 'is_paid' ) ) {
			return $order->is_paid();
		} else {
			return $order->has_status(
				array( WC_Payment_Maksuturva::STATUS_PROCESSING, WC_Payment_Maksuturva::STATUS_COMPLETED )
			);
		}
	}

	/**
	 * Status request.
	 *
	 * Runs the status query against the Maksuturva payment gateway.
	 *
	 * @param WC_Order $order The order.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 * @throws WC_Gateway_Maksuturva_Exception If communication fails.
	 */
	private static function status_request( $order ) {
		$gateway = new WC_Gateway_Implementation_Maksuturva( self::$gateway, $order );

		return $gateway->status_query();
	}
}
