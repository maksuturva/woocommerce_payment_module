<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 4.x
 * Plugin developed for Svea
 * Last update: 14/01/2021
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

/**
 * Variables defined.
 *
 * @var string $currency_symbol Symbol for inidicating the currency
 * @var array $payment_method_handling_costs Configured payment method handling costs.
 * @var string $payment_method_select_id The name to use for payment method select field.
 * @var array $payment_methods Available payment methods.
 * @var array $terms Terms.
 *
 * @since 2.1.5
 */

?>

<div>
	<?php
	foreach ( $payment_methods as $payment_method ) {
		// only EEAC is accepted in this section.
		if ( 'EEAC' === $payment_method['code'] ) {
			?>
		<div class="svea-payment-method-select" style="clear: both;">
			<input
				class="input-radio svea-payment-method-select-radio"
				id="<?php echo esc_attr( $payment_method_select_id ); ?>-<?php echo esc_attr( $payment_method['code'] ); ?>"
				name="<?php echo esc_attr( $payment_method_select_id ); ?>"
				type="radio"
				value="<?php echo esc_attr( $payment_method['code'] ); ?>"
			/>
			<label class="label-eeac" for="<?php echo esc_attr( $payment_method_select_id ); ?>-<?php echo esc_attr( $payment_method['code'] ); ?>">
				<img
					alt="<?php echo esc_attr( $payment_method['displayname'] ); ?>"
					src="<?php echo esc_url( $payment_method['imageurl'] ); ?>"
				/>
			</label>
			<?php
			foreach ( $payment_method_handling_costs as $handling_cost ) {
				if ( $handling_cost['payment_method_type'] === $payment_method['code'] ) {
					echo wp_kses_post( '<div class="handling-cost-amount">+' . WC_Utils_Maksuturva::filter_price( $handling_cost['handling_cost_amount'] ) . $currency_symbol . '</div>' );
					break;
				}
			}
			?>

		</div>
			<?php
		}
	}
	?>
</div>

<p>
<?php
if ( ! empty( $terms['text'] ) ) {
	echo esc_html( $terms['text'] );
	?>
(<a href="<?php echo esc_url( $terms['url'] ); ?>" target="_blank">PDF</a>)
<?php } ?></p>

<div style="clear: both;"></div>

<script>
(function() {
	var radioButtons = document.querySelectorAll( '.svea-payment-method-select-radio' );

	for( var i = 0; i < radioButtons.length; ++i ) {
		radioButtons[i].addEventListener('click', function() {
			document.querySelector( 'body' ).dispatchEvent( new CustomEvent('update_checkout') );
		});
	}
})();
</script>

<style>
	#payment .payment_methods li .svea-payment-method-select-radio:focus {
		outline: none;
	}
	#payment .payment_methods li .svea-payment-method-select-radio {
		width: 5%;
	}
	#payment .payment_methods li .label-eeac {
		width: 90%;
		float: right;
	}
	#payment .payment_methods li .svea-payment-method-select .handling-cost-amount {
		font-size: 0.8em;
		margin-bottom: 0.1em;
		margin-top: 0.1em;
		text-shadow: -1px 0 #ffffff, 0 1px #ffffff, 1px 0 #ffffff, 0 -1px #ffffff;
	}

	#payment .payment_methods li .svea-payment-method-select img {	
		float: none;
		max-height: 12em;
	}
</style>
