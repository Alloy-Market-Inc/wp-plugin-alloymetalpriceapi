<?php

/**
 * [metalpriceapi] shortcode handler.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Metal_Price_Shortcode {
	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'metalpriceapi';

	/**
	 * Map of supported metal symbols to Aurify API metal types.
	 *
	 * @var array<string, string>
	 */
	protected $symbol_map = array(
		'XAU' => 'gold',
		'XAG' => 'silver',
		'XPT' => 'platinum',
		'XPD' => 'palladium',
	);

	/**
	 * Supported display units.
	 *
	 * @var array<int, string>
	 */
	protected $allowed_units = array('gram', 'ounce', 'kilogram');

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
	 * Constructor.
	 *
	 * @param Alloy_Metal_Price_API_Client $api_client API client.
	 * @param Alloy_Metal_Price_API_Assets $assets Asset manager.
	 */
	public function __construct(Alloy_Metal_Price_API_Client $api_client, Alloy_Metal_Price_API_Assets $assets) {
		$this->api_client = $api_client;
		$this->assets     = $assets;
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
				'symbol' => 'XAU',
				'unit'   => 'ounce',
			),
			$atts,
			self::TAG
		);

		$symbol = strtoupper(sanitize_text_field((string) $atts['symbol']));
		$unit   = strtolower(sanitize_text_field((string) $atts['unit']));

		if (! isset($this->symbol_map[$symbol])) {
			$symbol = 'XAU';
		}

		if (! in_array($unit, $this->allowed_units, true)) {
			$unit = 'gram';
		}

		$price = $this->api_client->get_metal_price($this->symbol_map[$symbol]);

		if (is_wp_error($price)) {
			return '';
		}

		$this->assets->enqueue_frontend_assets();

		$formatted_price = (string) round($this->convert_price_unit($price, $unit), 2);
		$symbol_slug     = strtolower($symbol);

		ob_start();
		?>
		<span
			class="aur:text-base aur:font-sans"
			data-metal-symbol="<?php echo esc_attr($symbol_slug); ?>"
			data-metal-unit="<?php echo esc_attr($unit); ?>"
		>
			<?php echo esc_html($formatted_price); ?>
		</span>
		<?php

		return trim((string) ob_get_clean());
	}

	/**
	 * Convert the base price to the requested unit.
	 *
	 * @param float  $price Base price.
	 * @param string $unit Requested unit.
	 * @return float
	 */
	protected function convert_price_unit($price, $unit) {
		switch ($unit) {
			case 'kilogram':
				return $price * 1000;
			case 'ounce':
				return $price * 31.1035;
			case 'gram':
			default:
				return $price;
		}
	}
}
