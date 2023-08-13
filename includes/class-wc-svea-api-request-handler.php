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
	 * @param array $data Data to post.
	 * @param array $settings Settings for handling post request creation and result validation.
	 *
	 * @since 2.1.2
	 *
	 * @return array
	 */
	public function post( $route, $data, $settings = [] ) {

		$payment_api = $this->gateway->get_gateway_url();
		$request = curl_init( $payment_api . $route );

		if ( isset( $settings[self::SETTINGS_HASH_FIELD] ) ) {
			$hash_field = $settings[self::SETTINGS_HASH_FIELD];
			$data[$hash_field] = $this->get_hash(
				$data,
				$settings[self::SETTINGS_FIELDS_INCLUDED_IN_REQUEST_HASH]
			);
		}

		curl_setopt( $request, CURLOPT_HEADER, 0 );
		curl_setopt( $request, CURLOPT_FRESH_CONNECT, 1 );
		curl_setopt( $request, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $request, CURLOPT_FORBID_REUSE, 1 );
		curl_setopt( $request, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $request, CURLOPT_POST, 1 );
		curl_setopt( $request, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $request, CURLOPT_CONNECTTIMEOUT, 120 );
		curl_setopt( $request, CURLOPT_USERAGENT, WC_Utils_Maksuturva::get_user_agent() );
		curl_setopt( $request, CURLOPT_POSTFIELDS, $data );

		$response = curl_exec( $request );

		$this->verify_response_has_value( $response );

		curl_close( $request );

		$array_response = $this->parse_response( $response );

		if ( isset( $settings[self::SETTINGS_HASH_FIELD] ) ) {
			if ( $array_response[$settings[self::SETTINGS_RETURN_CODE_FIELD]] === self::RESPONSE_TYPE_OK ) {
				$this->verify_response_hash(
					$array_response,
					$settings[self::SETTINGS_FIELDS_INCLUDED_IN_RESPONSE_HASH],
					$settings[self::SETTINGS_HASH_FIELD]
				);
			}
		}

		return $array_response;
	}

	/**
	 * Get request to Svea payment api without hashing
	 * 
	 * @param string $route Route.
	 * @param array $data Data to post.
	 * @param array $settings Settings for handling post request creation and result validation.
	 *
	 * @since 2.4.2
	 *
	 * @return array
	 */
	public function get( $route, $data, $settings = [] ) {

		$payment_api = $this->gateway->get_gateway_url();
		$request_url = $payment_api . $route . "?" . http_build_query($data);
		$request = curl_init( $request_url );

		curl_setopt( $request, CURLOPT_HEADER, 0 );
		curl_setopt( $request, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $request, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $request, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $request, CURLOPT_CONNECTTIMEOUT, 20 );
		curl_setopt( $request, CURLOPT_USERAGENT, WC_Utils_Maksuturva::get_user_agent() );

		$response = curl_exec( $request );

		curl_close( $request );

		$array_response = $this->parse_response( $response );

		return $array_response;
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

		$hash_data = [];

		foreach ( $hash_fields as $field ) {
			if ( isset( $data[$field] ) ) {
				$hash_data[$field] = $data[$field];
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

		try {
			$xml_response = new SimpleXMLElement( $response );
		} catch ( Exception $e ) {
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
		if ( $response === false ) {
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

		if ( $hash_of_response !== $response[$hash_field] ) {

			$message = 'The authenticity of the answer could not be verified. '
				. 'Hashes did not match.';

			throw new WC_Gateway_Maksuturva_Exception($message);
		}
	}
}
