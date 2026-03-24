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
