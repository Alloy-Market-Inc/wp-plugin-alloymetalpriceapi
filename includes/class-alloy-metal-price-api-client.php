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
	 * Cached spot price lifetime in seconds.
	 *
	 * @var int
	 */
	const PRICE_CACHE_TTL = 60;

	/**
	 * Cached payout table lifetime in seconds.
	 *
	 * @var int
	 */
	const PAYOUT_TABLE_CACHE_TTL = 300;

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

		$price = (float) $body;

		set_transient($this->get_price_cache_key($metal_type), $price, self::PRICE_CACHE_TTL);
		update_option($this->get_last_price_option_key($metal_type), $price, false);

		return $price;
	}

	/**
	 * Get the last cached metal price without making a remote request.
	 *
	 * @param string $metal_type Metal type expected by the Aurify API.
	 * @return float|null
	 */
	public function get_cached_metal_price($metal_type) {
		$cached_price = get_transient($this->get_price_cache_key($metal_type));

		if (false === $cached_price || ! is_numeric($cached_price)) {
			$cached_price = get_option($this->get_last_price_option_key($metal_type), null);
		}

		if (null === $cached_price || false === $cached_price || ! is_numeric($cached_price)) {
			return null;
		}

		return (float) $cached_price;
	}

	/**
	 * Get a normalized payout table from the Aurify API.
	 *
	 * Falls back to the last cached payout table only. It intentionally does not
	 * fall back to legacy hardcoded offer percentages.
	 *
	 * @param string $metal Metal label expected by the payout-table endpoint.
	 * @return array<int, array<string, float|string>>|\WP_Error
	 */
	public function get_payout_table($metal) {
		$metal = sanitize_key($metal);

		if (! in_array($metal, array('gold', 'silver', 'platinum'), true)) {
			return new WP_Error(
				'alloy_metal_price_api_unsupported_payout_metal',
				__('Payout tables are not available for this metal.', 'alloy-metal-price-api')
			);
		}

		$endpoint = trailingslashit(self::BASE_URL)
			. 'wp/payout-table?metal='
			. rawurlencode($metal);

		$response = wp_remote_get(
			$endpoint,
			array(
				'timeout' => 10,
			)
		);

		if (is_wp_error($response)) {
			$this->logger->log_error(
				'Payout table API request failed: ' . $response->get_error_message()
			);
			return $this->get_cached_payout_table_or_error($metal, $response);
		}

		$status_code = wp_remote_retrieve_response_code($response);
		if (200 !== $status_code) {
			$this->logger->log_error(
				'Payout table API returned non-200 status: ' . (string) $status_code
			);
			return $this->get_cached_payout_table_or_error(
				$metal,
				new WP_Error(
					'alloy_metal_price_api_payout_bad_status',
					sprintf(
						/* translators: %d: HTTP status code. */
						__('Aurify payout table API returned an unexpected status code: %d', 'alloy-metal-price-api'),
						$status_code
					)
				)
			);
		}

		$body    = (string) wp_remote_retrieve_body($response);
		$payload = json_decode($body, true);

		if (! is_array($payload)) {
			$this->logger->log_error(
				'Invalid payout table API payload (expected JSON object): ' . $body
			);
			return $this->get_cached_payout_table_or_error(
				$metal,
				new WP_Error(
					'alloy_metal_price_api_payout_invalid_payload',
					__('Aurify payout table API returned an invalid payload.', 'alloy-metal-price-api')
				)
			);
		}

		$payout_table = self::normalize_payout_table_payload($payload);

		if (empty($payout_table)) {
			$this->logger->log_error(
				'Invalid payout table API payload (no usable payout rows): ' . $body
			);
			return $this->get_cached_payout_table_or_error(
				$metal,
				new WP_Error(
					'alloy_metal_price_api_payout_empty_payload',
					__('Aurify payout table API did not return usable payout rows.', 'alloy-metal-price-api')
				)
			);
		}

		set_transient(
			$this->get_payout_table_cache_key($metal),
			$payout_table,
			self::PAYOUT_TABLE_CACHE_TTL
		);
		update_option($this->get_last_payout_table_option_key($metal), $payout_table, false);

		return $payout_table;
	}

	/**
	 * Normalize an Aurify payout-table payload for calculator use.
	 *
	 * @param array<string, mixed> $payload Raw decoded JSON payload.
	 * @return array<int, array<string, float|string>>
	 */
	public static function normalize_payout_table_payload(array $payload) {
		if (! isset($payload['payoutTables']) || ! is_array($payload['payoutTables'])) {
			return array();
		}

		$rows = array();

		foreach ($payload['payoutTables'] as $row) {
			if (! is_array($row)) {
				continue;
			}

			$purity_percent      = isset($row['purityPercent']) ? $row['purityPercent'] : null;
			$external_spot_value = isset($row['externalSpotPercent']) ? $row['externalSpotPercent'] : null;
			$external_spot_value = null !== $external_spot_value
				? $external_spot_value
				: (isset($row['externalSpotPrecent']) ? $row['externalSpotPrecent'] : null);
			$external_spot_percent = is_numeric($external_spot_value) ? (float) $external_spot_value : null;

			if (! is_numeric($purity_percent) || null === $external_spot_percent) {
				continue;
			}

			$rows[] = array(
				'grade'               => isset($row['grade']) ? (string) $row['grade'] : '',
				'purityPercent'       => (float) $purity_percent,
				'externalSpotPercent' => $external_spot_percent,
			);
		}

		return $rows;
	}

	/**
	 * Get the last cached payout table without making a remote request.
	 *
	 * @param string $metal Metal label expected by the payout-table endpoint.
	 * @return array<int, array<string, float|string>>|null
	 */
	public function get_cached_payout_table($metal) {
		$metal        = sanitize_key($metal);
		$payout_table = get_transient($this->get_payout_table_cache_key($metal));

		if (false === $payout_table || ! is_array($payout_table)) {
			$payout_table = get_option($this->get_last_payout_table_option_key($metal), null);
		}

		if (! is_array($payout_table) || empty($payout_table)) {
			return null;
		}

		return $payout_table;
	}

	/**
	 * Return a cached payout table or the original remote error.
	 *
	 * @param string    $metal Metal label expected by the payout-table endpoint.
	 * @param \WP_Error $error Remote or payload error.
	 * @return array<int, array<string, float|string>>|\WP_Error
	 */
	protected function get_cached_payout_table_or_error($metal, WP_Error $error) {
		$payout_table = $this->get_cached_payout_table($metal);

		if (null !== $payout_table) {
			return $payout_table;
		}

		return $error;
	}

	/**
	 * Build the transient key for a metal price.
	 *
	 * @param string $metal_type Metal type expected by the Aurify API.
	 * @return string
	 */
	protected function get_price_cache_key($metal_type) {
		return 'alloy_metal_price_api_price_' . sanitize_key($metal_type);
	}

	/**
	 * Build the option key for the last known metal price.
	 *
	 * @param string $metal_type Metal type expected by the Aurify API.
	 * @return string
	 */
	protected function get_last_price_option_key($metal_type) {
		return 'alloy_metal_price_api_last_price_' . sanitize_key($metal_type);
	}

	/**
	 * Build the transient key for a payout table.
	 *
	 * @param string $metal Metal label expected by the payout-table endpoint.
	 * @return string
	 */
	protected function get_payout_table_cache_key($metal) {
		return 'alloy_metal_price_api_payout_table_' . sanitize_key($metal);
	}

	/**
	 * Build the option key for the last known payout table.
	 *
	 * @param string $metal Metal label expected by the payout-table endpoint.
	 * @return string
	 */
	protected function get_last_payout_table_option_key($metal) {
		return 'alloy_metal_price_api_last_payout_table_' . sanitize_key($metal);
	}
}
