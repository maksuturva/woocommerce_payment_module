<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 3.x, 4.x
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
 * Class WC_Gateway_Svea_Online_Bank_Payments
 *
 * Gateway handler for online bank payments
 *
 * @since 2.1.3
 */
class WC_Gateway_Svea_Online_Bank_Payments extends WC_Gateway_Maksuturva {

	/**
	 * WC_Gateway_Svea_Online_Bank_Payments constructor.
	 *
	 * Sets the values for gateway specific parent class properties
	 *
	 * @since 2.1.3
	 */
	public function __construct() {
		parent::__construct( WC_Gateway_Svea_Online_Bank_Payments::class );
		$this->method_title = 'Svea ' . __( 'Online Bank Payments', $this->td );
		$this->method_description = sprintf( __( 'General Svea settings are managed <a href="%s">here</a>.', $this->td), '?page=wc-settings&tab=checkout&section=wc_gateway_maksuturva' );
		$this->title = __( 'Online Bank Payments', $this->td );
	}

	/**
	 * @inheritdoc
	 */
	public function init_form_fields() {
    $this->form_fields = [];
  }
}
