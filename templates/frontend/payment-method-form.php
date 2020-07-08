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
 * @var string $payment_method_select_id The name to use for payment method select field.
 * @var array $payment_methods Available payment methods.
 * @var array $terms Terms.
 *
 * @since 2.0.10
 */

?>

<?php foreach ($payment_methods as $payment_method) { ?>
	<div style="clear: both;">
		<input
			class="input-radio"
			id="<?php echo $payment_method_select_id; ?>-<?php echo $payment_method["code"]; ?>"
			name="<?php echo $payment_method_select_id; ?>"
			type="radio"
			value="<?php echo $payment_method["code"]; ?>"
		/>
		<label for="<?php echo $payment_method_select_id; ?>-<?php echo $payment_method["code"]; ?>">
			<img
				alt="<?php echo $payment_method["displayname"]; ?>"
				src="<?php echo $payment_method["imageurl"]; ?>"
			/>
		</label>
	</div>
<?php } ?>

<p><a href="<?php echo $terms['url']; ?>"><?php echo $terms['text']; ?></a></p>

<div style="clear: both;"></div>
