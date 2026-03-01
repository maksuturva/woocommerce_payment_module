<?php
/**
 * Svea Payments Finland for WooCommerce Plugin
 *
 * @package Svea Payments Finland for WooCommerce Plugin
 */

/**
 * Svea Payments Finland for WooCommerce Plugin
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

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

require_once 'class-sveapafi-gateway-implementation.php';
require_once 'class-sveapafi-payment-checker.php';
require_once 'class-sveapafi-utils.php';

/**
 * Class Sveapafi_Meta_Box.
 *
 * Adds a meta box to the order page.
 *
 * @since 2.0.0
 */
class Sveapafi_Meta_Box
{

	/**
	 * Output the meta box.
	 *
	 * @param \WP_Post $post The post.
	 * @param array    $args Arguments passed to the output function.
	 */
	public static function output($post, $args)
	{
		try {
			if (!isset($args['args']['gateway'])) {
				throw new Sveapafi_Gateway_Exception('No gateway given to meta-box.');
			}
			$gateway = $args['args']['gateway'];

			if (OrderUtil::custom_orders_table_usage_is_enabled()) {
				// if creating a new order, the id is not yet set and no need to render the meta box
				if ('new' == wc_clean($_GET['action'])) {
					return;
				}

				$order_id = wc_clean($_GET['id']);
			} else {
				// legacy
				$order_id = $post->ID;
			}
			$order = wc_get_order($order_id);

			if (!empty($order->get_payment_method())) {
				$payment = new Sveapafi_Payment($order->get_id());
				if (!empty($payment->get_payment_id())) {
					$gateway->render(
						'meta-box',
						'admin',
						array(
							'message' => self::get_messages($payment),
							'extranet_payment_url' => self::get_extranet_payment_url($payment, $gateway),
							'payment_id' => $payment->get_payment_id(),
						)
					);
				}
			} else {

			}
		} catch (Sveapafi_Gateway_Exception $e) {
			// If the payment was not found, it probably means that the order was not paid with Svea.
			sveapafi_log($e->getMessage());
			return;
		}
	}

	/**
	 * Get messages.
	 *
	 * Returns the messages for the given payment.
	 *
	 * @param Sveapafi_Payment $payment The Svea payment object.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private static function get_messages($payment)
	{
		if (!$payment instanceof Sveapafi_Payment) {
			sveapafi_log('Not a Svea payment method, skipping status check');
		}

		/**
		 * query current status from Svea Payments if payment is not yet completed
		 */
		if ($payment->is_delayed() || $payment->is_pending()) {
			sveapafi_log('Manual pending payment check for order ' . $payment->get_order_id());
			(new Sveapafi_Payment_Checker())->check_payment($payment);
		}

		switch ($payment->get_status()) {
			case Sveapafi_Payment::STATUS_COMPLETED:
				$msg = __('The payment is confirmed by Svea Payments', 'svea-payments-finland-for-woocommerce');
				break;

			case Sveapafi_Payment::STATUS_CANCELLED:
				$msg = __('The payment is canceled by Svea Payments', 'svea-payments-finland-for-woocommerce');
				break;

			case Sveapafi_Payment::STATUS_ERROR:
				$msg = __('The payment could not be confirmed by Svea Payments, please check manually', 'svea-payments-finland-for-woocommerce');
				break;

			case Sveapafi_Payment::STATUS_DELAYED:
			case Sveapafi_Payment::STATUS_PENDING:
			default:
				$msg = __('The payment is still waiting for confirmation by Svea Payments', 'svea-payments-finland-for-woocommerce');
				break;
		}

		return $msg;
	}

	/**
	 * Get extranet payment url.
	 *
	 * Returns the extranet payment url for the given payment.
	 *
	 * @param Sveapafi_Payment $payment The Svea payment object.
	 * @param Sveapafi_Gateway $gateway The gateway object.
	 *
	 * @return string
	 */
	private static function get_extranet_payment_url($payment, $gateway)
	{
		return $gateway->get_gateway_url() . '/dashboard/PaymentEvent.db?pmt_id=' . $payment->get_payment_id();
	}
}
