<?php
/**
 * Shared Aurify API client.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Client {
	/**
	 * Aurify API base URL.
	 *
	 * @var string
	 */
	const BASE_URL = 'https://aurify.app/api/v1';

	/**
	 * Logger instance.
	 *
	 * @var Alloy_Metal_Price_API_Logger
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @param Alloy_Metal_Price_API_Logger $logger Logger service.
	 */
	public function __construct(Alloy_Metal_Price_API_Logger $logger) {
		$this->logger = $logger;
	}

	/**
	 * Get the current metal price from the Aurify API.
	 *
	 * @param string $metal_type Metal type expected by the Aurify API.
	 * @return float|\WP_Error
	 */
	public function get_metal_price($metal_type) {
		$metal_type = sanitize_key($metal_type);
		$endpoint   = trailingslashit(self::BASE_URL) . 'metal-price?metalType=' . rawurlencode($metal_type);

		$response = wp_remote_get(
			$endpoint,
			array(
				'timeout' => 10,
			)
		);

		if (is_wp_error($response)) {
			$this->logger->log_error('API request failed: ' . $response->get_error_message());
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code($response);
		if (200 !== $status_code) {
			$this->logger->log_error('API returned non-200 status: ' . (string) $status_code);
			return new WP_Error(
				'alloy_metal_price_api_bad_status',
				sprintf(
					/* translators: %d: HTTP status code. */
					__('Aurify API returned an unexpected status code: %d', 'alloy-metal-price-api'),
					$status_code
				)
			);
		}

		$body = trim((string) wp_remote_retrieve_body($response));

		if ('' === $body || ! is_numeric($body)) {
			$this->logger->log_error('Invalid API payload (expected numeric string): ' . $body);
			return new WP_Error(
				'alloy_metal_price_api_invalid_payload',
				__('Aurify API returned an invalid payload.', 'alloy-metal-price-api')
			);
		}

		return (float) $body;
	}
}
