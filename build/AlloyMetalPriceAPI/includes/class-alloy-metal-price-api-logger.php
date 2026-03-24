<?php
/**
 * Development logging helpers.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Logger {
	/**
	 * Check whether current request is for the local Valet dev domain.
	 *
	 * @return bool
	 */
	public function is_dev_domain_request() {
		if (empty($_SERVER['HTTP_HOST'])) {
			return false;
		}

		$host = strtolower((string) wp_unslash($_SERVER['HTTP_HOST']));
		$host = preg_replace('/:\d+$/', '', $host);

		return 'dev-wp.test' === $host;
	}

	/**
	 * Get the plugin error log file path in the WordPress root.
	 *
	 * @return string
	 */
	public function get_log_file_path() {
		return ABSPATH . 'alloy-metal-price-api-errors.log';
	}

	/**
	 * Log a plugin-specific error message to the plugin log file.
	 *
	 * @param string $message Error details.
	 * @return void
	 */
	public function log_error($message) {
		if (! $this->is_dev_domain_request()) {
			return;
		}

		$line = '[' . gmdate('Y-m-d H:i:s') . ' UTC] AlloyMetalPriceAPI: ' . $message . PHP_EOL;
		error_log($line, 3, $this->get_log_file_path());
	}

	/**
	 * Configure error logging for dev domain requests.
	 *
	 * @return void
	 */
	public function enable_dev_error_logging() {
		if (! $this->is_dev_domain_request()) {
			return;
		}

		error_reporting(E_ALL);
		ini_set('display_errors', '0');
		ini_set('display_startup_errors', '0');
		ini_set('log_errors', '1');
		ini_set('error_log', $this->get_log_file_path());
	}
}
