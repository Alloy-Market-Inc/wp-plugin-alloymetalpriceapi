<?php
/**
 * Frontend asset registration and enqueueing.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Assets {
	/**
	 * Shortcodes that require the frontend stylesheet.
	 *
	 * @var array<int, string>
	 */
	const STYLE_SHORTCODES = array(
		'metalpriceapi',
		'metal_price_table',
		'metal_calculator',
		'metal_calculator_layout',
		'conversion_rate_calculator',
		'metal_price_compare',
		'metal_payout_comparison',
		'metal_spot_ticker',
		'metal_goldbar_live_melt_table',
		'metal_fractional_goldbar_module',
		'metal_standard_goldbar_module',
		'metal_offer_card',
	);

	/**
	 * Shortcodes that require the frontend script bundle.
	 *
	 * @var array<int, string>
	 */
	const SCRIPT_SHORTCODES = array(
		'metal_calculator',
		'metal_calculator_layout',
		'conversion_rate_calculator',
		'metal_offer_card',
	);

	/**
	 * Shared frontend font stylesheet handle.
	 *
	 * @var string
	 */
	const FONT_HANDLE = 'alloy-metal-price-api-fonts';

	/**
	 * Shared frontend stylesheet handle.
	 *
	 * @var string
	 */
	const STYLE_HANDLE = 'alloy-metal-price-api-frontend';

	/**
	 * Shared frontend script handle.
	 *
	 * @var string
	 */
	const SCRIPT_HANDLE = 'alloy-metal-price-api-frontend';

	/**
	 * Register plugin frontend assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style(
			self::FONT_HANDLE,
			'https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@400;500;600;700&display=swap',
			array(),
			null
		);

		wp_register_style(
			self::STYLE_HANDLE,
			ALLOY_METAL_PRICE_API_PLUGIN_URL . 'assets/dist/css/plugin.css',
			array(self::FONT_HANDLE),
			$this->get_asset_version('assets/dist/css/plugin.css')
		);

		wp_register_script(
			self::SCRIPT_HANDLE,
			ALLOY_METAL_PRICE_API_PLUGIN_URL . 'assets/dist/js/alloy-calculator.js',
			array(),
			$this->get_asset_version('assets/dist/js/alloy-calculator.js'),
			true
		);
	}

	/**
	 * Conditionally enqueue plugin assets for singular content that uses supported shortcodes.
	 *
	 * @return void
	 */
	public function maybe_enqueue_shortcode_assets() {
		if (! is_singular()) {
			return;
		}

		$post = get_queried_object();

		if (! ($post instanceof WP_Post)) {
			return;
		}

		if ($this->post_has_any_shortcode($post->post_content, self::STYLE_SHORTCODES)) {
			$this->enqueue_frontend_assets();
		}

		if ($this->post_has_any_shortcode($post->post_content, self::SCRIPT_SHORTCODES)) {
			$this->enqueue_frontend_scripts();
		}
	}

	/**
	 * Enqueue plugin frontend styles.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		if (! wp_style_is(self::STYLE_HANDLE, 'registered')) {
			$this->register_assets();
		}

		wp_enqueue_style(self::STYLE_HANDLE);
	}

	/**
	 * Enqueue plugin frontend scripts.
	 *
	 * @return void
	 */
	public function enqueue_frontend_scripts() {
		if (! wp_script_is(self::SCRIPT_HANDLE, 'registered')) {
			$this->register_assets();
		}

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'alloyMetalPriceApi',
			array(
				'ajaxUrl'      => admin_url('admin-ajax.php'),
				'refreshNonce' => wp_create_nonce('alloy_metal_price_api_refresh'),
			)
		);

		wp_enqueue_script(self::SCRIPT_HANDLE);
	}

	/**
	 * Determine whether post content contains any shortcode from a list.
	 *
	 * @param string            $content Post content.
	 * @param array<int, string> $shortcodes Shortcode tags to check.
	 * @return bool
	 */
	protected function post_has_any_shortcode($content, array $shortcodes) {
		foreach ($shortcodes as $shortcode) {
			if (has_shortcode($content, $shortcode)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a cache-busting version for an asset.
	 *
	 * @param string $relative_path File path relative to the plugin root.
	 * @return string
	 */
	protected function get_asset_version($relative_path) {
		$file_path = ALLOY_METAL_PRICE_API_PLUGIN_DIR . ltrim($relative_path, '/');

		if (file_exists($file_path)) {
			return (string) filemtime($file_path);
		}

		return ALLOY_METAL_PRICE_API_VERSION;
	}
}
