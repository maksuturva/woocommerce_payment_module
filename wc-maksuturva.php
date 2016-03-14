<?php
/**
 * WooCommerce Maksuturva Payment Gateway
 *
 * @package     WooCommerce Maksuturva Payment Gateway
 *
 * @wordpress-plugin
 * Plugin Name: WooCommerce Maksuturva Payment Gateway
 * Plugin URI:   https://github.com/maksuturva/woocommerce_payment_module
 * Description: A plugin for Maksuturva, which provides intelligent online payment services consisting of the most
 * comprehensive set of high quality service features in the Finnish market
 * Version:     2.0.0
 * Author:      Maksuturva Group Oy
 * Author URI:  http://www.maksuturva.fi
 * Text Domain: wc-maksuturva
 * Domain Path: /languages/
 * Requires at least: 3.8
 * Tested up to: 4.1
 * License:      LGPL2.1
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
		if ( WP_DEBUG === true ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				error_log( var_export( $message, true ) );
			} else {
				error_log( $message );
			}
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
	const VERSION = '2.0.0';

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
	 * @since  2.0.0
	 */
	public function init() {
		load_plugin_textdomain( 'wc-maksuturva', false, basename( dirname( __FILE__ ) ) . '/languages');

		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_maksuturva_gateway' ) );
		add_filter( 'plugin_action_links_' . $this->plugin_name, array( __CLASS__, 'maksuturva_action_links' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
	}

	/**
	 * Add meta box to order page.
	 *
	 * Adds the Maksuturva order detail meta box to the order page.
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
		$methods[] = 'WC_Gateway_Maksuturva';

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

		if ( ! empty( $domain ) ) {
			$path = $this->plugin_dir . '/templates/' . $domain . '/' . $file;
		} else {
			$path = $this->plugin_dir . '/templates/' . $file;
		}

		if ( file_exists( $path ) ) {
			require_once( $path );
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
		global $wpdb;

		$this->load_class( 'WC_Payment_Maksuturva' );

		$table_name = $wpdb->prefix . WC_Payment_Maksuturva::TABLE_NAME;

		$sql = 'CREATE TABLE IF NOT EXISTS `' . $table_name . '` (
		`order_id` int(10) unsigned NOT NULL,
		`payment_id` varchar(36) NOT NULL,
		`status` varchar(36) NULL DEFAULT NULL,
		`data_sent` LONGBLOB NULL DEFAULT NULL,
		`data_received` LONGBLOB NULL DEFAULT NULL,
		`date_added` DATETIME NOT NULL,
		`date_updated`  DATETIME NULL DEFAULT NULL,
		PRIMARY KEY (order_id, payment_id)) DEFAULT CHARSET=utf8;';

		// See: https://codex.wordpress.org/Creating_Tables_with_Plugins.
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
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
}

add_action( 'init', array( WC_Maksuturva::get_instance(), 'init' ) );
