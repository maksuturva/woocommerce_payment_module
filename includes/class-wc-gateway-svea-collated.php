<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 6.x, 7.x
 * Plugin developed for Svea
 * Last update: 6/4/2023
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
 * Class WC_Gateway_Svea_Collated
 *
 * Gateway handler for credit card and mobile payments
 *
 * @since 2.1.3
 */
class WC_Gateway_Svea_Collated extends WC_Gateway_Maksuturva {

	/**
	 * WC_Gateway_Svea_Collated constructor.
	 *
	 * Sets the values for gateway specific parent class properties
	 *
	 * @since 2.3.10
	 */
	public function __construct() {
		parent::__construct( WC_Gateway_Svea_Collated::class );
		$this->method_title = 'Svea ' . __( 'Collated Payments', $this->td );
		$this->method_description = sprintf( __( 'General Svea settings are managed <a href="%s">here</a>.', $this->td), '?page=wc-settings&tab=checkout&section=wc_gateway_maksuturva' );
		$this->title = __( 'Collated', $this->td );
		$this->icon = WC_Maksuturva::get_instance()->get_plugin_url() . 'Empty_logo.png';
	}

	/**
	 * @inheritdoc
	 */
	public function init_form_fields() {
    	$this->form_fields = [];
  	}
}