<?php

/**
 * [conversion_rate_calculator] shortcode handler.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Conversion_Rate_Calculator_Shortcode {
	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'conversion_rate_calculator';

	/**
	 * Shared asset manager.
	 *
	 * @var Alloy_Metal_Price_API_Assets
	 */
	protected $assets;

	/**
	 * Constructor.
	 *
	 * @param Alloy_Metal_Price_API_Assets $assets Asset manager.
	 */
	public function __construct(Alloy_Metal_Price_API_Assets $assets) {
		$this->assets = $assets;
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
				'title'         => __('Advanced Conversion Rate Calculator', 'alloy-metal-price-api'),
				'sale_amount'   => '1000',
				'hard_cost'     => '25',
				'profit_margin' => '20',
			),
			$atts,
			self::TAG
		);

		$this->assets->enqueue_frontend_assets();
		$this->assets->enqueue_frontend_scripts();

		$title         = sanitize_text_field((string) $atts['title']);
		$sale_amount   = $this->sanitize_number($atts['sale_amount'], 1000, 50);
		$hard_cost     = $this->sanitize_number($atts['hard_cost'], 25, 1, 100);
		$profit_margin = $this->sanitize_number($atts['profit_margin'], 20, 1, 50);
		$sale_id       = wp_unique_id('conversion-sale-');
		$hard_cost_id  = wp_unique_id('conversion-hard-cost-');
		$profit_id     = wp_unique_id('conversion-profit-margin-');

		ob_start();
?>
		<div class="aur:flex aur:w-full aur:justify-center aur:font-sans">
			<section class="js-conversion-rate-calculator aur:w-full aur:max-w-130 aur:rounded-3xl aur:bg-white aur:p-5 aur:shadow-[0_8px_24px_rgba(0,0,0,0.06)] aur:sm:p-6">
				<h2 class="aur:mb-5 aur:text-center aur:text-xl! aur:font-semibold aur:text-primary!">
					<?php echo esc_html($title); ?>
				</h2>

				<form class="js-conversion-rate-calculator-form aur:grid aur:gap-5">
					<div class="aur:grid aur:gap-2">
						<label for="<?php echo esc_attr($sale_id); ?>" class="aur:text-sm aur:font-medium aur:text-slate-700">
							<?php esc_html_e('Sale Amount ($):', 'alloy-metal-price-api'); ?>
						</label>
						<input
							id="<?php echo esc_attr($sale_id); ?>"
							type="number"
							min="50"
							step="50"
							value="<?php echo esc_attr(number_format((float) $sale_amount, 0, '.', '')); ?>"
							class="js-conversion-sale-amount aur:w-full aur:rounded-md! aur:border aur:border-slate-300 aur:bg-white aur:px-4 aur:py-3! aur:text-base aur:text-slate-900 aur:focus:border-primary aur:focus:outline-none">
					</div>

					<div class="aur:grid aur:gap-2">
						<label for="<?php echo esc_attr($hard_cost_id); ?>" class="aur:text-sm aur:font-medium aur:text-slate-700">
							<?php
							printf(
								/* translators: %s: current hard cost value. */
								esc_html__('Internal Hard Cost ($): %s', 'alloy-metal-price-api'),
								'<span class="js-conversion-hard-cost-display">' . esc_html(number_format((float) $hard_cost, 0, '.', '')) . '</span>'
							);
							?>
						</label>
						<input
							id="<?php echo esc_attr($hard_cost_id); ?>"
							type="range"
							min="1"
							max="100"
							value="<?php echo esc_attr(number_format((float) $hard_cost, 0, '.', '')); ?>"
							class="js-conversion-hard-cost aur:w-full aur:accent-primary">
					</div>

					<div class="aur:grid aur:gap-2">
						<label for="<?php echo esc_attr($profit_id); ?>" class="aur:text-sm aur:font-medium aur:text-slate-700">
							<?php
							printf(
								/* translators: %s: current profit margin value. */
								esc_html__('Profit Margin (%%): %s%%', 'alloy-metal-price-api'),
								'<span class="js-conversion-profit-margin-display">' . esc_html(number_format((float) $profit_margin, 0, '.', '')) . '</span>'
							);
							?>
						</label>
						<input
							id="<?php echo esc_attr($profit_id); ?>"
							type="range"
							min="1"
							max="50"
							value="<?php echo esc_attr(number_format((float) $profit_margin, 0, '.', '')); ?>"
							class="js-conversion-profit-margin aur:w-full aur:accent-primary">
					</div>

					<div>
						<button type="submit" class="js-conversion-rate-calculate aur:inline-flex aur:w-full aur:justify-center aur:rounded-md! aur:border! aur:border-accent! aur:bg-accent! aur:px-4 aur:py-3 aur:text-base aur:font-semibold aur:text-white aur:transition-all aur:ease-in-out aur:hover:bg-white! aur:hover:text-accent! aur:focus:outline-none">
							<?php esc_html_e('Calculate', 'alloy-metal-price-api'); ?>
						</button>
					</div>

					<div class="js-conversion-rate-result aur:rounded-2xl aur:border aur:border-slate-200 aur:bg-slate-50 aur:p-4 aur:text-lg aur:leading-7 aur:text-slate-700 aur:flex aur:flex-col aur:items-start aur:gap-2">
						<p class="aur:m-0"><?php esc_html_e('Adjust the values and click “Calculate” to see the required conversion rate.', 'alloy-metal-price-api'); ?></p>
					</div>
				</form>
			</section>
		</div>
<?php

		return trim((string) ob_get_clean());
	}

	/**
	 * Sanitize a numeric shortcode value.
	 *
	 * @param mixed    $value Raw value.
	 * @param float    $fallback Fallback value.
	 * @param float    $min Minimum allowed value.
	 * @param float|null $max Maximum allowed value.
	 * @return float
	 */
	protected function sanitize_number($value, $fallback, $min = 0, $max = null) {
		$value = is_numeric($value) ? (float) $value : (float) $fallback;
		$value = max($min, $value);

		if (null !== $max) {
			$value = min($max, $value);
		}

		return $value;
	}
}
