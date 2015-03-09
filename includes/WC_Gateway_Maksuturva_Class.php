<?php

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

$unfortunately = __('Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction.', 'wc-maksuturva');

class WC_Gateway_Maksuturva extends WC_Payment_Gateway { 
  
  static public function filter_gettext($translation, $text, $domain) {
    if ( $text == 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction.' ) {
      $translations = get_translations_for_domain( 'wc-maksuturva' );
      return $translations->translate($text);
    }
    return $translation;
  }
  /** 
   * Constructor 
   */ 
  public function __construct() { 
      //add_action('init', array($this, 'action_init'));

      require_once dirname(__FILE__) . '/MaksuturvaGatewayWCImpl.php';
      

      $this->id                 = 'WC_Gateway_Maksuturva'; 
      $this->td                 = 'wc-maksuturva';

      $this->method_title       = __( 'Maksuturva', $this->td ); 
      $this->method_description = __( 'Take payments via Maksuturva.', $this->td ); 
      
      $this->has_fields         = false; 
      $this->supports           = array( 
          'products'
          /*'refunds', */ 
      ); 
      //$this->view_transaction_url = 'https://www.simplify.com/commerce/app#/payment/%s'; 


      // Load the form fields 
      $this->init_form_fields(); 

      // Load the settings. 
      $this->init_settings(); 

      // Get setting values 
      $this->title       = $this->get_option( 'title' ); 
      $this->description = $this->get_option( 'description' ); 
      $this->enabled     = $this->get_option( 'enabled' ); 
      $this->sandbox     = $this->get_option( 'sandbox' ); 
      //$this->sandbox_testaccount     = $this->get_option( 'sandbox_testaccount' ); 
      
      $this->notify_url           = WC()->api_request_url( $this->id );

      $this->maksuturva_showicons     = $this->get_option( 'maksuturva_showicons' ); 
      $this->maksuturva_colrowcount = $this->get_option('maksuturva_colrowcount');
      
      $this->maksuturva_sellerid = $this->get_option('maksuturva_sellerid');
      $this->maksuturva_secretkey = $this->get_option('maksuturva_secretkey');
      $this->maksuturva_keyversion = $this->get_option('maksuturva_keyversion');
      $this->maksuturva_url = $this->get_option('maksuturva_url');

      if ( $this->sandbox == 'yes') {
        //if ($this->sandbox_testaccount == 'yes') {
        $this->maksuturva_sellerid = "testikauppias";
        $this->maksuturva_secretkey = "11223344556677889900";
        $this->maksuturva_keyversion =  "1";  
        //} 
        if ($this->maksuturva_url == '') {
          $this->maksuturva_url = 'http://test1.maksuturva.fi/';
        }
      } else {
        if ($this->maksuturva_url == '') {
          $this->maksuturva_url = 'https://www.maksuturva.fi/';
        }
      }

      $this->maksuturva_encoding = get_bloginfo('charset'); //'UTF-8'; //$this->settings['maksuturva_encoding'];
      $this->maksuturva_emaksut = $this->get_option('maksuturva_emaksut');
      $this->maksuturva_orderid_prefix = $this->get_option('maksuturva_orderid_prefix');

      $this->debug     = $this->get_option( 'debug' ); 

      $this->pending_order_check_min_age = 5*60; // 5 minutes
      $this->pending_order_giveup_min_age = 60 * 60 * 2; //2 hours

      if ( 'yes' == $this->debug ) {
        $this->log = new WC_Logger();
      }

      //Filters

      add_filter('gettext', array('WC_Gateway_Maksuturva', 'filter_gettext'), 20, 3);
      
      // Hooks 
      add_action( 'valid-maksuturva-request', array( $this, 'successful_maksuturva_cb_request' ) );
      add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
      
      // Save settings
      if ( is_admin() ) {
          // Versions over 2.0
          // Save our administration options. Since we are not going to be doing anything special
          // we have not defined 'process_admin_options' in this class so the method in the parent
          // class will be used instead
          add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
      }     
      //add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'mt_return_handler' ) );
      
      add_action( 'admin_notices', array( $this, 'checks' ) ); 
      //add_action( 'woocommerce_settings_saved', array( $this, 'checks' ) ); 
      
      // Payment listener/API hook
      add_action( 'woocommerce_api_'.strtolower(get_class($this)), array( &$this, 'check_maksuturva_response' ) );
      
      if ( ! $this->is_valid_for_use() ) {
         $this->enabled = false;
      }
      
      $this->do_override_wc_clear_cart_after_payment();

  } 

  function do_override_wc_clear_cart_after_payment() {
    if (WC()->cart && WC()->cart->get_cart_contents_count( ) > 0) {
      $this->override_wc_clear_cart_after_payment(true);  
    }
  }

  function mt_wc_clear_cart_after_payment( $methods ) {
    global $wp, $woocommerce;
    
    if ( ! empty( $wp->query_vars['order-received'] ) ) {

        $order_id = absint( $wp->query_vars['order-received'] );

        if ( isset( $_GET['key'] ) )
            $order_key = $_GET['key'];
        else
            $order_key = '';

        if ( $order_id > 0 ) {
            $order = wc_get_order( $order_id );

            if ( $order->order_key == $order_key ) {
              
              if ( ! $order->has_status( array( 'failed', 'pending' ) ) ) { // added condition for Maksuturva
            
                 WC()->cart->empty_cart();
              }
            }
        }

    }

    if ( WC()->session->order_awaiting_payment > 0 ) {

        $order = wc_get_order( WC()->session->order_awaiting_payment );

        if ( $order->id > 0 ) {
            // If the order has not failed, or is not pending, the order must have gone through
            if ( ! $order->has_status( array( 'failed', 'pending' ) ) ) { ///// <- add your custom status here....
                WC()->cart->empty_cart();
            }
         }
    }
  }

  function override_wc_clear_cart_after_payment($override) {
      if ($this->is_available()) {
        if($override==true) {
          remove_filter('get_header', 'wc_clear_cart_after_payment' );
          add_action('get_header', array($this,'mt_wc_clear_cart_after_payment' ));
        } else {
          remove_filter('get_header', array($this,'mt_wc_clear_cart_after_payment' ));
          add_action('get_header', 'wc_clear_cart_after_payment' );
        }
        
      }
  }

   /**
    * Check if this gateway is enabled and available in the user's country
    *
    * @access public
    * @return bool
    */
   function is_valid_for_use() {
       if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_maksuturva_supported_currencies', array( 'EUR' ) ) ) ) {
           return false;
       }

       return true;
   }

  /** 
   * Admin Panel Options 
   * - Options for bits like 'title' and availability on a country-by-country basis 
   * 
   * @access public 
   * @return void 
   */ 
   public function admin_options() {
      if ( $this->is_valid_for_use() ) {
          
          if ($this->sandbox != 'yes' && isset($_GET["show-pending"]) && $_GET["show-pending"]=='1') {
            echo $this->admin_page_top(false);
            echo $this->pending_payments();
            exit;
          } else {
            if ($this->sandbox != 'yes')
              echo $this->admin_page_top(true);
            parent::admin_options();  
          }
      } else {
          ?>
          <div class="inline error"><p><strong><?php _e( 'Gateway Disabled', $this->td ); ?></strong>: <?php _e( 'Maksuturva does not support your store currency.', $this->td ); ?></p></div>
          <?php
      }
   }

  /** 
   * Check if GW is enabled/configured and notify the user 
   */ 
  public function checks() { 
      
      if ( $this->enabled == 'no') { 
          return; 
      } 


      // Check required fields 
      if (  (! $this->maksuturva_sellerid || ! $this->maksuturva_secretkey )) { 
          echo '<div class="error"><p>' . __( 'Maksuturva Error: Please enter your Maksuturva seller id and secret key', $this->td ) . '</p></div>'; 
      } 
      

      // Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected 
      /*if ($this->sandbox=='no' && 'no' == get_option( 'woocommerce_force_ssl_checkout' ) && ! class_exists( 'WordPressHTTPS' ) ) { 
        echo '<div class="error"><p>' . sprintf( __( 'Maksuturva is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - you need to enable sandbox mode.', $this->td ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '</p></div>'; 
      } */
  } 

  /** 
   * Check if this gateway is enabled 
   */ 
  public function is_available() { 
      if ( 'yes' != $this->enabled ) { 
          return false; 
      } 

      /*if ( ! is_ssl() && 'yes' != $this->sandbox ) { 
          return false; 
      } */

      if ( ! $this->maksuturva_sellerid || ! $this->maksuturva_secretkey ) { 
          return false; 
      } 

      return true; 
  } 

  /** 
   * Initialise Gateway Settings Form Fields 
   */ 
  public function init_form_fields() { 
      $form = array();
      $form['enabled'] = array(
          'title' => __( 'Enable/Disable', $this->td ),
          'type' => 'checkbox',
          'label' => __( 'Enable Maksuturva Payment Gateway', $this->td ),
          'default' => 'yes'
        );
      $form['title'] = array(
          'title' => __( 'Title', $this->td ),
          'type' => 'text',
          'description' => __( 'This controls the title which the user sees during checkout.', $this->td ),
          'default' => __( 'Maksuturva', $this->td ),
          'desc_tip'      => true
        );
      $form['description'] = array(
          'title' => __( 'Customer Message', $this->td ),
          'type' => 'textarea',
          'default' => __('Pay via Maksuturva.', $this->td ),
          'desc_tip'    => true
        );
      $form['sandbox'] = array(
          'type' => 'checkbox',
          'title' => __('Sandbox mode', $this->td ),
          'default' => 'no',
          'description' => __('Maksuturva sandbox can be used to test payments. None of the payments will be real.', $this->td ),
          'options' => array( 'yes' => '1', 'no' => '0'),
      );
      /*$form['sandbox_testaccount'] = array(
          'type' => 'checkbox',
          'title' => __('Use test account', $this->td ),
          'default' => 'no',
          'description' => __('Maksuturva sandbox can be used with test accounts. Use only for debugging payment integration.', $this->td ),
          'options' => array( 'yes' => '1', 'no' => '0'),
      );*/
      $form['debug'] = array(
          'type' => 'checkbox',
          'title' => __('Debug Log', $this->td ),
          'default' => 'no',
          'description' => sprintf( __('Enable logging to <code>%s</code>', $this->td ), wc_get_log_file_path( $this->id ) ),
          'options' => array( 'yes' => '1', 'no' => '0'),
      );
      $form['maksuturva_sellerid'] = array(
          'type' => 'textfield',
          'title' => __('Seller id', $this->td ),
          'desc_tip'    => true,
          'description' => __('The seller identification provided by Maksuturva upon your registration.', $this->td ),
          'default' => get_option('maksuturva_sellerid'),
      );
      $form['maksuturva_secretkey'] = array(
          'type' => 'textfield',
          'title' => __('Secret Key', $this->td ),
          'desc_tip'    => true,
          'description' => __('Your unique secret key provided by Maksuturva.', $this->td ),
          'default' => get_option('maksuturva_secretkey'),
      );
      $form['maksuturva_keyversion'] = array(
          'type' => 'textfield',
          'title' => __('Secret Key Version', $this->td ),
          'desc_tip'    => true,
          'description' => __('The version of the secret key provided by Maksuturva.', $this->td ),
          'default' => get_option('maksuturva_keyversion', '001') ,
      );
      /* I don't think these are needed at the UI, but enabled it for now / JH */
      $form['maksuturva_url'] = array(
          'type' => 'textfield',
          'title' => __('Gateway URL', $this->td ),
          'desc_tip'    => true,
          'description' => __('The URL used to communicate with Maksuturva. Do not change this configuration unless you know what you are doing.', $this->td ),
          'default' => get_option('maksuturva_url', 'https://www.maksuturva.fi') ,
      );

      $form['maksuturva_orderid_prefix'] = array(
          'type' => 'textfield',
          'title' => __('Maksuturva Payment Prefix', $this->td ),
          'desc_tip'    => true,
          'description' => __('Prefix for order identifiers. Can be used to generate unique payment ids after e.g. reinstall.', $this->td ),
          'default' => get_option('maksuturva_orderid_prefix', '0') ,
      );
      
      /*
      $form['maksuturva_encoding'] = array(
          'type' => 'select',
          'title' => __('Communication encoding', $this->td ),
          'desc_tip'    => true,
          'description' => __('Maksuturva accepts both ISO-8859-1 and UTF-8 encodings to receive the transactions.', $this->td ),
          'options' => array(
              'UTF-8' => 'UTF-8',
              'ISO-8859-1' => 'ISO-8859-1',
          ),
          'default' => get_option('maksuturva_encoding'),
      );
      */

      $form['maksuturva_emaksut'] = array(
          'type' => 'checkbox',
          'title' => __('eMaksut', $this->td ),
          'description' => __('Use eMaksut payment service instead of Maksuturva', $this->td ),
          'options' => array( 'no' => '0', 'yes' => '1'),
          'desc_tip'    => true,
          'default' => get_option('maksuturva_emaksut'),
      );
      

      $this -> form_fields = $form;
  } 

  /** 
   * Payment form on checkout page -- none for Maksuturva
   */ 
  public function payment_fields() { 
      if($this -> description) echo wpautop(wptexturize($this -> description));
  } 

  /**
  * Get Maksuturva Args for passing to them
  *
  * @access public
  * @param mixed $order
  * @return array
  */
  function get_maksuturva_args( $order ) {
    global $woocommerce;
    $gateway = new MaksuturvaGatewayWCImpl($this, $order, $woocommerce->cart); //$this->cart_data['session_id'], $this->cart_data, $wpsc_cart->cart_items);
    return $gateway->getFieldArray();
  }


  /**
   * Generate the maksuturva button 
   *
   * @access public
   * @param mixed $order_id
   * @return string
   */
  public function generate_maksuturva_form( $order_id ) {
    $order = wc_get_order( $order_id );
    
    $maksuturva_args = $this->get_maksuturva_args( $order );
    $maksuturva_args_array = array();
    
    $this->insert_to_maksuturva_queue($order, $maksuturva_args['pmt_id']);

    if ( 'yes' == $this->debug ) {
          $this->log->add( $this->id, 'Generating payment form for order ' . $order->get_order_number() . '. Notify URL: ' . $this->notify_url );
    }

    foreach ( $maksuturva_args as $key => $value ) {
       $maksuturva_args_array[] = '<input type="hidden" name="'.esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
    }
    
    wc_enqueue_js( '
            $.blockUI({
                    message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to Maksuturva to make payment.', $this->td ) ) . '",
                    baseZ: 99999,
                    overlayCSS:
                    {
                        background: "#fff",
                        opacity: 0.6
                    },
                    css: {
                        padding:        "20px",
                        zindex:         "9999999",
                        textAlign:      "center",
                        color:          "#555",
                        border:         "3px solid #aaa",
                        backgroundColor:"#fff",
                        cursor:         "wait",
                        lineHeight:     "24px",
                    }
                });
            jQuery("#submit_maksuturva_payment_form").click();
        ' );

    return '<form action="' . MaksuturvaGatewayWCImpl::getPaymentUrl($this->maksuturva_url) . '" method="post" id="maksuturva_payment_form" target="_top">
          ' . implode( '', $maksuturva_args_array ) . '
          <!-- Button Fallback -->
          <div class="payment_buttons">
              <input type="submit" class="button alt" id="submit_maksuturva_payment_form" value="' . __( 'Maksuturva', $this->td ) . '" /> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', $this->td ) . '</a>
          </div>
          <script type="text/javascript">
              jQuery(".payment_buttons").hide();
          </script>
      </form>';
  }

  /** 
   * Process the payment 
   * @param integer $order_id 
   */ 
  public function process_payment( $order_id ) { 
      $maksuturva_adr = MaksuturvaGatewayWCImpl::getPaymentUrl( $this->maksuturva_url);
      $order       = wc_get_order( $order_id );
      $order->update_status('pending', __( 'Waiting for payment', $this->td ));
      //$order->reduce_order_stock();

      /*$maksuturva_args = $this->get_maksuturva_args( $order );
      $maksuturva_args = http_build_query( $maksuturva_args, '', '&' );
      */
      return array(
          'result'    => 'success',
          'redirect'  => add_query_arg('order-pay', $order->id, add_query_arg('key', $order->order_key, $order->get_checkout_order_received_url()  )) //get_checkout_order_received_url() , get_checkout_payment_url()
          //woocommerce_get_page_id( 'pay' ) //add_query_arg('order',
          //$order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
          //$maksuturva_adr . $maksuturva_args //add_query_arg('order-pay', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay')))) //$maksuturva_adr . $maksuturva_args
      );
  } 

  /** 
   *  Remove from queue 
   */ 
  function remove_from_maksuturva_queue($order_id, $payment_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'maksuturva_queue';
    if(is_null($payment_id)) {
      $payment_id = $order_id;
    }
    $wpdb->delete($table_name, array('order_id' => $order_id, 'payment_id' => $payment_id), array('%d', '%s'));
    if ( 'yes' == $this->debug ) 
      $this->log->add($this->id, "remove order_id=".$order_id.", payment_id=". $payment_id." from queue");
  }

  /** 
   *  Add into maksuturva queue
   */
  function insert_to_maksuturva_queue($order, $payment_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'maksuturva_queue';
    $order_id = $order->id;
    if(is_null($payment_id)) {
      $payment_id = $order_id;
    }
    
    
    $query = "SELECT order_id FROM $table_name WHERE `order_id` = $order_id AND `payment_id` = '$payment_id'";
    $existing_order = $wpdb->get_results( $query);
    if($existing_order) {
    
      if ( 'yes' == $this->debug ) 
        $this->log->add($this->id, "exists");
      return;
    }
    if ( $this->sandbox =='no' ) {
      if ( 'yes' == $this->debug ) 
        $this->log->add($this->id, "insert ".$order_id." to queue");

      $wpdb->insert($table_name, array('order_id' => $order_id, 'payment_id' => $payment_id), array('%d', '%s'));
    }
  }

  /**
   * Complete payment
   */
  function payment_complete($order, $reference) {
    global $woocommerce;
    $order -> payment_complete($reference);
    $woocommerce -> cart -> empty_cart();
    $msg = __("Payment confirmed by Maksuturva.", $this->td );
    if ( 'yes' == $this->debug ) 
      $this->log->add($this->id, $msg);
   }

   

   /**
    * Process valid request from Maksuturva
    *
    * @access private
    * @return void
    */
   function successful_maksuturva_cb_request($mt_response) {
      global $woocommerce;
      $this->override_wc_clear_cart_after_payment(false);

      if ($action = $mt_response['pmt_act']){
        $payment_id = $mt_response['pmt_id'];

        if(strlen($this->maksuturva_orderid_prefix) && strpos($payment_id, $this->maksuturva_orderid_prefix)==0) {
          $payment_id = substr($payment_id, strlen($this->maksuturva_orderid_prefix));
        }
        $order_id = MaksuturvaGatewayWCImpl::getOrderId(intval($payment_id));
        $order       = wc_get_order( $order_id );

        $msg_template = "<html><head><title>" . __('Message from Maksuturva return', $this->td ) .
          "</title></head><body><h1>%s</h1><p>%s</p></body></html>";
        
        $msg_cart_link = "<a href='" . $woocommerce->cart->get_cart_url() . "'>" . __('Go back to checkout', $this->td ) . "</a>";
        
        if (!$order->needs_payment()) {
          if ( 'yes' == $this->debug )
            $this->log->add($this->id, "User already paid, check the order status");
          //$this->go_to_transaction_results($order_id);

          wp_redirect( $this->get_return_url( $order ) );
          return;
        }

        switch($action){
          case 'ok':
            $values = array();
            $gateway = new MaksuturvaGatewayWCImpl($this, $order);
            foreach ($gateway->compulsoryReturnData as $field) {
              if (isset($mt_response[$field]) ) {
                  $values[$field] = $mt_response[$field];
                } else {
                  $msg = __("Maksuturva returned missing information.", $this->td );
                  if ( 'yes' == $this->debug ) 
                    $this->log->add($this->id, $msg);
                  printf($msg_template, $msg, $msg_cart_link);
                  wp_redirect( $woocommerce->cart->get_checkout_url() );
                  return;
                }
            }
          // calculate the hash for order
            $calculatedHash = $gateway->generateReturnHash($values);
            // test the hash
            if (!($calculatedHash == $values['pmt_hash'])) {
              $msg = __("Maksuturva returned wrong information / hash code.", $this->td );
              if ( 'yes' == $this->debug ) 
                $this->log->add($this->id, $msg);
              printf($msg_template, $msg, $msg_cart_link);
              wp_redirect( $woocommerce->cart->get_checkout_url() );
              return;
            }     
      
            // Then we have a confirmed payment
            $this->payment_complete($order, $values['pmt_id']);
            $this->remove_from_maksuturva_queue($order->id, $values["pmt_id"]);
            wp_redirect( $this->get_return_url( $order ) );
            break;
          case 'error':
          case 'cancel':
          case 'delay':
            if ($action == 'error')
              $msg = __("Error from Maksuturva received.", $this->td );
            else if($action == 'cancel')
              $msg = __("Cancellation from Maksuturva received.", $this->td );
            else if($action == 'delay')
              $msg = __("Payment delayed by Maksuturva.", $this->td );
            
            if ( 'yes' == $this->debug ) {
              $this->log->add($this->id, $msg);
            }
            
            if ( $this->sandbox == 'no' ) {
              $status = $this->status_request($order);
              
              if ($status['status']==false) {
                wc_add_notice($msg, 'error');
              } else {
                $orders = $this->get_pending_orders($this->pending_order_check_min_age, $order->id);
                $res = $this->process_status_query_result(
                  $order, 
                  $status['response'], 
                  reset($orders));
                $order_status = $order->get_status();
                
                if ($order->needs_payment() && ($order_status != 'cancelled' || $order_status != 'failed')) {
                  if (isset($status['response']) && isset($status['response']["pmtq_returntext"]))
                    $msg .= ' ' . $status['response']["pmtq_returntext"];
                  $order->cancel_order($msg);  
                }
                if($order->needs_payment() || $order->has_status("cancelled")) {
                  wc_add_notice($msg, 'notice');
                } 
              }
            } else {
              if ($action == 'cancel' || $action == 'delay') {
                $comment = __('Payment cancelled by sandbox customer.', $this->td );
                $order->cancel_order($comment); // error return cancels order
                wc_add_notice($msg, 'notice');
              } else {
                $order -> update_status('failed', $msg);
                wc_add_notice($msg, 'error');
              }
            }

            if (version_compare(WOOCOMMERCE_VERSION, '2.1', '<')) {
              $redirect_url =  add_query_arg( 'order',
                      $order->id, 
                      add_query_arg('key', $order->order_key, 
                      get_permalink(get_option('woocommerce_thanks_page_id'))));
            } else {
              $redirect_url =  add_query_arg('key', $order->order_key, $this->get_return_url( $order ) );
            }
            if ($order->has_status('cancelled')) {
              $redirect_url =  add_query_arg('key', $order->order_key, $order->get_cancel_order_url());
            }
            $this->web_redirect( $redirect_url);

            break;
        }
        //printf($msg_template, $msg, $msg_cart_link);
        //wp_redirect( woocommerce_get_page_id( 'pay' ) );
        exit;
      }
   }

   public function web_redirect($url){
      
      echo "<html><head><script language=\"javascript\">
           <!--
           window.location=\"{$url}\";
           //-->
           </script>
           </head><body><noscript><meta http-equiv=\"refresh\" content=\"0;url={$url}\"></noscript></body></html>";
    }

   /**
   * Check PayPal IPN validity
   **/
   public function check_maksuturva_request_is_valid( $mt_response ) {
      global $woocommerce;
      $action = $mt_response['pmt_act'];
      $payment_id = $mt_response['pmt_id'];
      if(strlen($this->maksuturva_orderid_prefix) && strpos($payment_id, $this->maksuturva_orderid_prefix)==0) {
        $payment_id = substr($payment_id, strlen($this->maksuturva_orderid_prefix));
      }
      $order_id = MaksuturvaGatewayWCImpl::getOrderId(intval($payment_id));
      $order = wc_get_order( $order_id );

      $is_valid_response = false;
      if ($action && $order_id && $payment_id && $order){
        $is_valid_response = true;
      }
      return $is_valid_response;
   }

  /*function showMessage($content){
      return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
  }*/
  
  /**
   * Check for Maksuturva Response
   *
   * @access public
   * @return void
   */
   public function check_maksuturva_response() {

      @ob_clean();
      $mt_response = ! empty( $_GET ) ? $_GET : false;
      //add_action('the_content', array(&$this, 'showMessage'));
      if ( $mt_response && $this->check_maksuturva_request_is_valid( $mt_response ) ) {

          header( 'HTTP/1.1 200 OK' );

          do_action( "valid-maksuturva-request", $mt_response );
          wp_die( "Maksuturva Request Success", "Maksuturva", array( 'response' => 200 ) );

      } else {

          wp_die( "Maksuturva Request Failure", "Maksuturva", array( 'response' => 200 ) );

      }
  }


  /**
   * Output for the order received page.
   *
   * @access public
   * @return void
   */
  public function receipt_page( $order ) {
      echo '<p>' . __( 'Thank you - your order is now pending payment. You should be automatically redirected to Maksuturva to make payment.', $this->td ) . '</p>';
      echo $this->generate_maksuturva_form( $order );
  }

  /** 
   * get_icon function. 
   * 
   * @access public 
   * @return string 
   */ 
  public function get_icon() { 
      $sellerid = 'sellerid=' . $this->maksuturva_sellerid;
      $btype = '&bannertype=logo';
      
      $dynamic_icon = '<img src="' . $this->maksuturva_url . '/Banner.pmt?'. $sellerid . $btype . '" alt="Maksuturva" />';
      /*$icon  = '<img src="' . WC_HTTPS::force_https_url(  WC()->plugin_url() . '/assets/images/icons/credit-cards/visa.png' ) . '" alt="Visa" />'; 
      $icon .= '<img src="' . WC_HTTPS::force_https_url(  WC()->plugin_url() . '/assets/images/icons/credit-cards/mastercard.png' ) . '" alt="Mastercard" />'; 
      $icon .= '<img src="' . WC_HTTPS::force_https_url(  WC()->plugin_url() . '/assets/images/icons/credit-cards/discover.png' ) . '" alt="Discover" />'; 
      $icon .= '<img src="' . WC_HTTPS::force_https_url(  WC()->plugin_url() . '/assets/images/icons/credit-cards/amex.png' ) . '" alt="Amex" />'; 
      $icon .= '<img src="' . WC_HTTPS::force_https_url(  WC()->plugin_url() . '/assets/images/icons/credit-cards/jcb.png' ) . '" alt="JCB" />'; 
      */
      return apply_filters( 'woocommerce_gateway_icon', $dynamic_icon, $this->id ); 
  }  

  /**
    *   Process status query result
    */ 
   function process_status_query_result($order, $response, $pending_order) {
      $statuses = array();
      $id = $order->id;
      $comment = '';
      
      // new status query result check
      /*
        * tilauksille, joiden status on ”odottaa maksua”, ”peruttu” tai ”epäonnistui” ajetaan status query kahden tunnin ajan esim. 5 minuutin välein
        * jos tilaus saa vastauskoodin >= 20, tilauksen status muutetaan ”käsittelyssä” eikä tilaukselle enää toisteta status querya
        * Jos vastauskoodi on < 20, mutta tilaus on alle 2 tuntia vanha, niin sen statusta ei muuteta ja status queryn toistetaan taas 5 minuutin päästä
        * Jos vastauskoodi on <20 ja tilaus on >= 2 h vanha, niin ”odottaa maksua” –tilassa olevan tilauksen tila muutetaan ”perutuksi”, ”Perutun” ja ”epäonnistuneen” tilauksen statusta ei muuteta. Tilaukselle ei enää toisteta status querya.
      */
      $status_code = null;
      if (isset($response["pmtq_returncode"])) {
        $status_code = intval($response["pmtq_returncode"]);
      }
      //$orders = $this->get_pending_orders($this->pending_order_check_min_age, $order->id);
      
      if ($pending_order == null) {
        $orders = $this->get_pending_orders($this->pending_order_check_min_age, $order->id);
        $pending_order = reset($orders);
      }
      
      if ( 'yes' == $this->debug ) {
        $this->log->add($this->id, "      query_status status=".$status_code);
      }

      if (!is_null($status_code) && $pending_order) {
          
        if ($status_code >= 20) {
          $shipping_cost = $order->get_total_shipping() + $order->get_shipping_tax( ); //- $order->get_shipping_tax( );
          $orig_amount = str_replace('.', ',', sprintf("%.2f",  $order->get_total() - $shipping_cost ));
          $orig_shipping = str_replace('.', ',', sprintf("%.2f", $shipping_cost));

          if(isset($response["pmtq_amount"]) && $response["pmtq_amount"] == $orig_amount && (!isset($response["pmtq_sellercosts"]) || $response["pmtq_sellercosts"] == $orig_shipping)) {
              if ( 'yes' == $this->debug ) 
                $this->log->add($this->id, "    amounts match");
              $comment = __('Payment confirmed by Maksuturva/eMaksut.', $this->td );
              if (isset($response["pmtq_returntext"])) {
                $comment .= ' (' . $response["pmtq_returntext"] . ")";  
              }
              $res = array('id' => $id, 'status'=>$status_code, 'message' => $comment );
              $statuses = $res;
              $order->add_order_note( $comment );
              $order->payment_complete($response["pmtq_id"]);
              $this->remove_from_maksuturva_queue($order->id, $response["pmtq_id"]);
          } else {
              if ( 'yes' == $this->debug )  {
                if (isset($response["pmtq_amount"]))
                  $this->log->add($this->id, "    amounts do not match ". $response["pmtq_amount"] ."<>". $orig_amount);
                if(isset($response["pmtq_sellercosts"]))
                  $this->log->add($this->id,"    seller costs do not match ". $response["pmtq_sellercosts"] ."<>". $orig_shipping);
              }
              $comment = __('Original prices do not match with response.', $this->td );
              $statuses = array('id' => $id, 'status'=>$status_code, 'message' => $comment );
          }
        } elseif ($status_code < 20 && $pending_order->age >= $this->pending_order_giveup_min_age ) {
          
          $comment = __('Payment cancelled by customer.', $this->td );
          if (isset($response["pmtq_returntext"])) {
            $comment .= ' (' . $response["pmtq_returntext"] . ")";  
          }
          $res = array('id' => $id, 'status'=>$status_code, 'message' => $comment );
          $statuses = $res;
          $msg = "status_code < 20 and pending_order[age] ".$pending_order->age.">=".$this->pending_order_giveup_min_age;
        
          $order_status = $order->get_status();
          if ( 'yes' == $this->debug ) 
            $this->log->add($this->id, $msg );
          if ($order_status != 'cancelled' && $order_status != 'failed') {
            $order->cancel_order( $comment );  
          }
          $this->remove_from_maksuturva_queue($order->id, $response["pmtq_id"]);

        } else {
          $comment = "status_code < 20 and pending_order[age] ".$pending_order->age."<".$this->pending_order_giveup_min_age;
          if ( 'yes' == $this->debug ) 
            $this->log->add($this->id, $comment );

          if (isset($response["pmtq_returntext"])) {
            $comment = $response["pmtq_returntext"];
          }

          $statuses = array('id' => $id, 'status'=>$status_code, 'message' => $comment );
        }
      } else {
        $comment = __('Waiting for payment confirmation by Maksuturva/eMaksut.', $this->td );
        if (isset($response["pmtq_returntext"])) {
          $comment .= ' (' . $response["pmtq_returntext"] . ")";  
        }
        $statuses = array('id' => $id, 'status'=>$status_code, 'message' => $comment );  
      }
      
      if ( 'yes' == $this->debug )
        if (isset($response["pmtq_returntext"] )) {
          $comment .= ' (' . $response["pmtq_returntext"] . ")"; 
        } 
        $this->log->add($this->id, "    order: #". $id . " response: ".$comment);

      return $statuses;
   }

  
  function status_request($order){
    if ( 'yes' == $this->debug ) 
      $this->log->add($this->id, "  status_request");
    $gateway = new MaksuturvaGatewayWCImpl($this, $order);

    $result = array();
    try {
      $res = $gateway->statusQuery($this);
      $result = array('status' => 'true', 'response' => $res); 
    } catch (MaksuturvaGatewayException $ex) {
      $result = array('status' => 'false', 'exception' => 'true', 'response' => $ex->getMessage());
    }
    return $result;
  }

  function get_pending_orders($age_limit, $orderid = '') {
    global $wpdb;
    if(is_null($age_limit)) {
      $age_limit = $this->pending_order_check_min_age; //seconds  
    }
    if ( 'yes' == $this->debug ) 
      $this->log->add($this->id, "  get_pending_orders");
    
    $table_name = $wpdb->prefix . 'maksuturva_queue';
    $now = new DateTime();
    $age_criteria = "now() - INTERVAL " . $age_limit." SECOND";
    $age_field = "now() - time";
    $query = "SELECT order_id, payment_id, time, ". $age_field . " as age FROM $table_name";
    if ($orderid!='' && intval($orderid)>0) {
      $query .= " WHERE order_id = ".$orderid. ";";
    } else {
      $query .= " WHERE time <  " . $age_criteria;
    }
    
    $orders = $wpdb->get_results($query);
  
    return $orders;
  }

  function do_background_checks($explicit = false) {
      
      $statuses = array();
      $min_age = $this->pending_order_check_min_age;
      if ($explicit == true) {
        $min_age = 0;
      }
      $orders = $this->get_pending_orders($min_age);
      if ( 'yes' == $this->debug )  {
        $c_orders=0;
        if($orders)
          $c_orders = count($orders);
        $this->log->add($this->id, "do_background_checks: #orders=".$c_orders);
      }
      
      if ($orders) {
        
        foreach($orders as $ordera) {
          $order_id = $ordera->order_id;
          
          $order = new WC_Order($order_id);
          /*if (!$order->needs_payment()) {
            if ( 'yes' == $this->debug ) 
              $this->log->add($this->id, "    order: #".$order->id . " (".$order->get_status().") does not need payment. removing from the queue.");
          
            $this->remove_from_maksuturva_queue($ordera->order_id, $ordera->payment_id);
            continue;
          }*/
          if ( 'yes' == $this->debug ) 
              $this->log->add($this->id, "    process status_query for order: #".$order->id . " (".$order->get_status().") ");
          $response = $this->status_request($order);
          $status = $response['status'];

          if ($response['status'] === 'false') {
            if ($response['exception'] == 'true') {
              if ( 'yes' == $this->debug ) 
                $this->log->add($this->id, "    Error: ".$response['response']);
              
              $statuses[] = array(
              'id' => $order->id,
              'message' => __('Error:', $this->td ) . ": ".$response['response']);  
            } else {
              $statuses[] = array(
              'id' => $order->id,
              'message' => __('Error: order not found on Maksuturva', $this->td ). ": ".$response['response']);  
    
              if ( 'yes' == $this->debug ) 
                $this->log->add($this->id, "    order #".$order->id." not found. Removing from the queue...");
              $this->remove_from_maksuturva_queue($ordera->order_id, $ordera->payment_id);
              $order->cancel_order();
            }
            continue;
          }
          if ( 'yes' == $this->debug ) 
              $this->log->add($this->id, "    process_status_query_result");
          
          $res = $this->process_status_query_result($order, $response['response'], $ordera);
          
          if ($order->needs_payment()) {
            $statuses[] = $res;
          }
        }
      }

      return $statuses; 
  }

  function admin_page_top($show_pending_link) {
    $url = add_query_arg('post_status', 'wc-pending', get_admin_url() . "/edit.php" ); //"/edit.php");
    $url = add_query_arg('post_type', 'shop_order', $url );
    //echo '<br/><a target="_blank" href="https://www.maksuturva.fi/extranet/PaymentEventInformation.xtnt">'.__('Open KauppiasExtranet to view payments', $this->td ).'</a>
    $ver = '<br/><a target="_blank" href="' . $url . '">' . __('Verify pending payments', $this->td ) . '</a><hr/>';
    
    $output = ''; 
    
    $output .= '
    <hr />
      <div id="PendingPayments">
      <a target="_blank" href="https://www.maksuturva.fi/extranet/PaymentEventInformation.xtnt">'.__('Open KauppiasExtranet to view payments', $this->td ).'</a><br />
      <a target="_blank" href="' . $url . '">' . __('View orders payments', $this->td ) . '</a>
      <br /><hr/>
    ';
    if($show_pending_link==true) {
      $output .= '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_maksuturva&show-pending=1' ) . '">' . __( 'Check pending payments', $this->td ) . '</a>';
    } else {

      $output .= '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_maksuturva' ) . '">' . __( 'Show admin options', $this->td ) . '</a>';
    
    }
    $output .= '
        <br/>
        </table>
        
    </div>';
    return $output;
  }

  /**
      Show list of pending payments 
    */

  function pending_payments() {
    
    if ( 'yes' == $this->debug ) 
      $this->log->add($this->id, "explicit pending_payments");

    $url = add_query_arg('post_status', 'wc-pending', get_admin_url() . "/edit.php" ); //"/edit.php");
    $url = add_query_arg('post_type', 'shop_order', $url );
    //echo '<br/><a target="_blank" href="https://www.maksuturva.fi/extranet/PaymentEventInformation.xtnt">'.__('Open KauppiasExtranet to view payments', $this->td ).'</a>
    $ver = '<br/><a target="_blank" href="' . $url . '">' . __('Verify pending payments', $this->td ) . '</a><hr/>';
    
    $output = '
        <h3>'. __('List for verified pending payments', $this->td ) .'</h3>
      <table border="1" class="widefat page fixed">
        <tr><td width="100">'. __('Order ID', $this->td ) .'</td><td width="100">'. __('Response code', $this->td ) .'</td>
          <td>'.__('Message from Maksuturva', $this->td ).'</td>
        </tr>';
    $statuses = $this->do_background_checks(true);
    
    if($statuses) {
      foreach ($statuses as $status){
        $output .= '<tr><td>#'. $status['id'] .'</td>';
        if (isset($status['status'])) {
            $output .= '<td>'.$status['status'].'</td>';
        } else {
            $output .= '<td></td>';
        }
        $output .= '<td>'. $status['message'] .'</td></tr>';
      } 
    }
    
    $output .= '
        </table>
        
    </div><hr/>';
    return $output; 
  }
}
