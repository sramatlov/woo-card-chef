<?php
/**
 * Price helper.
 *
 * Stateless static methods for product price data retrieval and money formatting.
 * Handles simple, variable, and grouped product types and encapsulates the
 * max-percentage-discount logic for variable products.
 *
 * This class was extracted from WC_Product_Card_Elementor_Widget::get_product_price_data()
 * in v1.0.82 (Phase 6, R2). The behaviour is identical to the pre-extraction code —
 * this is pure internal restructuring so the same logic can be reused by future widgets
 * (e.g. the PDP Price & Promo Block, PDP Phase 2) without duplicating it. The rendered
 * product card output is unchanged.
 *
 * All methods are static and hold no state, so the class needs no constructor
 * and can be called directly: WCPCE_Price_Helper::get_product_price_data( $product ).
 *
 * Note on format_money() duplication: this method also exists on WCPCE_Badge_Helper.
 * Both helpers must be standalone-usable and both format money amounts, so a static
 * alias on each is the accepted design (documented in ROADMAP public helper API)
 * rather than a shared base class. The implementation is identical.
 *
 * @package WC_Product_Card_Elementor
 * @since 1.0.82
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Price data and formatting helper.
 *
 * @since 1.0.82
 */
class WCPCE_Price_Helper {

	/**
	 * Returns price data for a product, handling simple and variable types.
	 *
	 * For variable products we track the specific variation that produces the
	 * maximum percentage discount, and return ITS regular and sale prices for
	 * display. This keeps the badge text, the strikethrough, the sale price,
	 * and the savings line all internally consistent. Without this, max regular
	 * across variations and min sale across variations could produce a misleading
	 * "Bespaar €25" claim against a €199 strikethrough that never had €25 off.
	 *
	 * mixed_discounts is true when not all variations share the same percentage
	 * discount (or some aren't on sale), which drives the "Tot -X" badge prefix.
	 *
	 * @since 1.0.0
	 * @since 1.0.82 Moved here from the widget class (Phase 6, R2).
	 * @since 2.4.1 Cached per request because multiple PDP widgets reuse it.
	 * @param \WC_Product $product The product.
	 * @return array {
	 *     @type float $regular_price    Regular price of the best-discount variation (or simple product).
	 *     @type float $sale_price       Sale price of the best-discount variation (or simple product).
	 *     @type float $display_price    Lowest currently displayed price (used for shipping pill threshold).
	 *     @type int   $discount_percent Maximum discount percentage across variations (or simple product).
	 *     @type float $savings_amount   Savings in currency for the best-discount variation.
	 *     @type bool  $mixed_discounts  Whether variable-product discounts are mixed across variations.
	 * }
	 */
	public static function get_product_price_data( \WC_Product $product ): array {
		static $cache = array();

		$cache_key = $product->get_id() > 0 ? (string) $product->get_id() : spl_object_hash( $product );
		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		$result = array(
			'regular_price'    => 0.0,
			'sale_price'       => 0.0,
			'display_price'    => 0.0,
			'discount_percent' => 0,
			'savings_amount'   => 0.0,
			'mixed_discounts'  => false,
		);

		if ( $product->is_type( 'variable' ) ) {
			$variation_prices = $product->get_variation_prices();
			if ( empty( $variation_prices['price'] ) ) {
				$cache[ $cache_key ] = $result;
				return $result;
			}

			// Lowest currently displayed variation price. Used for the free-shipping
			// threshold when the product is variable but not on sale.
			$result['display_price'] = min( array_map( 'floatval', $variation_prices['price'] ) );

			$best_pct      = 0;
			$best_reg      = 0.0;
			$best_sal      = 0.0;
			$best_savings  = 0.0;
			$percentages   = array();
			$on_sale_count = 0;
			$total_count   = count( $variation_prices['price'] );

			foreach ( $variation_prices['price'] as $variation_id => $price ) {
				$reg = isset( $variation_prices['regular_price'][ $variation_id ] ) ? floatval( $variation_prices['regular_price'][ $variation_id ] ) : 0.0;
				$sal = floatval( $price );

				// Only consider variations actually on sale (sale price strictly less than regular).
				if ( $reg <= 0 || $sal <= 0 || $sal >= $reg ) {
					continue;
				}

				$pct           = (int) round( ( ( $reg - $sal ) / $reg ) * 100 );
				$percentages[] = $pct;
				$on_sale_count++;

				// Track the variation with the highest percentage discount and use ITS values.
				if ( $pct > $best_pct ) {
					$best_pct     = $pct;
					$best_reg     = $reg;
					$best_sal     = $sal;
					$best_savings = $reg - $sal;
				}
			}

			if ( $best_pct > 0 ) {
				$result['discount_percent'] = $best_pct;
				$result['regular_price']    = $best_reg;
				$result['sale_price']       = $best_sal;
				$result['savings_amount']   = $best_savings;

				// Mixed when not all on-sale variations share the same percentage,
				// or when some variations aren't on sale at all.
				if ( count( array_unique( $percentages ) ) > 1 || $on_sale_count !== $total_count ) {
					$result['mixed_discounts'] = true;
				}
			}

			// If no variations are on sale, fall back to the displayable price range.
			// We leave regular_price at 0 here so the template can use $product->get_price()
			// or get_price_html() for the not-on-sale variable display path.
			$cache[ $cache_key ] = $result;
			return $result;
		}

		// Simple and external products usually expose regular/sale price directly.
		$reg = floatval( $product->get_regular_price() );
		$sal = floatval( $product->get_sale_price() );

		$result['regular_price'] = $reg;
		// Keep sale_price at 0 when the product is not on sale, rather than
		// mirroring regular_price. This makes the data array semantically clearer
		// and lets callers detect "not on sale" by checking sale_price > 0.
		$result['sale_price']    = $sal;
		$result['display_price'] = floatval( $product->get_price() );

		// Grouped products and some external/catalog products can have empty direct
		// prices. Keep the numeric values at zero and let the template fall back to
		// WooCommerce's native get_price_html() output for display.
		if ( $reg > 0 && $sal > 0 && $sal < $reg ) {
			$result['discount_percent'] = (int) round( ( ( $reg - $sal ) / $reg ) * 100 );
			$result['savings_amount']   = $reg - $sal;
		}

		$cache[ $cache_key ] = $result;
		return $result;
	}

	/**
	 * Formats a number as locale-aware currency.
	 *
	 * Uses the active WooCommerce currency symbol so the plugin works on shops
	 * outside the eurozone. Rounded to whole units for badge compactness
	 * (-EUR 25 vs -EUR 24,99).
	 *
	 * Note: an identical method exists on WCPCE_Badge_Helper. Both helpers must
	 * be standalone-usable, so each carries its own copy (see file docblock).
	 *
	 * @since 1.0.0
	 * @since 1.0.82 Moved here from the widget class (Phase 6, R2).
	 * @param float $amount The amount.
	 * @return string
	 */
	public static function format_money( float $amount ): string {
		$symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '€';
		return $symbol . number_format_i18n( $amount, 0 );
	}
}
