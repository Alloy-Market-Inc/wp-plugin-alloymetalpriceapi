<?php

/**
 * [metal-price-calc] shortcode handler.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Metal_Price_Calc_Shortcode {
	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'metal-price-calc';

	/**
	 * Shared API client.
	 *
	 * @var Alloy_Metal_Price_API_Client
	 */
	protected $api_client;

	/**
	 * Constructor.
	 *
	 * @param Alloy_Metal_Price_API_Client $api_client API client.
	 */
	public function __construct(Alloy_Metal_Price_API_Client $api_client) {
		$this->api_client = $api_client;
	}

	/**
	 * Register the shortcode with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode(self::TAG, array($this, 'render'));
	}

	/**
	 * Render shortcode output.
	 *
	 * Supported output values:
	 * - market
	 * - pawn
	 * - alloy
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function render($atts) {
		$atts = shortcode_atts(
			array(
				'purity'      => '14K',
				'weight'      => '0',
				'weight_unit' => 'grams',
				'output'      => 'market',
			),
			$atts,
			self::TAG
		);

		$purity_karat = $this->parse_purity_karat($atts['purity']);
		$weight       = $this->parse_weight($atts['weight']);
		$weight_unit  = $this->normalize_weight_unit($atts['weight_unit']);
		$output       = $this->normalize_output($atts['output']);
		$spot_price   = $this->api_client->get_metal_price('gold');

		if (is_wp_error($spot_price)) {
			return '<span>' . esc_html__('Unavailable', 'alloy-metal-price-api') . '</span>';
		}

		$weight_in_grams = $this->convert_weight_to_grams($weight, $weight_unit);
		$purity_factor   = $purity_karat / 24;
		$market_value    = $spot_price * $purity_factor * $weight_in_grams;
		$pawn_value      = $market_value * 0.4;
		$alloy_value     = $market_value * $this->get_alloy_offer_rate($purity_karat);
		$values          = array(
			'market' => $market_value,
			'pawn'   => $pawn_value,
			'alloy'  => $alloy_value,
		);

		return sprintf(
			'<span>%s</span>',
			esc_html($this->format_currency($values[ $output ]))
		);
	}

	/**
	 * Parse a purity attribute into a karat integer.
	 *
	 * @param mixed $purity Raw shortcode purity value.
	 * @return int
	 */
	protected function parse_purity_karat($purity) {
		$purity = strtoupper(sanitize_text_field((string) $purity));

		if (preg_match('/^([1-9]|1[0-9]|2[0-4])K?$/', $purity, $matches)) {
			return (int) $matches[1];
		}

		return 14;
	}

	/**
	 * Parse a weight attribute into a non-negative float.
	 *
	 * @param mixed $weight Raw shortcode weight value.
	 * @return float
	 */
	protected function parse_weight($weight) {
		$weight = is_numeric($weight) ? (float) $weight : 0;

		return max(0, $weight);
	}

	/**
	 * Normalize a weight unit attribute.
	 *
	 * @param mixed $weight_unit Raw shortcode weight unit.
	 * @return string
	 */
	protected function normalize_weight_unit($weight_unit) {
		$weight_unit = strtolower(sanitize_text_field((string) $weight_unit));
		$weight_unit = str_replace('-', '', $weight_unit);

		$aliases = array(
			'gram'        => 'grams',
			'grams'       => 'grams',
			'ounce'       => 'ounces',
			'ounces'      => 'ounces',
			'pennyweight' => 'pennyweight',
			'pennyweights' => 'pennyweight',
			'dwt'         => 'pennyweight',
		);

		if (isset($aliases[ $weight_unit ])) {
			return $aliases[ $weight_unit ];
		}

		return 'grams';
	}

	/**
	 * Normalize an output type attribute.
	 *
	 * @param mixed $output Raw shortcode output type.
	 * @return string
	 */
	protected function normalize_output($output) {
		$output = strtolower(sanitize_text_field((string) $output));
		$output = str_replace(array('-', ' '), '_', $output);

		$aliases = array(
			'market'               => 'market',
			'market_value'         => 'market',
			'pawn'                 => 'pawn',
			'pawn_offer'           => 'pawn',
			'pawn_shop_offer'      => 'pawn',
			'alloy'                => 'alloy',
			'alloy_offer'          => 'alloy',
			'alloy_estimated_offer' => 'alloy',
		);

		if (isset($aliases[ $output ])) {
			return $aliases[ $output ];
		}

		return 'market';
	}

	/**
	 * Convert a weight value to grams.
	 *
	 * @param float  $weight Weight amount.
	 * @param string $weight_unit Normalized weight unit.
	 * @return float
	 */
	protected function convert_weight_to_grams($weight, $weight_unit) {
		switch ($weight_unit) {
			case 'ounces':
				return $weight * 31.1035;
			case 'pennyweight':
				return $weight * 1.55517384;
			case 'grams':
			default:
				return $weight;
		}
	}

	/**
	 * Get the Alloy offer multiplier for a karat value.
	 *
	 * @param int $purity_karat Purity karat value.
	 * @return float
	 */
	protected function get_alloy_offer_rate($purity_karat) {
		if (22 === (int) $purity_karat || 24 === (int) $purity_karat) {
			return 0.85;
		}

		return 0.7;
	}

	/**
	 * Format a currency value.
	 *
	 * @param float $amount Monetary value.
	 * @return string
	 */
	protected function format_currency($amount) {
		return '$' . number_format_i18n((float) $amount, 2);
	}
}
