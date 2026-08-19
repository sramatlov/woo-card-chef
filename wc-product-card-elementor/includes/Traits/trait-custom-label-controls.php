<?php
/**
 * Shared Elementor style controls for reusable custom product labels.
 *
 * @package WC_Product_Card_Elementor
 * @since 2.7.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds one consistent custom-label style surface to card-based widgets.
 *
 * The selectors deliberately target only `.wc-card__custom-label` and its
 * stack. Existing discount, Nieuw, PFAS, shipping, price and stock elements are
 * outside this contract and therefore cannot be changed by these controls.
 *
 * @since 2.7.1
 */
trait WCPCE_Custom_Label_Controls {

	/**
	 * Registers shared custom-label controls on Elementor's Style tab.
	 *
	 * @param string $label_selector   Context-specific custom label selector.
	 * @param string $stack_selector   Context-specific custom label container selector.
	 * @param array  $extra_conditions Additional Elementor section conditions.
	 * @return void
	 */
	protected function register_custom_label_style_controls( string $label_selector = '.wc-card__custom-label', string $stack_selector = '.wc-card__labels', array $extra_conditions = array() ): void {
		$label_css_selector = '{{WRAPPER}} ' . ltrim( $label_selector );
		$stack_css_selector = '{{WRAPPER}} ' . ltrim( $stack_selector );
		$conditions         = array_merge( array( 'show_custom_labels' => 'yes' ), $extra_conditions );

		$this->start_controls_section(
			'wcpce_custom_label_style_section',
			array(
				'label'     => esc_html__( 'Custom Product Labels', 'woo-card-chef' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => $conditions,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'           => 'custom_label_typography',
				'label'          => esc_html__( 'Typography', 'woo-card-chef' ),
				'selector'       => $label_css_selector,
				'fields_options' => array(
					'typography'    => array(
						'default' => 'custom',
					),
					'font_size'     => array(
						'default'        => array(
							'unit' => 'px',
							'size' => 13,
						),
						'tablet_default' => array(
							'unit' => 'px',
							'size' => 12,
						),
						'mobile_default' => array(
							'unit' => 'px',
							'size' => 11,
						),
					),
					'font_weight'   => array(
						'default' => '700',
					),
					'line_height'   => array(
						'default' => array(
							'unit' => 'em',
							'size' => 1.2,
						),
					),
					'letter_spacing' => array(
						'default' => array(
							'unit' => 'em',
							'size' => 0.01,
						),
					),
				),
			)
		);

		$this->add_responsive_control(
			'custom_label_padding',
			array(
				'label'          => esc_html__( 'Padding', 'woo-card-chef' ),
				'type'           => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units'     => array( 'px', 'em' ),
				'default'        => array( 'top' => '6', 'right' => '12', 'bottom' => '6', 'left' => '12', 'unit' => 'px', 'isLinked' => false ),
				'tablet_default' => array( 'top' => '5', 'right' => '10', 'bottom' => '5', 'left' => '10', 'unit' => 'px', 'isLinked' => false ),
				'mobile_default' => array( 'top' => '4', 'right' => '8', 'bottom' => '4', 'left' => '8', 'unit' => 'px', 'isLinked' => false ),
				'selectors'      => array(
					$label_css_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'custom_label_border_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array( 'top' => '6', 'right' => '6', 'bottom' => '6', 'left' => '6', 'unit' => 'px', 'isLinked' => true ),
				'selectors'  => array(
					$label_css_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'custom_label_gap',
			array(
				'label'          => esc_html__( 'Space between labels', 'woo-card-chef' ),
				'type'           => \Elementor\Controls_Manager::SLIDER,
				'size_units'     => array( 'px', 'em' ),
				'range'          => array(
					'px' => array( 'min' => 0, 'max' => 24, 'step' => 1 ),
					'em' => array( 'min' => 0, 'max' => 2, 'step' => 0.05 ),
				),
				'default'        => array( 'unit' => 'px', 'size' => 6 ),
				'tablet_default' => array( 'unit' => 'px', 'size' => 5 ),
				'mobile_default' => array( 'unit' => 'px', 'size' => 4 ),
				'selectors'      => array(
					$stack_css_selector => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'           => 'custom_label_shadow',
				'label'          => esc_html__( 'Shadow', 'woo-card-chef' ),
				'selector'       => $label_css_selector,
				'fields_options' => array(
					'box_shadow_type' => array(
						'default' => 'yes',
					),
					'box_shadow'      => array(
						'default' => array(
							'horizontal' => 0,
							'vertical'   => 2,
							'blur'       => 6,
							'spread'     => 0,
							'color'      => 'rgba(0, 0, 0, 0.18)',
						),
					),
				),
			)
		);

		$this->end_controls_section();
	}
}
