<?php
/**
 * Badge helper.
 *
 * Stateless static methods for all badge logic: discount badge text and money
 * formatting, discount-badge visibility, the ACF custom-badge flags (Nieuw,
 * PFAS-vrij, Niet meer leverbaar), the top-left badge priority rule, the
 * niet-leverbaar suppression of competing badges, badge label defaults, and the
 * accessible badge aria-label.
 *
 * This class was extracted from WC_Product_Card_Elementor_Widget and
 * templates/card.php in v1.0.80 (Phase 6, R1). The behaviour is identical to
 * the pre-extraction code — this is pure internal restructuring so the same
 * logic can be reused by future widgets (e.g. the PDP Gallery widget) without
 * duplicating it. The rendered product card output is unchanged.
 *
 * All methods are static and hold no state, so the class needs no constructor
 * and can be called directly: WCPCE_Badge_Helper::format_badge_text( ... ).
 *
 * @package WC_Product_Card_Elementor
 * @since 1.0.80
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Badge logic helper.
 *
 * @since 1.0.80
 */
class WCPCE_Badge_Helper {

	/**
	 * Decides whether the discount badge shows and produces its text.
	 *
	 * Takes the already-computed price data array (regular_price, sale_price,
	 * discount_percent, savings_amount, is_on_sale, is_variable) plus the widget
	 * settings, and returns the two badge keys the card data array needs:
	 * show_badge (bool) and badge_text (string).
	 *
	 * The caller (compute_card_data()) owns the price computation; this method
	 * owns only the badge decision and formatting. The discount-badge gate is:
	 * the show_badge setting is on, the product is genuinely on sale (WC flag
	 * plus a verified positive discount), and the discount meets the configured
	 * threshold.
	 *
	 * The $mixed_discounts flag drives the variable-product "Tot " prefix and is
	 * passed in because it is a by-product of the price computation, not derivable
	 * from the price data array alone.
	 *
	 * @since 1.0.80
	 * @param array $data            Price/card data with at least: is_on_sale (bool),
	 *                               is_variable (bool), regular_price (float),
	 *                               discount_percent (int), savings_amount (float).
	 * @param array $settings        Widget settings.
	 * @param bool  $mixed_discounts Whether variable-product discounts are mixed.
	 * @return array { show_badge: bool, badge_text: string }
	 */
	public static function compute_badge_data( array $data, array $settings, bool $mixed_discounts = false ): array {
		$result = array(
			'show_badge' => false,
			'badge_text' => '',
		);

		if ( 'yes' !== ( $settings['show_badge'] ?? 'yes' ) ) {
			return $result;
		}
		if ( empty( $data['is_on_sale'] ) || ( $data['discount_percent'] ?? 0 ) <= 0 ) {
			return $result;
		}

		$threshold = isset( $settings['badge_threshold'] ) ? absint( $settings['badge_threshold'] ) : 0;
		if ( ( $data['discount_percent'] ?? 0 ) < $threshold ) {
			return $result;
		}

		$result['show_badge'] = true;
		$result['badge_text'] = self::format_badge_text(
			(int) ( $data['discount_percent'] ?? 0 ),
			(float) ( $data['savings_amount'] ?? 0.0 ),
			(float) ( $data['regular_price'] ?? 0.0 ),
			$settings['badge_format'] ?? 'smart',
			! empty( $data['is_variable'] ) && $mixed_discounts && 'yes' === ( $settings['badge_variable_prefix'] ?? 'yes' )
		);

		return $result;
	}

	/**
	 * Formats the badge text per the chosen format and Rule of 100 logic.
	 *
	 * @since 1.0.0
	 * @since 1.0.80 Moved here from the widget class.
	 * @param int    $percent          Discount percentage.
	 * @param float  $amount           Savings amount in currency.
	 * @param float  $regular_price    Regular price (used by Smart format).
	 * @param string $format           One of percent, amount, smart.
	 * @param bool   $with_tot_prefix  Prefix with "Tot " for variable products with mixed discounts.
	 * @return string
	 */
	public static function format_badge_text( int $percent, float $amount, float $regular_price, string $format, bool $with_tot_prefix ): string {
		// Use __() not esc_html__() here: the badge text is escaped at output via
		// esc_html() in card.php. Pre-escaping with esc_html__() would double-encode
		// any special characters if the string is ever changed (CONVENTIONS).
		$prefix = $with_tot_prefix ? __( 'Tot ', 'woo-card-chef' ) : '';

		switch ( $format ) {
			case 'percent':
				return $prefix . '-' . $percent . '%';
			case 'amount':
				return $prefix . '-' . self::format_money( $amount );
			case 'smart':
			default:
				// Rule of 100: under €100 percentage feels bigger; at or above, the absolute amount feels bigger.
				if ( $regular_price < 100 ) {
					return $prefix . '-' . $percent . '%';
				}
				return $prefix . '-' . self::format_money( $amount );
		}
	}

	/**
	 * Formats a number as locale-aware currency for the badge.
	 *
	 * Uses the active WooCommerce currency symbol so the plugin works on shops
	 * outside the eurozone. Rounded to whole units for badge compactness
	 * (-EUR 25 vs -EUR 24,99).
	 *
	 * @since 1.0.0
	 * @since 1.0.80 Moved here from the widget class.
	 * @param float $amount The amount.
	 * @return string
	 */
	public static function format_money( float $amount ): string {
		$symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '€';
		return $symbol . number_format_i18n( $amount, 0 );
	}

	/**
	 * Resolves the three ACF custom-badge flags.
	 *
	 * Each badge activates only when (a) the widget toggle is on AND (b) the ACF
	 * field on the product is enabled. ACF true_false fields store '1' / '0' / ''
	 * in postmeta; empty() covers all the "off" states.
	 *
	 * @since 1.0.80
	 * @param array $settings Widget settings.
	 * @param array $acf_data ACF meta values keyed by field name (badge_nieuw,
	 *                        badge_pfas_vrij, badge_niet_leverbaar).
	 * @return array { nieuw: bool, pfas: bool, niet_leverbaar: bool }
	 */
	public static function get_acf_badge_flags( array $settings, array $acf_data ): array {
		return array(
			'nieuw'          => 'yes' === ( $settings['show_badge_nieuw'] ?? 'yes' ) && ! empty( $acf_data['badge_nieuw'] ),
			'pfas'           => 'yes' === ( $settings['show_badge_pfas'] ?? 'yes' ) && ! empty( $acf_data['badge_pfas_vrij'] ),
			'niet_leverbaar' => 'yes' === ( $settings['show_badge_niet_leverbaar'] ?? 'yes' ) && ! empty( $acf_data['badge_niet_leverbaar'] ),
		);
	}

	/**
	 * Applies top-left badge priority and niet-leverbaar suppression.
	 *
	 * Two rules, in this order:
	 *
	 * 1. Top-left priority: the discount badge (a direct commercial signal) wins
	 *    over the Nieuw badge. The Nieuw badge only shows when no discount badge
	 *    is showing.
	 *
	 * 2. Niet meer leverbaar suppression: a permanently-unavailable product gets
	 *    the out-of-stock visual treatment (dimmed/grayscale), its tijdelijk-
	 *    uitverkocht stock label is suppressed, and BOTH the discount badge and
	 *    the Nieuw badge are forced off. Showing "20% korting" or "Nieuw" on a
	 *    discontinued product is contradictory and undermines the message.
	 *
	 * $card_classes and $show_stock_label are passed by reference because the
	 * niet-leverbaar treatment mutates both (adds the OOS class, hides the label)
	 * — exactly as the inline card.php code did before extraction.
	 *
	 * @since 1.0.80
	 * @param bool  $show_badge       Discount badge currently slated to show.
	 * @param array $flags            Output of get_acf_badge_flags().
	 * @param array $card_classes     Card CSS classes, mutated in place.
	 * @param bool  $show_stock_label Stock label currently slated to show, mutated in place.
	 * @return array { show_badge: bool, show_nieuw: bool, show_pfas: bool, show_niet_leverbaar: bool }
	 */
	public static function apply_badge_priority( bool $show_badge, array $flags, array &$card_classes, bool &$show_stock_label ): array {
		$show_pfas           = ! empty( $flags['pfas'] );
		$show_niet_leverbaar = ! empty( $flags['niet_leverbaar'] );

		// Badge priority for linksboven (top-left):
		// 1. Korting badge (highest — direct commercial signal)
		// 2. Nieuw badge (only when no discount badge is showing)
		$show_nieuw = ! empty( $flags['nieuw'] ) && ! $show_badge;

		// Niet meer leverbaar gets the same visual treatment as tijdelijk uitverkocht
		// (dimmed image, grayscale). If a product is both niet_leverbaar AND tijdelijk
		// uitverkocht in WooCommerce, niet_leverbaar wins — it is the stronger message.
		//
		// Niet meer leverbaar also suppresses the discount badge and the Nieuw badge.
		// A permanently unavailable product showing a discount or "new" indicator is
		// confusing and undermines the niet_leverbaar message itself (D9 fix).
		if ( $show_niet_leverbaar ) {
			// Force OOS visual treatment regardless of the tijdelijk uitverkocht toggle.
			if ( ! in_array( 'wc-card--out-of-stock', $card_classes, true ) ) {
				$card_classes[] = 'wc-card--out-of-stock';
			}
			// Suppress all overlay indicators — niet_leverbaar overlay takes over.
			$show_stock_label = false;
			$show_badge       = false;
			$show_nieuw       = false;
		}

		return array(
			'show_badge'          => $show_badge,
			'show_nieuw'          => $show_nieuw,
			'show_pfas'           => $show_pfas,
			'show_niet_leverbaar' => $show_niet_leverbaar,
		);
	}

	/**
	 * Returns the three custom-badge label strings with defaults.
	 *
	 * Defaults use __() not esc_html__(): these values are escaped at output via
	 * esc_html()/esc_attr() in card.php. Pre-escaping here would double-encode
	 * special characters (CONVENTIONS).
	 *
	 * @since 1.0.80
	 * @param array $settings Widget settings.
	 * @return array { nieuw: string, pfas: string, niet_leverbaar: string }
	 */
	public static function get_badge_labels( array $settings ): array {
		return array(
			'nieuw'          => isset( $settings['badge_nieuw_label'] ) ? $settings['badge_nieuw_label'] : __( 'Nieuw', 'woo-card-chef' ),
			'pfas'           => isset( $settings['badge_pfas_label'] ) ? $settings['badge_pfas_label'] : __( 'PFAS-vrij', 'woo-card-chef' ),
			'niet_leverbaar' => isset( $settings['badge_niet_leverbaar_label'] ) ? $settings['badge_niet_leverbaar_label'] : __( 'Niet meer leverbaar', 'woo-card-chef' ),
		);
	}

	/**
	 * Builds the accessible aria-label for the discount badge.
	 *
	 * Screen readers should hear "12% korting" not "-12%" (which reads as
	 * "negative 12 percent"), and "€45 korting" not "-€45". For variable products
	 * the badge text may be "Tot -20%" — the regex strips the dash after the
	 * optional "Tot " prefix so it reads "Tot 20% korting".
	 *
	 * Returns an empty string when the badge is not showing or has no text, which
	 * is how card.php decides whether to output the aria-label at all.
	 *
	 * @since 1.0.80
	 * @param bool   $show_badge Whether the discount badge is showing.
	 * @param string $badge_text The badge text (e.g. "-20%", "Tot -€45").
	 * @return string
	 */
	public static function get_badge_aria_label( bool $show_badge, string $badge_text ): string {
		if ( ! $show_badge || '' === $badge_text ) {
			return '';
		}

		// Strip the leading minus/dash whether it appears at the start or after "Tot ".
		$badge_readable = preg_replace( '/^(Tot\s+)?-/', '$1', $badge_text );
		$badge_readable = trim( $badge_readable );

		// Use __() not esc_html__() here because esc_attr() will handle escaping at output.
		return $badge_readable . ' ' . __( 'korting', 'woo-card-chef' );
	}
}
