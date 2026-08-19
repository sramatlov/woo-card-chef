<?php
/**
 * Main plugin class.
 *
 * @package WC_Product_Card_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton bootstrap class for the plugin.
 *
 * Wires up Elementor widget registration, asset enqueueing, and ACF field
 * registration. All hooks are added once, the first time instance() is called.
 *
 * @since 1.0.0
 */
final class WC_Product_Card_Elementor_Plugin {

	/**
	 * Single instance of the plugin.
	 *
	 * @var WC_Product_Card_Elementor_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Returns the singleton instance, creating it on first call.
	 *
	 * @since 1.0.0
	 * @return WC_Product_Card_Elementor_Plugin
	 */
	public static function instance(): WC_Product_Card_Elementor_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Sets up plugin hooks. Private constructor enforces the singleton pattern.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Register reusable taxonomy-backed product labels. The class owns both
		// the product-editor management UI and the frontend label data contract.
		require_once WCPCE_PLUGIN_DIR . 'includes/class-product-labels.php';
		WCPCE_Product_Labels::register();
		require_once WCPCE_PLUGIN_DIR . 'includes/Traits/trait-custom-label-controls.php';

		// Load shared helper classes. These are stateless static utilities used by
		// both widget render paths and card/gallery templates. Loaded unconditionally
		// at bootstrap so all consumers can rely on them being available.
		require_once WCPCE_PLUGIN_DIR . 'includes/Helpers/class-badge-helper.php'; // Phase 6, R1.
		require_once WCPCE_PLUGIN_DIR . 'includes/Helpers/class-stock-helper.php'; // Phase 6, R3.
		require_once WCPCE_PLUGIN_DIR . 'includes/Helpers/class-price-helper.php'; // Phase 6, R2.
		require_once WCPCE_PLUGIN_DIR . 'includes/Helpers/class-image-helper.php'; // Phase 6, R4.
		require_once WCPCE_PLUGIN_DIR . 'includes/Helpers/class-acf-helper.php';   // Phase 6, R5.
		require_once WCPCE_PLUGIN_DIR . 'includes/Helpers/class-card-renderer.php';

		// Register ACF fields. We always require the class file and hook on acf/init,
		// regardless of whether ACF is currently loaded. acf/init only fires when ACF
		// loads, so this is self-gating and load-order independent.
		require_once WCPCE_PLUGIN_DIR . 'includes/class-acf-fields.php';
		add_action( 'acf/init', array( 'WC_Product_Card_Elementor_ACF_Fields', 'register' ) );

		// Register custom Elementor widget category.
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_widget_category' ) );

		// Register all Elementor widgets.
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		// Register frontend stylesheets and scripts via the centralised assets class
		// so Elementor's dependency system can enqueue them per widget. Assets are
		// registered here (not enqueued) — get_style_depends() / get_script_depends()
		// on each widget tell Elementor which handles to actually load.
		require_once WCPCE_PLUGIN_DIR . 'includes/class-assets.php';
		add_action( 'wp_enqueue_scripts', array( 'WCPCE_Assets', 'register' ) );

		// Register wcpce_paged as a recognised WP query var so it survives
		// WordPress URL parsing. Used by manual-mode pagination to avoid
		// conflicts with the main WooCommerce archive loop's paged query var.
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );

		// PF1: Flush manual-query transient cache whenever a product is saved.
		// Fires on publish, update, and unpublish — covers all write operations
		// that could change what appears in a cached product grid.
		add_action( 'save_post_product', array( $this, 'flush_query_cache' ) );

		// Do not hook into loop_shop_per_page. WooCommerce archive product counts
		// must remain controlled by WooCommerce, the theme, Customizer settings,
		// or dedicated filtering/catalog plugins.
	}

	/**
	 * Registers a custom widget category in the Elementor panel.
	 *
	 * Putting our widgets in their own category makes them findable in the panel
	 * sidebar and signals that they are custom builds, not stock Elementor widgets.
	 *
	 * @since 1.0.0
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_widget_category( $elements_manager ): void {
		$elements_manager->add_category(
			'custom-woocommerce',
			array(
				'title' => esc_html__( 'Woo Card Chef', 'woo-card-chef' ),
				'icon'  => 'eicon-woocommerce',
			)
		);
	}

	/**
	 * Registers all Woo Card Chef widgets with Elementor.
	 *
	 * Widget files live in includes/Widgets/ since v2.0.0 (Phase 6, R6).
	 *
	 * @since 2.0.0 Renamed from register_widget(); now registers multiple widgets.
	 * @since 2.1.0 Added the Price & Promo Block widget (PDP Phase 2).
	 * @since 2.2.0 Added the Product USP / Benefits widget (PDP Phase 3).
	 * @since 2.3.0 Added the Product Delivery & Availability widget (PDP Phase 4).
	 * @since 2.4.0 Added the Product Accordion widget (PDP Phase 6).
	 * @since 2.5.0 Added the Product Upsells widget (PDP Phase 7).
	 * @since 2.6.0 Added the Product Cross-sells / Related widget (PDP Phase 8).
	 * @since 2.7.1 Added the Product Label Details widget.
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ): void {
		require_once WCPCE_PLUGIN_DIR . 'includes/Widgets/class-product-card-widget.php';
		$widgets_manager->register( new WC_Product_Card_Elementor_Widget() );

		require_once WCPCE_PLUGIN_DIR . 'includes/Widgets/class-product-gallery-widget.php';
		$widgets_manager->register( new WCPCE_Product_Gallery_Widget() );

		require_once WCPCE_PLUGIN_DIR . 'includes/Widgets/class-product-price-widget.php';
		$widgets_manager->register( new WCPCE_Product_Price_Widget() );

		require_once WCPCE_PLUGIN_DIR . 'includes/Widgets/class-product-usps-widget.php';
		$widgets_manager->register( new WCPCE_Product_USPs_Widget() );

		require_once WCPCE_PLUGIN_DIR . 'includes/Widgets/class-product-delivery-widget.php';
		$widgets_manager->register( new WCPCE_Product_Delivery_Widget() );

		require_once WCPCE_PLUGIN_DIR . 'includes/Widgets/class-product-accordion-widget.php';
		$widgets_manager->register( new WCPCE_Product_Accordion_Widget() );

		require_once WCPCE_PLUGIN_DIR . 'includes/Widgets/class-product-upsells-widget.php';
		$widgets_manager->register( new WCPCE_Product_Upsells_Widget() );

		require_once WCPCE_PLUGIN_DIR . 'includes/Widgets/class-product-related-widget.php';
		$widgets_manager->register( new WCPCE_Product_Related_Widget() );

		require_once WCPCE_PLUGIN_DIR . 'includes/Widgets/class-product-label-details-widget.php';
		$widgets_manager->register( new WCPCE_Product_Label_Details_Widget() );
	}

	/**
	 * Registers plugin-specific query vars with WordPress.
	 *
	 * wcpce_paged is used by manual-mode pagination instead of the native
	 * paged var to prevent interference with the main WooCommerce archive loop.
	 *
	 * @since 1.0.36
	 * @param array $vars Existing query vars.
	 * @return array
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = 'wcpce_paged';
		return $vars;
	}

	/**
	 * Flushes all manual-query transient caches.
	 *
	 * Called on save_post_product so any product edit invalidates cached grids
	 * immediately. Keys are tracked in the wcpce_query_cache_keys option which
	 * run_manual_query() maintains. After flushing, the option is cleared.
	 *
	 * @since 1.0.46
	 * @return void
	 */
	public function flush_query_cache(): void {
		$known_keys = get_option( 'wcpce_query_cache_keys', array() );
		if ( ! empty( $known_keys ) ) {
			foreach ( $known_keys as $key ) {
				delete_transient( $key );
			}
			delete_option( 'wcpce_query_cache_keys' );
		}
	}
}
