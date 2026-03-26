<?php

/**
 * [metal_price_table] shortcode handler.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_14K_Gold_Price_Table_Shortcode {
	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'metal_price_table';

	/**
	 * Grams in a troy ounce.
	 *
	 * @var float
	 */
	const TROY_OUNCE_IN_GRAMS = 31.1035;

	/**
	 * Grams in an avoirdupois ounce.
	 *
	 * @var float
	 */
	const OUNCE_IN_GRAMS = 28.3495;

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
		$default_title = __('Gold Price Table', 'alloy-metal-price-api');

		$atts = shortcode_atts(
			array(
				'title'         => $default_title,
				'purity'        => '24K',
				'data'          => 'default',
				'show_live_box' => 'false',
			),
			$atts,
			self::TAG
		);

		$this->assets->enqueue_frontend_assets();

		$title               = sanitize_text_field((string) $atts['title']);
		$purity_karat        = $this->parse_purity_karat($atts['purity']);
		$purity_label        = $this->format_purity_label($purity_karat);
		$purity_multiplier   = $purity_karat / 24;
		$data_variant        = $this->normalize_data_variant($atts['data']);
		$show_live_box       = $this->parse_boolean_att($atts['show_live_box']);
		$spot_price_per_gram = $this->api_client->get_metal_price('gold');

		if (is_wp_error($spot_price_per_gram)) {
			return $this->render_unavailable_table($title, $purity_label, $show_live_box, $data_variant, $default_title);
		}

		$price_per_gram       = $spot_price_per_gram * $purity_multiplier;
		$price_per_ounce      = $price_per_gram * self::OUNCE_IN_GRAMS;
		$price_per_troy_ounce = $price_per_gram * self::TROY_OUNCE_IN_GRAMS;
		$price_per_kilo       = $price_per_gram * 1000;
		$table_config         = $this->build_table_config(
			$title,
			$default_title,
			$data_variant,
			$purity_label,
			$price_per_gram,
			$price_per_ounce,
			$price_per_troy_ounce,
			$price_per_kilo,
			$spot_price_per_gram
		);

		return $this->render_table(
			$table_config['title'],
			$table_config['rows'],
			$table_config['footer_left'],
			$this->get_updated_label(),
			$show_live_box ? array(
				'pill'        => sprintf(
					/* translators: %s: purity label like 14K. */
					__('LIVE %s GOLD PRICE (PER GRAM)', 'alloy-metal-price-api'),
					'default' === $data_variant ? $purity_label : '24K'
				),
				'price'       => $this->format_currency('default' === $data_variant ? $price_per_gram : $spot_price_per_gram),
				'subtext'     => sprintf(
					/* translators: %s: 24K spot price per gram. */
					__('Based on 24K spot price: %s/g', 'alloy-metal-price-api'),
					$this->format_currency($spot_price_per_gram)
				),
				'updated'     => $this->get_updated_label(),
				'price_class' => 'aur:text-primary',
			) : null
		);
	}

	/**
	 * Render the unavailable state table.
	 *
	 * @param string $title Table title.
	 * @param string $purity_label Purity label like 14K.
	 * @param bool   $show_live_box Whether to render the optional summary box.
	 * @param string $data_variant Table data variant.
	 * @param string $default_title Default shortcode title.
	 * @return string
	 */
	protected function render_unavailable_table($title, $purity_label, $show_live_box = false, $data_variant = 'default', $default_title = '') {
		$table_config = $this->build_unavailable_table_config($title, $default_title, $data_variant, $purity_label);

		return $this->render_table(
			$table_config['title'],
			$table_config['rows'],
			$table_config['footer_left'],
			__('Updating…', 'alloy-metal-price-api'),
			$show_live_box ? array(
				'pill'        => sprintf(
					/* translators: %s: purity label like 14K. */
					__('LIVE %s GOLD PRICE (PER GRAM)', 'alloy-metal-price-api'),
					'default' === $data_variant ? $purity_label : '24K'
				),
				'price'       => __('Unavailable', 'alloy-metal-price-api'),
				'subtext'     => __('Based on 24K spot price: Unavailable', 'alloy-metal-price-api'),
				'updated'     => __('Updating…', 'alloy-metal-price-api'),
				'price_class' => 'aur:text-slate-500',
			) : null
		);
	}

	/**
	 * Render the table wrapper and rows.
	 *
	 * @param string                            $title Table heading text.
	 * @param array<int, array<string, string>> $rows Table rows.
	 * @param string                            $footer_left Left footer text.
	 * @param string                            $footer_right Right footer text.
	 * @param array<string, string>|null        $live_box Optional live summary box content.
	 * @return string
	 */
	protected function render_table($title, $rows, $footer_left, $footer_right, $live_box = null) {
		ob_start();
?>
		<div class="aur:flex aur:w-full aur:justify-center aur:font-sans">
			<div class="aur:flex aur:w-full aur:flex-col aur:items-center aur:gap-8 aur:lg:flex-row aur:lg:items-stretch aur:lg:justify-center">
				<?php if (is_array($live_box)) : ?>
					<aside class="aur:flex aur:w-full aur:flex-col aur:items-center aur:justify-center aur:gap-5 aur:rounded-2xl aur:border aur:border-slate-900 aur:bg-white aur:p-5 aur:text-center aur:shadow-[0_4px_16px_rgba(0,0,0,0.06)] aur:lg:max-w-80">
						<div class="aur:inline-flex aur:rounded-full aur:bg-secondary aur:px-4 aur:py-2 aur:text-xs aur:font-semibold aur:tracking-wide aur:text-white">
							<?php echo esc_html($live_box['pill']); ?>
						</div>
						<div class="aur:text-5xl aur:font-semibold <?php echo esc_attr($live_box['price_class']); ?>">
							<?php echo esc_html($live_box['price']); ?>
						</div>
						<div class="aur:text-sm aur:text-slate-600">
							<?php echo esc_html($live_box['subtext']); ?>
						</div>
						<div class="aur:flex aur:items-center aur:justify-center aur:gap-2 aur:text-sm aur:text-slate-500">
							<span class="aur:inline-block aur:h-2 aur:w-2 aur:rounded-full aur:bg-secondary" aria-hidden="true"></span>
							<span><?php echo esc_html($live_box['updated']); ?></span>
						</div>
					</aside>
				<?php endif; ?>

				<section
					id="k14pgtbl"
					class="aur:w-full aur:max-w-180 aur:overflow-hidden aur:rounded-2xl aur:border aur:border-slate-900 aur:bg-white aur:px-4 aur:py-4 aur:shadow-[0_4px_16px_rgba(0,0,0,0.06)] aur:sm:px-6"
					aria-labelledby="k14pgtbl-title">
					<h2 id="k14pgtbl-title" class="aur:mb-3 aur:text-center aur:text-base! aur:font-semibold! aur:text-primary!">
						<?php echo esc_html($title); ?>
					</h2>

					<table class="aur:w-full aur:border-collapse aur:text-left aur:font-sans">
						<tbody>
							<?php foreach ($rows as $index => $row) : ?>
								<?php
								$row_classes = '';

								if (0 === $index % 2) {
									$row_classes .= ' aur:bg-table-row-alt';
								}
								?>
								<tr class="<?php echo esc_attr($row_classes); ?>">
									<td class="aur:px-4 aur:py-3 aur:text-base aur:font-normal aur:text-slate-900">
										<?php echo esc_html($row['label']); ?>
									</td>
									<td class="aur:px-4 aur:py-3 aur:text-right aur:text-base aur:font-semibold aur:text-primary">
										<?php echo esc_html($row['value']); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<div class="aur:mt-4 aur:flex aur:flex-wrap aur:items-center aur:justify-between aur:gap-3 aur:text-[11.5px] aur:leading-none">
						<p class="aur:m-0 aur:text-slate-900"><?php echo esc_html($footer_left); ?></p>
						<p class="aur:m-0 aur:flex aur:items-center aur:gap-2 aur:text-slate-500">
							<span class="aur:inline-block aur:h-2 aur:w-2 aur:rounded-full aur:bg-secondary" aria-hidden="true"></span>
							<span><?php echo esc_html($footer_right); ?></span>
						</p>
					</div>
				</section>
			</div>
		</div>
<?php

		return trim((string) ob_get_clean());
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

	/**
	 * Normalize the requested table data variant.
	 *
	 * @param mixed $data Raw shortcode data attribute.
	 * @return string
	 */
	protected function normalize_data_variant($data) {
		$data = strtolower(sanitize_text_field((string) $data));
		$data = str_replace(array('-', '_'), ' ', $data);

		if (in_array($data, array('gold bars', 'gold bar', 'bars', 'bar'), true)) {
			return 'gold_bars';
		}

		return 'default';
	}

	/**
	 * Build a table configuration for the current data variant.
	 *
	 * @param string $title Current shortcode title value.
	 * @param string $default_title Default shortcode title value.
	 * @param string $data_variant Normalized data variant.
	 * @param string $purity_label Current purity label.
	 * @param float  $price_per_gram Purity-adjusted price per gram.
	 * @param float  $price_per_ounce Purity-adjusted price per ounce.
	 * @param float  $price_per_troy_ounce Purity-adjusted price per troy ounce.
	 * @param float  $price_per_kilo Purity-adjusted price per kilo.
	 * @param float  $spot_price_per_gram Current 24K spot price per gram.
	 * @return array<string, mixed>
	 */
	protected function build_table_config($title, $default_title, $data_variant, $purity_label, $price_per_gram, $price_per_ounce, $price_per_troy_ounce, $price_per_kilo, $spot_price_per_gram) {
		if ('gold_bars' === $data_variant) {
			return array(
				'title'       => $default_title === $title ? __('24K Gold Bar Spot Prices', 'alloy-metal-price-api') : $title,
				'rows'        => array(
					array(
						'label' => __('1 oz Gold Bar', 'alloy-metal-price-api'),
						'value' => $this->format_currency($spot_price_per_gram * self::TROY_OUNCE_IN_GRAMS),
					),
					array(
						'label' => __('5 oz Gold Bar', 'alloy-metal-price-api'),
						'value' => $this->format_currency($spot_price_per_gram * self::TROY_OUNCE_IN_GRAMS * 5),
					),
					array(
						'label' => __('10 oz Gold Bar', 'alloy-metal-price-api'),
						'value' => $this->format_currency($spot_price_per_gram * self::TROY_OUNCE_IN_GRAMS * 10),
					),
					array(
						'label' => __('100 g Gold Bar', 'alloy-metal-price-api'),
						'value' => $this->format_currency($spot_price_per_gram * 100),
					),
					array(
						'label' => __('1 kg Gold Bar', 'alloy-metal-price-api'),
						'value' => $this->format_currency($spot_price_per_gram * 1000),
					),
				),
				'footer_left' => sprintf(
					/* translators: %s: 24K gold spot price per gram. */
					__('24K spot price %s per gram', 'alloy-metal-price-api'),
					$this->format_currency($spot_price_per_gram)
				),
			);
		}

		return array(
			'title'       => $title,
			'rows'        => array(
				array(
					'label' => sprintf(
						/* translators: %s: purity label like 14K. */
						__('%s Gold Price Per Gram', 'alloy-metal-price-api'),
						$purity_label
					),
					'value' => $this->format_currency($price_per_gram),
				),
				array(
					'label' => sprintf(
						/* translators: %s: purity label like 14K. */
						__('%s Gold Price Per Ounce', 'alloy-metal-price-api'),
						$purity_label
					),
					'value' => $this->format_currency($price_per_ounce),
				),
				array(
					'label' => sprintf(
						/* translators: %s: purity label like 14K. */
						__('%s Gold Price Per Troy Ounce', 'alloy-metal-price-api'),
						$purity_label
					),
					'value' => $this->format_currency($price_per_troy_ounce),
				),
				array(
					'label' => sprintf(
						/* translators: %s: purity label like 14K. */
						__('%s Gold Price Per Kilo', 'alloy-metal-price-api'),
						$purity_label
					),
					'value' => $this->format_currency($price_per_kilo),
				),
			),
			'footer_left' => sprintf(
				/* translators: %s: 24K gold spot price per gram. */
				__('24K spot price %s per gram', 'alloy-metal-price-api'),
				$this->format_currency($spot_price_per_gram)
			),
		);
	}

	/**
	 * Build an unavailable-state table configuration for the selected data variant.
	 *
	 * @param string $title Current shortcode title value.
	 * @param string $default_title Default shortcode title value.
	 * @param string $data_variant Normalized data variant.
	 * @param string $purity_label Current purity label.
	 * @return array<string, mixed>
	 */
	protected function build_unavailable_table_config($title, $default_title, $data_variant, $purity_label) {
		if ('gold_bars' === $data_variant) {
			return array(
				'title'       => $default_title === $title ? __('24K Gold Bar Spot Prices', 'alloy-metal-price-api') : $title,
				'rows'        => array(
					array(
						'label' => __('1 oz Gold Bar', 'alloy-metal-price-api'),
						'value' => __('Unavailable', 'alloy-metal-price-api'),
					),
					array(
						'label' => __('5 oz Gold Bar', 'alloy-metal-price-api'),
						'value' => __('Unavailable', 'alloy-metal-price-api'),
					),
					array(
						'label' => __('10 oz Gold Bar', 'alloy-metal-price-api'),
						'value' => __('Unavailable', 'alloy-metal-price-api'),
					),
					array(
						'label' => __('100 g Gold Bar', 'alloy-metal-price-api'),
						'value' => __('Unavailable', 'alloy-metal-price-api'),
					),
					array(
						'label' => __('1 kg Gold Bar', 'alloy-metal-price-api'),
						'value' => __('Unavailable', 'alloy-metal-price-api'),
					),
				),
				'footer_left' => __('24K spot price unavailable', 'alloy-metal-price-api'),
			);
		}

		return array(
			'title'       => $title,
			'rows'        => array(
				array(
					'label' => sprintf(
						/* translators: %s: purity label like 14K. */
						__('%s Gold Price Per Gram', 'alloy-metal-price-api'),
						$purity_label
					),
					'value' => __('Unavailable', 'alloy-metal-price-api'),
				),
				array(
					'label' => sprintf(
						/* translators: %s: purity label like 14K. */
						__('%s Gold Price Per Ounce', 'alloy-metal-price-api'),
						$purity_label
					),
					'value' => __('Unavailable', 'alloy-metal-price-api'),
				),
				array(
					'label' => sprintf(
						/* translators: %s: purity label like 14K. */
						__('%s Gold Price Per Troy Ounce', 'alloy-metal-price-api'),
						$purity_label
					),
					'value' => __('Unavailable', 'alloy-metal-price-api'),
				),
				array(
					'label' => sprintf(
						/* translators: %s: purity label like 14K. */
						__('%s Gold Price Per Kilo', 'alloy-metal-price-api'),
						$purity_label
					),
					'value' => __('Unavailable', 'alloy-metal-price-api'),
				),
			),
			'footer_left' => __('24K spot price unavailable', 'alloy-metal-price-api'),
		);
	}

	/**
	 * Parse a shortcode boolean attribute.
	 *
	 * @param mixed $value Raw shortcode attribute value.
	 * @return bool
	 */
	protected function parse_boolean_att($value) {
		$value = strtolower(sanitize_text_field((string) $value));

		return in_array($value, array('1', 'true', 'yes', 'on'), true);
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
