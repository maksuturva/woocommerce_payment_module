/* scripts/admin-scripts.js */
(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', function () {
		var paymentMethodHandlingCostTable = document.querySelector('#payment_method_handling_cost_table');
		if (!paymentMethodHandlingCostTable) {
			return;
		}

		var tableBody = paymentMethodHandlingCostTable.querySelector('tbody');
		var size = tableBody.querySelectorAll('tr').length;

		paymentMethodHandlingCostTable.addEventListener('click', function (event) {
			if (!event.target.matches('a.add')) {
				return;
			}

			event.preventDefault();

			var newRow = document.createElement('tr');

			var paymentMethodTypeColumn = document.createElement('td');
			var paymentMethodTypeInput = document.createElement('input');
			paymentMethodTypeInput.setAttribute('name', 'payment_method_type[' + size + ']');
			paymentMethodTypeInput.setAttribute('type', 'text');

			var handlingCostAmountColumn = document.createElement('td');
			var handlingCostAmountInput = document.createElement('input');
			handlingCostAmountInput.setAttribute('name', 'handling_cost_amount[' + size + ']');
			handlingCostAmountInput.setAttribute('type', 'text');

			paymentMethodTypeColumn.appendChild(paymentMethodTypeInput);
			handlingCostAmountColumn.appendChild(handlingCostAmountInput);
			newRow.appendChild(paymentMethodTypeColumn);
			newRow.appendChild(handlingCostAmountColumn);
			tableBody.appendChild(newRow);

			size += 1;

			return false;
		});

		// Warn if part payment widget is enabled while blocks mode is on
		var blockModeCheckbox = document.querySelector('[id$="_block_mode_enabled"]');
		var widgetSelects = [
			document.querySelector('[id$="_partpayment_widget_cart_location"]'),
			document.querySelector('[id$="_partpayment_widget_checkout_location"]')
		];

		if (blockModeCheckbox) {
			var checkIncompatibility = function(event) {
				if (blockModeCheckbox.checked) {
					var isIncompatible = false;
					widgetSelects.forEach(function(select) {
						if (select && select.value !== '0' && select.value !== '') {
							isIncompatible = true;
							if (event && event.target === select) {
								select.value = '0'; // Revert the specific select
							}
						}
					});

					if (isIncompatible) {
						alert('Part payment widget is not supported when using Blocks Mode. Please disable Blocks Mode first, or keep the widget disabled.');
						if (event && event.target === blockModeCheckbox) {
							blockModeCheckbox.checked = false; // Revert the checkbox
						} else {
							// If they loaded the page with both enabled, or we missed reverting
							widgetSelects.forEach(function(select) {
								if (select && select.value !== '0' && select.value !== '') {
									select.value = '0';
								}
							});
						}
					}
				}
			};

			blockModeCheckbox.addEventListener('change', checkIncompatibility);
			widgetSelects.forEach(function(select) {
				if (select) {
					select.addEventListener('change', checkIncompatibility);
				}
			});
		}
	});
})();
