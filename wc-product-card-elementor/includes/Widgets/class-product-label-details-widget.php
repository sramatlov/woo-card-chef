<?php
/**
 * PDP Product Label Details widget for Elementor.
 *
 * Renders optional rich-text explanations stored on reusable product labels.
 * Active state, visibility schedule and priority come from the shared label
 * definition; layout and typography remain widget-level Elementor concerns.
 *
 * @package WC_Product_Card_Elementor
 * @since 2.7.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** PDP Product Label Details Elementor widget. */
class WCPCE_Product_Label_Details_Widget extends \Elementor\Widget_Base {

	/** Returns the unique widget slug. */
	public function get_name(): string {
		return 'wcpce_product_label_details';
	}

	/** Returns the widget title. */
	public function get_title(): string {
		return esc_html__( 'Product Label Details (PDP)', 'woo-card-chef' );
	}

	/** Returns the Elementor panel icon. */
	public function get_icon(): string {
		return 'eicon-info-box';
	}

	/** Returns the widget category. */
	public function get_categories(): array {
		return array( 'custom-woocommerce' );
	}

	/** Returns search keywords. */
	public function get_keywords(): array {
		return array( 'product', 'label', 'promotion', 'details', 'actie', 'uitleg', 'woocommerce', 'pdp' );
	}

	/** Returns the static stylesheet dependency. */
	public function get_style_depends(): array {
		return array( 'wcpce-product-label-details' );
	}

	/** This server-rendered widget has no JavaScript. */
	public function get_script_depends(): array {
		return array();
	}

	/** Registers content and style controls. */
	protected function register_controls(): void {
		$this->start_controls_section(
			'label_details_content_section',
			array(
				'label' => esc_html__( 'Product label details', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'max_details',
			array(
				'label'       => esc_html__( 'Maximum detail blocks', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => 3,
				'min'         => 1,
				'max'         => 10,
				'step'        => 1,
				'description' => esc_html__( 'Labels with lower priority numbers appear first. Labels without PDP content are skipped.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'show_label_badge',
			array(
				'label'        => esc_html__( 'Show label name', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Uses the reusable label text and colour above its explanation.', 'woo-card-chef' ),
			)
		);

		$this->end_controls_section();

		$this->register_layout_style_controls();
		$this->register_label_style_controls();
		$this->register_content_style_controls();
	}

	/** Registers layout and panel style controls. */
	private function register_layout_style_controls(): void {
		$this->start_controls_section(
			'label_details_layout_style_section',
			array(
				'label' => esc_html__( 'Layout & panels', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'detail_gap',
			array(
				'label'      => esc_html__( 'Space between blocks', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 48 ),
					'em' => array( 'min' => 0, 'max' => 3, 'step' => 0.1 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 12 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-label-details' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'panel_padding',
			array(
				'label'      => esc_html__( 'Panel padding', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array( 'top' => '16', 'right' => '18', 'bottom' => '16', 'left' => '18', 'unit' => 'px', 'isLinked' => false ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-label-details__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'panel_background',
			array(
				'label'     => esc_html__( 'Panel background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-label-details__item' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'panel_border',
				'label'    => esc_html__( 'Panel border', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-label-details__item',
			)
		);

		$this->add_responsive_control(
			'panel_radius',
			array(
				'label'      => esc_html__( 'Panel border radius', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 32 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 8 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-label-details__item' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'panel_shadow',
				'label'    => esc_html__( 'Panel shadow', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-label-details__item',
			)
		);

		$this->end_controls_section();
	}

	/** Registers controls for the optional label-name badge. */
	private function register_label_style_controls(): void {
		$this->start_controls_section(
			'label_details_badge_style_section',
			array(
				'label'     => esc_html__( 'Label name', 'woo-card-chef' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_label_badge' => 'yes' ),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'           => 'label_typography',
				'label'          => esc_html__( 'Typography', 'woo-card-chef' ),
				'selector'       => '{{WRAPPER}} .wcpce-label-details__label',
				'fields_options' => array(
					'font_weight' => array( 'default' => '700' ),
				),
			)
		);

		$this->add_responsive_control(
			'label_padding',
			array(
				'label'      => esc_html__( 'Padding', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array( 'top' => '5', 'right' => '10', 'bottom' => '5', 'left' => '10', 'unit' => 'px', 'isLinked' => false ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-label-details__label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'label_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 24 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 6 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-label-details__label' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'label_spacing',
			array(
				'label'      => esc_html__( 'Space below label', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 32 ),
					'em' => array( 'min' => 0, 'max' => 2, 'step' => 0.1 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 10 ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-label-details__label' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/** Registers rich-text and link style controls. */
	private function register_content_style_controls(): void {
		$this->start_controls_section(
			'label_details_text_style_section',
			array(
				'label' => esc_html__( 'Explanation', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'content_typography',
				'label'    => esc_html__( 'Typography', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-label-details__content',
			)
		);

		$this->add_control(
			'content_color',
			array(
				'label'     => esc_html__( 'Text colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#2a2a2a',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-label-details__content' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'link_color',
			array(
				'label'     => esc_html__( 'Link colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#B4211C',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-label-details__content a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'link_hover_color',
			array(
				'label'     => esc_html__( 'Link hover colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#7f1714',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-label-details__content a:hover, {{WRAPPER}} .wcpce-label-details__content a:focus-visible' => 'color: {{VALUE}};',
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

		$elementor = \Elementor\Plugin::$instance;
		$in_editor = isset( $elementor->editor ) && is_object( $elementor->editor ) && method_exists( $elementor->editor, 'is_edit_mode' ) && $elementor->editor->is_edit_mode();
		$in_preview = isset( $elementor->preview ) && is_object( $elementor->preview ) && method_exists( $elementor->preview, 'is_preview_mode' ) && $elementor->preview->is_preview_mode();

		return $in_editor || $in_preview;
	}

	/** Resolves the current WooCommerce product without trusting a stale global. */
	private function get_current_product(): ?\WC_Product {
		$queried = get_queried_object();
		if ( $queried instanceof \WP_Post && 'product' === $queried->post_type ) {
			$product = wc_get_product( $queried->ID );
			if ( $product instanceof \WC_Product ) {
				return $product;
			}
		}

		global $product;
		return $product instanceof \WC_Product ? $product : null;
	}

	/** Returns a published-product fallback for Elementor preview. */
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

		return empty( $query->posts ) ? null : wc_get_product( $query->posts[0]->ID );
	}

	/** Renders an editor-only empty-state notice. */
	private function render_editor_notice( string $message ): void {
		if ( ! $this->is_elementor_editor_or_preview() ) {
			return;
		}

		echo '<div class="wcpce-label-details-editor-notice">' . esc_html( $message ) . '</div>';
	}

	/** Formats stored rich text with paragraphs and the safe post-HTML allowlist. */
	private function prepare_details_html( string $content ): string {
		return wp_kses_post( wpautop( $content ) );
	}

	/** Renders the current product's visible label explanations. */
	protected function render(): void {
		if ( ! function_exists( 'wc_get_product' ) || ! class_exists( 'WCPCE_Product_Labels' ) ) {
			return;
		}

		$settings  = $this->get_settings_for_display();
		$limit     = max( 1, min( 10, absint( $settings['max_details'] ?? 3 ) ) );
		$is_editor = $this->is_elementor_editor_or_preview();
		$product   = $this->get_current_product();
		if ( null === $product && $is_editor ) {
			$product = $this->get_editor_fallback_product();
		}

		if ( null === $product ) {
			$this->render_editor_notice( __( 'No product context found for the label details preview.', 'woo-card-chef' ) );
			return;
		}

		$acf_data = class_exists( 'WCPCE_ACF_Helper' ) ? WCPCE_ACF_Helper::get_card_data( $product->get_id() ) : array();
		$details  = ! empty( $acf_data['badge_niet_leverbaar'] )
			? array()
			: WCPCE_Product_Labels::get_product_label_details( $product->get_id(), $limit );
		if ( empty( $details ) ) {
			$this->render_editor_notice( __( 'This product has no active custom label with PDP explanation content.', 'woo-card-chef' ) );
			return;
		}

		$show_label = 'yes' === ( $settings['show_label_badge'] ?? 'yes' );
		echo '<div class="wcpce-label-details" role="list" aria-label="' . esc_attr__( 'Productacties en voorwaarden', 'woo-card-chef' ) . '">';
		foreach ( $details as $detail ) {
			$label_text = (string) ( $detail['text'] ?? '' );
			$style      = '--wcpce-label-detail-color:' . ( $detail['color'] ?? '#B4211C' ) . ';--wcpce-label-detail-text-color:' . ( $detail['text_color'] ?? '#ffffff' ) . ';';

			echo '<section class="wcpce-label-details__item" role="listitem" aria-label="' . esc_attr( $label_text ) . '" style="' . esc_attr( $style ) . '">';
			if ( $show_label ) {
				echo '<div class="wcpce-label-details__label">' . esc_html( $label_text ) . '</div>';
			}
			echo '<div class="wcpce-label-details__content">';
			echo $this->prepare_details_html( (string) ( $detail['pdp_details'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitised by prepare_details_html().
			echo '</div>';
			echo '</section>';
		}
		echo '</div>';
	}
}
