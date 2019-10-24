<?php
/**
 * WooCommerce Svea Payment Gateway
 *
 * @package WooCommerce Svea Payment Gateway
 */

/**
 * Svea Payment Gateway Plugin for WooCommerce 2.x, 3.x
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
 * Class WC_Product_Compatibility_Handler.
 *
 * Handles the difference on how to access order properties between
 * WooCommerce version 2.x and 3.x
 *
 * @since 2.0.6
 */
class WC_Product_Compatibility_Handler {

	/**
	 *
	 * @var WC_Product|WC_Product_Variable
	 */
	private $product;

	/**
	 * Access order values through methods instead of properties since version
	 */
	const NO_PROPERTIES_VERSION = 3;

	/**
	 * WC_Product_Compatibility_Handler constructor.
	 *
	 * @param WC_Product|WC_Product_Variable $product Product to handle
	 *
	 * @since 2.0.6
	 */
	public function __construct( $product ) {
		$this->product = $product;
	}

	/**
	 * Returns the unique ID for the product.
	 *
	 * @return int
	 */
	private function get_id() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->product->id;
		} else {
			return $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id();
		}
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->product->product_type;
		} else {
			return $this->product->get_type();
		}
	}

	/**
	 * Returns
	 *
	 * @return int
	 */
	public function get_post() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->product->post;
		} else {
			return get_post( $this->product->get_id() );
		}
	}

}
