<?php

/**
 * [metal_goldbar_live_melt_table] shortcode handler.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Metal_Goldbar_Live_Melt_Table_Shortcode {
	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'metal_goldbar_live_melt_table';

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

		$spot_price_per_gram = $this->api_client->get_cached_metal_price('gold');
		$spot_price_per_ounce = null === $spot_price_per_gram ? null : $spot_price_per_gram * self::TROY_OUNCE_IN_GRAMS;

		return $this->render_layout(
			$this->build_bar_rows($spot_price_per_gram)
		) . $this->render_hidden_spot_source($spot_price_per_ounce);
	}

	/**
	 * Render the responsive gold bar layout.
	 *
	 * @param array<int, array<string, string>> $rows Table and card rows.
	 * @return string
	 */
	protected function render_layout(array $rows) {
		ob_start();
		?>
		<div id="alloy-goldbar-live-melt-table" class="aur:w-full aur:font-sans">
			<div class="aur:hidden aur:md:block">
				<div class="aur:w-full aur:overflow-x-auto aur:rounded-2xl aur:border aur:border-slate-900 aur:bg-white aur:shadow-[0_4px_16px_rgba(0,0,0,0.06)]">
					<table class="aur:w-full aur:border-collapse aur:text-left">
						<thead>
							<tr class="aur:bg-slate-50">
								<th class="aur:px-4 aur:py-4 aur:text-sm aur:font-semibold aur:text-slate-900"><?php esc_html_e('Gold bar size', 'alloy-metal-price-api'); ?></th>
								<th class="aur:px-4 aur:py-4 aur:text-sm aur:font-semibold aur:text-slate-900"><?php esc_html_e('Weight', 'alloy-metal-price-api'); ?></th>
								<th class="aur:px-4 aur:py-4 aur:text-sm aur:font-semibold aur:text-slate-900"><?php esc_html_e('Purity', 'alloy-metal-price-api'); ?></th>
								<th class="aur:px-4 aur:py-4 aur:text-sm aur:font-semibold aur:text-slate-900"><?php esc_html_e('Live melt value', 'alloy-metal-price-api'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($rows as $index => $row) : ?>
									<tr class="<?php echo esc_attr(0 === $index % 2 ? 'aur:bg-table-row-alt' : ''); ?>">
										<td class="aur:px-4 aur:py-4 aur:text-base aur:text-slate-900"><strong><?php echo esc_html($row['size']); ?></strong></td>
										<td class="aur:px-4 aur:py-4 aur:text-base aur:text-slate-900"><?php echo esc_html($row['weight_label']); ?></td>
										<td class="aur:px-4 aur:py-4 aur:text-base aur:text-slate-900"><?php echo esc_html($row['purity']); ?></td>
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
	 * Build the static gold bar row data with live melt values.
	 *
	 * @param float|WP_Error $spot_price_per_gram Spot gold price per gram.
	 * @return array<int, array<string, string>>
	 */
	protected function build_bar_rows($spot_price_per_gram) {
		$rows = array(
			array(
				'size'               => '1 g',
				'grams'              => 1,
				'grams_display'      => '1',
				'troy_ounces_display' => '0.0322',
			),
			array(
				'size'               => '2.5 g',
				'grams'              => 2.5,
				'grams_display'      => '2.5',
				'troy_ounces_display' => '0.0804',
			),
			array(
				'size'               => '5 g',
				'grams'              => 5,
				'grams_display'      => '5',
				'troy_ounces_display' => '0.1608',
			),
			array(
				'size'               => '10 g',
				'grams'              => 10,
				'grams_display'      => '10',
				'troy_ounces_display' => '0.3215',
			),
			array(
				'size'               => '20 g',
				'grams'              => 20,
				'grams_display'      => '20',
				'troy_ounces_display' => '0.6430',
			),
			array(
				'size'               => '1 oz',
				'grams'              => 31.1035,
				'grams_display'      => '31.1035',
				'troy_ounces_display' => '1.0000',
			),
			array(
				'size'               => '50 g',
				'grams'              => 50,
				'grams_display'      => '50',
				'troy_ounces_display' => '1.6075',
			),
			array(
				'size'               => '100 g',
				'grams'              => 100,
				'grams_display'      => '100',
				'troy_ounces_display' => '3.2151',
			),
			array(
				'size'               => '5 oz',
				'grams'              => 155.5175,
				'grams_display'      => '155.5175',
				'troy_ounces_display' => '5.0000',
			),
			array(
				'size'               => '10 oz',
				'grams'              => 311.035,
				'grams_display'      => '311.035',
				'troy_ounces_display' => '10.0000',
			),
			array(
				'size'               => '250 g',
				'grams'              => 250,
				'grams_display'      => '250',
				'troy_ounces_display' => '8.0377',
			),
			array(
				'size'               => '500 g',
				'grams'              => 500,
				'grams_display'      => '500',
				'troy_ounces_display' => '16.0754',
			),
			array(
				'size'               => '1 kg',
				'grams'              => 1000,
				'grams_display'      => '1,000',
				'troy_ounces_display' => '32.1507',
			),
			array(
				'size'               => '400 oz',
				'grams'              => 12441.4,
				'grams_display'      => '12,441.4',
				'troy_ounces_display' => '400.0000',
			),
		);

		foreach ($rows as &$row) {
			$row['purity']       = '.9999';
			$row['weight_label'] = sprintf(
				/* translators: 1: grams label, 2: troy ounces label. */
				__('%1$s g (%2$s oz)', 'alloy-metal-price-api'),
				$row['grams_display'],
				$row['troy_ounces_display']
			);
			$row['melt_display'] = $this->format_melt_value($spot_price_per_gram, (float) $row['grams']);
			$row['melt_factor']  = (float) $row['grams'] * self::BAR_PURITY;
		}
		unset($row);

		return $rows;
	}

	/**
	 * Format the melt value for a gold bar row.
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
	 * Render the hidden spot price source markup.
	 *
	 * @param float|null $spot_price_per_ounce Current spot price per ounce.
	 * @return string
	 */
	protected function render_hidden_spot_source($spot_price_per_ounce) {
		ob_start();
		?>
			<div class="alloy-spot-source aur:hidden">
				<span class="aur:text-base aur:font-sans" data-metal-symbol="xau" data-metal-unit="ounce">
					<span class="js-alloy-live-price" data-metal="gold" data-price-factor="<?php echo esc_attr((string) self::TROY_OUNCE_IN_GRAMS); ?>" data-price-format="number" data-decimals="2"><?php echo esc_html(null === $spot_price_per_ounce ? __('Unavailable', 'alloy-metal-price-api') : number_format((float) $spot_price_per_ounce, 2, '.', '')); ?></span>
				</span>
			</div>
		<?php

		return trim((string) ob_get_clean());
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
