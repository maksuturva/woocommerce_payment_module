<?php
/**
 * WooCommerce Svea Payment Gateway
 *
 * @package WooCommerce Svea Payment Gateway
 */

/**
 * Svea Payment Gateway Plugin for WooCommerce 2.x, 3.x
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
 * @var WC_Gateway_Maksuturva $this The context from where this template was called from.
 *
 * @since 2.0.0
 */
?>
<div class="inline error">
	<p>
		<?php echo esc_attr( __( 'Svea does not support your store currency. Only EUR is supported.',
		$this->td ) ); ?>
	</p>
</div>
