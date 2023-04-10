<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 6.x, 7.x
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

<div>
	<div class="svea-payment-collated-title">
		<?php echo $method_group1['title']; ?>
	</div>
	<?php
		foreach ( $method_group1['methods'] as $payment_method ) { 
	?>
		<div class="svea-payment-method-select" style="clear: both;">
			<label for="<?php echo $payment_method_select_id; ?>-<?php echo $payment_method['code']; ?>">
				<img
					alt="<?php echo $payment_method['displayname']; ?>"
					src="<?php echo $payment_method['imageurl']; ?>"
				/>
			</label>
			<?php 
				foreach ( $payment_method_handling_costs as $handling_cost ) {
					if ( $handling_cost['payment_method_type'] === $payment_method['code'] ) {
						echo '<div class="handling-cost-amount">+' . WC_Utils_Maksuturva::filter_price( $handling_cost['handling_cost_amount'] ) . ' ' . $currency_symbol . '</div>';
						break;
					}
				}
			?>
			<input
				class="input-radio svea-payment-method-select-radio"
				id="<?php echo $payment_method_select_id; ?>-<?php echo $payment_method['code']; ?>"
				name="<?php echo $payment_method_select_id; ?>"
				type="radio"
				value="<?php echo $payment_method['code']; ?>"
			/>
		</div>
	<?php } ?>

	<div class="svea-payment-collated-title">
			<?php echo $method_group2['title']; ?>
	</div>
	<?php
		foreach ( $method_group2['methods'] as $payment_method ) { 
	?>
		<div class="svea-payment-method-select" style="clear: both;">
			<label for="<?php echo $payment_method_select_id; ?>-<?php echo $payment_method['code']; ?>">
				<img
					alt="<?php echo $payment_method['displayname']; ?>"
					src="<?php echo $payment_method['imageurl']; ?>"
				/>
			</label>
			<?php 
				foreach ( $payment_method_handling_costs as $handling_cost ) {
					if ( $handling_cost['payment_method_type'] === $payment_method['code'] ) {
						echo '<div class="handling-cost-amount">+' . WC_Utils_Maksuturva::filter_price( $handling_cost['handling_cost_amount'] ) . ' ' . $currency_symbol . '</div>';
						break;
					}
				}
			?>
			<input
				class="input-radio svea-payment-method-select-radio"
				id="<?php echo $payment_method_select_id; ?>-<?php echo $payment_method['code']; ?>"
				name="<?php echo $payment_method_select_id; ?>"
				type="radio"
				value="<?php echo $payment_method['code']; ?>"
			/>
		</div>
	<?php } ?>

	<div class="svea-payment-collated-title">
			<?php echo $method_group3['title']; ?>
	</div>
	<?php
		foreach ( $method_group3['methods'] as $payment_method ) { 
	?>
		<div class="svea-payment-method-select" style="clear: both;">
			<label for="<?php echo $payment_method_select_id; ?>-<?php echo $payment_method['code']; ?>">
				<img
					alt="<?php echo $payment_method['displayname']; ?>"
					src="<?php echo $payment_method['imageurl']; ?>"
				/>
			</label>
			<?php 
				foreach ( $payment_method_handling_costs as $handling_cost ) {
					if ( $handling_cost['payment_method_type'] === $payment_method['code'] ) {
						echo '<div class="handling-cost-amount">+' . WC_Utils_Maksuturva::filter_price( $handling_cost['handling_cost_amount'] ) . ' ' . $currency_symbol . '</div>';
						break;
					}
				}
			?>
			<input
				class="input-radio svea-payment-method-select-radio"
				id="<?php echo $payment_method_select_id; ?>-<?php echo $payment_method['code']; ?>"
				name="<?php echo $payment_method_select_id; ?>"
				type="radio"
				value="<?php echo $payment_method['code']; ?>"
			/>
		</div>
	<?php } ?>

	<div class="svea-payment-collated-title">
			<?php echo $method_group4['title']; ?>
	</div>
	<?php
		foreach ( $method_group4['methods'] as $payment_method ) { 
	?>
		<div class="svea-payment-method-select" style="clear: both;">
			<label for="<?php echo $payment_method_select_id; ?>-<?php echo $payment_method['code']; ?>">
				<img
					alt="<?php echo $payment_method['displayname']; ?>"
					src="<?php echo $payment_method['imageurl']; ?>"
				/>
			</label>
			<?php 
				foreach ( $payment_method_handling_costs as $handling_cost ) {
					if ( $handling_cost['payment_method_type'] === $payment_method['code'] ) {
						echo '<div class="handling-cost-amount">+' . WC_Utils_Maksuturva::filter_price( $handling_cost['handling_cost_amount'] ) . ' ' . $currency_symbol . '</div>';
						break;
					}
				}
			?>
			<input
				class="input-radio svea-payment-method-select-radio"
				id="<?php echo $payment_method_select_id; ?>-<?php echo $payment_method['code']; ?>"
				name="<?php echo $payment_method_select_id; ?>"
				type="radio"
				value="<?php echo $payment_method['code']; ?>"
			/>
		</div>
	<?php } ?>
</div>

<p><?php if (!empty($terms['text']) ) { echo $terms['text']; ?>  
(<a href="<?php echo $terms['url']; ?>" target="_blank">PDF</a>)</p>
<?php } ?>

<div style="clear: both;"></div>
