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

require_once 'class-wc-gateway-maksuturva-exception.php';
require_once 'class-wc-gateway-abstract-maksuturva.php';
require_once 'class-wc-payment-validator-maksuturva.php';
require_once 'class-wc-utils-maksuturva.php';

/**
 * Class WC_Gateway_Implementation_Maksuturva.
 *
 * Handles payment related data handling. Creates the necessary rows for the order.
 *
 * @since 2.0.0
 */
class WC_Gateway_Implementation_Maksuturva extends WC_Gateway_Abstract_Maksuturva {

	/**
	 * Sandbox seller ID.
	 *
	 * @var string SANDBOX_SELLER_ID
	 */
	const SANDBOX_SELLER_ID = 'testikauppias';

	/**
	 * Sandbox secret key.
	 *
	 * @var string SANDBOX_SECRET_KEY
	 */
	const SANDBOX_SECRET_KEY = '11223344556677889900';

	/**
	 * Shipping cost.
	 *
	 * @since 2.0.0
	 *
	 * @var float $shipping_cost The shipping cost of the order.
	 */
	private $shipping_cost = 0.00;

	/**
	 * The text domain to use for translations.
	 *
	 * @since 2.0.0
	 *
	 * @var string $td The text domain.
	 */
	public $td;

	/**
	 * WC_Gateway_Implementation_Maksuturva constructor.
	 *
	 * @param WC_Gateway_Maksuturva $gateway The gateway object.
	 * @param WC_Order              $order   The order.
	 *
	 * @since 2.0.0
	 */
	public function __construct( WC_Gateway_Maksuturva $gateway, WC_Order $order ) {
		$this->set_base_url( $gateway->get_gateway_url() );
		$this->seller_id  = ( $gateway->is_sandbox() ? self::SANDBOX_SELLER_ID : $gateway->get_seller_id() );
		$this->secret_key = ( $gateway->is_sandbox() ? self::SANDBOX_SECRET_KEY : $gateway->get_secret_key() );
		$this->set_encoding( $gateway->get_encoding() );
		$this->set_payment_id_prefix( $gateway->get_payment_id_prefix() );
		$this->set_payment_data( $this->create_payment_data( $gateway, $order ) );
		$this->td = $gateway->td;
	}

	/**
	 * Create payment data.
	 *
	 * Creates the payment data to be used for the Maksuturva payment gateway.
	 *
	 * @param WC_Gateway_Maksuturva $gateway The gateway object.
	 * @param WC_Order              $order   The order.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private function create_payment_data( WC_Gateway_Maksuturva $gateway, WC_Order $order ) {
		$payment_row_data = $this->create_payment_row_data( $order );
		$buyer_data       = $this->create_buyer_data( $order );
		$delivery_data    = $this->create_delivery_data( $order );
		$payment_id       = $this->get_payment_id( $order );

		return array(
			'pmt_keygeneration'      => $gateway->get_secret_key_version(),
			'pmt_id'                 => $payment_id,
			'pmt_orderid'            => $order->id,
			'pmt_reference'          => $this->get_internal_payment_id( $order ),
			'pmt_sellerid'           => $this->seller_id,
			'pmt_duedate'            => date( 'd.m.Y' ),
			'pmt_userlocale'         => $this->get_locale(),
			'pmt_okreturn'           => $gateway->get_payment_url( $payment_id, 'ok' ),
			'pmt_errorreturn'        => $gateway->get_payment_url( $payment_id, 'error' ),
			'pmt_cancelreturn'       => $gateway->get_payment_url( $payment_id, 'cancel' ),
			'pmt_delayedpayreturn'   => $gateway->get_payment_url( $payment_id, 'delay' ),
			'pmt_amount'             => WC_Utils_Maksuturva::filter_price( $order->get_total() - $this->shipping_cost ),
			'pmt_buyername'          => $buyer_data['name'],
			'pmt_buyeraddress'       => $buyer_data['address'],
			'pmt_buyerpostalcode'    => $buyer_data['postal_code'],
			'pmt_buyercity'          => $buyer_data['city'],
			'pmt_buyercountry'       => $buyer_data['country'],
			'pmt_buyeremail'         => $buyer_data['email'],
			'pmt_buyerphone'         => $buyer_data['phone'],
			'pmt_escrow'             => 'Y',
			'pmt_deliveryname'       => $delivery_data['name'],
			'pmt_deliveryaddress'    => $delivery_data['address'],
			'pmt_deliverypostalcode' => $delivery_data['postal_code'],
			'pmt_deliverycity'       => $delivery_data['city'],
			'pmt_deliverycountry'    => $delivery_data['country'],
			'pmt_sellercosts'        => WC_Utils_Maksuturva::filter_price( $this->shipping_cost ),
			'pmt_rows'               => count( $payment_row_data ),
			'pmt_rows_data'          => $payment_row_data,
		);
	}

	/**
	 * Create payment row data.
	 *
	 * Creates the payment row data for each item in the order.
	 *
	 * @param WC_Order $order The order.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private function create_payment_row_data( WC_Order $order ) {

		$payment_rows = array();
		foreach ( $order->get_items() as $order_item_id => $item ) {
			/* @var WC_Product $product */
			$product = $order->get_product_from_item( $item );

			$description = $this->get_product_description( $product, $order, $order_item_id );

			$payment_row_product = array();

			$payment_row_product['pmt_row_name']     = WC_Utils_Maksuturva::filter_characters( $item['name'] );
			$payment_row_product['pmt_row_desc']     = WC_Utils_Maksuturva::filter_description( $description );
			$payment_row_product['pmt_row_quantity'] = $item['qty'];

			$payment_row_product['pmt_row_articlenr'] = $product->get_sku() ?: '-';

			$price_gross = $order->get_item_subtotal( $item, true );

			$payment_row_product['pmt_row_deliverydate']       = date( 'd.m.Y' );
			$payment_row_product['pmt_row_price_gross']        = WC_Utils_Maksuturva::filter_price( $price_gross );
			$payment_row_product['pmt_row_vat']                = WC_Utils_Maksuturva::filter_price( $this->calc_tax_rate( $product ) );
			$payment_row_product['pmt_row_discountpercentage'] = '00,00';
			$payment_row_product['pmt_row_type']               = 1;
			$payment_rows[]                                    = $payment_row_product;
		}
		$payment_row_shipping = $this->create_payment_row_shipping_data( $order );
		if ( is_array( $payment_row_shipping ) ) {
			$payment_rows[] = $payment_row_shipping;
		}
		$payment_row_discount = $this->create_payment_row_discount_data( $order );
		if ( is_array( $payment_row_discount ) ) {
			$payment_rows[] = $payment_row_discount;
		}

		return $payment_rows;
	}

	/**
	 * Create shipping data row.
	 *
	 * Returns the shipping data for the order.
	 *
	 * @param WC_Order $order The order.
	 *
	 * @since 2.0.0
	 *
	 * @return array|null
	 */
	private function create_payment_row_shipping_data( WC_Order $order ) {
		$this->shipping_cost = $order->get_total_shipping() + $order->get_shipping_tax();

		if ( $this->shipping_cost > 0 ) {
			if ( $order->get_total_shipping() > 0 ) {
				$shipping_tax = 100 * $order->get_shipping_tax() / $order->get_total_shipping();
			} else {
				$shipping_tax = 0;
			}

			return array(
				'pmt_row_name'               => __( 'Shipping cost', $this->td ),
				'pmt_row_desc'               => WC_Utils_Maksuturva::filter_description( $order->get_shipping_method() ),
				'pmt_row_quantity'           => 1,
				'pmt_row_deliverydate'       => date( 'd.m.Y' ),
				'pmt_row_price_gross'        => WC_Utils_Maksuturva::filter_price( $this->shipping_cost ),
				'pmt_row_vat'                => WC_Utils_Maksuturva::filter_price( $shipping_tax ),
				'pmt_row_discountpercentage' => '00,00',
				'pmt_row_type'               => 2,
			);
		}

		return null;
	}

	/**
	 * Add discount row.
	 *
	 * If the order has any discounts, or a coupon is used, data is added.
	 *
	 * @param WC_Order $order The order.
	 *
	 * @since 2.0.0
	 *
	 * @return array|null
	 */
	private function create_payment_row_discount_data( WC_Order $order ) {
		if ( $order->get_total_discount( false ) ) {
			$amount      = $order->get_total_discount( false );
			$description = implode( ',', $order->get_used_coupons() );

			return array(
				'pmt_row_name'               => __( 'Discount', $this->td ),
				'pmt_row_desc'               => WC_Utils_Maksuturva::filter_description( $description ),
				'pmt_row_quantity'           => 1,
				'pmt_row_deliverydate'       => date( 'd.m.Y' ),
				'pmt_row_price_gross'        => '-' . WC_Utils_Maksuturva::filter_price( $amount ), // Negative amount.
				'pmt_row_vat'                => '00,00',
				'pmt_row_discountpercentage' => '00,00',
				'pmt_row_type'               => 6,
			);
		}

		return null;
	}

	/**
	 * Create buyer information data.
	 *
	 * Returns the buyer information.
	 *
	 * @param WC_Order $order The order.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private function create_buyer_data( WC_Order $order ) {
		$email = $order->billing_email;
		if ( ! empty( $order->customer_user ) ) {
			$user = get_user_by( 'id', $order->customer_user );
			if ( $user && empty( $email ) ) {
				$email = $user->user_email;
			}
		}

		return array(
			'name'        => trim( $order->billing_first_name . ' ' . $order->billing_last_name ),
			'address'     => trim( $order->billing_address_1 . ', ' . $order->billing_address_2, ', ' ),
			'postal_code' => $order->billing_postcode,
			'city'        => $order->billing_city,
			'country'     => $order->billing_country,
			'email'       => $email,
			'phone'       => $order->billing_phone,
		);
	}

	/**
	 * Create delivery information data.
	 *
	 * Returns the delivery information.
	 *
	 * @param WC_Order $order The order.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private function create_delivery_data( WC_Order $order ) {
		return array(
			'name'        => trim( $order->shipping_first_name . ' ' . $order->shipping_last_name ),
			'address'     => trim( $order->shipping_address_1 . ', ' . $order->shipping_address_2, ', ' ),
			'postal_code' => $order->shipping_postcode,
			'city'        => $order->shipping_city,
			'country'     => $order->shipping_country,
		);
	}

	/**
	 * Get locale.
	 *
	 * Returns the locale to be used with Maksuturva.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private function get_locale() {
		$locale = get_locale();
		if ( ! in_array( $locale, array( 'fi_FI', 'sv_FI', 'en_FI' ), true ) ) {
			$sub = substr( $locale, 0, 2 );
			if ( 'fi' === $sub ) {
				$locale = 'fi_FI';
			} elseif ( 'sv' === $sub ) {
				$locale = 'sv_FI';
			} else {
				$locale = 'en_FI';
			}
		}

		return $locale;
	}

	/**
	 * Get the payment id.
	 *
	 * Returns the payment id for Maksuturva.
	 *
	 * @param WC_Order $order The order.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	private function get_payment_id( WC_Order $order ) {
		$pmt_id = '';
		if ( strlen( $this->pmt_id_prefix ) ) {
			$pmt_id .= $this->pmt_id_prefix;
		}

		return $pmt_id . $this->get_internal_payment_id( $order );
	}

	/**
	 * Get the internal payment id.
	 *
	 * Returns the internal payment id. Order->id + 100.
	 *
	 * @param WC_Order $order The order.
	 *
	 * @since 2.0.0
	 *
	 * @return int
	 */
	private function get_internal_payment_id( WC_Order $order ) {
		return $order->id + 100;
	}

	/**
	 * Check for payment reference number.
	 *
	 * Compares given reference number with the reference number in the payment data.
	 *
	 * @param string $pmt_reference The reference number.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 * @throws WC_Gateway_Maksuturva_Exception If reference number is invalid.
	 */
	public function check_payment_reference_number( $pmt_reference ) {
		// DO NOT CHANGE TO STRICT ===.
		return ( $pmt_reference == $this->get_payment_reference_number() );
	}

	/**
	 * Get the payment reference number.
	 *
	 * Returns the reference number from the payment data.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 * @throws WC_Gateway_Maksuturva_Exception If reference number is invalid.
	 */
	public function get_payment_reference_number() {
		return $this->get_pmt_reference_number( $this->payment_data['pmt_reference'] );
	}

	/**
	 * Check payment id.
	 *
	 * Checks if the given payment id is same as in the order.
	 *
	 * @param string $pmt_id The payment id.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function check_payment_id( $pmt_id ) {
		if ( strlen( $this->pmt_id_prefix )
		     && substr( $pmt_id, 0, strlen( $this->pmt_id_prefix ) ) === $this->pmt_id_prefix
		) {
			$pmt_id = substr( $pmt_id, strlen( $this->pmt_id_prefix ) );
		}

		return ( ( (int) $pmt_id - 100 ) == $this->pmt_orderid );
	}

	/**
	 * Validate a payment.
	 *
	 * Runs the Maksuturva Payment Validator on the given params.
	 *
	 * @param array $params The parameters to validate.
	 *
	 * @since 2.0.0
	 *
	 * @return WC_Payment_Validator_Maksuturva
	 */
	public function validate_payment( array $params ) {
		$validator = new WC_Payment_Validator_Maksuturva( $this );

		return $validator->validate( $params );
	}

	/**
	 * Calculate the tax rate.
	 *
	 * Returns the calculated tax rate for the given product.
	 *
	 * @param WC_Product $product The product.
	 *
	 * @since 2.0.0
	 *
	 * @return int
	 */
	private function calc_tax_rate( $product ) {
		$tax_rates = WC_Tax::get_rates( $product->get_tax_class() );

		$rate = 0;
		foreach ( $tax_rates as $tax_rate ) {
			$rate += $tax_rate['rate'];
		}

		return $rate;
	}

	/**
	 * Get the product description.
	 *
	 * Returns the description for the given product.
	 *
	 * @param WC_Product|WC_Product_Variable $product       The product.
	 * @param WC_Order                       $order         The order.
	 * @param int                            $order_item_id The order item id.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	protected function get_product_description( $product, $order, $order_item_id ) {
		$description = '';

		$item_meta = new WC_Order_Item_Meta( $order->get_item_meta( $order_item_id ) );
		if ( $formatted = $item_meta->get_formatted() ) {
			foreach ( $formatted as $attr ) {
				$description .= implode( '=', $attr ) . '|';
			}
		}
		if ( 'variable' === $product->product_type ) {
			$description .= implode( ',', $product->get_variation_attributes() ) . ' ';
		}
		$description .= ( $product->post->post_excerpt );

		return $description;
	}
}
