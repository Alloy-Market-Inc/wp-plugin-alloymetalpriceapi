<?php

/**
 * [metal_price_calc] shortcode handler.
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
	const TAG = 'metal_price_calc';

	/**
	 * Legacy shortcode tag kept for backward compatibility.
	 *
	 * @var string
	 */
	const LEGACY_TAG = 'metal-price-calc';

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
		add_shortcode(self::LEGACY_TAG, array($this, 'render'));
	}

	/**
	 * Render shortcode output.
	 *
	 * Supported output values:
	 * - market
	 * - pawn
	 * - alloy
	 * - melt
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function render($atts) {
		$raw_atts = is_array($atts) ? $atts : array();

		$atts = shortcode_atts(
			array(
				'metal'       => 'gold',
				'purity'      => '14K',
				'weight'      => '0',
				'weight_unit' => 'grams',
				'output'      => 'market',
			),
			$atts,
			self::TAG
		);

		$metal        = $this->normalize_metal($atts['metal']);
		$purity_value = $this->parse_purity_value($atts['purity'], $metal);
		$has_manual_purity = array_key_exists('purity', $raw_atts);
		$weight       = $this->parse_weight($atts['weight']);
		$weight_unit  = $this->normalize_weight_unit($atts['weight_unit']);
		$output       = $this->normalize_output($atts['output']);
		$spot_price   = $this->api_client->get_cached_metal_price($metal);

		if (null === $spot_price) {
			return '<span>' . esc_html__('Unavailable', 'alloy-metal-price-api') . '</span>';
		}

		$weight_in_grams = $this->convert_weight_to_grams($weight, $weight_unit);
		$purity_factor   = 'gold' === $metal ? ((int) $purity_value / 24) : (float) $purity_value;
		$melt_purity_factor = $has_manual_purity ? $purity_factor : 0.9999;
		$market_value    = $spot_price * $purity_factor * $weight_in_grams;
		$pawn_value      = $market_value * 0.4;
		$alloy_value     = $market_value * $this->get_alloy_offer_rate($metal, $purity_value);
		$melt_value      = $spot_price * $melt_purity_factor * $weight_in_grams;
		$values          = array(
			'market' => $market_value,
			'pawn'   => $pawn_value,
			'alloy'  => $alloy_value,
			'melt'   => $melt_value,
		);

		return sprintf(
			'<span>%s</span>',
			esc_html($this->format_currency($values[ $output ]))
		);
	}

	/**
	 * Parse a purity attribute into a karat integer or decimal purity value.
	 *
	 * @param mixed $purity Raw shortcode purity value.
	 * @param string $metal Normalized metal key.
	 * @return int|float
	 */
	protected function parse_purity_value($purity, $metal) {
		if ('gold' !== $metal) {
			$purity = is_numeric($purity) ? (float) $purity : 0.9999;

			if ($purity > 1 && $purity <= 100) {
				$purity /= 100;
			} elseif ($purity > 100 && $purity <= 1000) {
				$purity /= 1000;
			} elseif ($purity > 1000 && $purity <= 10000) {
				$purity /= 10000;
			}

			return max(0, min(1, $purity));
		}

		$purity = strtoupper(sanitize_text_field((string) $purity));

		if (preg_match('/^([1-9]|1[0-9]|2[0-4])K?$/', $purity, $matches)) {
			return (int) $matches[1];
		}

		return 14;
	}

	/**
	 * Normalize the metal attribute to a supported API metal type.
	 *
	 * @param mixed $metal Raw metal attribute value.
	 * @return string
	 */
	protected function normalize_metal($metal) {
		$metal = strtolower(sanitize_text_field((string) $metal));

		if (in_array($metal, array('gold', 'silver', 'platinum', 'palladium'), true)) {
			return $metal;
		}

		return 'gold';
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
			'melt'                 => 'melt',
			'melt_value'           => 'melt',
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
	 * Get the Alloy offer multiplier for the selected metal and purity.
	 *
	 * @param string    $metal Normalized metal key.
	 * @param int|float $purity_value Purity input value.
	 * @return float
	 */
	protected function get_alloy_offer_rate($metal, $purity_value) {
		if ('gold' === $metal && 24 === (int) $purity_value) {
			return 0.85;
		}

		if ('gold' === $metal && 22 === (int) $purity_value) {
			return 0.8;
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
