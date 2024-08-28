<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 8.x
 * Plugin developed for Svea Payments Oy
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'class-wc-gateway-maksuturva.php';
require_once 'class-wc-gateway-maksuturva-exception.php';
require_once 'class-wc-utils-maksuturva.php';

/**
 * Class WC_Svea_Api_Request_Handler.
 *
 * Handles requests to Svea api
 */
class WC_Svea_Api_Request_Handler {

	public $gateway;

	/**
	 * OK response type.
	 *
	 * @var string RESPONSE_TYPE_OK
	 *
	 * @since 2.1.2
	 */
	public const RESPONSE_TYPE_OK = '00';

	/**
	 * Fields included in request hash setting field key.
	 *
	 * @var string SETTINGS_FIELDS_INCLUDED_IN_REQUEST_HASH
	 *
	 * @since 2.1.2
	 */
	public const SETTINGS_FIELDS_INCLUDED_IN_REQUEST_HASH = 'fields_included_in_request_hash';

	/**
	 * Fields included in response hash setting field key.
	 *
	 * @var string SETTINGS_FIELDS_INCLUDED_IN_RESPONSE_HASH
	 *
	 * @since 2.1.2
	 */
	public const SETTINGS_FIELDS_INCLUDED_IN_RESPONSE_HASH = 'fields_included_in_response_hash';

	/**
	 * Hash field setting field key.
	 *
	 * @var string SETTINGS_HASH_FIELD
	 *
	 * @since 2.1.2
	 */
	public const SETTINGS_HASH_FIELD = 'hash_field';

	/**
	 * Return code field setting field key.
	 *
	 * @var string SETTINGS_RETURN_CODE_FIELD
	 *
	 * @since 2.1.2
	 */
	public const SETTINGS_RETURN_CODE_FIELD = 'return_code_field';

	/**
	 * WC_Svea_Api_Request_Handler constructor.
	 *
	 * @param WC_Gateway_Maksuturva $gateway The gateway.
	 *
	 * @since 2.1.2
	 */
	public function __construct( WC_Gateway_Maksuturva $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Posts data to Svea payment api and checks that the return value is valid XML.
	 *
	 * @param string $route Route.
	 * @param array  $data Data to post.
	 * @param array  $settings Settings for handling post request creation and result validation.
	 *
	 * @since 2.1.2
	 *
	 * @return array
	 */
	public function post( $route, $data, $settings = array() ) {
		if ( isset( $settings[ self::SETTINGS_HASH_FIELD ] ) ) {
			$hash_field          = $settings[ self::SETTINGS_HASH_FIELD ];
			$data[ $hash_field ] = $this->get_hash(
				$data,
				$settings[ self::SETTINGS_FIELDS_INCLUDED_IN_REQUEST_HASH ]
			);
		}

		$response = wp_remote_post(
			trailingslashit( $this->gateway->get_gateway_url() ) . $route,
			array(
				'body'       => $data,
				'timeout'    => 120,
				'user-agent' => WC_Utils_Maksuturva::get_user_agent(),
			)
		);

		$this->verify_response_has_value( $response );

		$array_response = $this->parse_response( $response );

		if ( isset( $settings[ self::SETTINGS_HASH_FIELD ] ) ) {
			if ( $array_response[ $settings[ self::SETTINGS_RETURN_CODE_FIELD ] ] === self::RESPONSE_TYPE_OK ) {
				$this->verify_response_hash(
					$array_response,
					$settings[ self::SETTINGS_FIELDS_INCLUDED_IN_RESPONSE_HASH ],
					$settings[ self::SETTINGS_HASH_FIELD ]
				);
			}
		}

		return $array_response;
	}

	/**
	 * Get request to Svea payment api without hashing
	 *
	 * @param string $route Route.
	 * @param array  $data Data to post.
	 * @param array  $settings Settings for handling post request creation and result validation.
	 *
	 * @since 2.4.2
	 *
	 * @return array
	 */
	public function get( $route, $data, $settings = array() ) {
		$request_url = add_query_arg(
			$data,
			trailingslashit( $this->gateway->get_gateway_url() ) . $route
		);

		$response = wp_remote_get(
			$request_url,
			array(
				'timeout'    => 120,
				'user-agent' => WC_Utils_Maksuturva::get_user_agent(),
			)
		);

		$this->verify_response_has_value( $response );

		return $this->parse_response( $response );
	}

	/**
	 * Generates a hash based on data.
	 *
	 * @param array $data Data.
	 * @param array $hash_fields Fields to use to generate hash.
	 *
	 * @since 2.1.2
	 *
	 * @return string
	 */
	private function get_hash( $data, $hash_fields ) {

		$hash_data = array();

		foreach ( $hash_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$hash_data[ $field ] = $data[ $field ];
			}
		}

		$data_hasher = new WC_Data_Hasher( $this->gateway );
		return $data_hasher->create_hash( $hash_data );
	}

	/**
	 * Parse raw XML to array format
	 *
	 * @param string $response Response data.
	 *
	 * @since 2.1.2
	 *
	 * @return array
	 */
	private function parse_response( $response ) {
		$body = wp_remote_retrieve_body( $response );

		try {
			$xml_response = new \SimpleXMLElement( $body );
		} catch ( \Exception $e ) {
			throw new WC_Gateway_Maksuturva_Exception(
				'Not able to parse response XML.'
			);
		}

		return json_decode( json_encode( $xml_response ), true );
	}

	/**
	 * Verifies that the response has value
	 *
	 * @param array $response Response.
	 *
	 * @since 2.1.2
	 */
	private function verify_response_has_value( $response ) {
		if ( wp_remote_retrieve_response_code( $response ) !== \WP_Http::OK ) {
			throw new WC_Gateway_Maksuturva_Exception(
				'Failed to communicate with Svea. Please check the network connection.'
			);
		}
	}

	/**
	 * Verifies that the response's hash is valid.
	 *
	 * @param array $response Response.
	 *
	 * @since 2.1.2
	 */
	private function verify_response_hash( $response, $fields_included_in_hash, $hash_field ) {

		$hash_of_response = $this->get_hash(
			$response,
			$fields_included_in_hash
		);

		if ( $hash_of_response !== $response[ $hash_field ] ) {

			$message = 'The authenticity of the answer could not be verified. '
				. 'Hashes did not match.';

			throw new WC_Gateway_Maksuturva_Exception( $message );
		}
	}

	/**
	 * Get payment plan params from Svea
	 *
	 * @since 2.6.1
	 *
	 * @return array
	 */
	public function get_payment_plan_params() {
		$cache_key = 'svea_payment_plan_params';
		$cached    = get_transient( $cache_key );

		if ( $cached ) {
			return $cached;
		}

		$route       = 'GetSveaPaymentPlanParams.pmt';
		$payment_api = $this->gateway->get_gateway_url();

		$request_url = add_query_arg(
			array( 'gpp_sellerid' => $this->gateway->get_seller_id() ),
			trailingslashit( $payment_api ) . $route
		);

		$response = wp_remote_get( $request_url );

		if ( wp_remote_retrieve_response_code( $response ) === 200 ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( isset( $body['campaigns'] ) ) {
				set_transient( $cache_key, $body, MINUTE_IN_SECONDS * 15 );

				return $body;
			}
		}

		return array();
	}
}
