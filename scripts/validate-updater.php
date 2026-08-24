<?php

define('ABSPATH', __DIR__ . '/fixtures/');
define('ALLOY_UPDATE_SERVICE_URL', 'https://updates.example.test');
define('ALLOY_UPDATE_SERVICE_READ_TOKEN', 'test-read-token');

require dirname(__DIR__) . '/includes/class-alloy-metal-price-api-updater.php';

function assert_same($expected, $actual, $message) {
	if ($expected !== $actual) {
		fwrite(STDERR, $message . PHP_EOL);
		exit(1);
	}
}

assert_same(null, alloy_metal_price_api_update_service_config('http://updates.example.test', 'token'), 'HTTP service URL must fail closed.');
assert_same(null, alloy_metal_price_api_update_service_config('https://updates.example.test', ''), 'Missing token must fail closed.');

$matching = alloy_metal_price_api_update_request_args(
	[],
	'https://updates.example.test/v1/components/alloy-metal-price-api/stable'
);
assert_same('Bearer test-read-token', $matching['headers']['Authorization'] ?? null, 'Stable request must be authenticated.');

$download = alloy_metal_price_api_update_request_args(
	[],
	'https://updates.example.test/v1/components/alloy-metal-price-api/versions/1.1.1/download'
);
assert_same('Bearer test-read-token', $download['headers']['Authorization'] ?? null, 'Package request must be authenticated.');

$foreign = alloy_metal_price_api_update_request_args([], 'https://example.org/v1/components/alloy-metal-price-api/stable');
assert_same(null, $foreign['headers']['Authorization'] ?? null, 'Credential leaked to a foreign host.');

$other_component = alloy_metal_price_api_update_request_args([], 'https://updates.example.test/v1/components/gf-aurify/stable');
assert_same(null, $other_component['headers']['Authorization'] ?? null, 'Credential leaked to another component path.');

echo "Updater request scoping passed.\n";
