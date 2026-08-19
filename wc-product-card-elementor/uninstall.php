<?php
/**
 * Uninstall handler.
 *
 * Runs when the plugin is deleted (not just deactivated).
 *
 * Cleanup scope:
 * - wcpce_query_cache_keys option (introduced in 1.0.46 for transient cache tracking)
 * - All wcpce_q_* transients listed in that option
 *
 * Per-product ACF postmeta (card_title, usp_1/2/3, pdp_usps, badge_*, card_hover_image)
 * and reusable wcpce_product_label terms/relationships/term meta (including
 * visibility schedules and PDP explanations) are intentionally LEFT in place because:
 * - It may be valuable content the user wants to preserve.
 * - Re-installing the plugin restores access to it with no data loss.
 * - Wholesale postmeta deletion is risky and slow on large stores.
 *
 * If a user wants to fully purge that postmeta, they can do so manually
 * via WP-CLI or a one-off script.
 *
 * @package WC_Product_Card_Elementor
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Flush all manual-query transients we created and remove the tracking option.
$known_keys = get_option( 'wcpce_query_cache_keys', array() );
if ( ! empty( $known_keys ) && is_array( $known_keys ) ) {
	foreach ( $known_keys as $key ) {
		delete_transient( $key );
	}
}
delete_option( 'wcpce_query_cache_keys' );
