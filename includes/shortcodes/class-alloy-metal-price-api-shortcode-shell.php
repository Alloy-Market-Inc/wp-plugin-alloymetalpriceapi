<?php

/**
 * Shared first-paint shell helpers for styled shortcodes.
 *
 * @package AlloyMetalPriceAPI
 */

if (! defined('ABSPATH')) {
	exit;
}

class Alloy_Metal_Price_API_Shortcode_Shell {
	/**
	 * Render a shell that reserves shortcode layout until plugin.css is ready.
	 *
	 * @param string $content Real shortcode markup.
	 * @param string $skeleton Shape-matched skeleton markup.
	 * @param string $prefix Unique ID prefix.
	 * @param string $reserve_kind Optional first-paint reserve kind.
	 * @return string
	 */
	public static function render($content, $skeleton, $prefix = 'alloy-shortcode', $reserve_kind = '') {
		$instance_id   = wp_unique_id(sanitize_key($prefix) . '-');
		$skeleton_id   = $instance_id . '-skeleton';
		$reserve_kind  = self::normalize_reserve_kind($reserve_kind);
		$shell_classes = 'alloy-shortcode-shell';

		if ('' !== $reserve_kind) {
			$shell_classes .= ' alloy-shortcode-shell--reserve alloy-shortcode-shell--reserve-' . $reserve_kind;
		}

		return sprintf(
			'<div class="%1$s">%2$s<div id="%3$s" class="alloy-shortcode-skeleton" aria-hidden="true">%4$s</div><div id="%5$s" class="alloy-shortcode-content" data-alloy-shortcode-pending="true">%6$s</div>%7$s</div>',
			esc_attr($shell_classes),
			self::render_inline_styles(),
			esc_attr($skeleton_id),
			$skeleton,
			esc_attr($instance_id),
			$content,
			self::render_reveal_script($instance_id, $skeleton_id)
		);
	}

	/**
	 * Render shared inline styles used before the compiled stylesheet arrives.
	 *
	 * @return string
	 */
	protected static function render_inline_styles() {
		static $styles_rendered = false;

		if ($styles_rendered) {
			return '';
		}

		$styles_rendered = true;

		return '<style>@keyframes alloyShortcodeSkeletonExit{to{opacity:0;visibility:hidden}}@keyframes alloyShortcodePendingFallback{to{visibility:visible}}.alloy-shortcode-shell{position:relative;width:100%;box-sizing:border-box}.alloy-shortcode-shell--reserve{min-height:200px}.alloy-shortcode-shell--reserve-metal-price-table{min-height:386px}.alloy-shortcode-shell--reserve-metal-offer-card{min-height:200px}.alloy-shortcode-shell--reserve-metal-spot-ticker{min-height:142px}.alloy-shortcode-shell--reserve-conversion-rate-calculator{min-height:316px}.alloy-shortcode-skeleton{position:absolute;top:0;right:0;left:0;z-index:2;pointer-events:none;animation:alloyShortcodeSkeletonExit .01s linear 3s forwards}.alloy-shortcode-content[data-alloy-shortcode-pending="true"]{visibility:hidden;animation:alloyShortcodePendingFallback .01s linear 3s forwards}.alloy-shortcode-placeholder-card{box-sizing:border-box;width:100%;border-radius:16px;border:1px solid #0f172a;background:#fff;padding:24px;box-shadow:0 4px 16px rgba(0,0,0,.06);font-family:"Lexend Deca",Arial,sans-serif}.alloy-shortcode-placeholder-line{height:16px;border-radius:6px;background:#edf1f4}.alloy-shortcode-placeholder-strong{height:28px;border-radius:8px;background:#dfe6eb}.alloy-shortcode-placeholder-row{height:46px;border-radius:8px;background:#f4f7f9}.alloy-shortcode-placeholder-grid{display:grid;gap:12px}.alloy-shortcode-placeholder-table{box-sizing:border-box;width:100%;max-width:720px;margin:0 auto;border-radius:16px;border:1px solid #0f172a;background:#fff;padding:16px;box-shadow:0 4px 16px rgba(0,0,0,.06);font-family:"Lexend Deca",Arial,sans-serif}.alloy-shortcode-placeholder-table .alloy-shortcode-placeholder-row:nth-child(even){background:#f9f9f9}@media (max-width:767px){.alloy-shortcode-shell--reserve-metal-price-table{min-height:454px}}@media (min-width:768px){.alloy-shortcode-placeholder-two-col{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:32px}.alloy-shortcode-placeholder-three-col{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}}</style>';
	}

	/**
	 * Render the CSS-ready reveal script.
	 *
	 * @param string $instance_id Content element ID.
	 * @param string $skeleton_id Skeleton element ID.
	 * @return string
	 */
	protected static function render_reveal_script($instance_id, $skeleton_id) {
		$instance_id_json = wp_json_encode($instance_id);
		$skeleton_id_json = wp_json_encode($skeleton_id);

		return sprintf(
			'<script>(function(){var content=document.getElementById(%1$s);var skeleton=document.getElementById(%2$s);if(!content){return;}var revealed=false;var started=Date.now();function reveal(){if(revealed){return;}revealed=true;content.removeAttribute("data-alloy-shortcode-pending");content.style.visibility="";if(skeleton){skeleton.hidden=true;skeleton.style.display="none";}}function pluginStylesheet(){var links=document.querySelectorAll("link[rel~=\"stylesheet\"]");for(var i=0;i<links.length;i++){var link=links[i];var href=link.href||"";if(link.id==="alloy-metal-price-api-frontend-css"||href.indexOf("/assets/dist/css/plugin.css")!==-1||href.indexOf("AlloyMetalPriceAPI")!==-1||href.indexOf("alloy-metal-price-api")!==-1){return link;}}return null;}function ready(link){return !!(link&&link.sheet);}function wait(){var link=pluginStylesheet();if(ready(link)){reveal();return;}if(link){link.addEventListener("load",reveal,{once:true});link.addEventListener("error",reveal,{once:true});}if(Date.now()-started>=3000){reveal();return;}window.setTimeout(wait,50);}window.addEventListener("NitroStylesLoaded",reveal,{once:true});document.addEventListener("NitroStylesLoaded",reveal,{once:true});wait();})();</script>',
			$instance_id_json,
			$skeleton_id_json
		);
	}

	/**
	 * Normalize a reserve kind into a known CSS suffix.
	 *
	 * @param string $reserve_kind Reserve kind.
	 * @return string
	 */
	protected static function normalize_reserve_kind($reserve_kind) {
		$reserve_kind = str_replace('_', '-', sanitize_key((string) $reserve_kind));
		$allowed      = array(
			'metal-price-table',
			'metal-offer-card',
			'metal-spot-ticker',
			'conversion-rate-calculator',
		);

		return in_array($reserve_kind, $allowed, true) ? $reserve_kind : '';
	}

	/**
	 * Render a generic table-shaped placeholder.
	 *
	 * @param int $rows Number of row placeholders.
	 * @return string
	 */
	public static function table_skeleton($rows = 4) {
		ob_start();
		?>
		<div class="alloy-shortcode-placeholder-table">
			<div class="alloy-shortcode-placeholder-strong" style="width:56%;max-width:360px;margin:0 auto 16px;"></div>
			<div class="alloy-shortcode-placeholder-grid">
				<?php for ($index = 0; $index < $rows; $index++) : ?>
					<div class="alloy-shortcode-placeholder-row"></div>
				<?php endfor; ?>
			</div>
			<div class="alloy-shortcode-placeholder-line" style="width:72%;margin-top:16px;"></div>
		</div>
		<?php

		return trim((string) ob_get_clean());
	}

	/**
	 * Render a generic card-shaped placeholder.
	 *
	 * @param int $rows Number of row placeholders.
	 * @return string
	 */
	public static function card_skeleton($rows = 3) {
		ob_start();
		?>
		<div class="alloy-shortcode-placeholder-card">
			<div class="alloy-shortcode-placeholder-strong" style="width:62%;max-width:380px;margin:0 auto 20px;"></div>
			<div class="alloy-shortcode-placeholder-grid">
				<?php for ($index = 0; $index < $rows; $index++) : ?>
					<div class="alloy-shortcode-placeholder-row"></div>
				<?php endfor; ?>
			</div>
		</div>
		<?php

		return trim((string) ob_get_clean());
	}
}
