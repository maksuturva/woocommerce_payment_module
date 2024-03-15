<?php
/**
 * WooCommerce Svea Payments Gateway
 *
 * @package WooCommerce Svea Payments Gateway
 */

/**
 * Svea Payments Gateway Plugin for WooCommerce 7.x, 8.x
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
 * @var WC_Gateway_Maksuturva $this                The context where this template is called from.
 * @var WC_Order              $order               The order.
 * @var array                 $data                The data to be sent to Svea.
 * @var string                $payment_gateway_url The gateway URL.
 *
 * @since 2.0.0
 */
?>

<?php
$msg = __( 'Thank you for your order. You will now be redirected to Svea to complete the payment.', 'wc-maksuturva' );
wc_enqueue_js( '
		$.blockUI({
				message: "' . esc_js( $msg ) . '",
				baseZ: 99999,
				overlayCSS: {
					background: "#fff",
					opacity: 0.6
				},
				css: {
					padding:        "20px",
					zindex:         "9999999",
					textAlign:      "center",
					color:          "#555",
					border:         "3px solid #aaa",
					backgroundColor:"#fff",
					cursor:         "wait",
					lineHeight:     "24px",
				}
			});
		jQuery("#maksuturva_payment_form .payment_buttons").hide();
		jQuery("#maksuturva_payment_form").submit();
	' );
?>

<form action="<?php echo esc_url( $payment_gateway_url ); ?>" method="post" id="maksuturva_payment_form" target="_top">
	<?php foreach ( $data as $key => $value ) : ?>
		<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"/>
	<?php endforeach; ?>
	<!-- Button Fallback -->
	<div class="payment_buttons">
		<a class="button cancel" href="<?php echo esc_url( $order->get_cancel_order_url() ); ?>">
			<?php echo esc_attr( __( 'Cancel order', 'wc-maksuturva' ) ); ?></a>
		<input type="submit" class="button alt" id="submit_maksuturva_payment_form"
			   value="<?php echo esc_attr( __( 'Pay for order', 'wc-maksuturva' ) ); ?>"/>
	</div>
</form>
