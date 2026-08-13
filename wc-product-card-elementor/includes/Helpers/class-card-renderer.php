<?php
/**
 * Shared product card renderer.
 *
 * The archive Product Card Grid and PDP product-list widgets render the same
 * card partial. Keeping the sprite, card-data computation and template include
 * here prevents future PDP blocks from copying card business logic.
 *
 * @package WC_Product_Card_Elementor
 * @since 2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared product card rendering logic.
 *
 * @since 2.5.0
 */
class WCPCE_Card_Renderer {

	/**
	 * Outputs the SVG sprite used by card.php.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public static function render_svg_sprite(): void {
		static $rendered = false;
		if ( $rendered ) {
			return;
		}
		$rendered = true;

		echo '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute;width:0;height:0;overflow:hidden;" aria-hidden="true" focusable="false">';
		echo '<defs>';
		echo '<symbol id="wcpce-icon-check" viewBox="0 0 16 16">';
		echo '<path d="M3 8.5L6.5 12L13 4.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>';
		echo '</symbol>';
		echo '<symbol id="wcpce-icon-truck" viewBox="0 0 16 16">';
		echo '<path d="M1.5 4.5h8v6h-8v-6zM9.5 7h3l2 2v1.5h-5V7z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>';
		echo '<circle cx="4.5" cy="11.5" r="1" fill="none" stroke="currentColor" stroke-width="1.4"/>';
		echo '<circle cx="11.5" cy="11.5" r="1" fill="none" stroke="currentColor" stroke-width="1.4"/>';
		echo '</symbol>';
		echo '<symbol id="wcpce-icon-leaf" viewBox="0 0 2.996769 3.3520503">';
		echo '<path fill="currentColor" d="M 0.11367774,3.3348666 C 0.02320601,3.2929796 0.00890051,3.1786555 0.08832631,3.1322685 0.10391023,3.1231682 0.13260497,3.1181068 0.19506976,3.1134495 0.29652413,3.1058845 0.33914794,3.0987479 0.41633848,3.0764122 0.58186152,3.0285133 0.78884167,2.9229141 1.0409714,2.7577303 l 0.1132021,-0.074165 -0.029791,-0.0056 C 0.89175531,2.6342447 0.64950905,2.4417489 0.53054831,2.2060889 0.47187517,2.0898579 0.4486248,1.9900765 0.44822565,1.8527917 0.44779733,1.705458 0.47530363,1.5906616 0.54655753,1.4424094 0.68311158,1.1582923 0.92335288,0.89743049 1.2030447,0.72957453 1.3955841,0.61402279 1.5537682,0.54886654 1.9505712,0.42166842 2.2190293,0.33561226 2.3110565,0.30175807 2.421363,0.24847776 2.5480886,0.18726658 2.6454532,0.11580257 2.7124265,0.0348413 2.7323853,0.0107136 2.7506579,-0.00675553 2.7530322,-0.00397989 2.760681,0.00496356 2.8264941,0.23670288 2.8672214,0.39810327 2.9817216,0.85186416 3.0333776,1.2295717 3.0331945,1.6116957 3.0331031,1.8023051 3.0278164,1.8945448 3.0092418,2.0296231 2.9230828,2.6561979 2.6237012,3.0912894 2.1998487,3.2059148 2.1429872,3.2212921 2.1232265,3.2232829 2.0287943,3.2231472 1.9312935,3.2230117 1.9153688,3.2212167 1.8452901,3.2026511 1.6564554,3.1525904 1.5237147,3.0750108 1.4428613,2.9674522 L 1.4125374,2.9271126 1.534072,2.805339 C 1.9661414,2.372421 2.2887695,1.81821 2.4826704,1.1758359 2.5025544,1.1099621 2.5178789,1.0551211 2.5167249,1.053967 2.5155666,1.0528208 2.4986456,1.088693 2.4791133,1.1337001 2.3377437,1.4594496 2.1378337,1.7941674 1.9161261,2.0763332 1.7675395,2.2654387 1.5155865,2.5302833 1.3481611,2.6733585 1.086292,2.8971422 0.66337924,3.1659788 0.37329167,3.2930623 0.24743492,3.3481985 0.16955853,3.3607387 0.11367774,3.3348666 Z"/>';
		echo '</symbol>';
		echo '</defs>';
		echo '</svg>';
	}

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $widget_id is consumed by the included templates/card.php file.
	/**
	 * Renders a single product card using templates/card.php.
	 *
	 * @since 2.5.0
	 * @param \WC_Product $product   The product instance.
	 * @param array       $settings  Card settings.
	 * @param int         $index     Zero-based card index.
	 * @param string      $widget_id Elementor widget ID.
	 * @return void
	 */
	public static function render_card( \WC_Product $product, array $settings, int $index = 0, string $widget_id = '' ): void {
		// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		static $template_exists = null;
		if ( null === $template_exists ) {
			$template_exists = file_exists( WCPCE_PLUGIN_DIR . 'templates/card.php' );
		}
		if ( ! $template_exists ) {
			return;
		}

		$card     = self::compute_card_data( $product, $settings, $index );
		$template = WCPCE_PLUGIN_DIR . 'templates/card.php';
		include $template;
	}

	/**
	 * Computes derived card data for the card template.
	 *
	 * @since 2.5.0
	 * @param \WC_Product $product  The product instance.
	 * @param array       $settings Card settings.
	 * @param int         $index    Zero-based card index.
	 * @return array<string,mixed>
	 */
	public static function compute_card_data( \WC_Product $product, array $settings, int $index = 0 ): array {
		$data = array(
			'is_on_sale'       => false,
			'is_variable'      => $product->is_type( 'variable' ),
			'regular_price'    => 0.0,
			'sale_price'       => 0.0,
			'display_price'    => 0.0,
			'discount_percent' => 0,
			'savings_amount'   => 0.0,
			'show_badge'       => false,
			'badge_text'       => '',
			'index'            => $index,
		);

		$needs_discount_calc = 'yes' === ( $settings['show_badge'] ?? 'yes' )
			|| 'yes' === ( $settings['show_savings_line'] ?? '' );

		if ( $needs_discount_calc ) {
			$prices                   = WCPCE_Price_Helper::get_product_price_data( $product );
			$data['regular_price']    = $prices['regular_price'];
			$data['sale_price']       = $prices['sale_price'];
			$data['display_price']    = $prices['display_price'];
			$data['discount_percent'] = $prices['discount_percent'];
			$data['savings_amount']   = $prices['savings_amount'];
			$mixed_discounts          = $prices['mixed_discounts'];

			$data['is_on_sale'] = $product->is_on_sale() && $data['discount_percent'] > 0;
		} else {
			$data['display_price'] = floatval( $product->get_price() );
			$mixed_discounts       = false;
		}

		$data['price_html'] = $data['is_on_sale'] ? '' : $product->get_price_html();

		$badge             = WCPCE_Badge_Helper::compute_badge_data( $data, $settings, $mixed_discounts );
		$data['show_badge'] = $badge['show_badge'];
		$data['badge_text'] = $badge['badge_text'];

		return $data;
	}
}
