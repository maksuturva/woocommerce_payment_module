<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 7.x, 8.x
 * Plugin developed for Svea
 * Last update: 30/11/2020
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

namespace SveaPaymentGateway\includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WC_Order_Compatibility_Handler.
 *
 * Handles the difference on how to access order properties between
 * WooCommerce version 2.x and 3.x
 *
 * @since 2.0.6
 */
class WC_Order_Compatibility_Handler {

	/**
	 *
	 * @var \WC_Order
	 */
	private $order;

	/**
	 * Access order values through methods instead of properties since version
	 */
	const NO_PROPERTIES_VERSION = 3;

	/**
	 * WC_Order_Compatibility_Handler constructor.
	 *
	 * @param \WC_Order $order Order to handle
	 *
	 * @since 2.0.6
	 */
	public function __construct( \WC_Order $order ) {
		$this->order = $order;
	}

	/**
	 * Returns the unique ID for the order.
	 *
	 * @return int
	 */
	public function get_id() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->id;
		} else {
			return $this->order->get_id();
		}
	}

	/**
	 * Get billing first name.
	 *
	 * @return string
	 */
	public function get_billing_first_name() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->billing_first_name;
		} else {
			return $this->order->get_billing_first_name();
		}
	}

	/**
	 * Get billing last name.
	 *
	 * @return string
	 */
	public function get_billing_last_name() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->billing_last_name;
		} else {
			return $this->order->get_billing_last_name();
		}
	}

	/**
	 * Get billing address line 1.
	 *
	 * @return string
	 */
	public function get_billing_address_1() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->billing_address_1;
		} else {
			return $this->order->get_billing_address_1();
		}
	}

	/**
	 * Get billing address line 2.
	 *
	 * @return string
	 */
	public function get_billing_address_2() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->billing_address_2;
		} else {
			return $this->order->get_billing_address_2();
		}
	}

	/**
	 * Get billing postcode.
	 *
	 * @return string
	 */
	public function get_billing_postcode() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->billing_postcode;
		} else {
			return $this->order->get_billing_postcode();
		}
	}

	/**
	 * Get billing city.
	 *
	 * @return string
	 */
	public function get_billing_city() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->billing_city;
		} else {
			return $this->order->get_billing_city();
		}
	}

	/**
	 * Get billing country.
	 *
	 * @return string
	 */
	public function get_billing_country() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->billing_country;
		} else {
			return $this->order->get_billing_country();
		}
	}

	/**
	 * Get billing phone.
	 *
	 * @return string
	 */
	public function get_billing_phone() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->billing_phone;
		} else {
			return $this->order->get_billing_phone();
		}
	}

	/**
	 * Get shipping first name.
	 *
	 * @return string
	 */
	public function get_shipping_first_name() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->shipping_first_name;
		} else {
			return $this->order->get_shipping_first_name();
		}
	}

	/**
	 * Get shipping_last_name.
	 *
	 * @return string
	 */
	public function get_shipping_last_name() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->shipping_last_name;
		} else {
			return $this->order->get_shipping_last_name();
		}
	}

	/**
	 * Get shipping address line 1.
	 *
	 * @return string
	 */
	public function get_shipping_address_1() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->shipping_address_1;
		} else {
			return $this->order->get_shipping_address_1();
		}
	}

	/**
	 * Get shipping address line 2.
	 *
	 * @return string
	 */
	public function get_shipping_address_2() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->shipping_address_2;
		} else {
			return $this->order->get_shipping_address_2();
		}
	}

	/**
	 * Get shipping postcode.
	 *
	 * @return string
	 */
	public function get_shipping_postcode() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->shipping_postcode;
		} else {
			return $this->order->get_shipping_postcode();
		}
	}

	/**
	 * Get shipping city.
	 *
	 * @return string
	 */
	public function get_shipping_city() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->shipping_city;
		} else {
			return $this->order->get_shipping_city();
		}
	}

	/**
	 * Get shipping country.
	 *
	 * @return string
	 */
	public function get_shipping_country() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->shipping_country;
		} else {
			return $this->order->get_shipping_country();
		}
	}

	/**
	 * Get billing email.
	 *
	 * @return string
	 */
	public function get_billing_email() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->billing_email;
		} else {
			return $this->order->get_billing_email();
		}
	}

	/**
	 * Get customer_id.
	 *
	 * @return int
	 */
	public function get_customer_id() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->customer_user;
		} else {
			return $this->order->get_customer_id();
		}
	}

	/**
	 * Get order key.
	 *
	 * @return string
	 */
	public function get_order_key() {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->order_key;
		} else {
			return $this->order->get_order_key();
		}
	}

	/**
	 * Get order item meta.
	 *
	 * @param mixed $order_item_id
	 * @param string $key (default: '')
	 * @param bool $single (default: false)
	 * @return array|string
	 */
	public function get_item_meta( $order_item_id, $key = '', $single = false ) {
		if ( version_compare( WC_VERSION, self::NO_PROPERTIES_VERSION, '<' ) ) {
			return $this->order->get_item_meta( $order_item_id, $key, $single );
		} else {
			return get_metadata( 'order_item', $order_item_id, $key, $single );
		}
	}

}
