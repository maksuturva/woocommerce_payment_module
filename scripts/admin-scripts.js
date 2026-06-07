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
	});
})();
