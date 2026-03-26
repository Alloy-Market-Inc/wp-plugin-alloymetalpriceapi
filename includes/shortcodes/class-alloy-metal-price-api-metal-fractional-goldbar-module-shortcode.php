<?php

/**
 * [metal_fractional_goldbar_module] shortcode handler.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Metal_Fractional_Goldbar_Module_Shortcode {
	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'metal_fractional_goldbar_module';

	/**
	 * Grams in a troy ounce.
	 *
	 * @var float
	 */
	const TROY_OUNCE_IN_GRAMS = 31.1035;

	/**
	 * Bar purity multiplier.
	 *
	 * @var float
	 */
	const BAR_PURITY = 0.9999;

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
		$this->assets->enqueue_frontend_assets();

		$spot_price_per_gram  = $this->api_client->get_metal_price('gold');
		$spot_price_per_ounce = is_wp_error($spot_price_per_gram) ? null : $spot_price_per_gram * self::TROY_OUNCE_IN_GRAMS;

		return $this->render_module($this->build_rows($spot_price_per_gram), $spot_price_per_ounce);
	}

	/**
	 * Render the shortcode module.
	 *
	 * @param array<int, array<string, string>> $rows Fractional gold bar rows.
	 * @param float|null                        $spot_price_per_ounce Current spot price per troy ounce.
	 * @return string
	 */
	protected function render_module(array $rows, $spot_price_per_ounce) {
		ob_start();
		?>
		<div id="alloy-fractional-goldbar-module" class="aur:w-full aur:font-sans">
			<div class="aur:mb-5 aur:flex aur:flex-col aur:items-center aur:gap-2 aur:text-center">
				<div class="aur:text-[22px] aur:font-semibold aur:text-primary">
					<?php esc_html_e('Fractional Gold Bars (Live Melt Value)', 'alloy-metal-price-api'); ?>
				</div>
				<div class="aur:text-sm aur:text-slate-600">
					<?php esc_html_e('Spot price (USD / troy oz):', 'alloy-metal-price-api'); ?>
					<span class="aur:font-semibold aur:text-slate-900">
						$
						<span class="alloy-spot-price">
							<?php echo esc_html(null === $spot_price_per_ounce ? __('Unavailable', 'alloy-metal-price-api') : number_format((float) $spot_price_per_ounce, 2, '.', '')); ?>
						</span>
					</span>
				</div>
			</div>

			<div class="aur:hidden aur:md:block">
				<div class="aur:w-full aur:overflow-x-auto aur:rounded-2xl aur:border aur:border-slate-900 aur:bg-white aur:shadow-[0_4px_16px_rgba(0,0,0,0.06)]">
					<table class="aur:w-full aur:border-collapse aur:text-left">
						<thead>
							<tr class="aur:bg-slate-50">
								<th class="aur:px-4 aur:py-4 aur:text-sm aur:font-semibold aur:text-slate-900"><?php esc_html_e('Bar size', 'alloy-metal-price-api'); ?></th>
								<th class="aur:px-4 aur:py-4 aur:text-sm aur:font-semibold aur:text-slate-900"><?php esc_html_e('Weight (g)', 'alloy-metal-price-api'); ?></th>
								<th class="aur:px-4 aur:py-4 aur:text-sm aur:font-semibold aur:text-slate-900"><?php esc_html_e('Weight (troy oz)', 'alloy-metal-price-api'); ?></th>
								<th class="aur:px-4 aur:py-4 aur:text-sm aur:font-semibold aur:text-slate-900"><?php esc_html_e('Live melt value', 'alloy-metal-price-api'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($rows as $index => $row) : ?>
								<tr class="<?php echo esc_attr(0 === $index % 2 ? 'aur:bg-table-row-alt' : ''); ?>">
									<td class="aur:px-4 aur:py-4 aur:text-base aur:text-slate-900"><strong><?php echo esc_html($row['size']); ?></strong></td>
									<td class="aur:px-4 aur:py-4 aur:text-base aur:text-slate-900"><?php echo esc_html($row['grams_display']); ?></td>
									<td class="aur:px-4 aur:py-4 aur:text-base aur:text-slate-900"><?php echo esc_html($row['troy_ounces_display']); ?></td>
									<td class="aur:px-4 aur:py-4 aur:text-base aur:font-semibold aur:text-primary"><strong><?php echo esc_html($row['melt_display']); ?></strong></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="aur:grid aur:grid-cols-1 aur:gap-4 aur:md:hidden">
				<?php foreach ($rows as $row) : ?>
					<div class="aur:rounded-2xl aur:border aur:border-slate-900 aur:bg-white aur:p-4 aur:shadow-[0_4px_16px_rgba(0,0,0,0.06)]">
						<div class="aur:mb-4 aur:flex aur:items-start aur:justify-between aur:gap-4">
							<div class="aur:text-lg aur:font-semibold aur:text-slate-900"><?php echo esc_html($row['size']); ?></div>
							<div class="aur:text-lg aur:font-semibold aur:text-primary"><?php echo esc_html($row['melt_display']); ?></div>
						</div>
						<div class="aur:space-y-3">
							<div class="aur:flex aur:items-center aur:justify-between aur:gap-4">
								<span class="aur:text-sm aur:font-medium aur:text-slate-500"><?php esc_html_e('Weight (g)', 'alloy-metal-price-api'); ?></span>
								<span class="aur:text-sm aur:font-semibold aur:text-slate-900"><?php echo esc_html($row['grams_display']); ?></span>
							</div>
							<div class="aur:flex aur:items-center aur:justify-between aur:gap-4">
								<span class="aur:text-sm aur:font-medium aur:text-slate-500"><?php esc_html_e('Weight (troy oz)', 'alloy-metal-price-api'); ?></span>
								<span class="aur:text-sm aur:font-semibold aur:text-slate-900"><?php echo esc_html($row['troy_ounces_display']); ?></span>
							</div>
							<div class="aur:flex aur:items-center aur:justify-between aur:gap-4">
								<span class="aur:text-sm aur:font-medium aur:text-slate-500"><?php esc_html_e('Purity', 'alloy-metal-price-api'); ?></span>
								<span class="aur:text-sm aur:font-semibold aur:text-slate-900"><?php echo esc_html($row['purity']); ?></span>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php

		return trim((string) ob_get_clean());
	}

	/**
	 * Build the static fractional gold bar rows.
	 *
	 * @param float|WP_Error $spot_price_per_gram Spot gold price per gram.
	 * @return array<int, array<string, string>>
	 */
	protected function build_rows($spot_price_per_gram) {
		$rows = array(
			array(
				'size'                => '1 g',
				'grams'               => 1,
				'grams_display'       => '1',
				'troy_ounces_display' => '0.0322',
			),
			array(
				'size'                => '2.5 g',
				'grams'               => 2.5,
				'grams_display'       => '2.5',
				'troy_ounces_display' => '0.0804',
			),
			array(
				'size'                => '5 g',
				'grams'               => 5,
				'grams_display'       => '5',
				'troy_ounces_display' => '0.1608',
			),
			array(
				'size'                => '10 g',
				'grams'               => 10,
				'grams_display'       => '10',
				'troy_ounces_display' => '0.3215',
			),
			array(
				'size'                => '20 g',
				'grams'               => 20,
				'grams_display'       => '20',
				'troy_ounces_display' => '0.6430',
			),
		);

		foreach ($rows as &$row) {
			$row['purity']       = '.9999';
			$row['melt_display'] = $this->format_melt_value($spot_price_per_gram, (float) $row['grams']);
		}
		unset($row);

		return $rows;
	}

	/**
	 * Format the melt value for a fractional gold bar row.
	 *
	 * @param float|WP_Error $spot_price_per_gram Spot gold price per gram.
	 * @param float          $grams Gold bar weight in grams.
	 * @return string
	 */
	protected function format_melt_value($spot_price_per_gram, $grams) {
		if (is_wp_error($spot_price_per_gram)) {
			return __('Unavailable', 'alloy-metal-price-api');
		}

		return '$' . number_format_i18n((float) $spot_price_per_gram * $grams * self::BAR_PURITY, 2);
	}
}
