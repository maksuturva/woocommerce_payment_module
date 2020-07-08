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

require_once 'class-wc-gateway-implementation-maksuturva.php';
require_once 'class-wc-svea-api-request-handler.php';
require_once 'class-wc-utils-maksuturva.php';

/**
 * Class WC_Payment_Method_Select.
 *
 * Handles functionalities related to the payment method selection.
 *
 * @since 2.0.10
 */
class WC_Payment_Method_Select {

	/**
	 * Id for payment method select
	 *
	 * @var string PAYMENT_METHOD_SELECT_ID
	 */
	public const PAYMENT_METHOD_SELECT_ID = 'svea-payment-method';

	/**
	 * Payment method is selected in svea.
	 *
	 * @var string SELECT_PAYMENT_METHOD_SYSTEM_SVEA
	 */
	public const SELECT_PAYMENT_METHOD_SYSTEM_SVEA = '0';

	/**
	 * Payment method is selected in webstore.
	 *
	 * @var string SELECT_PAYMENT_METHOD_SYSTEM_WEBSTORE
	 */
	public const SELECT_PAYMENT_METHOD_SYSTEM_WEBSTORE = '1';

  /**
	 * Payment cancellation route.
	 *
	 * @var string ROUTE_ADD_DELIVERY_INFO
	 */
  private const ROUTE_RETRIEVE_AVAILABLE_PAYMENT_METHODS = '/GetPaymentMethods.pmt';

  /**
	 * Gateway.
	 *
	 * @var WC_Gateway_Maksuturva $gateway The gateway.
	 */
  private $gateway;

  /**
	 * Seller id.
	 *
	 * @var int $seller_id The seller id.
	 */
  private $seller_id;

  /**
	 * WC_Payment_Method_Select constructor.
	 * 
	 * @param WC_Gateway_Maksuturva $gateway The gateway.
	 * @param int $order_id The order.
	 * 
	 * @since 2.0.10
	 */
	public function __construct( WC_Gateway_Maksuturva $gateway ) {
		$this->gateway = $gateway;
    $this->seller_id = $gateway->get_seller_id();
  }

	/**
	 * Initialize payment method select box content
	 *
	 * @param int $price The price
	 *
	 * @since 2.0.10
	 */
  public function initialize_payment_method_select( $price ) {

    $available_payment_methods = $this->get_available_payment_methods( $price );

    $this->gateway->render(
			'payment-method-form',
			'frontend',
			[
				'payment_method_select_id' => self::PAYMENT_METHOD_SELECT_ID,
				'payment_methods' => $available_payment_methods['paymentmethod'],
				'terms' => [
					'text' => $available_payment_methods['termstext'],
					'url' => $available_payment_methods['termsurl']
				]
			]
		);
	}

	/**
	 * Returns true if the payment method selected in webstore.
	 *
	 * @since 2.0.10
	 *
	 * @return bool
	 */
	public function payment_method_is_selected_in_webstore() {
		return $this->gateway->get_option( 'select_payment_method_in_system' ) === self::SELECT_PAYMENT_METHOD_SYSTEM_WEBSTORE;
	}

	/**
	 * Validates that the payment method is selected.
	 *
	 * @since 2.0.10
	 *
	 * @return bool
	 */
  public function validate_payment_method_select() {

		if ( !isset( $_POST[ WC_Payment_Method_Select::PAYMENT_METHOD_SELECT_ID ] ) ) {
			wc_add_notice( __( 'Payment method not selected', $this->gateway->td ), 'error' );
			return false;
		}

		return true;
  }

  /**
	 *	Fetches available payment methods from Svea api.
	 *
	 * @since 2.0.10
	 *
	 * @param int $price The price
	 *
	 * @return array
	 */
	private function get_available_payment_methods( $price ) {

		$post_fields = [
      'request_locale' => explode( '_', get_user_locale() )[0],
      'sellerid' => $this->seller_id,
      'totalamount' => WC_Utils_Maksuturva::filter_price( $price )
		];

		$api = new WC_Svea_Api_Request_Handler( $this->gateway );

		return $api->post(
			self::ROUTE_RETRIEVE_AVAILABLE_PAYMENT_METHODS,
			$post_fields
		);
	}
}
