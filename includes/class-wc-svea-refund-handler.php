<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 7.x, 8.x
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

namespace SveaPaymentGateway\includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'class-wc-gateway-maksuturva-exception.php';
require_once 'class-wc-svea-api-request-handler.php';

/**
 * Class WC_Svea_Refund_Handler.
 *
 * Handles payment cancellations and refunding after settlement
 *
 * @since 2.1.2
 */
class WC_Svea_Refund_Handler {

	/**
	 * Cancel action.
	 *
	 * @var string ACTION_CANCEL
	 *
	 * @since 2.1.2
	 */
	private const ACTION_CANCEL = 'CANCEL';

	/**
	 * Refund after settlement action.
	 *
	 * @var string ACTION_REFUND_AFTER_SETTLEMENT
	 *
	 * @since 2.1.2
	 */
	private const ACTION_REFUND_AFTER_SETTLEMENT = 'REFUND_AFTER_SETTLEMENT';

	/**
	 * Full refund cancel type.
	 *
	 * @var string CANCEL_TYPE_FULL_REFUND
	 *
	 * @since 2.1.2
	 */
	private const CANCEL_TYPE_FULL_REFUND = 'FULL_REFUND';

	/**
	 * Partial refund cancel type.
	 *
	 * @var string CANCEL_TYPE_PARTIAL_REFUND
	 *
	 * @since 2.1.2
	 */
	private const CANCEL_TYPE_PARTIAL_REFUND = 'PARTIAL_REFUND';

	/**
	 * Refund after settlement cancel type.
	 *
	 * @var string CANCEL_TYPE_REFUND_AFTER_SETTLEMENT
	 *
	 * @since 2.1.2
	 */
	private const CANCEL_TYPE_REFUND_AFTER_SETTLEMENT = 'REFUND_AFTER_SETTLEMENT';

	/**
	 * Already settled response type.
	 *
	 * @var string RESPONSE_TYPE_ALREADY_SETTLED
	 *
	 * @since 2.1.2
	 */
	private const RESPONSE_TYPE_ALREADY_SETTLED = '30';

	/**
	 * Failed response type.
	 *
	 * @var string RESPONSE_TYPE_FAILED
	 *
	 * @since 2.1.2
	 */
	private const RESPONSE_TYPE_FAILED = '99';

	/**
	 * Payment cancellation route.
	 *
	 * @var string ROUTE_CANCEL_PAYMENT
	 *
	 * @since 2.1.2
	 */
	private const ROUTE_CANCEL_PAYMENT = '/PaymentCancel.pmt';

	/**
	 * Fields that should be used for hashing request data.
	 * The order of fields in this array is important, do not change it
	 * if you are not sure that you know what you are doing.
	 * 
	 * @var array $request_hash_fields Request hash fields.
	 *
	 * @since 2.1.2
	 */
	private static $request_hash_fields = [
		'pmtc_action',
		'pmtc_version',
		'pmtc_sellerid',
		'pmtc_id',
		'pmtc_amount',
		'pmtc_currency',
		'pmtc_canceltype',
		'pmtc_cancelamount',
		'pmtc_payeribanrefund'
	];

	/**
	 * Fields that should be used for hashing response data.
	 * The order of fields in this array is important, do not change it
	 * if you are not sure that you know what you are doing.
	 * 
	 * @var array $response_hash_fields Response hash fields.
	 *
	 * @since 2.1.2
	 */
	private static $response_hash_fields = [
		'pmtc_action',
		'pmtc_version',
		'pmtc_sellerid',
		'pmtc_id',
		'pmtc_returntext',
		'pmtc_returncode'
	];

	/**
	 * The Svea gateway.
	 *
	 * @var WC_Gateway_Maksuturva $gateway The gateway.
	 *
	 * @since 2.1.2
	 */
	private $gateway;

	/**
	 * The order.
	 *
	 * @var WC_Order $order The order.
	 *
	 * @since 2.1.2
	 */
	private $order;

	/**
	 * The payment.
	 *
	 * @var WC_Payment_Maksuturva $order The payment.
	 *
	 * @since 2.1.2
	 */
	private $payment;

	/*
	 * WC_Svea_Refund_Handler constructor.
	 * 
	 * @param int $order_id Order id.
	 * @param WC_Payment_Maksuturva $payment Payment.
	 * @param WC_Gateway_Maksuturva $gateway The gateway.
	 *
	 * @since 2.1.2
	 */
	public function __construct( $order_id, $payment, $gateway ) {
		$this->order = wc_get_order( $order_id );
		$this->payment = $payment;
		$this->gateway = $gateway;
	}

	/**
	 * Attempts a payment cancellation. If the payment is already settled,
	 * attempts a refund after settlement.
	 * 
	 * @param int $amount Amount.
	 * @param string $reason Reason.
	 *
	 * @since 2.1.2
	 *
	 * @return bool
	 */
	public function process_refund( $amount = null, $reason = '' ) {

		$this->verify_amount_has_value( $amount );

		$cancel_response = $this->post_to_svea(
			$amount,
			$reason,
			self::ACTION_CANCEL,
			$amount === $this->order->get_total()
				? self::CANCEL_TYPE_FULL_REFUND
				: self::CANCEL_TYPE_PARTIAL_REFUND
		);

		$return_code = $cancel_response['pmtc_returncode'];
		$return_text = $cancel_response['pmtc_returntext'];

		if ( $return_code === WC_Svea_Api_Request_Handler::RESPONSE_TYPE_OK ) {

			$this->create_comment(
				sprintf(
					__( 'Made a refund of %s € through Svea', 'wc-maksuturva' ),
					$this->format_amount( $amount )
				)
			);

			return true;
		}

		if ( $return_code === self::RESPONSE_TYPE_FAILED ) {

			$this->create_comment(
				$this->get_refund_failed_message()
			);

			throw new WC_Gateway_Maksuturva_Exception(
				$return_text
			);
		}

		if ( $return_code === self::RESPONSE_TYPE_ALREADY_SETTLED ) {

			$refund_after_settlement_response = $this->post_to_svea(
				$amount,
				$reason,
				self::ACTION_REFUND_AFTER_SETTLEMENT,
				self::CANCEL_TYPE_REFUND_AFTER_SETTLEMENT
			);

			$return_code = $refund_after_settlement_response['pmtc_returncode'];
			$return_text = $refund_after_settlement_response['pmtc_returntext'];

			if ( $return_code === WC_Svea_Api_Request_Handler::RESPONSE_TYPE_OK ) {
				$this->create_comment(
					$this->get_refund_payment_required_message(
						$refund_after_settlement_response
					)
				);

				return true;
			}

			if ( $return_code === self::RESPONSE_TYPE_FAILED ) {
				throw new WC_Gateway_Maksuturva_Exception(
					$return_text
				);
			}
		}

		return false;
	}

	/**
	 * Formats an int into comma separated numeric string
	 * 
	 * @param int $amount Amount.
	 *
	 * @since 2.1.2
	 *
	 * @return string
	 */
	private function format_amount( $amount ) {
		$string_amount = strval( $amount );
		$string_amount_parts = explode( '.', $string_amount );
		return implode( ',', $string_amount_parts );
	}

	/**
	 * Posts data to Svea payment api.
	 * 
	 * @param int $amount Amount.
	 * @param string $reason Reason.
	 * @param string $action Action.
	 * @param string $cancel_type Cancel type.
	 *
	 * @since 2.1.2
	 *
	 * @return array
	 */
	private function post_to_svea( $amount, $reason, $action, $cancel_type ) {

		$gateway_implementation = new WC_Gateway_Implementation_Maksuturva( $this->gateway, $this->order );
		$gateway_data = $gateway_implementation->get_field_array();

		$post_fields = [
			'pmtc_action' => $action,
			'pmtc_amount' => $this->format_amount( $this->order->get_total() ),
			'pmtc_cancelamount' => $this->format_amount( $amount ),
			'pmtc_canceldescription' => $reason,
			'pmtc_canceltype' => $cancel_type,
			'pmtc_currency' => 'EUR',
			'pmtc_hashversion' => $gateway_data['pmt_hashversion'],
			'pmtc_id' => $this->payment->get_payment_id(),
			'pmtc_keygeneration' => $this->gateway->get_secret_key_version(),
			'pmtc_resptype' => 'XML',
			'pmtc_sellerid' => $this->gateway->get_seller_id(),
			'pmtc_version' => '0005'
		];

		if ( $cancel_type === self::CANCEL_TYPE_FULL_REFUND ) {
			unset( $post_fields['pmtc_cancelamount'] );
		}

		if ( $post_fields['pmtc_canceldescription'] === '' ) {
			unset( $post_fields['pmtc_canceldescription'] );
		}

		$api = new WC_Svea_Api_Request_Handler( $this->gateway );
		return $api->post(
			self::ROUTE_CANCEL_PAYMENT,
			$post_fields,
			[
				WC_Svea_Api_Request_Handler::SETTINGS_FIELDS_INCLUDED_IN_REQUEST_HASH => self::$request_hash_fields,
				WC_Svea_Api_Request_Handler::SETTINGS_FIELDS_INCLUDED_IN_RESPONSE_HASH => self::$response_hash_fields,
				WC_Svea_Api_Request_Handler::SETTINGS_HASH_FIELD => 'pmtc_hash',
				WC_Svea_Api_Request_Handler::SETTINGS_RETURN_CODE_FIELD => 'pmtc_returncode'
			]
		);
	}

	/**
	 * Returns a comment data array with content
	 * 
	 * @param string $content Content.
	 *
	 * @since 2.1.2
	 *
	 * @return array
	 */
	private function create_comment( $content ) {
		wp_insert_comment(
			[
				'comment_author' => 'Svea Payments plugin',
				'comment_content' => $content,
				'comment_post_ID' => $this->order->get_id(),
				'comment_type' => 'order_note'
			]
		);
	}

	/**
	 * Returns a refund failed message
	 *
	 * @since 2.1.2
	 *
	 * @return string
	 */
	private function get_refund_failed_message() {

		$extranet_payment_url = $this->gateway->get_gateway_url()
			. '/dashboard/PaymentEvent.db'
			. '?pmt_id=' . $this->payment->get_payment_id();

		return __( 'Creating a refund failed', 'wc-maksuturva' )
			. '. '
			. __( 'Make a refund directly', 'wc-maksuturva' )
			. ' <a href="' . $extranet_payment_url . '" target="_blank">'
			. __( 'in Svea Extranet', 'wc-maksuturva' )
			. '</a>.';
	}

	/**
	 * Returns a refund payment required message
	 * 
	 * @param array $response Response.
	 *
	 * @since 2.1.2
	 *
	 * @return string
	 */
	private function get_refund_payment_required_message( $response ) {
		return implode(
			'<br />',
			[
				__( 'Payment is already settled. A payment to Svea is required to finalize refund:', 'wc-maksuturva' ),
				__( 'Recipient', 'wc-maksuturva' ) . ': ' . $response['pmtc_pay_with_recipientname'],
				__( 'IBAN', 'wc-maksuturva' ) . ': ' . $response['pmtc_pay_with_iban'],
				__( 'Reference', 'wc-maksuturva' ) . ': ' . $response['pmtc_pay_with_reference'],
				__( 'Amount', 'wc-maksuturva' ) . ': ' . $response['pmtc_pay_with_amount'] . ' €'
			]
		);
	}

	/**
	 * Verifies that the amount is not null.
	 * 
	 * @param int $amount Amount.
	 *
	 * @since 2.1.2
	 */
	private function verify_amount_has_value( $amount ) {
		if ( ! isset( $amount ) ) {
			throw new WC_Gateway_Maksuturva_Exception(
				'Refund amount is not defined.'
			);
		}
	}
}
