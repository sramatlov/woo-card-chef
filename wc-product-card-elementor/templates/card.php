<?php
/**
 * Product card template partial.
 *
 * Receives these variables from the widget's render_card() method:
 * - $product   WC_Product instance
 * - $settings  array of widget settings
 * - $card      array of pre-computed card data (discount, badge text, price_html, index, etc.)
 * - $widget_id string Elementor widget ID, used to build unique title IDs for aria-labelledby
 *
 * @package WC_Product_Card_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Pull common values once.
$product_id    = $product->get_id();
$permalink     = get_permalink( $product_id );
$wc_title      = $product->get_name();
$reg_price     = (float) $card['regular_price'];
$sale_price    = (float) $card['sale_price'];
$display_price = (float) $card['display_price'];
$is_on_sale    = (bool) $card['is_on_sale'];
$show_badge    = (bool) $card['show_badge'];
$badge_text    = $card['badge_text'];
$card_index    = isset( $card['index'] ) ? (int) $card['index'] : 0;
$image_size    = isset( $settings['image_size'] ) ? sanitize_key( $settings['image_size'] ) : 'woocommerce_thumbnail';
$allowed_sizes = array( 'woocommerce_thumbnail', 'medium', 'large', 'full' );
if ( ! in_array( $image_size, $allowed_sizes, true ) ) {
	$image_size = 'woocommerce_thumbnail';
}

// H10: dynamic sizes hint based on the configured column counts per breakpoint.
// Delegated to WCPCE_Image_Helper (Phase 6, R4) — behaviour unchanged.
$image_sizes_attr = WCPCE_Image_Helper::build_sizes_attribute( $settings );

// LCP optimization: mark the first row of cards as high priority so the browser
// fetches them ahead of below-the-fold work. Use the actual desktop column count
// rather than the hardcoded 3 so the hint stays accurate when columns change.
// Delegated to WCPCE_Image_Helper (Phase 6, R4) — behaviour unchanged.
$is_above_fold    = WCPCE_Image_Helper::is_above_fold( $card_index, $settings );
$primary_loading  = $is_above_fold ? 'eager' : 'lazy';
$primary_priority = $is_above_fold ? 'high' : 'auto';

// ACF data: read all meta keys in a single get_post_meta() call via
// WCPCE_ACF_Helper (Phase 6, R5). The meta cache is already primed by
// WCPCE_Image_Helper::prime_attachment_caches() before the render loop,
// so this is a pure object-cache read with no additional DB query.
// Must be loaded before image logic so card_hover_image is available
// when WCPCE_Image_Helper::get_image_ids() runs below.
$acf_data = WCPCE_ACF_Helper::get_card_data( $product_id );

// Image: primary always, secondary only if hover swap is enabled and a valid second image exists.
// H8: skip all secondary image resolution (ACF field lookup, gallery fetch,
// wp_attachment_is() validation) when hover swap is disabled in settings.
// Delegated to WCPCE_Image_Helper (Phase 6, R4) — behaviour unchanged.
$image_ids          = WCPCE_Image_Helper::get_image_ids( $product, $acf_data, $settings );
$primary_image_id   = $image_ids['primary_image_id'];
$secondary_image_id = $image_ids['secondary_image_id'];
$hover_swap_enabled = $image_ids['hover_swap_enabled'];

// Compose card classes.
$card_classes    = array( 'wc-card' );
$is_out_of_stock = WCPCE_Stock_Helper::is_out_of_stock( $product );
$show_oos_visual = WCPCE_Stock_Helper::should_show_oos_visual( $is_out_of_stock, $settings );
if ( $show_oos_visual ) {
	$card_classes[] = 'wc-card--out-of-stock';
}
if ( 'yes' === ( $settings['hover_lift'] ?? 'yes' ) ) {
	$card_classes[] = 'wc-card--hover-lift';
}
if ( 'yes' !== ( $settings['show_usps_mobile'] ?? '' ) ) {
	$card_classes[] = 'wc-card--hide-usps-mobile';
}
if ( $hover_swap_enabled && 'yes' !== ( $settings['show_hover_swap_mobile'] ?? '' ) ) {
	$card_classes[] = 'wc-card--no-mobile-hover-swap';
}
$card_title = ! empty( $acf_data['card_title'] ) ? $acf_data['card_title'] : $wc_title;

// --- Out-of-stock label ---
// Initialised here before the niet_leverbaar block so the priority logic below
// can suppress it cleanly without a later assignment overwriting it (B1 fix).
// WCPCE_Stock_Helper returns both the visibility flag and the label text in one
// call. $show_stock_label is subsequently passed by reference into
// WCPCE_Badge_Helper::apply_badge_priority(), which may set it to false when the
// niet-leverbaar overlay takes over.
$stock_label_data   = WCPCE_Stock_Helper::get_stock_label( $show_oos_visual, $settings );
$show_stock_label   = $stock_label_data['show'];
$out_of_stock_label = $stock_label_data['label'];

// --- Custom badge flags from ACF ---
// Resolved by WCPCE_Badge_Helper (Phase 6, R1). Each badge activates only when
// the widget toggle is on AND the ACF field on the product is enabled.
$badge_flags = WCPCE_Badge_Helper::get_acf_badge_flags( $settings, $acf_data );

// Apply top-left badge priority (korting wins over Nieuw) and niet-leverbaar
// suppression. $card_classes and $show_stock_label are passed by reference:
// the niet-leverbaar treatment adds the OOS visual class and hides the stock
// label, mutating both. Must run AFTER $show_stock_label is initialised above.
$badge_state = WCPCE_Badge_Helper::apply_badge_priority( $show_badge, $badge_flags, $card_classes, $show_stock_label );

$show_badge                = $badge_state['show_badge'];
$show_nieuw_badge_final    = $badge_state['show_nieuw'];
$show_badge_pfas           = $badge_state['show_pfas'];
$show_badge_niet_leverbaar = $badge_state['show_niet_leverbaar'];

// E1/E2: cache badge label values once to avoid repeated array lookups.
$badge_labels         = WCPCE_Badge_Helper::get_badge_labels( $settings );
$label_nieuw          = $badge_labels['nieuw'];
$label_pfas           = $badge_labels['pfas'];
$label_niet_leverbaar = $badge_labels['niet_leverbaar'];

// USPs from the same ACF data — only populated up to the configured maximum.
// E3: use a counter variable instead of count() on every loop iteration.
$usps     = array();
$max_usps = isset( $settings['usp_count'] ) ? absint( $settings['usp_count'] ) : 3;
$usp_count = 0;
for ( $i = 1; $i <= 3 && $usp_count < $max_usps; $i++ ) {
	$value = ! empty( $acf_data[ 'usp_' . $i ] ) ? $acf_data[ 'usp_' . $i ] : '';
	if ( ! empty( $value ) ) {
		$usps[] = $value;
		$usp_count++;
	}
}

// Free shipping pill eligibility.
// Use sale price when on sale, otherwise use regular price.
// For variable products not on sale we use the regular price of the displayed
// variation range rather than the minimum variation price, to avoid showing
// the pill when only the cheapest variant qualifies.
$show_shipping_pill = false;
if ( 'yes' === ( $settings['show_shipping'] ?? 'yes' ) ) {
	$threshold     = floatval( $settings['shipping_threshold'] ?? 25 );
	$compare_price = $is_on_sale && $sale_price > 0 ? $sale_price : ( $reg_price > 0 ? $reg_price : $display_price );
	if ( $compare_price >= $threshold ) {
		$show_shipping_pill = true;
	}
}
$shipping_label     = isset( $settings['shipping_label'] ) ? $settings['shipping_label'] : __( 'Gratis verzending', 'woo-card-chef' );
$action_type        = isset( $settings['action_type'] ) ? $settings['action_type'] : 'none';
$show_action_button = 'none' !== $action_type;

// Icon markup uses <use href> to reference symbols defined in the SVG sprite
// output once per grid by the widget's render_svg_sprite() method.
$check_icon = '<svg class="wc-card__usp-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><use href="#wcpce-icon-check"/></svg>';
$truck_icon = '<svg class="wc-card__shipping-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><use href="#wcpce-icon-truck"/></svg>';
$leaf_icon  = '<svg class="wc-card__pfas-icon" viewBox="0 0 2.996769 3.3520503" aria-hidden="true" focusable="false"><use href="#wcpce-icon-leaf"/></svg>';

// Build a plain-text price string for the overlay link aria-label so screen
// reader users hear the product name and price in one clean announcement.
// wp_strip_all_tags removes the <bdi>/<span> markup wc_price() outputs.
//
// We avoid get_price_html() whenever possible since it runs through WC's
// full filter chain. Three paths in order of cost:
// - On sale with a numeric sale_price → wc_price( $sale_price )
// - Simple product not on sale with a numeric display_price → wc_price()
// - Variable products and edge cases → fall back to get_price_html()
if ( $is_on_sale && $sale_price > 0 ) {
	$price_for_label = wp_strip_all_tags( wc_price( $sale_price ) );
} elseif ( ! $card['is_variable'] && $display_price > 0 ) {
	$price_for_label = wp_strip_all_tags( wc_price( $display_price ) );
} else {
	// Variable products and edge cases: use the cached price_html from compute_card_data().
	$price_for_label = wp_strip_all_tags( $card['price_html'] );
}

// H6: include stock status in the overlay link accessible name so screen reader
// users navigating by links hear the availability state without entering the card.
// "Niet meer leverbaar" takes priority over "Tijdelijk uitverkocht" — the same
// hierarchy used by the visual badge system.
$stock_suffix = '';
if ( $show_badge_niet_leverbaar ) {
	$stock_suffix = ', ' . $label_niet_leverbaar;
} elseif ( $show_stock_label ) {
	$stock_suffix = ', ' . $out_of_stock_label;
}

$overlay_aria_label = sprintf(
	/* translators: 1: product name, 2: price */
	__( 'Bekijk %1$s - %2$s', 'woo-card-chef' ),
	$wc_title,
	$price_for_label
) . $stock_suffix;

// Build accessible badge aria-label. Screen readers should hear "12% korting"
// not "-12%" (which reads as "negative 12 percent"), and "€45 korting" not "-€45".
// For variable products the badge text may be "Tot -20%" — the helper strips the
// dash after the optional "Tot " prefix so it reads "Tot 20% korting".
$badge_aria_label = WCPCE_Badge_Helper::get_badge_aria_label( $show_badge, (string) $badge_text );

// Allowed SVG tags for safe output. Icons use <svg><use href> referencing
// the sprite — only svg and use elements are needed here.
$allowed_svg = array(
	'svg' => array( 'class' => array(), 'viewbox' => array(), 'aria-hidden' => array(), 'focusable' => array() ),
	'use' => array( 'href' => array(), 'xlink:href' => array() ),
);
?>
<?php
// Unique ID for aria-labelledby linkage between <article> and <h3>.
// Includes $widget_id (the Elementor widget ID, passed from render_card())
// so two grids showing the same product on one page each get a distinct
// title ID. Duplicate IDs are invalid HTML and break aria-labelledby on
// the second instance.
$title_id = ! empty( $widget_id )
	? 'wcpce-title-' . $widget_id . '-' . $product_id
	: 'wcpce-title-' . $product_id;
?>
<article
	class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>"
	data-product-id="<?php echo esc_attr( $product_id ); ?>"
	aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
>

	<a class="wc-card__overlay-link" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $overlay_aria_label ); ?>"></a>

	<div class="wc-card__media<?php echo $hover_swap_enabled ? ' wc-card__media--has-hover-swap' : ''; ?>">

		<?php if ( $show_badge && ! empty( $badge_text ) ) : ?>
			<span class="wc-card__badge" aria-label="<?php echo esc_attr( $badge_aria_label ); ?>"><?php echo esc_html( $badge_text ); ?></span>
		<?php elseif ( $show_nieuw_badge_final ) : ?>
			<span class="wc-card__badge wc-card__badge--nieuw"><?php echo esc_html( $label_nieuw ); ?></span>
		<?php endif; ?>

		<?php if ( $show_badge_pfas ) : ?>
			<span class="wc-card__badge wc-card__badge--pfas">
				<?php echo wp_kses( $leaf_icon, $allowed_svg ); ?>
				<?php echo esc_html( $label_pfas ); ?>
			</span>
		<?php endif; ?>

		<?php if ( $show_badge_niet_leverbaar ) : ?>
			<div class="wc-card__niet-leverbaar-overlay" aria-hidden="true">
				<span class="wc-card__niet-leverbaar-label"><?php echo esc_html( $label_niet_leverbaar ); ?></span>
			</div>
		<?php endif; ?>

		<?php if ( $show_stock_label ) : ?>
			<span class="wc-card__stock-label"><?php echo esc_html( $out_of_stock_label ); ?></span>
		<?php endif; ?>

		<?php if ( $primary_image_id ) : ?>
			<?php
			echo wp_get_attachment_image(
				$primary_image_id,
				$image_size,
				false,
				array(
					'class'         => 'wc-card__image wc-card__image--primary',
					// Raw string: wp_get_attachment_image() escapes its own
					// attributes. Pre-escaping double-encodes & and quotes
					// in product titles (CONVENTIONS).
					'alt'           => $card_title,
					'loading'       => $primary_loading,
					'decoding'      => 'async',
					'fetchpriority' => $primary_priority,
					'sizes'         => $image_sizes_attr,
				)
			);
			?>
		<?php else : ?>
			<?php
			// No featured image: use WooCommerce's placeholder. We pull dimensions
			// from the configured catalog image size to set explicit width/height,
			// which prevents layout shift while images load.
			$placeholder_dims = wc_get_image_size( 'woocommerce_thumbnail' );
			$placeholder_w    = ! empty( $placeholder_dims['width'] ) ? (int) $placeholder_dims['width'] : 600;
			$placeholder_h    = ! empty( $placeholder_dims['height'] ) ? (int) $placeholder_dims['height'] : 600;
			?>
			<img
				src="<?php echo esc_url( wc_placeholder_img_src( 'woocommerce_thumbnail' ) ); ?>"
				class="wc-card__image wc-card__image--primary"
				alt="<?php echo esc_attr( $card_title ); ?>"
				width="<?php echo esc_attr( $placeholder_w ); ?>"
				height="<?php echo esc_attr( $placeholder_h ); ?>"
				loading="<?php echo esc_attr( $primary_loading ); ?>"
				decoding="async"
				fetchpriority="<?php echo esc_attr( $primary_priority ); ?>"
				sizes="<?php echo esc_attr( $image_sizes_attr ); ?>">
		<?php endif; ?>

		<?php if ( $hover_swap_enabled ) : ?>
			<?php
			// The secondary image carries an inline style="opacity:0" so it stays
			// hidden during the brief moment between HTML render and external CSS
			// load. Without it, on slow connections or with a render-order race the
			// secondary image can flash visible at first paint. Inline styles apply
			// before external stylesheets arrive.
			//
			// loading="lazy" defers the actual image fetch until the card is near
			// the viewport. For above-the-fold cards the browser fetches it almost
			// immediately anyway, but below-the-fold cards save real bandwidth.
			// decoding="async" prevents image decoding from blocking the main thread.
			echo wp_get_attachment_image(
				$secondary_image_id,
				$image_size,
				false,
				array(
					'class'    => 'wc-card__image wc-card__image--secondary',
					'alt'      => '',
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => $image_sizes_attr,
					'style'    => 'opacity:0;',
				)
			);
			?>
		<?php endif; ?>

	</div>

	<div class="wc-card__body">

		<h3 class="wc-card__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $card_title ); ?></h3>

		<?php if ( 'yes' === ( $settings['show_rating'] ?? 'yes' ) ) : ?>
			<div class="wc-card__rating">
				<?php // Lipscore placeholder. Their site-wide JS finds these and renders stars client-side. ?>
				<div class="lipscore-rating-small" data-ls-product-id="<?php echo esc_attr( $product_id ); ?>"></div>
			</div>
		<?php endif; ?>

		<div class="wc-card__price-block">
			<div class="wc-card__price">
				<?php if ( $is_on_sale ) : ?>
					<span class="wc-card__price-regular"><span class="sr-only"><?php esc_html_e( 'Van', 'woo-card-chef' ); ?> </span><?php echo wp_kses_post( wc_price( $reg_price ) ); ?></span>
					<span class="wc-card__price-sale"><span class="sr-only"><?php esc_html_e( 'Voor', 'woo-card-chef' ); ?> </span><?php echo wp_kses_post( wc_price( $sale_price ) ); ?></span>
				<?php else : ?>
				<?php
					// Use the price_html cached in compute_card_data() — calling
					// get_price_html() again here would re-run WC's full filter chain
					// (including get_variation_prices() for variable products) for no gain.
					$price_html = $card['price_html'];
					if ( ! empty( $price_html ) || $display_price > 0 ) :
						?>
						<span class="wc-card__price-current"><?php echo wp_kses_post( ! empty( $price_html ) ? $price_html : wc_price( $display_price ) ); ?></span>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<?php if ( 'yes' === ( $settings['show_savings_line'] ?? 'yes' ) && $is_on_sale && $card['savings_amount'] > 0 ) : ?>
				<span class="wc-card__savings">
					<?php
					printf(
						/* translators: %s: amount saved with currency symbol */
						esc_html__( 'Bespaar %s', 'woo-card-chef' ),
						wp_kses_post( wc_price( $card['savings_amount'] ) )
					);
					?>
				</span>
			<?php endif; ?>
		</div>

		<?php if ( 'yes' === ( $settings['show_usps'] ?? 'yes' ) ) : ?>
			<?php
			$reserved_slots = isset( $settings['usp_count'] ) ? absint( $settings['usp_count'] ) : 3;
			$reserved_slots = max( 1, min( 3, $reserved_slots ) );
			?>
			<ul class="wc-card__usps" data-usp-slots="<?php echo esc_attr( $reserved_slots ); ?>">
				<?php foreach ( $usps as $usp ) : ?>
					<li class="wc-card__usp">
						<?php echo wp_kses( $check_icon, $allowed_svg ); ?>
						<span><?php echo esc_html( $usp ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $show_shipping_pill ) : ?>
			<span class="wc-card__shipping">
				<?php echo wp_kses( $truck_icon, $allowed_svg ); ?>
				<?php echo esc_html( $shipping_label ); ?>
			</span>
		<?php endif; ?>

		<?php if ( $show_action_button ) : ?>
			<div class="wc-card__actions">
				<?php if ( 'add_to_cart' === $action_type && $product->is_type( 'simple' ) && $product->is_purchasable() && ! $is_out_of_stock ) : ?>
					<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-quantity="1" data-product_id="<?php echo esc_attr( $product_id ); ?>" data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>" class="wc-card__button button add_to_cart_button ajax_add_to_cart"><?php echo esc_html( $settings['action_label_add_to_cart'] ?? __( 'In winkelwagen', 'woo-card-chef' ) ); ?></a>
				<?php elseif ( 'add_to_cart' === $action_type && $product->is_type( 'variable' ) ) : ?>
					<a href="<?php echo esc_url( $permalink ); ?>" class="wc-card__button"><?php echo esc_html( $settings['action_label_options'] ?? __( 'Kies opties', 'woo-card-chef' ) ); ?></a>
				<?php else : ?>
					<a href="<?php echo esc_url( $permalink ); ?>" class="wc-card__button"><?php echo esc_html( $settings['action_label_view'] ?? __( 'Bekijk product', 'woo-card-chef' ) ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	</div>

</article>
