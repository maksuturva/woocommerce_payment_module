<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce
 * Plugin developed for Svea Payments Oy
 * Last update: 03/11/2024
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

/**
 * Class WC_Svea_Part_Payment_Calculator
 *
 * Handles loading of the part payment calculator
 *
 * @since 2.6.3
 */
class WC_Svea_Part_Payment_Calculator {

	/**
	 * Instance of WC_Gateway_Maksuturva
	 *
	 * @var WC_Gateway_Maksuturva
	 * @since 2.6.3
	 */
	protected $gateway;

	/**
	 * Constructor
	 *
	 * @param WC_Gateway_Maksuturva $gateway Maksuturva gateway instance.
	 * @since 2.6.3
	 */
	public function __construct( WC_Gateway_Maksuturva $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Load part payment calculator
	 *
	 * @param \WC_Product $product Product instance.
	 *
	 * @return void
	 * @since 2.6.3
	 */
	public function load( \WC_Product $product ) {
		$seller_id = $this->gateway->get_option( 'maksuturva_sellerid' );

		if ( ! $this->is_valid_product( $product ) || ! $seller_id ) {
			return;
		}

		$price = (float) wc_get_price_including_tax( $product );

		if ( $product->is_type( 'variable' ) ) {
			$variation_prices = $product->get_variation_prices( true );
			$price = (float) max( $variation_prices['price'] );
		}

		if ( ! $price || ! $this->should_display_calculator( $price ) ) {
			return;
		}

		$this->include_script( $seller_id, $product );
	}

	/**
	 * Include part payment calculator script
	 *
	 * @param string      $seller_id Seller id.
	 * @param \WC_Product $product Product instance.
	 *
	 * @return void
	 * @since 2.6.3
	 */
	protected function include_script( $seller_id, $product ) {
		$price_thresholds = $this->gateway->get_option( 'ppw_price_thresholds' );

		if ( $product->is_type( 'variable' ) ) {
			$variation_prices = $product->get_variation_prices( true );
			$price            = (float) max( $variation_prices['price'] );
		} else {
			$price = floatval( wc_get_price_including_tax( $product ) );
		}

		$params = array(
			'src'              => esc_url( 'https://payments.maksuturva.fi/tools/partpayment/partPayment.js' ),
			'class'            => 'svea-pp-widget-part-payment',
			'sellerid'         => $seller_id,
			'locale'           => explode( '_', get_user_locale() )[0],
			'price'            => $price,
			'maksuturva-host'  => $this->get_script_attr( 'partpayment_widget_use_test', 'yes' ) ? 'https://test1.maksuturva.fi' : '',
			'layout'           => $this->get_script_attr( 'partpayment_widget_mini', 'yes' ) ? 'mini' : '',
			'campaign-text-fi' => $this->get_script_attr( 'ppw_campaign_text_fi' ),
			'campaign-text-sv' => $this->get_script_attr( 'ppw_campaign_text_sv' ),
			'campaign-text-en' => $this->get_script_attr( 'ppw_campaign_text_en' ),
			'fallback-text-fi' => $this->get_script_attr( 'ppw_fallback_text_fi' ),
			'fallback-text-sv' => $this->get_script_attr( 'ppw_fallback_text_sv' ),
			'fallback-text-en' => $this->get_script_attr( 'ppw_fallback_text_en' ),
			'border-color'     => $this->get_script_attr( 'ppw_border_color' ),
			'text-color'       => $this->get_script_attr( 'ppw_text_color' ),
			'highlight-color'  => $this->get_script_attr( 'ppw_highlight_color' ),
			'active-color'     => $this->get_script_attr( 'ppw_active_color' ),
			'threshold-prices' => ! empty( $price_thresholds ) && $this->validate_price_threshold( $price_thresholds ) ? '[' . $price_thresholds . ']' : '',
		);

		$attrs = array();

		foreach ( $params as $key => $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			if ( $key !== 'src' && $key !== 'class' ) {
				$key = 'data-' . $key;
			}

			$attrs[ $key ] = $value;
		}

		wp_print_script_tag( $attrs );
	}

	/**
	 * Get script attribute
	 *
	 * @param string      $option Option name.
	 * @param string|bool $compare Compare value. Comparison is skipped if value is not provided
	 *
	 * @return array|bool|int|string
	 * @since 2.6.3
	 */
	protected function get_script_attr( $option, $compare = false ) {
		$value = $this->gateway->get_option( $option );

		if ( empty( $value ) ) {
			return false;
		}

		return $compare
			? $value === $compare
			: $value;
	}

	/**
	 * Check if product is valid
	 *
	 * @param \WC_Product $product Product instance.
	 *
	 * @return bool
	 * @since 2.6.3
	 */
	protected function is_valid_product( \WC_Product $product ) {
		return is_product() && ! empty( $product->get_price() );
	}

	/**
	 * Check if part payment calculator should be displayed
	 *
	 * @param float $price
	 *
	 * @return bool
	 * @since 2.6.1
	 */
	protected function should_display_calculator( float $price ): bool {
		$minThreshold = (float) $this->gateway->get_option( 'ppw_price_threshold_minimum' );
		$gateway      = new WC_Gateway_Maksuturva();
		$api          = new WC_Svea_Api_Request_Handler( $gateway );
		$plans        = $api->get_payment_plan_params();

		if (!empty($plans) && !empty($plans['campaigns'])) {
			$min = $plans['campaigns'][0]['FromAmount'];
			$max = $plans['campaigns'][0]['ToAmount'];

			foreach ($plans['campaigns'] as $plan) {
				if ($plan['FromAmount'] < $min) {
					$min = $plan['FromAmount'];
				}
				if ($plan['ToAmount'] > $max) {
					$max = $plan['ToAmount'];
				}
			}
		}

		wp_localize_script(
			'svea-part-payment-calculator-variable-product',
			'svea_ppc_vp_params',
			[
				'minThreshold' => empty($minThreshold) ? null : $minThreshold,
				'plansMin' => empty($min) ? null : (float)$min,
				'plansMax' => empty($max) ? null : (float)$max,
			]
		);

		if ( empty( $minThreshold ) ) {
			return $this->price_has_payment_plan_available( $price, $plans );
		}

		if ( $price < $minThreshold ) {
			return false;
		}

		if ( ! empty( $plans ) ) {
			foreach ( $plans['campaigns'] as $plan ) {
				if ( $price <= $plan['ToAmount'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if price has payment plan available
	 *
	 * @param float $price
	 *
	 * @return bool
	 * @since 2.6.1
	 */
	protected function price_has_payment_plan_available( float $price, $plans ) {
		if ( empty( $plans ) || empty( $plans['campaigns'] ) ) {
			return false;
		}

		foreach ( $plans['campaigns'] as $plan ) {
			if ( $price >= $plan['FromAmount'] && $price <= $plan['ToAmount'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check price thresholds configuration value
	 *
	 * @since 2.3.0
	 */
	private function validate_price_threshold( $value ) {
		return substr_count( $value, '[' ) === substr_count( $value, ']' );
	}
}
