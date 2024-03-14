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

/**
 * Class WC_Gateway_Svea_Credit_Card_And_Mobile
 *
 * Gateway handler for credit card and mobile payments
 *
 * @since 2.1.3
 */
class WC_Gateway_Svea_Credit_Card_And_Mobile extends WC_Gateway_Maksuturva {

	/**
	 * WC_Gateway_Svea_Credit_Card_And_Mobile constructor.
	 *
	 * Sets the values for gateway specific parent class properties
	 *
	 * @since 2.1.3
	 */
	public function __construct() {
		parent::__construct( WC_Gateway_Svea_Credit_Card_And_Mobile::class );
		$this->method_title = 'Svea ' . __( 'Credit Card and Mobile', $this->td );
		$this->method_description = sprintf( __( 'General Svea settings are managed <a href="%s">here</a>.', $this->td), '?page=wc-settings&tab=checkout&section=wc_gateway_maksuturva' );

		$custom_title = $this->get_option( 'payment_group_creditcard_title' );
		if (!empty($custom_title)) {
			$this->title = esc_html($custom_title);
		} else {
			$this->title = __( 'Credit Card and Mobile', $this->td );
		}
		$this->icon = WC_Maksuturva::get_instance()->get_plugin_url() . 'Empty_logo.png';
	}

	/**
	 * @inheritdoc
	 */
	public function init_form_fields() {
		$this->form_fields = [];
	}
}
