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

/**
 * Class WC_Payment_Checker_Maksuturva.
 *
 * Handles checking the status of payments using the Maksuturva API.
 *
 * @since 2.0.2
 */
class WC_Payment_Checker_Maksuturva {

	/**
	 * The status query log table name.
	 *
	 * @var string TABLE_NAME
	 */
	const TABLE_NAME = 'maksuturva_status_query_log';

	/**
	 * Installs the status query log db table.
	 *
	 * Installs the DB table for the status query log data.
	 *
	 * @since 2.0.2
	 */
	public static function install_db() {
		global $wpdb;

		$tbl = $wpdb->prefix . self::TABLE_NAME;

		$sql = 'CREATE TABLE `' . $tbl . '` (
		`payment_id` varchar(36) NOT NULL,
		`response` LONGBLOB NOT NULL,
		`date_added` DATETIME NOT NULL
		) DEFAULT CHARSET=utf8;';

		dbDelta( $sql );
	}

	/**
	 * Checks a payments status.
	 *
	 * Queries Maksuturva API for payment status and updates order if needed.
	 *
	 * @param WC_Payment_Maksuturva $payment the payment.
	 *
	 * @since 2.0.2
	 *
	 * @return array
	 */
	public function check_payment( $payment ) {
		$response = array();

		try {
			$gateway  = new WC_Gateway_Maksuturva();
			$order    = wc_get_order( $payment->get_order_id() );
			$response = ( new WC_Gateway_Implementation_Maksuturva( $gateway, $order ) )->status_query();
			$this->log( $payment, $response );

			switch ( $response['pmtq_returncode'] ) {
				case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAID:
				case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAID_DELIVERY:
				case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_COMPENSATED:
					// The payment confirmation was received - payment accepted
					$payment->complete();
					if ( ! $gateway->is_order_paid( $order ) ) {
						$order->payment_complete( $payment->get_payment_id() );
					}
					break;

				case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAYER_CANCELLED:
				case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAYER_CANCELLED_PARTIAL:
				case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAYER_CANCELLED_PARTIAL_RETURN:
				case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAYER_RECLAMATION:
				case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_CANCELLED:
					// The payment was canceled in Maksuturva
					$payment->cancel();
					$order->cancel_order();
					break;

				case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_NOT_FOUND:
				case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_FAILED:
				case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_WAITING:
				case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_UNPAID:
				case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_UNPAID_DELIVERY:
				default:
					// The payment is still waiting for confirmation
					break;
			}
		} catch ( WC_Gateway_Maksuturva_Exception $e ) {
			// Error while communicating with maksuturva
			_log( (string) $e );
		}

		return $response;
	}

	/**
	 * Checks a list of payments statuses.
	 *
	 * Queries Maksuturva API for payment statuses and updates orders if needed.
	 *
	 * @param WC_Payment_Maksuturva[] $payments the payments.
	 *
	 * @since 2.0.2
	 *
	 * @return array
	 */
	public function check_payments( array $payments ) {
		$responses = array();
		foreach ( $payments as $payment ) {
			$responses[ $payment->get_payment_id() ] = $this->check_payment( $payment );
		}

		return $responses;
	}

	/**
	 * Inserts status query log.
	 *
	 * Inserts a log entry to the db for the Maksuturva API status query.
	 *
	 * @param WC_Payment_Maksuturva $payment  the payment.
	 * @param array                 $response the response.
	 *
	 * @since 2.0.2
	 */
	protected function log( $payment, array $response ) {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . self::TABLE_NAME, array(
			'payment_id' => $payment->get_payment_id(),
			'response'   => wp_json_encode( $response ),
			'date_added' => date( 'Y-m-d H:i:s' ),
		) ); // Db call ok.
	}
}
