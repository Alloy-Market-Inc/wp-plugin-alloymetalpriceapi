<?php

/**
 * [metal_offer_card] shortcode handler.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Metal_Offer_Card_Shortcode {
	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'metal_offer_card';

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
	 * Register the shortcode and refresh handlers.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode(self::TAG, array($this, 'render'));
		add_action('wp_ajax_alloy_metal_price_api_refresh_offer_card', array($this, 'handle_refresh'));
		add_action('wp_ajax_nopriv_alloy_metal_price_api_refresh_offer_card', array($this, 'handle_refresh'));
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
				'title'  => __('Metal Price Offer Card', 'alloy-metal-price-api'),
				'purity' => '24K',
			),
			$atts,
			self::TAG
		);

		$this->assets->enqueue_frontend_assets();
		$this->assets->enqueue_frontend_scripts();

		$title        = sanitize_text_field((string) $atts['title']);
		$purity_karat = $this->parse_purity_karat($atts['purity']);
		$pricing      = $this->get_offer_card_pricing($purity_karat);
		$purity_label = $this->format_purity_label($purity_karat);
		$instance_id  = wp_unique_id('metal-offer-card-');

		ob_start();
?>
		<div class="aur:flex aur:w-full aur:justify-center aur:font-sans">
			<section
				id="<?php echo esc_attr($instance_id); ?>"
				class="js-metal-offer-card aur:w-full aur:rounded-3xl aur:bg-white aur:p-8 aur:font-sans aur:shadow-[0_8px_24px_rgba(0,0,0,0.06)]"
				data-purity="<?php echo esc_attr((string) $purity_karat); ?>">
				<!-- <h2 class="aur:mb-6 aur:text-center aur:text-2xl aur:font-semibold aur:text-primary">
				<?php echo esc_html($title); ?>
			</h2> -->

				<a href="https://thealloymarket.com/request-a-kit/" class="aur:block aur:no-underline!">
					<div class="aur:flex aur:flex-wrap aur:items-stretch aur:justify-between aur:gap-5 aur:text-slate-900">
						<div class="aur:min-w-65 aur:flex-1">
							<h2 class="aur:mb-2 aur:text-xl! aur:font-bold! aur:text-slate-900!">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: purity label like 14K. */
										__('%s Spot Price (per gram):', 'alloy-metal-price-api'),
										$purity_label
									)
								);
								?>
							</h2>
							<div class="js-metal-offer-card-spot aur:text-xl aur:text-slate-900">
								<?php echo esc_html($pricing['spot']); ?>
							</div>
						</div>

						<div class="aur:min-w-65 aur:flex-1 aur:rounded-2xl aur:border-2 aur:border-slate-900 aur:p-4 aur:text-center">
							<div class="aur:text-xl aur:font-semibold aur:text-slate-900"><?php esc_html_e('Pawn Shop Offer', 'alloy-metal-price-api'); ?></div>
							<div class="js-metal-offer-card-pawn aur:text-[22px] aur:text-slate-900"><?php echo esc_html($pricing['pawn']); ?></div>
						</div>

						<div class="aur:min-w-65 aur:flex-1 aur:rounded-2xl aur:border-2 aur:border-primary aur:p-4 aur:text-center">
							<div class="aur:text-xl aur:font-semibold aur:text-primary"><?php esc_html_e('Alloy\'s Estimated Offer', 'alloy-metal-price-api'); ?></div>
							<div class="js-metal-offer-card-alloy aur:text-[22px] aur:text-primary"><?php echo esc_html($pricing['alloy']); ?></div>
						</div>
					</div>
				</a>

				<div class="aur:mt-5 aur:flex! aur:flex-wrap! aur:items-center! aur:justify-between! aur:gap-4!">
					<div class="js-metal-offer-card-note aur:text-xs aur:leading-5 aur:text-slate-600 aur:flex-4">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: purity label like 14K. */
								__('Prices reflect live payout estimates for one gram of %s gold.', 'alloy-metal-price-api'),
								$purity_label
							)
						);
						?>
					</div>
					<button type="button" class="js-metal-offer-card-refresh aur:flex! aur:flex-1! aur:min-w-40 aur:justify-center aur:items-center aur:gap-2 aur:rounded-xl aur:border! aur:border-primary! aur:bg-primary! aur:px-4 aur:py-3 aur:text-sm aur:font-semibold aur:text-white! aur:fill-white! aur:hover:border-secondary! aur:hover:bg-white! aur:hover:text-secondary! aur:hover:fill-secondary! aur:transition-all aur:ease-in-out aur:hover:cursor-pointer aur:focus:outline-none test">
						<span class="aur:inline-block aur:w-4 aur:h-4" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
								<path d="M129.9 292.5C143.2 199.5 223.3 128 320 128C373 128 421 149.5 455.8 184.2C456 184.4 456.2 184.6 456.4 184.8L464 192L416.1 192C398.4 192 384.1 206.3 384.1 224C384.1 241.7 398.4 256 416.1 256L544.1 256C561.8 256 576.1 241.7 576.1 224L576.1 96C576.1 78.3 561.8 64 544.1 64C526.4 64 512.1 78.3 512.1 96L512.1 149.4L500.8 138.7C454.5 92.6 390.5 64 320 64C191 64 84.3 159.4 66.6 283.5C64.1 301 76.2 317.2 93.7 319.7C111.2 322.2 127.4 310 129.9 292.6zM573.4 356.5C575.9 339 563.7 322.8 546.3 320.3C528.9 317.8 512.6 330 510.1 347.4C496.8 440.4 416.7 511.9 320 511.9C267 511.9 219 490.4 184.2 455.7C184 455.5 183.8 455.3 183.6 455.1L176 447.9L223.9 447.9C241.6 447.9 255.9 433.6 255.9 415.9C255.9 398.2 241.6 383.9 223.9 383.9L96 384C87.5 384 79.3 387.4 73.3 393.5C67.3 399.6 63.9 407.7 64 416.3L65 543.3C65.1 561 79.6 575.2 97.3 575C115 574.8 129.2 560.4 129 542.7L128.6 491.2L139.3 501.3C185.6 547.4 249.5 576 320 576C449 576 555.7 480.6 573.4 356.5z" />
							</svg></span>
						<span><?php esc_html_e('Refresh Prices', 'alloy-metal-price-api'); ?></span>
					</button>
				</div>
			</section>
		</div>
<?php

		return trim((string) ob_get_clean());
	}

	/**
	 * Public AJAX refresh handler.
	 *
	 * @return void
	 */
	public function handle_refresh() {
		check_ajax_referer('alloy_metal_price_api_refresh', 'nonce');

		$purity_karat = $this->parse_purity_karat(isset($_POST['purity']) ? wp_unslash($_POST['purity']) : '24K');

		wp_send_json_success($this->get_offer_card_pricing($purity_karat));
	}

	/**
	 * Build offer card pricing strings for a purity.
	 *
	 * @param int $purity_karat Purity karat value.
	 * @return array<string, string>
	 */
	protected function get_offer_card_pricing($purity_karat) {
		$spot_price_per_gram = $this->api_client->get_metal_price('gold');

		if (is_wp_error($spot_price_per_gram)) {
			return array(
				'spot'  => __('Unavailable', 'alloy-metal-price-api'),
				'pawn'  => __('Unavailable', 'alloy-metal-price-api'),
				'alloy' => __('Unavailable', 'alloy-metal-price-api'),
			);
		}

		$purity_multiplier = $purity_karat / 24;
		$spot_price        = $spot_price_per_gram * $purity_multiplier;
		$pawn_offer        = $spot_price * 0.4;
		$alloy_offer       = $spot_price * $this->get_alloy_offer_rate($purity_karat);

		return array(
			'spot'  => $this->format_currency($spot_price),
			'pawn'  => $this->format_currency($pawn_offer),
			'alloy' => $this->format_currency($alloy_offer),
		);
	}

	/**
	 * Get the Alloy offer multiplier for a karat value.
	 *
	 * @param int $purity_karat Purity karat value.
	 * @return float
	 */
	protected function get_alloy_offer_rate($purity_karat) {
		if (22 === (int) $purity_karat || 24 === (int) $purity_karat) {
			return 0.85;
		}

		return 0.7;
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
	 * Format a karat integer as a purity label.
	 *
	 * @param int $karat Purity karat value.
	 * @return string
	 */
	protected function format_purity_label($karat) {
		return absint($karat) . 'K';
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
