<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Svea Payments Blocks Integration
 *
 * @since 2.7.0
 */
final class WC_Gateway_Maksuturva_Blocks extends AbstractPaymentMethodType
{

	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_Maksuturva
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'WC_Gateway_Maksuturva';

	/**
	 * Initialize the payment method type.
	 */
	public function initialize()
	{
		$this->settings = get_option('woocommerce_WC_Gateway_Maksuturva_settings', []);
		$this->gateway = new WC_Gateway_Maksuturva();

		add_action('woocommerce_rest_checkout_process_payment_with_context', array($this, 'set_payment_method_for_rest'), 10, 2);
	}

	/**
	 * Set payment method in $_POST for REST requests.
	 *
	 * @param \Automattic\WooCommerce\StoreApi\Payments\PaymentContext $context Payment context.
	 * @param \Automattic\WooCommerce\StoreApi\Payments\PaymentResult  $result  Payment result.
	 */
	public function set_payment_method_for_rest($context, $result)
	{
		if ($context->payment_method === $this->name) {
			$payment_data = $context->payment_data;
			if (isset($payment_data['svea_payment_method'])) {
				$_POST['svea_payment_method'] = $payment_data['svea_payment_method'];
				wc_maksuturva_log('Block REST payment: Set svea_payment_method to ' . $payment_data['svea_payment_method']);
			} else {
				wc_maksuturva_log('Block REST payment: svea_payment_method not found in payment_data');
			}
		}
	}

	/**
	 * Returns if this payment method should be active.
	 *
	 * @return boolean
	 */
	public function is_active()
	{
		$block_mode_enabled = 'yes' === $this->gateway->get_option('block_mode_enabled', 'yes');
		if (!$block_mode_enabled) {
			return false;
		}

		$is_active = $this->gateway->is_available();
		$context = array(
			'rest' => defined('REST_REQUEST') && REST_REQUEST ? 'yes' : 'no',
			'checkout' => is_checkout() ? 'yes' : 'no',
			'active' => $is_active ? 'yes' : 'no',
		);
		// wc_maksuturva_log('Block is_active check: ' . json_encode($context));
		return $is_active;
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles()
	{
		$script_path = '/assets/js/wc-maksuturva-blocks.js';
		$script_asset_path = WC_Maksuturva::get_instance()->get_plugin_dir() . '/assets/js/wc-maksuturva-blocks.asset.php';
		$script_asset = file_exists($script_asset_path)
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version' => '1.0.0',
			);

		$script_asset['dependencies'] = array_merge(
			$script_asset['dependencies'],
			array('wc-blocks-registry', 'wc-settings')
		);

		$script_url = WC_Maksuturva::get_instance()->get_plugin_url() . 'assets/js/wc-maksuturva-blocks.js';

		wp_register_script(
			'wc-maksuturva-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		return array('wc-maksuturva-blocks');
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data()
	{
		// Load WC_Payment_Method_Select class
		if (!class_exists('WC_Payment_Method_Select')) {
			require_once WC_Maksuturva::get_instance()->get_plugin_dir() . '/includes/class-wc-payment-method-select.php';
		}

		$payment_method_select = new WC_Payment_Method_Select($this->gateway);
		$price = WC()->cart ? WC()->cart->get_total('edit') : 0;
		if (empty($price)) {
			$price = 1000;
		}
		// wc_maksuturva_log('Block get_payment_method_data price: ' . $price);
		$collated_methods = $payment_method_select->get_payment_type_payment_methods('collated', $price);
		// wc_maksuturva_log('Block get_payment_method_data methods count: ' . count($collated_methods));

		$group_methods = array(
			'group1' => array(),
			'group2' => array(),
			'group3' => array(),
			'group4' => array(),
		);

		// Pass debug info to frontend
		$this->settings['debug_info'] = array(
			'price' => $price,
			'methods_count' => count($collated_methods),
		);

		foreach ($collated_methods as $payment_method) {
			if (empty($payment_method['code'])) {
				continue;
			}

			if (in_array($payment_method['code'], explode(',', $this->gateway->get_option('collated_group1_methods', '')))) {
				$group_methods['group1'][] = $payment_method;
			} elseif (in_array($payment_method['code'], explode(',', $this->gateway->get_option('collated_group2_methods', '')))) {
				$group_methods['group2'][] = $payment_method;
			} elseif (in_array($payment_method['code'], explode(',', $this->gateway->get_option('collated_group3_methods', '')))) {
				$group_methods['group3'][] = $payment_method;
			} elseif (in_array($payment_method['code'], explode(',', $this->gateway->get_option('collated_group4_methods', '')))) {
				$group_methods['group4'][] = $payment_method;
			}
		}

		$groups = array();
		if (!empty($group_methods['group1'])) {
			$groups[] = array(
				'title' => $this->gateway->get_option('collated_group1_title', ''),
				'methods' => $group_methods['group1'],
			);
		}
		if (!empty($group_methods['group2'])) {
			$groups[] = array(
				'title' => $this->gateway->get_option('collated_group2_title', ''),
				'methods' => $group_methods['group2'],
			);
		}
		if (!empty($group_methods['group3'])) {
			$groups[] = array(
				'title' => $this->gateway->get_option('collated_group3_title', ''),
				'methods' => $group_methods['group3'],
			);
		}
		if (!empty($group_methods['group4'])) {
			$groups[] = array(
				'title' => $this->gateway->get_option('collated_group4_title', ''),
				'methods' => $group_methods['group4'],
			);
		}

		// Load WC_Payment_Handling_Costs class
		if (!class_exists('WC_Payment_Handling_Costs')) {
			require_once WC_Maksuturva::get_instance()->get_plugin_dir() . '/includes/class-wc-payment-handling-costs.php';
		}
		$payment_handling_costs_handler = new WC_Payment_Handling_Costs($this->gateway);
		$handling_costs = $payment_handling_costs_handler->get_handling_costs_by_payment_method();

		// Get Terms
		$terms = array(
			'text' => $payment_method_select->get_terms_text($price),
			'url' => $payment_method_select->get_terms_url($price),
		);

		return array(
			'title' => $this->get_setting('title'),
			'description' => $this->get_setting('description'),
			'supports' => $this->gateway->supports,
			'groups' => $groups,
			'handling_costs' => $handling_costs,
			'terms' => $terms,
			'currency_symbol' => get_woocommerce_currency_symbol(),
			'debug_info' => $this->settings['debug_info'],
		);
	}
}
