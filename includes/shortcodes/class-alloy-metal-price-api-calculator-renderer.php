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
				'wrapper_class'          => '',
				'section_class'          => 'aur:mx-auto aur:w-full aur:max-w-130 aur:rounded-3xl aur:bg-white aur:p-5 aur:font-sans aur:shadow-[0_8px_24px_rgba(0,0,0,0.06)] aur:sm:p-6',
				'heading_class'          => 'aur:mb-10! aur:mt-5! aur:text-center aur:text-2xl! aur:font-semibold aur:text-black!',
				'button_class'           => 'aur:inline-flex aur:w-full aur:hover:cursor-pointer aur:justify-center aur:rounded-md! aur:border! aur:border-accent! aur:bg-accent! aur:px-4 aur:py-3 aur:text-base aur:font-semibold aur:text-white aur:transition-all aur:ease-in-out aur:hover:bg-white! aur:hover:text-accent! aur:focus:outline-none',
				'cta_class'              => 'aur:inline-flex aur:w-full aur:justify-center aur:rounded-md! aur:border aur:border-secondary aur:bg-secondary! aur:px-4 aur:py-5 aur:text-base aur:font-semibold aur:text-white! aur:no-underline! aur:transition-all aur:ease-in-out aur:hover:bg-white! aur:hover:text-secondary! aur:hover:border-secondary',
				'form_class'             => 'aur:grid aur:gap-4',
				'field_group_class'      => 'aur:grid aur:gap-6',
				'result_group_class'     => 'aur:hidden aur:gap-3',
				'cta_url'                => 'https://thealloymarket.com/request-a-kit/?referral_trigger=checked&amp;referral_code=GOLDCALC',
				'cta_label'              => __('Get A Free Alloy Kit', 'alloy-metal-price-api'),
				'karats'                 => array(24, 22, 18, 16, 14, 10),
			)
		);

		$instance_id       = wp_unique_id('alloy-calculator-');
		$title             = sanitize_text_field((string) $args['title']);
		$metal             = sanitize_key((string) $args['metal']);
		$metal_label       = $this->get_metal_label($metal);
		$purity_value      = 'gold' === $metal ? absint($args['purity_value']) : (float) $args['purity_value'];
		$classring         = rest_sanitize_boolean($args['classring']);
		$base_price        = (float) $args['base_price_per_gram'];
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
			<section
				id="<?php echo esc_attr($instance_id); ?>"
				class="<?php echo esc_attr($section_class); ?>"
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
						<div class="aur:text-2xl aur:font-semibold aur:text-slate-900">
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
					</div>

					<div class="<?php echo esc_attr($result_group); ?>">
						<div class="aur:rounded-2xl aur:border aur:border-slate-200 aur:bg-slate-50 aur:p-4 aur:flex aur:gap-2">
							<h3 class="aur:text-base! aur:font-semibold aur:text-slate-900! aur:m-0!"><?php esc_html_e('Current Market Value:', 'alloy-metal-price-api'); ?></h3>
							<p class="js-alloy-calculator-market-value aur:m-0! aur:text-base! aur:font-semibold aur:text-slate-900!">$0.00</p>
						</div>
						<div class="aur:rounded-2xl aur:border aur:border-red-200 aur:bg-red-50 aur:p-4 aur:flex aur:gap-2">
							<h3 class="aur:text-base! aur:font-semibold aur:text-slate-900! aur:m-0!"><?php esc_html_e('Average Pawn Shop Offer:', 'alloy-metal-price-api'); ?></h3>
							<p class="js-alloy-calculator-pawn-value aur:m-0! aur:text-base! aur:font-semibold aur:text-slate-900!">$0.00</p>
						</div>
						<div class="aur:rounded-2xl aur:border aur:border-emerald-200 aur:bg-emerald-50 aur:p-4 aur:flex aur:gap-2">
							<h3 class="aur:text-base! aur:font-semibold aur:text-slate-900! aur:m-0!"><?php esc_html_e('Alloy\'s Estimated Offer:', 'alloy-metal-price-api'); ?></h3>
							<p class="js-alloy-calculator-alloy-value aur:m-0! aur:text-base! aur:font-semibold aur:text-slate-900!">$0.00</p>
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
		</div>
<?php

		return trim((string) ob_get_clean());
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
