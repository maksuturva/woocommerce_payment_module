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

/**
 * Class WC_Utils_Maksuturva.
 *
 * Static class for filtering characters and prices.
 *
 * @since 2.0.0
 */
class WC_Utils_Maksuturva {

	/**
	 * Filter a string to only have alphanumeric characters.
	 *
	 * @param string $str The string to filter.
	 *
	 * @since 2.1.3
	 *
	 * @return string
	 */
	public static function filter_alphanumeric( $str ) {
		return preg_replace( '/[^a-zA-Z0-9]+/', '', $str );
	}

	/**
	 * Filter the product name
	 *
	 * @param string $str The string to filter.
	 *
	 * @since 2.2.3
	 *
	 * @return string
	 */
	public static function filter_productname( $str ) {
		return preg_replace( '/[^\p{L}\p{N}\s]/u', '_', $str );
	}

	/**
	 * Filters a price.
	 *
	 * Applies str_replace and sprintf on the given price.
	 *
	 * @param string|float|int $price The price.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public static function filter_price( $price ) {
		return str_replace( '.', ',', sprintf( '%.2f', $price ) );
	}

	/**
	 * Filters a quantity
	 *
	 * If quantity has a decimal delimiter, use comma
	 *
	 * @since 2.1.23
	 */
	public static function filter_quantity( $qty ) {
		return str_replace( '.', ',', $qty );
	}

	/**
	 * Helper function to filter out problematic characters.
	 *
	 * So far only quotation marks have been needed to filter out.
	 *
	 * @param string $string The string to filter.
	 *
	 * @since   2.0.0
	 *
	 * @return string
	 */
	public static function filter_characters( $str ) {
		$new_string = str_replace( '"', '', $str );
		if ( ! is_null( $new_string ) && mb_strlen( $new_string ) > 0 ) {
			return $new_string;
		}

		return ' ';
	}


	/**
	 * Filter a description string.
	 *
	 * Replace information with dash
	 *
	 * @param string $description The description string.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public static function filter_description( $description ) {
		/* 2.1.5 item description is not necessary for payment backend, replace with dash if not empty */
		if ( trim( $description ) != '' ) {
			$description = '-';
		}
		return $description;
		// return self::filter_characters( html_entity_decode( strip_tags( $description ) ) );
	}

	/***
	 * Generate S2S User-Agent
	 */
	public static function get_user_agent() {
		$user_agent = 'Svea Payments for WC/' . WC_Maksuturva::VERSION;

		try {
			$user_agent = mb_convert_encoding(
				$user_agent . ' (' . php_uname( 's' ) .
				' ' . php_uname( 'r' ) . ') Woocommerce/' . WC_VERSION . ' PHP/' . phpversion(),
				'ASCII'
			);
		} catch ( \Exception $e ) {
			// nop
		}
		return $user_agent;
	}
}
