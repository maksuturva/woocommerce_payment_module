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

?>

<script>
(function() {
	var radioButtons = document.querySelectorAll( '.svea-payment-method-select-radio' );

	for( var i = 0; i < radioButtons.length; ++i ) {
		radioButtons[i].addEventListener('click', function() {
			document.querySelector( 'body' ).dispatchEvent( new CustomEvent('update_checkout') );
		});
	}

	jQuery(function($) {
		$('body').on('updated_checkout', function() {
			$('.svea-payment-method-select-radio:checked').parent().addClass('checked');
			$('.svea-payment-method-select-radio:not(:checked)').parent().removeClass('checked');
        });
	});
})();
</script>

<style>
	.payment_box.payment_method_WC_Gateway_Svea_Credit_Card_And_Mobile div,
	.payment_box.payment_method_WC_Gateway_Svea_Invoice_And_Hire_Purchase div,
	.payment_box.payment_method_WC_Gateway_Svea_Online_Bank_Payments div,
	.payment_box.payment_method_WC_Gateway_Svea_Other_Payments div,
	.payment_box.payment_method_WC_Gateway_Svea_Estonia_Payments div {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(100px, 30%));
		grid-gap: 1rem;
	}

	#payment .payment_methods li .svea-payment-method-select {
		display: flex;
		flex-direction: column;
		justify-content: center;
		grid-gap: 0;
	}

	#payment .payment_methods li .svea-payment-method-select label {
		display: flex;
		justify-content: center;
		align-items: center;
		height: 80px;
		padding: 10px;
		background: #fff;
		border: 1px solid rgb(0, 0, 0, 0);
		box-shadow: 0 0 14px 0 rgb(0, 0, 0, 0.1);
		transition: box-shadow 0.3s, width 0.35s, margin 0.35s, height 0.35s;
		cursor: pointer;
	}

	#payment .payment_methods li .svea-payment-method-select label:hover {
   		box-shadow: 0 0 12px 0 rgb(0, 0, 0, 0.25);
	}

	#payment .payment_methods li .svea-payment-method-select.checked label {
   		box-shadow: 0 0 10px 0 rgb(0, 0, 0, 0.4);
	}

	#payment .payment_methods li .svea-payment-method-select-radio {
		display: none;
		margin: auto;
	}

	#payment .payment_methods li .svea-payment-method-select-radio:focus {
		outline: none;
	}

	#payment .payment_methods li .svea-payment-method-select .handling-cost-amount {
		font-size: 0.8em;
		margin-bottom: 0.1em;
		margin-top: 0.1em;
		text-shadow: -1px 0 #ffffff, 0 1px #ffffff, 1px 0 #ffffff, 0 -1px #ffffff;
	}

	#payment .payment_methods li .svea-payment-method-select img {
		max-height: 100%;
	}
</style>
