<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 6.x, 7.x
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
			'outbound_payment' => [
				'type'    		=> 'checkbox',
				'title'   		=> __( 'Redirect to Svea\'s Payment Method Selection Page', $this->gateway->td ),
				'label'   		=> __( 'The buyer is redirected to the Svea Payments site where they choose the payment method', $this->gateway->td ),
				'default' 		=> 'no',
				'desc_tip'    	=> true,
				'description'	=> __( 'If enabling this, visitors will see a single Svea Payments-button that sends them to the SVEA payment gateway', $this->gateway->td )
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
			'maksuturva_url' => [
				'type'        => 'textfield',
				'title'       => __( 'Gateway URL', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'The URL used to communicate with Svea Payments API.', $this->gateway->td ),
				'default'     => get_option( 'maksuturva_url', 'https://www.maksuturva.fi' ),
			],
			'maksuturva_sellerid' => [
				'type'        => 'textfield',
				'title'       => __( 'Seller Id', $this->gateway->td ),
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
			'maksuturva_orderid_prefix' => [
				'type'        => 'textfield',
				'title'       => __( 'Payment Prefix', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Prefix for order identifiers. Can be used to generate unique payment ids after e.g. reinstall.', $this->gateway->td ),
			],
			'maksuturva_send_delivery_information_status' => [
				'type'        => 'select',
				'title'       => __( 'Send delivery confirmation on status change to status', $this->gateway->td ),
				'desc_tip'    => true,
				'default'     => 'none',
				'description' => __( 'Send delivery confirmation to Svea when this status is selected.', $this->gateway->td ),
				'options'     => ['' => '-'] + wc_get_order_statuses()
			],
			'maksuturva_send_delivery_for_specific_payments' => [
				'type'        => 'textfield',
				'title'       => __( 'Send delivery confirmation only for specific payment methods', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Add payment method codes (for example FI70,FI71,FI72). If this field is left empty, ' .
										'the selected delivery confirmation is sent on all orders paid with any payment method. ' .
										'If the payment method codes are added, the selected delivery confirmation is sent only on ' .
										'orders paid with specific payment methods.', $this->gateway->td )
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
			'estonia_settings' => [
				'title' => __( 'Estonia payment method settings', $this->gateway->td ),
				'type'  => 'title',
				'id'    => 'estonia_settings',
			],
			'estonia_special_delivery' => [
				'type'        => 'checkbox',
				'title'       => __( 'Estonia Payment Method EEAC / Enable special delivery information support', $this->gateway->td ),
				'default'     => 'no',
				'desc_tip'    => true,
				'description' => __( 'This enables the special functionality for delivery info plugins without checkout addresses.', $this->gateway->td ),
				'options'     => [ 'yes' => '1', 'no' => '0' ],
			],
			'partpayment_widget_settings' => [
				'title' => __( 'Part payment widget settings', $this->gateway->td ),
				'type'  => 'title',
				'id'    => 'partpayment_widget_settings',
			],
			'partpayment_widget' => [
				'type'        => 'checkbox',
				'title'       => __( 'Part Payment widget on Product page', $this->gateway->td ),
				'default'     => 'no',
				'desc_tip'    => true,
				'description' => __( 'Enable the Part Payment widget on the product page.', $this->gateway->td ),
				'options'     => [ 'yes' => '1', 'no' => '0' ],
			],
			'partpayment_widget_use_test' => [
				'type'        => 'checkbox',
				'title'       => __( 'Use test environment for Part Payment widget API calls', $this->gateway->td ),
				'default'     => 'no',
				'desc_tip'    => true,
				'description' => __( 'Enable this if you use Svea test environment account in the credentials.', $this->gateway->td ),
				'options'     => [ 'yes' => '1', 'no' => '0' ],
			],
			'ppw_campaign_text_fi' => [
				'type'        => 'textfield',
				'title'       => __( 'Campaign text in Finnish', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Finnish campaign text', $this->gateway->td ),
				'default'     => get_option( 'ppw_campaign_text_fi', 'Campaign text FI' ),
			],
			'ppw_campaign_text_sv' => [
				'type'        => 'textfield',
				'title'       => __( 'Campaign text in Swedish', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Swedish campaign text', $this->gateway->td ),
				'default'     => get_option( 'ppw_campaign_text_sv', 'Campaign text SV' ),
			],
			'ppw_campaign_text_en' => [
				'type'        => 'textfield',
				'title'       => __( 'Campaign text in English', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'English campaign text', $this->gateway->td ),
				'default'     => get_option( 'ppw_campaign_text_en', 'Campaign text EN' ),
			],
			'ppw_fallback_text_fi' => [
				'type'        => 'textfield',
				'title'       => __( 'Fallback text in Finnish', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Finnish fallback text', $this->gateway->td ),
				'default'     => get_option( 'ppw_fallback_text_fi', 'Fallback text FI' ),
			],
			'ppw_fallback_text_sv' => [
				'type'        => 'textfield',
				'title'       => __( 'Fallback text in Swedish', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Swedish fallback text', $this->gateway->td ),
				'default'     => get_option( 'ppw_fallback_text_sv', 'Fallback text SV' ),
			],
			'ppw_fallback_text_en' => [
				'type'        => 'textfield',
				'title'       => __( 'Fallback text in English', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'English fallback text', $this->gateway->td ),
				'default'     => get_option( 'ppw_fallback_text_en', 'Fallback text EN' ),
			],
			'ppw_border_color' => [
				'type'        => 'color',
				'title'       => __( 'Border color', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Widget border color', $this->gateway->td ),
				'default'     => get_option( 'ppw_border_color', '#CCEEF5' ),
			],
			'ppw_text_color' => [
				'type'        => 'color',
				'title'       => __( 'Text color', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Widget text color', $this->gateway->td ),
				'default'     => get_option( 'ppw_text_color', '#00325C' ),
			],
			'ppw_highlight_color' => [
				'type'        => 'color',
				'title'       => __( 'Highlight color', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Widget highlight color', $this->gateway->td ),
				'default'     => get_option( 'ppw_highlight_color', '#00325C' ),
			],
			'ppw_active_color' => [
				'type'        => 'color',
				'title'       => __( 'Active color', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Widget active color', $this->gateway->td ),
				'default'     => get_option( 'ppw_active_color', '#00AECE' ),
			],
			'ppw_price_thresholds' => [
				'type'        => 'textfield',
				'title'       => __( 'Price thresholds', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Set price thresholds in following format [600, 6], [400, 12], [100, 24], [1000, 13] ', $this->gateway->td ),
				'default'     => get_option( 'ppw_price_thresholds', '[300, 6], [1000, 12]' ),
			],
			'payment_group_customization' => [
				'title' => __( 'Payment group title customization', $this->gateway->td ),
				'type'  => 'title',
				'id'    => 'payment_group_customization',
			],
			'payment_group_creditcard_title' => [
				'type'        => 'textfield',
				'title'       => __( 'Credit card and mobile payments group title', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Change the checkout page title for the Credit Cards payment group. If not set, the default localized title is used.', $this->gateway->td ),
				'default'     => get_option( 'payment_group_creditcard_title', '' ),
			],
			'payment_group_invoice_title' => [
				'type'        => 'textfield',
				'title'       => __( 'Invoice and Part Payment group title', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Change the checkout page title for the Invoice and Part Payment payment group. If not set, the default localized title is used.', $this->gateway->td ),
				'default'     => get_option( 'payment_group_invoice_title', '' ),
			],
			'payment_group_onlinebank_title' => [
				'type'        => 'textfield',
				'title'       => __( 'Online bank payments group title', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Change the checkout page title for the Online bank payment group. If not set, the default localized title is used.', $this->gateway->td ),
				'default'     => get_option( 'payment_group_onlinebank_title', '' ),
			],
			'payment_group_other_title' => [
				'type'        => 'textfield',
				'title'       => __( 'Other payments group title', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Change the checkout page title for Other payment group. If not set, the default localized title is used.', $this->gateway->td ),
				'default'     => get_option( 'payment_group_other_title', '' ),
			],
			'payment_group_estonia_title' => [
				'type'        => 'textfield',
				'title'       => __( 'Estonia payment group title', $this->gateway->td ),
				'desc_tip'    => true,
				'description' => __( 'Change the checkout page title for the Estonia payment group. If not set, the default localized title is used.', $this->gateway->td ),
				'default'     => get_option( 'payment_group_estonia_title', '' ),
			],
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
		if (!isset($tax_classes) || empty($tax_classes)) {
			$tax_classes = array(); 
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

	/**
	 * Hide fields whose features are not available for outbound payments in wp-admin
	 * 
	 * @since 2.2.0
	 */
	public function toggle_gateway_admin_settings($is_outbound_payment_enabled) {
		wc_enqueue_js('
			(function() {			
				jQuery(function($) {
					toggle_non_outbound_settings(' . ($is_outbound_payment_enabled ? 'true' : 'false') . ');

					$("body").on("change", "#woocommerce_WC_Gateway_Maksuturva_outbound_payment", function() {
						toggle_non_outbound_settings(this.checked);
					});

					function toggle_non_outbound_settings(is_op_enabled)
					{
						$("#payment_method_handling_cost_table").closest("tr")
							.css("display", is_op_enabled ? "none" : "table-row");
						$("#woocommerce_WC_Gateway_Maksuturva_payment_method_handling_cost_tax_class").closest("tr")
							.css("display", is_op_enabled ? "none" : "table-row");
					}
				});
			})();
		');
	}

}
