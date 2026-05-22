<?php
/**
 * Plugin bootstrap and service registration.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/class-alloy-metal-price-api-logger.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/class-alloy-metal-price-api-client.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/class-alloy-metal-price-api-assets.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/shortcodes/class-alloy-metal-price-api-calculator-renderer.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/shortcodes/class-alloy-metal-price-api-metal-price-shortcode.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/shortcodes/class-alloy-metal-price-api-metal-price-table-shortcode.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/shortcodes/class-alloy-metal-price-api-metal-calculator-shortcode.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/shortcodes/class-alloy-metal-price-api-metal-calculator-layout-shortcode.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/shortcodes/class-alloy-metal-price-api-metal-price-calc-shortcode.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/shortcodes/class-alloy-metal-price-api-metal-price-compare-shortcode.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/shortcodes/class-alloy-metal-price-api-conversion-rate-calculator-shortcode.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/shortcodes/class-alloy-metal-price-api-metal-payout-comparison-shortcode.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/shortcodes/class-alloy-metal-price-api-metal-spot-ticker-shortcode.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/shortcodes/class-alloy-metal-price-api-metal-goldbar-live-melt-table-shortcode.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/shortcodes/class-alloy-metal-price-api-metal-fractional-goldbar-module-shortcode.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/shortcodes/class-alloy-metal-price-api-metal-standard-goldbar-module-shortcode.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/shortcodes/class-alloy-metal-price-api-metal-offer-card-shortcode.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/shortcodes/class-alloy-metal-price-api-metal-budget-buy-widget-shortcode.php';

class Alloy_Metal_Price_API_Plugin {
	/**
	 * Plugin singleton instance.
	 *
	 * @var self|null
	 */
	protected static $instance = null;

	/**
	 * Shared logger instance.
	 *
	 * @var Alloy_Metal_Price_API_Logger
	 */
	protected $logger;

	/**
	 * Shared API client instance.
	 *
	 * @var Alloy_Metal_Price_API_Client
	 */
	protected $api_client;

	/**
	 * Shared asset manager instance.
	 *
	 * @var Alloy_Metal_Price_API_Assets
	 */
	protected $assets;

	/**
	 * Registered shortcode handlers.
	 *
	 * @var array<int, object>
	 */
	protected $shortcodes = array();

	/**
	 * Get the plugin singleton.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize plugin services and hooks.
	 *
	 * @return void
	 */
	public function init() {
		$this->logger     = new Alloy_Metal_Price_API_Logger();
		$this->api_client = new Alloy_Metal_Price_API_Client($this->logger);
		$this->assets     = new Alloy_Metal_Price_API_Assets();

		add_action('plugins_loaded', array($this, 'configure_dev_error_logging'), 1);
		add_action('wp_enqueue_scripts', array($this->assets, 'register_assets'));
		add_action('wp_enqueue_scripts', array($this->assets, 'maybe_enqueue_shortcode_assets'), 20);
		add_action('init', array($this, 'register_shortcodes'));
		add_action('wp_ajax_alloy_metal_price_api_refresh_calculator_price', array($this, 'handle_calculator_price_refresh'));
		add_action('wp_ajax_nopriv_alloy_metal_price_api_refresh_calculator_price', array($this, 'handle_calculator_price_refresh'));
	}

	/**
	 * Configure development error logging for the local dev domain.
	 *
	 * @return void
	 */
	public function configure_dev_error_logging() {
		$this->logger->enable_dev_error_logging();
	}

	/**
	 * Register all shortcodes for the plugin.
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		$this->shortcodes = array(
			new Alloy_Metal_Price_API_Metal_Price_Shortcode($this->api_client, $this->assets),
			new Alloy_Metal_Price_API_14K_Gold_Price_Table_Shortcode($this->api_client, $this->assets),
			new Alloy_Metal_Price_API_Alloy_Calculator_Shortcode($this->api_client, $this->assets),
			new Alloy_Metal_Price_API_Metal_Calculator_Layout_Shortcode($this->api_client, $this->assets),
			new Alloy_Metal_Price_API_Metal_Price_Calc_Shortcode($this->api_client),
			new Alloy_Metal_Price_API_Metal_Price_Compare_Shortcode($this->api_client, $this->assets),
			new Alloy_Metal_Price_API_Conversion_Rate_Calculator_Shortcode($this->assets),
			new Alloy_Metal_Price_API_Metal_Payout_Comparison_Shortcode($this->api_client, $this->assets),
			new Alloy_Metal_Price_API_Metal_Spot_Ticker_Shortcode($this->api_client, $this->assets),
			new Alloy_Metal_Price_API_Metal_Goldbar_Live_Melt_Table_Shortcode($this->api_client, $this->assets),
			new Alloy_Metal_Price_API_Metal_Fractional_Goldbar_Module_Shortcode($this->api_client, $this->assets),
			new Alloy_Metal_Price_API_Metal_Standard_Goldbar_Module_Shortcode($this->api_client, $this->assets),
			new Alloy_Metal_Price_API_Metal_Offer_Card_Shortcode($this->api_client, $this->assets),
			new Alloy_Metal_Price_API_Metal_Budget_Buy_Widget_Shortcode($this->api_client, $this->assets),
		);

		foreach ($this->shortcodes as $shortcode) {
			if (method_exists($shortcode, 'register')) {
				$shortcode->register();
			}
		}
	}

	/**
	 * Public AJAX handler for calculator live-price hydration.
	 *
	 * @return void
	 */
	public function handle_calculator_price_refresh() {
		check_ajax_referer('alloy_metal_price_api_refresh', 'nonce');

		$metal = isset($_POST['metal']) ? sanitize_key(wp_unslash($_POST['metal'])) : 'gold';

		if (! in_array($metal, array('gold', 'silver', 'platinum', 'palladium'), true)) {
			$metal = 'gold';
		}

		$price_per_gram = $this->api_client->get_metal_price($metal);

		if (is_wp_error($price_per_gram)) {
			wp_send_json_error(
				array(
					'message' => $price_per_gram->get_error_message(),
				)
			);
		}

		wp_send_json_success(
			array(
				'metal'         => $metal,
				'pricePerGram'  => (float) $price_per_gram,
				'pricePerOunce' => (float) $price_per_gram * 31.1035,
				'pricePerKilo'  => (float) $price_per_gram * 1000,
			)
		);
	}
}
