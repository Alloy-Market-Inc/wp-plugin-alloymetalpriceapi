<?php
/**
 * Plugin Name: AlloyMetalPriceAPI
 * Description: Provides Aurify API-powered shortcodes for WordPress.
 * Version: 1.1.0
 * Author: Alloy
 * Requires at least: 6.5
 * Tested up to: 7.0
 * Requires PHP: 8.1
 */

if (! defined('ABSPATH')) {
	exit;
}

define('ALLOY_METAL_PRICE_API_VERSION', '1.1.0');
define('ALLOY_METAL_PRICE_API_PLUGIN_FILE', __FILE__);
define('ALLOY_METAL_PRICE_API_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALLOY_METAL_PRICE_API_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/class-alloy-metal-price-api-plugin.php';
require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'includes/class-alloy-metal-price-api-updater.php';

Alloy_Metal_Price_API_Plugin::get_instance()->init();
alloy_metal_price_api_register_updater();
