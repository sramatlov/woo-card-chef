<?php
/**
 * PDP Product Upsells widget for Elementor.
 *
 * Renders WooCommerce upsells for the current product as the same product cards
 * used by the archive Product Card Grid. Bourgini uses this PDP block for
 * relevant accessories, spare parts and extensions.
 *
 * @package WC_Product_Card_Elementor
 * @since 2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PDP Product Upsells Elementor widget.
 *
 * @since 2.5.0
 */
class WCPCE_Product_Upsells_Widget extends \Elementor\Widget_Base {

	// -------------------------------------------------------------------------
	// Elementor identity & dependencies
	// -------------------------------------------------------------------------

	/**
	 * Returns the unique widget slug.
	 *
	 * @since 2.5.0
	 * @return string
	 */
	public function get_name(): string {
		return 'wcpce_product_upsells';
	}

	/**
	 * Returns the widget label shown in the Elementor panel.
	 *
	 * @since 2.5.0
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'Product Upsells (PDP)', 'woo-card-chef' );
	}

	/**
	 * Returns the Elementor icon class for the widget panel.
	 *
	 * @since 2.5.0
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-products';
	}

	/**
	 * Returns the widget category slugs shown in the Elementor panel.
	 *
	 * @since 2.5.0
	 * @return array<string>
	 */
	public function get_categories(): array {
		return array( 'custom-woocommerce' );
	}

	/**
	 * Returns search keywords for the Elementor panel.
	 *
	 * @since 2.5.0
	 * @return array<string>
	 */
	public function get_keywords(): array {
		return array( 'upsells', 'accessories', 'spare parts', 'onderdelen', 'accessoires', 'product', 'woocommerce', 'pdp' );
	}

	/**
	 * Returns the stylesheet handles this widget depends on.
	 *
	 * @since 2.5.0
	 * @return array<string>
	 */
	public function get_style_depends(): array {
		return array( 'wc-product-card-elementor', 'wcpce-product-upsells' );
	}

	/**
	 * Returns the script handles this widget depends on.
	 *
	 * The card template can render AJAX add-to-cart buttons when enabled, so the
	 * WooCommerce add-to-cart script is declared statically like the card grid.
	 *
	 * @since 2.5.0
	 * @return array<string>
	 */
	public function get_script_depends(): array {
		return array( 'wc-add-to-cart' );
	}

	// -------------------------------------------------------------------------
	// Elementor controls
	// -------------------------------------------------------------------------

	/**
	 * Registers all Elementor controls for this widget.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	protected function register_controls(): void {
		$this->register_content_controls();
		$this->register_card_element_controls();
		$this->register_badge_controls();
		$this->register_layout_style_controls();
		$this->register_section_style_controls();
		$this->register_card_style_controls();
		$this->register_typography_controls();
		$this->register_color_controls();
	}

	/**
	 * Content tab: source behavior and section heading.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	private function register_content_controls(): void {
		$this->start_controls_section(
			'upsells_content_section',
			array(
				'label' => esc_html__( 'Upsells', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'heading_text',
			array(
				'label'       => esc_html__( 'Heading', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'Accessoires en onderdelen', 'woo-card-chef' ),
				'placeholder' => esc_html__( 'Accessoires en onderdelen', 'woo-card-chef' ),
				'ai'          => array( 'active' => false ),
			)
		);

		$this->add_control(
			'heading_tag',
			array(
				'label'     => esc_html__( 'Heading tag', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'h2',
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
			'max_items',
			array(
				'label'       => esc_html__( 'Maximum products', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => 9,
				'min'         => 1,
				'max'         => 24,
				'description' => esc_html__( 'Uses WooCommerce upsells from the current product. Product order is controlled below.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'upsell_orderby',
			array(
				'label'       => esc_html__( 'Product order', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'linked',
				'options'     => array(
					'linked'     => esc_html__( 'WooCommerce linked order', 'woo-card-chef' ),
					'popularity' => esc_html__( 'Popularity', 'woo-card-chef' ),
				),
				'description' => esc_html__( 'Popularity sorts linked upsells by WooCommerce total sales, highest first. Products with the same sales count keep their linked order.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'hide_empty',
			array(
				'label'        => esc_html__( 'Hide when empty', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'empty_state_text',
			array(
				'label'     => esc_html__( 'Empty state text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Geen accessoires of onderdelen gekoppeld.', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'hide_empty!' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content tab: card element toggles.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	private function register_card_element_controls(): void {
		$this->start_controls_section(
			'upsells_card_elements_section',
			array(
				'label' => esc_html__( 'Card elements', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_rating',
			array(
				'label'        => esc_html__( 'Show Lipscore rating', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'show_savings_line',
			array(
				'label'        => esc_html__( 'Show savings line', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'show_shipping',
			array(
				'label'        => esc_html__( 'Show free shipping pill', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'shipping_threshold',
			array(
				'label'     => esc_html__( 'Free shipping threshold', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 25,
				'min'       => 0,
				'max'       => 1000,
				'step'      => 1,
				'condition' => array( 'show_shipping' => 'yes' ),
			)
		);

		$this->add_control(
			'shipping_label',
			array(
				'label'     => esc_html__( 'Shipping pill text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Gratis verzending', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'show_shipping' => 'yes' ),
			)
		);

		$this->add_control(
			'show_usps',
			array(
				'label'        => esc_html__( 'Show product-card USPs', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'usp_count',
			array(
				'label'     => esc_html__( 'Maximum USPs', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 2,
				'min'       => 1,
				'max'       => 3,
				'condition' => array( 'show_usps' => 'yes' ),
			)
		);

		$this->add_control(
			'show_usps_mobile',
			array(
				'label'        => esc_html__( 'Show USPs on mobile', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array( 'show_usps' => 'yes' ),
			)
		);

		$this->add_control(
			'show_out_of_stock_label',
			array(
				'label'        => esc_html__( 'Show out-of-stock label', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'out_of_stock_label',
			array(
				'label'     => esc_html__( 'Out-of-stock label text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Tijdelijk uitverkocht', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'show_out_of_stock_label' => 'yes' ),
			)
		);

		$this->add_control(
			'action_type',
			array(
				'label'   => esc_html__( 'Action button', 'woo-card-chef' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'none',
				'options' => array(
					'none'        => esc_html__( 'None', 'woo-card-chef' ),
					'view'        => esc_html__( 'View product', 'woo-card-chef' ),
					'add_to_cart' => esc_html__( 'Add to cart / choose options', 'woo-card-chef' ),
				),
			)
		);

		$this->add_control(
			'action_label_view',
			array(
				'label'     => esc_html__( 'View product text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Bekijk product', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'action_type' => 'view' ),
			)
		);

		$this->add_control(
			'action_label_add_to_cart',
			array(
				'label'     => esc_html__( 'Add to cart text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'In winkelwagen', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'action_type' => 'add_to_cart' ),
			)
		);

		$this->add_control(
			'action_label_options',
			array(
				'label'     => esc_html__( 'Variable product text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Kies opties', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'action_type' => 'add_to_cart' ),
			)
		);

		$this->add_control(
			'show_hover_swap',
			array(
				'label'        => esc_html__( 'Hover image swap', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'show_hover_swap_mobile',
			array(
				'label'        => esc_html__( 'Hover image swap on mobile', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array( 'show_hover_swap' => 'yes' ),
			)
		);

		$this->add_control(
			'image_size',
			array(
				'label'   => esc_html__( 'Image size', 'woo-card-chef' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'woocommerce_thumbnail',
				'options' => array(
					'woocommerce_thumbnail' => esc_html__( 'WooCommerce thumbnail', 'woo-card-chef' ),
					'medium'                => esc_html__( 'Medium', 'woo-card-chef' ),
					'large'                 => esc_html__( 'Large', 'woo-card-chef' ),
					'full'                  => esc_html__( 'Full', 'woo-card-chef' ),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content tab: discount and ACF badge controls.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	private function register_badge_controls(): void {
		$this->start_controls_section(
			'upsells_badges_section',
			array(
				'label' => esc_html__( 'Badges', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_badge',
			array(
				'label'        => esc_html__( 'Show discount badge', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'badge_format',
			array(
				'label'     => esc_html__( 'Discount badge format', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'smart',
				'options'   => array(
					'percent' => esc_html__( 'Percentage', 'woo-card-chef' ),
					'amount'  => esc_html__( 'Amount', 'woo-card-chef' ),
					'smart'   => esc_html__( 'Smart', 'woo-card-chef' ),
				),
				'condition' => array( 'show_badge' => 'yes' ),
			)
		);

		$this->add_control(
			'badge_threshold',
			array(
				'label'     => esc_html__( 'Minimum discount percentage', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 0,
				'min'       => 0,
				'max'       => 100,
				'step'      => 1,
				'condition' => array( 'show_badge' => 'yes' ),
			)
		);

		$this->add_control(
			'badge_variable_prefix',
			array(
				'label'        => esc_html__( 'Add "Tot" prefix for variable products', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'show_badge' => 'yes' ),
			)
		);

		$this->add_control(
			'custom_badges_heading',
			array(
				'label'     => esc_html__( 'Product badges', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'show_badge_nieuw',
			array(
				'label'        => esc_html__( 'Show "Nieuw" badge', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'badge_nieuw_label',
			array(
				'label'     => esc_html__( '"Nieuw" text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Nieuw', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'show_badge_nieuw' => 'yes' ),
			)
		);

		$this->add_control(
			'show_badge_pfas',
			array(
				'label'        => esc_html__( 'Show "PFAS-vrij" badge', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'badge_pfas_label',
			array(
				'label'     => esc_html__( '"PFAS-vrij" text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'PFAS-vrij', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'show_badge_pfas' => 'yes' ),
			)
		);

		$this->add_control(
			'show_badge_niet_leverbaar',
			array(
				'label'        => esc_html__( 'Show "Niet meer leverbaar" badge', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'badge_niet_leverbaar_label',
			array(
				'label'     => esc_html__( '"Niet meer leverbaar" text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Niet meer leverbaar', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'show_badge_niet_leverbaar' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: grid layout controls.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	private function register_layout_style_controls(): void {
		$this->start_controls_section(
			'upsells_layout_section',
			array(
				'label' => esc_html__( 'Layout', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'mobile_layout',
			array(
				'label'   => esc_html__( 'Mobile layout', 'woo-card-chef' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'scroll',
				'options' => array(
					'scroll' => esc_html__( 'Horizontal scroll', 'woo-card-chef' ),
					'grid'   => esc_html__( 'Compact grid', 'woo-card-chef' ),
				),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => esc_html__( 'Columns', 'woo-card-chef' ),
				'type'           => \Elementor\Controls_Manager::NUMBER,
				'default'        => 3,
				'tablet_default' => 2,
				'mobile_default' => 2,
				'min'            => 1,
				'max'            => 6,
				'selectors'      => array(
					'{{WRAPPER}} .wcpce-upsells__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
				),
			)
		);

		$this->add_responsive_control(
			'grid_gap',
			array(
				'label'      => esc_html__( 'Gap between cards', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 60, 'step' => 1 ),
				),
				'default'        => array( 'unit' => 'px', 'size' => 12 ),
				'tablet_default' => array( 'unit' => 'px', 'size' => 12 ),
				'mobile_default' => array( 'unit' => 'px', 'size' => 12 ),
				'selectors'      => array(
					'{{WRAPPER}} .wcpce-upsells__grid' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => esc_html__( 'Card body padding', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array( 'top' => '14', 'right' => '12', 'bottom' => '14', 'left' => '12', 'unit' => 'px', 'isLinked' => false ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-card__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_body_gap',
			array(
				'label'      => esc_html__( 'Gap between card elements', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 40, 'step' => 1 ),
				),
				'default'   => array( 'unit' => 'px', 'size' => 8 ),
				'selectors' => array(
					'{{WRAPPER}} .wc-card__body' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'title_clamp_lines',
			array(
				'label'          => esc_html__( 'Title clamp (lines)', 'woo-card-chef' ),
				'type'           => \Elementor\Controls_Manager::NUMBER,
				'default'        => 3,
				'mobile_default' => 3,
				'min'            => 1,
				'max'            => 5,
				'selectors'      => array(
					'{{WRAPPER}} .wcpce-upsells .wc-card__title' => '-webkit-line-clamp: {{VALUE}}; min-height: calc(1em * 1.28 * {{VALUE}});',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: section spacing and heading.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	private function register_section_style_controls(): void {
		$this->start_controls_section(
			'upsells_section_style',
			array(
				'label' => esc_html__( 'Section', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'section_gap',
			array(
				'label'      => esc_html__( 'Heading gap', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 60, 'step' => 1 ),
				),
				'default'   => array( 'unit' => 'px', 'size' => 14 ),
				'selectors' => array(
					'{{WRAPPER}} .wcpce-upsells' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'heading_typography',
				'label'    => esc_html__( 'Heading typography', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-upsells__heading',
				'condition' => array( 'heading_text!' => '' ),
			)
		);

		$this->add_control(
			'heading_color',
			array(
				'label'     => esc_html__( 'Heading color', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#d45550',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-upsells__heading' => 'color: {{VALUE}};',
				),
				'condition' => array( 'heading_text!' => '' ),
			)
		);

		$this->add_responsive_control(
			'heading_alignment',
			array(
				'label'     => esc_html__( 'Heading alignment', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'woo-card-chef' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'woo-card-chef' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'woo-card-chef' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'left',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-upsells__heading' => 'text-align: {{VALUE}};',
				),
				'condition' => array( 'heading_text!' => '' ),
			)
		);

		$this->add_responsive_control(
			'heading_margin',
			array(
				'label'      => esc_html__( 'Heading margin', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-upsells__heading' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array( 'heading_text!' => '' ),
			)
		);

		$this->add_responsive_control(
			'heading_padding',
			array(
				'label'      => esc_html__( 'Heading padding', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-upsells__heading' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array( 'heading_text!' => '' ),
				'separator'  => 'before',
			)
		);

		$this->add_control(
			'heading_background',
			array(
				'label'     => esc_html__( 'Heading background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-upsells__heading' => 'background-color: {{VALUE}};',
				),
				'condition' => array( 'heading_text!' => '' ),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'      => 'heading_border',
				'label'     => esc_html__( 'Heading border', 'woo-card-chef' ),
				'selector'  => '{{WRAPPER}} .wcpce-upsells__heading',
				'condition' => array( 'heading_text!' => '' ),
			)
		);

		$this->add_responsive_control(
			'heading_border_radius',
			array(
				'label'      => esc_html__( 'Heading border radius', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-upsells__heading' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'condition'  => array( 'heading_text!' => '' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: card chrome and images.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	private function register_card_style_controls(): void {
		$this->start_controls_section(
			'upsells_card_style_section',
			array(
				'label' => esc_html__( 'Card', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_background',
			array(
				'label'     => esc_html__( 'Background color', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wc-card' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'card_border_color',
			array(
				'label'     => esc_html__( 'Border color', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .wc-card' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'card_border_width',
			array(
				'label'      => esc_html__( 'Border width', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 10, 'step' => 1 ),
				),
				'default'   => array( 'unit' => 'px', 'size' => 0 ),
				'selectors' => array(
					'{{WRAPPER}} .wc-card' => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;',
				),
			)
		);

		$this->add_control(
			'card_border_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 24, 'step' => 1 ),
				),
				'default'   => array( 'unit' => 'px', 'size' => 8 ),
				'selectors' => array(
					'{{WRAPPER}} .wc-card' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .wc-card',
			)
		);

		$this->add_control(
			'hover_lift',
			array(
				'label'        => esc_html__( 'Lift on hover', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'image_aspect_ratio',
			array(
				'label'     => esc_html__( 'Image aspect ratio', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => '1 / 1',
				'options'   => array(
					'1 / 1'  => esc_html__( 'Square', 'woo-card-chef' ),
					'4 / 3'  => esc_html__( 'Landscape', 'woo-card-chef' ),
					'3 / 4'  => esc_html__( 'Portrait', 'woo-card-chef' ),
					'16 / 9' => esc_html__( 'Wide', 'woo-card-chef' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .wc-card__media' => 'aspect-ratio: {{VALUE}};',
				),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'image_max_height',
			array(
				'label'      => esc_html__( 'Image area max height', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 90, 'max' => 420, 'step' => 10 ),
				),
				'default'   => array( 'unit' => 'px', 'size' => 180 ),
				'selectors' => array(
					'{{WRAPPER}} .wc-card__media' => 'max-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'image_padding',
			array(
				'label'      => esc_html__( 'Image padding', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 60, 'step' => 1 ),
				),
				'default'   => array( 'unit' => 'px', 'size' => 8 ),
				'selectors' => array(
					'{{WRAPPER}} .wc-card__image' => 'padding: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: card typography.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	private function register_typography_controls(): void {
		$this->start_controls_section(
			'upsells_typography_section',
			array(
				'label' => esc_html__( 'Typography', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'           => 'title_typography',
				'label'          => esc_html__( 'Title', 'woo-card-chef' ),
				'selector'       => '{{WRAPPER}} .wcpce-upsells .wc-card__title',
				'fields_options' => array(
					'typography'  => array(
						'default' => 'custom',
					),
					'font_size'   => array(
						'default' => array(
							'unit' => 'px',
							'size' => 12,
						),
					),
					'font_weight' => array(
						'default' => '400',
					),
					'line_height' => array(
						'default' => array(
							'unit' => 'em',
							'size' => 1.28,
						),
					),
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'           => 'price_typography',
				'label'          => esc_html__( 'Price', 'woo-card-chef' ),
				'selector'       => '{{WRAPPER}} .wcpce-upsells .wc-card__price-current, {{WRAPPER}} .wcpce-upsells .wc-card__price-sale',
				'separator'      => 'before',
				'fields_options' => array(
					'typography'  => array(
						'default' => 'custom',
					),
					'font_size'   => array(
						'default' => array(
							'unit' => 'px',
							'size' => 15,
						),
					),
					'font_weight' => array(
						'default' => '500',
					),
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'      => 'usp_typography',
				'label'     => esc_html__( 'USP text', 'woo-card-chef' ),
				'selector'  => '{{WRAPPER}} .wc-card__usp',
				'separator' => 'before',
				'condition' => array( 'show_usps' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: common card colors.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	private function register_color_controls(): void {
		$this->start_controls_section(
			'upsells_colors_section',
			array(
				'label' => esc_html__( 'Colors', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Title', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#2a2a2a',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-upsells .wc-card__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'price_current_color',
			array(
				'label'     => esc_html__( 'Current price', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#2a2a2a',
				'selectors' => array(
					'{{WRAPPER}} .wc-card__price-current' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'price_sale_color',
			array(
				'label'     => esc_html__( 'Sale price', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#B4211C',
				'selectors' => array(
					'{{WRAPPER}} .wc-card__price-sale' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'price_strikethrough_color',
			array(
				'label'     => esc_html__( 'Strikethrough price', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#888888',
				'selectors' => array(
					'{{WRAPPER}} .wc-card__price-regular' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_bg_color',
			array(
				'label'     => esc_html__( 'Discount badge background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#3EC26D',
				'selectors' => array(
					'{{WRAPPER}} .wc-card__badge:not(.wc-card__badge--pfas):not(.wc-card__badge--nieuw)' => 'background-color: {{VALUE}};',
				),
				'condition' => array( 'show_badge' => 'yes' ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'badge_text_color',
			array(
				'label'     => esc_html__( 'Discount badge text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wc-card__badge:not(.wc-card__badge--pfas):not(.wc-card__badge--nieuw)' => 'color: {{VALUE}};',
				),
				'condition' => array( 'show_badge' => 'yes' ),
			)
		);

		$this->add_control(
			'out_of_stock_label_bg',
			array(
				'label'     => esc_html__( 'Out-of-stock background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(42,42,42,0.52)',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-upsells .wc-card__stock-label' => 'background-color: {{VALUE}};',
				),
				'condition' => array( 'show_out_of_stock_label' => 'yes' ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'out_of_stock_label_color',
			array(
				'label'     => esc_html__( 'Out-of-stock text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-upsells .wc-card__stock-label' => 'color: {{VALUE}};',
				),
				'condition' => array( 'show_out_of_stock_label' => 'yes' ),
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
	 * @since 2.5.0
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
	 * @since 2.5.0
	 * @param array $settings Raw widget settings.
	 * @return array Validated settings.
	 */
	private function validate_upsell_settings( array $settings ): array {
		$settings['max_items']          = max( 1, min( 24, isset( $settings['max_items'] ) ? absint( $settings['max_items'] ) : 9 ) );
		$settings['shipping_threshold'] = max( 0.0, min( 1000.0, isset( $settings['shipping_threshold'] ) ? (float) $settings['shipping_threshold'] : 25.0 ) );
		$settings['badge_threshold']    = max( 0, min( 100, isset( $settings['badge_threshold'] ) ? absint( $settings['badge_threshold'] ) : 0 ) );
		$settings['usp_count']          = max( 1, min( 3, isset( $settings['usp_count'] ) ? absint( $settings['usp_count'] ) : 2 ) );

		$settings['heading_tag'] = $this->validate_choice( $settings['heading_tag'] ?? 'h2', array( 'h2', 'h3', 'h4', 'h5', 'h6', 'div' ), 'h2' );
		$settings['mobile_layout'] = $this->validate_choice( $settings['mobile_layout'] ?? 'scroll', array( 'scroll', 'grid' ), 'scroll' );
		$settings['action_type']   = $this->validate_choice( $settings['action_type'] ?? 'none', array( 'none', 'view', 'add_to_cart' ), 'none' );
		$settings['badge_format']  = $this->validate_choice( $settings['badge_format'] ?? 'smart', array( 'percent', 'amount', 'smart' ), 'smart' );
		$settings['image_size']    = $this->validate_choice( $settings['image_size'] ?? 'woocommerce_thumbnail', array( 'woocommerce_thumbnail', 'medium', 'large', 'full' ), 'woocommerce_thumbnail' );
		$settings['upsell_orderby'] = $this->validate_choice( $settings['upsell_orderby'] ?? 'linked', array( 'linked', 'popularity' ), 'linked' );

		$switch_defaults = array(
			'hide_empty'                  => 'yes',
			'show_rating'                 => '',
			'show_savings_line'           => '',
			'show_shipping'               => '',
			'show_usps'                   => '',
			'show_usps_mobile'            => '',
			'show_out_of_stock_label'     => 'yes',
			'show_hover_swap'             => '',
			'show_hover_swap_mobile'      => '',
			'show_badge'                  => 'yes',
			'badge_variable_prefix'       => 'yes',
			'show_badge_nieuw'            => 'yes',
			'show_badge_pfas'             => '',
			'show_badge_niet_leverbaar'   => 'yes',
			'hover_lift'                  => 'yes',
		);

		foreach ( $switch_defaults as $switch_key => $default_value ) {
			$settings[ $switch_key ] = 'yes' === ( $settings[ $switch_key ] ?? $default_value ) ? 'yes' : '';
		}

		$text_defaults = array(
			'heading_text'                 => __( 'Accessoires en onderdelen', 'woo-card-chef' ),
			'empty_state_text'             => __( 'Geen accessoires of onderdelen gekoppeld.', 'woo-card-chef' ),
			'shipping_label'               => __( 'Gratis verzending', 'woo-card-chef' ),
			'out_of_stock_label'           => __( 'Tijdelijk uitverkocht', 'woo-card-chef' ),
			'action_label_view'            => __( 'Bekijk product', 'woo-card-chef' ),
			'action_label_add_to_cart'     => __( 'In winkelwagen', 'woo-card-chef' ),
			'action_label_options'         => __( 'Kies opties', 'woo-card-chef' ),
			'badge_nieuw_label'            => __( 'Nieuw', 'woo-card-chef' ),
			'badge_pfas_label'             => __( 'PFAS-vrij', 'woo-card-chef' ),
			'badge_niet_leverbaar_label'   => __( 'Niet meer leverbaar', 'woo-card-chef' ),
		);

		foreach (
			array(
				'heading_text'                 => 100,
				'empty_state_text'             => 160,
				'shipping_label'               => 100,
				'out_of_stock_label'           => 80,
				'action_label_view'            => 80,
				'action_label_add_to_cart'     => 80,
				'action_label_options'         => 80,
				'badge_nieuw_label'            => 40,
				'badge_pfas_label'             => 40,
				'badge_niet_leverbaar_label'   => 80,
			) as $key => $max_length
		) {
			$value            = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : (string) $text_defaults[ $key ];
			$settings[ $key ] = $this->clamp_text( $value, $max_length );
		}

		return $settings;
	}

	/**
	 * Returns a whitelisted choice value.
	 *
	 * @since 2.5.0
	 * @param string $value   Raw value.
	 * @param array  $allowed Allowed values.
	 * @param string $default_value Default value.
	 * @return string
	 */
	private function validate_choice( string $value, array $allowed, string $default_value ): string {
		return in_array( $value, $allowed, true ) ? $value : $default_value;
	}

	/**
	 * Sanitises a text field and clamps it to a display-safe length.
	 *
	 * @since 2.5.0
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
	 * @since 2.5.0
	 * @since 2.5.1 Prefer queried product before global product.
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
	 * Returns a fallback product for the Elementor editor.
	 *
	 * @since 2.5.0
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
	 * Returns visible WooCommerce upsell products for the current product.
	 *
	 * @since 2.5.0
	 * @since 2.6.2 Added optional popularity sorting.
	 * @param \WC_Product $product  Current product.
	 * @param int         $max      Maximum products.
	 * @param string      $orderby  Sort mode: linked or popularity.
	 * @return array<int,\WC_Product>
	 */
	private function get_upsell_products( \WC_Product $product, int $max, string $orderby = 'linked' ): array {
		$ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $product->get_upsell_ids( 'view' ) )
				)
			)
		);

		$current_id = $product->get_id();
		$ids        = array_values(
			array_filter(
				$ids,
				static function ( int $id ) use ( $current_id ): bool {
					return $id > 0 && $id !== $current_id;
				}
			)
		);

		if ( empty( $ids ) ) {
			return array();
		}

		$original_order = array_flip( $ids );

		if ( function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $ids );
		}

		$visible = array();
		foreach ( $ids as $id ) {
			$upsell = wc_get_product( $id );
			if ( ! $upsell instanceof \WC_Product ) {
				continue;
			}

			if ( function_exists( 'wc_products_array_filter_visible' ) && ! wc_products_array_filter_visible( $upsell ) ) {
				continue;
			}

			if ( ! function_exists( 'wc_products_array_filter_visible' ) && ! $upsell->is_visible() ) {
				continue;
			}

			$visible[] = $upsell;
			if ( 'popularity' !== $orderby && count( $visible ) >= $max ) {
				break;
			}
		}

		if ( 'popularity' === $orderby ) {
			usort(
				$visible,
				static function ( \WC_Product $a, \WC_Product $b ) use ( $original_order ): int {
					$a_sales = (int) $a->get_total_sales();
					$b_sales = (int) $b->get_total_sales();

					if ( $a_sales === $b_sales ) {
						return ( $original_order[ $a->get_id() ] ?? 0 ) <=> ( $original_order[ $b->get_id() ] ?? 0 );
					}

					return $b_sales <=> $a_sales;
				}
			);
		}

		return array_slice( $visible, 0, $max );
	}

	/**
	 * Builds the settings array consumed by the shared card template.
	 *
	 * @since 2.5.0
	 * @param array $settings Validated widget settings.
	 * @return array<string,mixed>
	 */
	private function build_card_settings( array $settings ): array {
		return array(
			'columns'                     => $settings['columns'] ?? 3,
			'columns_tablet'              => $settings['columns_tablet'] ?? 2,
			'columns_mobile'              => $settings['columns_mobile'] ?? 2,
			'image_size'                  => $settings['image_size'] ?? 'woocommerce_thumbnail',
			'show_rating'                 => $settings['show_rating'] ?? '',
			'show_savings_line'           => $settings['show_savings_line'] ?? '',
			'show_shipping'               => $settings['show_shipping'] ?? '',
			'shipping_threshold'          => $settings['shipping_threshold'] ?? 25,
			'shipping_label'              => $settings['shipping_label'] ?? __( 'Gratis verzending', 'woo-card-chef' ),
			'show_usps'                   => $settings['show_usps'] ?? '',
			'usp_count'                   => $settings['usp_count'] ?? 2,
			'show_usps_mobile'            => $settings['show_usps_mobile'] ?? '',
			'show_out_of_stock_label'     => $settings['show_out_of_stock_label'] ?? 'yes',
			'out_of_stock_label'          => $settings['out_of_stock_label'] ?? __( 'Tijdelijk uitverkocht', 'woo-card-chef' ),
			'action_type'                 => $settings['action_type'] ?? 'none',
			'action_label_view'           => $settings['action_label_view'] ?? __( 'Bekijk product', 'woo-card-chef' ),
			'action_label_add_to_cart'    => $settings['action_label_add_to_cart'] ?? __( 'In winkelwagen', 'woo-card-chef' ),
			'action_label_options'        => $settings['action_label_options'] ?? __( 'Kies opties', 'woo-card-chef' ),
			'show_hover_swap'             => $settings['show_hover_swap'] ?? '',
			'show_hover_swap_mobile'      => $settings['show_hover_swap_mobile'] ?? '',
			'show_badge'                  => $settings['show_badge'] ?? 'yes',
			'badge_format'                => $settings['badge_format'] ?? 'smart',
			'badge_threshold'             => $settings['badge_threshold'] ?? 0,
			'badge_variable_prefix'       => $settings['badge_variable_prefix'] ?? 'yes',
			'show_badge_nieuw'            => $settings['show_badge_nieuw'] ?? 'yes',
			'badge_nieuw_label'           => $settings['badge_nieuw_label'] ?? __( 'Nieuw', 'woo-card-chef' ),
			'show_badge_pfas'             => $settings['show_badge_pfas'] ?? '',
			'badge_pfas_label'            => $settings['badge_pfas_label'] ?? __( 'PFAS-vrij', 'woo-card-chef' ),
			'show_badge_niet_leverbaar'   => $settings['show_badge_niet_leverbaar'] ?? 'yes',
			'badge_niet_leverbaar_label'  => $settings['badge_niet_leverbaar_label'] ?? __( 'Niet meer leverbaar', 'woo-card-chef' ),
			'hover_lift'                  => $settings['hover_lift'] ?? 'yes',
		);
	}

	/**
	 * Outputs editor-only notices.
	 *
	 * @since 2.5.0
	 * @param \WC_Product|null $product  Resolved product.
	 * @param array            $products Upsell products.
	 * @return void
	 */
	private function render_editor_notices( ?\WC_Product $product, array $products ): void {
		if ( ! $this->is_elementor_editor_or_preview() ) {
			return;
		}

		if ( null === $product ) {
			echo '<div class="wcpce-upsells-editor-notice">';
			echo esc_html__( 'No product context found. Assign this template to a product or open a product page to preview.', 'woo-card-chef' );
			echo '</div>';
			return;
		}

		if ( empty( $products ) ) {
			echo '<div class="wcpce-upsells-editor-notice">';
			echo esc_html__( 'This product has no visible WooCommerce upsells. Add upsells in Product data > Linked Products.', 'woo-card-chef' );
			echo '</div>';
		}
	}

	/**
	 * Renders the frontend empty state when the widget is configured to show it.
	 *
	 * @since 2.5.0
	 * @param array $settings Validated widget settings.
	 * @return void
	 */
	private function render_empty_state( array $settings ): void {
		$message = $settings['empty_state_text'] ?? __( 'Geen accessoires of onderdelen gekoppeld.', 'woo-card-chef' );

		echo '<div class="wcpce-upsells__empty">';
		echo esc_html( $message );
		echo '</div>';
	}

	// -------------------------------------------------------------------------
	// Render
	// -------------------------------------------------------------------------

	/**
	 * Renders the widget on the front end and in the editor preview.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	protected function render(): void {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$settings  = $this->validate_upsell_settings( $this->get_settings_for_display() );
		$is_editor = $this->is_elementor_editor_or_preview();

		$product = $this->get_current_product();
		if ( null === $product && $is_editor ) {
			$product = $this->get_editor_fallback_product();
		}

		$products = null === $product ? array() : $this->get_upsell_products( $product, (int) $settings['max_items'], $settings['upsell_orderby'] ?? 'linked' );
		$this->render_editor_notices( $product, $products );

		if ( null === $product || ( empty( $products ) && 'yes' === ( $settings['hide_empty'] ?? 'yes' ) ) ) {
			return;
		}

		$card_settings = $this->build_card_settings( $settings );
		$classes       = array( 'wcpce-upsells', 'wcpce-grid-section' );

		if ( 'scroll' === ( $settings['mobile_layout'] ?? 'scroll' ) ) {
			$classes[] = 'wcpce-upsells--mobile-scroll';
		}

		echo '<section class="' . esc_attr( implode( ' ', $classes ) ) . '">';

		if ( '' !== ( $settings['heading_text'] ?? '' ) ) {
			$tag = tag_escape( $settings['heading_tag'] ?? 'h2' );
			echo '<' . tag_escape( $tag ) . ' class="wcpce-upsells__heading">' . esc_html( $settings['heading_text'] ) . '</' . tag_escape( $tag ) . '>';
		}

		if ( empty( $products ) ) {
			$this->render_empty_state( $settings );
			echo '</section>';
			return;
		}

		WCPCE_Card_Renderer::render_svg_sprite();
		WCPCE_Image_Helper::prime_attachment_caches( $products, $card_settings );

		echo '<ul class="products wc-card-grid wcpce-upsells__grid" role="list">';

		$index     = 0;
		$widget_id = $this->get_id();
		foreach ( $products as $upsell ) {
			echo '<li class="product wcpce-grid-item wcpce-upsells__item" role="listitem">';
			WCPCE_Card_Renderer::render_card( $upsell, $card_settings, $index, $widget_id );
			echo '</li>';
			$index++;
		}

		echo '</ul>';
		echo '</section>';
	}
}
