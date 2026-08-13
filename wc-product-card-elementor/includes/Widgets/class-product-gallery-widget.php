<?php
/**
 * PDP Product Gallery widget for Elementor.
 *
 * Replaces the default WooCommerce product image and gallery on the product
 * detail page (PDP) with a faster, more flexible gallery that fits within the
 * existing Bourgini style. Renders inside an Elementor Theme Builder
 * single-product template — product context is available automatically via
 * wc_get_product() / global $product.
 *
 * Feature set (v2.0.0 / PDP Phase 1):
 * - WooCommerce featured image + gallery images as slides
 * - YouTube video slides via ACF repeater (pdp_gallery_videos), positioned in
 *   the thumbnail window before the +x slot, loaded lazily after interaction
 * - Thumbnail strip below the main image, configurable visible count
 * - Badgebar (Korting / Nieuw / PFAS-vrij) above or below the gallery block
 * - Status overlays (Niet meer leverbaar / Tijdelijk uitverkocht) on the main image
 * - Full-featured lightbox for images and video (vanilla JS)
 * - Image zoom + mobile pinch-zoom
 * - Accessible: keyboard navigation, focus trap, aria-live active slide
 * - LCP: first main image loads eager with fetchpriority=high; rest lazy
 *
 * Out of scope for v2.0.0 (see ROADMAP):
 * - Variation image swap (v2.1)
 * - In-slide video playback / display_mode (v2.1)
 * - Manual video_position interleaving (v2.1)
 * - Thumbnail position other than below (v2.1)
 *
 * @package WC_Product_Card_Elementor
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PDP Product Gallery Elementor widget.
 *
 * @since 2.0.0
 */
class WCPCE_Product_Gallery_Widget extends \Elementor\Widget_Base {

	// -------------------------------------------------------------------------
	// Elementor identity & dependencies
	// -------------------------------------------------------------------------

	/**
	 * Returns the unique widget slug.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_name(): string {
		return 'wcpce_product_gallery';
	}

	/**
	 * Returns the widget label shown in the Elementor panel.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'Product Gallery (PDP)', 'woo-card-chef' );
	}

	/**
	 * Returns the Elementor icon class for the widget panel.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-gallery-grid';
	}

	/**
	 * Returns the widget category slugs shown in the Elementor panel.
	 *
	 * @since 2.0.0
	 * @return array<string>
	 */
	public function get_categories(): array {
		return array( 'custom-woocommerce' );
	}

	/**
	 * Returns search keywords for the Elementor panel.
	 *
	 * @since 2.0.0
	 * @return array<string>
	 */
	public function get_keywords(): array {
		return array( 'gallery', 'product', 'woocommerce', 'pdp', 'image', 'video', 'lightbox' );
	}

	/**
	 * Returns the stylesheet handles this widget depends on.
	 *
	 * Elementor enqueues these only when the widget is present on the page,
	 * including inside the editor preview iframe.
	 *
	 * @since 2.0.0
	 * @return array<string>
	 */
	public function get_style_depends(): array {
		return array( 'wcpce-product-gallery' );
	}

	/**
	 * Returns the script handles this widget depends on.
	 *
	 * IMPORTANT: This method is called by Elementor during early script
	 * enqueueing — before widget settings are initialised. Never call
	 * get_settings_for_display() here; it returns null at this lifecycle stage
	 * and causes a fatal TypeError. Always return a static array.
	 * See DECISIONS_LOG ("DO NOT call get_settings_for_display() here")
	 * and the v1.0.15 hotfix in KNOWN_ISSUES.
	 *
	 * @since 2.0.0
	 * @return array<string>
	 */
	public function get_script_depends(): array {
		return array( 'wcpce-product-gallery' );
	}

	// -------------------------------------------------------------------------
	// Elementor controls
	// -------------------------------------------------------------------------

	/**
	 * Registers all Elementor controls for this widget.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function register_controls(): void {
		$this->register_content_controls();
		$this->register_badge_controls();
		$this->register_layout_controls();
		$this->register_style_controls();
	}

	/**
	 * Content tab: gallery sources.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function register_content_controls(): void {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Gallery Content', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_featured_image',
			array(
				'label'        => esc_html__( 'Show featured image', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_gallery_images',
			array(
				'label'        => esc_html__( 'Show gallery images', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_videos',
			array(
				'label'        => esc_html__( 'Show ACF video slides', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Requires ACF Pro. Videos appear before the final visible thumbnail slot so they stay visible before the +x indicator.', 'woo-card-chef' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content tab: badge settings.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function register_badge_controls(): void {
		$this->start_controls_section(
			'section_badges',
			array(
				'label' => esc_html__( 'Badges', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_badgebar',
			array(
				'label'        => esc_html__( 'Show badgebar', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Badgebar shows Korting, Nieuw and PFAS-vrij.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'badgebar_position',
			array(
				'label'     => esc_html__( 'Badgebar position', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'above',
				'options'   => array(
					'above' => esc_html__( 'Above gallery', 'woo-card-chef' ),
					'below' => esc_html__( 'Below gallery', 'woo-card-chef' ),
				),
				'condition' => array( 'show_badgebar' => 'yes' ),
			)
		);

		$this->add_control(
			'show_badge',
			array(
				'label'        => esc_html__( 'Show discount badge', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'show_badgebar' => 'yes' ),
			)
		);

		$this->add_control(
			'show_badge_nieuw',
			array(
				'label'        => esc_html__( 'Show Nieuw badge', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'show_badgebar' => 'yes' ),
			)
		);

		$this->add_control(
			'show_badge_pfas',
			array(
				'label'        => esc_html__( 'Show PFAS-vrij badge', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'show_badgebar' => 'yes' ),
			)
		);

		$this->add_control(
			'show_status_overlays',
			array(
				'label'        => esc_html__( 'Show status overlays', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Niet meer leverbaar and Tijdelijk uitverkocht overlays on the main image.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'show_badge_niet_leverbaar',
			array(
				'label'        => esc_html__( 'Show Niet meer leverbaar overlay', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'show_status_overlays' => 'yes' ),
			)
		);

		$this->add_control(
			'show_out_of_stock_label',
			array(
				'label'        => esc_html__( 'Show Tijdelijk uitverkocht overlay', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'show_status_overlays' => 'yes' ),
			)
		);

		// Badge format controls (reuse same setting keys as card widget so
		// WCPCE_Badge_Helper::compute_badge_data() works without adaptation).
		$this->add_control(
			'badge_format',
			array(
				'label'     => esc_html__( 'Badge format', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'smart',
				'options'   => array(
					'smart'   => esc_html__( 'Smart (Rule of 100)', 'woo-card-chef' ),
					'percent' => esc_html__( 'Percentage (−20%)', 'woo-card-chef' ),
					'amount'  => esc_html__( 'Amount (€5,-)', 'woo-card-chef' ),
				),
				'condition' => array(
					'show_badgebar' => 'yes',
					'show_badge'    => 'yes',
				),
			)
		);

		$this->add_control(
			'badge_threshold',
			array(
				'label'     => esc_html__( 'Minimum discount % to show badge', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 0,
				'min'       => 0,
				'max'       => 100,
				'condition' => array(
					'show_badgebar' => 'yes',
					'show_badge'    => 'yes',
				),
			)
		);

		$this->add_control(
			'badge_variable_prefix',
			array(
				'label'        => esc_html__( 'Show "Tot " prefix on variable products', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array(
					'show_badgebar' => 'yes',
					'show_badge'    => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Layout tab controls.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function register_layout_controls(): void {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Layout', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'object_fit',
			array(
				'label'   => esc_html__( 'Image fit', 'woo-card-chef' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'contain',
				'options' => array(
					'contain' => esc_html__( 'Contain (full product visible)', 'woo-card-chef' ),
					'cover'   => esc_html__( 'Cover (fills frame)', 'woo-card-chef' ),
				),
			)
		);

		$this->add_control(
			'thumbnail_count',
			array(
				'label'   => esc_html__( 'Visible thumbnails', 'woo-card-chef' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => 2,
				'max'     => 10,
			)
		);

		$this->add_control(
			'enable_lightbox',
			array(
				'label'        => esc_html__( 'Enable lightbox', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'enable_zoom',
			array(
				'label'        => esc_html__( 'Enable image zoom', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'enable_lightbox' => 'yes' ),
			)
		);

		$this->end_controls_section();

		// Responsive controls.
		$this->start_controls_section(
			'section_responsive',
			array(
				'label' => esc_html__( 'Responsive', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'aspect_ratio',
			array(
				'label'           => esc_html__( 'Aspect ratio', 'woo-card-chef' ),
				'type'            => \Elementor\Controls_Manager::SELECT,
				'default'         => '1/1',
				'mobile_default'  => '1/1',
				'options'         => array(
					'1/1'  => '1:1',
					'4/3'  => '4:3',
					'3/4'  => '3:4',
					'16/9' => '16:9',
				),
				'selectors'       => array(
					'{{WRAPPER}} .wcpce-gallery__slides' => 'aspect-ratio: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'mobile_thumbnail_count',
			array(
				'label'          => esc_html__( 'Visible thumbnails (mobile)', 'woo-card-chef' ),
				'type'           => \Elementor\Controls_Manager::NUMBER,
				'default'        => 4,
				'mobile_default' => 4,
				'min'            => 2,
				'max'            => 6,
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab controls.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function register_style_controls(): void {
		$this->start_controls_section(
			'section_style_gallery',
			array(
				'label' => esc_html__( 'Gallery', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'gallery_border_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 32 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 8 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-gallery__slides'          => 'border-radius: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcpce-gallery__main-image-wrap' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'gallery_spacing',
			array(
				'label'      => esc_html__( 'Spacing between main image and thumbnails', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 40 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 10 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-gallery__thumbnails' => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Thumbnail style.
		$this->start_controls_section(
			'section_style_thumbs',
			array(
				'label' => esc_html__( 'Thumbnails', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'thumb_border_radius',
			array(
				'label'      => esc_html__( 'Thumbnail border radius', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 16 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 6 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-gallery__thumb' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'thumb_active_border_color',
			array(
				'label'     => esc_html__( 'Active thumbnail border colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#3EC26D',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-gallery__thumb--active .wcpce-gallery__thumb-btn, {{WRAPPER}} .wcpce-gallery__thumb-btn[aria-current="true"]' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'thumb_hover_border_color',
			array(
				'label'     => esc_html__( 'Thumbnail hover border colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#B4211C',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-gallery__thumb:not(.wcpce-gallery__thumb--active) .wcpce-gallery__thumb-btn:hover' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// Badgebar style.
		$this->start_controls_section(
			'section_style_badgebar',
			array(
				'label'     => esc_html__( 'Badgebar', 'woo-card-chef' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_badgebar' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'badgebar_gap',
			array(
				'label'      => esc_html__( 'Gap between badges', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 24 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 6 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-gallery__badgebar' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'badgebar_spacing',
			array(
				'label'      => esc_html__( 'Space between badgebar and gallery', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 24 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 8 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-gallery__badgebar--above' => 'margin-bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcpce-gallery__badgebar--below' => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Play button style.
		$this->start_controls_section(
			'section_style_play',
			array(
				'label' => esc_html__( 'Play button', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'play_button_size',
			array(
				'label'      => esc_html__( 'Play button size', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 32, 'max' => 96 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 64 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-gallery__play-btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'play_button_color',
			array(
				'label'     => esc_html__( 'Play button colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-gallery__play-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'play_button_bg',
			array(
				'label'     => esc_html__( 'Play button background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.68)',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-gallery__play-btn' => 'background: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	// -------------------------------------------------------------------------
	// Render helpers
	// -------------------------------------------------------------------------

	/**
	 * Null-safe check for Elementor editor or preview mode.
	 *
	 * Guards against Plugin::$instance being null in non-standard bootstrap
	 * orders (e.g. CLI, unit tests). Same pattern as the card widget.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	private function is_elementor_editor_or_preview(): bool {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return false;
		}

		$elementor = \Elementor\Plugin::$instance;

		if ( isset( $elementor->editor ) && is_object( $elementor->editor ) && method_exists( $elementor->editor, 'is_edit_mode' ) && $elementor->editor->is_edit_mode() ) {
			return true;
		}

		if ( isset( $elementor->preview ) && is_object( $elementor->preview ) && method_exists( $elementor->preview, 'is_preview_mode' ) && $elementor->preview->is_preview_mode() ) {
			return true;
		}

		return false;
	}

	/**
	 * Validates and sanitises gallery widget settings before render.
	 *
	 * Defensive validation against corrupted database state, direct postmeta
	 * edits, or import/export anomalies — not against attacker input (Elementor
	 * settings are only writable by authenticated editors). Same threat model and
	 * approach as the card widget's validate_manual_settings(): whitelist SELECT
	 * values and clamp numeric ranges to known-safe defaults.
	 *
	 * @since 1.0.89
	 * @param array $settings Raw widget settings.
	 * @return array Validated settings.
	 */
	private function validate_gallery_settings( array $settings ): array {
		// object_fit — whitelist against the two registered options.
		$fit = $settings['object_fit'] ?? 'contain';
		if ( ! in_array( $fit, array( 'contain', 'cover' ), true ) ) {
			$fit = 'contain';
		}
		$settings['object_fit'] = $fit;

		// badgebar_position — whitelist.
		$pos = $settings['badgebar_position'] ?? 'above';
		if ( ! in_array( $pos, array( 'above', 'below' ), true ) ) {
			$pos = 'above';
		}
		$settings['badgebar_position'] = $pos;

		// badge_format — whitelist.
		$fmt = $settings['badge_format'] ?? 'smart';
		if ( ! in_array( $fmt, array( 'smart', 'percent', 'amount' ), true ) ) {
			$fmt = 'smart';
		}
		$settings['badge_format'] = $fmt;

		// thumbnail_count — clamp 2-10 (absint first, then clamp).
		$settings['thumbnail_count'] = max( 2, min( 10, absint( $settings['thumbnail_count'] ?? 5 ) ) );

		// badge_threshold — clamp 0-100.
		$settings['badge_threshold'] = max( 0, min( 100, absint( $settings['badge_threshold'] ?? 0 ) ) );

		return $settings;
	}

	/**
	 * Resolves the current product from the single-product request context.
	 *
	 * Prefer the queried product before the WooCommerce global because product
	 * loops rendered earlier on the PDP can leave global $product pointing at a
	 * card item instead of the PDP product.
	 *
	 * @since 2.0.0
	 * @since 2.5.8 Prefer queried product before global product (aligned with Upsells widget).
	 * @return \WC_Product|null The current product, or null when none is available.
	 */
	private function get_current_product(): ?\WC_Product {
		$post = get_queried_object();
		if ( $post instanceof \WP_Post && 'product' === $post->post_type ) {
			$wc_product = wc_get_product( $post->ID );
			if ( $wc_product instanceof \WC_Product ) {
				return $wc_product;
			}
		}

		global $product;

		if ( $product instanceof \WC_Product ) {
			return $product;
		}

		return null;
	}

	/**
	 * Returns a fallback product for the Elementor editor when no product
	 * context is available (e.g. designing a template before assigning it).
	 *
	 * @since 2.0.0
	 * @return \WC_Product|null
	 */
	private function get_editor_fallback_product(): ?\WC_Product {
		$query = new \WP_Query(
			array(
				'post_type'      => 'product',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'no_found_rows'  => true,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( empty( $query->posts ) ) {
			return null;
		}

		return wc_get_product( $query->posts[0]->ID );
	}

	/**
	 * Outputs editor-only notices inside the widget.
	 *
	 * @since 2.0.0
	 * @param array           $settings Widget settings.
	 * @param \WC_Product|null $product  The resolved product (may be null).
	 * @return void
	 */
	private function render_editor_notices( array $settings, ?\WC_Product $product ): void {
		if ( ! $this->is_elementor_editor_or_preview() ) {
			return;
		}

		$messages = array();

		if ( null === $product ) {
			$messages[] = esc_html__( 'No product context found. Assign this template to a product or open a product page to preview.', 'woo-card-chef' );
		}

		if ( 'yes' === ( $settings['show_videos'] ?? 'yes' ) && ! self::acf_pro_active() ) {
			$messages[] = esc_html__( 'ACF Pro is not active. Video slides require ACF Pro with the repeater field type. The gallery will show WooCommerce images only.', 'woo-card-chef' );
		}

		if ( empty( $messages ) ) {
			return;
		}

		echo '<div class="wcpce-gallery-editor-notices">';
		foreach ( $messages as $message ) {
			echo '<div class="wcpce-gallery-editor-notice">' . esc_html( $message ) . '</div>';
		}
		echo '</div>';
	}

	/**
	 * Checks whether ACF Pro (with repeater support) is active.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	private static function acf_pro_active(): bool {
		if ( function_exists( 'acf_get_field_types' ) ) {
			$types = acf_get_field_types();
			return isset( $types['repeater'] );
		}
		return class_exists( 'acf_field_repeater' );
	}

	/**
	 * Builds the ordered list of gallery slides for the given product.
	 *
	 * Slide order: WC featured image → WC gallery images, with ACF videos
	 * inserted into the visible thumbnail window before the final +x slot.
	 * ACF videos only added when show_videos is on, ACF Pro is active, and
	 * the repeater has rows.
	 *
	 * Each slide is an array with:
	 * - type          : 'image' | 'video'
	 * - attachment_id : int (0 for video slides)
	 * - youtube_url   : string (empty for image slides)
	 * - video_title   : string (empty for image slides)
	 * - thumb_id      : int — for video slides: custom thumbnail ID or 0
	 *
	 * @since 2.0.0
	 * @param \WC_Product $product  The product.
	 * @param array       $settings Widget settings.
	 * @return array<int, array> Ordered slide list.
	 */
	private function get_gallery_slides( \WC_Product $product, array $settings ): array {
		$image_slides = array();
		$video_slides = array();

		// 1. WooCommerce featured image.
		if ( 'yes' === ( $settings['show_featured_image'] ?? 'yes' ) ) {
			$featured_id = (int) $product->get_image_id();
			if ( $featured_id > 0 ) {
				$image_slides[] = array(
					'type'          => 'image',
					'attachment_id' => $featured_id,
					'youtube_url'   => '',
					'video_title'   => '',
					'thumb_id'      => 0,
				);
			}
		}

		// 2. WooCommerce gallery images.
		if ( 'yes' === ( $settings['show_gallery_images'] ?? 'yes' ) ) {
			foreach ( $product->get_gallery_image_ids() as $gid ) {
				$gid = (int) $gid;
				if ( $gid > 0 ) {
					$image_slides[] = array(
						'type'          => 'image',
						'attachment_id' => $gid,
						'youtube_url'   => '',
						'video_title'   => '',
						'thumb_id'      => 0,
					);
				}
			}
		}

		// 3. ACF YouTube video slides.
		if ( 'yes' === ( $settings['show_videos'] ?? 'yes' ) && self::acf_pro_active() ) {
			$videos = get_field( 'pdp_gallery_videos', $product->get_id() );
			if ( is_array( $videos ) ) {
				foreach ( $videos as $row ) {
					$url = isset( $row['youtube_url'] ) ? trim( (string) $row['youtube_url'] ) : '';
					if ( empty( $url ) ) {
						continue;
					}
					$video_id = self::extract_youtube_id( $url );
					if ( '' === $video_id ) {
						continue;
					}
					$video_slides[] = array(
						'type'          => 'video',
						'attachment_id' => 0,
						'youtube_url'   => esc_url( $url ),
						'youtube_id'    => $video_id,
						'video_title'   => isset( $row['video_title'] ) ? sanitize_text_field( $row['video_title'] ) : '',
						'thumb_id'      => isset( $row['video_thumbnail'] ) ? absint( $row['video_thumbnail'] ) : 0,
					);
				}
			}
		}

		return $this->position_video_slides_before_thumbnail_overflow( $image_slides, $video_slides, $settings );
	}

	/**
	 * Returns the configured visible thumbnail count, clamped to the control range.
	 *
	 * @since 2.0.0
	 * @param array $settings Widget settings.
	 * @return int
	 */
	private function get_visible_thumbnail_count( array $settings ): int {
		$visible_thumbs = isset( $settings['thumbnail_count'] ) ? absint( $settings['thumbnail_count'] ) : 5;
		return max( 2, min( 10, $visible_thumbs ) );
	}

	/**
	 * Positions video slides in the visible thumbnail window.
	 *
	 * The last visible thumbnail carries the +x overlay when extra slides are
	 * hidden, so video slides are inserted immediately before that final visible
	 * slot. For the default 5 thumbnails and one video, the video becomes the
	 * 4th thumbnail and the 5th thumbnail remains available for +x.
	 *
	 * @since 2.0.0
	 * @param array $image_slides Image slide data.
	 * @param array $video_slides Video slide data.
	 * @param array $settings     Widget settings.
	 * @return array<int, array> Ordered slide list.
	 */
	private function position_video_slides_before_thumbnail_overflow( array $image_slides, array $video_slides, array $settings ): array {
		if ( empty( $video_slides ) ) {
			return $image_slides;
		}

		$visible_thumbs = $this->get_visible_thumbnail_count( $settings );

		// Human position "thumbnail_count - 1" is the slot before the final
		// visible thumbnail. Convert that to a zero-based insertion point and
		// reserve enough contiguous slots for all video thumbnails.
		$insert_at = max( 0, $visible_thumbs - 1 - count( $video_slides ) );
		$insert_at = min( $insert_at, count( $image_slides ) );

		return array_merge(
			array_slice( $image_slides, 0, $insert_at ),
			$video_slides,
			array_slice( $image_slides, $insert_at )
		);
	}

	/**
	 * Extracts the YouTube video ID from a URL.
	 *
	 * Handles: normal watch URLs, youtu.be short URLs, embed URLs, Shorts URLs.
	 *
	 * @since 2.0.0
	 * @param string $url The YouTube URL.
	 * @return string The video ID, or empty string if not found.
	 */
	private static function extract_youtube_id( string $url ): string {
		// Strict host validation — only accept known YouTube hosts so a malformed
		// or unexpected URL is never treated as an embeddable video.
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! is_string( $host ) ) {
			return '';
		}
		$host          = strtolower( $host );
		$allowed_hosts = array( 'youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be', 'youtube-nocookie.com', 'www.youtube-nocookie.com' );
		if ( ! in_array( $host, $allowed_hosts, true ) ) {
			return '';
		}

		// youtu.be/ID or youtu.be/ID?params
		if ( preg_match( '#youtu\.be/([a-zA-Z0-9_-]{11})#', $url, $m ) ) {
			return $m[1];
		}
		// /shorts/ID
		if ( preg_match( '#/shorts/([a-zA-Z0-9_-]{11})#', $url, $m ) ) {
			return $m[1];
		}
		// /embed/ID
		if ( preg_match( '#/embed/([a-zA-Z0-9_-]{11})#', $url, $m ) ) {
			return $m[1];
		}
		// ?v=ID or &v=ID
		if ( preg_match( '#[?&]v=([a-zA-Z0-9_-]{11})#', $url, $m ) ) {
			return $m[1];
		}
		return '';
	}

	/**
	 * Sanitises a YouTube ID without changing case.
	 *
	 * YouTube video IDs are case-sensitive, so sanitize_key() is not safe here
	 * because it lowercases the value and can break fallback thumbnail URLs.
	 *
	 * @since 2.6.4
	 * @param string $youtube_id Raw YouTube video ID.
	 * @return string Sanitised YouTube video ID.
	 */
	private static function sanitize_youtube_id( string $youtube_id ): string {
		return preg_replace( '/[^A-Za-z0-9_-]/', '', $youtube_id ) ?? '';
	}

	/**
	 * Outputs the gallery SVG sprite (once per page).
	 *
	 * Separate from the card widget sprite so the gallery sprite is only
	 * output when the gallery widget renders. Uses its own static flag to
	 * prevent duplication on pages with multiple gallery instances.
	 *
	 * Icons: play, chevron-left, chevron-right, close (X).
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function render_gallery_sprite(): void {
		static $rendered = false;
		if ( $rendered ) {
			return;
		}
		$rendered = true;

		echo '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute;width:0;height:0;overflow:hidden;" aria-hidden="true" focusable="false">';
		echo '<defs>';

		// Play icon.
		echo '<symbol id="wcpce-gallery-icon-play" viewBox="0 0 24 24">';
		echo '<polygon points="6,3 21,12 6,21" fill="currentColor"/>';
		echo '</symbol>';

		// Chevron left.
		echo '<symbol id="wcpce-gallery-icon-prev" viewBox="0 0 24 24">';
		echo '<path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>';
		echo '</symbol>';

		// Chevron right.
		echo '<symbol id="wcpce-gallery-icon-next" viewBox="0 0 24 24">';
		echo '<path d="M9 18l6-6-6-6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>';
		echo '</symbol>';

		// Close / X.
		echo '<symbol id="wcpce-gallery-icon-close" viewBox="0 0 24 24">';
		echo '<path d="M18 6L6 18M6 6l12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>';
		echo '</symbol>';

		// Leaf icon (PFAS-vrij badge) — identical to the card widget sprite symbol.
		echo '<symbol id="wcpce-gallery-icon-leaf" viewBox="0 0 2.996769 3.3520503">';
		echo '<path fill="currentColor" d="M 0.11367774,3.3348666 C 0.02320601,3.2929796 0.00890051,3.1786555 0.08832631,3.1322685 0.10391023,3.1231682 0.13260497,3.1181068 0.19506976,3.1134495 0.29652413,3.1058845 0.33914794,3.0987479 0.41633848,3.0764122 0.58186152,3.0285133 0.78884167,2.9229141 1.0409714,2.7577303 l 0.1132021,-0.074165 -0.029791,-0.0056 C 0.89175531,2.6342447 0.64950905,2.4417489 0.53054831,2.2060889 0.47187517,2.0898579 0.4486248,1.9900765 0.44822565,1.8527917 0.44779733,1.705458 0.47530363,1.5906616 0.54655753,1.4424094 0.68311158,1.1582923 0.92335288,0.89743049 1.2030447,0.72957453 1.3955841,0.61402279 1.5537682,0.54886654 1.9505712,0.42166842 2.2190293,0.33561226 2.3110565,0.30175807 2.421363,0.24847776 2.5480886,0.18726658 2.6454532,0.11580257 2.7124265,0.0348413 2.7323853,0.0107136 2.7506579,-0.00675553 2.7530322,-0.00397989 2.760681,0.00496356 2.8264941,0.23670288 2.8672214,0.39810327 2.9817216,0.85186416 3.0333776,1.2295717 3.0331945,1.6116957 3.0331031,1.8023051 3.0278164,1.8945448 3.0092418,2.0296231 2.9230828,2.6561979 2.6237012,3.0912894 2.1998487,3.2059148 2.1429872,3.2212921 2.1232265,3.2232829 2.0287943,3.2231472 1.9312935,3.2230117 1.9153688,3.2212167 1.8452901,3.2026511 1.6564554,3.1525904 1.5237147,3.0750108 1.4428613,2.9674522 L 1.4125374,2.9271126 1.534072,2.805339 C 1.9661414,2.372421 2.2887695,1.81821 2.4826704,1.1758359 2.5025544,1.1099621 2.5178789,1.0551211 2.5167249,1.053967 2.5155666,1.0528208 2.4986456,1.088693 2.4791133,1.1337001 2.3377437,1.4594496 2.1378337,1.7941674 1.9161261,2.0763332 1.7675395,2.2654387 1.5155865,2.5302833 1.3481611,2.6733585 1.086292,2.8971422 0.66337924,3.1659788 0.37329167,3.2930623 0.24743492,3.3481985 0.16955853,3.3607387 0.11367774,3.3348666 Z"/>';
		echo '</symbol>';

		echo '</defs>';
		echo '</svg>';
	}

	/**
	 * Renders the badgebar (Korting, Nieuw, PFAS-vrij).
	 *
	 * Niet meer leverbaar suppresses Korting and Nieuw in the badgebar
	 * (design decision E) but does NOT suppress PFAS-vrij (eigenschap badge).
	 * This differs from the card widget where all badges are suppressed.
	 *
	 * Does NOT use apply_badge_priority() — that method contains card-specific
	 * logic (korting wins over Nieuw, by-reference class/label mutation) that
	 * does not apply in the badgebar where both can show simultaneously.
	 *
	 * @since 2.0.0
	 * @param array      $settings Widget settings.
	 * @param array      $acf_data ACF badge flags from WCPCE_ACF_Helper.
	 * @param array      $price_data Price data from WCPCE_Price_Helper.
	 * @param string     $position 'above' or 'below'.
	 * @return void
	 */
	private function render_badgebar( array $settings, array $acf_data, array $price_data, string $position, bool $mixed_discounts = false ): void {
		if ( 'yes' !== ( $settings['show_badgebar'] ?? 'yes' ) ) {
			return;
		}

		$flags  = WCPCE_Badge_Helper::get_acf_badge_flags( $settings, $acf_data );
		$labels = WCPCE_Badge_Helper::get_badge_labels( $settings );

		// Decision E: niet meer leverbaar suppresses korting and Nieuw in the
		// badgebar. PFAS-vrij is an eigenschap badge and is NOT suppressed.
		$niet_leverbaar = ! empty( $flags['niet_leverbaar'] );

		// Discount badge data.
		$badge = WCPCE_Badge_Helper::compute_badge_data( $price_data, $settings, $mixed_discounts );

		$show_badge = ! $niet_leverbaar && ! empty( $badge['show_badge'] );
		$show_nieuw = ! $niet_leverbaar && ! empty( $flags['nieuw'] );
		$show_pfas  = ! empty( $flags['pfas'] );

		// Nothing to show.
		if ( ! $show_badge && ! $show_nieuw && ! $show_pfas ) {
			return;
		}

		$pos_class = 'above' === $position ? 'wcpce-gallery__badgebar--above' : 'wcpce-gallery__badgebar--below';

		echo '<div class="wcpce-gallery__badgebar ' . esc_attr( $pos_class ) . '" aria-label="' . esc_attr__( 'Productbadges', 'woo-card-chef' ) . '">';

		if ( $show_badge ) {
			$aria = WCPCE_Badge_Helper::get_badge_aria_label( true, $badge['badge_text'] );
			echo '<span class="wcpce-gallery__badge wcpce-gallery__badge--discount"';
			if ( $aria ) {
				echo ' aria-label="' . esc_attr( $aria ) . '"';
			}
			echo '>' . esc_html( $badge['badge_text'] ) . '</span>';
		}

		if ( $show_nieuw ) {
			echo '<span class="wcpce-gallery__badge wcpce-gallery__badge--nieuw">';
			echo esc_html( $labels['nieuw'] );
			echo '</span>';
		}

		if ( $show_pfas ) {
			// Leaf icon uses the same gallery sprite symbol as the card widget uses.
			// wp_kses() allows svg/use for the icon — same allowed list as card.php.
			$allowed_svg = array(
				'svg'  => array( 'aria-hidden' => true, 'focusable' => true, 'class' => true, 'style' => true ),
				'use'  => array( 'href' => true ),
			);
			echo '<span class="wcpce-gallery__badge wcpce-gallery__badge--pfas">';
			echo wp_kses( '<svg aria-hidden="true" focusable="false"><use href="#wcpce-gallery-icon-leaf"/></svg>', $allowed_svg );
			echo esc_html( $labels['pfas'] );
			echo '</span>';
		}

		echo '</div>';
	}

	/**
	 * Renders a single image slide.
	 *
	 * @since 2.0.0
	 * @param array $slide    Slide data array.
	 * @param int   $index    Zero-based slide index (0 = LCP candidate).
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private function render_image_slide( array $slide, int $index, array $settings, string $fallback_alt ): void {
		$attachment_id = (int) $slide['attachment_id'];
		$is_lcp        = 0 === $index;
		$loading       = $is_lcp ? 'eager' : 'lazy';
		$fetchpriority = $is_lcp ? 'high' : '';
		$object_fit    = $settings['object_fit'] ?? 'contain';
		$alt           = $this->get_attachment_alt_text( $attachment_id, $fallback_alt );

		$img_atts = array(
			'class'   => 'wcpce-gallery__image',
			'loading' => $loading,
			'style'   => 'object-fit:' . esc_attr( $object_fit ) . ';',
			'alt'     => $alt,
		);
		if ( $fetchpriority ) {
			$img_atts['fetchpriority'] = $fetchpriority;
		}

		echo wp_get_attachment_image( $attachment_id, 'woocommerce_single', false, $img_atts );
	}

	/**
	 * Returns an attachment alt text with a product-level fallback.
	 *
	 * @since 2.6.7
	 * @param int    $attachment_id Attachment ID.
	 * @param string $fallback_alt  Fallback alt text.
	 * @return string
	 */
	private function get_attachment_alt_text( int $attachment_id, string $fallback_alt ): string {
		$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		$alt = is_string( $alt ) ? trim( wp_strip_all_tags( $alt ) ) : '';

		if ( '' !== $alt ) {
			return $alt;
		}

		return trim( wp_strip_all_tags( $fallback_alt ) );
	}

	/**
	 * Renders a single video slide (thumbnail + play button).
	 *
	 * The YouTube iframe is NOT loaded here — JS handles lazy loading after
	 * user interaction. The youtube_id is stored in a data attribute so the JS
	 * can build the nocookie embed URL on demand.
	 *
	 * @since 2.0.0
	 * @param array $slide    Slide data array.
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private function render_video_slide( array $slide, array $settings, bool $is_active = true ): void {
		$youtube_id  = self::sanitize_youtube_id( (string) ( $slide['youtube_id'] ?? '' ) );
		$video_title = $slide['video_title'] ?? '';
		$thumb_id    = (int) ( $slide['thumb_id'] ?? 0 );

		// Play button aria label: prefer video title, fall back to generic.
		$play_label = $video_title
			? sprintf(
				/* translators: %s: video title */
				__( 'Bekijk video: %s', 'woo-card-chef' ),
				$video_title
			)
			: __( 'Bekijk productvideo', 'woo-card-chef' );

		echo '<div class="wcpce-gallery__video-slide" data-youtube-id="' . esc_attr( $youtube_id ) . '">';

		// Thumbnail: custom thumb_id takes priority, then YouTube thumbnail.
		$thumb_url = '';
		if ( $thumb_id > 0 ) {
			$thumb_url = wp_get_attachment_image_url( $thumb_id, 'woocommerce_single' );
		}

		if ( ! $thumb_url ) {
			$thumb_url = 'https://i.ytimg.com/vi/' . $youtube_id . '/mqdefault.jpg';
		}

		echo '<span class="wcpce-gallery__video-thumb" aria-hidden="true" style="background-image:url(\'' . esc_url( $thumb_url ) . '\');"></span>';

		// Play button. Inactive slides get tabindex=-1 so hidden controls are not
		// keyboard-reachable; goTo() in JS maintains this on slide change.
		echo '<button class="wcpce-gallery__play-btn" type="button" aria-label="' . esc_attr( $play_label ) . '"';
		if ( ! $is_active ) {
			echo ' tabindex="-1"';
		}
		echo '>';
		echo '<svg aria-hidden="true" focusable="false"><use href="#wcpce-gallery-icon-play"/></svg>';
		echo '</button>';

		echo '</div>'; // .wcpce-gallery__video-slide
	}

	// -------------------------------------------------------------------------
	// Main render
	// -------------------------------------------------------------------------

	/**
	 * Renders the widget HTML on the frontend and in the editor preview.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function render(): void {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$settings  = $this->validate_gallery_settings( $this->get_settings_for_display() );
		$is_editor = $this->is_elementor_editor_or_preview();

		// Resolve product from WC context or editor fallback.
		$product = $this->get_current_product();
		if ( null === $product && $is_editor ) {
			$product = $this->get_editor_fallback_product();
		}

		// Editor notices (ACF Pro missing, no product, etc).
		$this->render_editor_notices( $settings, $product );

		if ( null === $product ) {
			return;
		}

		// Build slide list.
		$slides = $this->get_gallery_slides( $product, $settings );

		if ( empty( $slides ) ) {
			if ( $is_editor ) {
				echo '<div class="wcpce-gallery-editor-notice">';
				echo esc_html__( 'This product has no images.', 'woo-card-chef' );
				echo '</div>';
			}
			return;
		}

		$gallery_alt_fallback = $product->get_name();

		// Resolve badge data once — used by badgebar and overlay.
		$acf_data = WCPCE_ACF_Helper::get_card_data( $product->get_id() );

		// Price data for the discount badge.
		$price_data = array(
			'is_on_sale'       => false,
			'is_variable'      => $product->is_type( 'variable' ),
			'regular_price'    => 0.0,
			'sale_price'       => 0.0,
			'display_price'    => 0.0,
			'discount_percent' => 0,
			'savings_amount'   => 0.0,
			'show_badge'       => 'yes' === ( $settings['show_badge'] ?? 'yes' ),
			'badge_text'       => '',
		);

		$mixed_discounts = false;
		if ( 'yes' === ( $settings['show_badge'] ?? 'yes' ) ) {
			$prices                         = WCPCE_Price_Helper::get_product_price_data( $product );
			$price_data['regular_price']    = $prices['regular_price'];
			$price_data['sale_price']       = $prices['sale_price'];
			$price_data['display_price']    = $prices['display_price'];
			$price_data['discount_percent'] = $prices['discount_percent'];
			$price_data['savings_amount']   = $prices['savings_amount'];
			$price_data['is_on_sale']       = $product->is_on_sale() && $price_data['discount_percent'] > 0;
			// is_variable stays the real product type; mixed_discounts is passed
			// separately to compute_badge_data() so the "Tot " prefix works.
			$mixed_discounts = ! empty( $prices['mixed_discounts'] );
		}

		// Status overlay data.
		$is_out_of_stock   = WCPCE_Stock_Helper::is_out_of_stock( $product );
		$stock_label_data  = WCPCE_Stock_Helper::get_stock_label( $is_out_of_stock, $settings );
		$flags             = WCPCE_Badge_Helper::get_acf_badge_flags( $settings, $acf_data );
		$show_niet_lev     = ! empty( $flags['niet_leverbaar'] );

		// OOS visual: niet_leverbaar also triggers the OOS visual treatment.
		$show_oos_visual   = WCPCE_Stock_Helper::should_show_oos_visual( $is_out_of_stock, $settings ) || $show_niet_lev;

		// Badgebar position.
		$badgebar_position = $settings['badgebar_position'] ?? 'above';

		// Prime attachment caches for all image slides AND custom video thumbnails
		// so wp_get_attachment_image_url() makes no per-image DB calls in the loop.
		$prime_ids = array();
		foreach ( $slides as $slide_for_prime ) {
			if ( 'image' === $slide_for_prime['type'] && ! empty( $slide_for_prime['attachment_id'] ) ) {
				$prime_ids[] = (int) $slide_for_prime['attachment_id'];
			} elseif ( 'video' === $slide_for_prime['type'] && ! empty( $slide_for_prime['thumb_id'] ) ) {
				$prime_ids[] = (int) $slide_for_prime['thumb_id'];
			}
		}
		$prime_ids = array_filter( array_unique( $prime_ids ) );
		if ( ! empty( $prime_ids ) ) {
			_prime_post_caches( $prime_ids, false, false );
			update_meta_cache( 'post', $prime_ids );
		}

		$widget_id        = $this->get_id();
		$enable_lb        = 'yes' === ( $settings['enable_lightbox'] ?? 'yes' );
		$enable_zoom      = $enable_lb && 'yes' === ( $settings['enable_zoom'] ?? 'yes' );
		$total            = count( $slides );
		$has_video_slides = ! empty(
			array_filter(
				$slides,
				static function( $slide ) {
					return 'video' === ( $slide['type'] ?? '' );
				}
			)
		);
		$render_lightbox  = $enable_lb || $has_video_slides;

		// Output the gallery sprite (play, chevrons, close).
		$this->render_gallery_sprite();

		echo '<div class="wcpce-gallery"';
		echo ' data-widget-id="' . esc_attr( $widget_id ) . '"';
		echo ' data-lightbox="' . ( $enable_lb ? '1' : '0' ) . '"';
		echo ' data-video-lightbox="' . ( $has_video_slides ? '1' : '0' ) . '"';
		echo ' data-zoom="' . ( $enable_zoom ? '1' : '0' ) . '"';
		echo ' data-total="' . esc_attr( $total ) . '"';
		echo '>';

		// Badgebar — above.
		if ( 'above' === $badgebar_position ) {
			$this->render_badgebar( $settings, $acf_data, $price_data, 'above', $mixed_discounts );
		}

		// Main image area.
		echo '<div class="wcpce-gallery__stage' . ( $show_oos_visual ? ' wcpce-gallery__stage--oos' : '' ) . '">';

		// Prev / Next navigation buttons.
		if ( $total > 1 ) {
			echo '<button class="wcpce-gallery__nav wcpce-gallery__nav--prev" type="button" aria-label="' . esc_attr__( 'Vorige afbeelding', 'woo-card-chef' ) . '" aria-controls="wcpce-gallery-slides-' . esc_attr( $widget_id ) . '">';
			echo '<svg aria-hidden="true" focusable="false"><use href="#wcpce-gallery-icon-prev"/></svg>';
			echo '</button>';
		}

		// Slides container.
		echo '<div class="wcpce-gallery__slides" id="wcpce-gallery-slides-' . esc_attr( $widget_id ) . '" role="region" aria-label="' . esc_attr__( 'Productafbeeldingen', 'woo-card-chef' ) . '">';
		echo '<div class="wcpce-gallery__slides-track" aria-live="polite" aria-atomic="false">';

		foreach ( $slides as $index => $slide ) {
			$is_active  = 0 === $index;
			$slide_classes = 'wcpce-gallery__slide';
			if ( $is_active ) {
				$slide_classes .= ' wcpce-gallery__slide--active';
			}
			if ( 'video' === $slide['type'] ) {
				$slide_classes .= ' wcpce-gallery__slide--video';
			}

			echo '<div class="' . esc_attr( $slide_classes ) . '"';
			echo ' role="group"';
			echo ' aria-label="' . esc_attr(
				sprintf(
				/* translators: 1: current slide, 2: total slides */
					__( 'Afbeelding %1$d van %2$d', 'woo-card-chef' ),
					(int) $index + 1,
					(int) $total
				)
			) . '"';
			echo ' aria-hidden="' . ( $is_active ? 'false' : 'true' ) . '"';
			if ( ! $is_active ) {
				echo ' inert';
			}
			echo '>';

			echo '<div class="wcpce-gallery__main-image-wrap">';

			if ( 'video' === $slide['type'] ) {
				$this->render_video_slide( $slide, $settings, $is_active );
			} else {
				$this->render_image_slide( $slide, $index, $settings, $gallery_alt_fallback );
			}

			echo '</div>'; // .wcpce-gallery__main-image-wrap

			echo '</div>'; // .wcpce-gallery__slide
		}

		echo '</div>'; // .wcpce-gallery__slides-track

		// Status overlays — rendered inside the slides container, positioned over active slide.
		if ( 'yes' === ( $settings['show_status_overlays'] ?? 'yes' ) ) {
			$labels = WCPCE_Badge_Helper::get_badge_labels( $settings );

			if ( $show_niet_lev && 'yes' === ( $settings['show_badge_niet_leverbaar'] ?? 'yes' ) ) {
				echo '<div class="wcpce-gallery__overlay wcpce-gallery__overlay--niet-leverbaar" aria-hidden="true">';
				echo '<span>' . esc_html( $labels['niet_leverbaar'] ) . '</span>';
				echo '</div>';
			} elseif ( $stock_label_data['show'] && 'yes' === ( $settings['show_out_of_stock_label'] ?? 'yes' ) ) {
				echo '<div class="wcpce-gallery__overlay wcpce-gallery__overlay--oos" aria-hidden="true">';
				echo '<span>' . esc_html( $stock_label_data['label'] ) . '</span>';
				echo '</div>';
			}
		}

		echo '</div>'; // .wcpce-gallery__slides

		if ( $total > 1 ) {
			echo '<button class="wcpce-gallery__nav wcpce-gallery__nav--next" type="button" aria-label="' . esc_attr__( 'Volgende afbeelding', 'woo-card-chef' ) . '" aria-controls="wcpce-gallery-slides-' . esc_attr( $widget_id ) . '">';
			echo '<svg aria-hidden="true" focusable="false"><use href="#wcpce-gallery-icon-next"/></svg>';
			echo '</button>';
		}

		echo '</div>'; // .wcpce-gallery__stage

		// Thumbnails.
		if ( $total > 1 ) {
			$visible_thumbs = $this->get_visible_thumbnail_count( $settings );

			echo '<div class="wcpce-gallery__thumbnails" role="list" aria-label="' . esc_attr__( 'Afbeelding thumbnails', 'woo-card-chef' ) . '">';

			foreach ( $slides as $index => $slide ) {
				$is_active     = 0 === $index;
				$is_hidden     = $index >= $visible_thumbs; // Beyond visible count.
				$thumb_classes = 'wcpce-gallery__thumb';
				if ( $is_active ) {
					$thumb_classes .= ' wcpce-gallery__thumb--active';
				}
				if ( 'video' === $slide['type'] ) {
					$thumb_classes .= ' wcpce-gallery__thumb--video';
				}
				if ( $is_hidden ) {
					$thumb_classes .= ' wcpce-gallery__thumb--hidden';
				}

				// More-indicator on the last visible thumbnail when there are more slides.
				$is_last_visible = ( $visible_thumbs - 1 ) === $index;
				$remaining       = $total - $visible_thumbs;
				$show_more_badge = $is_last_visible && $remaining > 0;

				echo '<div class="' . esc_attr( $thumb_classes ) . '" role="listitem">';

				echo '<button type="button"';
				echo ' class="wcpce-gallery__thumb-btn"';
				echo ' aria-label="' . esc_attr(
					sprintf(
					/* translators: %d: slide number */
						__( 'Ga naar afbeelding %d', 'woo-card-chef' ),
						(int) $index + 1
					)
				) . '"';
				echo ' aria-current="' . ( $is_active ? 'true' : 'false' ) . '"';
				echo ' data-slide-index="' . esc_attr( $index ) . '"';
				echo '>';

				// Thumbnail image.
				if ( 'video' === $slide['type'] ) {
					$thumb_id = (int) ( $slide['thumb_id'] ?? 0 );
					if ( $thumb_id > 0 ) {
						// Raw alt string: wp_get_attachment_image() escapes its own attributes (CONVENTIONS).
						echo wp_get_attachment_image( $thumb_id, 'thumbnail', false, array( 'loading' => 'lazy', 'alt' => $slide['video_title'] ?? '' ) );
					} else {
						$yt_id = self::sanitize_youtube_id( (string) ( $slide['youtube_id'] ?? '' ) );
						echo '<img src="' . esc_url( 'https://i.ytimg.com/vi/' . $yt_id . '/mqdefault.jpg' ) . '" alt="' . esc_attr( $slide['video_title'] ?? '' ) . '" loading="lazy" width="320" height="180">';
					}
					// Video indicator icon on thumbnail.
					echo '<span class="wcpce-gallery__thumb-video-icon" aria-hidden="true">';
					echo '<svg><use href="#wcpce-gallery-icon-play"/></svg>';
					echo '</span>';
				} else {
					echo wp_get_attachment_image(
						(int) $slide['attachment_id'],
						'thumbnail',
						false,
						array(
							'loading' => 'lazy',
							'alt'     => $this->get_attachment_alt_text( (int) $slide['attachment_id'], $gallery_alt_fallback ),
						)
					);
				}

				// More-indicator badge.
				if ( $show_more_badge ) {
					echo '<span class="wcpce-gallery__thumb-more" aria-hidden="true">+' . esc_html( $remaining ) . '</span>';
				}

				echo '</button>';
				echo '</div>'; // .wcpce-gallery__thumb
			}

			echo '</div>'; // .wcpce-gallery__thumbnails
		}

		// Badgebar — below.
		if ( 'below' === $badgebar_position ) {
			$this->render_badgebar( $settings, $acf_data, $price_data, 'below', $mixed_discounts );
		}

		echo '</div>'; // .wcpce-gallery

		// Lightbox rendered OUTSIDE .wcpce-gallery so position:fixed escapes to
		// the viewport. Any stacking context on an ancestor (isolation, transform,
		// will-change) traps position:fixed children — moving it outside avoids this.
		if ( $render_lightbox ) {
			echo '<div class="wcpce-gallery__lightbox" id="wcpce-gallery-lb-' . esc_attr( $widget_id ) . '" role="dialog" aria-modal="true" aria-label="' . esc_attr__( 'Afbeelding vergroot', 'woo-card-chef' ) . '" aria-hidden="true" data-gallery-id="' . esc_attr( $widget_id ) . '">';
			echo '<div class="wcpce-gallery__lightbox-backdrop" aria-hidden="true"></div>';
			echo '<div class="wcpce-gallery__lightbox-inner">';
			echo '<button class="wcpce-gallery__lightbox-close" type="button" aria-label="' . esc_attr__( 'Lightbox sluiten', 'woo-card-chef' ) . '">';
			echo '<svg aria-hidden="true" focusable="false"><use href="#wcpce-gallery-icon-close"/></svg>';
			echo '</button>';
			echo '<div class="wcpce-gallery__lightbox-content"></div>';

			if ( $enable_lb && $total > 1 ) {
				echo '<button class="wcpce-gallery__lightbox-nav wcpce-gallery__lightbox-nav--prev" type="button" aria-label="' . esc_attr__( 'Vorige afbeelding', 'woo-card-chef' ) . '">';
				echo '<svg aria-hidden="true" focusable="false"><use href="#wcpce-gallery-icon-prev"/></svg>';
				echo '</button>';
				echo '<button class="wcpce-gallery__lightbox-nav wcpce-gallery__lightbox-nav--next" type="button" aria-label="' . esc_attr__( 'Volgende afbeelding', 'woo-card-chef' ) . '">';
				echo '<svg aria-hidden="true" focusable="false"><use href="#wcpce-gallery-icon-next"/></svg>';
				echo '</button>';
			}

			echo '</div>'; // .wcpce-gallery__lightbox-inner
			echo '</div>'; // .wcpce-gallery__lightbox
		}
	}
}
