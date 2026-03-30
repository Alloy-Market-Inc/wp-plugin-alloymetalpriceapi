<?php

/**
 * [metal_price_compare] shortcode handler.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Metal_Price_Compare_Shortcode {
	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'metal_price_compare';

	/**
	 * Grams in a troy ounce.
	 *
	 * @var float
	 */
	const TROY_OUNCE_IN_GRAMS = 31.1035;

	/**
	 * Supported metal labels.
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
				'metal_a' => 'platinum',
				'metal_b' => 'gold',
				'title'   => '',
			),
			$atts,
			self::TAG
		);

		$this->assets->enqueue_frontend_assets();

		$metal_a       = $this->normalize_metal($atts['metal_a']);
		$metal_b       = $this->normalize_metal($atts['metal_b']);
		$metal_a_label = self::METAL_LABELS[$metal_a];
		$metal_b_label = self::METAL_LABELS[$metal_b];
		$title         = sanitize_text_field((string) $atts['title']);

		if ('' === $title) {
			$title = sprintf(
				/* translators: 1: first metal label, 2: second metal label. */
				__('%1$s vs %2$s Price Today (Per Troy Ounce)', 'alloy-metal-price-api'),
				$metal_a_label,
				$metal_b_label
			);
		}

		$price_a = $this->get_price_per_troy_ounce($metal_a);
		$price_b = $this->get_price_per_troy_ounce($metal_b);

		return $this->render_card(
			$title,
			$metal_a_label,
			$price_a,
			$metal_b_label,
			$price_b,
			(is_wp_error($price_a) || is_wp_error($price_b)) ? __('Feed error', 'alloy-metal-price-api') : $this->get_updated_label()
		);
	}

	/**
	 * Render the comparison card.
	 *
	 * @param string         $title Title text.
	 * @param string         $metal_a_label First metal label.
	 * @param float|\WP_Error $price_a First metal price per troy ounce.
	 * @param string         $metal_b_label Second metal label.
	 * @param float|\WP_Error $price_b Second metal price per troy ounce.
	 * @param string         $updated_label Updated label text.
	 * @return string
	 */
	protected function render_card($title, $metal_a_label, $price_a, $metal_b_label, $price_b, $updated_label) {
		ob_start();
?>
		<div class="aur:flex aur:w-full aur:justify-center aur:font-sans">
			<div class="aur:w-full aur:rounded-2xl aur:max-w-180 aur:mx-auto aur:border aur:border-slate-900 aur:bg-white aur:p-6 aur:shadow-[0_4px_16px_rgba(0,0,0,0.06)]">
				<div class="aur:mb-5 aur:text-center aur:text-xl! aur:font-semibold aur:text-primary!">
					<?php echo esc_html($title); ?>
				</div>

				<div class="aur:space-y-3">
					<div class="aur:flex aur:items-center aur:justify-between aur:rounded-xl aur:bg-slate-50 aur:px-4 aur:py-3">
						<span class="aur:text-base aur:font-medium aur:text-slate-900"><?php echo esc_html($metal_a_label); ?></span>
						<span class="aur:text-xl aur:font-semibold aur:text-primary"><?php echo esc_html($this->format_price($price_a)); ?></span>
					</div>

					<div class="aur:flex aur:items-center aur:justify-between aur:rounded-xl aur:bg-slate-50 aur:px-4 aur:py-3">
						<span class="aur:text-base aur:font-medium aur:text-slate-900"><?php echo esc_html($metal_b_label); ?></span>
						<span class="aur:text-xl aur:font-semibold aur:text-primary"><?php echo esc_html($this->format_price($price_b)); ?></span>
					</div>
				</div>

				<div class="aur:mt-5 aur:flex aur:items-center aur:justify-center aur:gap-2 aur:text-sm aur:text-slate-500">
					<span class="aur:inline-block aur:h-2 aur:w-2 aur:rounded-full aur:bg-secondary" aria-hidden="true"></span>
					<span><?php echo esc_html($updated_label); ?></span>
				</div>
			</div>
		</div>
<?php

		return trim((string) ob_get_clean());
	}

	/**
	 * Get the current metal price per troy ounce.
	 *
	 * @param string $metal Normalized metal key.
	 * @return float|\WP_Error
	 */
	protected function get_price_per_troy_ounce($metal) {
		$price_per_gram = $this->api_client->get_metal_price($metal);

		if (is_wp_error($price_per_gram)) {
			return $price_per_gram;
		}

		return (float) $price_per_gram * self::TROY_OUNCE_IN_GRAMS;
	}

	/**
	 * Format a live price value.
	 *
	 * @param float|\WP_Error $price Monetary value.
	 * @return string
	 */
	protected function format_price($price) {
		if (is_wp_error($price)) {
			return '$—';
		}

		return '$' . number_format_i18n((float) $price, 2);
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
