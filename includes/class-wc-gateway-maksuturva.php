<?php
/**
 * WooCommerce Maksuturva Payment Gateway
 *
 * @package WooCommerce Maksuturva Payment Gateway
 */

/**
 * Maksuturva Payment Gateway Plugin for WooCommerce 2.x
 * Plugin developed for Maksuturva
 * Last update: 08/03/2016
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

require_once 'class-wc-gateway-implementation-maksuturva.php';
require_once 'class-wc-meta-box-maksuturva.php';
require_once 'class-wc-payment-validator-maksuturva.php';
require_once 'class-wc-payment-maksuturva.php';

/**
 * Class WC_Gateway_Maksuturva.
 *
 * Handles the administration of the Maksuturva payment gateway. Handles checking of Maksuturva responses.
 *
 * @since 2.0.0
 */
class WC_Gateway_Maksuturva extends WC_Payment_Gateway {

	/**
	 * The text domain to use for translations.
	 *
	 * @since 2.0.0
	 *
	 * @var string $td The text domain.
	 */
	public $td;

	/**
	 * The notification URL for the payment gateway.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	protected $notify_url;

	/**
	 * The Maksuturva queue table name.
	 *
	 * @since 2.0.0
	 *
	 * @var string $table_name The queue table name.
	 */
	protected $table_name;

	/**
	 * WC_Gateway_Maksuturva constructor.
	 *
	 * Initializes the gateway, and adds necessary actions for parsing the
	 * payment gateway response.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		global $wpdb;

		$this->id = 'WC_Gateway_Maksuturva';
		$this->td = 'wc-maksuturva';

		$this->title              = __( 'Maksuturva', $this->td );
		$this->method_title       = __( 'Maksuturva', $this->td );
		$this->method_description = __( 'Take payments via Maksuturva.', $this->td );

		$this->notify_url         = WC()->api_request_url( $this->id );

		$this->icon = WC_Maksuturva::get_instance()->get_plugin_url() . 'maksuturva_logo.png';

		$this->table_name = $wpdb->prefix . 'maksuturva_queue';

		$this->has_fields = false;

		$this->init_form_fields();
		$this->init_settings();

		// Save the settings.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id,
		array( $this, 'process_admin_options' ) );

		add_action( 'woocommerce_api_wc_gateway_maksuturva', array( $this, 'check_response' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
	}

	/**
	 * @inheritdoc
	 */
	public function admin_options() {

		if ( ! WC_Maksuturva::get_instance()->is_currency_supported() ) {
			$this->render( 'not-supported-banner', 'admin' );
		}
		parent::admin_options();
	}

	/**
	 * @inheritdoc
	 */
	public function display_errors() {
		// Todo: make this work. $this->render('errors');.
		var_dump( $this->errors );
		die();
	}

	/**
	 * @inheritdoc
	 */
	public function init_form_fields() {
		$form = array();

		$form['enabled']               = array(
			'title'   => __( 'Enable/Disable', $this->td ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Maksuturva Payment Gateway', $this->td ),
			'default' => 'yes',
		);
		$form['title']                 = array(
			'title'       => __( 'Title', $this->td ),
			'type'        => 'text',
			'description' => __( 'This controls the title which the user sees during checkout.', $this->td ),
			'default'     => __( 'Maksuturva', $this->td ),
			'desc_tip'    => true,
		);
		$form['description']           = array(
			'title'    => __( 'Customer Message', $this->td ),
			'type'     => 'textarea',
			'default'  => __( 'Pay via Maksuturva.', $this->td ),
			'desc_tip' => true,
		);
		$form['sandbox']               = array(
			'type'        => 'checkbox',
			'title'       => __( 'Sandbox mode', $this->td ),
			'default'     => 'no',
			'description' => __( 'Maksuturva sandbox can be used to test payments. None of the payments will be real.',
			$this->td ),
			'options'     => array( 'yes' => '1', 'no' => '0' ),
		);
		$form['debug']                 = array(
			'type'        => 'checkbox',
			'title'       => __( 'Debug Log', $this->td ),
			'default'     => 'no',
			'description' => sprintf( __( 'Enable logging to <code>%s</code>', $this->td ),
			wc_get_log_file_path( $this->id ) ),
			'options'     => array( 'yes' => '1', 'no' => '0' ),
		);
		$form['maksuturva_sellerid']   = array(
			'type'        => 'textfield',
			'title'       => __( 'Seller id', $this->td ),
			'desc_tip'    => true,
			'description' => __( 'The seller identification provided by Maksuturva upon your registration.',
			$this->td ),
		);
		$form['maksuturva_secretkey']  = array(
			'type'        => 'textfield',
			'title'       => __( 'Secret Key', $this->td ),
			'desc_tip'    => true,
			'description' => __( 'Your unique secret key provided by Maksuturva.', $this->td ),
		);
		$form['maksuturva_keyversion'] = array(
			'type'        => 'textfield',
			'title'       => __( 'Secret Key Version', $this->td ),
			'desc_tip'    => true,
			'description' => __( 'The version of the secret key provided by Maksuturva.', $this->td ),
			'default'     => get_option( 'maksuturva_keyversion', '001' ),
		);
		/* I don't think these are needed at the UI, but enabled it for now / JH */
		$form['maksuturva_url'] = array(
			'type'        => 'textfield',
			'title'       => __( 'Gateway URL', $this->td ),
			'desc_tip'    => true,
			'description' => __( 'The URL used to communicate with Maksuturva. Do not change this configuration unless you know what you are doing.',
			$this->td ),
			'default'     => get_option( 'maksuturva_url', 'https://www.maksuturva.fi' ),
		);

		$form['maksuturva_orderid_prefix'] = array(
			'type'        => 'textfield',
			'title'       => __( 'Maksuturva Payment Prefix', $this->td ),
			'desc_tip'    => true,
			'description' => __( 'Prefix for order identifiers. Can be used to generate unique payment ids after e.g. reinstall.',
			$this->td ),
		);

		$this->form_fields = $form;
	}

	/**
	 * Get the queue table name.
	 *
	 * Returns the name of the Maksuturva queue table.
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
	 * @param string $payment_id The Maksuturva payment ID.
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
	 * @param string $payment_id The Maksuturva payment ID.
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
		return $this->get_option('maksuturva_encoding', 'UTF-8');
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
	 * Is sandbox.
	 *
	 * Checks if the sandbox mode is on.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_sandbox() {
		return ( $this->get_option( 'sandbox' ) === 'yes' );
	}

	/**
	 * Load order by payment id.
	 *
	 * Returns the order found by the given payment id.
	 *
	 * @param string $pmt_id The Maksuturva payment id.
	 *
	 * @since 2.0.0
	 *
	 * @return WC_Order
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
		$order = wc_get_order( $order_id );

		return array(
			'result'   => 'success',
			'redirect' => add_query_arg( 'order-pay', $order->id,
			add_query_arg( 'key', $order->order_key, $order->get_checkout_order_received_url() ) ),
		);
	}

	/**
	 * Print receipt page.
	 *
	 * Shows the receipt page and redirects the user to the payment gateway.
	 *
	 * @since 2.0.0
	 *
	 * @param int $order_id The order id.
	 */
	public function receipt_page( $order_id ) {

		$order               = wc_get_order( $order_id );
		$gateway             = new WC_Gateway_Implementation_Maksuturva( $this, $order );
		$payment_gateway_url = $gateway->get_payment_url();
		$data                = $gateway->get_field_array();

		if ( ! $this->is_sandbox() ) {
			// Create the payment for Maksuturva.
			WC_Payment_Maksuturva::create( array(
				'order_id'      => $order->id,
				'payment_id'    => $data['pmt_id'],
				'data_sent'     => $data,
				'data_received' => array(),
				'status'        => WC_Payment_Maksuturva::STATUS_PENDING,
			) );
		}

		$this->render( 'maksuturva-form', 'frontend',
		array( 'order' => $order, 'payment_gateway_url' => $payment_gateway_url, 'data' => $data ) );
	}

	/**
	 * Check response.
	 *
	 * Checks the response from Maksuturva, validates the response, and redirects to correct URL.
	 *
	 * @since 2.0.0
	 */
	public function check_response() {

		global $woocommerce;

		if ( ! WC_Maksuturva::get_instance()->is_currency_supported() ) {
			$msg = __( 'Payment gateway not available.', $this->td );
			wc_add_notice( $msg );
			wp_redirect( $woocommerce->cart->get_cart_url() );
		}

		$params = $_GET;
		if ( ! isset( $params['pmt_id'] ) ) {
			$msg = __( 'Missing reference number in response.', $this->td );
			wc_add_notice( $msg, 'error' );
			wp_redirect( $woocommerce->cart->get_cart_url() );
		}

		$order     = $this->load_order_by_pmt_id( $params['pmt_id'] );
		$gateway   = new WC_Gateway_Implementation_Maksuturva( $this, $order );
		$validator = $gateway->validate_payment( $params );

		$payment = null;
		$type    = 'success';

		if ( ! $this->is_sandbox() ) {
			$payment = new WC_Payment_Maksuturva( $order->id );
			$payment->update_data_received( $params );
		}

		if ( $validator->get_status() === WC_Payment_Maksuturva::STATUS_ERROR ) {
			// Error case.
			$msg  = __( 'Error from Maksuturva received.', $this->td );
			$type = 'error';
			$order->update_status( 'failed', $msg );

			if ( null !== $payment ) {
				$payment->error();
			}

			$redirect_url = add_query_arg( 'key', $order->order_key, $this->get_return_url( $order ) );
		} elseif ( $validator->get_status() === WC_Payment_Maksuturva::STATUS_DELAYED ) {
			// Delayed case.
			$msg  = __( 'Payment delayed by Maksuturva.', $this->td );
			$type = 'notice';
			$order->update_status( WC_Payment_Maksuturva::STATUS_DELAYED, $msg );

			if ( null !== $payment ) {
				$payment->delayed();
			}

			$redirect_url = add_query_arg( 'key', $order->order_key, $this->get_return_url( $order ) );
		} elseif ( $validator->get_status() === WC_Payment_Maksuturva::STATUS_CANCELLED ) {
			// Cancelled case.
			$msg  = __( 'Cancellation from Maksuturva received.', $this->td );
			$type = 'notice';
			$order->cancel_order( $msg );

			if ( null !== $payment ) {
				$payment->cancel();
			}

			$redirect_url = add_query_arg( 'key', $order->order_key, $order->get_cancel_order_url() );
		} else {
			// Order completed.
			$order->payment_complete( $params['pmt_id'] );

			if ( null !== $payment && ! $this->is_sandbox() ) {
				$payment->complete();
			}

			$woocommerce->cart->empty_cart();
			$msg          = __( 'Payment confirmed by Maksuturva.', $this->td );
			$redirect_url = $this->get_return_url( $order );
		}

		// Add surcharge to the order.
		if ( null !== $payment
		     && ( $payment->get_status() !== WC_Payment_Maksuturva::STATUS_CANCELLED
		     && $payment->includes_surcharge() )
		) {
			$this->add_surcharge( $payment->get_surcharge(), $order );
		}

		wc_add_notice( $msg, $type );
		wp_redirect( $redirect_url );
	}

	/**
	 * Add surcharge.
	 *
	 * Adds a surcharge fee to the order.
	 *
	 * @param float    $amount The amount to add.
	 * @param WC_Order $order  The order.
	 *
	 * @since 2.0.0
	 */
	protected function add_surcharge( $amount, $order ) {
		$fee          = new stdClass();
		$fee->name    = __( 'Surcharge from Payment Gateway', $this->td );
		$fee->amount  = $amount;
		$fee->taxable = false;
		$order->add_fee( $fee );
		$order->calculate_totals();
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
