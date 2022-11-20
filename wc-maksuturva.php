<?php
/**
 * Svea Payment Gateway
 *
 * @package     Svea Payment Gateway
 *
 * @wordpress-plugin
 * Plugin Name:  Svea Payment Gateway
 * Plugin URI:   https://github.com/maksuturva/woocommerce_payment_module
 * Description: A plugin for Svea Payments, which provides intelligent online payment services consisting of the most comprehensive set of high quality service features in the Finnish market
 * Version:     2.3.0  
 * Author:      Svea Development Oy  
 * Author URI:  http://www.sveapayments.fi  
 * Text Domain: wc-maksuturva  
 * Domain Path: /languages/  
 * Requires at least: 6.0  
 * Tested up to: 7.1  
 * License:      LGPL2.1  
 * WC requires at least: 6.0.0   
 * WC tested up to: 7.1.0  
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 6.x, 7.x
 * Plugin developed for Svea Development Oy
 * Last update: 20/11/2022
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

if ( ! function_exists( '_log' ) ) {
	/**
	 * Log a message.
	 *
	 * Uses the error_log to log messages.
	 *
	 * @param string $message The message to log.
	 *
	 * @since 1.0.0
	 */
	function _log( $message ) {
		if ( is_array( $message ) || is_object( $message ) ) {
			error_log('[SVEA PAYMENTS] ' . var_export( $message, true ) );
		} else {
			error_log('[SVEA PAYMENTS] ' . $message );
		}
	}
}

/**
 * Class WC_Maksuturva.
 *
 * Handles initialization of the Maksuturva Payment Gateway, adds filters and actions.
 *
 * @since   2.0.0
 */
class WC_Maksuturva {

	/**
	 * Plugin version, used for dependency checks.
	 *
	 * @since 2.0.0
	 *
	 * @var string VERSION The plugin version.
	 */
	const VERSION = '2.3.0';

	/**
	 * Plugin DB version.
	 *
	 * @since 2.0.3
	 *
	 * @var string DB_VERSION The plugin DB version.
	 */
	const DB_VERSION = '2.0.6';

	/**
	 * Plugin DB version option name.
	 *
	 * @since 2.0.3
	 *
	 * @var string OPTION_DB_VERSION The plugin DB version option name.
	 */
	const OPTION_DB_VERSION = 'wc-maksuturva-db-version';

	/**
	 * The working instance of the plugin, singleton.
	 *
	 * @since  2.0.0
	 *
	 * @var WC_Maksuturva $instance The plugin instance.
	 */
	private static $instance = null;

	/**
	 * Full path to the plugin directory.
	 *
	 * @since  2.0.0
	 *
	 * @var string $plugin_dir The directory path to the plugin.
	 */
	protected $plugin_dir = '';

	/**
	 * Plugin URL.
	 *
	 * @since  2.0.0
	 *
	 * @var string $plugin_url The URL to the plugin.
	 */
	protected $plugin_url = '';

	/**
	 * Plugin base name.
	 *
	 * @since  2.0.0
	 *
	 * @var string $plugin_name The name of the plugin.
	 */
	protected $plugin_name = '';

	/**
	 * The text domain to use for translations.
	 *
	 * @since 2.0.0
	 *
	 * @var string $td The text domain.
	 */
	public $td = 'wc-maksuturva';

	/**
	 * Get the plugin instance.
	 *
	 * Gets the singleton of the plugin.
	 *
	 * @since  2.0.0
	 *
	 * @return WC_Maksuturva The plugin instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new WC_Maksuturva();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * Plugin uses Singleton pattern, hence the constructor is private.
	 *
	 * @since  2.0.0
	 *
	 * @return WC_Maksuturva The plugin instance.
	 */
	private function __construct() {
		$this->plugin_dir  = untrailingslashit( plugin_dir_path( __FILE__ ) );
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_name = plugin_basename( __FILE__ );

		register_activation_hook( $this->plugin_name, array( $this, 'activate' ) );
		register_deactivation_hook( $this->plugin_name, array( $this, 'deactivate' ) );
		// The uninstall hook callback needs to be a static class method or function.
		register_uninstall_hook( $this->plugin_name, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Initializes the plugin.
	 *
	 * Load the plugin text domain, adds actions and filters to be used.
	 *
	 * @since 2.0.2 Add cron schedules, and action to check pending payments.
	 * @since 2.0.0
	 */
	public function init()
	{
		try {
			$this->update_db_check();

			load_plugin_textdomain('wc-maksuturva', false, basename(dirname(__FILE__)) . '/languages');

			add_filter('woocommerce_payment_gateways', array($this, 'add_maksuturva_gateway'));
			add_filter('plugin_action_links_' . $this->plugin_name, array(__CLASS__, 'maksuturva_action_links'));
			add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
			add_filter('cron_schedules', array($this, 'register_cron_schedules'));
			
			// woocommerce changed hook for the wc_clear_cart_after_payment function
			// https://github.com/woocommerce/woocommerce/commit/1be5e81860df97ea0d2efb9aed919480de7ac288
			remove_filter('template_redirect', 'wc_clear_cart_after_payment', 20);
			// leaving the old removal, in case they revert
			remove_filter('get_header', 'wc_clear_cart_after_payment');

			if (!wp_next_scheduled('maksuturva_check_pending_payments')) {
				_log("Adding new payment status event loop.");
				wp_schedule_event(time(), 'five_minutes', 'maksuturva_check_pending_payments');
			}

			add_action( 'maksuturva_check_pending_payments', [$this, 'check_pending_payments'] );
			add_action( 'woocommerce_cart_calculate_fees', [$this, 'set_handling_cost'] );

			add_filter( 'woocommerce_get_price_html', [$this, 'svea_add_part_payment_widget'], 99, 2 );
		} catch (Exception $e) { 
			_log("Error in Svea Payments module inititalization: " . $e->getMessage());
		}
	}

	/**
	 * Svea Part Payment injection next to the price
	 */ 
	public function svea_add_part_payment_widget( $price, $product ) {
		$this->load_class( 'WC_Gateway_Maksuturva' );
		$gateway = new WC_Gateway_Maksuturva();

		if ($gateway->get_option('partpayment_widget')==="yes") {
			$widgetSellerId = $gateway->get_option( 'maksuturva_sellerid' );

			if (is_product() && isset($price) && isset($product) && !empty($product->get_price())) {
				$floatPrice = floatval(wc_get_price_including_tax( $product ));

				if ($floatPrice && $floatPrice>=50.00) {
					$widgetHtml = "<script src=\"https://payments.maksuturva.fi/tools/partpayment/partPayment.js\" class=\"svea-pp-widget-part-payment\""
						// . " data-maksuturva-host=\"https://test1.maksuturva.fi\"" 
						. " data-sellerid=\"" . esc_html($widgetSellerId) . "\"" 
						. " data-locale=\"" . explode( '_', get_user_locale() )[0] . "\""
						. " data-price=\"" . floatval(wc_get_price_including_tax( $product )) . "\"";

					if(!empty($gateway->get_option('maksuturva_ppw_campaign_text_fi'))) {
						$widgetHtml = $widgetHtml . " data-campaign-text-fi=\"" . esc_html($gateway->get_option('maksuturva_ppw_campaign_text_fi')) . "\"";
					}
					if(!empty($gateway->get_option('maksuturva_ppw_campaign_text_sv'))) {
						$widgetHtml = $widgetHtml . " data-campaign-text-sv=\"" . esc_html($gateway->get_option('maksuturva_ppw_campaign_text_sv')) . "\"";
					}
					if(!empty($gateway->get_option('maksuturva_ppw_campaign_text_en'))) {
						$widgetHtml = $widgetHtml . " data-campaign-text-en=\"" . esc_html($gateway->get_option('maksuturva_ppw_campaign_text_en')) . "\"";
					}
					if(!empty($gateway->get_option('maksuturva_ppw_fallback_text_fi'))) {
						$widgetHtml = $widgetHtml . " data-fallback-text-fi=\"" . esc_html($gateway->get_option('maksuturva_ppw_fallback_text_fi')) . "\"";
					}
					if(!empty($gateway->get_option('maksuturva_ppw_fallback_text_sv'))) {
						$widgetHtml = $widgetHtml . " data-fallback-text-sv=\"" . esc_html($gateway->get_option('maksuturva_ppw_fallback_text_sv')) . "\"";
					}
					if(!empty($gateway->get_option('maksuturva_ppw_fallback_text_en'))) {
						$widgetHtml = $widgetHtml . " data-fallback-text-en=\"" . esc_html($gateway->get_option('maksuturva_ppw_fallback_text_en')) . "\"";
					}
					if(!empty($gateway->get_option('maksuturva_ppw_border_color'))) {
						$widgetHtml = $widgetHtml . " data-border-color=\"" . esc_html($gateway->get_option('maksuturva_ppw_border_color')) . "\"";
					}
					if(!empty($gateway->get_option('maksuturva_ppw_text_color'))) {
						$widgetHtml = $widgetHtml . " data-text-color=\"" . esc_html($gateway->get_option('maksuturva_ppw_text_color')) . "\"";
					}
					if(!empty($gateway->get_option('maksuturva_ppw_highlight_color'))) {
						$widgetHtml = $widgetHtml . " data-highlight-color=\"" . esc_html($gateway->get_option('maksuturva_ppw_highlight_color')) . "\"";
					}
					if(!empty($gateway->get_option('maksuturva_ppw_active_color'))) {
						$widgetHtml = $widgetHtml . " data-active-color=\"" . esc_html($gateway->get_option('maksuturva_ppw_active_color')) . "\"";
					}
					if(!empty($gateway->get_option('maksuturva_ppw_border_radius'))) {
						$widgetHtml = $widgetHtml . " data-border-radius=\"" . esc_html($gateway->get_option('maksuturva_ppw_border_radius')) . "\"";
					}
					if(!empty($gateway->get_option('maksuturva_ppw_price_thresholds')) && validate_price_threshold($gateway->get_option('maksuturva_ppw_price_thresholds')) ) {

						$widgetHtml = $widgetHtml . " data-threshold-prices=\"[" . esc_html($gateway->get_option('maksuturva_ppw_price_thresholds')) . "]\"";
					}
					$widgetHtml = $widgetHtml . "></script>";
						/*
						. " data-locale=\"fi\" data-campaign-text-fi=\"Campaign text FI\" data-campaign-text-sv=\"Campaign text SV\""
						. " data-campaign-text-en=\"Campaign text EN\" data-fallback-text-fi=\"Fallback text suomeksi\""
						. " data-fallback-text-sv=\"Fallback text på svenska\" data-fallback-text-en=\"Fallback text In english\""
						. " data-threshold-prices=\"[[600, 6], [400, 12], [100, 24], [1000, 13]]\"></script>";
						*/
					$priceHtml = $price . "<br />" . $widgetHtml;
					return $priceHtml;
				}
			}
		}
		// otherwise, return the original html price content
		return $price;
	}

	/**
	 * Check price thresholds configuation value
	 *
	 *
	 * @since 2.3.0
	 */
	private function validate_price_threshold( $value ) 
	{
		if (substr_count( $value, "[") != substr_count( $value, "]") ) {
			return false;
		}
		return true;
	}

	/**
	 * Add meta box to order page.
	 *
	 * Adds the Svea Payments order detail meta box to the order page.
	 *
	 * @since 2.0.0
	 */
	public function add_meta_boxes() {
		$this->load_class( 'WC_Meta_Box_Maksuturva' );
		$this->load_class( 'WC_Gateway_Maksuturva' );
		add_meta_box( 'maksuturva-order-details', __( 'Maksuturva order details', 'wc-maksuturva' ),
		'WC_Meta_Box_Maksuturva::output', 'shop_order', 'side', 'high', array( 'gateway' => new WC_Gateway_Maksuturva() ) );
	}

	/**
	 * Register new cron schedules.
	 *
	 * Register new cron schedules used by this module.
	 *
	 * @param array $schedules The schedules.
	 *
	 * @since 2.0.2
	 *
	 * @return mixed
	 */
	public function register_cron_schedules( $schedules ) {
		$schedules['five_minutes'] = array(
			'interval' => 5 * 60,
			'display'  => __( 'Once every 5 minutes', $this->td )
		);

		return $schedules;
	}

	/**
	 * Checks pending payments in the background.
	 *
	 * Cron action for checking the status of pending payments from Maksuturva API.
	 *
	 * @since 2.0.2
	 */
	public function check_pending_payments() {
		$this->load_class( 'WC_Gateway_Maksuturva' );
		$this->load_class( 'WC_Payment_Checker_Maksuturva' );
	
		$payments = WC_Payment_Maksuturva::findPending();
		if ( ! empty( $payments ) ) {
			( new WC_Payment_Checker_Maksuturva() )->check_payments( $payments );
		} 
	}

	/**
	 * Add a gateway.
	 *
	 * Adds the Maksuturva gateway.
	 *
	 * @param array $methods The existing payment gateways.
	 *
	 * @return array The list of payment gateways.
	 */
	public function add_maksuturva_gateway( $methods ) {

		$this->load_class( 'WC_Gateway_Maksuturva' );
		$methods[] = WC_Gateway_Maksuturva::class;

		$main_settings = get_option( 'woocommerce_' . WC_Gateway_Maksuturva::class . '_settings' );
		if ( isset( $main_settings['outbound_payment'] ) && $main_settings['outbound_payment'] == 'yes' ) {
			return $methods;
		}

		$this->load_class( 'WC_Gateway_Svea_Invoice_And_Hire_Purchase' );
		$methods[] = WC_Gateway_Svea_Invoice_And_Hire_Purchase::class;

		$this->load_class( 'WC_Gateway_Svea_Credit_Card_And_Mobile' );
		$methods[] = WC_Gateway_Svea_Credit_Card_And_Mobile::class;

		$this->load_class( 'WC_Gateway_Svea_Online_Bank_Payments' );
		$methods[] = WC_Gateway_Svea_Online_Bank_Payments::class;

		$this->load_class( 'WC_Gateway_Svea_Other_Payments' );
		$methods[] = WC_Gateway_Svea_Other_Payments::class;

		$this->load_class( 'WC_Gateway_Svea_Estonia_Payments' );
		$methods[] = WC_Gateway_Svea_Estonia_Payments::class;

		return $methods;
	}

	/**
	 * Add action links.
	 *
	 * Adds the maksuturva action links.
	 *
	 * @param array $links The existing links.
	 *
	 * @return array The links.
	 */
	public static function maksuturva_action_links( $links ) {
		$url     = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_maksuturva' );
		$action_links = array(
			'settings' => '<a href="' . esc_attr( $url ) . '">' . esc_html__( 'Settings' ) . '</a>',
		);

		return array_merge($action_links, $links);
	}

	/**
	 * Check if shop currency is supported.
	 *
	 * Checks if the shop supports EUR, which is the only supported currency by Maksuturva.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_currency_supported() {
		$currency = get_woocommerce_currency();

		return in_array( $currency, array( 'EUR' ), true );
	}

	/**
	 * Renders a template file.
	 *
	 * The file is expected to be located in the plugin "templates" directory. If the domain is given,
	 * the template file is expected to be located in the "templates/<domain>" directory.
	 *
	 * @since  2.0.0
	 *
	 * @param string $template The name of the template.
	 * @param string $domain   The "domain" the template belongs to. Subdirectory under /templates/.
	 * @param array  $data     The data to pass to the template file.
	 */
	public function render( $template, $domain, array $data = array() ) {
		if ( is_array( $data ) ) {
			// Use variable variables here.
			foreach ( $data as $key => $value ) {
				${$key} = $value;
			}
		}
		$file = $template . '.php';

		$path = $this->plugin_dir . '/templates/' . (!empty($domain) ? $domain . '/' : '') . $file;

		if ( file_exists( $path ) ) {
			require_once( $path );
		}

		/**
		 * Fetch styles & scripts from separate file
		 */
		$additional_file_path = $this->plugin_dir . '/templates/' . (!empty($domain) ? $domain . '/' : '') . 'payment-method-form-additional.php';
		if ( file_exists( $additional_file_path ) ) {
			require_once( $additional_file_path );
		}
	}

	/**
	 * Load class file based on class name.
	 *
	 * The file are expected to be located in the plugin "includes" directory.
	 *
	 * @since 2.0.0
	 *
	 * @param string $class_name The name of the class to load.
	 */
	protected function load_class( $class_name = '' ) {
		$file = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
		if ( file_exists( $this->plugin_dir . '/includes/' . $file ) ) {
			require_once( $this->plugin_dir . '/includes/' . $file );
		}
	}

	/**
	 * Getter for the plugin base name.
	 *
	 * Gets the plugin name.
	 *
	 * @since 2.0.0
	 *
	 * @return string The name.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Getter for the plugin directory.
	 *
	 * Gets the full path to the plugin directory.
	 *
	 * @since 2.0.0
	 *
	 * @return string The plugin directory.
	 */
	public function get_plugin_dir() {
		return $this->plugin_dir;
	}

	/**
	 * Get plugin URL.
	 *
	 * Returns the URL to the plugin.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_plugin_url() {
		return $this->plugin_url;
	}

	/**
	 * Activate plugin.
	 *
	 * Creates necessary tables for the plugin.
	 *
	 * @since 2.0.0
	 */
	public function activate() {
		$this->install_db();
	}

	/**
	 * Deactivate hook.
	 *
	 * Hook to run when deactivating the plugin.
	 *
	 * @since 2.0.0
	 */
	public function deactivate() {
		// Nothing to do.
	}

	/**
	 * Uninstalls the plugin.
	 *
	 * Removes plugin related database tables.
	 *
	 * @since 2.0.0
	 */
	public static function uninstall() {
		// We don't want to remove the database table, as all the history data will be erased.
	}

	/**
	 * Sets option.
	 *
	 * Adds or updates a new option to the WP options table.
	 *
	 * @param string $key   The option key.
	 * @param mixed  $value The option value.
	 *
	 * @since 2.0.3
	 */
	public function set_option( $key, $value ) {
		( get_option( $key ) === false ) ? add_option( $key, $value ) : update_option( $key, $value );
	}

	/**
	 * Checks for DB updates.
	 *
	 * Checks if the db needs to be updated.
	 *
	 * @since 2.0.3
	 */
	private function update_db_check() {
		if ( get_option( self::OPTION_DB_VERSION ) !== self::DB_VERSION ) {
			$this->install_db();
		}
	}

	/**
	 * Installs DB updates.
	 *
	 * Installs new db updates.
	 *
	 * @since 2.0.3
	 */
	private function install_db() {
		// See: https://codex.wordpress.org/Creating_Tables_with_Plugins.
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$this->load_class( 'WC_Payment_Maksuturva' );
		$this->load_class( 'WC_Payment_Checker_Maksuturva' );

		WC_Payment_Maksuturva::install_db();
		WC_Payment_Checker_Maksuturva::install_db();

		$this->set_option( self::OPTION_DB_VERSION, self::DB_VERSION );
	}

	/**
	 * Sets the payment method handling cost in checkout page
	 *
	 * @param WC_Cart $cart The cart.
	 *
	 * @since 2.1.3
	 */
	public function set_handling_cost( WC_Cart $cart ) {

		$this->load_class( 'WC_Gateway_Maksuturva' );
		$gateway = new WC_Gateway_Maksuturva();

		$this->load_class( 'WC_Payment_Handling_Costs' );
		$handling_costs_handler = new WC_Payment_Handling_Costs( $gateway );
		$handling_costs_handler->set_handling_cost( $cart );
	}
}

add_action( 'init', array( WC_Maksuturva::get_instance(), 'init' ) );
