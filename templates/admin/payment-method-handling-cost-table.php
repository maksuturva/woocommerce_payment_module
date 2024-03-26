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
 * @var array $field Field configuration.
 * @var array $payment_method_handling_costs Existing rows for the table.
 *
 * @since 2.1.3
 */
?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="payment_method_handling_cost_table">
			<?php echo esc_html( $field['title'] ); ?>
			<?php if ( isset( $field['description'] ) && isset( $field['desc_tip'] ) && $field['desc_tip'] ) { ?>
				<span class="woocommerce-help-tip" data-tip="<?php echo esc_html( $field['description'] ); ?>"></span>
			<?php } ?>
		</label>
	</th>
	<td class="forminp" id="payment_method_handling_cost_table">
		<table class="wc_input_table widefat" cellspacing="0">
			<thead>
				<tr>
					<th><?php echo esc_html( $field['code_column_title'] ); ?></th>
					<th><?php echo esc_html( $field['amount_column_title'] ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $payment_method_handling_costs as $key => $handling_cost ) { ?>
				<tr>
					<td>
						<input type="text" name="payment_method_type[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_html( $handling_cost['payment_method_type'] ); ?>"/>
					</td>
					<td>
						<input type="text" name="handling_cost_amount[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_html( $handling_cost['handling_cost_amount'] ); ?>"/>
					</td>
				</tr>
				<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="7">
						<a href="#" class="add button">
							<?php echo esc_html( $field['add_new_button_text'] ); ?>
						</a>
						<a href="#" class="remove_rows button">
							<?php echo esc_html( $field['remove_selected_rows_button_text'] ); ?>
						</a>
					</th>
				</tr>
			</tfoot>
		</table>
		<script type="text/javascript">
			(function() {
				var paymentMethodHandlingCostTable = document.querySelector('#payment_method_handling_cost_table');
				var tableBody = paymentMethodHandlingCostTable.querySelector('tbody');
				var size = tableBody.querySelectorAll('tr').length;

				paymentMethodHandlingCostTable.addEventListener('click', function(event) {

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
			})();
		</script>
	</td>
</tr>
