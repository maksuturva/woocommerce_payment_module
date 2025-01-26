<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce
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


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'class-wc-gateway-abstract-maksuturva.php';
require_once 'class-wc-gateway-maksuturva.php';
require_once 'class-wc-gateway-maksuturva-exception.php';

/**
 * Class WC_Data_Hasher.
 *
 * Handles encryption of data.
 *
 * @since 2.1.3
 */
class WC_Data_Hasher {

	/**
	 * SHA-1 algorithm
	 *
	 * @var string ALGORITHM_SHA_1
	 */
	const ALGORITHM_SHA_1 = 'SHA-1';

	/**
	 * SHA-256 algorithm
	 *
	 * @var string ALGORITHM_SHA_256
	 */
	const ALGORITHM_SHA_256 = 'SHA-256';

	/**
	 * SHA-512 algorithm
	 *
	 * @var string ALGORITHM_SHA_512
	 */
	const ALGORITHM_SHA_512 = 'SHA-512';

	/**
	 * MD5 algorithm
	 *
	 * @var string ALGORITHM_MD5
	 */
	const ALGORITHM_MD5 = 'MD5';

	/**
	 * Seller secret key.
	 *
	 * @since 2.1.3
	 *
	 * @var string $secret_key Seller secret key to use for identification when calling the gateway.
	 */
	private $secret_key;

	/**
	 * WC_Data_Hasher constructor.
	 *
	 * @param WC_Gateway_Maksuturva $gateway The gateway object.
	 *
	 * @since 2.1.3
	 */
	public function __construct( WC_Gateway_Maksuturva $gateway ) {
		$this->secret_key = $gateway->get_secret_key();
	}

	/**
	 * Create hash.
	 *
	 * Calculates a hash for given data.
	 *
	 * @param array $hash_data The data to hash.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function create_hash( array $hash_data ) {

		$hash_string = '';

		foreach ( $hash_data as $key => $data ) {
			if ( 'pmt_hash' != $key ) {
				$hash_string .= $data . '&';
			}
		}

		$hash_string .= $this->secret_key . '&';

		return strtoupper(
			hash(
				self::get_algorithm_in_php_format(
					self::get_hash_algorithm()
				),
				$hash_string
			)
		);
	}

	/**
	 * Get best hash algorithm available.
	 *
	 * @since 2.1.3
	 *
	 * @return string
	 */
	public static function get_hash_algorithm() {

		$hashing_algorithms = hash_algos();

		$allowed_hash_algorithms_in_priority_order = array(
			self::ALGORITHM_SHA_512,
			self::ALGORITHM_SHA_256,
			self::ALGORITHM_SHA_1,
			self::ALGORITHM_MD5,
		);

		foreach ( $allowed_hash_algorithms_in_priority_order as $allowed_hash_algorithm ) {

			$algorithm_in_php_format = self::get_algorithm_in_php_format(
				$allowed_hash_algorithm
			);

			if ( in_array( $algorithm_in_php_format, $hashing_algorithms ) ) {
				return $allowed_hash_algorithm;
			}
		}

		$hash_alhorithms_string = implode(
			', ',
			$allowed_hash_algorithms_in_priority_order
		);

		throw new WC_Gateway_Maksuturva_Exception(
			'The hash algorithms ' . $hash_alhorithms_string . ' are not supported!',
			WC_Gateway_Abstract_Maksuturva::EXCEPTION_CODE_ALGORITHMS_NOT_SUPPORTED
		);
	}

	/**
	 * Get algorihm string in php format
	 *
	 * For example transforms SHA-256 into sha256
	 *
	 * @since 2.1.3
	 *
	 * @return string
	 */
	private static function get_algorithm_in_php_format( $hash_algorithm ) {
		return str_replace(
			'-',
			'',
			strtolower(
				$hash_algorithm
			)
		);
	}
}
