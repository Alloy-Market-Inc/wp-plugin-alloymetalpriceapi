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
			),
			$atts,
			self::TAG
		);

		$this->assets->enqueue_frontend_assets();
		$this->assets->enqueue_frontend_scripts();

		$title               = sanitize_text_field((string) $atts['title']);
		$purity_karat        = $this->parse_purity_karat($atts['purity']);
		$spot_price_per_gram = $this->api_client->get_metal_price('gold');

		if (is_wp_error($spot_price_per_gram)) {
			$spot_price_per_gram = 0;
		}

		return $this->calculator_renderer->render(
			array(
				'title'               => $title,
				'purity_karat'        => $purity_karat,
				'base_price_per_gram' => $spot_price_per_gram,
				'wrapper_class'       => 'aur:flex aur:w-full aur:justify-center aur:font-sans',
			)
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
}
