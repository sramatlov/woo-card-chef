<?php
/**
 * PDP Price & Promo Block widget for Elementor.
 *
 * Replaces the default WooCommerce price output on the product detail page (PDP)
 * with a stronger, status-aware price block that fits the existing Bourgini
 * style. Renders inside an Elementor Theme Builder single-product template —
 * product context is available automatically via wc_get_product() / global
 * $product, exactly like the PDP Gallery widget.
 *
 * Feature set (v2.1.0 / PDP Phase 2):
 * - Regular price; on sale shows struck reference + sale price (simple products)
 * - Discount percentage chip and a separate savings amount line, each toggleable
 * - "Tot -X%" for variable products with mixed discounts (via WCPCE_Badge_Helper)
 * - Variable products: "Vanaf €X" (lowest current price) or the full WC range
 * - Compact and Extended layouts (layout/prominence only; content via toggles)
 * - Status-aware: "Niet meer leverbaar" dims the price and drops all promo framing
 * - Tax-correct display via wc_get_price_to_display() (matches WC get_price_html())
 * - Reference value runs through the wcpce_price_reference_value filter so a
 *   30-day-lowest (NL Omnibus/Prijzenwet) source can be injected without rework
 * - Server-side only, zero JS (progressive enhancement; see DECISIONS_LOG)
 *
 * Out of scope for v2.1.0 (see ROADMAP / design brief):
 * - Editorial promo line ("actieregel") — parked
 * - Variant-reactive live price — depends on a variant selector (v2.x)
 * - Emitting Product/Offer structured data — owned by WooCommerce core/SEO plugin
 *
 * @package WC_Product_Card_Elementor
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PDP Price & Promo Block Elementor widget.
 *
 * @since 2.1.0
 */
class WCPCE_Product_Price_Widget extends \Elementor\Widget_Base {

	// -------------------------------------------------------------------------
	// Elementor identity & dependencies
	// -------------------------------------------------------------------------

	/**
	 * Returns the unique widget slug.
	 *
	 * @since 2.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'wcpce_product_price';
	}

	/**
	 * Returns the widget label shown in the Elementor panel.
	 *
	 * @since 2.1.0
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'Product Price & Promo (PDP)', 'woo-card-chef' );
	}

	/**
	 * Returns the Elementor icon class for the widget panel.
	 *
	 * @since 2.1.0
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-price-list';
	}

	/**
	 * Returns the widget category slugs shown in the Elementor panel.
	 *
	 * @since 2.1.0
	 * @return array<string>
	 */
	public function get_categories(): array {
		return array( 'custom-woocommerce' );
	}

	/**
	 * Returns search keywords for the Elementor panel.
	 *
	 * @since 2.1.0
	 * @return array<string>
	 */
	public function get_keywords(): array {
		return array( 'price', 'promo', 'discount', 'sale', 'product', 'woocommerce', 'pdp' );
	}

	/**
	 * Returns the stylesheet handles this widget depends on.
	 *
	 * Elementor enqueues these only when the widget is present on the page,
	 * including inside the editor preview iframe.
	 *
	 * @since 2.1.0
	 * @return array<string>
	 */
	public function get_style_depends(): array {
		return array( 'wcpce-product-price' );
	}

	/**
	 * Returns the script handles this widget depends on.
	 *
	 * The Price & Promo Block ships no JavaScript — it is a purely presentational,
	 * server-rendered widget (progressive enhancement; see DECISIONS_LOG). This
	 * method still must return a static array and must never call
	 * get_settings_for_display(), which is null at this lifecycle stage and would
	 * fatal. See the v1.0.15 hotfix in KNOWN_ISSUES.
	 *
	 * @since 2.1.0
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
	 * @since 2.1.0
	 * @return void
	 */
	protected function register_controls(): void {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	/**
	 * Content tab: layout, elements and labels.
	 *
	 * @since 2.1.0
	 * @return void
	 */
	private function register_content_controls(): void {
		$this->start_controls_section(
			'price_content_section',
			array(
				'label' => esc_html__( 'Price', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'price_mode',
			array(
				'label'   => esc_html__( 'Layout', 'woo-card-chef' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'compact',
				'options' => array(
					'compact'  => esc_html__( 'Compact (inline)', 'woo-card-chef' ),
					'extended' => esc_html__( 'Extended (prominent)', 'woo-card-chef' ),
				),
			)
		);

		$this->add_control(
			'variable_price_display',
			array(
				'label'       => esc_html__( 'Variable products', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'from',
				'options'     => array(
					'from'  => esc_html__( 'From lowest price (Vanaf €X)', 'woo-card-chef' ),
					'range' => esc_html__( 'Full range (€X – €Y)', 'woo-card-chef' ),
				),
				'description' => esc_html__( 'How prices show for variable products before a variation is chosen.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'from_price_label',
			array(
				'label'       => esc_html__( 'From-price label', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Vanaf', 'woo-card-chef' ),
				'ai'          => array( 'active' => false ),
				'condition'   => array( 'variable_price_display' => 'from' ),
			)
		);

		$this->add_control(
			'show_reference_price',
			array(
				'label'        => esc_html__( 'Show reference (struck-through) price', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'reference_price_label',
			array(
				'label'     => esc_html__( 'Reference price label (visible)', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => esc_html__( 'Van', 'woo-card-chef' ),
				'ai'        => array( 'active' => false ),
				'condition' => array( 'show_reference_price' => 'yes' ),
			)
		);

		$this->add_control(
			'sale_price_label',
			array(
				'label'       => esc_html__( 'Sale price label (screen reader)', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => esc_html__( 'Nu', 'woo-card-chef' ),
				'ai'          => array( 'active' => false ),
				'description' => esc_html__( 'Read by screen readers before the sale price so the From/Now relation is clear.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'show_discount_percent',
			array(
				'label'        => esc_html__( 'Show discount percentage', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_savings_amount',
			array(
				'label'        => esc_html__( 'Show savings amount', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_variable_prefix',
			array(
				'label'        => esc_html__( '"Tot" prefix for variable products', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'dim_when_unavailable',
			array(
				'label'        => esc_html__( 'Dim price when discontinued', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'When the product has the "Niet meer leverbaar" flag, dim the price and drop the discount framing.', 'woo-card-chef' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: colours, typography and spacing.
	 *
	 * Pure style controls use Elementor's `selectors` so the editor writes the
	 * CSS itself — values are not read back in PHP (see CONVENTIONS / DECISIONS_LOG).
	 *
	 * @since 2.1.0
	 * @return void
	 */
	private function register_style_controls(): void {
		$this->start_controls_section(
			'price_style_section',
			array(
				'label' => esc_html__( 'Price', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'price_gap',
			array(
				'label'      => esc_html__( 'Element spacing', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-price__main' => 'gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wcpce-price'       => 'row-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'current_price_color',
			array(
				'label'     => esc_html__( 'Current price colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-price__current, {{WRAPPER}} .wcpce-price__current .woocommerce-Price-amount' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'sale_price_color',
			array(
				'label'     => esc_html__( 'Sale price colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-price__sale, {{WRAPPER}} .wcpce-price__sale .woocommerce-Price-amount' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'reference_price_color',
			array(
				'label'     => esc_html__( 'Reference price colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-price__reference, {{WRAPPER}} .wcpce-price__reference .woocommerce-Price-amount' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'current_price_typography',
				'label'    => esc_html__( 'Price typography', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-price__sale, {{WRAPPER}} .wcpce-price__current',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'reference_price_typography',
				'label'    => esc_html__( 'Reference typography', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-price__reference',
			)
		);

		$this->add_control(
			'chip_heading',
			array(
				'label'     => esc_html__( 'Discount chip', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'chip_bg_color',
			array(
				'label'     => esc_html__( 'Chip background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-price__chip' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'chip_text_color',
			array(
				'label'     => esc_html__( 'Chip text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-price__chip' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'chip_radius',
			array(
				'label'      => esc_html__( 'Chip border radius', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 24 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .wcpce-price__chip' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'savings_heading',
			array(
				'label'     => esc_html__( 'Savings line', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'savings_color',
			array(
				'label'     => esc_html__( 'Savings colour', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wcpce-price__savings, {{WRAPPER}} .wcpce-price__savings .woocommerce-Price-amount' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'unavailable_opacity',
			array(
				'label'       => esc_html__( 'Discontinued opacity', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0.2, 'max' => 1, 'step' => 0.05 ) ),
				'default'     => array( 'size' => 0.5 ),
				'separator'   => 'before',
				'selectors'   => array(
					'{{WRAPPER}} .wcpce-price--unavailable' => 'opacity: {{SIZE}};',
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
	 * Same pattern as the card and gallery widgets.
	 *
	 * @since 2.1.0
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
	 * Defensive validation against corrupted database state or import/export
	 * anomalies — not against attacker input (Elementor settings are only
	 * writable by authenticated editors). Same threat model and approach as the
	 * card widget's validate_manual_settings() and the gallery's
	 * validate_gallery_settings(): whitelist SELECT values and clamp text length.
	 *
	 * @since 2.1.0
	 * @param array $settings Raw widget settings.
	 * @return array Validated settings.
	 */
	private function validate_price_settings( array $settings ): array {
		// price_mode — whitelist.
		$mode = $settings['price_mode'] ?? 'compact';
		if ( ! in_array( $mode, array( 'compact', 'extended' ), true ) ) {
			$mode = 'compact';
		}
		$settings['price_mode'] = $mode;

		// variable_price_display — whitelist.
		$vpd = $settings['variable_price_display'] ?? 'from';
		if ( ! in_array( $vpd, array( 'from', 'range' ), true ) ) {
			$vpd = 'from';
		}
		$settings['variable_price_display'] = $vpd;

		// Label fields — sanitise and clamp to a sane length.
		foreach ( array( 'reference_price_label', 'sale_price_label', 'from_price_label' ) as $label_key ) {
			if ( isset( $settings[ $label_key ] ) ) {
				$settings[ $label_key ] = $this->clamp_text( (string) $settings[ $label_key ], 40 );
			}
		}

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
	 * @since 2.1.0
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
	 * @since 2.1.0
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
	 * @since 2.1.0
	 * @param array            $settings Widget settings.
	 * @param \WC_Product|null $product  The resolved product (may be null).
	 * @return void
	 */
	private function render_editor_notices( array $settings, ?\WC_Product $product ): void {
		if ( ! $this->is_elementor_editor_or_preview() ) {
			return;
		}

		if ( null !== $product ) {
			return;
		}

		echo '<div class="wcpce-price-editor-notice">';
		echo esc_html__( 'No product context found. Assign this template to a product or open a product page to preview.', 'woo-card-chef' );
		echo '</div>';
	}

	/**
	 * Converts a raw (base) price to the amount WooCommerce should display.
	 *
	 * Respects the woocommerce_tax_display_shop setting (incl/excl tax) so the
	 * widget always matches WooCommerce's own get_price_html() output and the
	 * Product schema. The discount percentage is tax-neutral (proportional) and
	 * is not run through this method.
	 *
	 * @since 2.1.0
	 * @param \WC_Product $product The product.
	 * @param float       $raw     A raw base price (regular/sale/etc.).
	 * @return float Display-context price.
	 */
	private function to_display( \WC_Product $product, float $raw ): float {
		return (float) wc_get_price_to_display( $product, array( 'price' => $raw ) );
	}

	/**
	 * Returns the display-context reference (struck-through) price.
	 *
	 * The raw regular price is passed through the wcpce_price_reference_value
	 * filter first, so a 30-day-lowest source (NL Omnibus/Prijzenwet) can be
	 * injected later without changing the widget. With no filter attached the
	 * reference equals the WooCommerce regular price.
	 *
	 * @since 2.1.0
	 * @param \WC_Product $product     The product.
	 * @param float       $raw_regular Raw regular price.
	 * @return float Display-context reference price.
	 */
	private function get_reference_display( \WC_Product $product, float $raw_regular ): float {
		$filtered = (float) apply_filters( 'wcpce_price_reference_value', $raw_regular, $product );
		return $this->to_display( $product, $filtered );
	}

	/**
	 * Renders the discount percentage chip (visual) plus its screen-reader text.
	 *
	 * The visible chip is aria-hidden (so "-20%" is not read as "minus twenty
	 * percent"); a screen-reader-only span carries the readable "20% korting" /
	 * "Tot 20% korting" via WCPCE_Badge_Helper::get_badge_aria_label().
	 *
	 * @since 2.1.0
	 * @param int   $percent     Discount percentage.
	 * @param float $reference   Display reference price (for Smart/amount formats; unused here, percent only).
	 * @param bool  $with_prefix Prefix with "Tot " (variable mixed discounts).
	 * @return void
	 */
	private function render_discount_chip( int $percent, float $reference, bool $with_prefix ): void {
		if ( $percent <= 0 ) {
			return;
		}

		$chip_text = WCPCE_Badge_Helper::format_badge_text( $percent, 0.0, $reference, 'percent', $with_prefix );
		$chip_aria = WCPCE_Badge_Helper::get_badge_aria_label( true, $chip_text );

		echo '<span class="wcpce-price__chip" aria-hidden="true">' . esc_html( $chip_text ) . '</span>';
		if ( '' !== $chip_aria ) {
			echo '<span class="wcpce-price__sr-only">' . esc_html( $chip_aria ) . '</span>';
		}
	}

	/**
	 * Renders the not-on-sale price (simple/grouped or variable).
	 *
	 * @since 2.1.0
	 * @param \WC_Product $product     The product.
	 * @param array       $price_data  Price data from WCPCE_Price_Helper.
	 * @param bool        $is_variable Whether the product is variable.
	 * @param array       $settings    Widget settings.
	 * @return void
	 */
	private function render_plain_price( \WC_Product $product, array $price_data, bool $is_variable, array $settings ): void {
		if ( $is_variable && 'range' === ( $settings['variable_price_display'] ?? 'from' ) ) {
			// Full WooCommerce range ("€X – €Y"), tax handling already applied by core.
			echo '<span class="wcpce-price__current">' . wp_kses_post( $product->get_price_html() ) . '</span>';
			return;
		}

		if ( $is_variable ) {
			// "Vanaf €X" — lowest current variation price.
			$lowest = $this->to_display( $product, (float) ( $price_data['display_price'] ?? 0.0 ) );
			echo '<span class="wcpce-price__from-label">' . esc_html( $settings['from_price_label'] ?? __( 'Vanaf', 'woo-card-chef' ) ) . ' </span>';
			echo '<span class="wcpce-price__current">' . wp_kses_post( wc_price( $lowest ) ) . '</span>';
			return;
		}

		// Simple/external: use the display-context price; fall back to get_price_html()
		// for grouped/external products that expose no direct price.
		$display = (float) ( $price_data['display_price'] ?? 0.0 );
		if ( $display > 0 ) {
			echo '<span class="wcpce-price__current">' . wp_kses_post( wc_price( $this->to_display( $product, $display ) ) ) . '</span>';
			return;
		}

		$html = $product->get_price_html();
		if ( ! empty( $html ) ) {
			echo '<span class="wcpce-price__current">' . wp_kses_post( $html ) . '</span>';
		}
	}

	/**
	 * Renders the full price block.
	 *
	 * @since 2.1.0
	 * @param \WC_Product $product     The product.
	 * @param array       $settings    Validated widget settings.
	 * @param array       $price_data  Price data from WCPCE_Price_Helper.
	 * @param bool        $is_variable Whether the product is variable.
	 * @param bool        $is_on_sale  Whether the product is genuinely on sale.
	 * @param bool        $has_mixed_discounts Whether variable discounts are mixed.
	 * @param bool        $unavailable Whether the product is "Niet meer leverbaar".
	 * @return void
	 */
	private function render_price_block( \WC_Product $product, array $settings, array $price_data, bool $is_variable, bool $is_on_sale, bool $has_mixed_discounts, bool $unavailable ): void {
		$classes = array( 'wcpce-price', 'wcpce-price--' . ( $settings['price_mode'] ?? 'compact' ) );
		if ( $unavailable ) {
			$classes[] = 'wcpce-price--unavailable';
		}

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		echo '<div class="wcpce-price__main">';

		if ( $unavailable || ! $is_on_sale ) {
			// Discontinued or not on sale: plain current price, no promo framing.
			$this->render_plain_price( $product, $price_data, $is_variable, $settings );
			echo '</div>'; // .wcpce-price__main
			echo '</div>'; // .wcpce-price
			return;
		}

		$show_percent = 'yes' === ( $settings['show_discount_percent'] ?? 'yes' );

		if ( $is_variable ) {
			// Variable on sale: lead with "Vanaf €X" (lowest current price) and a
			// "Tot -X%" chip. We deliberately do NOT show a single struck reference
			// or a literal savings amount here: the lowest variation never had that
			// reference price, so anchoring against it would be a misleading
			// (ghost) anchor — see the design brief, ethical guardrails section.
			$lowest      = $this->to_display( $product, (float) ( $price_data['display_price'] ?? 0.0 ) );
			$with_prefix = $has_mixed_discounts && 'yes' === ( $settings['show_variable_prefix'] ?? 'yes' );

			echo '<span class="wcpce-price__from-label">' . esc_html( $settings['from_price_label'] ?? __( 'Vanaf', 'woo-card-chef' ) ) . ' </span>';
			echo '<span class="wcpce-price__sale">';
			echo '<span class="wcpce-price__sr-only">' . esc_html( $settings['sale_price_label'] ?? __( 'Nu', 'woo-card-chef' ) ) . ' </span>';
			echo wp_kses_post( wc_price( $lowest ) );
			echo '</span>';

			if ( $show_percent ) {
				$this->render_discount_chip( (int) ( $price_data['discount_percent'] ?? 0 ), (float) ( $price_data['regular_price'] ?? 0.0 ), $with_prefix );
			}

			echo '</div>'; // .wcpce-price__main
			echo '</div>'; // .wcpce-price
			return;
		}

		// Simple product on sale: reference + sale price + chip + savings.
		$reference = $this->get_reference_display( $product, (float) ( $price_data['regular_price'] ?? 0.0 ) );
		$sale      = $this->to_display( $product, (float) ( $price_data['sale_price'] ?? 0.0 ) );

		// Percentage and savings derive from the (possibly filtered) reference so
		// they stay consistent with the displayed "van" price.
		$percent = $reference > 0 ? (int) round( ( ( $reference - $sale ) / $reference ) * 100 ) : (int) ( $price_data['discount_percent'] ?? 0 );
		$savings = max( 0.0, $reference - $sale );

		if ( 'yes' === ( $settings['show_reference_price'] ?? 'yes' ) && $reference > 0 ) {
			echo '<del class="wcpce-price__reference">';
			echo '<span class="wcpce-price__sr-only">' . esc_html( $settings['reference_price_label'] ?? __( 'Van', 'woo-card-chef' ) ) . ' </span>';
			echo wp_kses_post( wc_price( $reference ) );
			echo '</del>';
		}

		echo '<span class="wcpce-price__sale">';
		echo '<span class="wcpce-price__sr-only">' . esc_html( $settings['sale_price_label'] ?? __( 'Nu', 'woo-card-chef' ) ) . ' </span>';
		echo wp_kses_post( wc_price( $sale ) );
		echo '</span>';

		if ( $show_percent ) {
			$this->render_discount_chip( $percent, $reference, false );
		}

		echo '</div>'; // .wcpce-price__main

		if ( 'yes' === ( $settings['show_savings_amount'] ?? 'yes' ) && $savings > 0 ) {
			echo '<span class="wcpce-price__savings">';
			printf(
				/* translators: %s: amount saved with currency symbol */
				esc_html__( 'Bespaar %s', 'woo-card-chef' ),
				wp_kses_post( wc_price( $savings ) )
			);
			echo '</span>';
		}

		echo '</div>'; // .wcpce-price
	}

	// -------------------------------------------------------------------------
	// Render
	// -------------------------------------------------------------------------

	/**
	 * Renders the widget on the front end (and in the editor preview).
	 *
	 * @since 2.1.0
	 * @return void
	 */
	protected function render(): void {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$settings  = $this->validate_price_settings( $this->get_settings_for_display() );
		$is_editor = $this->is_elementor_editor_or_preview();

		$product = $this->get_current_product();
		if ( null === $product && $is_editor ) {
			$product = $this->get_editor_fallback_product();
		}

		$this->render_editor_notices( $settings, $product );

		if ( null === $product ) {
			return;
		}

		// Status: "Niet meer leverbaar" drops the discount framing and dims the price.
		$acf_data    = WCPCE_ACF_Helper::get_card_data( $product->get_id() );
		$unavailable = 'yes' === ( $settings['dim_when_unavailable'] ?? 'yes' ) && ! empty( $acf_data['badge_niet_leverbaar'] );

		$price_data  = WCPCE_Price_Helper::get_product_price_data( $product );
		$is_variable = $product->is_type( 'variable' );
		$is_on_sale  = ! $unavailable && $product->is_on_sale() && (int) ( $price_data['discount_percent'] ?? 0 ) > 0;
		$has_mixed_discounts = ! empty( $price_data['mixed_discounts'] );

		$this->render_price_block( $product, $settings, $price_data, $is_variable, $is_on_sale, $has_mixed_discounts, $unavailable );
	}
}
