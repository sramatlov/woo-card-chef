<?php
/**
 * PDP Product Accordion widget for Elementor.
 *
 * Renders a fully accessible accordion that replaces the default WooCommerce
 * product tabs on the product detail page. All sections are server-rendered;
 * a scoped, deferred JS file handles open/close toggling, FAQ inner accordion,
 * Lipscore review-count sync, and hash-jump navigation.
 *
 * Sections (in fixed order):
 * 1. Description    — WooCommerce product description
 * 2. Specifications — WooCommerce attributes table (public attributes only)
 * 3. Reviews        — Lipscore WC tab content, wrapped via the WC tab callback
 * 4. FAQ            — ACF repeater `product_faq` (fields: vraag / antwoord)
 * 5. Manual         — ACF file field `product_manual` (PDF download link)
 *
 * All sections are hidden when their content source is empty.
 *
 * Feature set (v2.4.0 / PDP Phase 6):
 * - WCAG 2.2 AA accessible: <button> triggers, aria-expanded, aria-controls,
 *   progressive-enhancement panel hiding, correct heading level, visible focus indicator
 * - Multiple sections can be open simultaneously (NNG recommendation)
 * - FAQ accordion-in-accordion: each vraag/antwoord pair is its own toggle
 * - Lipscore review-count sync via MutationObserver in JS
 * - Hash-jump support (#lipscore-review-list, #tab-lipscorereviews, #reviews)
 * - Configurable section labels, default-open section, and download label
 * - Heading level control (h2/h3/h4) to maintain page hierarchy
 *
 * @package WC_Product_Card_Elementor
 * @since 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PDP Product Accordion Elementor widget.
 *
 * @since 2.4.0
 */
class WCPCE_Product_Accordion_Widget extends \Elementor\Widget_Base {

	// -------------------------------------------------------------------------
	// Elementor identity & dependencies
	// -------------------------------------------------------------------------

	/**
	 * Returns the unique widget slug.
	 *
	 * @since 2.4.0
	 * @return string
	 */
	public function get_name(): string {
		return 'wcpce_product_accordion';
	}

	/**
	 * Returns the widget label shown in the Elementor panel.
	 *
	 * @since 2.4.0
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'Product Accordion (PDP)', 'woo-card-chef' );
	}

	/**
	 * Returns the Elementor icon class for the widget panel.
	 *
	 * @since 2.4.0
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-accordion';
	}

	/**
	 * Returns the widget category slugs shown in the Elementor panel.
	 *
	 * @since 2.4.0
	 * @return array<string>
	 */
	public function get_categories(): array {
		return array( 'custom-woocommerce' );
	}

	/**
	 * Returns search keywords for the Elementor panel.
	 *
	 * @since 2.4.0
	 * @return array<string>
	 */
	public function get_keywords(): array {
		return array( 'accordion', 'tabs', 'faq', 'description', 'specifications', 'reviews', 'manual', 'product', 'woocommerce', 'pdp' );
	}

	/**
	 * Returns the stylesheet handles this widget depends on.
	 *
	 * @since 2.4.0
	 * @return array<string>
	 */
	public function get_style_depends(): array {
		return array( 'wcpce-product-accordion' );
	}

	/**
	 * Returns the script handles this widget depends on.
	 *
	 * Static array — never conditional on settings. Calling
	 * get_settings_for_display() here causes a fatal TypeError (settings are
	 * null at this lifecycle stage). See DECISIONS_LOG.
	 *
	 * @since 2.4.0
	 * @return array<string>
	 */
	public function get_script_depends(): array {
		return array( 'wcpce-product-accordion' );
	}

	// -------------------------------------------------------------------------
	// Elementor controls
	// -------------------------------------------------------------------------

	/**
	 * Registers all Elementor controls for this widget.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	protected function register_controls(): void {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	/**
	 * Content tab: section labels, toggles, heading level, default-open, etc.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	private function register_content_controls(): void {

		// ----- General -------------------------------------------------------
		$this->start_controls_section(
			'accordion_general_section',
			array(
				'label' => esc_html__( 'General', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'heading_tag',
			array(
				'label'   => esc_html__( 'Section heading level', 'woo-card-chef' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
				),
			)
		);

		$this->add_control(
			'default_open_section',
			array(
				'label'   => esc_html__( 'Default open section', 'woo-card-chef' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'none',
				'options' => array(
					'none'        => esc_html__( 'None (all closed)', 'woo-card-chef' ),
					'description' => esc_html__( 'Description', 'woo-card-chef' ),
					'specs'       => esc_html__( 'Specifications', 'woo-card-chef' ),
					'reviews'     => esc_html__( 'Reviews', 'woo-card-chef' ),
					'faq'         => esc_html__( 'FAQ', 'woo-card-chef' ),
					'manual'      => esc_html__( 'Manual', 'woo-card-chef' ),
				),
			)
		);

		$this->end_controls_section();

		// ----- Description ---------------------------------------------------
		$this->start_controls_section(
			'accordion_description_section',
			array(
				'label' => esc_html__( 'Description', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_description',
			array(
				'label'        => esc_html__( 'Show Description section', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'label_description',
			array(
				'label'     => esc_html__( 'Section label', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Beschrijving', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'show_description' => 'yes' ),
			)
		);

		$this->end_controls_section();

		// ----- Specifications ------------------------------------------------
		$this->start_controls_section(
			'accordion_specs_section',
			array(
				'label' => esc_html__( 'Specifications', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_specs',
			array(
				'label'        => esc_html__( 'Show Specifications section', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'label_specs',
			array(
				'label'     => esc_html__( 'Section label', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Extra informatie', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'show_specs' => 'yes' ),
			)
		);

		$this->end_controls_section();

		// ----- Reviews -------------------------------------------------------
		$this->start_controls_section(
			'accordion_reviews_section',
			array(
				'label' => esc_html__( 'Reviews', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_reviews',
			array(
				'label'        => esc_html__( 'Show Reviews section', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'label_reviews',
			array(
				'label'     => esc_html__( 'Section label', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Beoordelingen', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'show_reviews' => 'yes' ),
			)
		);

		$this->end_controls_section();

		// ----- FAQ -----------------------------------------------------------
		$this->start_controls_section(
			'accordion_faq_section',
			array(
				'label' => esc_html__( 'FAQ', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_faq',
			array(
				'label'        => esc_html__( 'Show FAQ section', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'label_faq',
			array(
				'label'     => esc_html__( 'Section label', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Veelgestelde vragen', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'show_faq' => 'yes' ),
			)
		);

		$this->end_controls_section();

		// ----- Manual --------------------------------------------------------
		$this->start_controls_section(
			'accordion_manual_section',
			array(
				'label' => esc_html__( 'Manual', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_manual',
			array(
				'label'        => esc_html__( 'Show Manual section', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'label_manual',
			array(
				'label'     => esc_html__( 'Section label', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Handleiding', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'show_manual' => 'yes' ),
			)
		);

		$this->add_control(
			'manual_download_label',
			array(
				'label'     => esc_html__( 'Download link label', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Download handleiding', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'show_manual' => 'yes' ),
			)
		);

		$this->add_control(
			'manuals_dir',
			array(
				'label'       => esc_html__( 'Automatic manuals directory', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'manuals',
				'description' => esc_html__( 'Relative to the WordPress root. Used only when the product_manual ACF field is empty. Example: manuals', 'woo-card-chef' ),
				'ai'          => array( 'active' => false ),
				'condition'   => array( 'show_manual' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: dividers, trigger, content typography and colours.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	private function register_style_controls(): void {

		// ----- Divider -------------------------------------------------------
		$this->start_controls_section(
			'accordion_divider_style_section',
			array(
				'label' => esc_html__( 'Divider', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'divider_color',
			array(
				'label'     => esc_html__( 'Divider colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#dddddd',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-accordion__item' => 'border-bottom-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'divider_width',
			array(
				'label'      => esc_html__( 'Divider width', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 4 ) ),
				'default'    => array( 'size' => 1, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-accordion__item' => 'border-bottom-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// ----- Trigger -------------------------------------------------------
		$this->start_controls_section(
			'accordion_trigger_style_section',
			array(
				'label' => esc_html__( 'Trigger', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'trigger_padding',
			array(
				'label'      => esc_html__( 'Trigger padding', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'default'    => array(
					'top'    => 24,
					'right'  => 0,
					'bottom' => 24,
					'left'   => 0,
					'unit'   => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-accordion__trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'trigger_typography',
				'label'    => esc_html__( 'Trigger typography', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-accordion__trigger-text',
			)
		);

		$this->add_control(
			'trigger_color',
			array(
				'label'     => esc_html__( 'Trigger colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-accordion__trigger' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'trigger_color_open',
			array(
				'label'     => esc_html__( 'Trigger colour (open)', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-accordion__item.is-open .wcpce-accordion__trigger' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => esc_html__( 'Icon colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-accordion__icon::before,{{WRAPPER}} .wcpce-accordion__icon::after' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// ----- Content -------------------------------------------------------
		$this->start_controls_section(
			'accordion_content_style_section',
			array(
				'label' => esc_html__( 'Content', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'content_padding',
			array(
				'label'      => esc_html__( 'Content padding', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'default'    => array(
					'top'    => 4,
					'right'  => 0,
					'bottom' => 34,
					'left'   => 22,
					'unit'   => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-accordion__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'content_typography',
				'label'    => esc_html__( 'Content typography', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-accordion__content',
			)
		);

		$this->add_control(
			'content_color',
			array(
				'label'     => esc_html__( 'Content colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-accordion__content' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	// -------------------------------------------------------------------------
	// Validation
	// -------------------------------------------------------------------------

	/**
	 * Validates and sanitises widget settings before render.
	 *
	 * Guards against data corruption from import/export anomalies. All text
	 * labels are sanitised and length-clamped; selects are whitelisted; switchers
	 * are normalised to 'yes' or ''.
	 *
	 * @since 2.4.0
	 * @param array $settings Raw widget settings.
	 * @return array Validated settings.
	 */
	private function validate_accordion_settings( array $settings ): array {

		// Heading tag whitelist.
		$allowed_tags = array( 'h2', 'h3', 'h4' );
		if ( ! in_array( $settings['heading_tag'] ?? 'h3', $allowed_tags, true ) ) {
			$settings['heading_tag'] = 'h3';
		}

		// Default-open section whitelist.
		$allowed_sections = array( 'none', 'description', 'specs', 'reviews', 'faq', 'manual' );
		if ( ! in_array( $settings['default_open_section'] ?? 'none', $allowed_sections, true ) ) {
			$settings['default_open_section'] = 'none';
		}

		// Switchers.
		foreach ( array( 'show_description', 'show_specs', 'show_reviews', 'show_faq', 'show_manual' ) as $key ) {
			$settings[ $key ] = 'yes' === ( $settings[ $key ] ?? 'yes' ) ? 'yes' : '';
		}

		// Text labels.
		$text_defaults = array(
			'label_description'    => __( 'Beschrijving', 'woo-card-chef' ),
			'label_specs'          => __( 'Extra informatie', 'woo-card-chef' ),
			'label_reviews'        => __( 'Beoordelingen', 'woo-card-chef' ),
			'label_faq'            => __( 'Veelgestelde vragen', 'woo-card-chef' ),
			'label_manual'         => __( 'Handleiding', 'woo-card-chef' ),
			'manual_download_label' => __( 'Download handleiding', 'woo-card-chef' ),
		);

		foreach ( $text_defaults as $key => $default ) {
			$raw              = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : (string) $default;
			$settings[ $key ] = $this->clamp_text( $raw, 120 );
		}

		$settings['manuals_dir'] = $this->sanitize_manuals_dir( isset( $settings['manuals_dir'] ) ? (string) $settings['manuals_dir'] : 'manuals' );

		return $settings;
	}

	/**
	 * Sanitises a text value and clamps it to a maximum character length.
	 *
	 * @since 2.4.0
	 * @param string $value      Raw value.
	 * @param int    $max_length Maximum character count.
	 * @return string
	 */
	private function clamp_text( string $value, int $max_length ): string {
		$value = sanitize_text_field( wp_strip_all_tags( $value, true ) );

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, 0, $max_length );
		}

		return substr( $value, 0, $max_length );
	}

	/**
	 * Sanitises the relative directory used for automatic PDF manual matching.
	 *
	 * @since 2.6.8
	 * @param string $value Raw directory setting.
	 * @return string Safe relative directory path.
	 */
	private function sanitize_manuals_dir( string $value ): string {
		$value = wp_normalize_path( sanitize_text_field( wp_strip_all_tags( $value, true ) ) );
		$value = trim( $value, "/ \t\n\r\0\x0B" );

		if ( '' === $value || false !== strpos( $value, '..' ) ) {
			return 'manuals';
		}

		$value = preg_replace( '#[^A-Za-z0-9/_\-\s]#', '', $value );
		$value = trim( (string) $value, "/ \t\n\r\0\x0B" );

		return '' !== $value ? $value : 'manuals';
	}

	// -------------------------------------------------------------------------
	// Product context
	// -------------------------------------------------------------------------

	/**
	 * Resolves the current product from the single-product request context.
	 *
	 * Prefer the queried product before the WooCommerce global because product
	 * loops rendered earlier on the PDP can leave global $product pointing at a
	 * card item instead of the PDP product.
	 *
	 * @since 2.4.0
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
	 * Returns a fallback product for the Elementor editor when no product exists.
	 *
	 * @since 2.4.0
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
	 * Null-safe check for Elementor editor or preview mode.
	 *
	 * @since 2.4.0
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

	// -------------------------------------------------------------------------
	// Section data builders
	// -------------------------------------------------------------------------

	/**
	 * Returns the processed product description HTML, or empty string if none.
	 *
	 * @since 2.4.0
	 * @param \WC_Product $product The product.
	 * @return string
	 */
	private function get_description_content( \WC_Product $product ): string {
		$description = $product->get_description();

		if ( '' === trim( $description ) ) {
			return '';
		}

		// Apply the same processing WooCommerce uses for the description tab.
		if ( function_exists( 'wc_format_long_description' ) ) {
			$description = wc_format_long_description( $description );
		}

		return wpautop( $description );
	}

	/**
	 * Returns the WooCommerce attributes table HTML, or empty string if none.
	 *
	 * Uses WooCommerce's native renderer so dimensions and weight are included
	 * even when the product has no custom visible attributes.
	 *
	 * @since 2.4.0
	 * @param \WC_Product $product The product.
	 * @return string
	 */
	private function get_specs_content( \WC_Product $product ): string {
		// Capture WooCommerce's native attributes table output. This is the same
		// renderer WooCommerce uses for the "Additional information" tab.
		if ( ! function_exists( 'wc_display_product_attributes' ) ) {
			return '';
		}

		ob_start();
		wc_display_product_attributes( $product );
		$output = ob_get_clean();

		if ( ! is_string( $output ) || false === strpos( $output, 'woocommerce-product-attributes' ) ) {
			return '';
		}

		return $output;
	}

	/**
	 * Returns Lipscore review panel content via the WC tab callback, or ''.
	 *
	 * WooCommerce registers product tabs via the woocommerce_product_tabs filter.
	 * We read the tab array once, find the Lipscore tab by key, and call its
	 * callback to capture the output. This mirrors exactly what WC does when
	 * rendering native tabs, so no Lipscore-specific hooks are needed.
	 *
	 * global $product is set to the current product just before the callback
	 * and restored via finally, even if the callback throws. The filter call
	 * that builds the tab list does not need the global to be set.
	 *
	 * @since 2.4.0
	 * @since 2.5.8 Removed redundant set/restore of global $product around the
	 *              woocommerce_product_tabs filter; global is now set once, just
	 *              before the callback, and restored via finally.
	 * @param \WC_Product $wc_product The product.
	 * @return string
	 */
	private function get_reviews_content( \WC_Product $wc_product ): string {
		$tabs = apply_filters( 'woocommerce_product_tabs', array() );

		if ( empty( $tabs ) || ! is_array( $tabs ) ) {
			return '';
		}

		// Find Lipscore tab. Lipscore registers under the key 'lipscorereviews'.
		$lipscore_tab = null;
		foreach ( $tabs as $key => $tab ) {
			if ( 'lipscorereviews' === $key ) {
				$lipscore_tab = $tab;
				break;
			}
		}

		if ( null === $lipscore_tab || empty( $lipscore_tab['callback'] ) || ! is_callable( $lipscore_tab['callback'] ) ) {
			return '';
		}

		// Set WooCommerce product into global context so the tab callback sees
		// the right product, then restore via finally even if the callback throws.
		global $product;
		$prev_product = $product;
		$product      = $wc_product;

		$buffer_level = ob_get_level();
		ob_start();
		try {
			call_user_func( $lipscore_tab['callback'], 'lipscorereviews', $lipscore_tab );
			$output = ob_get_clean();
		} catch ( \Throwable $e ) {
			if ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}
			$output = '';
		} finally {
			$product = $prev_product;
		}

		return is_string( $output ) ? $output : '';
	}

	/**
	 * Returns FAQ items from the ACF product_faq repeater, or empty array.
	 *
	 * The product_faq repeater is registered outside the plugin. Sub-fields are
	 * 'vraag' (text) and 'antwoord' (textarea). We read via get_field() because
	 * there is no flat meta path for repeater rows.
	 *
	 * @since 2.4.0
	 * @param int $product_id The product ID.
	 * @return array<int, array{vraag: string, antwoord: string}>
	 */
	private function get_faq_items( int $product_id ): array {
		if ( ! function_exists( 'get_field' ) ) {
			return array();
		}

		$rows = get_field( 'product_faq', $product_id );

		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return array();
		}

		$items = array();
		foreach ( $rows as $row ) {
			$vraag    = isset( $row['vraag'] ) ? trim( (string) $row['vraag'] ) : '';
			$antwoord = isset( $row['antwoord'] ) ? trim( (string) $row['antwoord'] ) : '';

			if ( '' === $vraag || '' === $antwoord ) {
				continue;
			}

			$items[] = array(
				'vraag'    => $vraag,
				'antwoord' => $antwoord,
			);
		}

		return $items;
	}

	/**
	 * Returns the manual file data array.
	 *
	 * The product_manual ACF field wins when filled. If it is empty, the widget
	 * searches the configured manuals directory for a PDF filename containing
	 * the product SKU/MPN, including a variant with trailing zeroes stripped.
	 *
	 * @since 2.4.0
	 * @since 2.6.8 Adds automatic /manuals PDF fallback matching.
	 * @param \WC_Product $product     The product.
	 * @param string      $manuals_dir Relative manuals directory.
	 * @return array{url: string, filename: string, title: string}|null
	 */
	private function get_manual_file( \WC_Product $product, string $manuals_dir ): ?array {
		$acf_file = $this->get_acf_manual_file( $product->get_id() );
		if ( null !== $acf_file ) {
			return $acf_file;
		}

		return $this->get_auto_manual_file( $product, $manuals_dir );
	}

	/**
	 * Returns the manual file data array from the ACF product_manual field.
	 *
	 * The product_manual field is registered by this plugin (group_wcpce_pdp_accordion).
	 * Return format is 'array', providing url, filename, and title keys.
	 *
	 * @since 2.6.8
	 * @param int $product_id The product ID.
	 * @return array{url: string, filename: string, title: string}|null
	 */
	private function get_acf_manual_file( int $product_id ): ?array {
		if ( ! function_exists( 'get_field' ) ) {
			return null;
		}

		$file = get_field( 'product_manual', $product_id );

		if ( ! is_array( $file ) || empty( $file['url'] ) ) {
			return null;
		}

		return array(
			'url'      => (string) $file['url'],
			'filename' => isset( $file['filename'] ) ? (string) $file['filename'] : '',
			'title'    => isset( $file['title'] ) ? (string) $file['title'] : '',
		);
	}

	/**
	 * Finds the best matching PDF in the configured manuals directory.
	 *
	 * @since 2.6.8
	 * @param \WC_Product $product     The product.
	 * @param string      $manuals_dir Relative manuals directory.
	 * @return array{url: string, filename: string, title: string}|null
	 */
	private function get_auto_manual_file( \WC_Product $product, string $manuals_dir ): ?array {
		$tokens = $this->get_manual_search_tokens( $product );
		if ( empty( $tokens ) ) {
			return null;
		}

		$files = $this->get_manual_pdf_files( $manuals_dir );
		if ( empty( $files ) ) {
			return null;
		}

		foreach ( $tokens as $token ) {
			foreach ( $files as $file ) {
				if ( false === stripos( $file['name'], $token ) ) {
					continue;
				}

				return array(
					'url'      => trailingslashit( site_url( $manuals_dir ) ) . rawurlencode( $file['basename'] ),
					'filename' => $file['basename'],
					'title'    => $file['name'],
				);
			}
		}

		return null;
	}

	/**
	 * Builds SKU/MPN filename search tokens for manual matching.
	 *
	 * @since 2.6.8
	 * @param \WC_Product $product The product.
	 * @return array<int, string>
	 */
	private function get_manual_search_tokens( \WC_Product $product ): array {
		$raw_values = array();
		$sku        = trim( (string) $product->get_sku() );
		if ( '' !== $sku ) {
			$raw_values[] = $sku;
		}

		$meta_keys = apply_filters(
			'wcpce_product_manual_mpn_meta_keys',
			array( 'mpn', '_mpn', 'product_mpn', '_product_mpn', 'woocommerce_gpf_mpn', '_woocommerce_gpf_mpn' )
		);

		if ( is_array( $meta_keys ) ) {
			foreach ( $meta_keys as $meta_key ) {
				$meta_key = sanitize_key( (string) $meta_key );
				if ( '' === $meta_key ) {
					continue;
				}

				$value = trim( (string) $product->get_meta( $meta_key, true ) );
				if ( '' !== $value ) {
					$raw_values[] = $value;
				}
			}
		}

		$tokens = array();
		foreach ( array_unique( $raw_values ) as $value ) {
			$normalised = $this->normalise_manual_search_token( $value );
			if ( '' !== $normalised ) {
				$tokens[] = $normalised;
			}

			$without_two_zeroes = preg_replace( '/00$/', '', $value );
			if ( is_string( $without_two_zeroes ) ) {
				$normalised_without_two_zeroes = $this->normalise_manual_search_token( $without_two_zeroes );
				if ( '' !== $normalised_without_two_zeroes ) {
					$tokens[] = $normalised_without_two_zeroes;
				}
			}

			$without_trailing_zeroes = preg_replace( '/0+$/', '', $value );
			if ( is_string( $without_trailing_zeroes ) ) {
				$normalised_without_trailing_zeroes = $this->normalise_manual_search_token( $without_trailing_zeroes );
				if ( '' !== $normalised_without_trailing_zeroes ) {
					$tokens[] = $normalised_without_trailing_zeroes;
				}
			}
		}

		return array_values( array_unique( $tokens ) );
	}

	/**
	 * Normalises a product code into a safe filename-search token.
	 *
	 * @since 2.6.8
	 * @param string $value Raw product code.
	 * @return string Search token, or empty string when too short.
	 */
	private function normalise_manual_search_token( string $value ): string {
		$value = strtolower( remove_accents( $value ) );
		$value = preg_replace( '/[^a-z0-9]+/', '', $value );

		if ( ! is_string( $value ) || strlen( $value ) < 4 ) {
			return '';
		}

		return $value;
	}

	/**
	 * Returns PDF files from the manuals directory, cached for the current request.
	 *
	 * @since 2.6.8
	 * @param string $manuals_dir Relative manuals directory.
	 * @return array<int, array{basename: string, name: string}>
	 */
	private function get_manual_pdf_files( string $manuals_dir ): array {
		static $cache = array();

		if ( isset( $cache[ $manuals_dir ] ) ) {
			return $cache[ $manuals_dir ];
		}

		$dir_path = trailingslashit( ABSPATH ) . $manuals_dir;
		if ( ! is_dir( $dir_path ) || ! is_readable( $dir_path ) ) {
			$cache[ $manuals_dir ] = array();
			return $cache[ $manuals_dir ];
		}

		$items = scandir( $dir_path );
		if ( ! is_array( $items ) ) {
			$cache[ $manuals_dir ] = array();
			return $cache[ $manuals_dir ];
		}

		$files = array();
		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item || 'pdf' !== strtolower( pathinfo( $item, PATHINFO_EXTENSION ) ) ) {
				continue;
			}

			$name = (string) pathinfo( $item, PATHINFO_FILENAME );
			$files[] = array(
				'basename' => $item,
				'name'     => strtolower( remove_accents( preg_replace( '/[^A-Za-z0-9]+/', '', $name ) ) ),
			);
		}

		usort(
			$files,
			static function ( array $a, array $b ): int {
				return strnatcasecmp( $a['basename'], $b['basename'] );
			}
		);

		$cache[ $manuals_dir ] = $files;
		return $cache[ $manuals_dir ];
	}

	// -------------------------------------------------------------------------
	// Render helpers
	// -------------------------------------------------------------------------

	/**
	 * Outputs the editor-only notice block.
	 *
	 * @since 2.4.0
	 * @param string $message The notice message.
	 * @return void
	 */
	private function render_editor_notice( string $message ): void {
		echo '<div class="wcpce-accordion-editor-notice">' . esc_html( $message ) . '</div>';
	}

	/**
	 * Renders a single accordion item (outer section or inner FAQ row).
	 *
	 * Panels are rendered visible server-side as a no-JS/crawler fallback. After
	 * JS initialises, closed panels receive the HTML `hidden` attribute so their
	 * contents are removed from the tab order and invisible to screen readers.
	 *
	 * @since 2.4.0
	 * @param string $trigger_id   Unique ID for the trigger button.
	 * @param string $panel_id     Unique ID for the panel.
	 * @param string $heading_tag  HTML tag for the heading wrapper (h2/h3/h4).
	 * @param string $label        Visible trigger label.
	 * @param string $content_html Pre-escaped or safe HTML for the panel body.
	 * @param string $extra_class  Optional extra CSS class on the item wrapper.
	 * @param bool   $is_open      Whether this item starts open.
	 * @param string $section_key  Optional section key for JS state restoration.
	 * @return void
	 */
	private function render_accordion_item(
		string $trigger_id,
		string $panel_id,
		string $heading_tag,
		string $label,
		string $content_html,
		string $extra_class = '',
		bool $is_open = false,
		string $section_key = ''
	): void {
		$item_class = 'wcpce-accordion__item' . ( '' !== $extra_class ? ' ' . $extra_class : '' ) . ( $is_open ? ' is-open' : '' );

		echo '<div class="' . esc_attr( $item_class ) . '"';
		if ( '' !== $section_key ) {
			echo ' data-section="' . esc_attr( $section_key ) . '"';
		}
		echo '>';

		// Heading wrapper provides the correct level for screen readers and SEO.
		echo '<' . tag_escape( $heading_tag ) . ' class="wcpce-accordion__heading">';
		echo '<button';
		echo ' class="wcpce-accordion__trigger"';
		echo ' type="button"';
		echo ' id="' . esc_attr( $trigger_id ) . '"';
		echo ' aria-expanded="' . ( $is_open ? 'true' : 'false' ) . '"';
		echo ' aria-controls="' . esc_attr( $panel_id ) . '"';
		echo '>';
		echo '<span class="wcpce-accordion__trigger-text">' . esc_html( $label ) . '</span>';
		echo '<span class="wcpce-accordion__icon" aria-hidden="true"></span>';
		echo '</button>';
		echo '</' . tag_escape( $heading_tag ) . '>';

		// Panel: visible server-side for no-JS/crawler fallback. JS applies
		// hidden to closed panels after initialisation.
		echo '<div';
		echo ' class="wcpce-accordion__content"';
		echo ' id="' . esc_attr( $panel_id ) . '"';
		echo ' role="region"';
		echo ' aria-labelledby="' . esc_attr( $trigger_id ) . '"';
		echo '>';
		// $content_html is trusted output from WC/ACF/wp functions or escaped below.
		echo $content_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Renders the Description section content.
	 *
	 * @since 2.4.0
	 * @param string $content Description HTML from get_description_content().
	 * @return string Safe HTML string.
	 */
	private function build_description_html( string $content ): string {
		return wp_kses_post( $content );
	}

	/**
	 * Renders the Specifications section content.
	 *
	 * @since 2.4.0
	 * @param string $content Specs HTML from get_specs_content().
	 * @return string Safe HTML string.
	 */
	private function build_specs_html( string $content ): string {
		return wp_kses_post( $content );
	}

	/**
	 * Renders the Reviews (Lipscore) section content.
	 *
	 * The captured tab output is sanitised with wp_kses_post(). This strips
	 * any inline <script> tags, but data-* attributes survive kses and
	 * Lipscore renders the panel client-side via its site-wide JS on the
	 * placeholder markup, so no script pass-through is needed. The wrapping
	 * div gets a class so JS and CSS can target the panel.
	 *
	 * @since 2.4.0
	 * @param string $content Reviews HTML from get_reviews_content().
	 * @return string Safe HTML string.
	 */
	private function build_reviews_html( string $content ): string {
		return '<div class="wcpce-accordion__lipscore-panel">' . wp_kses_post( $content ) . '</div>';
	}

	/**
	 * Builds the FAQ inner-accordion HTML from FAQ items.
	 *
	 * Each vraag/antwoord pair is its own nested accordion item using the same
	 * accessible pattern (button, aria-expanded, aria-controls, hidden panel).
	 * Inner triggers use h4 regardless of the outer heading level to keep
	 * hierarchy sensible and to avoid nesting h3 inside h3.
	 *
	 * @since 2.4.0
	 * @param array<int, array{vraag: string, antwoord: string}> $items FAQ items.
	 * @param string                                             $widget_id Widget instance ID.
	 * @return string Safe HTML string.
	 */
	private function build_faq_html( array $items, string $widget_id ): string {
		if ( empty( $items ) ) {
			return '';
		}

		$html = '<div class="wcpce-accordion__faq">';

		foreach ( $items as $index => $item ) {
			$trigger_id = 'wcpce-faq-trigger-' . esc_attr( $widget_id ) . '-' . $index;
			$panel_id   = 'wcpce-faq-panel-' . esc_attr( $widget_id ) . '-' . $index;

			$html .= '<div class="wcpce-accordion__faq-item is-open">';
			$html .= '<h4 class="wcpce-accordion__faq-heading">';
			$html .= '<button';
			$html .= ' class="wcpce-accordion__faq-trigger"';
			$html .= ' type="button"';
			$html .= ' id="' . esc_attr( $trigger_id ) . '"';
			$html .= ' aria-expanded="true"';
			$html .= ' aria-controls="' . esc_attr( $panel_id ) . '"';
			$html .= '>';
			$html .= '<span class="wcpce-accordion__faq-icon" aria-hidden="true"></span>';
			$html .= '<span class="wcpce-accordion__faq-question">' . esc_html( $item['vraag'] ) . '</span>';
			$html .= '</button>';
			$html .= '</h4>';
			$html .= '<div';
			$html .= ' class="wcpce-accordion__faq-answer"';
			$html .= ' id="' . esc_attr( $panel_id ) . '"';
			$html .= ' role="region"';
			$html .= ' aria-labelledby="' . esc_attr( $trigger_id ) . '"';
			$html .= '>';
			$html .= '<p>' . wp_kses_post( nl2br( esc_html( $item['antwoord'] ) ) ) . '</p>';
			$html .= '</div>';
			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Builds the Manual section content HTML.
	 *
	 * @since 2.4.0
	 * @param array{url: string, filename: string, title: string} $file   ACF file data.
	 * @param string                                              $label  Download link label.
	 * @return string Safe HTML string.
	 */
	private function build_manual_html( array $file, string $label ): string {
		$url = esc_url( $file['url'] );
		if ( '' === $url ) {
			return '';
		}

		$link_text = '' !== $label ? $label : __( 'Download handleiding', 'woo-card-chef' );

		return '<p class="wcpce-accordion__manual-download">'
			. '<a href="' . $url . '" class="wcpce-accordion__manual-link" target="_blank" rel="noopener noreferrer">'
			. '<svg class="wcpce-accordion__manual-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false">'
			. '<path d="M9 1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V5L9 1Zm0 1.5L12.5 5H9V2.5ZM4 14V2h4v4h4v8H4Z" fill="currentColor"/>'
			. '<path d="M5.5 8.5h5v1h-5zm0 2h5v1h-5zm0 2h3v1h-3z" fill="currentColor"/>'
			. '</svg>'
			. esc_html( $link_text )
			. '</a>'
			. '</p>';
	}

	// -------------------------------------------------------------------------
	// Render
	// -------------------------------------------------------------------------

	/**
	 * Renders the widget on the front end and in the Elementor editor preview.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	protected function render(): void {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$settings  = $this->validate_accordion_settings( $this->get_settings_for_display() );
		$is_editor = $this->is_elementor_editor_or_preview();
		$widget_id = $this->get_id();

		$product = $this->get_current_product();
		if ( null === $product && $is_editor ) {
			$product = $this->get_editor_fallback_product();
		}

		if ( null === $product ) {
			if ( $is_editor ) {
				$this->render_editor_notice( __( 'No product context found. Assign this template to a product or open a product page to preview.', 'woo-card-chef' ) );
			}
			return;
		}

		$product_id       = $product->get_id();
		$default_open     = $settings['default_open_section'] ?? 'none';
		$heading_tag      = $settings['heading_tag'] ?? 'h3';

		// Collect sections in fixed order.
		$sections = array();

		// 1. Description.
		if ( 'yes' === ( $settings['show_description'] ?? 'yes' ) ) {
			$content = $this->get_description_content( $product );
			if ( '' !== $content ) {
				$sections[] = array(
					'key'     => 'description',
					'label'   => $settings['label_description'],
					'content' => $this->build_description_html( $content ),
				);
			}
		}

		// 2. Specifications.
		if ( 'yes' === ( $settings['show_specs'] ?? 'yes' ) ) {
			$content = $this->get_specs_content( $product );
			if ( '' !== $content ) {
				$sections[] = array(
					'key'     => 'specs',
					'label'   => $settings['label_specs'],
					'content' => $this->build_specs_html( $content ),
				);
			}
		}

		// 3. Reviews (Lipscore).
		if ( 'yes' === ( $settings['show_reviews'] ?? 'yes' ) ) {
			$content = $this->get_reviews_content( $product );
			if ( '' !== $content ) {
				$sections[] = array(
					'key'     => 'reviews',
					'label'   => $settings['label_reviews'],
					'content' => $this->build_reviews_html( $content ),
				);
			}
		}

		// 4. FAQ.
		if ( 'yes' === ( $settings['show_faq'] ?? 'yes' ) ) {
			$faq_items = $this->get_faq_items( $product_id );
			if ( ! empty( $faq_items ) ) {
				$sections[] = array(
					'key'     => 'faq',
					'label'   => $settings['label_faq'],
					'content' => $this->build_faq_html( $faq_items, $widget_id ),
				);
			}
		}

		// 5. Manual.
		if ( 'yes' === ( $settings['show_manual'] ?? 'yes' ) ) {
			$file = $this->get_manual_file( $product, $settings['manuals_dir'] ?? 'manuals' );
			if ( null !== $file ) {
				$sections[] = array(
					'key'     => 'manual',
					'label'   => $settings['label_manual'],
					'content' => $this->build_manual_html( $file, $settings['manual_download_label'] ?? '' ),
				);
			}
		}

		if ( empty( $sections ) ) {
			if ( $is_editor ) {
				$this->render_editor_notice( __( 'No accordion content is available for this product. Enable sections and add product content to see the accordion.', 'woo-card-chef' ) );
			}
			return;
		}

		// Output the accordion wrapper. data-widget-id lets the JS scope itself
		// to this instance; data-default-open lets JS restore the configured
		// collapsed state after the no-JS fallback has been rendered.
		echo '<div class="wcpce-accordion" data-widget-id="' . esc_attr( $widget_id ) . '" data-default-open="' . esc_attr( $default_open ) . '">';

		foreach ( $sections as $index => $section ) {
			$key        = $section['key'];
			$trigger_id = 'wcpce-accordion-trigger-' . esc_attr( $widget_id ) . '-' . $index;
			$panel_id   = 'wcpce-accordion-panel-' . esc_attr( $widget_id ) . '-' . $index;
			// Render open server-side for no-JS/crawler fallback. JS applies the
			// configured default-open state during initialisation.
			$is_open    = true;
			// The per-section modifier class (e.g. wcpce-accordion__item--reviews)
			// is all JS and CSS need; JS targets the reviews section via the
			// data-section attribute set in render_accordion_item().
			$extra_class = 'wcpce-accordion__item--' . esc_attr( $key );

			$this->render_accordion_item(
				$trigger_id,
				$panel_id,
				$heading_tag,
				$section['label'],
				$section['content'],
				$extra_class,
				$is_open,
				$key
			);
		}

		echo '</div>';
	}
}
