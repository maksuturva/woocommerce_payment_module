<?php
/**
 * Svea Payments Finland for WooCommerce Plugin
 *
 * @package Svea Payments Finland for WooCommerce Plugin
 */

/**
 * Svea Payments Finland for WooCommerce Plugin
 * Plugin developed for Svea Payments Oy
 * Last update: 01/03/2026
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

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Class Sveapafi_Gateway_Svea_Online_Bank_Payments
 *
 * Gateway handler for online bank payments
 *
 * @since 2.1.3
 */
class Sveapafi_Gateway_Svea_Online_Bank_Payments extends Sveapafi_Gateway
{

	/**
	 * Sveapafi_Gateway_Svea_Online_Bank_Payments constructor.
	 *
	 * Sets the values for gateway specific parent class properties
	 *
	 * @since 2.1.3
	 */
	public function __construct()
	{
		parent::__construct(self::class);
		$this->method_title = 'Svea ' . __('Online Bank Payments', 'svea-payments-finland-for-woocommerce');

		/* translators: %s: URL */
		$this->method_description = sprintf(__('General Svea settings are managed <a href="%s">here</a>.', 'svea-payments-finland-for-woocommerce'), '?page=wc-settings&tab=checkout&section=wc_gateway_maksuturva');
		$custom_title = $this->get_option('payment_group_onlinebank_title');
		if (!empty($custom_title)) {
			$this->title = esc_html($custom_title);
		} else {
			$this->title = __('Online Bank Payments', 'svea-payments-finland-for-woocommerce');
		}
		$this->icon = Sveapafi_Maksuturva::get_instance()->get_plugin_url() . 'Svea_logo.png';
	}

	/**
	 * Initialize the form fields
	 */
	public function init_form_fields()
	{
		$this->form_fields = array();
	}
}
