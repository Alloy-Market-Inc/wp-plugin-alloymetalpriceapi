<?php

/**
 * [metal_budget_buy_widget] shortcode handler.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Metal_Budget_Buy_Widget_Shortcode {
	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'metal_budget_buy_widget';

	/**
	 * Grams per troy ounce.
	 *
	 * @var float
	 */
	const TROY_OUNCE_IN_GRAMS = 31.1035;

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
				'title'   => '',
				'metal'   => 'gold',
				'budget'  => '10000',
				'premium' => '5',
			),
			$atts,
			self::TAG
		);

		$this->assets->enqueue_frontend_assets();
		$this->assets->enqueue_frontend_scripts();

		$metal       = $this->normalize_metal($atts['metal']);
		$metal_label = $this->get_metal_label($metal);
		$budget      = $this->parse_budget($atts['budget']);
		$premium     = $this->parse_premium($atts['premium']);
		$title       = '' !== trim((string) $atts['title'])
			? sanitize_text_field((string) $atts['title'])
			: sprintf(
				/* translators: 1: formatted budget, 2: metal label. */
				__('How much %2$s will %1$s buy?', 'alloy-metal-price-api'),
				'$' . number_format_i18n($budget, 0),
				strtolower($metal_label)
			);

		$spot_price_per_gram = $this->api_client->get_cached_metal_price($metal);

		if (null === $spot_price_per_gram) {
			$spot_price_per_gram = 0;
		}

		$spot_price_per_ounce = (float) $spot_price_per_gram * self::TROY_OUNCE_IN_GRAMS;
		$output_ounces        = $this->calculate_ounces($budget, $spot_price_per_ounce, $premium);

		ob_start();
		?>
		<div class="aur:flex aur:w-full aur:justify-center aur:font-sans">
			<div
				class="js-metal-budget-buy-widget js-alloy-live-spot-ounce aur:w-full aur:max-w-160 aur:rounded-2xl aur:border aur:border-slate-300 aur:bg-white aur:p-6 aur:shadow-[0_4px_10px_rgba(0,0,0,0.08)]"
				data-metal="<?php echo esc_attr($metal); ?>"
				data-budget="<?php echo esc_attr(number_format((float) $budget, 2, '.', '')); ?>"
				data-spot-ounce="<?php echo esc_attr(number_format($spot_price_per_ounce, 2, '.', '')); ?>">
				<h3 class="aur:mb-3 aur:mt-0 aur:text-2xl! aur:font-semibold aur:text-slate-900!">
					<?php echo esc_html($title); ?>
				</h3>

				<p class="aur:mb-3 aur:mt-0 aur:text-base aur:text-slate-700">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: metal label. */
							__('Live spot price for %s (per oz):', 'alloy-metal-price-api'),
							strtolower($metal_label)
						)
					);
					?>
					<strong class="aur:whitespace-nowrap aur:font-semibold aur:text-slate-900">
						<span class="js-alloy-live-price" data-metal="<?php echo esc_attr($metal); ?>" data-price-factor="<?php echo esc_attr((string) self::TROY_OUNCE_IN_GRAMS); ?>"><?php echo esc_html($this->format_currency($spot_price_per_ounce)); ?></span>
					</strong>
				</p>

				<div class="aur:mb-3 aur:mt-3 aur:flex aur:flex-wrap aur:items-center aur:gap-2 aur:text-base aur:text-slate-700">
					<span><?php esc_html_e('Assumed average premium (%):', 'alloy-metal-price-api'); ?></span>
					<input
						type="number"
						min="0"
						max="30"
						step="0.5"
						value="<?php echo esc_attr(number_format((float) $premium, 1, '.', '')); ?>"
						aria-label="<?php esc_attr_e('Assumed average premium percent', 'alloy-metal-price-api'); ?>"
						class="js-metal-budget-buy-premium aur:w-18 aur:rounded-md! aur:border aur:border-slate-300 aur:bg-white aur:px-3 aur:py-2 aur:text-base aur:text-slate-900 aur:focus:border-primary aur:focus:outline-none">
					<span class="aur:whitespace-nowrap aur:text-sm aur:text-slate-500"><?php esc_html_e('(example: 3% to 10%)', 'alloy-metal-price-api'); ?></span>
				</div>

				<p class="aur:mb-2 aur:mt-3 aur:text-base aur:text-slate-700">
					<?php esc_html_e('Estimated metal you can buy:', 'alloy-metal-price-api'); ?>
					<strong class="aur:font-semibold aur:text-slate-900">
						<span class="js-metal-budget-buy-output"><?php echo esc_html(number_format((float) $output_ounces, 3, '.', '')); ?></span>
						<?php esc_html_e('oz', 'alloy-metal-price-api'); ?>
					</strong>
				</p>

				<p class="aur:m-0 aur:text-xs aur:leading-5 aur:text-slate-500">
					<?php esc_html_e('Estimate uses spot price plus premium. Actual dealer pricing varies by product, availability, and seller fees.', 'alloy-metal-price-api'); ?>
				</p>
			</div>
		</div>
		<?php

		$content = trim((string) ob_get_clean());

		return Alloy_Metal_Price_API_Shortcode_Shell::render(
			$content,
			Alloy_Metal_Price_API_Shortcode_Shell::card_skeleton(3),
			self::TAG
		);
	}

	/**
	 * Calculate purchasable ounces for a budget.
	 *
	 * @param float $budget Budget amount in USD.
	 * @param float $spot_price_per_ounce Live spot price per ounce.
	 * @param float $premium_percent Premium percentage.
	 * @return float
	 */
	protected function calculate_ounces($budget, $spot_price_per_ounce, $premium_percent) {
		if ($budget <= 0 || $spot_price_per_ounce <= 0) {
			return 0;
		}

		$denominator = $spot_price_per_ounce * (1 + ($premium_percent / 100));

		if ($denominator <= 0) {
			return 0;
		}

		return $budget / $denominator;
	}

	/**
	 * Normalize a supported metal key.
	 *
	 * @param mixed $metal Raw metal value.
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
	 * Get a human-readable metal label.
	 *
	 * @param string $metal Normalized metal key.
	 * @return string
	 */
	protected function get_metal_label($metal) {
		$labels = array(
			'gold'      => __('Gold', 'alloy-metal-price-api'),
			'silver'    => __('Silver', 'alloy-metal-price-api'),
			'platinum'  => __('Platinum', 'alloy-metal-price-api'),
			'palladium' => __('Palladium', 'alloy-metal-price-api'),
		);

		return isset($labels[ $metal ]) ? $labels[ $metal ] : $labels['gold'];
	}

	/**
	 * Parse a positive budget amount.
	 *
	 * @param mixed $budget Raw budget value.
	 * @return float
	 */
	protected function parse_budget($budget) {
		$budget = is_numeric($budget) ? (float) $budget : 10000;

		return max(0, $budget);
	}

	/**
	 * Parse a premium percentage.
	 *
	 * @param mixed $premium Raw premium value.
	 * @return float
	 */
	protected function parse_premium($premium) {
		$premium = is_numeric($premium) ? (float) $premium : 5;

		return max(0, min(30, $premium));
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
