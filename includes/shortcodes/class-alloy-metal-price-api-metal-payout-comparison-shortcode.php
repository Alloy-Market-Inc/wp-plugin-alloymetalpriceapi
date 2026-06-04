<?php

/**
 * [metal_payout_comparison] shortcode handler.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Metal_Payout_Comparison_Shortcode {
	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'metal_payout_comparison';

	/**
	 * Grams in a troy ounce.
	 *
	 * @var float
	 */
	const TROY_OUNCE_IN_GRAMS = 31.1035;

	/**
	 * Karat values displayed in the comparison table.
	 *
	 * @var array<int, int>
	 */
	const KARATS = array(24, 22, 18, 14, 10);

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
				'title'    => __('Pawn Shop Payout per Gram Karat Comparison', 'alloy-metal-price-api'),
				'link_url' => 'https://thealloymarket.com/request-a-kit',
			),
			$atts,
			self::TAG
		);

		$this->assets->enqueue_frontend_assets();
		$this->assets->enqueue_frontend_scripts();

		$title               = sanitize_text_field((string) $atts['title']);
		$link_url            = esc_url((string) $atts['link_url']);
		$spot_price_per_gram = $this->api_client->get_cached_metal_price('gold');

		if (null === $spot_price_per_gram) {
			return $this->render_unavailable_state($title, $link_url);
		}

		$rows = array();

		foreach (self::KARATS as $karat) {
			$spot_price = $spot_price_per_gram * ($karat / 24);
			$pawn_offer = $spot_price * 0.4;
			$alloy_offer = $spot_price * $this->get_alloy_offer_rate($karat);

			$rows[] = array(
				'karat'        => absint($karat) . 'K',
				'spot'         => $this->format_currency($spot_price),
				'pawn'         => $this->format_currency($pawn_offer),
				'alloy'        => $this->format_currency($alloy_offer),
				'spot_factor'  => $karat / 24,
				'pawn_factor'  => ($karat / 24) * 0.4,
				'alloy_factor' => ($karat / 24) * $this->get_alloy_offer_rate($karat),
			);
		}

		return $this->render_card(
			$title,
			$link_url,
			$rows,
			__('Prices reflect live payout estimates for one gram of gold, based on current 24K spot price.', 'alloy-metal-price-api'),
			(string) round($spot_price_per_gram * self::TROY_OUNCE_IN_GRAMS, 2)
		);
	}

	/**
	 * Render the unavailable state.
	 *
	 * @param string $title Comparison title.
	 * @param string $link_url Destination URL.
	 * @return string
	 */
	protected function render_unavailable_state($title, $link_url) {
		$rows = array();

		foreach (self::KARATS as $karat) {
			$rows[] = array(
				'karat' => absint($karat) . 'K',
				'spot'  => __('Unavailable', 'alloy-metal-price-api'),
				'pawn'  => __('Unavailable', 'alloy-metal-price-api'),
				'alloy' => __('Unavailable', 'alloy-metal-price-api'),
			);
		}

		return $this->render_card(
			$title,
			$link_url,
			$rows,
			__('Prices are temporarily unavailable. Please refresh and try again.', 'alloy-metal-price-api'),
			__('Unavailable', 'alloy-metal-price-api')
		);
	}

	/**
	 * Render the payout comparison card.
	 *
	 * @param string                            $title Comparison title.
	 * @param string                            $link_url Destination URL.
	 * @param array<int, array<string, string>> $rows Table rows.
	 * @param string                            $footnote Footnote text.
	 * @param string                            $hidden_gold_price Hidden ounce price output.
	 * @return string
	 */
	protected function render_card($title, $link_url, $rows, $footnote, $hidden_gold_price) {
		$refresh_url = get_permalink();

		if (! is_string($refresh_url) || '' === $refresh_url) {
			$refresh_url = $link_url;
		}

		ob_start();
?>
		<div class="aur:relative aur:my-7">
			<!-- <a href="<?php echo esc_url($refresh_url); ?>" class="aur:absolute aur:right-4 aur:top-4 aur:z-20 aur:flex aur:w-fit aur:items-center aur:justify-center aur:gap-2 aur:rounded-full aur:border! aur:border-primary! aur:bg-primary! aur:px-4 aur:py-3 aur:text-sm aur:font-semibold aur:text-white! aur:fill-white! aur:no-underline! aur:transition-all aur:ease-in-out aur:hover:cursor-pointer aur:hover:border-secondary! aur:hover:bg-white! aur:hover:text-secondary! aur:hover:fill-secondary! aur:focus:outline-none">
				<span class="aur:inline-block aur:h-4 aur:w-4" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
						<path d="M129.9 292.5C143.2 199.5 223.3 128 320 128C373 128 421 149.5 455.8 184.2C456 184.4 456.2 184.6 456.4 184.8L464 192L416.1 192C398.4 192 384.1 206.3 384.1 224C384.1 241.7 398.4 256 416.1 256L544.1 256C561.8 256 576.1 241.7 576.1 224L576.1 96C576.1 78.3 561.8 64 544.1 64C526.4 64 512.1 78.3 512.1 96L512.1 149.4L500.8 138.7C454.5 92.6 390.5 64 320 64C191 64 84.3 159.4 66.6 283.5C64.1 301 76.2 317.2 93.7 319.7C111.2 322.2 127.4 310 129.9 292.6zM573.4 356.5C575.9 339 563.7 322.8 546.3 320.3C528.9 317.8 512.6 330 510.1 347.4C496.8 440.4 416.7 511.9 320 511.9C267 511.9 219 490.4 184.2 455.7C184 455.5 183.8 455.3 183.6 455.1L176 447.9L223.9 447.9C241.6 447.9 255.9 433.6 255.9 415.9C255.9 398.2 241.6 383.9 223.9 383.9L96 384C87.5 384 79.3 387.4 73.3 393.5C67.3 399.6 63.9 407.7 64 416.3L65 543.3C65.1 561 79.6 575.2 97.3 575C115 574.8 129.2 560.4 129 542.7L128.6 491.2L139.3 501.3C185.6 547.4 249.5 576 320 576C449 576 555.7 480.6 573.4 356.5z" />
					</svg></span>
				<span class="aur:hidden"><?php esc_html_e('Refresh Prices', 'alloy-metal-price-api'); ?></span>
			</a> -->

			<a href="<?php echo esc_url($link_url); ?>" class="aur:relative aur:z-10 aur:block aur:no-underline! aur:text-inherit!">
				<div class="aur:w-full aur:rounded-3xl aur:bg-white aur:px-7 aur:pb-6 aur:pt-6 aur:shadow-[0_4px_20px_rgba(0,0,0,0.08)]">
					<h2 class="aur:mb-5 aur:text-center aur:text-2xl! aur:font-semibold aur:text-slate-900!">
						<?php echo esc_html($title); ?>
					</h2>

					<table aria-label="<?php esc_attr_e('Pawn shop and Alloy gold payout comparison by karat', 'alloy-metal-price-api'); ?>" class="aur:w-full aur:border-collapse aur:text-center aur:text-base aur:text-slate-900">
						<thead class="aur:bg-slate-100">
							<tr>
								<th class="aur:px-2 aur:py-2 aur:text-sm aur:leading-tight aur:font-semibold"><?php esc_html_e('Karat', 'alloy-metal-price-api'); ?></th>
								<th class="aur:px-2 aur:py-2 aur:text-sm aur:leading-tight aur:font-semibold">
									<?php esc_html_e('Spot Price', 'alloy-metal-price-api'); ?><br>
									<?php esc_html_e('(per gram)', 'alloy-metal-price-api'); ?>
								</th>
								<th class="aur:px-2 aur:py-2 aur:text-sm aur:leading-tight aur:font-semibold">
									<?php esc_html_e('Pawn Shop', 'alloy-metal-price-api'); ?><br>
									<?php esc_html_e('Offer', 'alloy-metal-price-api'); ?>
								</th>
								<th class="aur:px-2 aur:py-2 aur:text-sm aur:leading-tight aur:font-semibold">
									<?php esc_html_e('Alloy Estimated', 'alloy-metal-price-api'); ?><br>
									<?php esc_html_e('Offer', 'alloy-metal-price-api'); ?>
								</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($rows as $index => $row) : ?>
								<tr class="<?php echo esc_attr(0 === $index % 2 ? 'aur:bg-white' : 'aur:bg-table-row-alt'); ?> aur:group">
									<td class="aur:px-2 aur:py-2 aur:group-hover:bg-secondary! aur:group-hover:text-white!"><?php echo esc_html($row['karat']); ?></td>
										<td class="aur:px-2 aur:py-2 aur:group-hover:bg-secondary! aur:group-hover:text-white!"><?php echo $this->render_live_price_cell($row, 'spot'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
										<td class="aur:px-2 aur:py-2 aur:group-hover:bg-secondary! aur:group-hover:text-white!"><?php echo $this->render_live_price_cell($row, 'pawn'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
										<td class="aur:px-2 aur:py-2 aur:font-medium aur:text-primary  aur:group-hover:bg-primary! aur:group-hover:text-white!"><?php echo $this->render_live_price_cell($row, 'alloy'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<div class="aur:mt-3 aur:text-center aur:text-xs aur:leading-tight aur:text-slate-600">
						<?php echo esc_html($footnote); ?>
					</div>

						<div id="currentGoldPrice" class="aur:hidden">
							<span class="aur:text-base aur:font-sans" data-metal-symbol="xau" data-metal-unit="ounce">
								<span class="js-alloy-live-price" data-metal="gold" data-price-factor="<?php echo esc_attr((string) self::TROY_OUNCE_IN_GRAMS); ?>" data-price-format="number" data-decimals="2"><?php echo esc_html($hidden_gold_price); ?></span>
							</span>
						</div>
				</div>
			</a>
		</div>
<?php

		$content = trim((string) ob_get_clean());

		return Alloy_Metal_Price_API_Shortcode_Shell::render(
			$content,
			Alloy_Metal_Price_API_Shortcode_Shell::table_skeleton(count($rows)),
			self::TAG
		);
	}

	/**
	 * Render a table value with an optional live-price hydration hook.
	 *
	 * @param array<string, string|float> $row Row data.
	 * @param string                      $key Value key.
	 * @return string
	 */
	protected function render_live_price_cell($row, $key) {
		$factor_key = $key . '_factor';

		if (! isset($row[$factor_key])) {
			return esc_html($row[$key]);
		}

		return sprintf(
			'<span class="js-alloy-live-price" data-metal="gold" data-price-factor="%1$s">%2$s</span>',
			esc_attr((string) $row[$factor_key]),
			esc_html($row[$key])
		);
	}

	/**
	 * Get the Alloy offer multiplier for a karat value.
	 *
	 * @param int $karat Purity karat value.
	 * @return float
	 */
	protected function get_alloy_offer_rate($karat) {
		if (22 === (int) $karat || 24 === (int) $karat) {
			return 0.85;
		}

		return 0.7;
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
