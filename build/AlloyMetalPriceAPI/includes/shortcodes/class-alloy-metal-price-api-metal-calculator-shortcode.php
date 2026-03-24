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
				'title'  => __('Gold Calculator', 'alloy-metal-price-api'),
				'purity' => '14K',
			),
			$atts,
			self::TAG
		);

		$this->assets->enqueue_frontend_assets();
		$this->assets->enqueue_frontend_scripts();

		$title             = sanitize_text_field((string) $atts['title']);
		$purity_karat      = $this->parse_purity_karat($atts['purity']);
		$spot_price_per_gram = $this->api_client->get_metal_price('gold');

		if (is_wp_error($spot_price_per_gram)) {
			$spot_price_per_gram = 0;
		}

		$instance_id = wp_unique_id('alloy-calculator-');
		$karats      = array(24, 22, 18, 16, 14, 10);

		ob_start();
?>
		<div class="aur:flex aur:w-full aur:justify-center aur:font-sans">
			<section
				id="<?php echo esc_attr($instance_id); ?>"
				class="js-alloy-calculator aur:mx-auto aur:w-full aur:max-w-130 aur:rounded-3xl aur:bg-white aur:p-5 aur:font-sans aur:shadow-[0_8px_24px_rgba(0,0,0,0.06)] aur:sm:p-6"
				data-base-price="<?php echo esc_attr((string) $spot_price_per_gram); ?>"
				data-default-karat="<?php echo esc_attr((string) $purity_karat); ?>">
				<h2 class="aur:mb-5 aur:text-center aur:text-2xl aur:font-semibold aur:text-primary">
					<?php echo esc_html($title); ?>
				</h2>

				<form class="js-alloy-calculator-form aur:grid aur:gap-4">
					<div>
						<label class="aur:mb-1 aur:block aur:text-sm aur:font-medium aur:text-slate-700">
							<?php esc_html_e('Current Price of Gold Per Gram ($):', 'alloy-metal-price-api'); ?>
						</label>
						<div class="aur:text-2xl aur:font-semibold aur:text-primary">
							$<span class="js-alloy-calculator-display-price"><?php echo esc_html(number_format_i18n((float) $spot_price_per_gram, 2)); ?></span>
						</div>
					</div>

					<div class="aur:grid aur:gap-4 aur:md:grid-cols-2">
						<div>
							<label for="<?php echo esc_attr($instance_id . '-karat'); ?>" class="aur:mb-1 aur:block aur:text-sm aur:font-medium aur:text-slate-700">
								<?php esc_html_e('Gold Karat:', 'alloy-metal-price-api'); ?>
							</label>
							<select id="<?php echo esc_attr($instance_id . '-karat'); ?>" class="js-alloy-calculator-karat aur:w-full aur:rounded-xl aur:border aur:border-slate-300 aur:bg-white aur:px-4 aur:py-3 aur:text-base aur:text-slate-900 aur:focus:border-primary aur:focus:outline-none">
								<?php foreach ($karats as $karat) : ?>
									<option value="<?php echo esc_attr((string) $karat); ?>" <?php selected($purity_karat, $karat); ?>>
										<?php echo esc_html(sprintf(__('%d Karat', 'alloy-metal-price-api'), $karat)); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div>
							<label for="<?php echo esc_attr($instance_id . '-weight-unit'); ?>" class="aur:mb-1 aur:block aur:text-sm aur:font-medium aur:text-slate-700">
								<?php esc_html_e('Weight Unit:', 'alloy-metal-price-api'); ?>
							</label>
							<select id="<?php echo esc_attr($instance_id . '-weight-unit'); ?>" class="js-alloy-calculator-weight-unit aur:w-full aur:rounded-xl aur:border aur:border-slate-300 aur:bg-white aur:px-4 aur:py-3 aur:text-base aur:text-slate-900 aur:focus:border-primary aur:focus:outline-none">
								<option value="grams"><?php esc_html_e('Grams', 'alloy-metal-price-api'); ?></option>
								<option value="ounces"><?php esc_html_e('Ounces', 'alloy-metal-price-api'); ?></option>
								<option value="pennyweight"><?php esc_html_e('Pennyweight', 'alloy-metal-price-api'); ?></option>
							</select>
						</div>
					</div>

					<div>
						<label for="<?php echo esc_attr($instance_id . '-weight'); ?>" class="aur:mb-1 aur:block aur:text-sm aur:font-medium aur:text-slate-700">
							<?php esc_html_e('Weight:', 'alloy-metal-price-api'); ?>
						</label>
						<input
							id="<?php echo esc_attr($instance_id . '-weight'); ?>"
							type="number"
							min="0"
							step="any"
							placeholder="<?php esc_attr_e('Enter weight', 'alloy-metal-price-api'); ?>"
							class="js-alloy-calculator-weight aur:w-full aur:rounded-xl aur:border aur:border-slate-300 aur:bg-white aur:px-4 aur:py-3 aur:text-base aur:text-slate-900 aur:placeholder:text-slate-400 aur:focus:border-primary aur:focus:outline-none">
					</div>

					<div>
						<button type="submit" class="js-alloy-calculator-calculate aur:inline-flex aur:w-full aur:hover:cursor-pointer aur:justify-center aur:rounded-xl aur:bg-primary aur:hover:bg-white aur:border aur:border-primary aur:hover:text-primary aur:px-4 aur:py-3 aur:text-base aur:font-semibold aur:text-white aur:transition-all aur:ease-in-out aur:focus:outline-none">
							<?php esc_html_e('Calculate Value', 'alloy-metal-price-api'); ?>
						</button>
					</div>

					<div class="js-alloy-calculator-results aur:hidden aur:gap-3 aur:lg:grid-cols-3">
						<div class="aur:rounded-2xl aur:border aur:border-slate-200 aur:bg-slate-50 aur:p-4">
							<h3 class="aur:mb-2 aur:text-base aur:font-semibold aur:text-slate-900"><?php esc_html_e('Current Market Value:', 'alloy-metal-price-api'); ?></h3>
							<p class="js-alloy-calculator-market-value aur:m-0 aur:text-2xl aur:font-semibold aur:text-primary">$0.00</p>
						</div>
						<div class="aur:rounded-2xl aur:border aur:border-red-200 aur:bg-red-50 aur:p-4">
							<h3 class="aur:mb-2 aur:text-base aur:font-semibold aur:text-slate-900"><?php esc_html_e('Average Pawn Shop Offer:', 'alloy-metal-price-api'); ?></h3>
							<p class="js-alloy-calculator-pawn-value aur:m-0 aur:text-2xl aur:font-semibold aur:text-red-600">$0.00</p>
						</div>
						<div class="aur:rounded-2xl aur:border aur:border-emerald-200 aur:bg-emerald-50 aur:p-4">
							<h3 class="aur:mb-2 aur:text-base aur:font-semibold aur:text-slate-900"><?php esc_html_e('Alloy\'s Estimated Offer:', 'alloy-metal-price-api'); ?></h3>
							<p class="js-alloy-calculator-alloy-value aur:m-0 aur:text-2xl aur:font-semibold aur:text-emerald-600">$0.00</p>
						</div>
					</div>

					<div class="js-alloy-calculator-cta aur:hidden">
						<a class="aur:inline-flex aur:w-full aur:justify-center aur:rounded-xl aur:border aur:border-secondary aur:bg-secondary aur:px-4 aur:py-5 aur:text-base aur:font-semibold aur:text-white! aur:no-underline! aur:transition-all aur:ease-in-out aur:hover:bg-white aur:hover:text-secondary! aur:hover:border-primary" href="https://thealloymarket.com/request-a-kit/?referral_trigger=checked&amp;referral_code=GOLDCALC">
							<?php esc_html_e('Get A Free Alloy Kit', 'alloy-metal-price-api'); ?>
						</a>
					</div>
				</form>
			</section>
		</div>
<?php

		return trim((string) ob_get_clean());
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
