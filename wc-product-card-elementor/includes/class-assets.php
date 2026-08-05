<?php
/**
 * Centralised asset registration for all Woo Card Chef widgets.
 *
 * Registers (but never enqueues) every stylesheet and script used by the
 * plugin's widgets. Elementor reads each widget's get_style_depends() and
 * get_script_depends() and enqueues only the handles that are actually
 * present on the page, both on the frontend and in the editor preview iframe.
 *
 * Adding a new widget's assets here keeps class-plugin.php free of asset
 * details and gives us one authoritative place to update version strings,
 * dependencies, and file paths.
 *
 * @package WC_Product_Card_Elementor
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers plugin assets on wp_enqueue_scripts.
 *
 * All methods are static. No constructor, no state.
 *
 * @since 2.0.0
 */
final class WCPCE_Assets {

	/**
	 * Registers all plugin stylesheets and scripts.
	 *
	 * Hooked to wp_enqueue_scripts in class-plugin.php. Registration only —
	 * Elementor's dependency system handles actual enqueueing per widget.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function register(): void {
		self::register_product_card_assets();
		self::register_product_gallery_assets();
		self::register_product_price_assets();
		self::register_product_usps_assets();
		self::register_product_delivery_assets();
		self::register_product_accordion_assets();
		self::register_product_upsells_assets();
		self::register_product_related_assets();
	}

	/**
	 * Registers assets for the Product Card widget.
	 *
	 * CSS handle: wc-product-card-elementor
	 * No JS — the product card widget is zero-JS by design since v1.0.54.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private static function register_product_card_assets(): void {
		wp_register_style(
			'wc-product-card-elementor',
			WCPCE_PLUGIN_URL . 'assets/css/product-card.css',
			array(),
			WCPCE_VERSION
		);
	}

	/**
	 * Registers assets for the Product Gallery widget.
	 *
	 * CSS handle: wcpce-product-gallery
	 * JS handle:  wcpce-product-gallery
	 *
	 * The JS is loaded via get_script_depends() with a static array — never
	 * conditional on widget settings. Calling get_settings_for_display() inside
	 * get_script_depends() causes a fatal (settings are null at that lifecycle
	 * stage). See DECISIONS_LOG and the v1.0.15 hotfix in KNOWN_ISSUES.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private static function register_product_gallery_assets(): void {
		wp_register_style(
			'wcpce-product-gallery',
			WCPCE_PLUGIN_URL . 'assets/css/product-gallery.css',
			array(),
			WCPCE_VERSION
		);

		// 'defer' keeps the script off the critical render path. The gallery
		// initialises on DOMContentLoaded / Elementor frontend init, so deferring
		// has no functional downside. Falls back to in-footer on WP < 6.3.
		wp_register_script(
			'wcpce-product-gallery',
			WCPCE_PLUGIN_URL . 'assets/js/product-gallery.js',
			array(),
			WCPCE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Registers assets for the Price & Promo Block widget.
	 *
	 * CSS handle: wcpce-product-price
	 * No JS — the Price & Promo Block is server-rendered (progressive enhancement,
	 * see DECISIONS_LOG).
	 *
	 * @since 2.1.0
	 * @return void
	 */
	private static function register_product_price_assets(): void {
		wp_register_style(
			'wcpce-product-price',
			WCPCE_PLUGIN_URL . 'assets/css/product-price.css',
			array(),
			WCPCE_VERSION
		);
	}

	/**
	 * Registers assets for the Product USP / Benefits widget.
	 *
	 * CSS handle: wcpce-product-usps
	 * No JS - the Product USP / Benefits widget is server-rendered.
	 *
	 * @since 2.2.0
	 * @return void
	 */
	private static function register_product_usps_assets(): void {
		wp_register_style(
			'wcpce-product-usps',
			WCPCE_PLUGIN_URL . 'assets/css/product-usps.css',
			array(),
			WCPCE_VERSION
		);
	}

	/**
	 * Registers assets for the Product Accordion widget.
	 *
	 * CSS handle: wcpce-product-accordion
	 * JS handle:  wcpce-product-accordion
	 *
	 * JS is required for accordion toggle, FAQ inner accordion, Lipscore count
	 * sync and hash-jump navigation. get_script_depends() returns a static array
	 * per the lifecycle rule (see DECISIONS_LOG).
	 *
	 * @since 2.4.0
	 * @return void
	 */
	private static function register_product_accordion_assets(): void {
		wp_register_style(
			'wcpce-product-accordion',
			WCPCE_PLUGIN_URL . 'assets/css/product-accordion.css',
			array(),
			WCPCE_VERSION
		);

		wp_register_script(
			'wcpce-product-accordion',
			WCPCE_PLUGIN_URL . 'assets/js/product-accordion.js',
			array(),
			WCPCE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Registers assets for the Product Delivery & Availability widget.
	 *
	 * CSS handle: wcpce-product-delivery
	 * No JS - the Delivery & Availability widget is server-rendered.
	 *
	 * @since 2.3.0
	 * @return void
	 */
	private static function register_product_delivery_assets(): void {
		wp_register_style(
			'wcpce-product-delivery',
			WCPCE_PLUGIN_URL . 'assets/css/product-delivery.css',
			array(),
			WCPCE_VERSION
		);
	}

	/**
	 * Registers assets for the Product Upsells widget.
	 *
	 * CSS handle: wcpce-product-upsells
	 * No widget-specific JS. The card template may still declare WooCommerce's
	 * wc-add-to-cart script through the widget dependency list.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	private static function register_product_upsells_assets(): void {
		wp_register_style(
			'wcpce-product-upsells',
			WCPCE_PLUGIN_URL . 'assets/css/product-upsells.css',
			array( 'wc-product-card-elementor' ),
			WCPCE_VERSION
		);
	}

	/**
	 * Registers assets for the Product Cross-sells / Related widget.
	 *
	 * CSS handle: wcpce-product-related
	 * No widget-specific JS. The card template may still declare WooCommerce's
	 * wc-add-to-cart script through the widget dependency list.
	 *
	 * @since 2.6.0
	 * @return void
	 */
	private static function register_product_related_assets(): void {
		wp_register_style(
			'wcpce-product-related',
			WCPCE_PLUGIN_URL . 'assets/css/product-related.css',
			array( 'wc-product-card-elementor' ),
			WCPCE_VERSION
		);
	}
}
