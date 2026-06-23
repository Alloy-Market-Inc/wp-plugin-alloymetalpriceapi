<?php
/**
 * Fixture validation for payout-table payload normalization.
 *
 * This intentionally avoids bootstrapping WordPress so it can run before the
 * Aurify endpoint is live.
 *
 * @package AlloyMetalPriceAPI
 */

define('ABSPATH', __DIR__ . '/');

require_once dirname(__DIR__) . '/includes/class-alloy-metal-price-api-client.php';

$payload = array(
	'metal'        => array(
		'_id'    => '68e955a3ed6c47456b6a2fc1',
		'label'  => 'Gold',
		'symbol' => 'au',
	),
	'payoutTables' => array(
		array(
			'_id'                 => '69001ce72ca4081978ea9ae7',
			'grade'               => '24K',
			'purityPercent'       => 1,
			'percentOnSpot'       => 0.75,
			'externalSpotPercent' => 0.8,
		),
		array(
			'grade'               => '22K',
			'purityPercent'       => 0.9167,
			'externalSpotPrecent' => 0.79,
		),
		array(
			'grade'               => 'Invalid',
			'purityPercent'       => 0.5,
			'externalSpotPercent' => null,
		),
	),
);

$rows = Alloy_Metal_Price_API_Client::normalize_payout_table_payload($payload);

if (2 !== count($rows)) {
	fwrite(STDERR, "Expected 2 normalized rows.\n");
	exit(1);
}

if ('24K' !== $rows[0]['grade'] || 1.0 !== $rows[0]['purityPercent'] || 0.8 !== $rows[0]['externalSpotPercent']) {
	fwrite(STDERR, "Canonical externalSpotPercent row did not normalize as expected.\n");
	exit(1);
}

if ('22K' !== $rows[1]['grade'] || 0.9167 !== $rows[1]['purityPercent'] || 0.79 !== $rows[1]['externalSpotPercent']) {
	fwrite(STDERR, "Defensive externalSpotPrecent row did not normalize as expected.\n");
	exit(1);
}

echo "Payout table normalizer fixture passed.\n";
