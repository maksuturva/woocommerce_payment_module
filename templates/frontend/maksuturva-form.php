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

/**
 * Variables defined.
 *
 * @var WC_Gateway_Maksuturva $this                The context where this template is called from.
 * @var WC_Order              $order               The order.
 * @var array                 $data                The data to be sent to Maksuturva.
 * @var string                $payment_gateway_url The gateway URL.
 *
 * @since 2.0.0
 */
?>

<form action="<?php echo esc_url( $payment_gateway_url ); ?>" method="post" id="maksuturva_payment_form" target="_top">
	<?php foreach ( $data as $key => $value ) : ?>
		<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"/>
	<?php endforeach; ?>
	<!-- Button Fallback -->
	<div class="payment_buttons">
		<input type="submit" class="button alt" id="submit_maksuturva_payment_form"
		       value="<?php echo esc_attr( __( 'Maksuturva', $this->td ) ); ?>"/>
		<a class="button cancel" href="<?php echo esc_url( $order->get_cancel_order_url() ); ?>">
			<?php echo esc_attr( __( 'Cancel order', $this->td ) ); ?></a>
	</div>

</form>
