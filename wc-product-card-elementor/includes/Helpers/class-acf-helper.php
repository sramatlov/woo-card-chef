<?php
/**
 * ACF helper.
 *
 * Stateless static methods for reading ACF product card fields from postmeta.
 * Encapsulates the field key list and the single get_post_meta() read pattern
 * so both the product card template and future widgets (e.g. PDP Gallery,
 * PDP Spare Parts) can access the same fields without duplicating the field names
 * or the meta-reading logic.
 *
 * This class was extracted from templates/card.php in v1.0.84 (Phase 6, R5).
 * The behaviour is identical to the pre-extraction code — this is pure internal
 * restructuring. The rendered product card output is unchanged.
 *
 * Why direct get_post_meta() instead of get_fields():
 * get_fields() loads and formats every ACF field group registered on the product,
 * regardless of how many we need. On products with many field groups (specs,
 * marketing, etc.) this is a measurable overhead per card. All eight fields used
 * here are plain strings or integers in postmeta; ACF formatting adds nothing.
 * Since these fields are exclusively owned by this plugin and have no formatting
 * hooks, direct meta access is safe and significantly faster. See DECISIONS_LOG.
 *
 * All methods are static and hold no state, so the class needs no constructor
 * and can be called directly: WCPCE_ACF_Helper::get_card_data( $product_id ).
 *
 * @package WC_Product_Card_Elementor
 * @since 1.0.84
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ACF field reading helper.
 *
 * @since 1.0.84
 */
class WCPCE_ACF_Helper {

	/**
	 * Returns all ACF field keys used by the product card.
	 *
	 * Centralises the field name list so future widgets can request a subset
	 * without hardcoding the names in multiple places. All keys correspond to
	 * ACF fields registered programmatically by class-acf-fields.php.
	 *
	 * Field types and their postmeta storage format:
	 * - card_title           text          → string or ''
	 * - usp_1 / usp_2 / usp_3  text       → string or ''
	 * - badge_nieuw          true_false    → '1' (on) or '0' / '' (off)
	 * - badge_pfas_vrij      true_false    → '1' (on) or '0' / '' (off)
	 * - badge_niet_leverbaar true_false    → '1' (on) or '0' / '' (off)
	 * - card_hover_image     image (id)    → attachment ID as string or ''
	 *
	 * All values that drive logic in card.php use empty()/string truthiness,
	 * which is the same comparison ACF's own get_field() output produces.
	 *
	 * @since 1.0.84
	 * @return string[]
	 */
	public static function get_field_keys(): array {
		return array(
			'card_title',
			'usp_1',
			'usp_2',
			'usp_3',
			'badge_nieuw',
			'badge_pfas_vrij',
			'badge_niet_leverbaar',
			'card_hover_image',
		);
	}

	/**
	 * Reads all product card ACF fields for a product in a single meta call.
	 *
	 * Returns an array keyed by field name. Every key is always present; missing
	 * or unset fields default to an empty string so callers can use empty() checks
	 * without isset() guards.
	 *
	 * Must be called after prime_attachment_caches() (via WCPCE_Image_Helper) has
	 * run, because that method calls update_meta_cache() for all product IDs,
	 * turning this get_post_meta() call into a pure object-cache read with no
	 * additional DB query.
	 *
	 * @since 1.0.44
	 * @since 1.0.84 Moved here from card.php (Phase 6, R5).
	 * @param int $product_id The WooCommerce product ID.
	 * @return array<string, string> ACF field values keyed by field name.
	 */
	public static function get_card_data( int $product_id ): array {
		$keys = self::get_field_keys();
		$data = array_fill_keys( $keys, '' );

		if ( $product_id <= 0 ) {
			return $data;
		}

		$all_meta = get_post_meta( $product_id );
		foreach ( $keys as $key ) {
			$data[ $key ] = isset( $all_meta[ $key ][0] ) ? $all_meta[ $key ][0] : '';
		}

		return $data;
	}
}
