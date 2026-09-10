<?php
/**
 * Product Category Navigation widget for Elementor.
 *
 * Renders a curated, manually ordered list of WooCommerce product categories.
 * Category identity, permalink and default image remain owned by WooCommerce;
 * Elementor stores only the selection order and optional presentation overrides.
 *
 * @package WC_Product_Card_Elementor
 * @since 2.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Product Category Navigation Elementor widget. */
class WCPCE_Category_Navigation_Widget extends \Elementor\Widget_Base {

	/** Maximum number of category cards rendered by one widget instance. */
	private const MAX_ITEMS = 24;

	/** Number of cards after which the editor shows a performance warning. */
	private const RECOMMENDED_ITEMS = 12;

	/** Returns the unique widget slug. */
	public function get_name(): string {
		return 'wcpce_category_navigation';
	}

	/** Returns the widget title. */
	public function get_title(): string {
		return esc_html__( 'Product Category Navigation', 'woo-card-chef' );
	}

	/** Returns the Elementor panel icon. */
	public function get_icon(): string {
		return 'eicon-product-categories';
	}

	/** Returns the widget category. */
	public function get_categories(): array {
		return array( 'custom-woocommerce' );
	}

	/** Returns search keywords. */
	public function get_keywords(): array {
		return array( 'category', 'categories', 'navigation', 'carousel', 'woocommerce', 'shop', 'categorie' );
	}

	/** Returns the static stylesheet dependency. */
	public function get_style_depends(): array {
		return array( 'wcpce-category-navigation' );
	}

	/** Returns the static script dependency. */
	public function get_script_depends(): array {
		return array( 'wcpce-category-navigation' );
	}

	/** Registers content and style controls. */
	protected function register_controls(): void {
		$this->register_content_controls();
		$this->register_layout_style_controls();
		$this->register_header_style_controls();
		$this->register_card_style_controls();
		$this->register_navigation_style_controls();
	}

	/** Registers section, category and navigation content controls. */
	private function register_content_controls(): void {
		$this->start_controls_section(
			'category_navigation_content_section',
			array(
				'label' => esc_html__( 'Category navigation', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'heading_text',
			array(
				'label'       => esc_html__( 'Heading', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'Ontdek onze categorieën', 'woo-card-chef' ),
				'label_block' => true,
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
					'div' => 'DIV',
				),
				'condition' => array( 'heading_text!' => '' ),
			)
		);

		$this->add_control(
			'show_shop_link',
			array(
				'label'        => esc_html__( 'Show shop link', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'shop_link_text',
			array(
				'label'       => esc_html__( 'Shop link text', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'Bekijk alle producten →', 'woo-card-chef' ),
				'label_block' => true,
				'condition'   => array( 'show_shop_link' => 'yes' ),
				'ai'          => array( 'active' => false ),
			)
		);

		$this->add_control(
			'shop_link_source',
			array(
				'label'     => esc_html__( 'Shop link source', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'automatic',
				'options'   => array(
					'automatic' => esc_html__( 'WooCommerce shop page', 'woo-card-chef' ),
					'custom'    => esc_html__( 'Custom URL', 'woo-card-chef' ),
				),
				'condition' => array( 'show_shop_link' => 'yes' ),
			)
		);

		$this->add_control(
			'custom_shop_link',
			array(
				'label'       => esc_html__( 'Custom shop link', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'label_block' => true,
				'condition'   => array(
					'show_shop_link'  => 'yes',
					'shop_link_source' => 'custom',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'category_items_section',
			array(
				'label' => esc_html__( 'Categories', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'category_id',
			array(
				'label'       => esc_html__( 'Category', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $this->get_product_category_options_lazy(),
				'label_block' => true,
				'multiple'    => false,
			)
		);

		$repeater->add_control(
			'display_name',
			array(
				'label'       => esc_html__( 'Display name', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
				'description' => esc_html__( 'Optional. Leave empty to use the WooCommerce category name.', 'woo-card-chef' ),
				'ai'          => array( 'active' => false ),
			)
		);

		$repeater->add_control(
			'image_override',
			array(
				'label'       => esc_html__( 'Image override', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::MEDIA,
				'description' => esc_html__( 'Optional. Falls back to the WooCommerce category image.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'category_items',
			array(
				'label'         => esc_html__( 'Category cards', 'woo-card-chef' ),
				'type'          => \Elementor\Controls_Manager::REPEATER,
				'fields'        => $repeater->get_controls(),
				'default'       => array(),
				'prevent_empty' => false,
				'title_field'   => '{{{ display_name || category_id }}}',
				'description'   => esc_html__( 'Drag the rows to set the exact frontend order. New WooCommerce categories are never added automatically.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'image_size',
			array(
				'label'       => esc_html__( 'Image source size', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'woocommerce_thumbnail',
				'options'     => array(
					'woocommerce_thumbnail' => esc_html__( 'WooCommerce thumbnail', 'woo-card-chef' ),
					'medium'                  => esc_html__( 'Medium', 'woo-card-chef' ),
					'medium_large'            => esc_html__( 'Medium large', 'woo-card-chef' ),
					'large'                   => esc_html__( 'Large', 'woo-card-chef' ),
				),
				'description' => esc_html__( 'WordPress still provides responsive srcset variants. WooCommerce thumbnail is recommended.', 'woo-card-chef' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'category_navigation_behavior_section',
			array(
				'label' => esc_html__( 'Navigation', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_arrows',
			array(
				'label'        => esc_html__( 'Show arrows', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_arrows_mobile',
			array(
				'label'        => esc_html__( 'Show arrows on mobile', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'condition'    => array( 'show_arrows' => 'yes' ),
			)
		);

		$this->add_control(
			'show_dots',
			array(
				'label'        => esc_html__( 'Show pagination dots', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'smooth_scroll',
			array(
				'label'        => esc_html__( 'Smooth scrolling', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/** Registers section layout style controls. */
	private function register_layout_style_controls(): void {
		$this->start_controls_section(
			'category_navigation_layout_style_section',
			array(
				'label' => esc_html__( 'Section & layout', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'section_background',
			array(
				'label'     => esc_html__( 'Background colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#FEFBFB',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-category-nav' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'section_padding',
			array(
				'label'      => esc_html__( 'Section padding', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'default'    => array(
					'top'      => '48',
					'right'    => '0',
					'bottom'   => '40',
					'left'     => '0',
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-category-nav' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_width',
			array(
				'label'      => esc_html__( 'Card width', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 80,
						'max' => 260,
					),
				),
				'default'    => array( 'unit' => 'px', 'size' => 150 ),
				'tablet_default' => array( 'unit' => 'px', 'size' => 130 ),
				'mobile_default' => array( 'unit' => 'px', 'size' => 100 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-category-nav' => '--wcpce-category-card-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_gap',
			array(
				'label'      => esc_html__( 'Card gap', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 60 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 20 ),
				'tablet_default' => array( 'unit' => 'px', 'size' => 16 ),
				'mobile_default' => array( 'unit' => 'px', 'size' => 12 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-category-nav' => '--wcpce-category-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/** Registers heading and shop-link style controls. */
	private function register_header_style_controls(): void {
		$this->start_controls_section(
			'category_navigation_header_style_section',
			array(
				'label' => esc_html__( 'Header', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'header_bottom_spacing',
			array(
				'label'      => esc_html__( 'Space below header', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 28 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-category-nav__header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'heading_colour',
			array(
				'label'     => esc_html__( 'Heading colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#B4211C',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-category-nav__heading' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'heading_typography',
				'selector' => '{{WRAPPER}} .wcpce-category-nav__heading',
			)
		);

		$this->add_control(
			'shop_link_colour',
			array(
				'label'     => esc_html__( 'Shop link colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#333232',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-category-nav__shop-link' => 'color: {{VALUE}}; border-bottom-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'shop_link_hover_colour',
			array(
				'label'     => esc_html__( 'Shop link hover colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#B4211C',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-category-nav__shop-link:hover, {{WRAPPER}} .wcpce-category-nav__shop-link:focus-visible' => 'color: {{VALUE}}; border-bottom-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'shop_link_typography',
				'selector' => '{{WRAPPER}} .wcpce-category-nav__shop-link',
			)
		);

		$this->end_controls_section();
	}

	/** Registers category-card style controls. */
	private function register_card_style_controls(): void {
		$this->start_controls_section(
			'category_navigation_card_style_section',
			array(
				'label' => esc_html__( 'Category cards', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'thumbnail_background',
			array(
				'label'     => esc_html__( 'Image background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-category-nav__thumbnail' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'thumbnail_radius',
			array(
				'label'      => esc_html__( 'Image border radius', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array(
					'top'      => '6',
					'right'    => '6',
					'bottom'   => '6',
					'left'     => '6',
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-category-nav__thumbnail' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'thumbnail_shadow',
				'selector' => '{{WRAPPER}} .wcpce-category-nav__thumbnail',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'thumbnail_hover_shadow',
				'label'    => esc_html__( 'Hover shadow', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-category-nav--hover .wcpce-category-nav__card:hover .wcpce-category-nav__thumbnail',
			)
		);

		$this->add_control(
			'enable_hover_animation',
			array(
				'label'        => esc_html__( 'Hover animation', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'hover_lift',
			array(
				'label'      => esc_html__( 'Hover lift', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 12 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 4 ),
				'condition'  => array( 'enable_hover_animation' => 'yes' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-category-nav' => '--wcpce-category-hover-lift: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'hover_zoom',
			array(
				'label'      => esc_html__( 'Hover image zoom', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::NUMBER,
				'min'        => 1,
				'max'        => 1.15,
				'step'       => 0.01,
				'default'    => 1.05,
				'condition'  => array( 'enable_hover_animation' => 'yes' ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-category-nav' => '--wcpce-category-hover-scale: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'label_spacing',
			array(
				'label'      => esc_html__( 'Image to label spacing', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 12 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-category-nav__card' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'label_colour',
			array(
				'label'     => esc_html__( 'Label colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#333232',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-category-nav__name' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'label_hover_colour',
			array(
				'label'     => esc_html__( 'Label hover colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#B4211C',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-category-nav__card:hover .wcpce-category-nav__name, {{WRAPPER}} .wcpce-category-nav__card:focus-visible .wcpce-category-nav__name' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'selector' => '{{WRAPPER}} .wcpce-category-nav__name',
			)
		);

		$this->end_controls_section();
	}

	/** Registers arrow and pagination-dot style controls. */
	private function register_navigation_style_controls(): void {
		$this->start_controls_section(
			'category_navigation_navigation_style_section',
			array(
				'label' => esc_html__( 'Navigation controls', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'arrow_size',
			array(
				'label'      => esc_html__( 'Arrow button size', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 32, 'max' => 64 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 40 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-category-nav' => '--wcpce-category-arrow-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'arrow_colour',
			array(
				'label'     => esc_html__( 'Arrow colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#333232',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-category-nav' => '--wcpce-category-arrow-colour: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_hover_colour',
			array(
				'label'     => esc_html__( 'Arrow hover colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#B4211C',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-category-nav' => '--wcpce-category-arrow-hover: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_background',
			array(
				'label'     => esc_html__( 'Arrow background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#FEFBFB',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-category-nav' => '--wcpce-category-arrow-background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_border_colour',
			array(
				'label'     => esc_html__( 'Arrow border colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#D8D4D3',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-category-nav' => '--wcpce-category-arrow-border: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_radius',
			array(
				'label'      => esc_html__( 'Arrow border radius', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 32 ),
					'%'  => array( 'min' => 0, 'max' => 50 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 4 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-category-nav__arrow' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'dot_colour',
			array(
				'label'     => esc_html__( 'Pagination dot colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#B4211C',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-category-nav' => '--wcpce-category-dot-colour: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'dot_size',
			array(
				'label'      => esc_html__( 'Visible dot size', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 5, 'max' => 16 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 9 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-category-nav' => '--wcpce-category-dot-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'dot_gap',
			array(
				'label'      => esc_html__( 'Pagination dot gap', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 24 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 0 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-category-nav__dots' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/** Returns whether Elementor editor/preview mode is active. */
	private function is_elementor_editor_or_preview(): bool {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance ) ) {
			return false;
		}

		$elementor  = \Elementor\Plugin::$instance;
		$in_editor  = isset( $elementor->editor ) && is_object( $elementor->editor ) && method_exists( $elementor->editor, 'is_edit_mode' ) && $elementor->editor->is_edit_mode();
		$in_preview = isset( $elementor->preview ) && is_object( $elementor->preview ) && method_exists( $elementor->preview, 'is_preview_mode' ) && $elementor->preview->is_preview_mode();

		return $in_editor || $in_preview;
	}

	/** Returns product-category options only when Elementor can display controls. */
	private function get_product_category_options_lazy(): array {
		$is_editor_context = is_admin() || $this->is_elementor_editor_or_preview() || ( defined( 'DOING_AJAX' ) && DOING_AJAX );
		if ( ! $is_editor_context || ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}

		static $options = null;
		if ( null !== $options ) {
			return $options;
		}

		$options = array();
		$terms   = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) ) {
			return $options;
		}

		$term_map = array();
		foreach ( $terms as $term ) {
			$term_map[ (int) $term->term_id ] = $term;
		}

		foreach ( $terms as $term ) {
			$label     = array( $term->name );
			$parent_id = (int) $term->parent;
			$depth     = 0;
			while ( $parent_id > 0 && isset( $term_map[ $parent_id ] ) && $depth < 10 ) {
				array_unshift( $label, $term_map[ $parent_id ]->name );
				$parent_id = (int) $term_map[ $parent_id ]->parent;
				++$depth;
			}

			$options[ $term->term_id ] = implode( ' › ', $label );
		}

		return $options;
	}

	/** Clamps a plain-text value without requiring the mbstring extension. */
	private function clamp_text( string $value, int $max_length ): string {
		$value = sanitize_text_field( $value );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, 0, $max_length );
		}
		return substr( $value, 0, $max_length );
	}

	/** Validates repeater rows and resolves their selected WooCommerce terms. */
	private function prepare_category_items( array $raw_items ): array {
		$result = array(
			'items'          => array(),
			'duplicate_count' => 0,
			'missing_count'   => 0,
			'truncated_count' => max( 0, count( $raw_items ) - self::MAX_ITEMS ),
		);

		$sanitized = array();
		$seen_ids  = array();
		foreach ( array_slice( $raw_items, 0, self::MAX_ITEMS ) as $raw_item ) {
			if ( ! is_array( $raw_item ) ) {
				++$result['missing_count'];
				continue;
			}

			$term_id = absint( $raw_item['category_id'] ?? 0 );
			if ( $term_id < 1 ) {
				++$result['missing_count'];
				continue;
			}

			if ( isset( $seen_ids[ $term_id ] ) ) {
				++$result['duplicate_count'];
				continue;
			}

			$seen_ids[ $term_id ] = true;
			$image_data             = is_array( $raw_item['image_override'] ?? null ) ? $raw_item['image_override'] : array();
			$sanitized[]            = array(
				'term_id'       => $term_id,
				'display_name'  => $this->clamp_text( (string) ( $raw_item['display_name'] ?? '' ), 120 ),
				'override_id'   => absint( $image_data['id'] ?? 0 ),
				'repeater_id'   => sanitize_key( (string) ( $raw_item['_id'] ?? '' ) ),
			);
		}

		if ( empty( $sanitized ) || ! taxonomy_exists( 'product_cat' ) ) {
			return $result;
		}

		$term_ids = array_column( $sanitized, 'term_id' );
		$terms    = get_terms(
			array(
				'taxonomy'               => 'product_cat',
				'include'                => $term_ids,
				'hide_empty'             => false,
				'orderby'                => 'include',
				'update_term_meta_cache' => true,
			)
		);

		if ( is_wp_error( $terms ) ) {
			$result['missing_count'] += count( $sanitized );
			return $result;
		}

		$term_map = array();
		foreach ( $terms as $term ) {
			$term_map[ (int) $term->term_id ] = $term;
		}

		foreach ( $sanitized as $item ) {
			if ( ! isset( $term_map[ $item['term_id'] ] ) ) {
				++$result['missing_count'];
				continue;
			}

			$term = $term_map[ $item['term_id'] ];
			$link = get_term_link( $term, 'product_cat' );
			if ( is_wp_error( $link ) ) {
				++$result['missing_count'];
				continue;
			}

			$item['term']               = $term;
			$item['url']                = $link;
			$item['term_thumbnail_id']  = absint( get_term_meta( $item['term_id'], 'thumbnail_id', true ) );
			$result['items'][]          = $item;
		}

		return $result;
	}

	/** Returns a responsive image sizes hint based on the card-width controls. */
	private function build_image_sizes( array $settings ): string {
		$desktop = $this->get_pixel_control_size( $settings, 'card_width', 150 );
		$tablet  = $this->get_pixel_control_size( $settings, 'card_width_tablet', 130 );
		$mobile  = $this->get_pixel_control_size( $settings, 'card_width_mobile', 100 );

		return sprintf(
			'(max-width: 767px) %dpx, (max-width: 1024px) %dpx, %dpx',
			$mobile,
			$tablet,
			$desktop
		);
	}

	/** Reads and clamps one responsive pixel control value. */
	private function get_pixel_control_size( array $settings, string $key, int $default ): int {
		$value = $settings[ $key ] ?? array();
		$size  = is_array( $value ) && is_numeric( $value['size'] ?? null ) ? (int) round( (float) $value['size'] ) : $default;
		return max( 80, min( 260, $size ) );
	}

	/** Returns one of the explicitly supported WordPress image sizes. */
	private function get_image_size( array $settings ): string {
		$size    = sanitize_key( (string) ( $settings['image_size'] ?? 'woocommerce_thumbnail' ) );
		$allowed = array( 'woocommerce_thumbnail', 'medium', 'medium_large', 'large' );
		return in_array( $size, $allowed, true ) ? $size : 'woocommerce_thumbnail';
	}

	/** Renders an editor-only notice. */
	private function render_editor_notice( string $message, string $type = 'info' ): void {
		if ( ! $this->is_elementor_editor_or_preview() ) {
			return;
		}

		$type = in_array( $type, array( 'info', 'warning' ), true ) ? $type : 'info';
		echo '<div class="wcpce-category-nav-editor-notice wcpce-category-nav-editor-notice--' . esc_attr( $type ) . '">' . esc_html( $message ) . '</div>';
	}

	/** Adds the configured shop-link attributes and returns whether it can render. */
	private function prepare_shop_link_attributes( array $settings ): bool {
		if ( 'yes' !== ( $settings['show_shop_link'] ?? 'yes' ) || '' === trim( (string) ( $settings['shop_link_text'] ?? '' ) ) ) {
			return false;
		}

		$link_settings = array();
		if ( 'custom' === ( $settings['shop_link_source'] ?? 'automatic' ) ) {
			$link_settings = is_array( $settings['custom_shop_link'] ?? null ) ? $settings['custom_shop_link'] : array();
		} elseif ( function_exists( 'wc_get_page_permalink' ) ) {
			$link_settings['url'] = wc_get_page_permalink( 'shop' );
		}

		if ( empty( $link_settings['url'] ) ) {
			return false;
		}

		$this->add_render_attribute( 'shop_link', 'class', 'wcpce-category-nav__shop-link' );
		$this->add_link_attributes( 'shop_link', $link_settings );
		return true;
	}

	/** Renders the configured category image or the WooCommerce placeholder. */
	private function render_category_image( int $image_id, string $image_size, string $sizes ): void {
		if ( $image_id > 0 && wp_attachment_is_image( $image_id ) ) {
			echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress escapes attachment markup and attributes.
				$image_id,
				$image_size,
				false,
				array(
					'class'    => 'wcpce-category-nav__image',
					'alt'      => '',
					'decoding' => 'async',
					'sizes'    => $sizes,
				)
			);
			return;
		}

		$placeholder = function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( $image_size ) : '';
		if ( '' !== $placeholder ) {
			echo '<img class="wcpce-category-nav__image wcpce-category-nav__image--placeholder" src="' . esc_url( $placeholder ) . '" alt="" width="300" height="300" loading="lazy" decoding="async">';
		}
	}

	/** Renders the widget on the frontend and in Elementor preview. */
	protected function render(): void {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			$this->render_editor_notice( __( 'WooCommerce product categories are unavailable.', 'woo-card-chef' ), 'warning' );
			return;
		}

		$settings = $this->get_settings_for_display();
		$raw_items = is_array( $settings['category_items'] ?? null ) ? $settings['category_items'] : array();
		$prepared = $this->prepare_category_items( $raw_items );
		$items    = $prepared['items'];

		if ( empty( $items ) ) {
			$this->render_editor_notice( __( 'Select at least one WooCommerce category to preview this widget.', 'woo-card-chef' ) );
			return;
		}

		if ( count( $raw_items ) > self::RECOMMENDED_ITEMS ) {
			$this->render_editor_notice( __( 'More than 12 category cards can make navigation and image loading heavier. Keep the list curated where possible.', 'woo-card-chef' ), 'warning' );
		}
		if ( $prepared['duplicate_count'] > 0 ) {
			$this->render_editor_notice( __( 'Duplicate category rows were skipped; the first occurrence determines the position and overrides.', 'woo-card-chef' ), 'warning' );
		}
		if ( $prepared['missing_count'] > 0 ) {
			$this->render_editor_notice( __( 'One or more category rows are incomplete or refer to a deleted category and were skipped.', 'woo-card-chef' ), 'warning' );
		}
		if ( $prepared['truncated_count'] > 0 ) {
			$this->render_editor_notice( __( 'Only the first 24 category rows are rendered.', 'woo-card-chef' ), 'warning' );
		}

		$image_ids = array();
		foreach ( $items as $item ) {
			if ( $item['override_id'] > 0 ) {
				$image_ids[] = $item['override_id'];
			}
			if ( $item['term_thumbnail_id'] > 0 ) {
				$image_ids[] = $item['term_thumbnail_id'];
			}
		}
		if ( class_exists( 'WCPCE_Image_Helper' ) ) {
			WCPCE_Image_Helper::prime_attachment_ids( $image_ids );
		}

		$widget_id     = sanitize_html_class( $this->get_id() );
		$heading_id    = 'wcpce-category-nav-heading-' . $widget_id;
		$row_id        = 'wcpce-category-nav-row-' . $widget_id;
		$heading_text  = $this->clamp_text( (string) ( $settings['heading_text'] ?? '' ), 160 );
		$heading_tag   = sanitize_key( (string) ( $settings['heading_tag'] ?? 'h2' ) );
		$heading_tag   = in_array( $heading_tag, array( 'h2', 'h3', 'h4', 'h5', 'h6', 'div' ), true ) ? $heading_tag : 'h2';
		$show_arrows   = 'yes' === ( $settings['show_arrows'] ?? 'yes' );
		$show_dots     = 'yes' === ( $settings['show_dots'] ?? 'yes' );
		$arrows_mobile = 'yes' === ( $settings['show_arrows_mobile'] ?? '' );
		$hover_enabled = 'yes' === ( $settings['enable_hover_animation'] ?? 'yes' );
		$smooth_scroll = 'yes' === ( $settings['smooth_scroll'] ?? 'yes' );
		$image_size    = $this->get_image_size( $settings );
		$image_sizes   = $this->build_image_sizes( $settings );
		$shop_link     = $this->prepare_shop_link_attributes( $settings );

		$classes = array( 'wcpce-category-nav' );
		if ( $show_arrows ) {
			$classes[] = 'wcpce-category-nav--arrows';
		}
		if ( $show_dots ) {
			$classes[] = 'wcpce-category-nav--dots';
		}
		if ( $arrows_mobile ) {
			$classes[] = 'wcpce-category-nav--arrows-mobile';
		}
		if ( $hover_enabled ) {
			$classes[] = 'wcpce-category-nav--hover';
		}

		$label_attribute = '' !== $heading_text
			? ' aria-labelledby="' . esc_attr( $heading_id ) . '"'
			: ' aria-label="' . esc_attr__( 'Productcategorieën', 'woo-card-chef' ) . '"';

		$page_label = __( 'Pagina %1$d van %2$d', 'woo-card-chef' );
		echo '<nav class="' . esc_attr( implode( ' ', $classes ) ) . '" data-wcpce-category-navigation data-smooth-scroll="' . esc_attr( $smooth_scroll ? 'yes' : 'no' ) . '" data-page-label="' . esc_attr( $page_label ) . '"' . $label_attribute . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $label_attribute is escaped while constructed.
		echo '<div class="wcpce-category-nav__header">';
		if ( '' !== $heading_text ) {
			echo '<' . esc_attr( $heading_tag ) . ' id="' . esc_attr( $heading_id ) . '" class="wcpce-category-nav__heading">' . esc_html( $heading_text ) . '</' . esc_attr( $heading_tag ) . '>';
		}
		echo '<div class="wcpce-category-nav__tools">';
		if ( $shop_link ) {
			echo '<a ' . $this->get_render_attribute_string( 'shop_link' ) . '>' . esc_html( $this->clamp_text( (string) $settings['shop_link_text'], 120 ) ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor escapes prepared link attributes.
		}
		if ( $show_arrows ) {
			echo '<div class="wcpce-category-nav__arrows" aria-hidden="true">';
			echo '<button type="button" class="wcpce-category-nav__arrow wcpce-category-nav__arrow--previous" data-wcpce-category-direction="-1" aria-label="' . esc_attr__( 'Vorige categorieën', 'woo-card-chef' ) . '" aria-controls="' . esc_attr( $row_id ) . '" disabled><span aria-hidden="true">&#8249;</span></button>';
			echo '<button type="button" class="wcpce-category-nav__arrow wcpce-category-nav__arrow--next" data-wcpce-category-direction="1" aria-label="' . esc_attr__( 'Volgende categorieën', 'woo-card-chef' ) . '" aria-controls="' . esc_attr( $row_id ) . '" disabled><span aria-hidden="true">&#8250;</span></button>';
			echo '</div>';
		}
		echo '</div></div>';

		echo '<ul id="' . esc_attr( $row_id ) . '" class="wcpce-category-nav__list">';
		foreach ( $items as $position => $item ) {
			$term         = $item['term'];
			$display_name = '' !== $item['display_name'] ? $item['display_name'] : $this->clamp_text( (string) $term->name, 120 );
			$image_id     = $item['override_id'];
			if ( $image_id < 1 || ! wp_attachment_is_image( $image_id ) ) {
				$image_id = $item['term_thumbnail_id'];
			}
			$item_class = 'wcpce-category-nav__item';
			if ( '' !== $item['repeater_id'] ) {
				$item_class .= ' elementor-repeater-item-' . $item['repeater_id'];
			}

			echo '<li class="' . esc_attr( $item_class ) . '">';
			echo '<a class="wcpce-category-nav__card" href="' . esc_url( $item['url'] ) . '" data-category-id="' . esc_attr( (string) $term->term_id ) . '" data-category-slug="' . esc_attr( $term->slug ) . '" data-position="' . esc_attr( (string) ( $position + 1 ) ) . '" data-wcpce-component="category-navigation">';
			echo '<span class="wcpce-category-nav__thumbnail">';
			$this->render_category_image( $image_id, $image_size, $image_sizes );
			echo '</span>';
			echo '<span class="wcpce-category-nav__name">' . esc_html( $display_name ) . '</span>';
			echo '</a></li>';
		}
		echo '</ul>';

		if ( $show_dots ) {
			echo '<div class="wcpce-category-nav__dots" role="group" aria-label="' . esc_attr__( 'Paginering categorieën', 'woo-card-chef' ) . '" aria-hidden="true"></div>';
		}

		echo '</nav>';
	}
}
