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
		return array(
			'enabled'                                     => array(
				'title'   => __( 'Enable/Disable', 'wc-maksuturva' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Svea Payments Gateway', 'wc-maksuturva' ),
				'default' => 'yes',
			),
			'title'                                       => array(
				'title'       => __( 'Title', 'wc-maksuturva' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc-maksuturva' ),
				'default'     => __( 'Svea Payments', 'wc-maksuturva' ),
				'desc_tip'    => true,
			),
			'description'                                 => array(
				'title'       => __( 'Customer Message', 'wc-maksuturva' ),
				'type'        => 'textarea',
				'description' => __( 'This message is shown below the payment method on the checkout page.', 'wc-maksuturva' ),
				'default'     => __( 'Make payment using Svea Payments card, mobile, invoice and bank payment methods', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'css'         => 'width: 25em;',
			),
			'outbound_payment'                            => array(
				'type'        => 'checkbox',
				'title'       => __( 'Redirect to Svea\'s Payment Method Selection Page', 'wc-maksuturva' ),
				'label'       => __( 'The buyer is redirected to the Svea Payments site where they choose the payment method', 'wc-maksuturva' ),
				'default'     => 'no',
				'desc_tip'    => true,
				'description' => __( 'If enabling this, visitors will see a single Svea Payments-button that sends them to the SVEA payment gateway', 'wc-maksuturva' ),
			),
			'payment_method_handling_cost_table'          => array(
				'add_new_button_text'              => __( 'Add new', 'wc-maksuturva' ),
				'amount_column_title'              => __( 'Handling cost amount', 'wc-maksuturva' ),
				'code_column_title'                => __( 'Payment method code', 'wc-maksuturva' ),
				'desc_tip'                         => true,
				'description'                      => __( 'Payment method handling costs', 'wc-maksuturva' ),
				'remove_selected_rows_button_text' => __( 'Remove selected rows', 'wc-maksuturva' ),
				'title'                            => __( 'Payment handling fees', 'wc-maksuturva' ),
				'type'                             => 'payment_method_handling_cost_table',
			),
			'payment_method_handling_cost_tax_class'      => array(
				'type'        => 'select',
				'title'       => __( 'Payment handling fees tax class', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'default'     => '',
				'description' => __( 'Tax class determines the tax percentage used for the payment handling fees.', 'wc-maksuturva' ),
				'options'     => $this->get_tax_class_options(),
			),
			'account_settings'                            => array(
				'title' => __( 'Account settings', 'wc-maksuturva' ),
				'type'  => 'title',
				'id'    => 'account_settings',
			),
			'maksuturva_url'                              => array(
				'type'        => 'textfield',
				'title'       => __( 'Gateway URL', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'The URL used to communicate with Svea Payments API.', 'wc-maksuturva' ),
				'default'     => get_option( 'maksuturva_url', 'https://www.maksuturva.fi' ),
			),
			'maksuturva_sellerid'                         => array(
				'type'        => 'textfield',
				'title'       => __( 'Seller Id', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'The seller identification provided by Svea upon your registration.', 'wc-maksuturva' ),
			),
			'maksuturva_secretkey'                        => array(
				'type'        => 'textfield',
				'title'       => __( 'Secret Key', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Your unique secret key provided by Svea.', 'wc-maksuturva' ),
			),
			'maksuturva_keyversion'                       => array(
				'type'        => 'textfield',
				'title'       => __( 'Secret Key Version', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'The version of the secret key provided by Svea.', 'wc-maksuturva' ),
				'default'     => get_option( 'maksuturva_keyversion', '001' ),
			),
			'advanced_settings'                           => array(
				'title' => __( 'Advanced settings', 'wc-maksuturva' ),
				'type'  => 'title',
				'id'    => 'advanced_settings',
			),
			'maksuturva_orderid_prefix'                   => array(
				'type'        => 'textfield',
				'title'       => __( 'Payment Prefix', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Prefix for order identifiers. Can be used to generate unique payment ids after e.g. reinstall.', 'wc-maksuturva' ),
			),
			'maksuturva_send_delivery_information_status' => array(
				'type'        => 'select',
				'title'       => __( 'Send delivery confirmation on status change to status', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'default'     => 'none',
				'description' => __( 'Send delivery confirmation to Svea when this status is selected.', 'wc-maksuturva' ),
				'options'     => array( '' => '-' ) + wc_get_order_statuses(),
			),
			'maksuturva_send_delivery_for_specific_payments' => array(
				'type'        => 'textfield',
				'title'       => __( 'Send delivery confirmation only for specific payment methods', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __(
					'Add payment method codes (for example FI70,FI71,FI72). If this field is left empty, ' .
										'the selected delivery confirmation is sent on all orders paid with any payment method. ' .
										'If the payment method codes are added, the selected delivery confirmation is sent only on ' .
					'orders paid with specific payment methods.',
					'wc-maksuturva'
				),
			),
			'estonia_settings'                            => array(
				'title' => __( 'Estonia payment method settings', 'wc-maksuturva' ),
				'type'  => 'title',
				'id'    => 'estonia_settings',
			),
			'estonia_special_delivery'                    => array(
				'type'        => 'checkbox',
				'title'       => __( 'Estonia Payment Method EEAC / Enable special delivery information support', 'wc-maksuturva' ),
				'default'     => 'no',
				'desc_tip'    => true,
				'description' => __( 'This enables the special functionality for delivery info plugins without checkout addresses.', 'wc-maksuturva' ),
				'options'     => array(
					'yes' => '1',
					'no'  => '0',
				),
			),
			'partpayment_widget_settings'                 => array(
				'title' => __( 'Part payment widget settings', 'wc-maksuturva' ),
				'type'  => 'title',
				'id'    => 'partpayment_widget_settings',
			),
			'partpayment_widget_location'                 => array(
				'type'        => 'select',
				'title'       => __( 'Part Payment widget on Product page', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'default'     => '',
				'description' => __( 'Select Svea Part Payment Widget location on product page.', 'wc-maksuturva' ),
				'options'     => $this->get_widget_locations(),
			),
			'partpayment_widget_use_test'                 => array(
				'type'        => 'checkbox',
				'title'       => __( 'Use test environment for Part Payment widget API calls', 'wc-maksuturva' ),
				'default'     => 'no',
				'desc_tip'    => true,
				'description' => __( 'Enable this if you use Svea test environment account in the credentials.', 'wc-maksuturva' ),
				'options'     => array(
					'yes' => '1',
					'no'  => '0',
				),
			),
			'partpayment_widget_mini'                     => array(
				'type'        => 'checkbox',
				'title'       => __( 'Use mini-layout for the widget', 'wc-maksuturva' ),
				'default'     => 'no',
				'desc_tip'    => true,
				'description' => __( 'Enables mini layout user interface for the part payment widget.', 'wc-maksuturva' ),
				'options'     => array(
					'yes' => '1',
					'no'  => '0',
				),
			),
			'ppw_campaign_text_fi'                        => array(
				'type'        => 'textfield',
				'title'       => __( 'Campaign text in Finnish', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Finnish campaign text', 'wc-maksuturva' ),
				'default'     => get_option( 'ppw_campaign_text_fi', 'Campaign text FI' ),
			),
			'ppw_campaign_text_sv'                        => array(
				'type'        => 'textfield',
				'title'       => __( 'Campaign text in Swedish', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Swedish campaign text', 'wc-maksuturva' ),
				'default'     => get_option( 'ppw_campaign_text_sv', 'Campaign text SV' ),
			),
			'ppw_campaign_text_en'                        => array(
				'type'        => 'textfield',
				'title'       => __( 'Campaign text in English', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'English campaign text', 'wc-maksuturva' ),
				'default'     => get_option( 'ppw_campaign_text_en', 'Campaign text EN' ),
			),
			'ppw_fallback_text_fi'                        => array(
				'type'        => 'textfield',
				'title'       => __( 'Fallback text in Finnish', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Finnish fallback text', 'wc-maksuturva' ),
				'default'     => get_option( 'ppw_fallback_text_fi', 'Fallback text FI' ),
			),
			'ppw_fallback_text_sv'                        => array(
				'type'        => 'textfield',
				'title'       => __( 'Fallback text in Swedish', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Swedish fallback text', 'wc-maksuturva' ),
				'default'     => get_option( 'ppw_fallback_text_sv', 'Fallback text SV' ),
			),
			'ppw_fallback_text_en'                        => array(
				'type'        => 'textfield',
				'title'       => __( 'Fallback text in English', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'English fallback text', 'wc-maksuturva' ),
				'default'     => get_option( 'ppw_fallback_text_en', 'Fallback text EN' ),
			),
			'ppw_border_color'                            => array(
				'type'        => 'color',
				'title'       => __( 'Border color', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Widget border color', 'wc-maksuturva' ),
				'default'     => get_option( 'ppw_border_color', '#CCEEF5' ),
			),
			'ppw_text_color'                              => array(
				'type'        => 'color',
				'title'       => __( 'Text color', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Widget text color', 'wc-maksuturva' ),
				'default'     => get_option( 'ppw_text_color', '#00325C' ),
			),
			'ppw_highlight_color'                         => array(
				'type'        => 'color',
				'title'       => __( 'Highlight color', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Widget highlight color', 'wc-maksuturva' ),
				'default'     => get_option( 'ppw_highlight_color', '#00325C' ),
			),
			'ppw_active_color'                            => array(
				'type'        => 'color',
				'title'       => __( 'Active color', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Widget active color', 'wc-maksuturva' ),
				'default'     => get_option( 'ppw_active_color', '#00AECE' ),
			),
			'ppw_price_threshold_minimum'                 => array(
				'type'        => 'textfield',
				'title'       => __( 'Price threshold minimum', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Enter a custom minimum price threshold only if you want to enable the calculator for more expensive purchases than the default minimum threshold returned by Svea. If empty, the minimum threshold is deduced from the payment plans returned by Svea.', 'wc-maksuturva' ),
				'default'     => get_option( 'ppw_price_threshold_minimum', '' ),
			),
			'ppw_price_thresholds'                        => array(
				'type'        => 'textfield',
				'title'       => __( 'Price thresholds', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Set price thresholds in following format [600, 6], [400, 12], [100, 24], [1000, 13] ', 'wc-maksuturva' ),
				'default'     => get_option( 'ppw_price_thresholds', '[300, 6], [1000, 12]' ),
			),
			'payment_group_customization'                 => array(
				'title' => __( 'Payment group title customization', 'wc-maksuturva' ),
				'type'  => 'title',
				'id'    => 'payment_group_customization',
			),
			'payment_group_creditcard_title'              => array(
				'type'        => 'textfield',
				'title'       => __( 'Credit card and mobile payments group title', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Change the checkout page title for the Credit Cards and Mobile payment group. If not set, the default localized title is used.', 'wc-maksuturva' ),
				'default'     => get_option( 'payment_group_creditcard_title', '' ),
			),
			'payment_group_invoice_title'                 => array(
				'type'        => 'textfield',
				'title'       => __( 'Invoice and Part Payment group title', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Change the checkout page title for the Invoice and Part Payment payment group. If not set, the default localized title is used.', 'wc-maksuturva' ),
				'default'     => get_option( 'payment_group_invoice_title', '' ),
			),
			'payment_group_onlinebank_title'              => array(
				'type'        => 'textfield',
				'title'       => __( 'Online bank payments group title', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Change the checkout page title for the Online bank payment group. If not set, the default localized title is used.', 'wc-maksuturva' ),
				'default'     => get_option( 'payment_group_onlinebank_title', '' ),
			),
			'payment_group_other_title'                   => array(
				'type'        => 'textfield',
				'title'       => __( 'Other payments group title', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Change the checkout page title for Other payment methods group. If not set, the default localized title is used.', 'wc-maksuturva' ),
				'default'     => get_option( 'payment_group_other_title', '' ),
			),
			'payment_group_estonia_title'                 => array(
				'type'        => 'textfield',
				'title'       => __( 'Estonia payment group title', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Change the checkout page title for the Estonia payment methods group. If not set, the default localized title is used.', 'wc-maksuturva' ),
				'default'     => get_option( 'payment_group_estonia_title', '' ),
			),
			'collated_settings'                           => array(
				'title' => __( 'Collated payment methods', 'wc-maksuturva' ),
				'type'  => 'title',
				'id'    => 'collated_settings',
			),
			'collated_title'                              => array(
				'type'        => 'textfield',
				'title'       => __( 'Collated payment method title', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Collated payment method title', 'wc-maksuturva' ),
				'default'     => get_option( 'collated_title', 'Svea Payments' ),
			),
			'collated_group1_title'                       => array(
				'type'        => 'textfield',
				'title'       => __( 'Payment group 1 title', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Collated payment methods, group 1 title', 'wc-maksuturva' ),
				'default'     => get_option( 'collated_group1_title', 'Online bank payments' ),
			),
			'collated_group1_methods'                     => array(
				'type'        => 'textfield',
				'title'       => __( 'Payment group 1 methods', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Collated payment methods, group 1 methods', 'wc-maksuturva' ),
				'default'     => get_option( 'collated_group1_methods', 'FI01,FI02,FI03,FI04,FI05,FI06,FI07,FI08,FI09,FI10,FI11,FI12,FI13,FI14,FI15' ),
			),
			'collated_group2_title'                       => array(
				'type'        => 'textfield',
				'title'       => __( 'Payment group 2 title', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Collated payment methods, group 2 title', 'wc-maksuturva' ),
				'default'     => get_option( 'collated_group2_title', 'Mobile and Card payments' ),
			),
			'collated_group2_methods'                     => array(
				'type'        => 'textfield',
				'title'       => __( 'Payment group 2 methods', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Collated payment methods, group 2 methods', 'wc-maksuturva' ),
				'default'     => get_option( 'collated_group2_methods', 'FI50,FI51,FI52,FI53,FI54,SIIR' ),
			),
			'collated_group3_title'                       => array(
				'type'        => 'textfield',
				'title'       => __( 'Payment group 3 title', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Collated payment methods, group 3 title', 'wc-maksuturva' ),
				'default'     => get_option( 'collated_group3_title', 'Pay later' ),
			),
			'collated_group3_methods'                     => array(
				'type'        => 'textfield',
				'title'       => __( 'Payment group 3 methods', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Collated payment methods, group 3 methods', 'wc-maksuturva' ),
				'default'     => get_option( 'collated_group3_methods', 'FI70,FI71,FI72,FIIN,FIPP,FIBI' ),
			),
			'collated_group4_title'                       => array(
				'type'        => 'textfield',
				'title'       => __( 'Payment group 4 title', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Collated payment methods, group 4 title', 'wc-maksuturva' ),
				'default'     => get_option( 'collated_group4_title', '' ),
			),
			'collated_group4_methods'                     => array(
				'type'        => 'textfield',
				'title'       => __( 'Payment group 4 methods', 'wc-maksuturva' ),
				'desc_tip'    => true,
				'description' => __( 'Collated payment methods, group 4 methods', 'wc-maksuturva' ),
				'default'     => get_option( 'collated_group4_methods', '' ),
			),
		);
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

		if ( ! isset( $handling_cost_field ) ) {
			return;
		}

		$this->gateway->render(
			'payment-method-handling-cost-table',
			'admin',
			array(
				'field'                         => $handling_cost_field,
				'payment_method_handling_costs' => $payment_method_handling_costs_handler->get_handling_costs_by_payment_method(),
			)
		);

		return ob_get_clean();
	}

	public function get_tax_class_options() {
		foreach ( \WC_Tax::get_tax_classes() as $tax_class ) {
			$tax_classes[ sanitize_title( $tax_class ) ] = $tax_class;
		}
		if ( ! isset( $tax_classes ) || empty( $tax_classes ) ) {
			$tax_classes = array();
		}
		$tax_classes = $tax_classes + array( '' => __( 'Standard', 'woocommerce' ) );
		asort( $tax_classes );
		return $tax_classes;
	}

	/**
	 * Get list of possible locations for part payment widget
	 *
	 * @since 2.4.1
	 */
	public function get_widget_locations() {
		$widget_locations = array( 'Disabled', 'Before add to cart quantity', 'After add to cart button', 'After add to cart form' );
		return $widget_locations;
	}

	/**
	 * Handles saving payment method handling costs
	 *
	 * @return array
	 *
	 * @since 2.1.3
	 */
	public function save_payment_method_handling_costs() {
		$errors                        = array();
		$payment_method_handling_costs = array();

		if ( isset( $_POST['payment_method_type'] ) ) {
			$payment_method_types  = array_map( 'wc_clean', $_POST['payment_method_type'] );
			$handling_cost_amounts = array_map( 'wc_clean', $_POST['handling_cost_amount'] );

			foreach ( array_keys( $payment_method_types ) as $i ) {
				if ( ! isset( $payment_method_types[ $i ] ) || $payment_method_types[ $i ] === '' ) {
					continue;
				}

				if ( ! is_numeric( $handling_cost_amounts[ $i ] ) ) {
					// accept comma decimals
					if ( ! is_numeric( str_replace( ',', '.', $handling_cost_amounts[ $i ] ) ) ) {
						$errors[] = __(
							'Invalid payment method handling costs, not a valid numeric value -> "'
							. $handling_cost_amounts[ $i ] . "'",
							'wc-maksuturva'
						);
					} else {
						$handling_cost_amounts[ $i ] = floatval( str_replace( ',', '.', $handling_cost_amounts[ $i ] ) );
					}
				}

				$payment_method_handling_costs[] = array(
					'payment_method_type'  => $payment_method_types[ $i ],
					'handling_cost_amount' => $handling_cost_amounts[ $i ],
				);
			}
		}

		if ( count( $errors ) > 0 ) {
			return $errors;
		}

		update_option( 'payment_method_handling_costs', $payment_method_handling_costs );

		return array();
	}

	/**
	 * Hide fields whose features are not available for outbound payments in wp-admin
	 *
	 * @since 2.2.0
	 */
	public function toggle_gateway_admin_settings( $is_outbound_payment_enabled ) {
		wc_enqueue_js(
			'
			(function() {			
				jQuery(function($) {
					toggle_non_outbound_settings(' . ( $is_outbound_payment_enabled ? 'true' : 'false' ) . ');

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
		'
		);
	}
}
