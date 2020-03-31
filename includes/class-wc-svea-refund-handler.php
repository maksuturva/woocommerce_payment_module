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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the GNU
 * Lesser General Public License for more details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'class-wc-utils-maksuturva.php';
require_once 'class-wc-gateway-maksuturva-exception.php';

/**
 * Class WC_Svea_Fefund_Handler.
 *
 * Handles payment cancellations and refunding after settlement
 */
class WC_Svea_Refund_Handler {

	/**
	 * Payment cancellation route.
	 *
	 * @var string ROUTE_CANCEL_PAYMENT
	 */
	const ROUTE_CANCEL_PAYMENT = '/PaymentCancel.pmt';

	/**
	 * Fields that should be used for hashing post data.
	 * The order of fields in this array is important, do not change it
	 * if you are not sure that you know what you are doing.
	 * 
	 * @var array $hash_fields Hash fields.
	 */
	private static $post_data_hash_fields = [
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
	 * @var array $hash_fields Hash fields.
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
	 * @var WC_Gateway_Implementation_Maksuturva $gateway The gateway
	 */
	private $gateway;

	/**
	 * The order.
	 *
	 * @var WC_Order $order The order.
	 */
	private $order;

	/**
	 * The payment.
	 *
	 * @var WC_Payment_Maksuturva $order The payment.
	 */
	private $payment;

	/**
	 * The seller id.
	 *
	 * @var string $seller_id The seller id.
	 */
	private $seller_id;

	/*
	 * WC_Svea_Refund_Handler constructor.
	 * 
	 * @param int $order_id Order id.
	 * @param WC_Payment_Maksuturva $payment Payment.
	 * @param WC_Gateway_Maksuturva $gateway The gateway.
	 */
	public function __construct( $order_id, $payment, $gateway ) {

		$this->order = wc_get_order( $order_id );
		$this->payment = $payment;

		$this->gateway = new WC_Gateway_Implementation_Maksuturva( $gateway, $this->order );
		$this->seller_id = $gateway->get_seller_id();
	}

	/**
	 * Attempts a payment cancellation. If the payment is already settled,
	 * attempts a refund after settlement.
	 * 
	 * @param int $amount Amount.
	 * @param string $reason Reason.
	 * 
	 * @return bool
	 */
	public function process_refund( $amount = null, $reason = '' ) {

		$this->verify_amount_has_value( $amount );

		$cancel_response = $this->post_to_svea(
			$amount,
			$reason,
			'CANCEL',
			'PARTIAL_REFUND'
		);

		$return_code = $cancel_response['pmtc_returncode'];
		$return_text = $cancel_response['pmtc_returntext'];

		if ( $return_code === '30' ) {

			$refund_after_settlement_response = $this->post_to_svea(
				$amount,
				$reason,
				'REFUND_AFTER_SETTLEMENT',
				'REFUND_AFTER_SETTLEMENT'
			);

			$return_code = $refund_after_settlement_response['pmtc_returncode'];
			$return_text = $refund_after_settlement_response['pmtc_returntext'];
		}

		if ( $return_code === '00' ) {
			return true;
		}

		if ( $return_code === '99' ) {
			throw new WC_Gateway_Maksuturva_Exception(
				$return_text
			);
		}

		return false;
	}

	/**
	 * Formats an int into comma separated numeric string
	 * 
	 * @param int $amount Amount.
	 * 
	 * @return string
	 */
	private function format_amount( $amount ) {
		$string_amount = strval( $amount );
		$string_amount_parts = explode( '.', $string_amount );
		return implode( ',', $string_amount_parts );
	}

	/**
	 * Posts data to Svea payment api and checks that the return value is valid XML.
	 * 
	 * @param int $amount Amount.
	 * @param string $reason Reason.
	 * @param string $action Action.
	 * @param string $cancel_type Cancel type.
	 * 
	 * @return array
	 */
	private function post_to_svea( $amount, $reason, $action, $cancel_type ) {

		$url = rtrim( $this->gateway->get_payment_url(), '/' )
			. self::ROUTE_CANCEL_PAYMENT;

		$data = $this->gateway->get_field_array();

		$request = curl_init( $url );

		$post_fields = [
			'pmtc_action' => $action,
			'pmtc_amount' => $this->format_amount( $this->order->get_total() ),
			'pmtc_cancelamount' => $this->format_amount( $amount ),
			'pmtc_canceldescription' => $reason,
			'pmtc_canceltype' => $cancel_type,
			'pmtc_currency' => 'EUR',
			'pmtc_hashversion' => $data['pmt_hashversion'],
			'pmtc_id' => $this->payment->get_payment_id(),
			'pmtc_keygeneration' => '001',
			'pmtc_resptype' => 'XML',
			'pmtc_sellerid' => $this->seller_id,
			'pmtc_version' => '0005'
		];

		if ( $post_fields['pmtc_canceldescription'] === '' ) {
			unset( $post_fields['pmtc_canceldescription'] );
		}

		$post_fields['pmtc_hash'] = $this->get_hash( $post_fields, self::$post_data_hash_fields );

		curl_setopt( $request, CURLOPT_HEADER, 0 );
		curl_setopt( $request, CURLOPT_FRESH_CONNECT, 1 );
		curl_setopt( $request, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $request, CURLOPT_FORBID_REUSE, 1 );
		curl_setopt( $request, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $request, CURLOPT_POST, 1 );
		curl_setopt( $request, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $request, CURLOPT_CONNECTTIMEOUT, 120 );
		curl_setopt( $request, CURLOPT_USERAGENT, WC_Utils_Maksuturva::get_user_agent() );
		curl_setopt( $request, CURLOPT_POSTFIELDS, $post_fields );

		$response = curl_exec( $request );

		$this->verify_response_has_value( $response );

		curl_close( $request );

		try {
			$xml_response = new SimpleXMLElement( $response );
		} catch (Exception $e) {
			throw new WC_Gateway_Maksuturva_Exception(
				'Not able to parse response XML.'
			);
		}

		$array_response = json_decode( json_encode( $xml_response ), true );

		$this->verify_response_hash( $array_response );

		return $array_response;
	}

	/**
	 * Generates a hash based on data.
	 * 
	 * @param array $data Data.
	 * 
	 * @return string
	 */
	private function get_hash( $data, $hash_fields ) {

		$hash_data = [];

		foreach ( $hash_fields as $field ) {
			if ( isset( $data[$field] ) ) {
				$hash_data[$field] = $data[$field];
			}
		}

		return $this->gateway->create_hash( $hash_data );
	}

	/**
	 * Verifies that the amount is not null.
	 * 
	 * @param int $amount Amount.
	 */
	private function verify_amount_has_value( $amount ) {
		if ( ! isset( $amount ) ) {
			throw new WC_Gateway_Maksuturva_Exception(
				'Refund amount is not defined.'
			);
		}
	}

	/**
	 * Verifies that the response's hash is valid.
	 * 
	 * @param array $response Response.
	 */
	private function verify_response_hash( $response ) {

		$hash_of_response = $this->get_hash(
			$response,
			self::$response_hash_fields
		);

		if ( $hash_of_response !== $response['pmtc_hash'] ) {
			throw new WC_Gateway_Maksuturva_Exception(
				'The authenticity of the answer could not be verified. Hashes did not match.'
			);
		}
	}

	/**
	 * Verifies that the response has value
	 * 
	 * @param array $response Response.
	 */
	private function verify_response_has_value( $response ) {
		if ( $response === false ) {
			throw new WC_Gateway_Maksuturva_Exception(
				'Failed to communicate with Svea. Please check the network connection.'
			);
		}
	}
}
