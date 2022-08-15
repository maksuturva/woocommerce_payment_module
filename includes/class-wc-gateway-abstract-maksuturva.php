<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 3.x, 4.x
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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'class-wc-data-hasher.php';

/**
 * Class WC_Gateway_Abstract_Maksuturva.
 *
 * Abstract for the Svea payments gateway. Handles basic functionality against the Svea Payments Gateway.
 *
 * @since 2.0.0
 *
 * @property int $pmt_orderid The Svea order id.
 * @property int $pmt_id      The Svea payment id.
 */
abstract class WC_Gateway_Abstract_Maksuturva {

	/**
	 * Status query not found.
	 *
	 * @var string STATUS_QUERY_NOT_FOUND
	 */
	const STATUS_QUERY_NOT_FOUND = '00';

	/**
	 * Status query failed.
	 *
	 * @var string STATUS_QUERY_FAILED
	 */
	const STATUS_QUERY_FAILED = '01';

	/**
	 * Status query waiting.
	 *
	 * @var string STATUS_QUERY_WAITING
	 */
	const STATUS_QUERY_WAITING = '10';

	/**
	 * Status query unpaid.
	 *
	 * @var string STATUS_QUERY_UNPAID
	 */
	const STATUS_QUERY_UNPAID = '11';

	/**
	 * Status query unpaid delivery.
	 *
	 * @var string STATUS_QUERY_UNPAID_DELIVERY
	 */
	const STATUS_QUERY_UNPAID_DELIVERY = '15';

	/**
	 * Status query paid.
	 *
	 * @var string STATUS_QUERY_PAID
	 */
	const STATUS_QUERY_PAID = '20';

	/**
	 * Status query paid delivery.
	 *
	 * @var string STATUS_QUERY_PAID_DELIVERY
	 */
	const STATUS_QUERY_PAID_DELIVERY = '30';

	/**
	 * Status query compensated.
	 *
	 * @var string STATUS_QUERY_COMPENSATED
	 */
	const STATUS_QUERY_COMPENSATED = '40';

	/**
	 * Status query payer cancelled.
	 *
	 * @var string STATUS_QUERY_PAYER_CANCELLED
	 */
	const STATUS_QUERY_PAYER_CANCELLED = '91';

	/**
	 * Status query payer cancelled partial.
	 *
	 * @var string STATUS_QUERY_PAYER_CANCELLED_PARTIAL
	 */
	const STATUS_QUERY_PAYER_CANCELLED_PARTIAL = '92';

	/**
	 * Status query payer cancelled partial return.
	 *
	 * @var string STATUS_QUERY_PAYER_CANCELLED_PARTIAL_RETURN
	 */
	const STATUS_QUERY_PAYER_CANCELLED_PARTIAL_RETURN = '93';

	/**
	 * Status query payer reclamation.
	 *
	 * @var string STATUS_QUERY_PAYER_RECLAMATION
	 */
	const STATUS_QUERY_PAYER_RECLAMATION = '95';

	/**
	 * Status query cancelled.
	 *
	 * @var string STATUS_QUERY_CANCELLED
	 */
	const STATUS_QUERY_CANCELLED = '99';

	/**
	 * Code algorithm not supported.
	 *
	 * @var string EXCEPTION_CODE_ALGORITHMS_NOT_SUPPORTED
	 */
	const EXCEPTION_CODE_ALGORITHMS_NOT_SUPPORTED = '00';

	/**
	 * URL generation errors.
	 *
	 * @var string EXCEPTION_CODE_URL_GENERATION_ERRORS
	 */
	const EXCEPTION_CODE_URL_GENERATION_ERRORS = '01';

	/**
	 * Field array generation errors.
	 *
	 * @var string EXCEPTION_CODE_FIELD_ARRAY_GENERATION_ERRORS
	 */
	const EXCEPTION_CODE_FIELD_ARRAY_GENERATION_ERRORS = '02';

	/**
	 * Reference number below 100.
	 *
	 * @var string EXCEPTION_CODE_REFERENCE_NUMBER_UNDER_100
	 */
	const EXCEPTION_CODE_REFERENCE_NUMBER_UNDER_100 = '03';

	/**
	 * Field missing.
	 *
	 * @var string EXCEPTION_CODE_FIELD_MISSING
	 */
	const EXCEPTION_CODE_FIELD_MISSING = '04';

	/**
	 * Invalid item.
	 *
	 * @var string EXCEPTION_CODE_INVALID_ITEM
	 */
	const EXCEPTION_CODE_INVALID_ITEM = '05';

	/**
	 * Curl not installed.
	 *
	 * @var string EXCEPTION_CODE_PHP_CURL_NOT_INSTALLED
	 */
	const EXCEPTION_CODE_PHP_CURL_NOT_INSTALLED = '06';

	/**
	 * Hash don't match.
	 *
	 * @var string EXCEPTION_CODE_HASHES_DONT_MATCH
	 */
	const EXCEPTION_CODE_HASHES_DONT_MATCH = '07';

	/**
	 * Order vs status query response data mismatch
	 *
	 * @var string EXCEPTION_CODE_DATA_MISMATCH
	 */
	const EXCEPTION_CODE_DATA_MISMATCH = '08';

	/**
	 * Route to payment.
	 *
	 * @var string ROUTE_PAYMENT
	 */
	const ROUTE_PAYMENT = '/NewPaymentExtended.pmt';

	/**
	 * Route to status query.
	 *
	 * @var string ROUTE_STATUS_QUERY
	 */
	const ROUTE_STATUS_QUERY = '/PaymentStatusQuery.pmt';

	/**
	 * Base URL.
	 *
	 * @since 2.0.0
	 *
	 * @var string $base_url Gateway URL for making new payments.
	 */
	protected $base_url = 'https://www.maksuturva.fi/NewPaymentExtended.pmt';

	/**
	 * Status query URL.
	 *
	 * @since 2.0.0
	 *
	 * @var string $base_url_status_query Gateway URL for checking payment statuses.
	 */
	protected $base_url_status_query = 'https://www.maksuturva.fi/PaymentStatusQuery.pmt';

	/**
	 * The WC_Payment_Gateway extension.
	 *
	 * @since 2.1.3
	 * 
	 * @var WC_Gateway_Maksuturva $wc_gateway The WC_Payment_Gateway extension.
	 */
	public $wc_gateway;

	/**
	 * Seller ID.
	 *
	 * @since 2.0.0
	 *
	 * @var string $seller_id Seller ID to use for identification when calling the gateway.
	 */
	protected $seller_id;

	/**
	 * Charset.
	 *
	 * @since 2.0.0
	 *
	 * @var string $charset Charset for the payment data.
	 */
	protected $charset = 'UTF-8';

	/**
	 * HTTP charset.
	 *
	 * @since 2.0.0
	 *
	 * @var string $charset_http Charset for the payment http data.
	 */
	protected $charset_http = 'UTF-8';

	/**
	 * Payment ID prefix.
	 *
	 * @since 2.0.0
	 *
	 * @var string $pmt_id_prefix Prefix used for the `pmt_id` field.
	 */
	protected $pmt_id_prefix;

	/**
	 * Hash algorithm.
	 *
	 * @since 2.0.0
	 *
	 * @var string Algorithm used for hashing (sha512, sha256, sha1 or md5).
	 */
	protected $hash_algorithm;

	/**
	 * Payment data.
	 *
	 * @since 2.0.0
	 *
	 * @var array $payment_data The payment data.
	 */
	protected $payment_data = array(
		'pmt_action'              => 'NEW_PAYMENT_EXTENDED',
		'pmt_version'             => '0004',
		'pmt_escrow'              => 'Y',
		'pmt_keygeneration'       => '001',
		'pmt_currency'            => 'EUR',
		'pmt_escrowchangeallowed' => 'N',
		'pmt_charset'             => 'UTF-8',
		'pmt_charsethttp'         => 'UTF-8',
	);

	/**
	 * Status query data.
	 *
	 * @since 2.0.0
	 *
	 * @var array $status_query_data Payment query status data.
	 */
	protected $status_query_data = array();

	/**
	 * Mandatory data.
	 *
	 * @since 2.0.0
	 *
	 * @var array $mandatory_data Mandatory properties in the payment data.
	 */
	private static $mandatory_data = array(
		'pmt_action',               // Alphanumeric  max-length: 50   min-length: 4   NEW_PAYMENT_EXTENDED.
		'pmt_version',              // Alphanumeric  max-length: 4    min-length: 4   0004.
		'pmt_sellerid',             // Alphanumeric  max-length: 15   -.
		'pmt_id',                   // Alphanumeric  max-length: 20   -.
		'pmt_orderid',              // Alphanumeric  max-length: 50   -.
		'pmt_reference',            // Numeric       max-length: 20   min-length: 4   Reference number + check digit.
		'pmt_duedate',              // Alphanumeric  max-length: 10   min-length: 10  dd.MM.yyyy.
		'pmt_amount',               // Alphanumeric  max-length: 17   min-length: 4.
		'pmt_currency',             // Alphanumeric  max-length: 3    min-length: 3   EUR.
		'pmt_okreturn',             // Alphanumeric  max-length: 200  -.
		'pmt_errorreturn',          // Alphanumeric  max-length: 200  -.
		'pmt_cancelreturn',         // Alphanumeric  max-length: 200  -.
		'pmt_delayedpayreturn',     // Alphanumeric  max-length: 200  -.
		'pmt_escrow',               // Alpha         max-length: 1    min-length: 1   Y/N.
		'pmt_escrowchangeallowed',  // Alpha         max-length: 1    min-length: 1   N.
		'pmt_buyername',            // Alphanumeric  max-length: 40   -.
		'pmt_buyeraddress',         // Alphanumeric  max-length: 40   -.
		'pmt_buyerpostalcode',      // Numeric       max-length: 5    -.
		'pmt_buyercity',            // Alphanumeric  max-length: 40   -.
		'pmt_buyercountry',         // Alpha         max-length: 2    -               Respecting the ISO 3166.
		'pmt_deliveryname',         // Alphanumeric  max-length: 40   -.
		'pmt_deliveryaddress',      // Alphanumeric  max-length: 40   -.
		'pmt_deliverypostalcode',   // Numeric       max-length: 5    -.
		'pmt_deliverycountry',      // Alpha         max-length: 2    -               Respecting the ISO 3166.
		'pmt_sellercosts',          // Alphanumeric  max-length: 17   min-length: 4   n,nn.
		'pmt_rows',                 // Numeric       max-length: 4    min-length: 1.
		'pmt_charset',              // Alphanumeric  max-length: 15   -               {ISO-8859-1, ISO-8859-15, UTF-8}.
		'pmt_charsethttp',          // Alphanumeric  max-length: 15   -               {ISO-8859-1, ISO-8859-15, UTF-8}.
		'pmt_hashversion',          // Alphanumeric  max-length: 10   -               {SHA-512, SHA-256, SHA-1, MD5}.
		'pmt_keygeneration',        // N umeric       max-length: 3    -..
	);

	/**
	 * Optional data.
	 *
	 * @since 2.0.0
	 *
	 * @var array $optional_data Optional properties in the payment data.
	 */
	private static $optional_data = array(
		'pmt_selleriban',
		'pmt_userlocale',
		'pmt_invoicefromseller',
		'pmt_paymentmethod',
		'pmt_buyeridentificationcode',
		'pmt_buyerphone',
		'pmt_buyeremail',
	);

	/**
	 * Mandatory row data.
	 *
	 * @since 2.0.0
	 *
	 * @var array $row_mandatory_data Mandatory properties for the payment data rows.
	 */
	private static $row_mandatory_data = array(
		'pmt_row_name',                  // Alphanumeric  max-length: 40    -.
		'pmt_row_desc',                  // Alphanumeric  max-length: 1000  min-length: 1.
		'pmt_row_quantity',              // Numeric       max-length: 10     min-length: 1.
		'pmt_row_deliverydate',          // Alphanumeric  max-length: 10    min-length: 10  dd.MM.yyyy.
		'pmt_row_price_gross',           // Alphanumeric  max-length: 17    min-length: 4   n,nn.
		'pmt_row_price_net',             // Alphanumeric  max-length: 17    min-length: 4   n,nn.
		'pmt_row_vat',                   // Alphanumeric  max-length: 5     min-length: 4   n,nn.
		'pmt_row_discountpercentage',    // Alphanumeric  max-length: 5     min-length: 4   n,nn.
		'pmt_row_type',                  // Numeric       max-length: 5     min-length: 1.
	);

	/**
	 * Optional row data.
	 *
	 * @since 2.0.0
	 *
	 * @var array $row_optional_data Optional properties for the payment data rows.
	 */
	private static $row_optional_data = array(
		'pmt_row_articlenr',
		'pmt_row_unit',
	);

	/**
	 * Hash data.
	 *
	 * @since 2.0.0
	 *
	 * @var array $hash_data Properties used for hashing.
	 */
	private static $hash_data = array(
		'pmt_action',
		'pmt_version',
		'pmt_selleriban',
		'pmt_id',
		'pmt_orderid',
		'pmt_reference',
		'pmt_duedate',
		'pmt_amount',
		'pmt_currency',
		'pmt_okreturn',
		'pmt_errorreturn',
		'pmt_cancelreturn',
		'pmt_delayedpayreturn',
		'pmt_escrow',
		'pmt_escrowchangeallowed',
		'pmt_invoicefromseller',
		'pmt_paymentmethod',
		'pmt_buyeridentificationcode',
		'pmt_buyername',
		'pmt_buyeraddress',
		'pmt_buyerpostalcode',
		'pmt_buyercity',
		'pmt_buyercountry',
		'pmt_deliveryname',
		'pmt_deliveryaddress',
		'pmt_deliverypostalcode',
		'pmt_deliverycity',
		'pmt_deliverycountry',
		'pmt_sellercosts',
	);

	/**
	 * Field filters.
	 *
	 * @since 2.0.0
	 *
	 * @var array $field_filters Field filters (min, max).
	 */
	private static $field_filters = array(
		'pmt_action'                  => array( 4, 50 ),
		'pmt_version'                 => array( 4, 4 ),
		'pmt_sellerid'                => array( 1, 15 ),
		'pmt_selleriban'              => array( 18, 30 ), // Optional.
		'pmt_id'                      => array( 1, 20 ),
		'pmt_orderid'                 => array( 1, 50 ),
		'pmt_reference'               => array( 3, 20 ), // > 100.
		'pmt_duedate'                 => array( 10, 10 ),
		'pmt_userlocale'              => array( 5, 5 ), // Optional.
		'pmt_amount'                  => array( 4, 17 ),
		'pmt_currency'                => array( 3, 3 ),
		'pmt_okreturn'                => array( 1, 200 ),
		'pmt_errorreturn'             => array( 1, 200 ),
		'pmt_cancelreturn'            => array( 1, 200 ),
		'pmt_delayedpayreturn'        => array( 1, 200 ),
		'pmt_escrow'                  => array( 1, 1 ),
		'pmt_escrowchangeallowed'     => array( 1, 1 ),
		'pmt_invoicefromseller'       => array( 1, 1 ), // Optional.
		'pmt_paymentmethod'           => array( 4, 4 ), // Optional.
		'pmt_buyeridentificationcode' => array( 9, 11 ), // Optional.
		'pmt_buyername'               => array( 1, 40 ),
		'pmt_buyeraddress'            => array( 1, 40 ),
		'pmt_buyerpostalcode'         => array( 1, 5 ),
		'pmt_buyercity'               => array( 1, 40 ),
		'pmt_buyercountry'            => array( 1, 2 ),
		'pmt_buyerphone'              => array( 0, 40 ), // Optional.
		'pmt_buyeremail'              => array( 0, 320 ), // Optional.
		'pmt_deliveryname'            => array( 1, 40 ),
		'pmt_deliveryaddress'         => array( 1, 40 ),
		'pmt_deliverypostalcode'      => array( 1, 5 ),
		'pmt_deliverycity'            => array( 1, 40 ),
		'pmt_deliverycountry'         => array( 1, 2 ),
		'pmt_sellercosts'             => array( 4, 17 ),
		'pmt_rows'                    => array( 1, 4 ),
		'pmt_row_name'                => array( 1, 40 ),
		'pmt_row_desc'                => array( 1, 1000 ),
		'pmt_row_quantity'            => array( 1, 10 ),
		'pmt_row_deliverydate'        => array( 10, 10 ),
		'pmt_row_price_gross'         => array( 4, 17 ),
		'pmt_row_price_net'           => array( 4, 17 ),
		'pmt_row_vat'                 => array( 4, 5 ),
		'pmt_row_discountpercentage'  => array( 4, 5 ),
		'pmt_row_type'                => array( 1, 5 ),
		'pmt_charset'                 => array( 1, 15 ),
		'pmt_charsethttp'             => array( 1, 15 ),
		'pmt_hashversion'             => array( 1, 10 ),
		'pmt_keygeneration'           => array( 1, 3 ),
	);

	/**
	 * Validate payment data.
	 *
	 * Checks if the payment data is valid.
	 *
	 * @since 2.0.0
	 *
	 * @throws WC_Gateway_Maksuturva_Exception If validation fails.
	 */
	protected function validate_payment_data() {
		$delivery_fields = array(
			'pmt_deliveryname'       => 'pmt_buyername',
			'pmt_deliveryaddress'    => 'pmt_buyeraddress',
			'pmt_deliverypostalcode' => 'pmt_buyerpostalcode',
			'pmt_deliverycity'       => 'pmt_buyercity',
			'pmt_deliverycountry'    => 'pmt_buyercountry',
		);

		foreach ( $delivery_fields as $k => $v ) {
			if ( ( ! isset( $this->payment_data[ $k ] ) ) || mb_strlen( trim( $this->payment_data[ $k ] ) ) == 0
			     || is_null( $this->payment_data[ $k ] )
			) {
				$this->payment_data[ $k ] = $this->payment_data[ $v ];
			}
		}

		foreach ( self::$mandatory_data as $field ) {
			if ( ! array_key_exists( $field, $this->payment_data ) ) {
				throw new WC_Gateway_Maksuturva_Exception(
					sprintf( 'Field "%s" is mandatory', $field ),
					self::EXCEPTION_CODE_FIELD_ARRAY_GENERATION_ERRORS
				);
			}
			if ( 'pmt_reference' === $field ) {
				if ( mb_strlen( (string) $this->payment_data['pmt_reference'] ) < 3 ) {
					throw new WC_Gateway_Maksuturva_Exception(
						sprintf( 'Field "%s" needs to have at least 3 digits', $field ),
						self::EXCEPTION_CODE_FIELD_ARRAY_GENERATION_ERRORS
					);
				}
			}
		}

		$count_rows = 0;
		if ( array_key_exists( 'pmt_rows_data', $this->payment_data ) ) {
			foreach ( $this->payment_data['pmt_rows_data'] as $row_data ) {
				$this->validate_payment_data_item( $row_data, $count_rows );
				$count_rows ++;
			}
		}

		if ( $count_rows != $this->payment_data['pmt_rows'] ) {
			throw new WC_Gateway_Maksuturva_Exception(
				sprintf(
					'The amount of items (%s) passed in field "pmt_rows" does not match with real amount(%s)',
					$this->payment_data['pmt_rows'],
					$count_rows
				),
				self::EXCEPTION_CODE_FIELD_ARRAY_GENERATION_ERRORS
			);
		}

		$this->filter_fields();
	}

	/**
	 * Validate a single payment data item.
	 *
	 * Checks if an payment data row item is valid.
	 *
	 * @param array $data       The data to validate.
	 * @param int   $count_rows Row count.
	 *
	 * @since 2.0.0
	 *
	 * @throws WC_Gateway_Maksuturva_Exception If validation fails.
	 */
	protected function validate_payment_data_item( array $data, $count_rows = null ) {
		foreach ( self::$row_mandatory_data as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				if ( 'pmt_row_price_gross' === $field && array_key_exists( 'pmt_row_price_net', $data ) ) {
					throw new WC_Gateway_Maksuturva_Exception( sprintf(
						'pmt_row_price_net%d and pmt_row_price_gross%d are both supplied, only one of them should be',
						$count_rows,
						$count_rows
					) );
				}
			} else {
				if ( 'pmt_row_price_gross' === $field && array_key_exists( 'pmt_row_price_net', $data ) ) {
					continue;
				} elseif ( 'pmt_row_price_net' === $field && array_key_exists( 'pmt_row_price_gross', $data ) ) {
					continue;
				}
				throw new WC_Gateway_Maksuturva_Exception( sprintf( 'Field %s%d is mandatory', $field, $count_rows ) );
			}
		}
	}

	/**
	 * Create the payment hash.
	 *
	 * Creates a hash of the payment data.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	protected function create_payment_hash() {
		$hash_data = array();
		foreach ( self::$hash_data as $field ) {
			switch ( $field ) {
				case 'pmt_selleriban':
				case 'pmt_invoicefromseller':
				case 'pmt_paymentmethod':
				case 'pmt_buyeridentificationcode':
					if ( in_array( $field, array_keys( $this->payment_data ) ) ) {
						$hash_data[ $field ] = $this->payment_data[ $field ];
					}
					break;
				default:
					$hash_data[ $field ] = $this->payment_data[ $field ];
					break;
			}
		}

		foreach ( $this->payment_data['pmt_rows_data'] as $i => $row ) {
			foreach ( $row as $k => $v ) {
				$hash_data[ $k . $i ] = $v;
			}
		}

		$data_hasher = new WC_Data_Hasher( $this->wc_gateway );
		return $data_hasher->create_hash( $hash_data );
	}

	/**
	 * Get the reference number.
	 *
	 * Turn the given reference number into a Svea reference number.
	 *
	 * @param int $number The reference number to apply to the Svea reference number.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 * @throws WC_Gateway_Maksuturva_Exception If number below 100.
	 */
	protected function get_pmt_reference_number( $number ) {
		if ( $number < 100 ) {
			throw new WC_Gateway_Maksuturva_Exception(
				'Cannot generate reference numbers for an ID smaller than 100',
				self::EXCEPTION_CODE_REFERENCE_NUMBER_UNDER_100
			);
		}
		$multiples = array( 7, 3, 1 );
		$str       = (string) $number;
		$sum       = 0;
		$j         = 0;
		for ( $i = mb_strlen( $str ) - 1; $i >= 0; $i -- ) {
			$sum += (int) mb_substr( $str, $i, 1 ) * (int) ( $multiples[ $j % 3 ] );
			$j ++;
		}
		$next_ten = ceil( (int) $sum / 10 ) * 10;

		return $str . (string) ( abs( $next_ten - $sum ) );
	}

	/**
	 * Verify status query response.
	 *
	 * Validates the consistency of maksuturva responses for a given status query.
	 *
	 * @param array $data The data to verify.
	 *
	 * @since 2.0.0
	 *
	 * @return boolean
	 */
	private function verify_status_query_response( $data ) {
		$hash_fields = array(
				'pmtq_action',
				'pmtq_version',
				'pmtq_sellerid',
				'pmtq_id',
				'pmtq_amount',
				'pmtq_returncode',
				'pmtq_returntext',
				'pmtq_sellercosts',
				'pmtq_paymentmethod',
				'pmtq_escrow',
				'pmtq_certification',
				'pmtq_paymentdate'
		);

		$optional_hash_fields = array(
				'pmtq_sellercosts',
				'pmtq_paymentmethod',
				'pmtq_escrow',
				'pmtq_certification',
				'pmtq_paymentdate'
		);


		$hash_data = array();
		foreach ( $hash_fields as $field ) {
			if ( ! isset( $data[ $field ] ) && ! in_array( $field, $optional_hash_fields ) ) {
				return false;
			} elseif ( ! isset( $data[ $field ] ) ) {
				continue;
			}
			// Test the validity of data as well, when the field exists.
			if ( isset( $this->status_query_data[ $field ] ) &&
			     ( $data[ $field ] != $this->status_query_data[ $field ] )
			) {
				return false;
			}
			$hash_data[ $field ] = $data[ $field ];
		}

		$data_hasher = new WC_Data_Hasher( $this->wc_gateway );
		if ( $data_hasher->create_hash( $hash_data ) != $data['pmtq_hash'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Filter fields.
	 *
	 * Traverses the payment data and filters/trims them as needed.
	 * If a required field is missing or with length below required, throws an exception.
	 *
	 * @since 2.0.0
	 *
	 * @throws WC_Gateway_Maksuturva_Exception If validation fails.
	 */
	private function filter_fields() {
		foreach ( $this->payment_data as $k => $value ) {
			if ( ( array_key_exists( $k, self::$field_filters ) && in_array( $k, self::$mandatory_data ) )
			     || array_key_exists( $k, self::$field_filters ) && in_array( $k, self::$row_mandatory_data )
			) {
				if ( mb_strlen( $value ) < self::$field_filters[ $k ][0] ) {
					throw new WC_Gateway_Maksuturva_Exception(
						sprintf( 'Field "%s" should be at least %d characters long.', $k,
						self::$field_filters[ $k ][0] )
					);
				}
				if ( mb_strlen( $value ) > self::$field_filters[ $k ][1] ) {
					// Auto trim.
					$this->payment_data[ $k ] = mb_substr( $value, 0, self::$field_filters[ $k ][1] );
					$this->payment_data[ $k ] = $this->encode( $this->payment_data[ $k ] );
				}
				continue;
			} elseif ( ( array_key_exists( $k, self::$field_filters )
			             && in_array( $k, self::$optional_data ) && mb_strlen( $value ) )
			           || ( array_key_exists( $k, self::$field_filters )
			                && in_array( $k, self::$row_optional_data ) && mb_strlen( $value ) )
			) {
				if ( mb_strlen( $value ) < self::$field_filters[ $k ][0] ) {
					throw new WC_Gateway_Maksuturva_Exception(
						sprintf( 'Field "%s" should be at least %d characters long.', $k,
						self::$field_filters[ $k ][0] )
					);
				}
				if ( mb_strlen( $value ) > self::$field_filters[ $k ][1] ) {
					// Auto trim.
					$this->payment_data[ $k ] = mb_substr( $value, 0, self::$field_filters[ $k ][1] );
					$this->payment_data[ $k ] = $this->encode( $this->payment_data[ $k ] );
				}
				continue;
			}
		}
		foreach ( $this->payment_data['pmt_rows_data'] as $i => $p ) {
			// Putting desc or title to not be blank.
			if ( array_key_exists( 'pmt_row_name', $p ) && array_key_exists( 'pmt_row_desc', $p ) ) {
				if ( ! trim( $p['pmt_row_name'] ) ) {
					$this->payment_data['pmt_rows_data'][ $i ]['pmt_row_name'] = $p['pmt_row_name'] = $p['pmt_row_desc'];
				} elseif ( ! trim( $p['pmt_row_desc'] ) ) {
					$this->payment_data['pmt_rows_data'][ $i ]['pmt_row_desc'] = $p['pmt_row_desc'] = $p['pmt_row_name'];
				}
			}
			foreach ( $p as $k => $value ) {
				if ( ( array_key_exists( $k, self::$field_filters ) && in_array( $k, self::$mandatory_data ) )
				     || array_key_exists( $k, self::$field_filters ) && in_array( $k, self::$row_mandatory_data )
				) {
					if ( mb_strlen( $value ) < self::$field_filters[ $k ][0] ) {
						throw new WC_Gateway_Maksuturva_Exception( sprintf(
							'Field "%s" should be at least %d characters long.',
							$k,
							self::$field_filters[ $k ][0]
						) );
					}
					if ( mb_strlen( $value ) > self::$field_filters[ $k ][1] ) {
						// Auto trim.
						$this->payment_data['pmt_rows_data'][ $i ][ $k ] = mb_substr(
							$value,
							0,
							self::$field_filters[ $k ][1]
						);
						$this->payment_data['pmt_rows_data'][ $i ][ $k ] = $this->encode(
							$this->payment_data['pmt_rows_data'][ $i ][ $k ]
						);
					}
					continue;
				} elseif ( ( array_key_exists( $k, self::$field_filters )
				             && in_array( $k, self::$optional_data ) && mb_strlen( $value ) )
				           || ( array_key_exists( $k, self::$field_filters )
				                && in_array( $k, self::$row_optional_data ) && mb_strlen( $value ) )
				) {
					if ( mb_strlen( $value ) < self::$field_filters[ $k ][0] ) {
						throw new WC_Gateway_Maksuturva_Exception( sprintf(
							'Field "%s" should be at least %d characters long.',
							$k,
							self::$field_filters[ $k ][0]
						) );
					}
					if ( mb_strlen( $value ) > self::$field_filters[ $k ][1] ) {
						// Auto trim.
						$this->payment_data['pmt_rows_data'][ $i ][ $k ] = mb_substr(
							$value,
							0,
							self::$field_filters[ $k ][1]
						);
						$this->payment_data['pmt_rows_data'][ $i ][ $k ] = $this->encode(
							$this->payment_data['pmt_rows_data'][ $i ][ $k ]
						);
					}
					continue;
				}
			}
		}
	}

	/**
	 * Getter.
	 *
	 * Magic get for fetching payment data fields.
	 *
	 * @param string $name The payment data key to get.
	 *
	 * @since 2.0.0
	 *
	 * @return mixed|null
	 */
	public function __get( $name ) {
		if ( in_array( $name, self::$mandatory_data, true )
		     || in_array( $name, self::$optional_data, true ) || 'pmt_rows_data' === $name
		) {
			return $this->payment_data[ $name ];
		}

		return null;
	}

	/**
	 * Status query.
	 *
	 * Perform a status query to maksuturva's server using the current payment data.
	 *
	 * <code>
	 * array(
	 *        "pmtq_action",
	 *        "pmtq_version",
	 *        "pmtq_sellerid",
	 *        "pmtq_id",
	 *        "pmtq_resptype",
	 *        "pmtq_return",
	 *        "pmtq_hashversion",
	 *        "pmtq_keygeneration"
	 * );
	 * </code>
	 *
	 * The return data is an array if the order is successfully organized;
	 * Otherwise, possible situations of errors:
	 *
	 * 1) Exceptions in case of not having curl in PHP - exception
	 * 2) Network problems (cannot connect, etc) - exception
	 * 3) Invalid returned data (hash or consistency) - return false
	 *
	 * @param array $data Configuration values to be used.
	 *
	 * @since 2.0.0
	 *
	 * @return array|bool
	 * @throws WC_Gateway_Maksuturva_Exception If curl not found, or failure to communicate with Svea.
	 */
	public function status_query( $data = array() ) {
		if ( ! function_exists( 'curl_init' ) ) {
			throw new WC_Gateway_Maksuturva_Exception(
				'cURL is needed in order to communicate with the maksuturva server. Check your PHP installation.',
				self::EXCEPTION_CODE_PHP_CURL_NOT_INSTALLED
			);
		}
		$default_fields = array(
			'pmtq_action'        => 'PAYMENT_STATUS_QUERY',
			'pmtq_version'       => '0005',
			'pmtq_sellerid'      => $this->payment_data['pmt_sellerid'],
			'pmtq_id'            => $this->payment_data['pmt_id'],
			'pmtq_resptype'      => 'XML',
			'pmtq_return'        => '',
			'pmtq_hashversion'   => $this->payment_data['pmt_hashversion'],
			'pmtq_keygeneration' => $this->payment_data['pmt_keygeneration'], 
			"req_ts_ms"          => \DateTime::createFromFormat('U.u', microtime(TRUE))->format('Y-m-d H:i:s:u')
		);
		// Overrides with user-defined fields.
		$this->status_query_data = array_merge( $default_fields, $data );
		// Last step: the hash is placed correctly.
		$hash_fields = array(
			'pmtq_action',
			'pmtq_version',
			'pmtq_sellerid',
			'pmtq_id',
		);
		$hash_data   = array();
		foreach ( $hash_fields as $field ) {
			$hash_data[ $field ] = $this->status_query_data[ $field ];
		}

		$data_hasher = new WC_Data_Hasher( $this->wc_gateway );
		$this->status_query_data['pmtq_hash'] = $data_hasher->create_hash( $hash_data );

		// Now the request is made to maksuturva.
		$request = curl_init( $this->base_url_status_query );
		curl_setopt( $request, CURLOPT_HEADER, 0 );
		curl_setopt( $request, CURLOPT_FRESH_CONNECT, 1 );
		curl_setopt( $request, CURLOPT_FORBID_REUSE, 1 );
		curl_setopt( $request, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $request, CURLOPT_POST, 1 );
		curl_setopt( $request, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $request, CURLOPT_CONNECTTIMEOUT, 120 );
		curl_setopt( $request, CURLOPT_USERAGENT, WC_Utils_Maksuturva::get_user_agent() );
		curl_setopt( $request, CURLOPT_POSTFIELDS, $this->status_query_data );
		$res = curl_exec( $request );

		if ( false === $res ) {
			throw new WC_Gateway_Maksuturva_Exception(
				'Failed to communicate with Svea Payments API. Please check the network connection.'
			);
		}
		curl_close( $request );
		// We will not rely on xml parsing - instead,
		// the fields are going to be collected by means of regular expression.
		$parsed_response = array();
		$response_fields = array(
			'pmtq_action',
			'pmtq_version',
			'pmtq_sellerid',
			'pmtq_id',
			'pmtq_amount',
			'pmtq_returncode',
			'pmtq_returntext',
			'pmtq_trackingcodes',
			'pmtq_sellercosts',
			'pmtq_invoicingfee',
			'pmtq_orderid',
			'pmtq_paymentmethod',
			'pmtq_escrow',
			'pmtq_certification',
			'pmtq_paymentdate',
			'pmtq_buyername',
			'pmtq_buyeraddress1',
			'pmtq_buyeraddress2',
			'pmtq_buyerpostalcode',
			'pmtq_buyercity',
			'pmtq_hash',
		);
		foreach ( $response_fields as $field ) {
			preg_match( "/<$field>(.*)?<\/$field>/i", $res, $matches );
			if ( 2 === count( $matches ) ) {
				$parsed_response[ $field ] = $matches[1];
			}
		}
		// Do not provide a response which is not valid.
		if ( ! $this->verify_status_query_response( $parsed_response ) ) {
			throw new WC_Gateway_Maksuturva_Exception(
				'The authenticity of the answer could not be verified. Hashes did not match.',
				self::EXCEPTION_CODE_HASHES_DONT_MATCH
			);
		}

		// Check that pmt_orderid exists in the response
		if ( empty($parsed_response['pmtq_orderid']) ) {
			throw new WC_Gateway_Maksuturva_Exception(
				'Status query response order id does not exist for the order ' . $this->payment_data['pmt_orderid'] . 
				'. Unable to verify the response.',
				self::EXCEPTION_CODE_DATA_MISMATCH
			);
		}

		// Validate order to match payment data
		if ( !($this->payment_data['pmt_orderid'] === $parsed_response['pmtq_orderid']) ) {
			throw new WC_Gateway_Maksuturva_Exception(
				'Status query response order id does not match the requested payment order id. ' . 
				$this->payment_data['pmt_orderid'] . ' vs response ' . $parsed_response['pmtq_orderid'],
				self::EXCEPTION_CODE_DATA_MISMATCH
			);
		}

		// Check payment total and seller costs
		$pmtq_amount = floatval(str_replace(',', '.', $parsed_response["pmtq_amount"]) );
		if ( empty($parsed_response["pmtq_sellercosts"]) )
			$pmtq_sellercosts = floatval(str_replace(',', '.', "0,00") );
		else
			$pmtq_sellercosts = floatval(str_replace(',', '.', $parsed_response["pmtq_sellercosts"]) );

		if ( abs(floatval(str_replace(',', '.', $this->payment_data['pmt_sellercosts'])) - $pmtq_sellercosts) > 1.00 ) {
			throw new WC_Gateway_Maksuturva_Exception(
				'Status query response seller costs does not match the requested payment seller costs. ' . 
				$this->payment_data['pmt_sellercosts'] . ' vs response ' . $parsed_response['pmtq_sellercosts'],
				self::EXCEPTION_CODE_DATA_MISMATCH
			);
		}

		if ( abs(floatval(str_replace(',', '.', $this->payment_data['pmt_amount'])) - $pmtq_amount) > 5.00 ) {
			throw new WC_Gateway_Maksuturva_Exception(
				'Status query response amount does not match the requested payment amount. Amount ' . 
				$this->payment_data['pmt_amount'] . ' vs response ' . $parsed_response['pmtq_amount'],
				self::EXCEPTION_CODE_DATA_MISMATCH
			);
		}

		// Return the response - verified.
		return $parsed_response;
	}

	/**
	 * Get fields as an array.
	 *
	 * Turns the payment data into a single level associative array.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 * @throws WC_Gateway_Maksuturva_Exception If reference number is invalid.
	 */
	public function get_field_array() {
		$this->validate_payment_data();
		$return_array                        = array();
		$this->payment_data['pmt_reference'] = $this->get_pmt_reference_number( $this->payment_data['pmt_reference'] );
		foreach ( $this->payment_data as $key => $data ) {
			if ( 'pmt_rows_data' === $key ) {
				$row_count = 1;
				foreach ( $data as $row ) {
					foreach ( $row as $k => $v ) {
						$return_array[ $this->http_encode( $k . $row_count ) ] = $this->http_encode( $v );
					}
					$row_count ++;
				}
			} else {
				$return_array[ $this->http_encode( $key ) ] = $this->http_encode( $data );
			}
		}
		$return_array[ $this->http_encode( 'pmt_hash' ) ] = $this->encode( $this->create_payment_hash(),
		$this->charset );

		return $return_array;
	}

	/**
	 * Encode data.
	 *
	 * Encodes the data to the defined "http encoding".
	 *
	 * @param string      $data          The data to encode.
	 * @param null|string $from_encoding The "from" encoding.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function http_encode( $data, $from_encoding = null ) {
		return $this->encode( $data, $this->charset_http, $from_encoding );
	}

	/**
	 * Encodes data.
	 *
	 * By default both `to` and `from` encoding is the one defined in `$this->charset`.
	 *
	 * @param string      $data          The data to encode.
	 * @param string|null $to_encoding   The "to" encoding.
	 * @param string|null $from_encoding The "from" encoding.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function encode( $data, $to_encoding = null, $from_encoding = null ) {
		if ( is_null( $to_encoding ) ) {
			$to_encoding = $this->charset;
		}
		if ( is_null( $from_encoding ) ) {
			$from_encoding = $this->charset;
		}

		return mb_convert_encoding( $data, $to_encoding, $from_encoding );
	}

	/**
	 * Get payment URL.
	 *
	 * Returns the payment gateway base URL.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_payment_url() {
		return $this->base_url;
	}

	/**
	 * Get status query URL.
	 *
	 * Returns the base URL for the status query.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_status_query_url() {
		return $this->base_url_status_query;
	}

	/**
	 * Set base URL.
	 *
	 * Sets the base URL for the gateway.
	 *
	 * @since 2.0.0
	 *
	 * @param string $base_url The URL to set.
	 */
	public function set_base_url( $base_url ) {
		$this->base_url              = rtrim( $base_url, '/' ) . self::ROUTE_PAYMENT;
		$this->base_url_status_query = rtrim( $base_url, '/' ) . self::ROUTE_STATUS_QUERY;
	}

	/**
	 * Set encoding.
	 *
	 * Sets the encoding for the gateway.
	 *
	 * @param string $encoding The encoding.
	 *
	 * @since 2.0.0
	 */
	public function set_encoding( $encoding ) {
		$this->charset      = $encoding;
		$this->charset_http = $encoding;
	}

	/**
	 * Set id prefix.
	 *
	 * Sets the prefix to be used for the payment ID.
	 *
	 * @param string $prefix The prefix.
	 *
	 * @since 2.0.0
	 */
	public function set_payment_id_prefix( $prefix ) {
		$this->pmt_id_prefix = $prefix;
	}

	/**
	 * Set payment data.
	 *
	 * Sets the payment data to be sent to the Svea payments gateway.
	 *
	 * @param array $payment_data The payment data.
	 *
	 * @since 2.0.0
	 *
	 * @throws WC_Gateway_Maksuturva_Exception If hash algorithm is not supported.
	 */
	public function set_payment_data( array $payment_data ) {

		foreach ( $payment_data as $key => $value ) {
			if ( 'pmt_rows_data' === $key ) {
				foreach ( $value as $k => $v ) {
					$this->payment_data[ $key ][ $k ] = str_replace( '&amp;', '', $v );
				}
			} else {
				$this->payment_data[ $key ] = str_replace( '&amp;', '', $value );
			}
		}

		$this->payment_data['server_info'] = WC_Utils_Maksuturva::get_user_agent();
		$this->payment_data['req_ts_ms'] = \DateTime::createFromFormat('U.u', microtime(TRUE))->format('Y-m-d H:i:s:u');
		$this->payment_data['pmt_hashversion'] = WC_Data_Hasher::get_hash_algorithm();
	}
}
