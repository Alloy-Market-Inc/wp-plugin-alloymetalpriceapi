<?php

/**
 * [metal_calculator] shortcode handler.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Alloy_Calculator_Shortcode {
	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'metal_calculator';

	/**
	 * Shared API client.
	 *
	 * @var Alloy_Metal_Price_API_Client
	 */
	protected $api_client;

	/**
	 * Shared asset manager.
	 *
	 * @var Alloy_Metal_Price_API_Assets
	 */
	protected $assets;

	/**
	 * Shared calculator renderer.
	 *
	 * @var Alloy_Metal_Price_API_Calculator_Renderer
	 */
	protected $calculator_renderer;

	/**
	 * Constructor.
	 *
	 * @param Alloy_Metal_Price_API_Client $api_client API client.
	 * @param Alloy_Metal_Price_API_Assets $assets Asset manager.
	 */
	public function __construct(Alloy_Metal_Price_API_Client $api_client, Alloy_Metal_Price_API_Assets $assets) {
		$this->api_client          = $api_client;
		$this->assets              = $assets;
		$this->calculator_renderer = new Alloy_Metal_Price_API_Calculator_Renderer();
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
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function render($atts) {
		$atts = shortcode_atts(
			array(
				'title'  => __('Gold Calculator', 'alloy-metal-price-api'),
				'purity' => '14K',
				'metal'  => 'gold',
			),
			$atts,
			self::TAG
		);

		$this->assets->enqueue_frontend_assets();
		$this->assets->enqueue_frontend_scripts();

		$title               = sanitize_text_field((string) $atts['title']);
		$metal               = $this->normalize_metal($atts['metal']);
		$purity_value        = $this->parse_purity_value($atts['purity'], $metal);
		$spot_price_per_gram = $this->api_client->get_metal_price($metal);

		if (is_wp_error($spot_price_per_gram)) {
			$spot_price_per_gram = 0;
		}

		return $this->calculator_renderer->render(
			array(
				'title'               => $title,
				'metal'               => $metal,
				'purity_value'        => $purity_value,
				'base_price_per_gram' => $spot_price_per_gram,
				'wrapper_class'       => 'aur:flex aur:w-full aur:justify-center aur:font-sans',
			)
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
}
