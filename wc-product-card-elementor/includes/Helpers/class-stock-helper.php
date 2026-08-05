<?php
/**
 * Stock helper.
 *
 * Stateless static methods for all stock-status logic: determining whether a
 * product is out of stock, whether the OOS visual treatment (dimming/grayscale)
 * should be applied, and initialising the stock label slot (visibility flag plus
 * label text).
 *
 * This class was extracted from templates/card.php in v1.0.81 (Phase 6, R3).
 * The behaviour is identical to the pre-extraction code — this is pure internal
 * restructuring so the same logic can be reused by future widgets (e.g. the PDP
 * Gallery widget) without duplicating it. The rendered product card output is
 * unchanged.
 *
 * All methods are static and hold no state, so the class needs no constructor
 * and can be called directly: WCPCE_Stock_Helper::is_out_of_stock( $product ).
 *
 * @package WC_Product_Card_Elementor
 * @since 1.0.81
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stock status logic helper.
 *
 * @since 1.0.81
 */
class WCPCE_Stock_Helper {

	/**
	 * Returns whether the product is out of stock according to WooCommerce.
	 *
	 * Thin wrapper around WC_Product::is_in_stock() with a boolean return so
	 * callers get a typed result without needing to negate is_in_stock() themselves.
	 *
	 * @since 1.0.81
	 * @param \WC_Product $product The product.
	 * @return bool True when the product is out of stock.
	 */
	public static function is_out_of_stock( \WC_Product $product ): bool {
		return ! $product->is_in_stock();
	}

	/**
	 * Returns whether the OOS visual treatment should be applied to the card.
	 *
	 * The visual treatment (dimming and grayscale via the wc-card--out-of-stock
	 * modifier class) is applied only when:
	 * - the product is actually out of stock, AND
	 * - the "Show out of stock label" toggle is enabled in widget settings.
	 *
	 * Without the second condition, disabling the OOS label toggle would still
	 * leave the card visually dimmed, which does not match what the toggle implies.
	 *
	 * Note: the niet-leverbaar treatment (ACF badge) also applies the OOS visual
	 * class, but that is handled separately by WCPCE_Badge_Helper::apply_badge_priority()
	 * which mutates $card_classes by reference after this method runs.
	 *
	 * @since 1.0.81
	 * @param bool  $is_out_of_stock Whether the product is out of stock.
	 * @param array $settings        Widget settings.
	 * @return bool True when the OOS modifier class should be added to the card.
	 */
	public static function should_show_oos_visual( bool $is_out_of_stock, array $settings ): bool {
		return $is_out_of_stock && 'yes' === ( $settings['show_out_of_stock_label'] ?? 'yes' );
	}

	/**
	 * Initialises the stock label slot: visibility flag and label text.
	 *
	 * Returns a small array rather than two separate calls so callers get a single
	 * consistent initialisation point before WCPCE_Badge_Helper::apply_badge_priority()
	 * can mutate the visibility flag via its by-reference $show_stock_label parameter.
	 *
	 * Defaults use __() not esc_html__(): the value is escaped at output via esc_html()
	 * in card.php. Pre-escaping here would double-encode characters like apostrophes if
	 * the default is ever changed to include one (CONVENTIONS).
	 *
	 * @since 1.0.81
	 * @param bool  $show_oos_visual Whether the OOS visual treatment is active.
	 * @param array $settings        Widget settings.
	 * @return array {
	 *     @type bool   $show  Whether the tijdelijk-uitverkocht label should render.
	 *     @type string $label The label text (editor-customisable, defaults to Dutch).
	 * }
	 */
	public static function get_stock_label( bool $show_oos_visual, array $settings ): array {
		return array(
			'show'  => $show_oos_visual,
			'label' => isset( $settings['out_of_stock_label'] )
				? $settings['out_of_stock_label']
				: __( 'Tijdelijk uitverkocht', 'woo-card-chef' ),
		);
	}
}
