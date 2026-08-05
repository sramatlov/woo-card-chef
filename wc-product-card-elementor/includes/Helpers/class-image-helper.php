<?php
/**
 * Image helper.
 *
 * Stateless static methods for product card image logic: bulk attachment cache
 * priming before the render loop, dynamic sizes attribute computation from column
 * settings, above-the-fold LCP priority detection, and primary/secondary image ID
 * resolution (including hover swap guard and ACF card_hover_image fallback).
 *
 * This class was extracted from WC_Product_Card_Elementor_Widget::prime_attachment_caches()
 * and templates/card.php in v1.0.83 (Phase 6, R4). The behaviour is identical to the
 * pre-extraction code — this is pure internal restructuring so the same logic can be
 * reused by future widgets (e.g. the PDP Gallery widget, PDP Phase 1) without
 * duplicating it. The rendered product card output is unchanged.
 *
 * All methods are static and hold no state, so the class needs no constructor
 * and can be called directly: WCPCE_Image_Helper::prime_attachment_caches( $products, $settings ).
 *
 * @package WC_Product_Card_Elementor
 * @since 1.0.83
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image logic helper.
 *
 * @since 1.0.83
 */
class WCPCE_Image_Helper {

	/**
	 * Bulk-primes attachment and product meta caches before the card render loop.
	 *
	 * Without priming, wp_get_attachment_image() and wp_attachment_is() each trigger
	 * individual get_post() / meta lookups per card. On a 12-card grid with hover swap
	 * enabled that can be 24–48 individual DB queries on a cold cache. This method
	 * collapses them into two bulk queries: one for post objects, one for metadata.
	 *
	 * The product meta cache is also primed unconditionally so the per-card
	 * get_post_meta() calls in card.php (ACF fields: card_title, usp_1/2/3, badge
	 * toggles, card_hover_image) hit the object cache rather than the DB.
	 *
	 * Secondary image IDs are only collected when hover swap is enabled — otherwise
	 * the template never reads them and there is nothing to prime.
	 *
	 * @since 1.0.48
	 * @since 1.0.83 Moved here from the widget class (Phase 6, R4).
	 * @param \WC_Product[] $products The products about to be rendered.
	 * @param array         $settings Widget settings.
	 * @return void
	 */
	public static function prime_attachment_caches( array $products, array $settings ): void {
		if ( empty( $products ) ) {
			return;
		}

		$hover_enabled = 'yes' === ( $settings['show_hover_swap'] ?? '' );
		$ids           = array();

		// Bulk-prime the product meta cache. card.php calls get_post_meta()
		// for every product to read ACF fields (card_title, usp_1/2/3, badge
		// toggles, optionally card_hover_image), and this method also reads
		// card_hover_image below when hover swap is enabled. Without priming,
		// each call is an individual DB hit on a cold cache.
		$product_ids = array_map(
			static function ( $product ) {
				return (int) $product->get_id();
			},
			$products
		);
		update_meta_cache( 'post', $product_ids );

		foreach ( $products as $product ) {
			$primary = (int) $product->get_image_id();
			if ( $primary > 0 ) {
				$ids[] = $primary;
			}

			// Only collect secondary IDs if hover swap is on. Otherwise the
			// template never reads them and there's nothing to prime.
			if ( ! $hover_enabled ) {
				continue;
			}

			// ACF card_hover_image (if set) takes precedence; otherwise gallery[0].
			$product_id   = $product->get_id();
			$acf_hover_id = (int) get_post_meta( $product_id, 'card_hover_image', true );
			if ( $acf_hover_id > 0 ) {
				$ids[] = $acf_hover_id;
				continue;
			}
			$gallery = $product->get_gallery_image_ids();
			if ( ! empty( $gallery ) ) {
				$ids[] = (int) $gallery[0];
			}
		}

		$ids = array_filter( array_unique( $ids ) );
		if ( empty( $ids ) ) {
			return;
		}

		// Prime the post cache (used by wp_attachment_is and the inner
		// get_post() inside wp_get_attachment_image).
		_prime_post_caches( $ids, false, false );

		// Prime the metadata cache (used by wp_get_attachment_image to build
		// srcset/sizes attributes from _wp_attachment_metadata).
		update_meta_cache( 'post', $ids );
	}

	/**
	 * Builds the dynamic sizes attribute from column settings.
	 *
	 * Computes `round(100 / columns)` vw per breakpoint so the browser fetches an
	 * appropriately-sized srcset variant rather than over-fetching based on the
	 * hardcoded assumption of 3 desktop columns. Breakpoints match Elementor defaults
	 * (767px mobile, 1024px tablet). The final value (no media condition) is the
	 * desktop fallback.
	 *
	 * Known trade-off: the formula ignores grid gap and section padding, so the hint
	 * slightly over-specifies the image width. Accepted — the browser tolerates
	 * over-spec by choosing a marginally larger srcset variant, and adding gap
	 * correction would require either hardcoding a fixed gap or a JS measurement step.
	 * See DECISIONS_LOG and KNOWN_ISSUES.
	 *
	 * @since 1.0.78
	 * @since 1.0.83 Moved here from card.php (Phase 6, R4).
	 * @param array $settings Widget settings.
	 * @return string The sizes attribute value, e.g. "(max-width: 767px) 50vw, (max-width: 1024px) 50vw, 33vw".
	 */
	public static function build_sizes_attribute( array $settings ): string {
		$cols_desktop = max( 1, absint( $settings['columns']        ?? 3 ) );
		$cols_tablet  = max( 1, absint( $settings['columns_tablet'] ?? 2 ) );
		$cols_mobile  = max( 1, absint( $settings['columns_mobile'] ?? 2 ) );

		$vw_desktop = (int) round( 100 / $cols_desktop );
		$vw_tablet  = (int) round( 100 / $cols_tablet );
		$vw_mobile  = (int) round( 100 / $cols_mobile );

		return sprintf(
			'(max-width: 767px) %dvw, (max-width: 1024px) %dvw, %dvw',
			$vw_mobile,
			$vw_tablet,
			$vw_desktop
		);
	}

	/**
	 * Returns whether the card at the given index is above the fold.
	 *
	 * Uses the desktop column count to estimate the first visible row. Cards in the
	 * first row receive eager loading and fetchpriority=high for LCP optimisation;
	 * all others are lazy-loaded. Uses the actual configured column count rather than
	 * a hardcoded value so the hint stays accurate when editors configure 2, 4, 5, or
	 * 6 columns instead of the default 3.
	 *
	 * @since 1.0.78
	 * @since 1.0.83 Moved here from card.php (Phase 6, R4).
	 * @param int   $index    Zero-based card index within the grid.
	 * @param array $settings Widget settings.
	 * @return bool True when the card is in the first (desktop) row.
	 */
	public static function is_above_fold( int $index, array $settings ): bool {
		$cols_desktop = max( 1, absint( $settings['columns'] ?? 3 ) );
		return $index < $cols_desktop;
	}

	/**
	 * Resolves the primary and secondary image IDs for a card.
	 *
	 * Primary is always the WooCommerce featured image. Secondary is only resolved
	 * when hover swap is enabled in settings — skipping the ACF field lookup,
	 * gallery fetch, and wp_attachment_is() validation entirely when the feature is
	 * off (H8 fix, v1.0.76).
	 *
	 * Secondary image source priority:
	 * 1. ACF card_hover_image field (attachment ID, validated via wp_attachment_is()).
	 * 2. First WooCommerce gallery image as fallback.
	 *
	 * $acf_data must already be populated (via get_post_meta()) before calling this
	 * method so card_hover_image is available without an additional DB read.
	 *
	 * @since 1.0.47
	 * @since 1.0.83 Moved here from card.php (Phase 6, R4).
	 * @param \WC_Product $product  The product.
	 * @param array       $acf_data ACF meta values keyed by field name, as read by card.php.
	 * @param array       $settings Widget settings.
	 * @return array {
	 *     @type int  $primary_image_id   Featured image attachment ID (0 if none).
	 *     @type int  $secondary_image_id Hover image attachment ID (0 if none or swap disabled).
	 *     @type bool $hover_swap_enabled Whether hover swap is active for this card.
	 * }
	 */
	public static function get_image_ids( \WC_Product $product, array $acf_data, array $settings ): array {
		$result = array(
			'primary_image_id'   => (int) $product->get_image_id(),
			'secondary_image_id' => 0,
			'hover_swap_enabled' => false,
		);

		// H8: skip all secondary image resolution when hover swap is disabled.
		// prime_attachment_caches() applies the same conditional, so this keeps
		// card.php consistent with the cache-priming layer.
		if ( 'yes' !== ( $settings['show_hover_swap'] ?? '' ) ) {
			return $result;
		}

		$gallery_ids = $product->get_gallery_image_ids();

		// C3: card_hover_image ACF field takes precedence over the first gallery image.
		// The field stores an attachment ID (return_format = 'id'). We cast to int and
		// validate with wp_attachment_is() to guard against stale IDs from deleted media.
		$acf_hover_id = ! empty( $acf_data['card_hover_image'] ) ? absint( $acf_data['card_hover_image'] ) : 0;
		if ( $acf_hover_id > 0 && wp_attachment_is( 'image', $acf_hover_id ) ) {
			$result['secondary_image_id'] = $acf_hover_id;
		} else {
			$result['secondary_image_id'] = ! empty( $gallery_ids ) ? (int) $gallery_ids[0] : 0;
		}

		$result['hover_swap_enabled'] = $result['secondary_image_id'] > 0;

		return $result;
	}
}
