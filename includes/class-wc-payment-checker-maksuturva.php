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

if ( ! function_exists( '_log' ) ) {
	/**
	 * Log a message.
	 *
	 * Uses the error_log to log messages.
	 *
	 * @param string $message The message to log.
	 *
	 * @since 2.1.18
	 */
	function _log( $message ) {
		if ( is_array( $message ) || is_object( $message ) ) {
			error_log('[SVEA PAYMENTS] ' . var_export( $message, true ) );
		} else {
			error_log('[SVEA PAYMENTS] ' . $message );
		}
	}
}

/**
 * Class WC_Payment_Checker_Maksuturva.
 *
 * Handles checking the status of payments using the Svea API.
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
		`date_added` DATETIME NOT NULL,
		UNIQUE KEY `payment_id` (payment_id)
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
	 * Queries Svea API for payment status and updates order if needed.
	 *
	 * @param WC_Payment_Maksuturva $payment the payment.
	 *
	 * @since 2.0.2
	 *
	 * @return array
	 */
	public function check_payment( $payment ) {
		$response = array();
		$query_count = 0;

		if (!$payment instanceof WC_Payment_Maksuturva) {
            _log("Not a Svea payment method, skipping status check");
			return;
        }

		try {
			$gateway  = new WC_Gateway_Maksuturva();			
			$order    = wc_get_order($payment->get_order_id());

			// don't query status in sandbox mode
			if ($gateway->is_sandbox()) {
				_log("Payment check is disabled for sandbox mode. Skipping status query for order " . $payment->get_order_id() );
				return;
			}

			/**
			 * check time windows for status query
			 */
			if ( !($this->is_time_to_check($payment->get_date_added(), $payment->get_date_updated())) ) {
				_log("Skipped the status query for the order " . $payment->get_order_id() . ", because it does not fullfill the check time window rules yet." );
				return;
			}

			/**
			 * if order is not found anymore, skip payment checks and cancel it it Maksuturva status queue (2.12.2019) 
			 */
			if ($order == NULL) {
				_log("Order for id " . $payment->get_order_id() . " not found anymore! Cancelling it in the check queue.");
				WC_Payment_Maksuturva::updateToCancelled($payment->get_order_id());
			} else {
				$response = (new WC_Gateway_Implementation_Maksuturva($gateway, $order))->status_query();
				$query_count = $this->log($payment, $response);

				switch ($response['pmtq_returncode']) {
					case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAID:
					case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAID_DELIVERY:
					case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_COMPENSATED:
						// The payment confirmation was received - payment accepted
						$payment->complete();
						if (!$gateway->is_order_paid($order)) {
							$order->payment_complete($payment->get_payment_id());
						}
						_log("Payment for order " . $payment->get_order_id() . " is updated to as paid.");
						break;

					case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAYER_CANCELLED:
					case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAYER_CANCELLED_PARTIAL:
					case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAYER_CANCELLED_PARTIAL_RETURN:
					case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_PAYER_RECLAMATION:
					case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_CANCELLED:
						// The payment was canceled in Svea
						$payment->cancel();
						$order->cancel_order();
						_log("Payment for order " . $payment->get_order_id() . " is updated to cancelled status.");
						break;

					case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_NOT_FOUND:
						$payment->update();
						_log("Payment check for order " . $payment->get_order_id() . " failed, because status is not found. ");
						break;

					case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_FAILED:
						$payment->update();
						_log("Payment query for order " . $payment->get_order_id() . " failed.");
						break;

					case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_WAITING:
					case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_UNPAID:
					case WC_Gateway_Implementation_Maksuturva::STATUS_QUERY_UNPAID_DELIVERY:
					default:
						// The payment is still waiting for confirmation, update date_update
						$payment->update();
						_log("Payment status for order " . $payment->get_order_id() . " cannot be confirmed yet and is waiting for a payment.");
						break;
				}
			}
		} catch (WC_Gateway_Maksuturva_Exception $e) {
			_log("Status query failed for order " . $payment->get_order_id() . " because exception occured: " . $e->getMessage());
			// update database timestamp and query_count
			$query_count = $this->log($payment, array("error" => $e->getMessage() ));
		}

		// if query count for the order exeeds safe limit throw an exception
		if ($query_count > 40) {
			_log('Status query count for order ' . $payment->get_order_id() . ' exceeded the maximum 40 retries. ' . 
				'Cancelled the order!');
			$payment->cancel();
		}
		return $response;
	}

	/**
	 * Checks a list of payments statuses.
	 *
	 * Queries Svea API for payment statuses and updates orders if needed.
	 *
	 * @param WC_Payment_Maksuturva[] $payments the payments.
	 *
	 * @since 2.0.2
	 *
	 * @return array
	 */
	public function check_payments(array $payments)
	{
		$responses = array();
		try {
			foreach ($payments as $payment) {
				$check_me = $this->is_time_to_check($payment->get_date_added(), $payment->get_date_updated());
				if ($check_me) {
					$sqresponse = $this->check_payment($payment);
					if (!empty($sqresponse))
						$responses[$payment->get_payment_id()] = $sqresponse;
				}
			}
		} catch (Exception $e) {
			_log("Payment check exception: " . $e->getMessage());
		}
		return $responses;
	}

	/**
	 * 
	 * Dynamic payment status check interval function
	 * 
	 * @since 2.1.1 
	 */
	protected function is_time_to_check($payment_date_added, $payment_date_updated)
	{
		$now_time = strtotime(date('Y-m-d H:i:s'));

		$create_diff = $now_time - strtotime($payment_date_added);
		/* if there is no 'updated date', so do status query if order is created max 7 days ago */
		if (is_null($payment_date_updated) && $this->in_range($create_diff, 0, 168*3600)) {
			return true;
		}
		$update_diff = $now_time - strtotime($payment_date_updated);

		$checkrule = 0;
		if ($this->in_range($create_diff, 5*60, 2*3600) && $update_diff > 20*60) {
			$checkrule = 1;
		}
		if ($this->in_range($create_diff, 2*3600, 24*3600) && $update_diff > 2*3600) {
			$checkrule = 2;
		} 
		// 168 hours = 7 days. No older than 7 days allowed.
		if ($create_diff < 168*3600 && $update_diff > 12 * 3600) {
			$checkrule = 3;
		}

		if ($checkrule>0)
			return true;
		else
			return false;
	}

	/**
	 * Determines if $number is between $min and $max
	 *
	 * @param  integer  $number     The number to test
	 * @param  integer  $min        The minimum value in the range
	 * @param  integer  $max        The maximum value in the range
	 * @param  boolean  $inclusive  Whether the range should be inclusive or not
	 * @return boolean              Whether the number was in the range
	 */
	protected function in_range($number, $min, $max, $inclusive = FALSE)
	{
		$number = intval($number);
		$min = intval($min);
		$max = intval($max);

		return $inclusive
			? ($number >= $min && $number <= $max)
			: ($number > $min && $number < $max);
	}
	 
	/**
	 * Inserts status query log.
	 *
	 * Inserts a log entry to the db for the Svea API status query.
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

		return $query_count;
	}
}
