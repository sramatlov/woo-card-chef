<?php
/**
 * PDP Delivery & Availability widget for Elementor.
 *
 * Renders the key availability and delivery promises near the buy section on a
 * WooCommerce product detail page. Product-specific status comes from existing
 * WooCommerce/ACF data; wording, threshold and presentation stay in Elementor.
 *
 * Feature set (v2.3.0 / PDP Phase 4):
 * - Stock-aware line: in stock, temporarily out of stock, or discontinued
 * - Global delivery promise text controlled in Elementor
 * - Free-shipping line switches between "Gratis bezorging" and
 *   "Gratis bezorging vanaf EUR X" based on the existing price helper
 * - Server-side only, zero JS
 *
 * @package WC_Product_Card_Elementor
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PDP Delivery & Availability Elementor widget.
 *
 * @since 2.3.0
 */
class WCPCE_Product_Delivery_Widget extends \Elementor\Widget_Base {

	// -------------------------------------------------------------------------
	// Elementor identity & dependencies
	// -------------------------------------------------------------------------

	/**
	 * Returns the unique widget slug.
	 *
	 * @since 2.3.0
	 * @return string
	 */
	public function get_name(): string {
		return 'wcpce_product_delivery';
	}

	/**
	 * Returns the widget label shown in the Elementor panel.
	 *
	 * @since 2.3.0
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'Product Delivery & Availability (PDP)', 'woo-card-chef' );
	}

	/**
	 * Returns the Elementor icon class for the widget panel.
	 *
	 * @since 2.3.0
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-product-info';
	}

	/**
	 * Returns the widget category slugs shown in the Elementor panel.
	 *
	 * @since 2.3.0
	 * @return array<string>
	 */
	public function get_categories(): array {
		return array( 'custom-woocommerce' );
	}

	/**
	 * Returns search keywords for the Elementor panel.
	 *
	 * @since 2.3.0
	 * @return array<string>
	 */
	public function get_keywords(): array {
		return array( 'delivery', 'shipping', 'stock', 'availability', 'product', 'woocommerce', 'pdp' );
	}

	/**
	 * Returns the stylesheet handles this widget depends on.
	 *
	 * @since 2.3.0
	 * @return array<string>
	 */
	public function get_style_depends(): array {
		return array( 'wcpce-product-delivery' );
	}

	/**
	 * Returns the script handles this widget depends on.
	 *
	 * @since 2.3.0
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
	 * @since 2.3.0
	 * @return void
	 */
	protected function register_controls(): void {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	/**
	 * Content tab: availability, delivery and shipping text.
	 *
	 * @since 2.3.0
	 * @return void
	 */
	private function register_content_controls(): void {
		$this->start_controls_section(
			'delivery_content_section',
			array(
				'label' => esc_html__( 'Delivery & availability', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_stock_status',
			array(
				'label'        => esc_html__( 'Show stock status', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'in_stock_label',
			array(
				'label'     => esc_html__( 'In-stock label', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Op voorraad', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'show_stock_status' => 'yes' ),
			)
		);

		$this->add_control(
			'out_of_stock_label',
			array(
				'label'     => esc_html__( 'Out-of-stock label', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Tijdelijk uitverkocht', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'show_stock_status' => 'yes' ),
			)
		);

		$this->add_control(
			'discontinued_label',
			array(
				'label'     => esc_html__( 'Discontinued label', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Niet meer leverbaar', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'show_stock_status' => 'yes' ),
			)
		);

		$this->add_control(
			'delivery_heading',
			array(
				'label'     => esc_html__( 'Delivery promise', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'show_delivery_line',
			array(
				'label'        => esc_html__( 'Show delivery line', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'delivery_text',
			array(
				'label'       => esc_html__( 'Delivery text', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'Voor 23:00 besteld, morgen in huis', 'woo-card-chef' ),
				'label_block' => true,
				'ai'          => array( 'active' => false ),
				'condition'   => array( 'show_delivery_line' => 'yes' ),
			)
		);

		$this->add_control(
			'show_out_of_stock_note',
			array(
				'label'        => esc_html__( 'Show out-of-stock delivery note', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'show_delivery_line' => 'yes' ),
			)
		);

		$this->add_control(
			'out_of_stock_delivery_text',
			array(
				'label'       => esc_html__( 'Out-of-stock delivery text', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'Niet direct leverbaar', 'woo-card-chef' ),
				'label_block' => true,
				'ai'          => array( 'active' => false ),
				'condition'   => array(
					'show_delivery_line'     => 'yes',
					'show_out_of_stock_note' => 'yes',
				),
			)
		);

		$this->add_control(
			'shipping_heading',
			array(
				'label'     => esc_html__( 'Shipping threshold', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'show_shipping_line',
			array(
				'label'        => esc_html__( 'Show shipping line', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'shipping_threshold',
			array(
				'label'       => esc_html__( 'Free-shipping threshold', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => 25,
				'min'         => 0,
				'max'         => 1000,
				'step'        => 0.01,
				'description' => esc_html__( 'When the current display price is below this value, the widget shows the threshold line instead of claiming free shipping.', 'woo-card-chef' ),
				'condition'   => array( 'show_shipping_line' => 'yes' ),
			)
		);

		$this->add_control(
			'free_shipping_label',
			array(
				'label'       => esc_html__( 'Free-shipping text', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'Gratis bezorging', 'woo-card-chef' ),
				'label_block' => true,
				'ai'          => array( 'active' => false ),
				'condition'   => array( 'show_shipping_line' => 'yes' ),
			)
		);

		$this->add_control(
			'shipping_threshold_prefix',
			array(
				'label'       => esc_html__( 'Below-threshold text', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'Gratis bezorging vanaf', 'woo-card-chef' ),
				'label_block' => true,
				'ai'          => array( 'active' => false ),
				'condition'   => array( 'show_shipping_line' => 'yes' ),
			)
		);

		$this->add_control(
			'layout_heading',
			array(
				'label'     => esc_html__( 'Layout', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'layout_mode',
			array(
				'label'   => esc_html__( 'Layout', 'woo-card-chef' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'list',
				'options' => array(
					'list'  => esc_html__( 'List', 'woo-card-chef' ),
					'pills' => esc_html__( 'Compact pills', 'woo-card-chef' ),
				),
			)
		);

		$this->add_control(
			'show_icons',
			array(
				'label'        => esc_html__( 'Show icons', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: spacing, typography, colours and item frame.
	 *
	 * @since 2.3.0
	 * @return void
	 */
	private function register_style_controls(): void {
		$this->start_controls_section(
			'delivery_layout_style_section',
			array(
				'label' => esc_html__( 'Layout', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'item_gap',
			array(
				'label'      => esc_html__( 'Item gap', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 32 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-delivery__list' => 'gap: {{SIZE}}{{UNIT}};',
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
					'{{WRAPPER}} .wcpce-delivery__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .wcpce-delivery' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'delivery_text_style_section',
			array(
				'label' => esc_html__( 'Text', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typography',
				'label'    => esc_html__( 'Typography', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-delivery__text',
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'     => esc_html__( 'Text colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-delivery__text' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'delivery_icon_style_section',
			array(
				'label'     => esc_html__( 'Icons', 'woo-card-chef' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_icons' => 'yes' ),
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
					'{{WRAPPER}} .wcpce-delivery__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => esc_html__( 'Default icon colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-delivery__icon' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'stock_icon_color',
			array(
				'label'     => esc_html__( 'In-stock icon colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-delivery__item--stock .wcpce-delivery__icon' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'unavailable_icon_color',
			array(
				'label'     => esc_html__( 'Unavailable icon colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-delivery__item--unavailable .wcpce-delivery__icon, {{WRAPPER}} .wcpce-delivery__item--discontinued .wcpce-delivery__icon' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'delivery_item_style_section',
			array(
				'label' => esc_html__( 'Items', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'item_background',
			array(
				'label'     => esc_html__( 'Item background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-delivery__item' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'item_border',
				'label'    => esc_html__( 'Item border', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-delivery__item',
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
					'{{WRAPPER}} .wcpce-delivery__item' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'item_shadow',
				'label'    => esc_html__( 'Item shadow', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-delivery__item',
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
	 * @since 2.3.0
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
	 * Validates and sanitises widget settings before render.
	 *
	 * @since 2.3.0
	 * @param array $settings Raw widget settings.
	 * @return array Validated settings.
	 */
	private function validate_delivery_settings( array $settings ): array {
		$layout = $settings['layout_mode'] ?? 'list';
		if ( ! in_array( $layout, array( 'list', 'pills' ), true ) ) {
			$layout = 'list';
		}
		$settings['layout_mode'] = $layout;

		foreach (
			array(
				'show_stock_status',
				'show_delivery_line',
				'show_out_of_stock_note',
				'show_shipping_line',
				'show_icons',
			) as $switch_key
		) {
			$settings[ $switch_key ] = 'yes' === ( $settings[ $switch_key ] ?? 'yes' ) ? 'yes' : '';
		}

		$settings['shipping_threshold'] = max( 0.0, min( 1000.0, isset( $settings['shipping_threshold'] ) ? (float) $settings['shipping_threshold'] : 25.0 ) );

		$text_defaults = array(
			'in_stock_label'             => __( 'Op voorraad', 'woo-card-chef' ),
			'out_of_stock_label'         => __( 'Tijdelijk uitverkocht', 'woo-card-chef' ),
			'discontinued_label'         => __( 'Niet meer leverbaar', 'woo-card-chef' ),
			'delivery_text'              => __( 'Voor 23:00 besteld, morgen in huis', 'woo-card-chef' ),
			'out_of_stock_delivery_text' => __( 'Niet direct leverbaar', 'woo-card-chef' ),
			'free_shipping_label'        => __( 'Gratis bezorging', 'woo-card-chef' ),
			'shipping_threshold_prefix'  => __( 'Gratis bezorging vanaf', 'woo-card-chef' ),
		);

		foreach (
			array(
				'in_stock_label'             => 80,
				'out_of_stock_label'         => 80,
				'discontinued_label'         => 80,
				'delivery_text'              => 140,
				'out_of_stock_delivery_text' => 140,
				'free_shipping_label'        => 100,
				'shipping_threshold_prefix'  => 100,
			) as $key => $max_length
		) {
			$value            = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : (string) $text_defaults[ $key ];
			$settings[ $key ] = $this->clamp_text( $value, $max_length );
		}

		return $settings;
	}

	/**
	 * Sanitises a text field and clamps it to a safe display length.
	 *
	 * @since 2.3.0
	 * @param string $value      Raw value.
	 * @param int    $max_length Maximum character length.
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
	 * @since 2.3.0
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
	 * @since 2.3.0
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
	 * Returns the product availability state.
	 *
	 * @since 2.3.0
	 * @param \WC_Product $product The product.
	 * @return string One of in_stock, out_of_stock, discontinued.
	 */
	private function get_availability_state( \WC_Product $product ): string {
		$acf_data = WCPCE_ACF_Helper::get_card_data( $product->get_id() );
		if ( ! empty( $acf_data['badge_niet_leverbaar'] ) ) {
			return 'discontinued';
		}

		if ( WCPCE_Stock_Helper::is_out_of_stock( $product ) ) {
			return 'out_of_stock';
		}

		return 'in_stock';
	}

	/**
	 * Returns the conservative price used for the free-shipping threshold.
	 *
	 * @since 2.3.0
	 * @param \WC_Product $product The product.
	 * @return float Price to compare with the threshold.
	 */
	private function get_compare_price( \WC_Product $product ): float {
		$price_data = WCPCE_Price_Helper::get_product_price_data( $product );

		foreach ( array( 'display_price', 'sale_price', 'regular_price' ) as $key ) {
			$value = isset( $price_data[ $key ] ) ? (float) $price_data[ $key ] : 0.0;
			if ( $value > 0 ) {
				return $value;
			}
		}

		return (float) $product->get_price();
	}

	/**
	 * Formats the configured shipping threshold as compact shopper copy.
	 *
	 * @since 2.3.0
	 * @param float $amount Threshold amount.
	 * @return string Formatted amount.
	 */
	private function format_threshold_amount( float $amount ): string {
		$symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : 'EUR ';

		if ( abs( $amount - round( $amount ) ) < 0.01 ) {
			return $symbol . number_format_i18n( $amount, 0 ) . ',-';
		}

		if ( function_exists( 'wc_price' ) ) {
			return wp_strip_all_tags( wc_price( $amount ) );
		}

		return $symbol . number_format_i18n( $amount, 2 );
	}

	/**
	 * Builds the below-threshold shipping line.
	 *
	 * @since 2.3.0
	 * @param array $settings Widget settings.
	 * @return string Shipping threshold line.
	 */
	private function build_shipping_threshold_label( array $settings ): string {
		$prefix = $settings['shipping_threshold_prefix'] ?? __( 'Gratis bezorging vanaf', 'woo-card-chef' );
		$amount = $this->format_threshold_amount( (float) ( $settings['shipping_threshold'] ?? 25.0 ) );

		return trim( $prefix . ' ' . $amount );
	}

	/**
	 * Builds renderable delivery/availability items for the current product.
	 *
	 * @since 2.3.0
	 * @param \WC_Product $product  The product.
	 * @param array       $settings Widget settings.
	 * @return array<int, array{type:string,text:string}>
	 */
	private function get_delivery_items( \WC_Product $product, array $settings ): array {
		$items = array();
		$state = $this->get_availability_state( $product );

		if ( 'discontinued' === $state ) {
			if ( 'yes' === ( $settings['show_stock_status'] ?? 'yes' ) && '' !== ( $settings['discontinued_label'] ?? '' ) ) {
				$items[] = array(
					'type' => 'discontinued',
					'text' => $settings['discontinued_label'],
				);
			}

			return $items;
		}

		if ( 'out_of_stock' === $state ) {
			if ( 'yes' === ( $settings['show_stock_status'] ?? 'yes' ) && '' !== ( $settings['out_of_stock_label'] ?? '' ) ) {
				$items[] = array(
					'type' => 'unavailable',
					'text' => $settings['out_of_stock_label'],
				);
			}

			if ( 'yes' === ( $settings['show_delivery_line'] ?? 'yes' ) && 'yes' === ( $settings['show_out_of_stock_note'] ?? 'yes' ) && '' !== ( $settings['out_of_stock_delivery_text'] ?? '' ) ) {
				$items[] = array(
					'type' => 'delivery',
					'text' => $settings['out_of_stock_delivery_text'],
				);
			}

			return $items;
		}

		if ( 'yes' === ( $settings['show_stock_status'] ?? 'yes' ) && '' !== ( $settings['in_stock_label'] ?? '' ) ) {
			$items[] = array(
				'type' => 'stock',
				'text' => $settings['in_stock_label'],
			);
		}

		if ( 'yes' === ( $settings['show_delivery_line'] ?? 'yes' ) && '' !== ( $settings['delivery_text'] ?? '' ) ) {
			$items[] = array(
				'type' => 'delivery',
				'text' => $settings['delivery_text'],
			);
		}

		if ( 'yes' === ( $settings['show_shipping_line'] ?? 'yes' ) ) {
			$threshold     = (float) ( $settings['shipping_threshold'] ?? 25.0 );
			$compare_price = $this->get_compare_price( $product );
			$text          = $threshold <= 0.0 || $compare_price >= $threshold
				? ( $settings['free_shipping_label'] ?? __( 'Gratis bezorging', 'woo-card-chef' ) )
				: $this->build_shipping_threshold_label( $settings );

			if ( '' !== $text ) {
				$items[] = array(
					'type' => 'shipping',
					'text' => $text,
				);
			}
		}

		return $items;
	}

	/**
	 * Outputs editor-only notices inside the widget.
	 *
	 * @since 2.3.0
	 * @param \WC_Product|null $product The resolved product.
	 * @param array            $items   Resolved render items.
	 * @return void
	 */
	private function render_editor_notices( ?\WC_Product $product, array $items ): void {
		if ( ! $this->is_elementor_editor_or_preview() ) {
			return;
		}

		if ( null === $product ) {
			echo '<div class="wcpce-delivery-editor-notice">';
			echo esc_html__( 'No product context found. Assign this template to a product or open a product page to preview.', 'woo-card-chef' );
			echo '</div>';
			return;
		}

		if ( empty( $items ) ) {
			echo '<div class="wcpce-delivery-editor-notice">';
			echo esc_html__( 'No delivery or availability lines are currently enabled for this product.', 'woo-card-chef' );
			echo '</div>';
		}
	}

	/**
	 * Renders one item icon.
	 *
	 * @since 2.3.0
	 * @param string $type     Item type.
	 * @param array  $settings Widget settings.
	 * @return void
	 */
	private function render_icon( string $type, array $settings ): void {
		if ( 'yes' !== ( $settings['show_icons'] ?? 'yes' ) ) {
			return;
		}

		echo '<span class="wcpce-delivery__icon" aria-hidden="true">';

		if ( 'delivery' === $type ) {
			echo '<svg viewBox="0 0 16 16" focusable="false"><path d="M8 1.6a6.4 6.4 0 1 0 0 12.8A6.4 6.4 0 0 0 8 1.6Zm0 11.2A4.8 4.8 0 1 1 8 3.2a4.8 4.8 0 0 1 0 9.6Zm.6-7.9H7.1v3.7l3.1 1.9.8-1.2-2.4-1.4v-3Z" fill="currentColor"/></svg>';
		} elseif ( 'shipping' === $type ) {
			echo '<svg viewBox="0 0 16 16" focusable="false"><path d="M1.5 3.4h8.6v2.1h2.1l2.3 2.6v3.2h-1.3a2.1 2.1 0 0 1-4.1 0H6.7a2.1 2.1 0 0 1-4.1 0H1.5V3.4Zm1.5 1.5v4.9h.1a2.1 2.1 0 0 1 3.2 0h3.8V4.9H3Zm8.6 2.1v2.8h.1a2.1 2.1 0 0 1 1.4-.6h.1v-.6L11.5 7h.1ZM4.7 12a.7.7 0 1 0 0-1.5.7.7 0 0 0 0 1.5Zm6.5-.7a.7.7 0 1 0 1.5 0 .7.7 0 0 0-1.5 0Z" fill="currentColor"/></svg>';
		} elseif ( 'unavailable' === $type || 'discontinued' === $type ) {
			echo '<svg viewBox="0 0 16 16" focusable="false"><path d="M8 1.5a6.5 6.5 0 1 0 0 13A6.5 6.5 0 0 0 8 1.5Zm-.8 3.1h1.6v4.6H7.2V4.6Zm0 5.8h1.6V12H7.2v-1.6Z" fill="currentColor"/></svg>';
		} else {
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
	 * @since 2.3.0
	 * @return void
	 */
	protected function render(): void {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$settings  = $this->validate_delivery_settings( $this->get_settings_for_display() );
		$is_editor = $this->is_elementor_editor_or_preview();

		$product = $this->get_current_product();
		if ( null === $product && $is_editor ) {
			$product = $this->get_editor_fallback_product();
		}

		$items = null === $product ? array() : $this->get_delivery_items( $product, $settings );
		$this->render_editor_notices( $product, $items );

		if ( null === $product || empty( $items ) ) {
			return;
		}

		$layout  = $settings['layout_mode'] ?? 'list';
		$classes = array( 'wcpce-delivery', 'wcpce-delivery--' . $layout );
		if ( 'yes' !== ( $settings['show_icons'] ?? 'yes' ) ) {
			$classes[] = 'wcpce-delivery--no-icons';
		}

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" role="group" aria-label="' . esc_attr__( 'Beschikbaarheid en bezorging', 'woo-card-chef' ) . '">';
		echo '<ul class="wcpce-delivery__list" role="list">';
		foreach ( $items as $item ) {
			$type = isset( $item['type'] ) ? sanitize_html_class( (string) $item['type'] ) : 'delivery';
			$text = isset( $item['text'] ) ? (string) $item['text'] : '';

			echo '<li class="wcpce-delivery__item wcpce-delivery__item--' . esc_attr( $type ) . '">';
			$this->render_icon( $type, $settings );
			echo '<span class="wcpce-delivery__text">' . esc_html( $text ) . '</span>';
			echo '</li>';
		}
		echo '</ul>';
		echo '</div>';
	}
}
