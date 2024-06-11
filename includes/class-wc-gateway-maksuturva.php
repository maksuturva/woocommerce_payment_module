<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 4.x, 5.x
 * Plugin developed for Svea
 * Last update: 11/04/2021
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

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'class-wc-gateway-admin-form-fields.php';
require_once 'class-wc-gateway-implementation-maksuturva.php';
require_once 'class-wc-meta-box-maksuturva.php';
require_once 'class-wc-order-compatibility-handler.php';
require_once 'class-wc-payment-maksuturva.php';
require_once 'class-wc-payment-method-select.php';
require_once 'class-wc-payment-validator-maksuturva.php';
require_once 'class-wc-svea-delivery-handler.php';
require_once 'class-wc-svea-refund-handler.php';
require_once 'class-wc-utils-maksuturva.php';

/**
 * Class WC_Gateway_Maksuturva.
 *
 * Handles the administration of the Svea payments gateway. Handles checking of Svea responses.
 *
 * @since 2.0.0
 */
class WC_Gateway_Maksuturva extends \WC_Payment_Gateway {

	/**
	 * Major WooCommerce version no longer supporting notices on cancellation, thank you etc
	 */
	const NO_NOTICE_VERSION = 3;

	/**
	 * The notification URL for the payment gateway.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	protected $notify_url;

	/**
	 * The Svea queue table name.
	 *
	 * @since 2.0.0
	 *
	 * @var string $table_name The queue table name.
	 */
	protected $table_name;

	/**
	 * The payment method select handler
	 *
	 * @since 2.1.3
	 *
	 * @var WC_Payment_Method_Select $payment_method_select The payment method select handler.
	 */
	protected $payment_method_select;

	/**
	 * The payment method type
	 *
	 * @since 2.1.3
	 *
	 * @var string $payment_method_type The payment method type.
	 */
	protected $payment_method_type;

	/**
	 * The outbound payment indicator
	 *
	 * @since 2.2.0
	 *
	 * @var boolean
	 */
	protected $outbound_payment;

	/**
	 * WC_Gateway_Maksuturva constructor.
	 *
	 * Initializes the gateway, and adds necessary actions for parsing the
	 * payment gateway response.
	 *
	 * @since 2.0.0
	 */
	public function __construct( $id = null ) {
		global $wpdb;

		$this->id = isset( $id ) ? $id : self::class;

		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->method_title       = __( 'Svea', 'wc-maksuturva' );
		$this->method_description = __( 'Take payments via Svea.', 'wc-maksuturva' );

		$this->outbound_payment = $this->get_option( 'outbound_payment' );

		$this->notify_url = WC()->api_request_url( self::class );

		$this->icon = WC_Maksuturva::get_instance()->get_plugin_url() . 'Svea_logo.png';

		$this->table_name = $wpdb->prefix . 'maksuturva_queue';

		$this->payment_method_select = new WC_Payment_Method_Select( $this );

		$this->has_fields = true;

		$this->supports[] = 'refunds';

		$this->init_form_fields();
		$this->init_settings();

		// Save the settings.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action( 'woocommerce_api_wc_gateway_maksuturva', array( $this, 'check_response' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );

		add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed_event' ), 10, 3 );

		// see Github issue #23, @since 2.1.7, 2.4.2 added is checkout boolean
		// if (!is_admin() && is_checkout()) {
		if ( ! is_admin() ) { // TODO: ## Restore to old
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'payment_gateway_disable_empty' ) );
		}

		add_filter( 'woocommerce_gateway_title', array( $this, 'override_payment_gateway_title' ), 25, 2 );
	}

	public function override_payment_gateway_title( $title, $gateway_id ) {
		global $woocommerce, $post;
		if ( ! is_admin() ) {
			return $title;
		}
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			if (
				'wc-orders' != wc_clean( $_GET['page'] )
				// (!('shop_order' === OrderUtil::get_order_type( $order_id )) )
				|| ( ! str_contains( $gateway_id, 'Maksuturva' ) && ! str_contains( $gateway_id, 'Svea' ) )
			) {
				return $title;
			}

			if ( empty( $_GET['id'] ) ) {
				return $title;
			}
			$order_id = wc_clean( $_GET['id'] );
			$order    = new \WC_Order( $order_id );
		} else {
			// old WC way
			if (
				( 'shop_order' != get_post_type() )
				|| ( ! str_contains( $gateway_id, 'Maksuturva' ) && ! str_contains( $gateway_id, 'Svea' ) )
			) {
				return $title;
			}

			$order = new \WC_Order( $post->ID );
		}

		if ( ! empty( $order ) && $order->get_payment_method() !== null ) {
			$payment       = new WC_Payment_Maksuturva( $order->get_id() );
			$paymentMethod = $payment->get_payment_method();
			if ( ! empty( $paymentMethod ) ) {
				$paymentMethodName = $this->payment_method_select->get_payment_method_name( $paymentMethod );
				if ( ! empty( $paymentMethodName ) && ! str_contains( $title, $paymentMethodName ) ) {
					return $title . ': ' . $paymentMethodName;
				}
			}
		}

		return $title;
	}

	/**
	 * Hide gateways without payment methods from shop
	 *
	 * @param array $available_gateways The available gateways.
	 *
	 * @since 2.1.3
	 *
	 * @return array
	 */
	public function payment_gateway_disable_empty( $available_gateways ) {
		if ( empty( WC()->cart ) ) {
			return;
		}

		if ( $this->id === self::class ) {
			if ( ! $this->is_outbound_payment_enabled() ) {
				unset( $available_gateways[ $this->id ] );
			}
			return $available_gateways;
		}

		$payment_method_type = explode( 'WC_Gateway_Svea_', $this->id )[1];
		$payment_method_type = strtolower( $payment_method_type );
		$payment_method_type = str_replace( '_', '-', $payment_method_type );
		try {
			$payment_methods = $this->payment_method_select->get_payment_type_payment_methods(
				$payment_method_type,
				\WC_Payment_Gateway::get_order_total()
			);
		} catch ( \Exception $e ) {
			wc_maksuturva_log( "Couldn't get available payment gateways, reason: " . $e->getMessage() );
		}

		if ( ! isset( $payment_methods ) || count( $payment_methods ) === 0 ) {
			unset( $available_gateways[ $this->id ] );
		}

		return $available_gateways;
	}

	/**
	 * @inheritdoc
	 */
	public function admin_options() {

		if ( ! WC_Maksuturva::get_instance()->is_currency_supported() ) {
			$this->render( 'not-supported-banner', 'admin' );
		}

		$svealogo = WC_Maksuturva::get_instance()->get_plugin_url() . 'Svea_logo.png';
		?>
		<img src="<?php echo esc_url( $svealogo ); ?>" />
		<?php
		parent::admin_options();

		/***
		 * <p>You may use diagnostics functionality to send additional webstore platform information to Svea Payments
		 * when contacting Svea Payments technical support. A copy of this information can be found on your log files.</p>
		 * <button id="diagnostics">Send diagnostics</button>
		 */
	}

	/**
	 * @inheritdoc
	 */
	public function init_form_fields() {
		$gateway_admin_form_fields = new WC_Gateway_Admin_Form_Fields( $this );
		$this->form_fields         = $gateway_admin_form_fields->as_array();
		$gateway_admin_form_fields->toggle_gateway_admin_settings( $this->is_outbound_payment_enabled() );
	}

	/**
	 * Generates payment method handling cost table html
	 *
	 * @since 2.1.3
	 */
	public function generate_payment_method_handling_cost_table_html() {
		$gateway_admin_form_fields = new WC_Gateway_Admin_Form_Fields( $this );
		return $gateway_admin_form_fields->generate_payment_method_handling_cost_table_html();
	}

	/**
	 * Handles processing adming options
	 *
	 * @since 2.1.3
	 */
	public function process_admin_options() {

		$gateway_admin_form_fields = new WC_Gateway_Admin_Form_Fields( $this );
		$errors                    = $gateway_admin_form_fields->save_payment_method_handling_costs();

		$settings = new \WC_Admin_Settings();

		if ( isset( $errors ) ) {
			foreach ( $errors as $error ) {
				$settings->add_error( $error );
			}
		}
		parent::process_admin_options();
	}

	/**
	 * Payment fields method definition for selecting payment method in webstore.
	 *
	 * @since 2.1.3
	 */
	public function payment_fields() {
		if ( $this->is_outbound_payment_enabled() ) {
			$payment_method_type = 'outbound';
		} else {
			$payment_method_type = str_replace( '_', '-', strtolower( explode( 'WC_Gateway_Svea_', $this->id )[1] ) );
		}

		return $this->payment_method_select->initialize_payment_method_select(
			$payment_method_type,
			\WC_Payment_Gateway::get_order_total(),
			$this->is_outbound_payment_enabled()
		);
	}

	/**
	 * Validating payment method that is selected in webstore.
	 *
	 * @since 2.1.3
	 */
	public function validate_fields() {
		if ( ! $this->is_outbound_payment_enabled() ) {
			$valid = $this->payment_method_select->validate_payment_method_select();

			if ( ! $valid ) {
				return false;
			}
		}

		return parent::validate_fields();
	}

	/**
	 * Process refund
	 *
	 * More documentation: https://docs.woocommerce.com/wc-apidocs/class-WC_Payment_Gateway.html#_process_refund
	 *
	 * @param int    $order_id The order id.
	 * @param int    $amount The amount.
	 * @param string $reason The reason.
	 *
	 * @since 2.1.2
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$payment             = new WC_Payment_Maksuturva( $order_id );
		$svea_refund_handler = new WC_Svea_Refund_Handler( $order_id, $payment, $this );
		return $svea_refund_handler->process_refund( $amount, $reason );
	}

	/**
	 * Generate radio button HTML.
	 *
	 * Returns the HTML for a radio button.
	 *
	 * @param  string $key
	 * @param  array  $data
	 *
	 * @since  2.0.0
	 *
	 * @return string
	 */
	public function generate_radio_html( $key, $data ) {

		$field    = $this->get_field_key( $key );
		$defaults = array(
			'title'             => '',
			'label'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		if ( ! $data['label'] ) {
			$data['label'] = $data['title'];
		}

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span>
					</legend>
					<?php foreach ( $data['options'] as $value => $label ) : ?>
						<label for="<?php echo esc_attr( $field . '_' . $value ); ?>">
						<input <?php disabled( $data['disabled'], true ); ?>
							class="<?php echo esc_attr( $data['class'] ); ?>"
							type="radio"
							name="<?php echo esc_attr( $field ); ?>"
							id="<?php echo esc_attr( $field . '_' . $value ); ?>"
							style="<?php echo esc_attr( $data['css'] ); ?>"
							value="<?php echo esc_attr( $value ); ?>"
							<?php checked( $this->get_option( $key, $data['default'] ), $value ); ?>
							<?php echo $this->get_custom_attribute_html( $data ); ?> />
							<?php echo wp_kses_post( $label ); ?>
						</label><br/>
					<?php endforeach; ?>
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get the queue table name.
	 *
	 * Returns the name of the Svea queue table.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * Get the payment URL.
	 *
	 * Returns the OK, ERROR, CANCEL, DELAY URL for the payment gateway.
	 *
	 * @param string $payment_id The Svea payment ID.
	 * @param string $type       The type, one of: ok, cancel, error, delay
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_payment_url( $payment_id, $type ) {
		$base_url = $this->get_payment_base_url( $payment_id );

		return add_query_arg( 'pmt_act', $type, $base_url );
	}

	/**
	 * Get the payment base URL.
	 *
	 * Constructs the URL from notify_url, and adds session id and order id to the URL.
	 *
	 * @param string $payment_id The Svea payment ID.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	protected function get_payment_base_url( $payment_id ) {
		global $woocommerce;

		$return_url = $this->notify_url;
		if ( $woocommerce->session && $woocommerce->session->id ) {
			$session_id = $woocommerce->session->id;
			$return_url = add_query_arg( 'sessionid', $session_id, $this->notify_url );
			$return_url = add_query_arg( 'orderid', $payment_id, $return_url );
		}

		return $return_url;
	}

	/**
	 * Get the notify URL.
	 *
	 * Returns the URL to where payment gateway should return to.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_notify_url() {
		return $this->notify_url;
	}

	/**
	 * Get option
	 *
	 * Returns the main gateway's option when it is used, otherwise current gateway's option.
	 *
	 * @since 2.1.3
	 *
	 * @return string|int|array|null
	 */
	public function get_option( $key, $empty_value = null ) {

		if ( $key === 'enabled' ) {
			return parent::get_option( $key, $empty_value );
		}

		$option_name   = 'woocommerce_' . self::class . '_settings';
		$main_settings = get_option( $option_name );

		if ( isset( $main_settings[ $key ] ) ) {
			return $main_settings[ $key ];
		}

		// Fetch the previous namespace settings if the current namespace settings are not found
		$previous_namespace = str_replace( __NAMESPACE__, '', $option_name );
		$previous_namespace = str_replace( '\\', '', $previous_namespace );
		$fallback_settings  = get_option( $previous_namespace );

		if ( isset( $fallback_settings[ $key ] ) ) {
			return $fallback_settings[ $key ];
		}

		return parent::get_option( $key, $empty_value );
	}

	/**
	 * Get the seller id.
	 *
	 * Returns the defined seller id.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_seller_id() {
		return $this->get_option( 'maksuturva_sellerid' );
	}

	/**
	 * Get the seller secret key.
	 *
	 * Returns the defined seller secret key.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_secret_key() {
		return $this->get_option( 'maksuturva_secretkey' );
	}

	/**
	 * Get the secret key version.
	 *
	 * Returns the version of the secret key.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_secret_key_version() {
		return $this->get_option( 'maksuturva_keyversion' );
	}

	/**
	 * Get the encoding for the site.
	 *
	 * Returns the charset for the blog.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_encoding() {
		return $this->get_option( 'maksuturva_encoding', 'UTF-8' );
	}

	/**
	 * Get the payment id prefix.
	 *
	 * Returns the defined payment id prefix.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_payment_id_prefix() {
		return $this->get_option( 'maksuturva_orderid_prefix' );
	}

	/**
	 * Get the gateway URL.
	 *
	 * Returns the URL to the gateway.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_gateway_url() {
		return $this->get_option( 'maksuturva_url' );
	}

	/**
	 * Checks if outbound payments are enabled.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public function is_outbound_payment_enabled() {
		return $this->outbound_payment == 'yes';
	}

	/**
	 * Is sandbox.
	 *
	 * Checks if the sandbox mode is on.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_sandbox() {
		return false;
		// return ( $this->get_option( 'sandbox' ) === 'yes' );
	}

	/**
	 * Is Estonia Special Delivery functionality enabled.
	 *
	 * Checks if the Estonia delivery functionality is enabled
	 *
	 * @since 2.1.5
	 *
	 * @return bool
	 */
	public function is_estonia_special_delivery() {
		return ( $this->get_option( 'estonia_special_delivery' ) === 'yes' );
	}

	/**
	 * Load order by payment id.
	 *
	 * Returns the order found by the given payment id.
	 *
	 * @param string $pmt_id The Svea payment id.
	 *
	 * @since 2.0.0
	 *
	 * @return \WC_Order
	 */
	protected function load_order_by_pmt_id( $pmt_id ) {
		$pmt_id_prefix = $this->get_option( 'maksuturva_orderid_prefix' );
		if ( strlen( $pmt_id_prefix ) && substr( $pmt_id, 0, strlen( $pmt_id_prefix ) ) === $pmt_id_prefix ) {
			$pmt_id = substr( $pmt_id, strlen( $pmt_id_prefix ) );
		}
		$order_id = (int) $pmt_id - 100;

		return wc_get_order( $order_id );
	}

	/**
	 * @inheritdoc
	 */
	public function process_payment( $order_id ) {

		$order         = wc_get_order( $order_id );
		$order_handler = new WC_Order_Compatibility_Handler( $order );

		/**
		 * special functionality for Estonia EEAC payment method, needs to be activated in the admin panel
		 *
		 * @since 2.1.5
		 */
		if ( $this->is_estonia_special_delivery() && $order->get_payment_method() == 'WC_Gateway_Svea_Estonia_Payments' ) {
			if ( trim( $order->get_shipping_postcode() ) == '' ) {
				$order->set_shipping_postcode( '00000' );
			}
			if ( trim( $order->get_shipping_city() ) == '' ) {
				$order->set_shipping_city( 'none' );
			}

			if ( trim( $order->get_billing_first_name() ) == '' ) {
				$order->set_billing_first_name( 'none' );
			}
			if ( trim( $order->get_billing_last_name() ) == '' ) {
				$order->set_billing_last_name( 'none' );
			}
			if ( trim( $order->get_billing_address_1() ) == '' ) {
				$order->set_billing_address_1( 'none' );
			}
			if ( trim( $order->get_billing_postcode() ) == '' ) {
				$order->set_billing_postcode( '00000' );
			}
			if ( trim( $order->get_billing_city() ) == '' ) {
				$order->set_billing_city( 'none' );
			}
			if ( trim( $order->get_billing_country() ) == '' ) {
				$order->set_billing_country( 'EE' );
			}

			if ( trim( $order->get_billing_country() ) == '' ) {
				$order->set_billing_country( 'EE' );
			}
			$order->save();
		}
		$url = $order->get_checkout_order_received_url();
		$url = add_query_arg( 'key', $order_handler->get_order_key(), $url );
		$url = add_query_arg( 'order-pay', $order_handler->get_id(), $url );

		if ( ! $this->is_outbound_payment_enabled() ) {
			$payment_method = WC_Utils_Maksuturva::filter_alphanumeric( $_POST[ WC_Payment_Method_Select::PAYMENT_METHOD_SELECT_ID ] );
			$url            = add_query_arg( WC_Payment_Method_Select::PAYMENT_METHOD_SELECT_ID, $payment_method, $url );
		}

		return array(
			'result'   => 'success',
			'redirect' => $url,
		);
	}

	/**
	 * Print receipt page.
	 *
	 * Shows the receipt page and redirects the user to the payment gateway.
	 *
	 * @param int $order_id The order id.
	 * @throws WC_Gateway_Maksuturva_Exception
	 * @since 2.0.0
	 */
	public function receipt_page( $order_id ) {

		$order = wc_get_order( $order_id );

		$payment_handling_costs = new WC_Payment_Handling_Costs( $this );
		$payment_handling_costs->update_payment_handling_cost_fee( $order );

		$gateway             = new WC_Gateway_Implementation_Maksuturva( $this, $order );
		$order_handler       = new WC_Order_Compatibility_Handler( $order );
		$payment_gateway_url = $gateway->get_payment_url();
		$data                = $gateway->get_field_array();
		$payment_method      = isset( $data['pmt_paymentmethod'] ) ? $data['pmt_paymentmethod'] : '';

		// Create the payment for Svea.
		WC_Payment_Maksuturva::create(
			array(
				'order_id'       => $order_handler->get_id(),
				'payment_id'     => $data['pmt_id'],
				'payment_method' => $payment_method,
				'data_sent'      => $data,
				'data_received'  => array(),
				'status'         => WC_Payment_Maksuturva::STATUS_PENDING,
			)
		);

		$this->render(
			'maksuturva-form',
			'frontend',
			array(
				'order'               => $order,
				'payment_gateway_url' => $payment_gateway_url,
				'data'                => $data,
			)
		);
	}

	/**
	 * Check response.
	 *
	 * Checks the response from Svea, validates the response, and redirects to correct URL.
	 *
	 * @since 2.0.0
	 */
	public function check_response() {

		global $woocommerce;

		// Clear any existing notices in case of "double-submissions".
		wc_clear_notices();

		if ( ! WC_Maksuturva::get_instance()->is_currency_supported() ) {
			$this->add_notice( __( 'Payment gateway not available.', 'wc-maksuturva' ), 'error' );
			wp_redirect( $woocommerce->cart->get_cart_url() );

			return;
		}

		$params = $_GET;
		// Make sure the payment id is found in the return parameters, and that it actually exists.
		if ( ! isset( $params['pmt_id'] ) || false === ( $order = $this->load_order_by_pmt_id( $params['pmt_id'] ) ) ) {
			$this->add_notice( __( 'Missing reference number in response.', 'wc-maksuturva' ), 'error' );
			wp_redirect( $woocommerce->cart->get_cart_url() );
			return;
		}

		$order_handler = new WC_Order_Compatibility_Handler( $order );
		try {
			$payment = new WC_Payment_Maksuturva( $order_handler->get_id() );
		} catch ( WC_Gateway_Maksuturva_Exception $e ) {
			wc_maksuturva_log( (string) $e );
			$this->add_notice( __( 'Could not process order.', 'wc-maksuturva' ), 'error' );
			wp_redirect( $woocommerce->cart->get_cart_url() );

			return;
		}

		$gateway   = new WC_Gateway_Implementation_Maksuturva( $this, $order );
		$validator = $gateway->validate_payment( $params );

		// If the payment is already completed.
		if ( $payment->is_completed() ) {
			// Redirect the user ALWAYS to the order complete page.
			wp_redirect( $this->get_return_url( $order ) );

			return;
		}

		$payment->set_data_received( $params );

		switch ( $validator->get_status() ) {
			case WC_Payment_Maksuturva::STATUS_ERROR:
				if ( isset( $params['pmt_errortexttouser'] ) ) {
					$this->add_notice( __( 'Payment failed: ' . $params['pmt_errortexttouser'], 'wc-maksuturva' ), 'error' );
					wc_add_notice( 'Correct the checkout information and try again.' );
				} else {
					$this->add_notice( __( 'Error from Svea received.', 'wc-maksuturva' ), 'error' );
				}

				$this->order_fail( $order, $payment );
				// wp_redirect( add_query_arg( 'key', $order_handler->get_order_key(), $this->get_return_url( $order ) ) );
				wp_redirect( $woocommerce->cart->get_cart_url() );
				break;

			case WC_Payment_Maksuturva::STATUS_DELAYED:
				$this->order_delay( $order, $payment );
				$this->add_notice( __( 'Payment delayed by Svea.', 'wc-maksuturva' ), 'notice' );
				wp_redirect( add_query_arg( 'key', $order_handler->get_order_key(), $this->get_return_url( $order ) ) );
				break;

			case WC_Payment_Maksuturva::STATUS_CANCELLED:
				$this->order_cancel( $order, $payment );
				$this->add_notice( __( 'Cancellation from Svea received.', 'wc-maksuturva' ), 'notice' );
				wp_redirect( add_query_arg( 'key', $order_handler->get_order_key(), $order->get_cancel_order_url() ) );
				break;

			case WC_Payment_Maksuturva::STATUS_COMPLETED:
			default:
				$this->order_complete( $order, $payment );
				$woocommerce->cart->empty_cart();
				$this->add_notice( __( 'Payment confirmed by Svea.', 'wc-maksuturva' ), 'success' );
				wp_redirect( $this->get_return_url( $order ) );
				break;
		}
	}

	/**
	 * Check if the order is already paid.
	 *
	 * Returns if the order has already been paid.
	 *
	 * @param \WC_Order $order the order
	 *
	 * @since 2.0.2
	 *
	 * @return bool
	 */
	public function is_order_paid( \WC_Order $order ) {
		if ( method_exists( $order, 'is_paid' ) ) {
			return $order->is_paid();
		} else {
			return $order->has_status(
				array( WC_Payment_Maksuturva::STATUS_PROCESSING, WC_Payment_Maksuturva::STATUS_COMPLETED )
			);
		}
	}

	/**
	 * Add surcharge.
	 *
	 * Adds a surcharge fee to the order.
	 *
	 * @param WC_Payment_Maksuturva $payment The payment.
	 * @param \WC_Order             $order The order.
	 *
	 * @since 2.0.0
	 */
	protected function add_surcharge( $payment, $order ) {
		if ( ! $payment->is_cancelled() && $payment->includes_surcharge() ) {
			$fee          = new \stdClass();
			$fee->name    = __( 'Surcharge from Payment Gateway', 'wc-maksuturva' );
			$fee->amount  = $payment->get_surcharge();
			$fee->taxable = false;
			$order->add_fee( $fee );
			$order->calculate_totals();
		}
	}

	/**
	 * Adds a WC notice.
	 *
	 * Adds the notice, but only of it is not already added.
	 *
	 * @param string $msg The notice message.
	 * @param string $type The notice type.
	 *
	 * @since 2.0.2
	 */
	protected function add_notice( $msg, $type ) {
		if ( ! wc_has_notice( $msg, $type ) ) {
			wc_add_notice( $msg, $type );
		}
	}

	/**
	 * Fails order.
	 *
	 * Fails the order and payment if not already failed.
	 *
	 * @param \WC_Order             $order The order.
	 * @param WC_Payment_Maksuturva $payment The payment.
	 *
	 * @throws WC_Gateway_Maksuturva_Exception
	 * @since 2.0.2
	 */
	protected function order_fail( $order, $payment ) {
		if ( ! $order->has_status( WC_Payment_Maksuturva::STATUS_FAILED ) ) {
			$order->update_status(
				WC_Payment_Maksuturva::STATUS_FAILED,
				__( 'Error from Svea received.', 'wc-maksuturva' )
			);
		}

		if ( ! $payment->is_error() ) {
			$this->add_surcharge( $payment, $order );
			$payment->error();
		}
	}

	/**
	 * Cancel order.
	 *
	 * Cancels the order and payment if not already cancelled.
	 *
	 * @param \WC_Order             $order The order.
	 * @param WC_Payment_Maksuturva $payment The payment.
	 *
	 * @throws WC_Gateway_Maksuturva_Exception
	 * @since 2.0.2
	 */
	protected function order_cancel( $order, $payment ) {
		if ( ! $order->has_status( WC_Payment_Maksuturva::STATUS_CANCELLED ) ) {
			$order->cancel_order( __( 'Cancellation from Svea received.', 'wc-maksuturva' ) );
		}

		if ( ! $payment->is_cancelled() ) {
			$payment->cancel();
		}
	}

	/**
	 * Delay order.
	 *
	 * Delay the order and payment if not already delayed.
	 *
	 * @param \WC_Order             $order The order.
	 * @param WC_Payment_Maksuturva $payment The payment.
	 *
	 * @throws WC_Gateway_Maksuturva_Exception
	 * @since 2.0.2
	 */
	protected function order_delay( $order, $payment ) {
		if ( ! $payment->is_delayed() ) {
			$this->add_surcharge( $payment, $order );
			$payment->delayed();
		}
	}

	/**
	 * Complete order.
	 *
	 * Completes the order and payment if not already completed.
	 *
	 * @param \WC_Order             $order The order.
	 * @param WC_Payment_Maksuturva $payment The payment.
	 *
	 * @throws WC_Gateway_Maksuturva_Exception
	 * @since 2.0.2
	 */
	protected function order_complete( $order, $payment ) {
		if ( ! $this->is_order_paid( $order ) ) {
			$order->payment_complete( $payment->get_payment_id() );
		}

		if ( ! $payment->is_completed() ) {
			$this->add_surcharge( $payment, $order );
			$payment->complete();
		}
	}


	/**
	 * Handles the order status change event
	 *
	 * @param int    $order_id The order id.
	 * @param string $old_status The old status.
	 * @param array  $new_status The new status.
	 *
	 * @since 2.1.2
	 */
	public function order_status_changed_event( $order_id, $old_status, $new_status ) {

		if ( $old_status === $new_status ) {
			return;
		}

		$order   = wc_get_order( $order_id );
		$payment = new WC_Payment_Maksuturva( $order->get_id() );

		if ( empty( $payment->get_payment_id() ) ) {
			return;
		}

		$option = $this->get_option(
			'maksuturva_send_delivery_information_status'
		);

		if ( ! is_string( $option ) ) {
			return;
		}

		$sendDeliveryInformationToSveaStatus = preg_replace( '/^wc-/', '', $option );

		if ( $new_status === $sendDeliveryInformationToSveaStatus ) {
			$selectedPayments = $this->get_option(
				'maksuturva_send_delivery_for_specific_payments'
			);

			if ( ! empty( $selectedPayments ) ) {
				$selectedPaymentsArray = explode( ',', str_ireplace( ' ', '', $selectedPayments ) );

				if ( ! empty( $order ) && ! empty( $order->get_payment_method() ) ) {
					$paymentMethod = $payment->get_payment_method();

					if ( ! empty( $paymentMethod ) && ! in_array( $paymentMethod, $selectedPaymentsArray ) ) {
						return;
					}
				}
			}

			$deliveryHandler = new WC_Svea_Delivery_Handler( $this, $order_id );
			$deliveryHandler->send_delivery_info();
		}
	}

	/**
	 * Renders a template file.
	 *
	 * The file is expected to be located in the plugin "templates" directory. If the domain is given,
	 * the template file is expected to be located in the "templates/<domain>" directory.
	 *
	 * @since  2.0.0
	 * @access protected
	 *
	 * @param string $template The name of the template.
	 * @param string $domain   The "domain" the template belongs to. Subdirectory under /templates/.
	 * @param array  $data     The data to pass to the template file.
	 */
	public function render( $template, $domain, array $data = array() ) {
		WC_Maksuturva::get_instance()->render( $template, $domain, $data );
	}
}
