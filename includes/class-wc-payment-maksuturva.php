<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 7.x, 8.x
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

namespace SveaPaymentGateway\includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WC_Payment_Maksuturva.
 *
 * Handles the saving and loading payment related data. Keeps track of payments and their statuses.
 *
 * @since 2.0.0
 */
class WC_Payment_Maksuturva {

	/**
	 * The queue table name.
	 *
	 * @var string TABLE_NAME
	 */
	const TABLE_NAME = 'maksuturva_queue';

	/**
	 * Payment "cancelled".
	 *
	 * @var string STATUS_CANCELLED
	 */
	const STATUS_CANCELLED = 'cancelled';

	/**
	 * Payment "completed".
	 *
	 * @var string STATUS_COMPLETED
	 */
	const STATUS_COMPLETED = 'completed';

	/**
	 * Payment "on-hold".
	 *
	 * @var string STATUS_ON_HOLD
	 */
	const STATUS_ON_HOLD = 'on-hold';

	/**
	 * Payment "processing".
	 *
	 * @var string STATUS_PROCESSING
	 */
	const STATUS_PROCESSING = 'processing';

	/**
	 * Payment "pending".
	 *
	 * @var string STATUS_PENDING
	 */
	const STATUS_PENDING = 'pending';

	/**
	 * Payment "refunded".
	 *
	 * @var string STATUS_REFUNDED
	 */
	const STATUS_REFUNDED = 'refunded';

	/**
	 * Payment "failed".
	 *
	 * @var string STATUS_FAILED
	 */
	const STATUS_FAILED = 'failed';

	/**
	 * Payment "delayed".
	 *
	 * @var string STATUS_DELAYED
	 */
	const STATUS_DELAYED = 'delayed';

	/**
	 * Payment "error".
	 *
	 * @var string STATUS_ERROR
	 */
	const STATUS_ERROR = 'error';

	/**
	 * Order id.
	 *
	 * @var int $order_id The order id.
	 */
	protected $order_id;

	/**
	 * Payment id.
	 *
	 * @var string $payment_id The Svea payment id.
	 */
	protected $payment_id;

	/**
	 * Payment method.
	 *
	 * @var string $payment_method The Svea payment method.
	 */
	protected $payment_method;

	/**
	 * Payment status.
	 *
	 * @var string $status The status of the payment.
	 */
	protected $status;

	/**
	 * Data sent.
	 *
	 * @var array $data_sent Data sent to the payment gateway.
	 */
	protected $data_sent = array();

	/**
	 * Data received.
	 *
	 * @var array $data_received The data received from the payment gateway.
	 */
	protected $data_received = array();

	/**
	 * Date added.
	 *
	 * @var string $date_added The date when the record was created.
	 */
	protected $date_added;

	/**
	 * Date updated.
	 *
	 * @var string $date_updated The date when the record was updated.
	 */
	protected $date_updated;

	/**
	 * WC_Payment_Maksuturva constructor.
	 *
	 * If the order id is given, the model will be loaded from the database.
	 *
	 * @param int|null $order_id The order id to load.
	 *
	 * @since 2.0.0
	 *
	 * @throws WC_Gateway_Maksuturva_Exception If load fails.
	 */
	public function __construct( $order_id = null ) {
		if ( (int) $order_id > 0 ) {
			$this->load( $order_id );
		}
	}

	/**
	 * Installs the payment db table.
	 *
	 * Installs the DB table for the payment data.
	 *
	 * @since 2.0.2
	 */
	public static function install_db() {
		global $wpdb;

		$tbl = $wpdb->prefix . self::TABLE_NAME;

		$sql = 'CREATE TABLE `' . $tbl . '` (
		`order_id` int(10) unsigned NOT NULL,
		`payment_id` varchar(36) NOT NULL,
		`payment_method` varchar(36) NULL DEFAULT NULL,
		`status` varchar(36) NULL DEFAULT NULL,
		`data_sent` LONGBLOB NULL DEFAULT NULL,
		`data_received` LONGBLOB NULL DEFAULT NULL,
		`date_added` DATETIME NOT NULL,
		`date_updated`  DATETIME NULL DEFAULT NULL,
		UNIQUE KEY order_id_payment_id (order_id,payment_id)) DEFAULT CHARSET=utf8;';

		dbDelta( $sql );
	}

	/**
	 * Create a new payment record.
	 *
	 * Creates the payment record in the database based on given data.
	 *
	 * @param array $data The data to save.
	 *
	 * @since 2.0.0
	 *
	 * @return WC_Payment_Maksuturva
	 * @throws WC_Gateway_Maksuturva_Exception If creating fails.
	 */
	public static function create( array $data ) {
		global $wpdb;

		$result = $wpdb->replace( $wpdb->prefix . self::TABLE_NAME, array(
			'order_id'     		=> (int) $data['order_id'],
			'payment_id'    	=> $data['payment_id'],
			'payment_method'	=> $data['payment_method'],
			'status'        	=> $data['status'],
			'data_sent'     	=> wp_json_encode( $data['data_sent'] ),
			'data_received' 	=> wp_json_encode( $data['data_received'] ),
			'date_added'    	=> date( 'Y-m-d H:i:s' ),
			'date_updated'  	=> null,
		) ); // Db call ok.

		if ( false === $result ) {
			throw new WC_Gateway_Maksuturva_Exception( 'Failed to create Svea payment.' );
		}

		return new self( (int) $data['order_id'] );
	}

    /**
     * Finds pending payments and returns them.
     *
     * Queries for payments that are `pending` or `delayed` and returns object representations.
     *
     * @return WC_Payment_Maksuturva[]
     * @since 2.0.2
     *
     */
	public static function findPending() {
		global $wpdb;

		$tbl   = $wpdb->prefix . self::TABLE_NAME;
		$query = $wpdb->prepare( 'SELECT `order_id` FROM `' . $tbl . '` WHERE `status` IN ("%s","%s")',
			self::STATUS_PENDING, self::STATUS_DELAYED );
		$data = $wpdb->get_results( $query ); // Db call ok; No-cache ok.

		$payments = array();

		if ( is_array( $data ) && count( $data ) > 0 ) {
			foreach ( $data as $item ) {
				try {
					$payments[] = new WC_Payment_Maksuturva( $item->order_id );
				} catch ( WC_Gateway_Maksuturva_Exception $e ) {
					wc_maksuturva_log( (string) $e );
				}
			}
		}

		return $payments;
	}

	public static function updateToCancelled($order_id)
	{
		global $wpdb;
		$tbl   = $wpdb->prefix . self::TABLE_NAME;

		try {
			$sql = "UPDATE " . $tbl . 
				" SET status='" . self::STATUS_CANCELLED . "',date_updated='" . date( 'Y-m-d H:i:s' ) . 
				"' WHERE (order_id = '" . $order_id . "');";
			$result = $wpdb->query($sql);
			if ($result === false) {
				wc_maksuturva_log( 'Could not cancel order ' . $order_id . ' in thr queue.');
			}
		} catch (Exception $e) {
			wc_maksuturva_log( "Order cancel in thr queue failed: " . $e->getMessage());
		}
	}
	/**
	 * Get order ID.
	 *
	 * Returns the WooCommerce order ID.
	 *
	 * @since 2.0.2
	 *
	 * @return int
	 */
	public function get_order_id() {
		return $this->order_id;
	}

	/**
	 * Get payment ID.
	 *
	 * Returns the payment ID as registered in Svea.
	 *
	 * @since 2.0.2
	 *
	 * @return string
	 */
	public function get_payment_id() {
		return $this->payment_id;
	}

	/**
	 * Get payment method.
	 *
	 * Returns the payment method as registered in Svea.
	 *
	 * @since 2.0.6
	 *
	 * @return string
	 */
	public function get_payment_method() {
		return $this->payment_method;
	}

	/**
	 * Get status.
	 *
	 * Returns the payment status.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Get date added
	 * 
	 * Returns date when payment was added
	 * 
	 * @since 2.1.1
	 */
	public function get_date_added()
	{
		return $this->date_added;
	}

	/**
	 * Get date updated
	 * 
	 * Returns date when payment was updated last time
	 * 
	 * @since 2.1.1
	 */
	public function get_date_updated()
	{
		return $this->date_updated;
	}

	/**
	 * Set data received.
	 *
	 * Sets the received data property with given data.
	 *
	 * @param array $data The data to update.
	 *
	 * @since 2.0.0
	 *
	 * @throws WC_Gateway_Maksuturva_Exception If update fails.
	 */
	public function set_data_received( array $data ) {
		$this->data_received = $data;
		$this->update();
	}

	/**
	 * Complete payment.
	 *
	 * Completes the payment by setting the status to "completed".
	 *
	 * @since 2.0.0
	 *
	 * @throws WC_Gateway_Maksuturva_Exception If update fails.
	 */
	public function complete() {
		$this->status = self::STATUS_COMPLETED;
		$this->update();
	}

	/**
	 * Get if completed.
	 *
	 * Returns true if the payment status is cancel, false otherwise.
	 *
	 * @since 2.0.2
	 *
	 * @return bool
	 */
	public function is_completed() {
		return ( $this->status === self::STATUS_COMPLETED );
	}

	/**
	 * Cancel payment.
	 *
	 * Cancels the payment by setting the status to "cancelled".
	 *
	 * @since 2.0.0
	 *
	 * @throws WC_Gateway_Maksuturva_Exception If update fails.
	 */
	public function cancel() {
		$this->status = self::STATUS_CANCELLED;
		$this->update();
	}

	/**
	 * Get if cancelled.
	 *
	 * Returns true if the payment status is cancel, false otherwise.
	 *
	 * @since 2.0.2
	 *
	 * @return bool
	 */
	public function is_cancelled() {
		return ( $this->status === self::STATUS_CANCELLED );
	}

	/**
	 * Payment error.
	 *
	 * Update the status of the payment to "error", if something went wrong.
	 *
	 * @since 2.0.0
	 *
	 * @throws WC_Gateway_Maksuturva_Exception If update fails.
	 */
	public function error() {
		$this->status = self::STATUS_ERROR;
		$this->update();
	}

	/**
	 * Get if error.
	 *
	 * Returns true if the payment status is error, false otherwise.
	 *
	 * @since 2.0.2
	 *
	 * @return bool
	 */
	public function is_error() {
		return ( $this->status === self::STATUS_ERROR );
	}

	/**
	 * Payment delayed.
	 *
	 * Updates the status of the payment to "delayed".
	 *
	 * @since 2.0.0
	 *
	 * @throws WC_Gateway_Maksuturva_Exception If update fails.
	 */
	public function delayed() {
		$this->status = self::STATUS_DELAYED;
		$this->update();
	}

	/**
	 * Get if delayed.
	 *
	 * Returns true if the payment status is delayed, false otherwise.
	 *
	 * @since 2.0.2
	 *
	 * @return bool
	 */
	public function is_delayed() {
		return ( $this->status === self::STATUS_DELAYED );
	}

	/**
	 * Payment pending.
	 *
	 * Updates the status of the payment to "pending".
	 *
	 * @since 2.0.0
	 *
	 * @throws WC_Gateway_Maksuturva_Exception If update fails.
	 */
	public function pending() {
		$this->status = self::STATUS_PENDING;
		$this->update();
	}

	/**
	 * Get if pending.
	 *
	 * Returns true if the payment status is pending, false otherwise.
	 *
	 * @since 2.0.2
	 *
	 * @return bool
	 */
	public function is_pending() {
		return ( $this->status === self::STATUS_PENDING );
	}

	/**
	 * Get surcharge.
	 *
	 * Returns the monetary amount for the payments surcharge if it was included, zero otherwise.
	 *
	 * @since 2.0.0
	 *
	 * @return float|int
	 */
	public function get_surcharge() {
		if ( isset( $this->data_sent['pmt_sellercosts'], $this->data_received['pmt_sellercosts'] ) ) {
			$sent_seller_cost     = str_replace( ',', '.', $this->data_sent['pmt_sellercosts'] );
			$received_seller_cost = str_replace( ',', '.', $this->data_received['pmt_sellercosts'] );
			if ( $received_seller_cost > $sent_seller_cost ) {
				return number_format( $received_seller_cost - $sent_seller_cost, 2, '.', '' );
			}
		}

		return 0;
	}

	/**
	 * Includes surcharge.
	 *
	 * Checks if the payment includes surcharges, i.e. if the used payment method charged an additional fee.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function includes_surcharge() {
		return ( $this->get_surcharge() > 0 );
	}

	/**
	 * Load data.
	 *
	 * Loads the data for the given order id from the database to the model.
	 *
	 * @param int $order_id The order id to load.
	 *
	 * @since 2.0.0
	 *
	 * @throws WC_Gateway_Maksuturva_Exception If load fails.
	 */
	protected function load( $order_id ) {
		global $wpdb;

		$query = $wpdb->prepare( 'SELECT order_id, payment_id, payment_method, status, data_sent, data_received, date_added, date_updated FROM `'
		. $wpdb->prefix . self::TABLE_NAME . '` WHERE `order_id` = %d LIMIT 1', $order_id );

		$data = $wpdb->get_results( $query ); // Db call ok; No-cache ok.

		if ( ! ( is_array( $data ) && count( $data ) === 1 ) ) {
			return; // no order found in Maksuturva queue
			//throw new WC_Gateway_Maksuturva_Exception( 'Failed to load Svea payment!' );
		}

		$this->order_id      	= (int) $data[0]->order_id;
		$this->payment_id    	= $data[0]->payment_id;
		$this->payment_method	= $data[0]->payment_method;
		$this->status        	= $data[0]->status;
		$this->data_sent     	= (array) json_decode( $data[0]->data_sent );
		$this->data_received 	= (array) json_decode( $data[0]->data_received );
		$this->date_added    	= $data[0]->date_added;
		$this->date_updated  	= $data[0]->date_updated;

		if (
			empty( $this->payment_method )
			&& !empty( $this->data_received['pmt_paymentmethod'] )
		) {
			$this->payment_method = $this->data_received['pmt_paymentmethod'];
		}
	}

	/**
	 * Update model.
	 *
	 * Updates the payment model in the database with all the properties there is.
	 *
	 * @since 2.0.0
	 *
	 * @throws WC_Gateway_Maksuturva_Exception If update fails.
	 */
	public function update() {
		global $wpdb;

		$data = array(
			'status'        => $this->status,
			'data_received' => wp_json_encode( $this->data_received ),
			'date_updated'  => date( 'Y-m-d H:i:s' ),
		);

		if (
			empty( $this->payment_method )
			&& !empty( $this->data_received['pmt_paymentmethod'] )
		) {
			$this->payment_method = $this->data_received['pmt_paymentmethod'];
			$data['payment_method'] = $this->payment_method;
		}

		$result = $wpdb->update( $wpdb->prefix . self::TABLE_NAME, $data,
		array( 'order_id' => $this->order_id, 'payment_id' => $this->payment_id ) ); // Db call ok; No-cache ok.

		if ( false === $result ) {
			throw new WC_Gateway_Maksuturva_Exception( 'Failed to update Svea payment!' );
		}
	}
}
