<?php

/**
 * [metal_spot_ticker] shortcode handler.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Metal_Spot_Ticker_Shortcode {
	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'metal_spot_ticker';

	/**
	 * Grams in a troy ounce.
	 *
	 * @var float
	 */
	const TROY_OUNCE_IN_GRAMS = 31.1035;

	/**
	 * Supported metal types.
	 *
	 * @var array<string, string>
	 */
	const METAL_LABELS = array(
		'gold'      => 'Gold',
		'silver'    => 'Silver',
		'platinum'  => 'Platinum',
		'palladium' => 'Palladium',
	);

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
				'metal'     => 'gold',
				'purity'    => '24K',
				'pill_text' => 'Live Spot Gold',
			),
			$atts,
			self::TAG
		);

		$this->assets->enqueue_frontend_assets();
		$this->assets->enqueue_frontend_scripts();

		$metal               = $this->normalize_metal($atts['metal']);
		$purity_karat        = $this->parse_purity_karat($atts['purity']);
		$purity_multiplier   = $purity_karat / 24;
		$metal_label         = self::METAL_LABELS[$metal];
		$pill_text           = $this->build_pill_text($atts['pill_text'], $metal_label, $purity_karat);
		$spot_price_per_gram = $this->api_client->get_cached_metal_price($metal);

		if (null === $spot_price_per_gram) {
			return $this->render_card(
				$pill_text,
				__('Unavailable', 'alloy-metal-price-api'),
				__('Unavailable', 'alloy-metal-price-api'),
				__('Updating…', 'alloy-metal-price-api'),
				$metal,
				$purity_multiplier
			);
		}

		$spot_price_per_gram  = $spot_price_per_gram * $purity_multiplier;
		$spot_price_per_ounce = $spot_price_per_gram * self::TROY_OUNCE_IN_GRAMS;

		return $this->render_card(
				$pill_text,
				$this->format_currency($spot_price_per_ounce),
				$this->format_currency($spot_price_per_gram),
				$this->get_updated_label(),
				$metal,
				$purity_multiplier
			);
	}

	/**
	 * Render the spot ticker card.
	 *
	 * @param string $pill_text Ticker pill text.
	 * @param string $price_ounce Gold spot price per ounce.
	 * @param string $price_gram Gold spot price per gram.
	 * @param string $updated_label Updated label text.
	 * @return string
	 */
	protected function render_card($pill_text, $price_ounce, $price_gram, $updated_label, $metal = 'gold', $purity_multiplier = 1) {
		ob_start();
	?>
		<div class="aur:flex aur:w-full aur:justify-center aur:font-sans">
			<div class="aur:w-full aur:rounded-xl aur:border aur:border-slate-900 aur:bg-white aur:p-6 aur:shadow-[0_4px_20px_rgba(0,0,0,0.08)] aur:flex aur:flex-row aur:items-center aur:justify-between aur:gap-4">
				<div class="aur:inline-flex aur:rounded-lg aur:bg-primary aur:px-4 aur:py-2 aur:text-sm aur:font-semibold aur:text-white aur:uppercase">
					<?php echo esc_html($pill_text); ?>
				</div>
					<div class="aur:flex aur:flex-col aur:gap-3 aur:text-xl aur:text-slate-900 aur:sm:flex-row aur:sm:items-center aur:sm:justify-center aur:sm:gap-8">
						<span><?php esc_html_e('USD/oz:', 'alloy-metal-price-api'); ?> <strong><span class="js-alloy-live-price" data-metal="<?php echo esc_attr($metal); ?>" data-price-factor="<?php echo esc_attr((string) ($purity_multiplier * self::TROY_OUNCE_IN_GRAMS)); ?>"><?php echo esc_html($price_ounce); ?></span></strong></span>
						<span><?php esc_html_e('USD/g:', 'alloy-metal-price-api'); ?> <strong><span class="js-alloy-live-price" data-metal="<?php echo esc_attr($metal); ?>" data-price-factor="<?php echo esc_attr((string) $purity_multiplier); ?>"><?php echo esc_html($price_gram); ?></span></strong></span>
					</div>
				<div class="aur:flex aur:items-center aur:justify-center aur:gap-2 aur:text-sm aur:text-slate-500">
					<span class="aur:inline-block aur:h-2 aur:w-2 aur:rounded-full aur:bg-secondary" aria-hidden="true"></span>
					<span><?php echo esc_html($updated_label); ?></span>
				</div>
			</div>
		</div>
<?php

		$content = trim((string) ob_get_clean());

		return Alloy_Metal_Price_API_Shortcode_Shell::render(
			$content,
			Alloy_Metal_Price_API_Shortcode_Shell::card_skeleton(1),
			self::TAG
		);
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

	/**
	 * Normalize the metal attribute to a supported API metal type.
	 *
	 * @param mixed $metal Raw metal attribute value.
	 * @return string
	 */
	protected function normalize_metal($metal) {
		$metal = strtolower(sanitize_text_field((string) $metal));

		if (isset(self::METAL_LABELS[$metal])) {
			return $metal;
		}

		return 'gold';
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

		return 24;
	}

	/**
	 * Build the pill text from shortcode attributes.
	 *
	 * @param mixed  $pill_text Raw pill text attribute.
	 * @param string $metal_label Human-readable metal label.
	 * @param int    $purity_karat Purity karat value.
	 * @return string
	 */
	protected function build_pill_text($pill_text, $metal_label, $purity_karat) {
		$pill_text = sanitize_text_field((string) $pill_text);

		if ('' !== $pill_text) {
			return $pill_text;
		}

		return sprintf(
			/* translators: 1: purity label like 24K, 2: metal name like Gold. */
			__('Live Spot %1$s %2$s', 'alloy-metal-price-api'),
			absint($purity_karat) . 'K',
			$metal_label
		);
	}

	/**
	 * Build the updated timestamp label.
	 *
	 * @return string
	 */
	protected function get_updated_label() {
		return sprintf(
			/* translators: %s: localized time string. */
			__('Updated %s', 'alloy-metal-price-api'),
			wp_date(get_option('time_format'))
		);
	}
}
