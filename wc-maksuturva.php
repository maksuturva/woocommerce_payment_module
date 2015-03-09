<?php
/**
 * Plugin Name:  WooCommerce Maksuturva Payment Gateway
 * Plugin URI:   https://github.com/maksuturva/woocommerce_payment_module
 * Text Domain:  wc-maksuturva
 * Domain Path:  /languages/
 * Description:  A plugin for Maksuturva, which provides intelligent online payment services consisting of the most comprehensive set of high quality service features in the Finnish market
 * Version:      1.0
 * Author:       Maksuturva Group Oy
 * Requires at least: 3.8
 * Tested up to: 4.1
 * Author URI:   http://www.maksuturva.fi
 * License:      LGPL2.1
 */

/**
 * Maksuturva Payment Gateway Plugin for WooCommerce 2.x
 * Plugin developed for Maksuturva
 * Last update: 06/03/2015
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
    exit; // Exit if accessed directly
}

if(!function_exists('_log')){
  function _log( $message ) {
    if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log( print_r( $message, true ) );
      } else {
        error_log( $message );
      }
    }
  }
}

// Check for woocommerce
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
 // Hooks for adding/ removing the database table, and the wpcron to check them
  register_activation_hook( __FILE__, 'create_background_checks' );
  register_deactivation_hook( __FILE__, 'remove_background_checks' );
  register_uninstall_hook( __FILE__, 'on_uninstall' );
  
  // cron interval for ever 10 seconds for testing
  add_filter('cron_schedules','register_fivemin');
  function register_fivemin($schedules){
    _log('register_fivemin');
    $schedules['fivemin'] = array(
        'interval'=> 5 * 60,
        'display'=>  __('Once every 5 minutes', 'wc-maksuturva')
    );

    return $schedules;
  }
  
  function wi_create_status_check_schedule(){
    //Use wp_next_scheduled to check if the event is already scheduled
    $timestamp = wp_next_scheduled( 'maksuturva_background_check' );
    
    //If $timestamp == false schedule daily backups since it hasn't been done previously
    if( $timestamp == false ){
      //Schedule the event for right now, then to repeat daily using the hook 'wi_create_daily_backup'
      
  
      wp_schedule_event( time(), 'fivemin', 'maksuturva_background_check' );
    }
  }

  function do_background_checks() {
    //_log('scheduled do_background_checks');
    
    $gw = new WC_Gateway_Maksuturva();
    if ( $gw->sandbox != 'yes' ) {
      $gw->do_background_checks();
    }  
  }

  add_action( 'maksuturva_background_check',  'do_background_checks');

  /**
    * Activation, create processing order table, and table version option
    * @return void
    */
  function create_background_checks()
  {
    //Get the table name with the WP database prefix
    global $wpdb;
    $db_version = "1.0";
    $table_name = $wpdb->prefix . "maksuturva_queue";
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
      order_id mediumint(9) NOT NULL,
      payment_id varchar(36) NOT NULL,
      time timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
      PRIMARY KEY (order_id, payment_id)
    );";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    add_option('maksuturva_db_version', $db_version);
    wi_create_status_check_schedule();
  } 
  
  function remove_background_checks()
  {
    //$next_sheduled = wp_next_scheduled( 'maksuturvabgcheck' );
    //wp_unschedule_event($next_sheduled, 'maksuturvabgcheck');
    wp_clear_scheduled_hook('maksuturva_background_check');
  }


  /**
   * Clean up table and options on uninstall
   * @return [type] [description]
   */
  function on_uninstall()
  {
    // Clean up i.e. delete the table, wp_cron already removed on deacivate
    delete_option('maksuturva_db_version');
    global $wpdb;
    $table_name = $wpdb->prefix . "maksuturva_queue";
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
  } 

  add_action( 'init', 'maksuturva_load_plugin_textdomain' );    
  function maksuturva_load_plugin_textdomain() {
      $domain = 'wc-maksuturva';
      load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
  
      //$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
      //load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); //languages/
  }

  add_action('plugins_loaded', 'woocommerce_maksuturva_init', 0);
  function woocommerce_maksuturva_init() {
    if(!class_exists('WC_Payment_Gateway')) return;

    maksuturva_load_plugin_textdomain();
    // If we made it this far, then include our Gateway Class
    include_once ('includes/WC_Gateway_Maksuturva_Class.php');
    //$GLOBALS['WC_Gateway_Maksuturva_Class'] = new WC_Gateway_Maksuturva();
    
    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_maksuturva_gateway($methods) {
        $methods[] = 'WC_Gateway_Maksuturva';
        return $methods;
    }

    add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_maksuturva_gateway' );
    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woocommerce_maksuturva_action_links' );
    
  }

  // Add custom action links
  function woocommerce_maksuturva_action_links( $links ) {
      $plugin_links = array(
          '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_maksuturva' ) . '">' . __( 'Settings' ) . '</a>',
      );
   
      // Merge our new link with the default ones
      return array_merge( $plugin_links, $links );    
  }
}