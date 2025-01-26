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
 * Class WC_Product_Compatibility_Handler.
 *
 * Handles the difference on how to access order properties between
 * WooCommerce with or without HPOS
 *
 * @since 2.0.6
 */
class WC_Product_Compatibility_Handler {

	/**
	 * Product to handle
	 *
	 * @var \WC_Product|\WC_Product_Variable
	 */
	private $product;

	/**
	 * WC_Product_Compatibility_Handler constructor.
	 *
	 * @param \WC_Product|\WC_Product_Variable $product Product to handle.
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
		return $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id();
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->product->get_type();
	}

	/**
	 * Returns
	 *
	 * @return int
	 */
	public function get_post() {
		return get_post( $this->product->get_id() );
	}
}
