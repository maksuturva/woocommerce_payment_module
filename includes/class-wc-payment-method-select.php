<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce
 * Plugin developed for Svea Payments Oy
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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'class-wc-gateway-implementation-maksuturva.php';
require_once 'class-wc-payment-handling-costs.php';
require_once 'class-wc-svea-api-request-handler.php';
require_once 'class-wc-utils-maksuturva.php';

/**
 * Class WC_Payment_Method_Select.
 *
 * Handles functionalities related to the payment method selection.
 *
 * @since 2.1.3
 */
class WC_Payment_Method_Select {

	/**
	 * Id for payment method select
	 *
	 * @var string PAYMENT_METHOD_SELECT_ID
	 */
	public const PAYMENT_METHOD_SELECT_ID = 'svea_payment_method';

	/**
	 * Payment cancellation route.
	 *
	 * @var string ROUTE_ADD_DELIVERY_INFO
	 */
	private const ROUTE_RETRIEVE_AVAILABLE_PAYMENT_METHODS = 'GetPaymentMethods.pmt';

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
	 * Available payment methods.
	 *
	 * @var array $available_payment_methods The available payment methods.
	 */
	private static $available_payment_methods;

	/**
	 * WC_Payment_Method_Select constructor.
	 *
	 * @param WC_Gateway_Maksuturva $gateway The gateway.
	 *
	 * @since 2.1.3
	 */
	public function __construct( WC_Gateway_Maksuturva $gateway ) {
		$this->gateway   = $gateway;
		$this->seller_id = $gateway->get_seller_id();
	}

	/**
	 * Initialize payment method select box content
	 *
	 * @param string $payment_type Payment type.
	 * @param int $price The price.
	 * @param bool $is_outbound_payment_enabled Is outbound payment enabled.
	 *
	 * @since 2.1.3
	 */
	public function initialize_payment_method_select( $payment_type, $price, $is_outbound_payment_enabled ) {

		$payment_handling_costs_handler = new WC_Payment_Handling_Costs( $this->gateway );
		$paymentMethodsFetchedSuccessfully = true;

		$form_params = array(
			'currency_symbol'               => get_woocommerce_currency_symbol(),
			'payment_method_handling_costs' => $payment_handling_costs_handler->get_handling_costs_by_payment_method(),
			'payment_method_select_id'      => self::PAYMENT_METHOD_SELECT_ID,
		);

		if ( ! $is_outbound_payment_enabled ) {
			if ( $payment_type == 'collated' ) {
				$group_methods           = array();
				$group_methods['group1'] = array();
				$group_methods['group2'] = array();
				$group_methods['group3'] = array();
				$group_methods['group4'] = array();

				// $form_params['collated_title'] = $this->gateway->get_option('collated_title', "Svea Payments");
				$collated_payment_methods = $this->get_payment_type_payment_methods( $payment_type, $price );

				foreach ( $collated_payment_methods as $payment_method ) {
					if (empty($payment_method['code'])) {
						$paymentMethodsFetchedSuccessfully = false;

						continue;
					}

					if ( in_array( $payment_method['code'], explode( ',', $this->gateway->get_option( 'collated_group1_methods', '' ) ) ) ) {
						$group_methods['group1'][] = $payment_method;
					} elseif ( in_array( $payment_method['code'], explode( ',', $this->gateway->get_option( 'collated_group2_methods', '' ) ) ) ) {
						$group_methods['group2'][] = $payment_method;
					} elseif ( in_array( $payment_method['code'], explode( ',', $this->gateway->get_option( 'collated_group3_methods', '' ) ) ) ) {
						$group_methods['group3'][] = $payment_method;
					} elseif ( in_array( $payment_method['code'], explode( ',', $this->gateway->get_option( 'collated_group4_methods', '' ) ) ) ) {
						$group_methods['group4'][] = $payment_method;
					}
				}
				$form_params['method_group1'] = array(
					'title'   => $this->gateway->get_option( 'collated_group1_title', '' ),
					'methods' => $group_methods['group1'],
				);
				$form_params['method_group2'] = array(
					'title'   => $this->gateway->get_option( 'collated_group2_title', '' ),
					'methods' => $group_methods['group2'],
				);
				$form_params['method_group3'] = array(
					'title'   => $this->gateway->get_option( 'collated_group3_title', '' ),
					'methods' => $group_methods['group3'],
				);
				$form_params['method_group4'] = array(
					'title'   => $this->gateway->get_option( 'collated_group4_title', '' ),
					'methods' => $group_methods['group4'],
				);
			} else {
				$methods = $this->get_payment_type_payment_methods( $payment_type, $price );

				if ( empty( $methods ) || isset( $methods['ERROR'] ) ) {
					$paymentMethodsFetchedSuccessfully = false;
				}

				$form_params['payment_methods'] = $methods;
			}

			if ( ! $paymentMethodsFetchedSuccessfully ) {
				$form_params['payment_methods'] = [];

				$this->gateway->render(
					'payment-method-error-form',
					'frontend',
				);
			}

			$form_params['terms'] = array(
				'text' => $this->get_terms_text( $price ),
				'url'  => $this->get_terms_url( $price ),
			);
		}

		$this->gateway->render(
			'payment-method-' . $payment_type . '-form',
			'frontend',
			$form_params
		);
	}

	/**
	 * Validates that the payment method is selected.
	 *
	 * @since 2.1.3
	 *
	 * @return bool
	 */
	public function validate_payment_method_select() {

		if ( ! isset( $_POST[ self::PAYMENT_METHOD_SELECT_ID ] ) ) {
			wc_add_notice( __( 'Payment method not selected', 'wc-maksuturva' ), 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Sorts payment methods to categories.
	 *
	 * @since 2.1.3
	 *
	 * @return array
	 */
	public function get_payment_type_payment_methods( $payment_type, $price ) {

		$available_payment_methods = $this->get_available_payment_methods( $price );

		if ( isset( $available_payment_methods['ERROR'] ) ) {
			return $available_payment_methods;
		}

		$payment_type_payment_methods = array(
			'credit-card-and-mobile'    => array(),
			'invoice-and-hire-purchase' => array(),
			'online-bank-payments'      => array(),
			'estonia-payments'          => array(),
			'other-payments'            => array(),
			'collated'                  => array(),
		);

		if ( $payment_type == 'collated' ) {
			foreach ( $available_payment_methods['paymentmethod'] as $key => $payment_method ) {
				$payment_type_payment_methods['collated'][] = $payment_method;
				unset( $available_payment_methods['paymentmethod'][ $key ] );
			}
		} else {
			foreach ( $available_payment_methods['paymentmethod'] as $key => $payment_method ) {
				if ( in_array( substr( $payment_method['code'], 0, 3 ), array( 'FI0', 'FI1' ) ) ) {
					$payment_type_payment_methods['online-bank-payments'][] = $payment_method;
					unset( $available_payment_methods['paymentmethod'][ $key ] );
				}
			}

			foreach ( $available_payment_methods['paymentmethod'] as $key => $payment_method ) {
				if ( in_array( substr( $payment_method['code'], 0, 3 ), array( 'FI5' ) ) || in_array( substr( $payment_method['code'], 0, 4 ), array( 'SIIR' ) ) ) {
					$payment_type_payment_methods['credit-card-and-mobile'][] = $payment_method;
					unset( $available_payment_methods['paymentmethod'][ $key ] );
				}
			}

			foreach ( $available_payment_methods['paymentmethod'] as $key => $payment_method ) {
				if ( in_array( substr( $payment_method['code'], 0, 3 ), array( 'FI6', 'FI7' ) ) ) {
					$payment_type_payment_methods['invoice-and-hire-purchase'][] = $payment_method;
					unset( $available_payment_methods['paymentmethod'][ $key ] );
				}
			}

			foreach ( $available_payment_methods['paymentmethod'] as $payment_method ) {
				if ( $payment_method['code'] == 'EEAC' ) {
					/* check if plugin has EAAC_logo.png and if exist, use it as payment method logo */
					$payment_method['imageurl']                         = $this->get_eeac_payment_method_logo_url( $payment_method['imageurl'] );
					$payment_type_payment_methods['estonia-payments'][] = $payment_method;
					unset( $available_payment_methods['paymentmethod'][ $key ] );
				}
			}

			foreach ( $available_payment_methods['paymentmethod'] as $payment_method ) {
				$payment_type_payment_methods['other-payments'][] = $payment_method;
			}
		}

		return $payment_type_payment_methods[ $payment_type ];
	}

	/**
	 * Fetches terms text
	 *
	 * @since 2.1.3
	 *
	 * @return string
	 */
	private function get_terms_text( $price ) {
		$available_payment_methods = $this->get_available_payment_methods( $price );
		if ( isset( $available_payment_methods['termtext'] ) ) {
			return $available_payment_methods['termstext'];
		} else {
			return '';
		}
	}

	/**
	 * Fetches terms url
	 *
	 * @since 2.1.3
	 *
	 * @return string
	 */
	private function get_terms_url( $price ) {
		$available_payment_methods = $this->get_available_payment_methods( $price );
		if ( isset( $available_payment_methods['termsurl'] ) ) {
			return $available_payment_methods['termsurl'];
		} else {
			return '';
		}
	}

	/**
	 * Get display name for payment method
	 *
	 * @since 2.3.5
	 *
	 * @return string
	 */
	public function get_payment_method_name( $payment_method_code ) {
		$available_payment_methods = $this->get_available_payment_methods( 10 );
		if ( isset( $available_payment_methods['paymentmethod'] ) ) {
			foreach ( $available_payment_methods['paymentmethod'] as $method ) {
				if ( $method['code'] == $payment_method_code ) {
					return $method['displayname'];
				}
			}
		}

		return '';
	}

	/**
	 * Allow override payment method logo for Estonia EEAC payment method
	 *
	 * install to plugin path as file EEAC_logo.png
	 *
	 * @since 2.1.4
	 *
	 * @return string
	 */
	private function get_eeac_payment_method_logo_url( $original_url ) {
		$logo_path     = trailingslashit( WC_Maksuturva::get_instance()->get_plugin_dir() ) . 'EEAC_logo.png';
		$override_logo = file_exists( $logo_path );

		if ( $override_logo ) {
			return WC_Maksuturva::get_instance()->get_plugin_url() . 'EEAC_logo.png';
		} else {
			return $original_url;
		}
	}

	/**
	 *  Fetches payment type payment methods from Svea api.
	 *
	 * @since 2.1.3
	 *
	 * @param int $price The price
	 *
	 * @return array
	 */
	private function get_available_payment_methods( $price ) {
		if ( isset( self::$available_payment_methods ) ) {
			return self::$available_payment_methods;
		}

		$post_fields = array(
			'request_locale' => explode( '_', get_user_locale() )[0],
			'sellerid'       => $this->seller_id,
			'totalamount'    => WC_Utils_Maksuturva::filter_price( $price ),
		);

		$cache_key      = sanitize_title( 'svea-payment-methods-' . implode( '-', $post_fields ) );
		$result_methods = get_transient( $cache_key );

		if ( ! $result_methods ) {
			$api = new WC_Svea_Api_Request_Handler( $this->gateway );

			$result_methods = $api->get(
				self::ROUTE_RETRIEVE_AVAILABLE_PAYMENT_METHODS,
				$post_fields
			);

			if ( ! isset( $result_methods['ERROR'] ) ) {
				set_transient( $cache_key, $result_methods, HOUR_IN_SECONDS * 4 );
			}
		}

		/**
		 * bugfix: if there is only one payment method, the request handler will not return
		 * array... this will cause problems later
		 *
		 * at this point, we will check this and fix the variable type
		 */
		if ( array_key_exists( 'paymentmethod', $result_methods ) && is_array( $result_methods['paymentmethod'] ) ) {
			if ( ! array_key_exists( '0', $result_methods['paymentmethod'] ) ) {
				$result_methods['paymentmethod'] = array( $result_methods['paymentmethod'] );
			}
		}
		self::$available_payment_methods = $result_methods;
		return self::$available_payment_methods;
	}
}
