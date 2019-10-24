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
 * Class WC_Utils_Maksuturva.
 *
 * Static class for filtering characters and prices.
 *
 * @since 2.0.0
 */
class WC_Utils_Maksuturva {

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
	public static function filter_characters( $string ) {
		$new_string = str_replace( '"', '', $string );
		if ( ! is_null( $new_string ) && mb_strlen( $new_string ) > 0 ) {
			return $new_string;
		}

		return ' ';
	}

	/**
	 * Filter a description string.
	 *
	 * Applies html_entity_decode and strip_tags on the given description.
	 *
	 * @param string $description The description string.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public static function filter_description( $description ) {
		return self::filter_characters( html_entity_decode( strip_tags( $description ) ) );
	}
}
