<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 3.x, 4.x
 * Plugin developed for Svea
 * Last update: 3/4/2020
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * [GNU LGPL v. 2.1 @gnu.org] (https://www.gnu.org/licenses/lgpl-2.1.html)
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the GNU
 * Lesser General Public License for more details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'class-wc-gateway-implementation-maksuturva.php';
require_once 'class-wc-payment-maksuturva.php';
require_once 'class-wc-svea-api-request-handler.php';

/**
 * Class WC_Svea_Delivery_Handler.
 *
 * Handles sending delivery information to Svea API
 */
class WC_Svea_Delivery_Handler {

	/**
	 * Payment cancellation route.
	 *
	 * @var string ROUTE_ADD_DELIVERY_INFO
	 *
	 * @since 2.1.2
	 */
	private const ROUTE_ADD_DELIVERY_INFO = '/addDeliveryInfo.pmt';

	/**
	 * Gateway.
	 *
	 * @var WC_Gateway_Maksuturva $gateway The gateway.
	 *
	 * @since 2.1.3
	 */
	private $gateway;

	/**
	 * Order id.
	 *
	 * @var int $order_id The order id.
	 *
	 * @since 2.1.3
	 */
	private $order_id;

	/**
	 * Fields that should be used for hashing request data.
	 * The order of fields in this array is important, do not change it
	 * if you are not sure that you know what you are doing.
	 * 
	 * @var array $request_hash_fields Request hash fields.
	 *
	 * @since 2.1.2
	 */
	private static $request_hash_fields = [
		"pkg_id",
		"pkg_deliverymethodid",
		"pkg_allsent",
	];

	/**
	 * Fields that should be used for hashing response data.
	 * The order of fields in this array is important, do not change it
	 * if you are not sure that you know what you are doing.
	 * 
	 * @var array $response_hash_fields Response hash fields.
	 *
	 * @since 2.1.2
	 */
	private static $response_hash_fields = [
		'pkg_sellerid',
		'pkg_id',
		'pkg_resultcode'
	];

	/**
	 * Seller id.
	 *
	 * @var int $seller_id The seller id.
	 *
	 * @since 2.1.3
	 */
	private $seller_id;

	/**
	 * WC_Svea_Delivery_Handler constructor.
	 * 
	 * @param WC_Gateway_Maksuturva $gateway The gateway.
	 * @param int $order_id The order.
	 * 
	 * @since 2.1.2
	 */
	public function __construct( $gateway, $order_id ) {
		$this->gateway = $gateway;
		$this->order_id = $order_id;
		$this->seller_id = $gateway->get_seller_id();
	}

	/**
	 *	Sends delivery info to Svea API
	 *
	 * @since 2.1.2
	 *
	 * @return array
	 */
	public function send_delivery_info() {

		$payment = new WC_Payment_Maksuturva( $this->order_id );

		$gateway_implementation = new WC_Gateway_Implementation_Maksuturva( $this->gateway, wc_get_order( $this->order_id ) );
		$gateway_data = $gateway_implementation->get_field_array();

		$post_fields = [
			"pkg_version" => "0002",
			"pkg_sellerid" => $this->seller_id,
			"pkg_id" => $payment->get_payment_id(),
			"pkg_deliverymethodid" => "UNRDL",
			"pkg_adddeliveryinfo" => "Capture from WooCommerce",
			"pkg_allsent" => "Y",
			"pkg_resptype" => "XML",
			"pkg_hashversion" => $gateway_data['pmt_hashversion'],
			"pkg_keygeneration" => $this->gateway->get_secret_key_version()
		];

		$api = new WC_Svea_Api_Request_Handler( $this->gateway );
		return $api->post(
			self::ROUTE_ADD_DELIVERY_INFO,
			$post_fields,
			[
				WC_Svea_Api_Request_Handler::SETTINGS_FIELDS_INCLUDED_IN_REQUEST_HASH => self::$request_hash_fields,
				WC_Svea_Api_Request_Handler::SETTINGS_FIELDS_INCLUDED_IN_RESPONSE_HASH => self::$response_hash_fields,
				WC_Svea_Api_Request_Handler::SETTINGS_HASH_FIELD => 'pkg_hash',
				WC_Svea_Api_Request_Handler::SETTINGS_RETURN_CODE_FIELD => 'pkg_resultcode'
			]
		);
	}
}
