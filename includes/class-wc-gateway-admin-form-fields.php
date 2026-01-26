<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce
 * Plugin developed for Svea Payments Oy
 * Last update: 30/11/2025
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

if (!defined('ABSPATH')) {
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
class WC_Gateway_Admin_Form_Fields
{

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
	public function __construct(WC_Gateway_Maksuturva $gateway)
	{
		$this->gateway = $gateway;
	}

	/**
	 * Returns the gateway admin form fields as an array
	 *
	 * @since 2.1.3
	 *
	 * @return array
	 */
	public function as_array()
	{
		$payment_settings = array(
			'general_settings' => array(
				'title' => __('General settings', 'svea-payments'),
				'type' => 'title',
				'id' => 'general_settings',
			),
			'enabled' => array(
				'title' => __('Enable/Disable', 'svea-payments'),
				'type' => 'checkbox',
				'label' => __('Enable Svea Payments Gateway', 'svea-payments'),
				'default' => 'yes',
			),
			'block_mode_enabled' => array(
				'type' => 'checkbox',
				'title' => __('Enable Block Mode', 'svea-payments'),
				'label' => __('Enable support for WooCommerce Blocks Checkout', 'svea-payments'),
				'default' => 'yes',
				'desc_tip' => true,
				'description' => __('Enable this if you are using the new WooCommerce Blocks Checkout. If you are using the classic shortcode checkout, you can disable this.', 'svea-payments'),
			),
			'outbound_payment' => array(
				'type' => 'checkbox',
				'title' => __('Redirect to Svea\'s Payment Method Selection Page', 'svea-payments'),
				'label' => __('The buyer is redirected to the Svea Payments site where they choose the payment method', 'svea-payments'),
				'default' => 'no',
				'desc_tip' => true,
				'description' => __('If enabling this, visitors will see a single Svea Payments-button that redirects them to the SVEA payment gateway', 'svea-payments'),
			),
			'title' => array(
				'title' => __('Redirect to Svea\'s Payment Method Title', 'svea-payments'),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout when using the redirect to Svea\'s Payment Method Selection Page.', 'svea-payments'),
				'default' => __('Svea Payments', 'svea-payments'),
				'desc_tip' => true,
			),
			'description' => array(
				'title' => __('Redirect to Svea\'s Payment Method Customer Message', 'svea-payments'),
				'type' => 'textarea',
				'description' => __('This message is shown below the payment method on the checkout page.', 'svea-payments'),
				'default' => __('Make payment using Svea Payments card, mobile, invoice and bank payment methods', 'svea-payments'),
				'desc_tip' => true,
				'css' => 'width: 25em;',
			),

			'payment_method_handling_cost_table' => array(
				'add_new_button_text' => __('Add new', 'svea-payments'),
				'amount_column_title' => __('Handling cost amount', 'svea-payments'),
				'code_column_title' => __('Payment method code', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Payment method handling costs', 'svea-payments'),
				'remove_selected_rows_button_text' => __('Remove selected rows', 'svea-payments'),
				'title' => __('Payment handling fees', 'svea-payments'),
				'type' => 'payment_method_handling_cost_table',
			),
			'payment_method_handling_cost_tax_class' => array(
				'type' => 'select',
				'title' => __('Payment handling fees tax class', 'svea-payments'),
				'desc_tip' => true,
				'default' => '',
				'description' => __('Tax class determines the tax percentage used for the payment handling fees.', 'svea-payments'),
				'options' => $this->get_tax_class_options(),
			),
			'account_settings' => array(
				'title' => __('Account settings', 'svea-payments'),
				'type' => 'title',
				'id' => 'account_settings',
			),
			'maksuturva_url' => array(
				'type' => 'textfield',
				'title' => __('Gateway URL', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('The URL used to communicate with Svea Payments API.', 'svea-payments'),
				'default' => get_option('maksuturva_url', 'https://www.maksuturva.fi'),
			),
			'maksuturva_sellerid' => array(
				'type' => 'textfield',
				'title' => __('Seller Id', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('The seller identification provided by Svea upon your registration.', 'svea-payments'),
			),
			'maksuturva_secretkey' => array(
				'type' => 'textfield',
				'title' => __('Secret Key', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Your unique secret key provided by Svea.', 'svea-payments'),
			),
			'maksuturva_keyversion' => array(
				'type' => 'textfield',
				'title' => __('Secret Key Version', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('The version of the secret key provided by Svea.', 'svea-payments'),
				'default' => get_option('maksuturva_keyversion', '001'),
			),
			'advanced_settings' => array(
				'title' => __('Advanced settings', 'svea-payments'),
				'type' => 'title',
				'id' => 'advanced_settings',
			),
			'maksuturva_orderid_prefix' => array(
				'type' => 'textfield',
				'title' => __('Payment Prefix', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Prefix for order identifiers. Can be used to generate unique payment ids after e.g. reinstall.', 'svea-payments'),
			),
			'maksuturva_send_delivery_information_status' => array(
				'type' => 'select',
				'title' => __('Send delivery confirmation on status change to status', 'svea-payments'),
				'desc_tip' => true,
				'default' => 'none',
				'description' => __('Send delivery confirmation to Svea when this status is selected.', 'svea-payments'),
				'options' => array('' => '-') + wc_get_order_statuses(),
			),
			'maksuturva_send_delivery_for_specific_payments' => array(
				'type' => 'textfield',
				'title' => __('Send delivery confirmation only for specific payment methods', 'svea-payments'),
				'desc_tip' => true,
				'description' => __(
					'Add payment method codes (for example FI70,FI71,FI72). If this field is left empty, ' .
					'the selected delivery confirmation is sent on all orders paid with any payment method. ' .
					'If the payment method codes are added, the selected delivery confirmation is sent only on ' .
					'orders paid with specific payment methods.',
					'svea-payments'
				),
			),
			'collated_settings' => array(
				'title' => __('Grouped payment method view settings', 'svea-payments'),
				'type' => 'title',
				'id' => 'collated_settings',
			),
			'collated_title' => array(
				'type' => 'textfield',
				'title' => __('Grouped payment methods title', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Grouped payment methods title', 'svea-payments'),
				'default' => get_option('collated_title', 'Svea Payments'),
			),
			'collated_group1_title' => array(
				'type' => 'textfield',
				'title' => __('Payment group 1 title', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Collated payment methods, group 1 title', 'svea-payments'),
				'default' => get_option('collated_group1_title', 'Online bank payments'),
			),
			'collated_group1_methods' => array(
				'type' => 'textfield',
				'title' => __('Payment group 1 methods', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Collated payment methods, group 1 methods', 'svea-payments'),
				'default' => get_option('collated_group1_methods', 'FI01,FI02,FI03,FI04,FI05,FI06,FI07,FI08,FI09,FI10,FI11,FI12,FI13,FI14,FI15'),
			),
			'collated_group2_title' => array(
				'type' => 'textfield',
				'title' => __('Payment group 2 title', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Collated payment methods, group 2 title', 'svea-payments'),
				'default' => get_option('collated_group2_title', 'Mobile and Card payments'),
			),
			'collated_group2_methods' => array(
				'type' => 'textfield',
				'title' => __('Payment group 2 methods', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Collated payment methods, group 2 methods', 'svea-payments'),
				'default' => get_option('collated_group2_methods', 'FI50,FI51,FI52,FI53,FI54,SIIR'),
			),
			'collated_group3_title' => array(
				'type' => 'textfield',
				'title' => __('Payment group 3 title', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Collated payment methods, group 3 title', 'svea-payments'),
				'default' => get_option('collated_group3_title', 'Pay later'),
			),
			'collated_group3_methods' => array(
				'type' => 'textfield',
				'title' => __('Payment group 3 methods', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Collated payment methods, group 3 methods', 'svea-payments'),
				'default' => get_option('collated_group3_methods', 'FI70,FI71,FI72,FIIN,FIPP,FIBI'),
			),
			'collated_group4_title' => array(
				'type' => 'textfield',
				'title' => __('Payment group 4 title', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Collated payment methods, group 4 title', 'svea-payments'),
				'default' => get_option('collated_group4_title', ''),
			),
			'collated_group4_methods' => array(
				'type' => 'textfield',
				'title' => __('Payment group 4 methods', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Collated payment methods, group 4 methods', 'svea-payments'),
				'default' => get_option('collated_group4_methods', ''),
			),

			'payment_group_customization' => array(
				'title' => __('Separate payment methods view settings', 'svea-payments'),
				'type' => 'title',
				'id' => 'payment_group_customization',
			),
			'payment_group_creditcard_title' => array(
				'type' => 'textfield',
				'title' => __('Credit card and mobile payments group title', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Change the checkout page title for the Credit Cards and Mobile payment group. If not set, the default localized title is used.', 'svea-payments'),
				'default' => get_option('payment_group_creditcard_title', ''),
			),
			'payment_group_invoice_title' => array(
				'type' => 'textfield',
				'title' => __('Invoice and Part Payment group title', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Change the checkout page title for the Invoice and Part Payment payment group. If not set, the default localized title is used.', 'svea-payments'),
				'default' => get_option('payment_group_invoice_title', ''),
			),
			'payment_group_onlinebank_title' => array(
				'type' => 'textfield',
				'title' => __('Online bank payments group title', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Change the checkout page title for the Online bank payment group. If not set, the default localized title is used.', 'svea-payments'),
				'default' => get_option('payment_group_onlinebank_title', ''),
			),
			'payment_group_other_title' => array(
				'type' => 'textfield',
				'title' => __('Other payments group title', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Change the checkout page title for Other payment methods group. If not set, the default localized title is used.', 'svea-payments'),
				'default' => get_option('payment_group_other_title', ''),
			),
			'payment_group_estonia_title' => array(
				'type' => 'textfield',
				'title' => __('Estonia payment group title', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Change the checkout page title for the Estonia payment methods group. If not set, the default localized title is used.', 'svea-payments'),
				'default' => get_option('payment_group_estonia_title', ''),
			),
		);

		$widget_settings = $this->get_part_payment_widget_settings();

		$estonia_settings = array(
			'estonia_settings' => array(
				'title' => __('Estonia payment method settings', 'svea-payments'),
				'type' => 'title',
				'id' => 'estonia_settings',
			),
			'estonia_special_delivery' => array(
				'type' => 'checkbox',
				'title' => __('Estonia Payment Method EEAC / Enable special delivery information support', 'svea-payments'),
				'default' => 'no',
				'desc_tip' => true,
				'description' => __('This enables the special functionality for delivery info plugins without checkout addresses.', 'svea-payments'),
				'options' => array(
					'yes' => '1',
					'no' => '0',
				),
			),
		);

		return array_merge($payment_settings, $widget_settings, $estonia_settings);
	}


	/**
	 * Returns the settings for the Part Payment Widget.
	 *
	 * This function encapsulates the settings array, making it easy to manage
	 * and merge with other plugin settings.
	 * 
	 * @since 2.6.16
	 */
	public function get_part_payment_widget_settings()
	{

		return array(
			// Section: General Settings
			'partpayment_widget_settings' => array(
				'title' => __('Part Payment Widget Settings', 'svea-payments'),
				'type' => 'title',
				'description' => __('All settings for the part payment calculator widget are here.', 'svea-payments'),
				'id' => 'partpayment_widget_settings',
			),
			'partpayment_widget_use_test' => array(
				'type' => 'checkbox',
				'title' => __('Test Environment', 'svea-payments'),
				'label' => __('Use test environment for Part Payment widget API calls', 'svea-payments'),
				'default' => 'no',
				'desc_tip' => true,
				'description' => __('Enable this if you use Svea test environment account in the credentials.', 'svea-payments'),
			),
			'partpayment_widget_layout' => array(
				'type' => 'select',
				'title' => __('Widget Layout', 'svea-payments'),
				'default' => 'full',
				'desc_tip' => true,
				'description' => __('Select the layout for the part payment widget.', 'svea-payments'),
				'options' => array(
					'full' => __('Full', 'svea-payments'),
					'mini' => __('Mini', 'svea-payments'),
					'button' => __('Button', 'svea-payments'),
				),
			),
			'partpayment_widget_margin' => array(
				'type' => 'text',
				'title' => __('Widget Margin', 'svea-payments'),
				'desc_tip' => true,
				'default' => '5px',
				'description' => __('Set the margin for the part payment widget. Use format "10px".', 'svea-payments'),
			),
			'partpayment_widget_location' => array(
				'type' => 'select',
				'title' => __('Product Page Location', 'svea-payments'),
				'desc_tip' => true,
				'default' => '',
				'description' => __('Select Svea Part Payment Widget location on product page.', 'svea-payments'),
				'options' => $this->get_widget_locations(),
			),
			'partpayment_widget_cart_location' => array(
				'type' => 'select',
				'title' => __('Cart Page Location', 'svea-payments'),
				'desc_tip' => true,
				'default' => '',
				'description' => __('Select Svea Part Payment Widget location on cart page.', 'svea-payments'),
				'options' => $this->get_widget_cart_locations(),
			),
			'partpayment_widget_checkout_location' => array(
				'type' => 'select',
				'title' => __('Checkout Page Location', 'svea-payments'),
				'desc_tip' => true,
				'default' => '',
				'description' => __('Select Svea Part Payment Widget location on checkout page.', 'svea-payments'),
				'options' => $this->get_widget_checkout_locations(),
			),
			'ppw_border_color' => array(
				'type' => 'color',
				'title' => __('Border Color', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Widget border color.', 'svea-payments'),
				'default' => get_option('ppw_border_color', '#CCEEF5'),
				'css' => 'width:100px;',
			),
			'ppw_text_color' => array(
				'type' => 'color',
				'title' => __('Text Color', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Widget text color.', 'svea-payments'),
				'default' => get_option('ppw_text_color', '#00325C'),
				'css' => 'width:100px;',
			),
			'ppw_highlight_color' => array(
				'type' => 'color',
				'title' => __('Highlight Color', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Widget highlight color.', 'svea-payments'),
				'default' => get_option('ppw_highlight_color', '#00325C'),
				'css' => 'width:100px;',
			),
			'ppw_active_color' => array(
				'type' => 'color',
				'title' => __('Active Color', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Widget active color.', 'svea-payments'),
				'default' => get_option('ppw_active_color', '#00AECE'),
				'css' => 'width:100px;',
			),
			'ppw_campaign_text_fi' => array(
				'type' => 'text',
				'title' => __('Campaign Text (Finnish)', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('This text is shown when a specific campaign is active.', 'svea-payments'),
				'default' => get_option('ppw_campaign_text_fi', 'Campaign text FI'),
			),
			'ppw_campaign_text_sv' => array(
				'type' => 'text',
				'title' => __('Campaign Text (Swedish)', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('This text is shown when a specific campaign is active.', 'svea-payments'),
				'default' => get_option('ppw_campaign_text_sv', 'Campaign text SV'),
			),
			'ppw_campaign_text_en' => array(
				'type' => 'text',
				'title' => __('Campaign Text (English)', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('This text is shown when a specific campaign is active.', 'svea-payments'),
				'default' => get_option('ppw_campaign_text_en', 'Campaign text EN'),
			),
			'ppw_fallback_text_fi' => array(
				'type' => 'text',
				'title' => __('Fallback Text (Finnish)', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('This text is shown if the calculator cannot be displayed.', 'svea-payments'),
				'default' => get_option('ppw_fallback_text_fi', 'Fallback text FI'),
			),
			'ppw_fallback_text_sv' => array(
				'type' => 'text',
				'title' => __('Fallback Text (Swedish)', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('This text is shown if the calculator cannot be displayed.', 'svea-payments'),
				'default' => get_option('ppw_fallback_text_sv', 'Fallback text SV'),
			),
			'ppw_fallback_text_en' => array(
				'type' => 'text',
				'title' => __('Fallback Text (English)', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('This text is shown if the calculator cannot be displayed.', 'svea-payments'),
				'default' => get_option('ppw_fallback_text_en', 'Fallback text EN'),
			),
			'ppw_price_threshold_minimum' => array(
				'type' => 'text',
				'title' => __('Minimum Price Threshold', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('If empty, the minimum threshold is deduced from the payment plans returned by Svea. Enter a value to override.', 'svea-payments'),
				'default' => get_option('ppw_price_threshold_minimum', ''),
				'css' => 'width:60px;',
			),
			'ppw_price_thresholds' => array(
				'type' => 'text',
				'title' => __('Price Thresholds', 'svea-payments'),
				'desc_tip' => true,
				'description' => __('Set price thresholds in the format: [price, months], [price, months] etc.', 'svea-payments'),
				'default' => get_option('ppw_price_thresholds', '[300, 6], [1000, 12]'),
				'css' => 'width:250px',
			),
		);
	}


	/**
	 * Payment method handling cost table content
	 *
	 * @since 2.1.3
	 */
	public function generate_payment_method_handling_cost_table_html()
	{

		ob_start();

		$payment_method_handling_costs_handler = new WC_Payment_Handling_Costs($this->gateway);

		$handling_cost_field = null;
		foreach ($this->gateway->get_form_fields() as $field) {
			if ($field['type'] === 'payment_method_handling_cost_table') {
				$handling_cost_field = $field;
				break;
			}
		}

		if (!isset($handling_cost_field)) {
			return;
		}

		$this->gateway->render(
			'payment-method-handling-cost-table',
			'admin',
			array(
				'field' => $handling_cost_field,
				'payment_method_handling_costs' => $payment_method_handling_costs_handler->get_handling_costs_by_payment_method(),
			)
		);

		return ob_get_clean();
	}

	public function get_tax_class_options()
	{
		foreach (\WC_Tax::get_tax_classes() as $tax_class) {
			$tax_classes[sanitize_title($tax_class)] = $tax_class;
		}
		if (!isset($tax_classes) || empty($tax_classes)) {
			$tax_classes = array();
		}
		$tax_classes = $tax_classes + array('' => __('Standard', 'woocommerce'));
		asort($tax_classes);
		return $tax_classes;
	}

	/**
	 * Get list of possible locations for part payment widget on product page
	 *
	 * @since 2.4.1
	 */
	public function get_widget_locations()
	{
		$widget_locations = array('Disabled', 'Before add to cart quantity', 'After add to cart button', 'After add to cart form');
		return $widget_locations;
	}

	/**
	 * Get list of possible locations for part payment widget on cart page
	 *
	 * @since 2.5.16
	 */
	public function get_widget_cart_locations()
	{
		$widget_locations = array('Disabled', 'After order total', "After cart table", "Before cart totals");
		return $widget_locations;
	}

	/**
	 * Get list of possible locations for part payment widget on checkout page
	 *
	 * @since 2.5.16
	 */
	public function get_widget_checkout_locations()
	{
		$widget_locations = array('Disabled', 'Before payment', 'After payment');
		return $widget_locations;
	}


	/**
	 * Handles saving payment method handling costs
	 *
	 * @return array
	 *
	 * @since 2.1.3
	 */
	public function save_payment_method_handling_costs()
	{
		$errors = array();
		$payment_method_handling_costs = array();

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
						$errors[] = __(
							'Invalid payment method handling costs, not a valid numeric value -> "'
							. $handling_cost_amounts[$i] . "'",
							'svea-payments'
						);
					} else {
						$handling_cost_amounts[$i] = floatval(str_replace(',', '.', $handling_cost_amounts[$i]));
					}
				}

				$payment_method_handling_costs[] = array(
					'payment_method_type' => $payment_method_types[$i],
					'handling_cost_amount' => $handling_cost_amounts[$i],
				);
			}
		}

		if (count($errors) > 0) {
			return $errors;
		}

		update_option('payment_method_handling_costs', $payment_method_handling_costs);

		return array();
	}

	/**
	 * Hide fields whose features are not available for outbound payments in wp-admin
	 *
	 * @since 2.2.0
	 */
	public function toggle_gateway_admin_settings($is_outbound_payment_enabled)
	{
		wc_enqueue_js(
			'
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
		'
		);
	}
}
