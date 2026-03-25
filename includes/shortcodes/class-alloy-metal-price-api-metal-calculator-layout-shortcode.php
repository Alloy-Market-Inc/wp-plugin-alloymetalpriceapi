<?php

/**
 * [metal_calculator_layout] shortcode handler.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Metal_Calculator_Layout_Shortcode {
	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'metal_calculator_layout';

	/**
	 * Grams in a troy ounce.
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
				'title' => __('Cash for Gold Calculator', 'alloy-metal-price-api'),
				'purity' => '24K',
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

		$calculator_markup = $this->calculator_renderer->render(
			array(
				'title'               => $title,
				'purity_karat'        => $purity_karat,
				'base_price_per_gram' => $spot_price_per_gram,
				'wrapper_class'       => '',
				'section_class'       => 'aur:w-full aur:rounded-3xl aur:bg-white aur:p-8 aur:font-sans aur:shadow-[0_4px_10px_rgba(0,0,0,0.1)]',
				'heading_class'       => 'aur:mb-10 aur:mt-5 aur:text-center aur:text-2xl! aur:font-semibold aur:text-black',
			)
		);

		$price_per_ounce = $spot_price_per_gram * self::TROY_OUNCE_IN_GRAMS;
		$price_per_kilo  = $spot_price_per_gram * 1000;

		ob_start();
?>
		<div class="aur:flex aur:w-full aur:justify-center aur:font-sans">
			<div class="aur:flex aur:w-full aur:max-w-7xl aur:flex-col aur:gap-15 aur:md:grid aur:md:grid-cols-2 aur:md:items-start">
				<div class="aur:w-full aur:md:max-w-150">
					<?php echo $calculator_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
					?>
				</div>

				<div class="aur:w-full aur:md:max-w-95 aur:space-y-5 aur:justify-self-end">
					<div class="aur:w-full aur:rounded-2xl aur:bg-white aur:p-5 aur:text-center aur:shadow-[0_4px_10px_rgba(0,0,0,0.1)]">
						<h3 class="aur:mb-4 aur:text-2xl! aur:font-semibold aur:text-accent!"><?php esc_html_e('Current Gold Prices', 'alloy-metal-price-api'); ?></h3>

						<div class="aur:space-y-3">
							<div class="aur:flex aur:items-center aur:justify-between aur:rounded-2xl aur:bg-slate-50 aur:px-4 aur:py-3 aur:text-lg aur:font-bold aur:text-slate-800">
								<span class="aur:font-normal aur:text-slate-500"><?php esc_html_e('Per Gram:', 'alloy-metal-price-api'); ?></span>
								<span class="aur:text-slate-800"><?php echo esc_html($this->format_currency($spot_price_per_gram)); ?></span>
							</div>
							<div class="aur:flex aur:items-center aur:justify-between aur:rounded-2xl aur:bg-slate-50 aur:px-4 aur:py-3 aur:text-lg aur:font-bold aur:text-slate-800">
								<span class="aur:font-normal aur:text-slate-500"><?php esc_html_e('Per Ounce:', 'alloy-metal-price-api'); ?></span>
								<span class="aur:text-slate-800"><?php echo esc_html($this->format_currency($price_per_ounce)); ?></span>
							</div>
							<div class="aur:flex aur:items-center aur:justify-between aur:rounded-2xl aur:bg-slate-50 aur:px-4 aur:py-3 aur:text-lg aur:font-bold aur:text-slate-800">
								<span class="aur:font-normal aur:text-slate-500"><?php esc_html_e('Per Kilo:', 'alloy-metal-price-api'); ?></span>
								<span class="aur:text-slate-800"><?php echo esc_html($this->format_currency($price_per_kilo)); ?></span>
							</div>
						</div>

						<div class="aur:mt-4 aur:text-sm aur:text-slate-500">
							<?php esc_html_e('Prices updated every minute via live market data.', 'alloy-metal-price-api'); ?>
						</div>
					</div>

					<div class="aur:w-full aur:rounded-2xl aur:bg-white aur:p-5 aur:text-center aur:shadow-[0_4px_10px_rgba(0,0,0,0.1)]">
						<h3 class="aur:mb-4 aur:text-2xl! aur:font-semibold aur:text-accent!"><?php esc_html_e('Gold Karat Marking Guide', 'alloy-metal-price-api'); ?></h3>

						<div class="aur:rounded-2xl aur:bg-slate-50 aur:px-4 aur:py-4 aur:text-left aur:text-sm aur:text-slate-600 aur:shadow-[0_2px_6px_rgba(0,0,0,0.1)]">
							<strong class="aur:text-slate-800"><?php esc_html_e('Gold Markings:', 'alloy-metal-price-api'); ?></strong>
							<ul class="aur:mt-3 aur:list-disc aur:space-y-1 aur:pl-5">
								<li><?php esc_html_e('24K: Marked "999" or "24K"', 'alloy-metal-price-api'); ?></li>
								<li><?php esc_html_e('22K: Marked "916" or "22K"', 'alloy-metal-price-api'); ?></li>
								<li><?php esc_html_e('18K: Marked "750" or "18K"', 'alloy-metal-price-api'); ?></li>
								<li><?php esc_html_e('14K: Marked "585" or "14K"', 'alloy-metal-price-api'); ?></li>
								<li><?php esc_html_e('10K: Marked "417" or "10K"', 'alloy-metal-price-api'); ?></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
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

		return 24;
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
