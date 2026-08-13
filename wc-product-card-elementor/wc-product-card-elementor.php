<?php
/**
 * Plugin Name:       Woo Card Chef
 * Plugin URI:        https://vaneekerenindustries.nl
 * Description:       Serving clean, customizable WooCommerce product cards and PDP widgets in Elementor, with ACF-powered USPs, media, prices, badges, delivery status and flexible product grids.
 * Version:           2.6.9
 * Requires at least: 6.0
 * Tested up to:      6.7
 * Requires PHP:      7.4
 * Author:            S15 Webdesign
 * Author URI:        https://vaneekerenindustries.nl
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-card-chef
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce, elementor
 * Elementor tested up to: 4.1.0
 * Elementor Pro tested up to: 4.0.4
 * WC requires at least: 6.0
 * WC tested up to: 10.7
 *
 * @package WC_Product_Card_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'WCPCE_VERSION', '2.6.9' );
define( 'WCPCE_PLUGIN_FILE', __FILE__ );
define( 'WCPCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCPCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCPCE_MIN_ELEMENTOR_VERSION', '3.5.0' );
define( 'WCPCE_MIN_PHP_VERSION', '7.4' );

/**
 * Declares WooCommerce HPOS (High Performance Order Storage) compatibility.
 *
 * This plugin does not interact with orders directly, so it is fully compatible
 * with both the legacy posts-based storage and the new custom order tables.
 * Declaring this explicitly prevents WooCommerce from showing a compatibility
 * warning in the admin.
 *
 * @since 1.0.14
 * @return void
 */
function wcpce_declare_woocommerce_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
	}
}
add_action( 'before_woocommerce_init', 'wcpce_declare_woocommerce_compatibility' );

/**
 * Bootstraps the plugin after all plugins are loaded.
 *
 * Runs the dependency checks that are safe at plugins_loaded, then waits for
 * Elementor's own elementor/loaded hook before registering the widget. This
 * avoids load-order issues where did_action( 'elementor/loaded' ) is checked
 * too early and the widget never appears in the editor.
 *
 * @since 1.0.2
 * @return void
 */
function wcpce_bootstrap() {
	// PHP version check.
	if ( version_compare( PHP_VERSION, WCPCE_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'wcpce_admin_notice_php_version' );
		return;
	}

	// WooCommerce active check.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wcpce_admin_notice_missing_woocommerce' );
		return;
	}

	// If Elementor has already loaded, initialize immediately. Otherwise wait for
	// Elementor's own loaded hook. This supports different plugin load orders.
	if ( did_action( 'elementor/loaded' ) ) {
		wcpce_init_after_elementor_loaded();
	} else {
		add_action( 'elementor/loaded', 'wcpce_init_after_elementor_loaded' );
		add_action( 'admin_notices', 'wcpce_admin_notice_missing_elementor_if_still_missing' );
	}
}
add_action( 'plugins_loaded', 'wcpce_bootstrap', 20 );

/**
 * Initializes the plugin once Elementor is available.
 *
 * @since 1.0.2
 * @return void
 */
function wcpce_init_after_elementor_loaded() {
	// Elementor version check.
	if ( ! defined( 'ELEMENTOR_VERSION' ) || ! version_compare( ELEMENTOR_VERSION, WCPCE_MIN_ELEMENTOR_VERSION, '>=' ) ) {
		add_action( 'admin_notices', 'wcpce_admin_notice_elementor_version' );
		return;
	}

	// ACF active check. ACF is optional. Free and Pro are both detected through
	// the shared acf_add_local_field_group() function instead of a plugin slug.
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		add_action( 'admin_notices', 'wcpce_admin_notice_missing_acf' );
	}

	require_once WCPCE_PLUGIN_DIR . 'includes/class-plugin.php';
	WC_Product_Card_Elementor_Plugin::instance();
}

/**
 * Shows the Elementor missing notice only if Elementor never fired its hook.
 *
 * @since 1.0.2
 * @return void
 */
function wcpce_admin_notice_missing_elementor_if_still_missing() {
	if ( ! did_action( 'elementor/loaded' ) ) {
		wcpce_admin_notice_missing_elementor();
	}
}

/**
 * Admin notice for outdated PHP.
 *
 * @since 1.0.0
 * @return void
 */
function wcpce_admin_notice_php_version() {
	$message = sprintf(
		/* translators: 1: required PHP version, 2: current PHP version */
		esc_html__( 'Woo Card Chef requires PHP version %1$s or higher. You are running %2$s.', 'woo-card-chef' ),
		esc_html( WCPCE_MIN_PHP_VERSION ),
		esc_html( PHP_VERSION )
	);
	echo '<div class="notice notice-error"><p>' . $message . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Admin notice when Elementor is not installed or active.
 *
 * @since 1.0.0
 * @return void
 */
function wcpce_admin_notice_missing_elementor() {
	$message = esc_html__( 'Woo Card Chef requires Elementor to be installed and active.', 'woo-card-chef' );
	echo '<div class="notice notice-warning"><p>' . $message . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Admin notice when Elementor version is too low.
 *
 * @since 1.0.0
 * @return void
 */
function wcpce_admin_notice_elementor_version() {
	$message = sprintf(
		/* translators: %s: required Elementor version */
		esc_html__( 'Woo Card Chef requires Elementor version %s or higher.', 'woo-card-chef' ),
		esc_html( WCPCE_MIN_ELEMENTOR_VERSION )
	);
	echo '<div class="notice notice-warning"><p>' . $message . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Admin notice when WooCommerce is not installed or active.
 *
 * @since 1.0.0
 * @return void
 */
function wcpce_admin_notice_missing_woocommerce() {
	$message = esc_html__( 'Woo Card Chef requires WooCommerce to be installed and active.', 'woo-card-chef' );
	echo '<div class="notice notice-warning"><p>' . $message . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Admin notice when ACF is not installed or active.
 *
 * @since 1.0.0
 * @return void
 */
function wcpce_admin_notice_missing_acf() {
	$message = esc_html__( 'Woo Card Chef works best with Advanced Custom Fields or ACF Pro installed. The USP block on product cards will be empty until ACF is active and the USP fields are filled in.', 'woo-card-chef' );
	echo '<div class="notice notice-info"><p>' . $message . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Load plugin text domain for translations.
 *
 * @since 1.0.0
 * @return void
 */
function wcpce_load_textdomain() {
	load_plugin_textdomain(
		'woo-card-chef',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'wcpce_load_textdomain' );
