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
	 * Supported metal labels.
	 *
	 * @var array<string, string>
	 */
	const METAL_LABELS = array(
		'gold'      => 'Gold',
		'silver'    => 'Silver',
		'platinum'  => 'Platinum',
		'palladium' => 'Palladium',
	);

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
				'title'     => __('Cash for Gold Calculator', 'alloy-metal-price-api'),
				'purity'    => '24K',
				'metal'     => 'gold',
				'right'     => 'default',
				'classring' => 'false',
			),
			$atts,
			self::TAG
		);

		$this->assets->enqueue_frontend_assets();
		$this->assets->enqueue_frontend_scripts();

		$title               = sanitize_text_field((string) $atts['title']);
		$metal               = $this->normalize_metal($atts['metal']);
		$metal_label         = self::METAL_LABELS[$metal];
		$purity_value        = $this->parse_purity_value($atts['purity'], $metal);
		$right_variant       = $this->normalize_right_variant($atts['right']);
		$classring           = $this->normalize_boolean_attribute($atts['classring']);
		$spot_price_per_gram = $this->api_client->get_cached_metal_price($metal);
		$spot_price_per_gram = null === $spot_price_per_gram ? 0 : $spot_price_per_gram;

		$layout_id        = wp_unique_id('alloy-calculator-layout-');
		$layout_skeleton = $layout_id . '-skeleton';

		$calculator_markup = $this->calculator_renderer->render(
			array(
				'title'               => $title,
				'metal'               => $metal,
				'purity_value'        => $purity_value,
				'classring'           => $classring,
				'base_price_per_gram' => $spot_price_per_gram,
				'show_skeleton'       => false,
				'wrapper_class'       => '',
				'section_class'       => 'aur:w-full aur:rounded-3xl aur:bg-white aur:p-8 aur:font-sans aur:shadow-[0_4px_10px_rgba(0,0,0,0.1)]',
				'heading_class'       => 'aur:mb-10 aur:mt-5 aur:text-center aur:text-2xl! aur:font-semibold aur:text-black',
			)
		);

		$shell_class = 'alloy-calculator-layout-shell alloy-calculator-layout-shell--reserve';
		if ($classring) {
			$shell_class .= ' alloy-calculator-layout-shell--classring';
		}

		ob_start();
?>
		<div class="<?php echo esc_attr($shell_class); ?>">
			<?php echo $this->render_layout_skeleton($layout_skeleton, $title, $classring); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div
				id="<?php echo esc_attr($layout_id); ?>"
				class="alloy-calculator-layout-content aur:flex aur:w-full aur:justify-center aur:font-sans"
				data-metal="<?php echo esc_attr($metal); ?>"
				data-alloy-layout-pending="true">
			<div class="alloy-calculator-layout-grid aur:flex aur:w-full aur:max-w-7xl aur:flex-col aur:gap-15 aur:md:grid aur:md:grid-cols-2 aur:md:items-start">
				<div class="alloy-calculator-layout-main aur:w-full aur:md:max-w-150">
					<?php echo $calculator_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
					?>
				</div>

				<div class="alloy-calculator-layout-side aur:w-full aur:md:max-w-95 aur:space-y-5 aur:justify-self-end">
					<?php
					if ('gold' === $metal && '14k' === $right_variant) {
						echo $this->render_14k_right_column($spot_price_per_gram); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						echo $this->render_default_right_column($spot_price_per_gram, $metal, $metal_label); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>
			</div>
			</div>
			<?php echo $this->render_layout_reveal_script($layout_id, $layout_skeleton); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	<?php

		return trim((string) ob_get_clean());
	}

	/**
	 * Render a two-column first-paint skeleton for the full calculator layout shortcode.
	 *
	 * @param string $skeleton_id Skeleton element ID.
	 * @param string $title       Calculator title.
	 * @param bool   $classring   Whether the class ring selector will render.
	 * @return string
	 */
	protected function render_layout_skeleton($skeleton_id, $title, $classring) {
		$rows = $classring ? 4 : 3;

		ob_start();
	?>
		<style>
			@keyframes alloyCalculatorLayoutSkeletonExit {
				to {
					opacity: 0;
					visibility: hidden;
				}
			}
			@keyframes alloyCalculatorLayoutPendingFallback {
				to {
					visibility: visible;
				}
			}
			.alloy-calculator-layout-shell {
				box-sizing: border-box;
				position: relative;
				width: 100%;
			}
			.alloy-calculator-layout-shell--reserve {
				min-height: 1291px;
			}
			.alloy-calculator-layout-shell--classring {
				min-height: 1405px;
			}
			.alloy-calculator-layout-skeleton {
				position: absolute;
				top: 0;
				right: 0;
				left: 0;
				z-index: 2;
				pointer-events: none;
				animation: alloyCalculatorLayoutSkeletonExit .01s linear 3s forwards;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] {
				visibility: hidden;
				animation: alloyCalculatorLayoutPendingFallback .01s linear 3s forwards;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] {
				display: flex;
				width: 100%;
				justify-content: center;
				font-family: "Lexend Deca", Arial, sans-serif;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-grid {
				display: grid;
				width: 100%;
				max-width: 1280px;
				gap: 60px;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-main {
				width: 100%;
				max-width: 600px;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .js-alloy-calculator {
				box-sizing: border-box;
				width: 100%;
				border-radius: 24px;
				background: #fff;
				padding: 32px;
				font-family: "Lexend Deca", Arial, sans-serif;
				box-shadow: 0 4px 10px rgba(0,0,0,.1);
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .js-alloy-calculator h2 {
				margin: 0 0 12px;
				font-size: 24px;
				line-height: 32px;
				font-weight: 600;
				text-align: center;
				color: #000;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .js-alloy-calculator form,
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .js-alloy-calculator .aur\:grid {
				display: grid;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .js-alloy-calculator form {
				gap: 16px;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .js-alloy-calculator .aur\:gap-6 {
				gap: 24px;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .js-alloy-calculator .aur\:relative {
				position: relative;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .js-alloy-calculator .aur\:mb-1 {
				margin-bottom: 4px;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .js-alloy-calculator .aur\:text-2xl {
				font-size: 24px;
				line-height: 32px;
				font-weight: 600;
				color: #1b98b9;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .js-alloy-calculator label {
				display: block;
				margin: 0 0 8px;
				font-size: 14px;
				font-weight: 500;
				color: #334155;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .js-alloy-calculator select,
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .js-alloy-calculator input {
				box-sizing: border-box;
				width: 100%;
				min-height: 48px;
				border: 1px solid #cbd5e1;
				border-radius: 6px;
				background: #fff;
				padding: 12px 16px;
				font-size: 16px;
				color: #0f172a;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .js-alloy-calculator-tooltip {
				display: none;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .js-alloy-calculator-tooltip-button {
				display: inline-flex;
				width: 20px;
				height: 20px;
				align-items: center;
				justify-content: center;
				border: 0;
				border-radius: 9999px;
				background: #1b98b9;
				padding: 0;
				color: #fff;
				font-size: 11px;
				line-height: 1;
				font-weight: 700;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side {
				display: grid;
				gap: 20px;
				width: 100%;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side > div {
				box-sizing: border-box;
				width: 100%;
				border-radius: 16px;
				background: #fff;
				padding: 20px;
				box-shadow: 0 4px 10px rgba(0,0,0,.1);
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side h3 {
				margin: 0 0 16px;
				font-size: 24px;
				line-height: 1.25;
				text-align: center;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side .aur\:space-y-3 > :not(:last-child) {
				margin-bottom: 12px;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side .aur\:space-y-1 > :not(:last-child) {
				margin-bottom: 4px;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side .aur\:flex {
				display: flex;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side .aur\:items-center {
				align-items: center;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side .aur\:justify-between {
				justify-content: space-between;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side .aur\:rounded-2xl {
				border-radius: 16px;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side .aur\:bg-slate-50 {
				background: #f8fafc;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side .aur\:px-4 {
				padding-right: 16px;
				padding-left: 16px;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side .aur\:py-3 {
				padding-top: 12px;
				padding-bottom: 12px;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side .aur\:py-4 {
				padding-top: 16px;
				padding-bottom: 16px;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side .aur\:text-lg {
				font-size: 18px;
				line-height: 28px;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side .aur\:text-sm {
				font-size: 14px;
				line-height: 20px;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side .aur\:font-bold {
				font-weight: 700;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side .aur\:font-normal {
				font-weight: 400;
			}
			.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side ul {
				margin-bottom: 0;
				list-style-type: disc;
				padding-left: 20px;
			}
			@media (min-width: 768px) {
				.alloy-calculator-layout-shell--reserve,
				.alloy-calculator-layout-shell--classring {
					min-height: 760px;
				}
				.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-grid,
				.alloy-calculator-layout-skeleton__grid {
					grid-template-columns: repeat(2, minmax(0, 1fr));
				}
				.alloy-calculator-layout-content[data-alloy-layout-pending="true"] .alloy-calculator-layout-side,
				.alloy-calculator-layout-skeleton__side {
					max-width: 380px;
					justify-self: end;
				}
			}
		</style>
		<div id="<?php echo esc_attr($skeleton_id); ?>" class="alloy-calculator-layout-skeleton" aria-hidden="true" style="box-sizing:border-box;width:100%;max-width:1280px;margin:0 auto;font-family:'Lexend Deca',Arial,sans-serif;">
			<div class="alloy-calculator-layout-skeleton__grid" style="display:grid;gap:60px;width:100%;">
				<div style="box-sizing:border-box;width:100%;padding:32px;border-radius:24px;background:#fff;box-shadow:0 4px 10px rgba(0,0,0,.1);">
					<div style="height:32px;width:68%;max-width:380px;margin:20px auto 40px;border-radius:8px;background:#e8edf1;color:transparent;overflow:hidden;"><?php echo esc_html($title); ?></div>
					<div style="display:grid;gap:24px;">
						<div style="display:grid;gap:8px;">
							<div style="width:52%;height:15px;border-radius:6px;background:#edf1f4;"></div>
							<div style="width:36%;height:32px;border-radius:8px;background:#dfe6eb;"></div>
						</div>
						<?php for ($index = 0; $index < $rows; $index++) : ?>
							<div style="display:grid;gap:8px;">
								<div style="width:28%;height:14px;border-radius:6px;background:#edf1f4;"></div>
								<div style="height:50px;border-radius:6px;border:1px solid #d5dbe1;background:#f8fafb;"></div>
							</div>
						<?php endfor; ?>
						<div style="height:50px;border-radius:6px;background:#737a82;"></div>
					</div>
				</div>
				<div class="alloy-calculator-layout-skeleton__side" style="display:grid;gap:20px;width:100%;">
					<div style="box-sizing:border-box;width:100%;padding:20px;border-radius:16px;background:#fff;box-shadow:0 4px 10px rgba(0,0,0,.1);">
						<div style="height:30px;width:70%;margin:0 auto 18px;border-radius:8px;background:#e8edf1;"></div>
						<div style="display:grid;gap:12px;">
							<div style="height:50px;border-radius:16px;background:#f1f4f6;"></div>
							<div style="height:50px;border-radius:16px;background:#f1f4f6;"></div>
							<div style="height:50px;border-radius:16px;background:#f1f4f6;"></div>
						</div>
					</div>
					<div style="box-sizing:border-box;width:100%;padding:20px;border-radius:16px;background:#fff;box-shadow:0 4px 10px rgba(0,0,0,.1);">
						<div style="height:30px;width:76%;margin:0 auto 18px;border-radius:8px;background:#e8edf1;"></div>
						<div style="height:160px;border-radius:16px;background:#f1f4f6;"></div>
					</div>
				</div>
			</div>
		</div>
	<?php

		return trim((string) ob_get_clean());
	}

	/**
	 * Render a reveal script for the layout-level skeleton.
	 *
	 * @param string $layout_id Layout content element ID.
	 * @param string $skeleton_id Skeleton element ID.
	 * @return string
	 */
	protected function render_layout_reveal_script($layout_id, $skeleton_id) {
		$layout_id_json   = wp_json_encode($layout_id);
		$skeleton_id_json = wp_json_encode($skeleton_id);

		return sprintf(
			'<script>(function(){var layout=document.getElementById(%1$s);var skeleton=document.getElementById(%2$s);if(!layout){return;}var revealed=false;var started=Date.now();function reveal(){if(revealed){return;}revealed=true;layout.removeAttribute("data-alloy-layout-pending");layout.style.visibility="";if(skeleton){skeleton.hidden=true;skeleton.style.display="none";}}function pluginStylesheet(){var links=document.querySelectorAll("link[rel~=\"stylesheet\"]");for(var i=0;i<links.length;i++){var link=links[i];var href=link.href||"";if(link.id==="alloy-metal-price-api-frontend-css"||href.indexOf("/assets/dist/css/plugin.css")!==-1||href.indexOf("AlloyMetalPriceAPI")!==-1||href.indexOf("alloy-metal-price-api")!==-1){return link;}}return null;}function ready(link){return !!(link&&link.sheet);}function wait(){var link=pluginStylesheet();if(ready(link)){reveal();return;}if(link){link.addEventListener("load",reveal,{once:true});link.addEventListener("error",reveal,{once:true});}if(Date.now()-started>=3000){reveal();return;}window.setTimeout(wait,50);}window.addEventListener("NitroStylesLoaded",reveal,{once:true});document.addEventListener("NitroStylesLoaded",reveal,{once:true});wait();})();</script>',
			$layout_id_json,
			$skeleton_id_json
		);
	}

	/**
	 * Parse a purity attribute into a karat integer or decimal purity value.
	 *
	 * @param mixed $purity Raw shortcode purity value.
	 * @param string $metal Normalized metal key.
	 * @return int|float
	 */
	protected function parse_purity_value($purity, $metal) {
		if ('gold' !== $metal) {
			$purity = is_numeric($purity) ? (float) $purity : 0.9999;

			return max(0, min(1, $purity));
		}

		$purity = strtoupper(sanitize_text_field((string) $purity));

		if (preg_match('/^([1-9]|1[0-9]|2[0-4])K?$/', $purity, $matches)) {
			return (int) $matches[1];
		}

		return 24;
	}

	/**
	 * Normalize the right-column variant attribute.
	 *
	 * @param mixed $right Raw right-column attribute value.
	 * @return string
	 */
	protected function normalize_right_variant($right) {
		$right = strtolower(sanitize_text_field((string) $right));

		if ('14k' === $right) {
			return '14k';
		}

		return 'default';
	}

	/**
	 * Normalize the metal attribute to a supported API metal type.
	 *
	 * @param mixed $metal Raw metal attribute value.
	 * @return string
	 */
	protected function normalize_metal($metal) {
		$metal = strtolower(sanitize_text_field((string) $metal));

		if (isset(self::METAL_LABELS[$metal])) {
			return $metal;
		}

		return 'gold';
	}

	/**
	 * Normalize a text boolean shortcode attribute.
	 *
	 * @param mixed $value Raw attribute value.
	 * @return bool
	 */
	protected function normalize_boolean_attribute($value) {
		return rest_sanitize_boolean($value);
	}

	/**
	 * Render the default right-column content.
	 *
	 * @param float  $spot_price_per_gram Live spot price per gram.
	 * @param string $metal Normalized metal key.
	 * @param string $metal_label Human-readable metal label.
	 * @return string
	 */
	protected function render_default_right_column($spot_price_per_gram, $metal, $metal_label) {
		$price_per_ounce = $spot_price_per_gram * self::TROY_OUNCE_IN_GRAMS;
		$price_per_kilo  = $spot_price_per_gram * 1000;

		ob_start();
	?>
		<div class="aur:w-full aur:rounded-2xl aur:bg-white aur:p-5 aur:text-center aur:shadow-[0_4px_10px_rgba(0,0,0,0.1)]">
			<h3 class="aur:mb-4 aur:text-2xl! aur:font-semibold aur:text-accent!">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: metal label like Gold or Platinum. */
						__('Current %s Prices', 'alloy-metal-price-api'),
						$metal_label
					)
				);
				?>
			</h3>

			<div class="aur:space-y-3">
				<div class="aur:flex aur:items-center aur:justify-between aur:rounded-2xl aur:bg-slate-50 aur:px-4 aur:py-3 aur:text-lg aur:font-bold aur:text-slate-800">
					<span class="aur:font-normal aur:text-slate-500"><?php esc_html_e('Per Gram:', 'alloy-metal-price-api'); ?></span>
					<span class="js-alloy-metal-price-per-gram aur:text-slate-800"><?php echo esc_html($this->format_currency($spot_price_per_gram)); ?></span>
				</div>
				<div class="aur:flex aur:items-center aur:justify-between aur:rounded-2xl aur:bg-slate-50 aur:px-4 aur:py-3 aur:text-lg aur:font-bold aur:text-slate-800">
					<span class="aur:font-normal aur:text-slate-500"><?php esc_html_e('Per Ounce:', 'alloy-metal-price-api'); ?></span>
					<span class="js-alloy-metal-price-per-ounce aur:text-slate-800"><?php echo esc_html($this->format_currency($price_per_ounce)); ?></span>
				</div>
				<div class="aur:flex aur:items-center aur:justify-between aur:rounded-2xl aur:bg-slate-50 aur:px-4 aur:py-3 aur:text-lg aur:font-bold aur:text-slate-800">
					<span class="aur:font-normal aur:text-slate-500"><?php esc_html_e('Per Kilo:', 'alloy-metal-price-api'); ?></span>
					<span class="js-alloy-metal-price-per-kilo aur:text-slate-800"><?php echo esc_html($this->format_currency($price_per_kilo)); ?></span>
				</div>
			</div>

			<div class="aur:mt-4 aur:text-sm aur:text-slate-500">
				<?php esc_html_e('Prices updated every minute via live market data.', 'alloy-metal-price-api'); ?>
			</div>
		</div>

		<?php echo $this->render_bottom_right_box($metal); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
		?>
	<?php

		return trim((string) ob_get_clean());
	}

	/**
	 * Render the bottom-right content box for the selected metal.
	 *
	 * @param string $metal Normalized metal key.
	 * @return string
	 */
	protected function render_bottom_right_box($metal) {
		if ('platinum' === $metal) {
			return $this->render_platinum_markings_box();
		}

		if ('silver' === $metal || 'palladium' === $metal) {
			return $this->render_placeholder_markings_box($metal);
		}

		return $this->render_gold_markings_box();
	}

	/**
	 * Render the default gold markings box.
	 *
	 * @return string
	 */
	protected function render_gold_markings_box() {
		ob_start();
	?>
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
	<?php

		return trim((string) ob_get_clean());
	}

	/**
	 * Render the platinum markings box.
	 *
	 * @return string
	 */
	protected function render_platinum_markings_box() {
		ob_start();
	?>
		<div class="aur:w-full aur:rounded-2xl aur:bg-white aur:p-5 aur:text-center aur:shadow-[0_4px_10px_rgba(0,0,0,0.1)]">
			<h3 class="aur:mb-4 aur:text-2xl! aur:font-semibold aur:text-black!"><?php esc_html_e('Platinum Fineness Markings', 'alloy-metal-price-api'); ?></h3>

			<div class="aur:rounded-2xl aur:bg-slate-50 aur:px-4 aur:py-5 aur:text-left aur:text-sm aur:text-slate-600 aur:shadow-[0_2px_6px_rgba(0,0,0,0.1)]">
				<ul class="aur:list-disc aur:space-y-2 aur:pl-5">
					<li><strong><?php esc_html_e('999.5', 'alloy-metal-price-api'); ?></strong>: <?php esc_html_e('99.95% pure platinum (bullion standard)', 'alloy-metal-price-api'); ?></li>
					<li><strong><?php esc_html_e('999', 'alloy-metal-price-api'); ?></strong>: <?php esc_html_e('99.9% pure platinum', 'alloy-metal-price-api'); ?></li>
					<li><strong><?php esc_html_e('950', 'alloy-metal-price-api'); ?></strong>: <?php esc_html_e('95% pure platinum (most fine jewelry)', 'alloy-metal-price-api'); ?></li>
					<li><strong><?php esc_html_e('900', 'alloy-metal-price-api'); ?></strong>: <?php esc_html_e('90% pure platinum', 'alloy-metal-price-api'); ?></li>
					<li><strong><?php esc_html_e('850', 'alloy-metal-price-api'); ?></strong>: <?php esc_html_e('85% pure platinum', 'alloy-metal-price-api'); ?></li>
				</ul>
			</div>
		</div>
	<?php

		return trim((string) ob_get_clean());
	}

	/**
	 * Render a placeholder bottom-right box for metals whose content is pending.
	 *
	 * @param string $metal Normalized metal key.
	 * @return string
	 */
	protected function render_placeholder_markings_box($metal) {
		$metal_label = self::METAL_LABELS[$metal];

		ob_start();
	?>
		<div class="aur:w-full aur:rounded-2xl aur:bg-white aur:p-5 aur:text-center aur:shadow-[0_4px_10px_rgba(0,0,0,0.1)]">
			<h3 class="aur:mb-4 aur:text-2xl! aur:font-semibold aur:text-accent!">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: metal label like Silver or Palladium. */
						__('%s Fineness Markings', 'alloy-metal-price-api'),
						$metal_label
					)
				);
				?>
			</h3>

			<div class="aur:rounded-2xl aur:bg-slate-50 aur:px-4 aur:py-5 aur:text-left aur:text-sm aur:text-slate-600 aur:shadow-[0_2px_6px_rgba(0,0,0,0.1)]">
				<p class="aur:m-0">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: metal label like Silver or Palladium. */
							__('%s-specific fineness content will be added here soon.', 'alloy-metal-price-api'),
							$metal_label
						)
					);
					?>
				</p>
			</div>
		</div>
	<?php

		return trim((string) ob_get_clean());
	}

	/**
	 * Render the 14K-specific right-column content.
	 *
	 * @param float $spot_price_per_gram Live 24K gold spot price per gram.
	 * @return string
	 */
	protected function render_14k_right_column($spot_price_per_gram) {
		$purity_multiplier = 14 / 24;
		$price_per_gram    = $spot_price_per_gram * $purity_multiplier;
		$price_per_ounce   = $price_per_gram * self::TROY_OUNCE_IN_GRAMS;
		$price_per_kilo    = $price_per_gram * 1000;

		ob_start();
	?>
		<div class="aur:w-full aur:rounded-2xl aur:bg-white aur:p-5 aur:text-center aur:shadow-[0_4px_10px_rgba(0,0,0,0.1)]">
			<h2 class="aur:m-0 aur:text-2xl! aur:font-bold aur:text-black"><?php esc_html_e('How Much Is 14K Gold Currently Worth?', 'alloy-metal-price-api'); ?></h2>
			<h3 class="aur:mb-4 aur:mt-2 aur:text-base! aur:font-medium aur:text-slate-500"><?php esc_html_e('Current 14K Gold Prices', 'alloy-metal-price-api'); ?></h3>

			<div class="aur:space-y-3">
				<div class="aur:flex aur:items-center aur:justify-between aur:rounded-2xl aur:bg-slate-50 aur:px-4 aur:py-3 aur:text-lg aur:font-bold aur:text-slate-800">
					<span class="aur:font-normal aur:text-slate-500"><?php esc_html_e('Per Gram:', 'alloy-metal-price-api'); ?></span>
					<span class="js-alloy-metal-price-per-gram aur:text-slate-800" data-purity-multiplier="<?php echo esc_attr((string) $purity_multiplier); ?>"><?php echo esc_html($this->format_currency($price_per_gram)); ?></span>
				</div>
				<div class="aur:flex aur:items-center aur:justify-between aur:rounded-2xl aur:bg-slate-50 aur:px-4 aur:py-3 aur:text-lg aur:font-bold aur:text-slate-800">
					<span class="aur:font-normal aur:text-slate-500"><?php esc_html_e('Per Ounce:', 'alloy-metal-price-api'); ?></span>
					<span class="js-alloy-metal-price-per-ounce aur:text-slate-800" data-purity-multiplier="<?php echo esc_attr((string) $purity_multiplier); ?>"><?php echo esc_html($this->format_currency($price_per_ounce)); ?></span>
				</div>
				<div class="aur:flex aur:items-center aur:justify-between aur:rounded-2xl aur:bg-slate-50 aur:px-4 aur:py-3 aur:text-lg aur:font-bold aur:text-slate-800">
					<span class="aur:font-normal aur:text-slate-500"><?php esc_html_e('Per Kilo:', 'alloy-metal-price-api'); ?></span>
					<span class="js-alloy-metal-price-per-kilo aur:text-slate-800" data-purity-multiplier="<?php echo esc_attr((string) $purity_multiplier); ?>"><?php echo esc_html($this->format_currency($price_per_kilo)); ?></span>
				</div>
			</div>

			<div class="aur:mt-4 aur:text-sm aur:leading-6 aur:text-slate-500">
				<?php esc_html_e('14K prices reflect 58.3% of the live gold spot rate.', 'alloy-metal-price-api'); ?><br>
				<?php esc_html_e('Updated every minute via ', 'alloy-metal-price-api'); ?>
				<a href="https://metalpriceapi.com/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Metal Price API', 'alloy-metal-price-api'); ?></a>.
			</div>
		</div>

		<div class="aur:w-full aur:rounded-2xl aur:bg-white aur:p-5 aur:text-center aur:shadow-[0_4px_10px_rgba(0,0,0,0.1)]">
			<h3 class="aur:mb-4 aur:text-2xl! aur:font-semibold aur:text-accent!"><?php esc_html_e('14K Gold Markings', 'alloy-metal-price-api'); ?></h3>

			<div class="aur:rounded-2xl aur:bg-slate-50 aur:px-4 aur:py-4 aur:text-left aur:text-sm aur:leading-7 aur:text-slate-600 aur:shadow-[0_2px_6px_rgba(0,0,0,0.1)]">
				<ul class="aur:list-disc aur:space-y-1 aur:pl-5">
					<li><strong><?php esc_html_e('585', 'alloy-metal-price-api'); ?></strong> <?php esc_html_e('— European standard marking', 'alloy-metal-price-api'); ?></li>
					<li><strong><?php esc_html_e('14K', 'alloy-metal-price-api'); ?></strong> <?php esc_html_e('— Common U.S. marking', 'alloy-metal-price-api'); ?></li>
					<li><strong><?php esc_html_e('14KT', 'alloy-metal-price-api'); ?></strong> <?php esc_html_e('— Alternate seen on fine jewelry', 'alloy-metal-price-api'); ?></li>
					<li><strong><?php esc_html_e('14C', 'alloy-metal-price-api'); ?></strong> <?php esc_html_e('— Older or imported pieces', 'alloy-metal-price-api'); ?></li>
				</ul>
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
}
