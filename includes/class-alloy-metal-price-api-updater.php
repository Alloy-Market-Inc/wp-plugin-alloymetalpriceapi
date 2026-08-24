<?php

if (! defined('ABSPATH')) {
	exit;
}

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Build a validated, environment-owned update-service configuration.
 *
 * @return array{base_url: string, token: string}|null
 */
function alloy_metal_price_api_update_service_config($base_url, $token) {
	$base_url = is_string($base_url) ? rtrim(trim($base_url), '/') : '';
	$token = is_string($token) ? trim($token) : '';
	$parts = $base_url !== '' ? parse_url($base_url) : false;

	if (
		$token === '' ||
		! is_array($parts) ||
		strtolower($parts['scheme'] ?? '') !== 'https' ||
		empty($parts['host']) ||
		isset($parts['user']) ||
		isset($parts['pass']) ||
		isset($parts['query']) ||
		isset($parts['fragment'])
	) {
		return null;
	}

	return [
		'base_url' => $base_url,
		'token' => $token,
	];
}

/**
 * Attach the read credential only to this component's update-service routes.
 */
function alloy_metal_price_api_update_request_args($args, $url) {
	if (! defined('ALLOY_UPDATE_SERVICE_URL') || ! defined('ALLOY_UPDATE_SERVICE_READ_TOKEN')) {
		return $args;
	}

	$config = alloy_metal_price_api_update_service_config(
		ALLOY_UPDATE_SERVICE_URL,
		ALLOY_UPDATE_SERVICE_READ_TOKEN
	);
	$target = is_string($url) ? parse_url($url) : false;
	$service = $config ? parse_url($config['base_url']) : false;

	if (! $config || ! is_array($target) || ! is_array($service)) {
		return $args;
	}

	$service_path = rtrim($service['path'] ?? '', '/');
	$component_path = $service_path . '/v1/components/alloy-metal-price-api/';
	$same_origin =
		strtolower($target['scheme'] ?? '') === 'https' &&
		strtolower($target['host'] ?? '') === strtolower($service['host']) &&
		($target['port'] ?? null) === ($service['port'] ?? null);

	if (! $same_origin || ! str_starts_with($target['path'] ?? '', $component_path)) {
		return $args;
	}

	if (! isset($args['headers']) || ! is_array($args['headers'])) {
		$args['headers'] = [];
	}
	$args['headers']['Authorization'] = 'Bearer ' . $config['token'];

	return $args;
}

/**
 * Register the private updater outside admin-only hooks so SPM and WP-CLI see it.
 */
function alloy_metal_price_api_register_updater() {
	if (! defined('ALLOY_UPDATE_SERVICE_URL') || ! defined('ALLOY_UPDATE_SERVICE_READ_TOKEN')) {
		return null;
	}

	$config = alloy_metal_price_api_update_service_config(
		ALLOY_UPDATE_SERVICE_URL,
		ALLOY_UPDATE_SERVICE_READ_TOKEN
	);
	if (! $config) {
		return null;
	}

	require_once ALLOY_METAL_PRICE_API_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';
	add_filter('http_request_args', 'alloy_metal_price_api_update_request_args', 10, 2);

	$checker = PucFactory::buildUpdateChecker(
		$config['base_url'] . '/v1/components/alloy-metal-price-api/stable',
		ALLOY_METAL_PRICE_API_PLUGIN_FILE,
		'AlloyMetalPriceAPI'
	);
	$GLOBALS['alloy_metal_price_api_update_checker'] = $checker;

	return $checker;
}
