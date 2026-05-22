<?php

/**
 * Shared calculator renderer used by calculator-based shortcodes.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Calculator_Renderer {
	/**
	 * Render the shared calculator UI.
	 *
	 * @param array<string, mixed> $args Calculator render arguments.
	 * @return string
	 */
	public function render($args = array()) {
		$args = wp_parse_args(
			$args,
			array(
				'title'                  => __('Gold Calculator', 'alloy-metal-price-api'),
				'metal'                  => 'gold',
				'purity_value'           => 14,
				'classring'              => false,
				'base_price_per_gram'    => 0,
				'show_skeleton'          => true,
				'wrapper_class'          => '',
				'section_class'          => 'aur:mx-auto aur:w-full aur:max-w-130 aur:rounded-3xl aur:bg-white aur:p-5 aur:font-sans aur:shadow-[0_8px_24px_rgba(0,0,0,0.06)] aur:sm:p-6',
				'heading_class'          => 'aur:mb-5 aur:text-center aur:text-xl! aur:font-semibold aur:text-primary',
				'button_class'           => 'alloy-calculator-submit aur:inline-flex aur:w-full aur:hover:cursor-pointer aur:justify-center aur:rounded-md! aur:border! aur:border-accent! aur:bg-accent! aur:px-4 aur:py-3 aur:text-base aur:font-semibold aur:text-white aur:transition-all aur:ease-in-out aur:focus:outline-none',
				'cta_class'              => 'alloy-calculator-kit-button aur:inline-flex aur:w-full aur:justify-center aur:rounded-md! aur:border aur:border-secondary aur:bg-secondary! aur:px-4 aur:py-5 aur:text-base aur:font-semibold aur:text-white! aur:no-underline! aur:transition-all aur:ease-in-out',
				'form_class'             => 'aur:grid aur:gap-4',
				'field_group_class'      => 'aur:grid aur:gap-6',
				'result_group_class'     => 'aur:hidden aur:gap-3',
				'cta_url'                => 'https://thealloymarket.com/request-a-kit/?referral_trigger=checked&amp;referral_code=GOLDCALC',
				'cta_label'              => __('Get A Free Appraisal Kit', 'alloy-metal-price-api'),
				'karats'                 => array(24, 22, 18, 16, 14, 10),
			)
		);

		$instance_id       = wp_unique_id('alloy-calculator-');
		$skeleton_id       = $instance_id . '-skeleton';
		$title             = sanitize_text_field((string) $args['title']);
		$metal             = sanitize_key((string) $args['metal']);
		$metal_label       = $this->get_metal_label($metal);
		$purity_value      = 'gold' === $metal ? absint($args['purity_value']) : (float) $args['purity_value'];
		$classring         = rest_sanitize_boolean($args['classring']);
		$base_price        = (float) $args['base_price_per_gram'];
		$show_skeleton     = rest_sanitize_boolean($args['show_skeleton']);
		$wrapper_class     = trim((string) $args['wrapper_class']);
		$section_class     = trim('js-alloy-calculator ' . (string) $args['section_class']);
		$heading_class     = trim((string) $args['heading_class']);
		$button_class      = trim('js-alloy-calculator-calculate ' . (string) $args['button_class']);
		$cta_class         = trim((string) $args['cta_class']);
		$form_class        = trim('js-alloy-calculator-form ' . (string) $args['form_class']);
		$field_group_class = trim((string) $args['field_group_class']);
		$result_group      = trim('js-alloy-calculator-results ' . (string) $args['result_group_class']);
		$cta_url           = esc_url((string) $args['cta_url']);
		$cta_label         = sanitize_text_field((string) $args['cta_label']);
		$karats            = is_array($args['karats']) ? $args['karats'] : array(24, 22, 18, 16, 14, 10);

		ob_start();
?>
		<div class="<?php echo esc_attr($wrapper_class); ?>">
			<?php echo $this->render_inline_styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ($show_skeleton) : ?>
				<?php echo $this->render_skeleton($skeleton_id, $title, $classring); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
			<section
				id="<?php echo esc_attr($instance_id); ?>"
				class="<?php echo esc_attr($section_class); ?>"
				data-alloy-calculator-pending="true"
				data-base-price="<?php echo esc_attr((string) $base_price); ?>"
				data-metal="<?php echo esc_attr($metal); ?>"
				data-classring="<?php echo $classring ? 'true' : 'false'; ?>"
				data-default-purity="<?php echo esc_attr((string) $purity_value); ?>">
				<h2 class="<?php echo esc_attr($heading_class); ?>">
					<?php echo esc_html($title); ?>
				</h2>

				<form class="<?php echo esc_attr($form_class); ?>">
					<div>
						<label class="aur:mb-2 aur:block aur:text-sm aur:font-medium aur:text-slate-700">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: metal label like Gold or Platinum. */
									__('Current Price of %s Per Gram ($):', 'alloy-metal-price-api'),
									$metal_label
								)
							);
							?>
						</label>
						<div class="aur:text-2xl aur:font-semibold aur:text-primary">
							$<span class="js-alloy-calculator-display-price"><?php echo esc_html(number_format_i18n($base_price, 2)); ?></span>
						</div>
					</div>

					<div class="<?php echo esc_attr($field_group_class); ?>">
						<div>
							<label for="<?php echo esc_attr($instance_id . '-purity'); ?>" class="aur:mb-2 aur:block aur:text-sm aur:font-medium aur:text-slate-700">
								<?php echo esc_html('gold' === $metal ? __('Gold Karat:', 'alloy-metal-price-api') : __('Purity:', 'alloy-metal-price-api')); ?>
							</label>
							<?php if ('gold' === $metal) : ?>
								<select id="<?php echo esc_attr($instance_id . '-purity'); ?>" class="js-alloy-calculator-purity aur:w-full aur:rounded-md! aur:border aur:border-slate-300 aur:bg-white aur:px-4 aur:py-3! aur:text-base aur:text-slate-900 aur:focus:border-primary aur:focus:outline-none">
									<?php foreach ($karats as $karat) : ?>
										<option value="<?php echo esc_attr((string) $karat); ?>" <?php selected($purity_value, (int) $karat); ?>>
											<?php echo esc_html($this->format_purity_option_label((int) $karat, $metal)); ?>
										</option>
									<?php endforeach; ?>
								</select>
							<?php elseif ('platinum' === $metal) : ?>
								<select id="<?php echo esc_attr($instance_id . '-purity'); ?>" class="js-alloy-calculator-purity aur:w-full aur:rounded-md! aur:border aur:border-slate-300 aur:bg-white aur:px-4 aur:py-3! aur:text-base aur:text-slate-900 aur:focus:border-primary aur:focus:outline-none">
									<?php foreach ($this->get_platinum_purity_options() as $option) : ?>
										<option value="<?php echo esc_attr($option['value']); ?>" <?php selected((float) $purity_value, (float) $option['value']); ?>>
											<?php echo esc_html($option['label']); ?>
										</option>
									<?php endforeach; ?>
								</select>
							<?php else : ?>
								<input
									id="<?php echo esc_attr($instance_id . '-purity'); ?>"
									type="number"
									min="0"
									max="1"
									step="any"
									value="<?php echo esc_attr(number_format((float) $purity_value, 4, '.', '')); ?>"
									placeholder="<?php esc_attr_e('Example: 0.9995', 'alloy-metal-price-api'); ?>"
									class="js-alloy-calculator-purity aur:w-full aur:rounded-md! aur:border aur:border-slate-300 aur:bg-white aur:px-4 aur:py-3! aur:text-base aur:text-slate-900 aur:placeholder:text-slate-400 aur:focus:border-primary aur:focus:outline-none">
							<?php endif; ?>
						</div>

						<div>
							<label for="<?php echo esc_attr($instance_id . '-weight-unit'); ?>" class="aur:mb-2 aur:block aur:text-sm aur:font-medium aur:text-slate-700">
								<?php esc_html_e('Weight Unit:', 'alloy-metal-price-api'); ?>
							</label>
							<select id="<?php echo esc_attr($instance_id . '-weight-unit'); ?>" class="js-alloy-calculator-weight-unit aur:w-full aur:rounded-md! aur:border aur:border-slate-300 aur:bg-white aur:px-4 aur:py-3! aur:text-base aur:text-slate-900 aur:focus:border-primary aur:focus:outline-none">
								<option value="grams"><?php esc_html_e('Grams', 'alloy-metal-price-api'); ?></option>
								<option value="ounces"><?php esc_html_e('Ounces', 'alloy-metal-price-api'); ?></option>
								<option value="pennyweight"><?php esc_html_e('Pennyweight', 'alloy-metal-price-api'); ?></option>
							</select>
						</div>
					</div>

					<div>
						<label for="<?php echo esc_attr($instance_id . '-weight'); ?>" class="aur:mb-2 aur:block aur:text-sm aur:font-medium aur:text-slate-700">
							<?php esc_html_e('Weight:', 'alloy-metal-price-api'); ?>
						</label>
						<input
							id="<?php echo esc_attr($instance_id . '-weight'); ?>"
							type="number"
							min="0"
							step="any"
							placeholder="<?php esc_attr_e('Enter weight', 'alloy-metal-price-api'); ?>"
							class="js-alloy-calculator-weight aur:w-full aur:rounded-md! aur:border aur:border-slate-300 aur:bg-white aur:px-4 aur:py-3! aur:text-base aur:text-slate-900 aur:placeholder:text-slate-400 aur:focus:border-primary aur:focus:outline-none">
					</div>

					<?php if ($classring) : ?>
						<div class="js-alloy-calculator-classring aur:relative aur:mb-1">
							<div class="aur:mb-2 aur:flex aur:items-center aur:gap-2">
								<label for="<?php echo esc_attr($instance_id . '-stone'); ?>" class="aur:m-0 aur:text-sm aur:font-medium aur:text-primary">
									<?php esc_html_e('Stone material:', 'alloy-metal-price-api'); ?>
								</label>
								<div
									class="js-alloy-calculator-tooltip-button aur:inline-flex aur:h-5 aur:w-5 aur:items-center aur:justify-center aur:rounded-full! aur:border-0 aur:bg-primary aur:p-0 aur:text-[11px] aur:leading-none aur:font-bold aur:text-white aur:cursor-pointer!"
									aria-expanded="false"
									aria-controls="<?php echo esc_attr($instance_id . '-tooltip'); ?>">
									i
								</div>
							</div>

							<div
								id="<?php echo esc_attr($instance_id . '-tooltip'); ?>"
								role="tooltip"
								class="js-alloy-calculator-tooltip aur:absolute aur:left-1/2 aur:top-9 aur:z-30 aur:hidden aur:w-full aur:min-w-60 aur:max-w-85 aur:-translate-x-1/2 aur:rounded-2xl aur:bg-primary aur:px-3 aur:py-3 aur:text-sm aur:leading-5 aur:text-white aur:shadow-[0_6px_18px_rgba(0,0,0,0.18)]">
								<?php esc_html_e('Not sure which to pick? Try these quick cues:', 'alloy-metal-price-api'); ?>
								<ul class="aur:mt-2 aur:list-disc aur:space-y-1 aur:pl-4">
									<li><?php esc_html_e('Most colored class-ring stones: Spinel or Corundum', 'alloy-metal-price-api'); ?></li>
									<li><?php esc_html_e('Very clear and sparkly: Cubic Zirconia (CZ)', 'alloy-metal-price-api'); ?></li>
									<li><?php esc_html_e('Opaque or translucent school colors: Glass or Quartz', 'alloy-metal-price-api'); ?></li>
									<li><?php esc_html_e('Unsure or plain metal: No stone (metal-only)', 'alloy-metal-price-api'); ?></li>
								</ul>
							</div>

							<select
								id="<?php echo esc_attr($instance_id . '-stone'); ?>"
								class="js-alloy-calculator-stone aur:w-full aur:rounded-md! aur:border aur:border-slate-300 aur:bg-white aur:px-4 aur:py-3! aur:text-base aur:text-slate-900 aur:focus:border-primary aur:focus:outline-none">
								<option value="none"><?php esc_html_e('No stone (metal-only)', 'alloy-metal-price-api'); ?></option>
								<option value="quartz"><?php esc_html_e('Quartz / Glass', 'alloy-metal-price-api'); ?></option>
								<option value="spinel"><?php esc_html_e('Spinel (synthetic)', 'alloy-metal-price-api'); ?></option>
								<option value="corundum"><?php esc_html_e('Sapphire/Ruby (corundum)', 'alloy-metal-price-api'); ?></option>
								<option value="cz"><?php esc_html_e('Cubic Zirconia', 'alloy-metal-price-api'); ?></option>
							</select>
						</div>
					<?php endif; ?>

					<div>
						<button type="submit" class="<?php echo esc_attr($button_class); ?>">
							<?php esc_html_e('Calculate Value', 'alloy-metal-price-api'); ?>
						</button>
					</div>

					<div class="<?php echo esc_attr($result_group); ?>">
						<div class="alloy-calculator-result-card alloy-calculator-result-card--market aur:rounded-2xl aur:border aur:border-slate-200 aur:bg-slate-50 aur:p-4">
							<h3 class="aur:m-0 aur:text-base! aur:font-semibold aur:text-slate-900!">
								<?php esc_html_e('Current Market Value:', 'alloy-metal-price-api'); ?>
								<span class="js-alloy-calculator-market-value alloy-calculator-result-value">$0.00</span>
							</h3>
						</div>
						<div class="alloy-calculator-result-card alloy-calculator-result-card--pawn aur:rounded-2xl aur:border aur:border-red-200 aur:bg-red-50 aur:p-4">
							<h3 class="aur:m-0 aur:text-base! aur:font-semibold aur:text-slate-900!">
								<?php esc_html_e('Average Pawn Shop Offer:', 'alloy-metal-price-api'); ?>
								<span class="js-alloy-calculator-pawn-value alloy-calculator-result-value">$0.00</span>
							</h3>
						</div>
						<div class="alloy-calculator-result-card alloy-calculator-result-card--alloy aur:rounded-2xl aur:border aur:border-emerald-200 aur:bg-emerald-50 aur:p-4">
							<h3 class="aur:m-0 aur:text-base! aur:font-semibold aur:text-slate-900!">
								<?php esc_html_e('Alloy\'s Minimum Offer:', 'alloy-metal-price-api'); ?>
								<span class="js-alloy-calculator-alloy-value alloy-calculator-result-value">$0.00</span>
							</h3>
						</div>
						<?php if ($classring) : ?>
							<p class="js-alloy-calculator-meta aur:m-0 aur:text-sm aur:text-slate-600"></p>
						<?php endif; ?>
					</div>

					<div class="js-alloy-calculator-cta aur:hidden">
						<a class="<?php echo esc_attr($cta_class); ?>" href="<?php echo esc_url($cta_url); ?>">
							<?php echo esc_html($cta_label); ?>
						</a>
					</div>
				</form>
			</section>
			<?php if ($show_skeleton) : ?>
				<?php echo $this->render_reveal_script($instance_id, $skeleton_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</div>
<?php

		return trim((string) ob_get_clean());
	}

	/**
	 * Render lightweight inline styles needed for first-paint controls and result cards.
	 *
	 * @return string
	 */
	protected function render_inline_styles() {
		static $styles_rendered = false;

		if ($styles_rendered) {
			return '';
		}

		$styles_rendered = true;

		return '<style>@keyframes alloyCalculatorSkeletonExit{to{height:0;margin:0;padding:0;opacity:0;overflow:hidden;visibility:hidden}}.alloy-calculator-skeleton{animation:alloyCalculatorSkeletonExit .01s linear .6s forwards}.alloy-calculator-submit{min-height:49px;border-color:#727a82!important;background:#727a82!important;color:#fff!important;border-radius:4px!important;font-weight:700!important}.alloy-calculator-submit:hover{border-color:#616870!important;background:#616870!important;color:#fff!important}.alloy-calculator-result-card{border-radius:6px!important;padding:14px 10px!important;font-family:var(--aur-font-sans,"Lexend Deca",Arial,sans-serif}.alloy-calculator-result-card h3{font-size:18px!important;line-height:1.35!important;font-weight:700!important;color:#1f2937!important}.alloy-calculator-result-value{display:inline;font:inherit;color:inherit}.alloy-calculator-result-card--market{border-color:#c7ccd2!important;background:#eef0f3!important}.alloy-calculator-result-card--pawn{border-color:#e0c5c7!important;background:#efd4d6!important}.alloy-calculator-result-card--alloy{border-color:#bdd7c2!important;background:#d8ead9!important}.alloy-calculator-kit-button{min-height:62px;align-items:center;border-color:#df8158!important;background:#df8158!important;color:#fff!important;border-radius:7px!important;font-weight:700!important}.alloy-calculator-kit-button:hover{border-color:#d4744b!important;background:#d4744b!important;color:#fff!important}</style>';
	}

	/**
	 * Render the inline skeleton that reserves calculator layout before plugin.css loads.
	 *
	 * @param string $skeleton_id Skeleton element ID.
	 * @param string $title Calculator title.
	 * @param bool   $classring Whether the class ring selector will render.
	 * @return string
	 */
	protected function render_skeleton($skeleton_id, $title, $classring) {
		$rows = $classring ? 4 : 3;

		ob_start();
?>
		<div id="<?php echo esc_attr($skeleton_id); ?>" class="alloy-calculator-skeleton" aria-hidden="true" style="box-sizing:border-box;width:100%;max-width:520px;margin:0 auto;padding:20px;border-radius:24px;background:#fff;box-shadow:0 8px 24px rgba(0,0,0,.06);font-family:'Lexend Deca',Arial,sans-serif;color:#1f2937;">
			<div style="height:28px;width:70%;max-width:320px;margin:0 auto 20px;border-radius:8px;background:#e8edf1;color:transparent;overflow:hidden;"><?php echo esc_html($title); ?></div>
			<div style="display:grid;gap:16px;">
				<div style="display:grid;gap:8px;">
					<div style="width:62%;height:15px;border-radius:6px;background:#edf1f4;"></div>
					<div style="width:38%;height:30px;border-radius:8px;background:#dfe6eb;"></div>
				</div>
				<?php for ($index = 0; $index < $rows; $index++) : ?>
					<div style="display:grid;gap:8px;">
						<div style="width:34%;height:14px;border-radius:6px;background:#edf1f4;"></div>
						<div style="height:48px;border-radius:6px;border:1px solid #d5dbe1;background:#f8fafb;"></div>
					</div>
				<?php endfor; ?>
				<div style="height:48px;border-radius:6px;background:#737a82;"></div>
			</div>
		</div>
<?php

		return trim((string) ob_get_clean());
	}

	/**
	 * Render an inline reveal script so the calculator waits for plugin.css.
	 *
	 * @param string $instance_id Calculator section ID.
	 * @param string $skeleton_id Skeleton element ID.
	 * @return string
	 */
	protected function render_reveal_script($instance_id, $skeleton_id) {
		$instance_id_json = wp_json_encode($instance_id);
		$skeleton_id_json = wp_json_encode($skeleton_id);

		return sprintf(
			'<script>(function(){var calculator=document.getElementById(%1$s);var skeleton=document.getElementById(%2$s);if(!calculator){return;}var revealed=false;function reveal(){if(revealed){return;}revealed=true;calculator.style.display="";calculator.removeAttribute("data-alloy-calculator-pending");if(skeleton){skeleton.hidden=true;skeleton.style.display="none";}}function pluginStylesheet(){var links=document.querySelectorAll("link[rel~=\"stylesheet\"]");for(var i=0;i<links.length;i++){var link=links[i];if(link.id==="alloy-metal-price-api-frontend-css"||(link.href&&link.href.indexOf("/assets/dist/css/plugin.css")!==-1)){return link;}}return null;}var link=pluginStylesheet();if(!link){reveal();return;}if(link.sheet){reveal();return;}link.addEventListener("load",reveal,{once:true});var checks=0;var timer=window.setInterval(function(){checks++;if(link.sheet||checks>=8){window.clearInterval(timer);reveal();}},50);})();</script>',
			$instance_id_json,
			$skeleton_id_json
		);
	}

	/**
	 * Get a display label for a supported metal.
	 *
	 * @param string $metal Normalized metal key.
	 * @return string
	 */
	protected function get_metal_label($metal) {
		$labels = array(
			'gold'      => __('Gold', 'alloy-metal-price-api'),
			'silver'    => __('Silver', 'alloy-metal-price-api'),
			'platinum'  => __('Platinum', 'alloy-metal-price-api'),
			'palladium' => __('Palladium', 'alloy-metal-price-api'),
		);

		if (isset($labels[$metal])) {
			return $labels[$metal];
		}

		return $labels['gold'];
	}

	/**
	 * Format the purity option label shown in the calculator select.
	 *
	 * @param int    $karat Purity value on a 24-point scale.
	 * @param string $metal Normalized metal key.
	 * @return string
	 */
	protected function format_purity_option_label($karat, $metal) {
		if ('gold' === $metal) {
			return sprintf(
				/* translators: %d: karat value. */
				__('%d Karat', 'alloy-metal-price-api'),
				$karat
			);
		}

		return (string) $karat;
	}

	/**
	 * Get the predefined platinum purity options.
	 *
	 * @return array<int, array<string, string>>
	 */
	protected function get_platinum_purity_options() {
		return array(
			array(
				'value' => '0.9995',
				'label' => '999.5',
			),
			array(
				'value' => '0.999',
				'label' => '999',
			),
			array(
				'value' => '0.95',
				'label' => '950',
			),
			array(
				'value' => '0.9',
				'label' => '900',
			),
			array(
				'value' => '0.85',
				'label' => '850',
			),
		);
	}
}
