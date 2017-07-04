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

		// Truncate the table if the current version is below the specified DB version.
		$version = get_option( WC_Maksuturva::OPTION_DB_VERSION );
		if ( version_compare( $version, WC_Maksuturva::DB_VERSION ) < 0 ) {
			self::truncate_table();
		}

		$tbl = $wpdb->prefix . self::TABLE_NAME;

		$sql = 'CREATE TABLE `' . $tbl . '` (
		`payment_id` varchar(36) NOT NULL,
		`response` LONGBLOB NOT NULL,
		`query_count` INT NOT NULL DEFAULT 1,
		`date_added` DATETIME NOT NULL
		) DEFAULT CHARSET=utf8;';

		dbDelta( $sql );
	}

	/**
	 * Truncate table.
	 *
	 * Truncates the status query log table.
	 *
	 * @since 2.0.5
	 */
	private function truncate_table() {
		global $wpdb;
		$tbl = $wpdb->prefix . self::TABLE_NAME;

		$sql    = 'TRUNCATE TABLE `' . $tbl . '`;';
		$result = $wpdb->query( $sql );
		if ( $result === false ) {
			_log( 'Could not truncate status log table ' . $tbl );
		}
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

		$tbl        = $wpdb->prefix . self::TABLE_NAME;
		$payment_id = $payment->get_payment_id();

		// First get the results for the payment.
		$results = $wpdb->get_results( $wpdb->prepare(
			'SELECT payment_id, date_added, query_count FROM `' . $tbl . '` WHERE payment_id = %s', $payment_id ) );

		// By default we set query count to 1. Loop through any found results and increment the query_count.
		$query_count = 1;
		foreach ( $results as $result ) {
			$query_count += $result->query_count;
		}

		// If we found anything, e.g. query_count is over 1, update the record and increase the query_count by one.
		if ( $query_count > 1 ) {
			$wpdb->update( $tbl, array(
				'response'    => wp_json_encode( $response ),
				'query_count' => $query_count,
			), array(
					'payment_id' => $payment_id,
				)
			); // Db call ok.
		} else {
			// No results found, insert new record.
			$wpdb->insert( $tbl, array(
				'payment_id'  => $payment_id,
				'response'    => wp_json_encode( $response ),
				'query_count' => $query_count,
				'date_added'  => date( 'Y-m-d H:i:s' ),
			) ); // Db call ok.
		}
	}
}
