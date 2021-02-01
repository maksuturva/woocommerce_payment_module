<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 3.x, 4.x
 * Plugin developed for Svea
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

require_once 'class-wc-gateway-maksuturva.php';
require_once 'class-wc-payment-handling-costs.php';

/**
 * Class WC_Gateway_Admin_Form_Fields.
 *
 * Handles defining the gateway admin view form fields.
 *
 * @since 2.1.3
 */
class WC_Gateway_Admin_Form_Fields {

	/**
	 * Gateway.
	 *
	 * @var WC_Gateway_Maksuturva $gateway The gateway.
	 */
	private $gateway;

	/**
	 * WC_Gateway_Maksuturva constructor.
	 * 
	 * @param WC_Gateway_Maksuturva $gateway The gateway.
	 * 
	 * @since 2.1.3
	 */
	public function __construct( WC_Gateway_Maksuturva $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Returns the gateway admin form fields as an array
	 *
	 * @since 2.1.3
	 *
	 * @return array
	 */
	public function as_array() {
		return [
			'enabled' => [
				'title'   => __( 'Enable/Disable', $this->gateway->td ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Svea Payments Gateway', $this->gateway->td ),
				'default' => 'yes',
			],
			'title' => [
				'title'       => __( 'Title', $this->gateway->td ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', $this->gateway->td ),
				'default'     => __( 'Svea Payments', $this->gateway->td ),
				'desc_tip'    => true,
			],
			'description' => [
				'title'       => __( 'Customer Message', $this->gateway->td ),
				'type'        => 'textarea',
				'description' => __('This message is shown below the payment method on the checkout page.', $this->gateway->td ),
				'default'     => __( 'Make payment using Svea Payments card, mobile, invoice and bank payment methods', $this->gateway->td ),
				'desc_tip'    => true,
				'css'         => 'width: 25em;',
			],
			'payment_method_handling_cost_table' => [
				'add_new_button_text'              => __( 'Add new', $this->gateway->td ),
				'amount_column_title'              => __( 'Handling cost amount', $this->gateway->td ),
				'code_column_title'                => __( 'Payment method code', $this->gateway->td ),
				'desc_tip'                         => true,
				'description'                      => __( 'Payment method handling costs', $this->gateway->td ),
				'remove_selected_rows_button_text' => __( 'Remove selected rows', $this->gateway->td ),
				'title'                            => __( 'Payment handling fees', $this->gateway->td ),
				'type'                             => 'payment_method_handling_cost_table'
			],
			'payment_method_handling_cost_tax_class' => [
				'type'        => 'select',
				'title'       => __( 'Payment handling fees tax class', $this->gateway->td ),
				'desc_tip'    => true,
				'default'     => '',
				'description' => __( 'Tax class determines the tax percentage used for the payment handling fees.', $this->gateway->td ),
				'options'     => $this->get_tax_class_options()
			],
			'account_settings' => [
				'title' => __( 'Account settings', $this->gateway->td ),
				'type'  => 'title',
				'id'    => 'account_settings',
			],
			'maksuturva_sellerid' => [
				'type'        => 'textfield',
				'title'       => __( 'Seller id', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'The seller identification provided by Svea upon your registration.', $this->gateway->td ),
			],
			'maksuturva_secretkey' => [
				'type'        => 'textfield',
				'title'       => __( 'Secret Key', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Your unique secret key provided by Svea.', $this->gateway->td ),
			],
			'maksuturva_keyversion' => [
				'type'        => 'textfield',
				'title'       => __( 'Secret Key Version', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'The version of the secret key provided by Svea.', $this->gateway->td ),
				'default'     => get_option( 'maksuturva_keyversion', '001' ),
			],
			'advanced_settings' => [
				'title' => __( 'Advanced settings', $this->gateway->td ),
				'type'  => 'title',
				'id'    => 'advanced_settings',
			],
			/* I don't think these are needed at the UI, but enabled it for now / JH */
			'maksuturva_url' => [
				'type'        => 'textfield',
				'title'       => __( 'Gateway URL', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'The URL used to communicate with Svea. Do not change this configuration unless you know what you are doing.', $this->gateway->td ),
				'default'     => get_option( 'maksuturva_url', 'https://www.maksuturva.fi' ),
			],
			'maksuturva_orderid_prefix' => [
				'type'        => 'textfield',
				'title'       => __( 'Payment Prefix', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Prefix for order identifiers. Can be used to generate unique payment ids after e.g. reinstall.', $this->gateway->td ),
			],
			'maksuturva_send_delivery_information_status' => [
				'type'        => 'select',
				'title'       => __( 'Send delivery information on status change to status', $this->gateway->td ),
				'desc_tip'    => true,
				'default'     => 'none',
				'description' => __( 'Send delivery information to Svea when this status is selected.', $this->gateway->td ),
				'options'     => ['' => '-'] + wc_get_order_statuses()
			],
			'sandbox' => [
				'type'        => 'checkbox',
				'title'       => __( 'Sandbox mode', $this->gateway->td ),
				'default'     => 'no',
				'description' => __( 'Svea sandbox can be used to test payments. None of the payments will be real.', $this->gateway->td ),
				'options'     => [ 'yes' => '1', 'no' => '0' ],
			],
			'maksuturva_encoding' => [
				'type'        => 'radio',
				'title'       => __( 'Svea encoding', $this->gateway->td ),
				'desc_tip'    => true,
				'default'     => 'UTF-8',
				'description' => __( 'The encoding used for Svea.', $this->gateway->td ),
				'options'     => [ 'UTF-8' => 'UTF-8', 'ISO-8859-1' => 'ISO-8859-1' ],
			],
			'estonia_special_delivery' => [
				'type'        => 'checkbox',
				'title'       => __( 'Estonia Payment Method EEAC / Enable special delivery functionality', $this->gateway->td ),
				'default'     => 'no',
				'description' => __( 'This enables the special functionality for delivery info plugins without checkout addresses.', $this->gateway->td ),
				'options'     => [ 'yes' => '1', 'no' => '0' ],
			]
		];
	}

	/**
	 * Payment method handling cost table content
	 * 
	 * @since 2.1.3
	 */
	public function generate_payment_method_handling_cost_table_html() {

		ob_start();

		$payment_method_handling_costs_handler = new WC_Payment_Handling_Costs( $this->gateway );

		$handling_cost_field = null;
		foreach ( $this->gateway->get_form_fields() as $field ) {
			if ( $field['type'] === 'payment_method_handling_cost_table' ) {
				$handling_cost_field = $field;
				break;
			}
		}

		if ( !isset( $handling_cost_field ) ) {
			return;
		}

		$this->gateway->render(
			'payment-method-handling-cost-table',
			'admin',
			[
				'field' => $handling_cost_field,
				'payment_method_handling_costs' => $payment_method_handling_costs_handler->get_handling_costs_by_payment_method()
			]
		);

		return ob_get_clean();
	}

	public function get_tax_class_options() {

		foreach (WC_Tax::get_tax_classes() as $tax_class) {
			$tax_classes[sanitize_title( $tax_class )] = $tax_class;
		}

		$tax_classes = $tax_classes + ['' => __( 'Standard', 'woocommerce' )];

		asort($tax_classes);

		return $tax_classes;
	}

	/**
	 * Handles saving payment method handling costs
	 * 
	 * @return array
	 * 
	 * @since 2.1.3
	 */
	public function save_payment_method_handling_costs() {
		$errors = [];
		$payment_method_handling_costs = [];

		if (isset($_POST['payment_method_type'])) {
			$payment_method_types = array_map('wc_clean', $_POST['payment_method_type']);
			$handling_cost_amounts = array_map('wc_clean', $_POST['handling_cost_amount']);

			foreach (array_keys($payment_method_types) as $i) {
				if (!isset($payment_method_types[$i]) || $payment_method_types[$i] === '') {
					continue;
				}

				if (!is_numeric($handling_cost_amounts[$i])) {
					// accept comma decimals
					if (!is_numeric(str_replace(',', '.', $handling_cost_amounts[$i]))) {
						$errors[] = __('Invalid payment method handling costs, not a valid numeric value -> "'
						.  $handling_cost_amounts[$i] . "'", $this->gateway->td);
					} else {
						$handling_cost_amounts[$i] = floatval(str_replace(',', '.', $handling_cost_amounts[$i]));
					}
				}

				$payment_method_handling_costs[] = [
					'payment_method_type' => $payment_method_types[$i],
					'handling_cost_amount' => $handling_cost_amounts[$i],
				];
			}
		}

		if (count($errors) > 0) {
			return $errors;
		}

		update_option( 'payment_method_handling_costs', $payment_method_handling_costs );

		return [];
	}
}
