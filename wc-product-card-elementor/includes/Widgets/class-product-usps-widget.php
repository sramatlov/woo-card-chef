<?php
/**
 * PDP Product USP / Benefits widget for Elementor.
 *
 * Renders short, scan-friendly product benefits on the product detail page.
 * Content belongs in ACF or WooCommerce product content; presentation belongs
 * in Elementor controls.
 *
 * Feature set (v2.2.0 / PDP Phase 3):
 * - PDP-specific ACF repeater `pdp_usps` with one text field per row: `usp_text`
 * - Automatic fallback chain: PDP USPs -> short description -> product card USPs
 * - Optional global icon, layout modes, responsive columns and style controls
 * - Server-side only, zero JS
 *
 * @package WC_Product_Card_Elementor
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PDP Product USP / Benefits Elementor widget.
 *
 * @since 2.2.0
 */
class WCPCE_Product_USPs_Widget extends \Elementor\Widget_Base {

	// -------------------------------------------------------------------------
	// Elementor identity & dependencies
	// -------------------------------------------------------------------------

	/**
	 * Returns the unique widget slug.
	 *
	 * @since 2.2.0
	 * @return string
	 */
	public function get_name(): string {
		return 'wcpce_product_usps';
	}

	/**
	 * Returns the widget label shown in the Elementor panel.
	 *
	 * @since 2.2.0
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'Product USP / Benefits (PDP)', 'woo-card-chef' );
	}

	/**
	 * Returns the Elementor icon class for the widget panel.
	 *
	 * @since 2.2.0
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-check-circle';
	}

	/**
	 * Returns the widget category slugs shown in the Elementor panel.
	 *
	 * @since 2.2.0
	 * @return array<string>
	 */
	public function get_categories(): array {
		return array( 'custom-woocommerce' );
	}

	/**
	 * Returns search keywords for the Elementor panel.
	 *
	 * @since 2.2.0
	 * @return array<string>
	 */
	public function get_keywords(): array {
		return array( 'usp', 'benefits', 'selling points', 'product', 'woocommerce', 'pdp' );
	}

	/**
	 * Returns the stylesheet handles this widget depends on.
	 *
	 * @since 2.2.0
	 * @return array<string>
	 */
	public function get_style_depends(): array {
		return array( 'wcpce-product-usps' );
	}

	/**
	 * Returns the script handles this widget depends on.
	 *
	 * @since 2.2.0
	 * @return array<string>
	 */
	public function get_script_depends(): array {
		return array();
	}

	// -------------------------------------------------------------------------
	// Elementor controls
	// -------------------------------------------------------------------------

	/**
	 * Registers all Elementor controls for this widget.
	 *
	 * @since 2.2.0
	 * @return void
	 */
	protected function register_controls(): void {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	/**
	 * Content tab: source, heading, layout and icon choices.
	 *
	 * @since 2.2.0
	 * @return void
	 */
	private function register_content_controls(): void {
		$this->start_controls_section(
			'usps_content_section',
			array(
				'label' => esc_html__( 'USP content', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'source_mode',
			array(
				'label'       => esc_html__( 'Content source', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'              => esc_html__( 'Auto: PDP USPs, then fallback', 'woo-card-chef' ),
					'pdp_usps'          => esc_html__( 'PDP USP repeater only', 'woo-card-chef' ),
					'short_description' => esc_html__( 'Short description only', 'woo-card-chef' ),
					'card_usps'         => esc_html__( 'Product card USPs only', 'woo-card-chef' ),
				),
				'description' => esc_html__( 'ACF stores only the USP text. Layout and styling stay in Elementor.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'max_items',
			array(
				'label'   => esc_html__( 'Maximum USP items', 'woo-card-chef' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 4,
				'min'     => 1,
				'max'     => 8,
			)
		);

		$this->add_control(
			'heading_text',
			array(
				'label'       => esc_html__( 'Optional heading', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'Waarom dit product?', 'woo-card-chef' ),
				'ai'          => array( 'active' => false ),
			)
		);

		$this->add_control(
			'heading_tag',
			array(
				'label'     => esc_html__( 'Heading tag', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'h3',
				'options'   => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'h5'  => 'H5',
					'h6'  => 'H6',
					'div' => 'div',
				),
				'condition' => array( 'heading_text!' => '' ),
			)
		);

		$this->add_control(
			'layout_mode',
			array(
				'label'   => esc_html__( 'Layout', 'woo-card-chef' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'list',
				'options' => array(
					'list'   => esc_html__( 'List', 'woo-card-chef' ),
					'cards'  => esc_html__( 'Compact cards', 'woo-card-chef' ),
					'inline' => esc_html__( 'Inline row', 'woo-card-chef' ),
				),
			)
		);

		$this->add_control(
			'show_icons',
			array(
				'label'        => esc_html__( 'Show icon', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'icon_style',
			array(
				'label'     => esc_html__( 'Icon style', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'check',
				'options'   => array(
					'check' => esc_html__( 'Checkmark', 'woo-card-chef' ),
					'dot'   => esc_html__( 'Dot', 'woo-card-chef' ),
				),
				'condition' => array( 'show_icons' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: layout, colours and typography.
	 *
	 * @since 2.2.0
	 * @return void
	 */
	private function register_style_controls(): void {
		$this->start_controls_section(
			'usps_layout_style_section',
			array(
				'label' => esc_html__( 'Layout', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'     => esc_html__( 'Columns', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 1,
				'min'       => 1,
				'max'       => 4,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-usps' => '--wcpce-usp-columns: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'item_row_gap',
			array(
				'label'      => esc_html__( 'Row gap', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-usps__list' => 'row-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'item_column_gap',
			array(
				'label'      => esc_html__( 'Column gap', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-usps__list' => 'column-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'item_padding',
			array(
				'label'      => esc_html__( 'Item padding', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-usps__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'text_align',
			array(
				'label'     => esc_html__( 'Alignment', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'woo-card-chef' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'woo-card-chef' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'woo-card-chef' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .wcpce-usps' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'usps_text_style_section',
			array(
				'label' => esc_html__( 'Text', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'heading_typography',
				'label'    => esc_html__( 'Heading typography', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-usps__heading',
			)
		);

		$this->add_control(
			'heading_color',
			array(
				'label'     => esc_html__( 'Heading colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-usps__heading' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'heading_spacing',
			array(
				'label'      => esc_html__( 'Heading spacing', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-usps__heading' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'item_typography',
				'label'    => esc_html__( 'USP typography', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-usps__text',
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'     => esc_html__( 'USP text colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-usps__text' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'usps_item_style_section',
			array(
				'label' => esc_html__( 'Items', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => esc_html__( 'Icon colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-usps__icon' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'icon_size',
			array(
				'label'      => esc_html__( 'Icon size', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 32 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-usps__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'item_background',
			array(
				'label'     => esc_html__( 'Item background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-usps__item' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'item_border',
				'label'    => esc_html__( 'Item border', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-usps__item',
			)
		);

		$this->add_control(
			'item_radius',
			array(
				'label'      => esc_html__( 'Item border radius', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 24 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-usps__item' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'item_shadow',
				'label'    => esc_html__( 'Item shadow', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-usps__item',
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
	 * @since 2.2.0
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
	 * Checks whether ACF Pro repeater support is available.
	 *
	 * @since 2.2.0
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
	 * Validates and sanitizes widget settings before render.
	 *
	 * @since 2.2.0
	 * @param array $settings Raw widget settings.
	 * @return array Validated settings.
	 */
	private function validate_usp_settings( array $settings ): array {
		$source = $settings['source_mode'] ?? 'auto';
		if ( ! in_array( $source, array( 'auto', 'pdp_usps', 'short_description', 'card_usps' ), true ) ) {
			$source = 'auto';
		}
		$settings['source_mode'] = $source;

		$layout = $settings['layout_mode'] ?? 'list';
		if ( ! in_array( $layout, array( 'list', 'cards', 'inline' ), true ) ) {
			$layout = 'list';
		}
		$settings['layout_mode'] = $layout;

		$icon = $settings['icon_style'] ?? 'check';
		if ( ! in_array( $icon, array( 'check', 'dot' ), true ) ) {
			$icon = 'check';
		}
		$settings['icon_style'] = $icon;

		$tag = $settings['heading_tag'] ?? 'h3';
		if ( ! in_array( $tag, array( 'h2', 'h3', 'h4', 'h5', 'h6', 'div' ), true ) ) {
			$tag = 'h3';
		}
		$settings['heading_tag'] = $tag;

		$settings['max_items']    = max( 1, min( 8, isset( $settings['max_items'] ) ? absint( $settings['max_items'] ) : 4 ) );
		$settings['heading_text'] = isset( $settings['heading_text'] ) ? $this->clamp_text( (string) $settings['heading_text'], 80 ) : '';

		return $settings;
	}

	/**
	 * Sanitizes a text value and clamps it to a maximum character length.
	 *
	 * @since 2.4.1
	 * @param string $value      Raw text value.
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
	 * Resolves the current product from the single-product request context.
	 *
	 * Prefer the queried product before the WooCommerce global because product
	 * loops rendered earlier on the PDP can leave global $product pointing at a
	 * card item instead of the PDP product.
	 *
	 * @since 2.2.0
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
	 * Returns a fallback product for the Elementor editor when no product context exists.
	 *
	 * @since 2.2.0
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
	 * Sanitises one USP line.
	 *
	 * @since 2.2.0
	 * @param string $value Raw text value.
	 * @return string
	 */
	private function sanitize_usp_text( string $value ): string {
		return $this->clamp_text( $value, 140 );
	}

	/**
	 * Limits a USP list to the configured maximum and removes empty lines.
	 *
	 * @since 2.2.0
	 * @param array $items Raw USP text lines.
	 * @param int   $max   Maximum number of lines.
	 * @return array<int, string>
	 */
	private function limit_usps( array $items, int $max ): array {
		$clean = array();

		foreach ( $items as $item ) {
			$text = $this->sanitize_usp_text( (string) $item );
			if ( '' === $text ) {
				continue;
			}

			$clean[] = $text;
			if ( count( $clean ) >= $max ) {
				break;
			}
		}

		return $clean;
	}

	/**
	 * Reads PDP-specific USP rows from the ACF Pro repeater.
	 *
	 * @since 2.2.0
	 * @param int $product_id Product ID.
	 * @param int $max        Maximum items.
	 * @return array<int, string>
	 */
	private function get_pdp_usps( int $product_id, int $max ): array {
		if ( ! function_exists( 'get_field' ) || ! self::acf_pro_active() ) {
			return array();
		}

		$rows = get_field( 'pdp_usps', $product_id );
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$items = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$items[] = $row['usp_text'] ?? '';
		}

		return $this->limit_usps( $items, $max );
	}

	/**
	 * Splits the WooCommerce short description into USP-like lines.
	 *
	 * @since 2.2.0
	 * @param \WC_Product $product The product.
	 * @param int         $max     Maximum items.
	 * @return array<int, string>
	 */
	private function get_short_description_usps( \WC_Product $product, int $max ): array {
		$html = (string) $product->get_short_description();
		if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
			return array();
		}

		$html = preg_replace( '#<br\s*/?>#i', "\n", $html );
		$html = preg_replace( '#<(li|p|div)\b[^>]*>#i', "\n", (string) $html );
		$html = preg_replace( '#</(li|p|div)>#i', "\n", (string) $html );
		$text = wp_strip_all_tags( (string) $html, false );
		$rows = preg_split( '/\r\n|\r|\n/', $text );

		if ( ! is_array( $rows ) || empty( $rows ) ) {
			$rows = array( $text );
		}

		return $this->limit_usps( $rows, $max );
	}

	/**
	 * Reads the existing short product-card USP fields as a final fallback.
	 *
	 * @since 2.2.0
	 * @param int $product_id Product ID.
	 * @param int $max        Maximum items.
	 * @return array<int, string>
	 */
	private function get_card_usps( int $product_id, int $max ): array {
		$acf_data = WCPCE_ACF_Helper::get_card_data( $product_id );
		return $this->limit_usps(
			array(
				$acf_data['usp_1'] ?? '',
				$acf_data['usp_2'] ?? '',
				$acf_data['usp_3'] ?? '',
			),
			$max
		);
	}

	/**
	 * Resolves USP lines based on the selected source mode.
	 *
	 * @since 2.2.0
	 * @param \WC_Product $product  The product.
	 * @param array       $settings Widget settings.
	 * @return array<int, string>
	 */
	private function get_usps( \WC_Product $product, array $settings ): array {
		$max    = (int) ( $settings['max_items'] ?? 4 );
		$source = $settings['source_mode'] ?? 'auto';

		if ( 'pdp_usps' === $source ) {
			return $this->get_pdp_usps( $product->get_id(), $max );
		}

		if ( 'short_description' === $source ) {
			return $this->get_short_description_usps( $product, $max );
		}

		if ( 'card_usps' === $source ) {
			return $this->get_card_usps( $product->get_id(), $max );
		}

		// Auto: evaluate each source only if the previous one returned nothing.
		// Sequential assignments — not an array literal — so each fetch is
		// skipped as soon as an earlier source provides data.
		$items = $this->get_pdp_usps( $product->get_id(), $max );
		if ( empty( $items ) ) {
			$items = $this->get_short_description_usps( $product, $max );
		}
		if ( empty( $items ) ) {
			$items = $this->get_card_usps( $product->get_id(), $max );
		}

		return $items;
	}

	/**
	 * Outputs editor-only notices inside the widget.
	 *
	 * @since 2.2.0
	 * @param array             $settings Widget settings.
	 * @param \WC_Product|null  $product  The resolved product.
	 * @param array<int,string> $items    Resolved USP items.
	 * @return void
	 */
	private function render_editor_notices( array $settings, ?\WC_Product $product, array $items ): void {
		if ( ! $this->is_elementor_editor_or_preview() ) {
			return;
		}

		if ( null === $product ) {
			echo '<div class="wcpce-usps-editor-notice">';
			echo esc_html__( 'No product context found. Assign this template to a product or open a product page to preview.', 'woo-card-chef' );
			echo '</div>';
			return;
		}

		if ( 'pdp_usps' === ( $settings['source_mode'] ?? 'auto' ) && ! self::acf_pro_active() ) {
			echo '<div class="wcpce-usps-editor-notice">';
			echo esc_html__( 'PDP USP fields use an ACF Pro repeater. Install/activate ACF Pro or choose a fallback source.', 'woo-card-chef' );
			echo '</div>';
			return;
		}

		if ( empty( $items ) ) {
			echo '<div class="wcpce-usps-editor-notice">';
			echo esc_html__( 'No USP content found for this product.', 'woo-card-chef' );
			echo '</div>';
		}
	}

	/**
	 * Renders the selected item icon.
	 *
	 * @since 2.2.0
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private function render_icon( array $settings ): void {
		if ( 'yes' !== ( $settings['show_icons'] ?? 'yes' ) ) {
			return;
		}

		$style = $settings['icon_style'] ?? 'check';
		echo '<span class="wcpce-usps__icon wcpce-usps__icon--' . esc_attr( $style ) . '" aria-hidden="true">';
		if ( 'dot' !== $style ) {
			echo '<svg viewBox="0 0 16 16" focusable="false"><path d="M6.2 11.4 2.8 8l1.4-1.4 2 2 5.6-5.8 1.4 1.4z" fill="currentColor"/></svg>';
		}
		echo '</span>';
	}

	// -------------------------------------------------------------------------
	// Render
	// -------------------------------------------------------------------------

	/**
	 * Renders the widget on the front end and in the editor preview.
	 *
	 * @since 2.2.0
	 * @return void
	 */
	protected function render(): void {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$settings  = $this->validate_usp_settings( $this->get_settings_for_display() );
		$is_editor = $this->is_elementor_editor_or_preview();

		$product = $this->get_current_product();
		if ( null === $product && $is_editor ) {
			$product = $this->get_editor_fallback_product();
		}

		$items = null === $product ? array() : $this->get_usps( $product, $settings );
		$this->render_editor_notices( $settings, $product, $items );

		if ( null === $product || empty( $items ) ) {
			return;
		}

		$layout  = $settings['layout_mode'] ?? 'list';
		$classes = array( 'wcpce-usps', 'wcpce-usps--' . $layout );
		if ( 'yes' !== ( $settings['show_icons'] ?? 'yes' ) ) {
			$classes[] = 'wcpce-usps--no-icons';
		}

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';

		if ( ! empty( $settings['heading_text'] ) ) {
			$tag = $settings['heading_tag'] ?? 'h3';
			echo '<' . tag_escape( $tag ) . ' class="wcpce-usps__heading">' . esc_html( $settings['heading_text'] ) . '</' . tag_escape( $tag ) . '>';
		}

		echo '<ul class="wcpce-usps__list" role="list">';
		foreach ( $items as $item ) {
			echo '<li class="wcpce-usps__item">';
			$this->render_icon( $settings );
			echo '<span class="wcpce-usps__text">' . esc_html( $item ) . '</span>';
			echo '</li>';
		}
		echo '</ul>';

		echo '</div>';
	}
}
