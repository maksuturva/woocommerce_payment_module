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

/**
 * Variables defined.
 *
 * @var string $currency_symbol Symbol for inidicating the currency
 * @var array $payment_method_handling_costs Configured payment method handling costs.
 * @var string $payment_method_select_id The name to use for payment method select field.
 * @var array $payment_methods Available payment methods.
 * @var array $terms Terms.
 *
 * @since 2.1.3
 */

?>

<?php foreach ( $payment_methods as $payment_method ) { ?>
	<div style="clear: both;">
		<input
			class="input-radio svea-payment-method-select-radio"
			id="<?php echo $payment_method_select_id; ?>-<?php echo $payment_method['code']; ?>"
			name="<?php echo $payment_method_select_id; ?>"
			type="radio"
			value="<?php echo $payment_method['code']; ?>"
		/>
		<label for="<?php echo $payment_method_select_id; ?>-<?php echo $payment_method['code']; ?>">
			<img
				alt="<?php echo $payment_method['displayname']; ?>"
				src="<?php echo $payment_method['imageurl']; ?>"
			/>
		</label>
		<?php 
			foreach ( $payment_method_handling_costs as $handling_cost ) {
				if ( $handling_cost['payment_method_type'] === $payment_method['code'] ) {
					echo $handling_cost['handling_cost_amount'] . ' ' . $currency_symbol;
					break;
				}
			}
		?>
	</div>
<?php } ?>

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

<p><a href="<?php echo $terms['url']; ?>"><?php echo $terms['text']; ?></a></p>

<div style="clear: both;"></div>
