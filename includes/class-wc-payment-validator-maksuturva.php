<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 2.x, 3.x
 * Plugin developed for Svea
 * Last update: 24/10/2019
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
 * Class WC_Payment_Validator_Maksuturva.
 *
 * Handles validation of the Svea data.
 *
 * @since 2.0.0
 */
class WC_Payment_Validator_Maksuturva {

	/**
	 * Return action "ok".
	 *
	 * @var string ACTION_OK
	 */
	const ACTION_OK = 'ok';

	/**
	 * Return action "delayed".
	 *
	 * @var string ACTION_DELAYED
	 */
	const ACTION_DELAYED = 'delay';

	/**
	 * Return action "cancel".
	 *
	 * @var string ACTION_CANCEL
	 */
	const ACTION_CANCEL = 'cancel';

	/**
	 * Return action "error".
	 *
	 * @var string ACTION_ERROR
	 */
	const ACTION_ERROR = 'error';

	/**
	 * Validation "ok".
	 *
	 * @var string STATUS_OK
	 */
	const STATUS_OK = 'ok';

	/**
	 * Validation "delayed".
	 *
	 * @var string STATUS_DELAYED
	 */
	const STATUS_DELAYED = 'delayed';

	/**
	 * Validation "cancelled".
	 *
	 * @var string STATUS_CANCEL
	 */
	const STATUS_CANCEL = 'cancelled';

	/**
	 * Validation "error".
	 *
	 * @var string STATUS_ERROR
	 */
	const STATUS_ERROR = 'error';

	/**
	 * Validation "pending".
	 *
	 * @var string STATUS_PENDING
	 */
	const STATUS_PENDING = 'pending';

	/**
	 * Mandatory fields.
	 *
	 * @since 2.0.0
	 *
	 * @var array $mandatory_fields All mandatory fields that must exist in the validated params.
	 */
	private static $mandatory_fields = array(
		'pmt_action',
		'pmt_version',
		'pmt_id',
		'pmt_reference',
		'pmt_amount',
		'pmt_currency',
		'pmt_sellercosts',
		'pmt_paymentmethod',
		'pmt_escrow',
		'pmt_hash',
	);

	/**
	 * Ignored fields.
	 *
	 * @since 2.0.0
	 *
	 * @var array $ignored_consistency_check_fields Fields that can be ignored during data consistency checks.
	 */
	private static $ignored_consistency_check_fields = array(
		'pmt_hash',
		'pmt_paymentmethod',
		'pmt_reference',
		'pmt_sellercosts',
		'pmt_escrow',
	);

	/**
	 * Payment gateway object.
	 *
	 * @since 2.0.0
	 *
	 * @var WC_Gateway_Implementation_Maksuturva|null $gateway The payment gateway implementation.
	 */
	protected $gateway;

	/**
	 * Status.
	 *
	 * @since 2.0.0
	 *
	 * @var string $status The status of the validation.
	 */
	protected $status;

	/**
	 * Errors.
	 *
	 * @since 2.0.0
	 *
	 * @var array $errors Validation errors encountered during validation.
	 */
	protected $errors = array();

	/**
	 * WC_Payment_Validator_Maksuturva constructor.
	 *
	 * @param WC_Gateway_Implementation_Maksuturva $gateway The payment gateway implementation.
	 *
	 * @since 2.0.0
	 */
	public function __construct( WC_Gateway_Implementation_Maksuturva $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Validates a payment requests.
	 *
	 * If the payment gateway return an 'ok' response, only then will the entire request be validated.
	 * In other cases, we rely on the gateway status code.
	 *
	 * @param array $params List of parameters to validate.
	 *
	 * @return WC_Payment_Validator_Maksuturva
	 * @throws WC_Gateway_Maksuturva_Exception
	 * @since 2.0.0
	 *
	 */
	public function validate( array $params ) {
		switch ( $this->get_action( $params ) ) {
			case self::ACTION_CANCEL:
				$this->status = self::STATUS_CANCEL;
				break;
			case self::ACTION_DELAYED:
				$this->status = self::STATUS_DELAYED;
				break;
			case self::ACTION_ERROR:
				$this->status = self::STATUS_ERROR;
				$this->error( __( 'An error occurred and the payment was not confirmed.', $this->gateway->td ) );
				break;
			case self::ACTION_OK:
			default:
				$values = $this->validate_mandatory_fields( $params );
				$this->validate_payment_id( $values );
				$this->validate_checksum( $values );
				$this->validate_consistency( $values );
				$this->validate_seller_costs( $values );
				$this->validate_reference_number( $values );
				if ( ! empty( $this->errors ) ) {
					$this->status = self::STATUS_ERROR;
				} else {
					$this->status = self::STATUS_OK;
				}
				break;
		}

		return $this;
	}

	/**
	 * Get status.
	 *
	 * Returns the validation status (one of the STATUS_* constants).
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Get all errors.
	 *
	 * Returns the validation errors if any.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Add error.
	 *
	 * Adds a new validation error message.
	 *
	 * @param string $message The error message.
	 *
	 * @since 2.0.0
	 */
	protected function error( $message ) {
		$this->errors[] = $message;
	}

	/**
	 * Get action.
	 *
	 * Parses out the `action` from the passed params.
	 *
	 * The `action` is the status of the payment as passed by the payment gateway.
	 * Possible actions are:
	 * - ok
	 * - cancel
	 * - error
	 * - delayed
	 *
	 * @param array $params List of parameters to parse from.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	protected function get_action( array $params = array() ) {
		$action = self::ACTION_ERROR;
		if ( isset( $params['pmt_act'] ) && in_array( $params['pmt_act'],
		array( self::ACTION_CANCEL, self::ACTION_DELAYED, self::ACTION_ERROR, self::ACTION_OK ), true ) ) {
			$action = $params['pmt_act'];
		}

		return $action;
	}

	/**
	 * Validate all mandatory fields.
	 *
	 * Validates that all mandatory fields are present in the params and returns them.
	 * If a mandatory field is not set in the params list, a validation error will issued.
	 *
	 * @param array $params List of parameters to validate.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function validate_mandatory_fields( array $params ) {
		$values         = array();
		$missing_fields = array();
		foreach ( self::$mandatory_fields as $field ) {
			if ( isset( $params[ $field ] ) ) {
				$values[ $field ] = $params[ $field ];
			} else {
				$missing_fields[] = $field;
			}
		}
		if ( count( $missing_fields ) > 0 ) {
			$this->error( sprintf(
				__( 'Missing payment field(s) in response: "%s"', $this->gateway->td ),
				implode( '", "', $missing_fields )
			) );
		}

		return $values;
	}

	/**
	 * Validate payment id.
	 *
	 * Validates that the 'pmt_id' passed in the params matches the order we are validating.
	 *
	 * @param array $values List of values.
	 *
	 * @since 2.0.0
	 */
	protected function validate_payment_id( array $values ) {
		if ( ! isset( $values['pmt_id'] ) || ! $this->gateway->check_payment_id( $values['pmt_id'] ) ) {
			$this->error( __( 'The payment did not match any order', $this->gateway->td ) );
		}
	}

	/**
	 * Validate checksum.
	 *
	 * Validates that the checksum of the passed params matches the hash passed which is passed along.
	 *
	 * @param array $values List of values.
	 *
	 * @since 2.0.0
	 */
	protected function validate_checksum( array $values ) {
		$data_hasher = new WC_Data_Hasher( $this->gateway->wc_gateway );
		if ( ! isset( $values['pmt_hash'] ) || $data_hasher->create_hash( $values ) != $values['pmt_hash'] ) {
			$this->error( __( 'Payment verification checksum does not match', $this->gateway->td ) );
		}
	}

	/**
	 * Validate reference number.
	 *
	 * Validates that the reference number matches the reference number of the order information.
	 *
	 * @param array $values List of values.
	 *
	 * @throws WC_Gateway_Maksuturva_Exception
	 * @since 2.0.0
	 */
	protected function validate_reference_number( array $values ) {
		if ( ! isset( $values['pmt_reference'] )
		     || ! $this->gateway->check_payment_reference_number( $values['pmt_reference'] )
		) {
			$this->error( __( 'Payment reference number could not be verified', $this->gateway->td ) );
		}
	}

	/**
	 * Validate consistency.
	 *
	 * Validates that the passed values matches those of the gateways internal order information.
	 * The info should not have changed between creating the order and returning from the payment gateway.
	 *
	 * @param array $values List of values.
	 *
	 * @since 2.0.0
	 */
	protected function validate_consistency( array $values ) {
		$not_matching_fields = array();
		foreach ( $values as $key => $value ) {
			if ( in_array( $key, self::$ignored_consistency_check_fields, true ) ) {
				continue;
			}
			if ( isset( $this->gateway->{$key} ) && $this->gateway->{$key} !== $value ) {
				$not_matching_fields[] = sprintf(
					__( '%s (obtained %s, expected %s)', $this->gateway->td ),
					$key,
					$value,
					$this->gateway->{$key}
				);
			}
		}
		if ( count( $not_matching_fields ) > 0 ) {
			$this->error( sprintf(
				__( 'The following field(s) differs from order: %s', $this->gateway->td ),
				implode( ', ', $not_matching_fields )
			) );
		}
	}

	/**
	 * Validate seller costs.
	 *
	 * Validates if the paid amounts differ from the amounts of the created order.
	 * This can happen if the payment option (credit card, bank transfer etc.) charged a surcharge.
	 *
	 * @param array $values List of values.
	 *
	 * @since 2.0.0
	 */
	protected function validate_seller_costs( array $values ) {
		if ( isset( $this->gateway->{'pmt_sellercosts'}, $values['pmt_sellercosts'] ) ) {
			$sent_seller_cost     = str_replace( ',', '.', $this->gateway->{'pmt_sellercosts'} );
			$received_seller_cost = str_replace( ',', '.', $values['pmt_sellercosts'] );
			if ( $sent_seller_cost > $received_seller_cost ) {
				$this->error( sprintf(
					__( 'Invalid payment amount (obtained %s, expected %s)', $this->gateway->td ),
					$values['pmt_sellercosts'],
					$this->gateway->{'pmt_sellercosts'}
				) );
			}
		}
	}
}
