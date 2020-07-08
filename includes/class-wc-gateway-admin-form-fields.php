<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 2.x, 3.x
 * Plugin developed for Svea
 * Last update: 24/10/2019
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

/**
 * Class WC_Gateway_Admin_Form_Fields.
 *
 * Handles defining the gateway admin view form fields.
 *
 * @since 2.0.10
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
	 * @since 2.0.10
	 */
	public function __construct( WC_Gateway_Maksuturva $gateway ) {
		$this->gateway = $gateway;
  }

  /**
	 * Returns the gateway admin form fields as an array
	 *
	 * @since 2.0.10
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
      'select_payment_method_in_system' => [
        'type'        => 'select',
        'title'       => __( 'Choose payment method in', $this->gateway->td ),
        'desc_tip'    => true,
        'default'     => WC_Payment_Method_Select::SELECT_PAYMENT_METHOD_SYSTEM_SVEA,
        'description' => __( 'Payment method can be selected in webstore or in Svea.', $this->gateway->td ),
        'options'     => [
          WC_Payment_Method_Select::SELECT_PAYMENT_METHOD_SYSTEM_SVEA => 'Svea',
          WC_Payment_Method_Select::SELECT_PAYMENT_METHOD_SYSTEM_WEBSTORE => 'Webstore'
        ]
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
      ]
    ];
  }
}
