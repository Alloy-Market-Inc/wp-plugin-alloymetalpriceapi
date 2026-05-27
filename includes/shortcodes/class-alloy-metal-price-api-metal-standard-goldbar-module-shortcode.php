<?php

/**
 * [metal_standard_goldbar_module] shortcode handler.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Metal_Standard_Goldbar_Module_Shortcode {
	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'metal_standard_goldbar_module';

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
		$this->assets->enqueue_frontend_scripts();

		$spot_price_per_gram  = $this->api_client->get_cached_metal_price('gold');
		$spot_price_per_ounce = null === $spot_price_per_gram ? null : $spot_price_per_gram * self::TROY_OUNCE_IN_GRAMS;

		return $this->render_module($this->build_rows($spot_price_per_gram), $spot_price_per_ounce);
	}

	/**
	 * Render the shortcode module.
	 *
	 * @param array<int, array<string, string>> $rows Standard gold bar rows.
	 * @param float|null                        $spot_price_per_ounce Current spot price per troy ounce.
	 * @return string
	 */
	protected function render_module(array $rows, $spot_price_per_ounce) {
		ob_start();
		?>
		<div id="alloy-standard-goldbar-module" class="aur:w-full aur:font-sans">
			<div class="aur:mb-5 aur:flex aur:flex-col aur:items-center aur:gap-2 aur:text-center">
				<div class="aur:text-[22px] aur:font-semibold aur:text-primary">
					<?php esc_html_e('Standard Gold Bars (Live Melt Value)', 'alloy-metal-price-api'); ?>
				</div>
				<div class="aur:text-sm aur:text-slate-600">
					<?php esc_html_e('Spot price (USD / troy oz):', 'alloy-metal-price-api'); ?>
					<span class="aur:font-semibold aur:text-slate-900">
						$
						<span class="alloy-spot-price js-alloy-live-price" data-metal="gold" data-price-factor="<?php echo esc_attr((string) self::TROY_OUNCE_IN_GRAMS); ?>" data-price-format="number" data-decimals="2">
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
										<td class="aur:px-4 aur:py-4 aur:text-base aur:font-semibold aur:text-primary"><strong><?php echo $this->render_live_melt_value($row); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></td>
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
								<div class="aur:text-lg aur:font-semibold aur:text-primary"><?php echo $this->render_live_melt_value($row); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
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

		$content = trim((string) ob_get_clean());

		return Alloy_Metal_Price_API_Shortcode_Shell::render(
			$content,
			Alloy_Metal_Price_API_Shortcode_Shell::table_skeleton(count($rows)),
			self::TAG
		);
	}

	/**
	 * Build the static standard gold bar rows.
	 *
	 * @param float|WP_Error $spot_price_per_gram Spot gold price per gram.
	 * @return array<int, array<string, string>>
	 */
	protected function build_rows($spot_price_per_gram) {
		$rows = array(
			array(
				'size'                => '50 g',
				'grams'               => 50,
				'grams_display'       => '50',
				'troy_ounces_display' => '1.6075',
			),
			array(
				'size'                => '100 g',
				'grams'               => 100,
				'grams_display'       => '100',
				'troy_ounces_display' => '3.2151',
			),
			array(
				'size'                => '5 oz',
				'grams'               => 155.5175,
				'grams_display'       => '155.5175',
				'troy_ounces_display' => '5.0000',
			),
		);

		foreach ($rows as &$row) {
			$row['purity']       = '.9999';
			$row['melt_display'] = $this->format_melt_value($spot_price_per_gram, (float) $row['grams']);
			$row['melt_factor']  = (float) $row['grams'] * self::BAR_PURITY;
		}
		unset($row);

		return $rows;
	}

	/**
	 * Format the melt value for a standard gold bar row.
	 *
	 * @param float|null $spot_price_per_gram Spot gold price per gram.
	 * @param float          $grams Gold bar weight in grams.
	 * @return string
	 */
	protected function format_melt_value($spot_price_per_gram, $grams) {
		if (null === $spot_price_per_gram) {
			return __('Unavailable', 'alloy-metal-price-api');
		}

		return '$' . number_format_i18n((float) $spot_price_per_gram * $grams * self::BAR_PURITY, 2);
	}

	/**
	 * Render a melt value that can be hydrated with a fresh spot price.
	 *
	 * @param array<string, string|float> $row Row data.
	 * @return string
	 */
	protected function render_live_melt_value($row) {
		return sprintf(
			'<span class="js-alloy-live-price" data-metal="gold" data-price-factor="%1$s">%2$s</span>',
			esc_attr((string) $row['melt_factor']),
			esc_html($row['melt_display'])
		);
	}
}
