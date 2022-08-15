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
	<?php echo __( 'Pay with Svea', 'wc-maksuturva' ); ?>
	<?php 
	/*
		foreach ( $payment_method_handling_costs as $handling_cost ) {
			if ( $handling_cost['payment_method_type'] === 'outbound' ) {
				echo '<div class="handling-cost-amount">+' . WC_Utils_Maksuturva::filter_price( $handling_cost['handling_cost_amount'] ) . ' ' . $currency_symbol . '</div>';
				break;
			}
		}
		*/
	?>
</div>

<p><?php if (!empty($terms['text']) ) { echo $terms['text']; ?>  
(<a href="<?php echo $terms['url']; ?>" target="_blank">PDF</a>)</p>
<?php } ?>

<div style="clear: both;"></div>
