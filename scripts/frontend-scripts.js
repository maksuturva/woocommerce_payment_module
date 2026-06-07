/* scripts/frontend-scripts.js */
(function ($) {
	'use strict';
	$(document).ready(function () {
		// Toggle checked classes and trigger checkout update
		$(document).on('click', '.svea-payment-method-select-radio', function () {
			$('.svea-payment-method-select-radio:checked').parent().addClass('checked');
			$('.svea-payment-method-select-radio:not(:checked)').parent().removeClass('checked');
			$('body').trigger('update_checkout');
		});

		// Re-apply checked classes after the WooCommerce checkout finishes updating
		$('body').on('updated_checkout', function () {
			$('.svea-payment-method-select-radio:checked').parent().addClass('checked');
			$('.svea-payment-method-select-radio:not(:checked)').parent().removeClass('checked');
		});

		// Auto-select the only payment method available in a group if it is the only option
		$('body').on('payment_method_selected', function () {
			if (($('div.payment_method_WC_Gateway_Svea_Credit_Card_And_Mobile > div > div > input').length === 1) &&
				($('form[name="checkout"] input[name="payment_method"]:checked').val() === "Sveapafi_Gateway_Svea_Credit_Card_And_Mobile")) {
				console.log("Autoselecting the only payment method that is available in this payment group.");
				$('div.payment_method_WC_Gateway_Svea_Invoice_And_Hire_Purchase > div > div > input').prop("checked", true);
				$('body').trigger('update_checkout');
			}
		});
	});
})(jQuery);
