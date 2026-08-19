<?php
/**
 * Custom WooCommerce Product Card widget for Elementor.
 *
 * @package WC_Product_Card_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a grid of WooCommerce products as custom-designed cards.
 *
 * Key behaviors:
 * - On archive templates with source = auto: rides the main query so pagination,
 *   sorting widgets, and filter plugins all work as if it were a native loop.
 * - On regular pages or with source = manual: runs its own WP_Query.
 * - Discount badge supports three formats (percent, amount, smart) with a
 *   configurable minimum-percent threshold (default 0, meaning all sale products
 *   show a badge).
 * - USPs come from ACF; empty USPs are skipped.
 * - Free shipping pill auto-renders when the regular price clears the threshold.
 * - Lipscore rating is a placeholder div that Lipscore JS fills in client-side.
 *
 * @since 1.0.0
 */
class WC_Product_Card_Elementor_Widget extends \Elementor\Widget_Base {
	use WCPCE_Custom_Label_Controls;

	/**
	 * Widget machine name. Must be unique within Elementor.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name(): string {
		return 'wc_product_card';
	}

	/**
	 * Widget title shown in the editor panel.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_title(): string {
		return esc_html__( 'Product Card Grid', 'woo-card-chef' );
	}

	/**
	 * Widget icon shown in the editor panel.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-products';
	}

	/**
	 * Editor panel categories the widget appears in.
	 *
	 * Only the custom category is declared here. The built-in woocommerce-elements
	 * category requires Elementor Pro and silently no-ops on free Elementor.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_categories(): array {
		return array( 'custom-woocommerce' );
	}

	/**
	 * Search keywords for the editor widget panel.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_keywords(): array {
		return array( 'product', 'woocommerce', 'card', 'grid', 'category', 'archive', 'shop' );
	}

	/**
	 * URL for the widget help link shown below the controls in the editor.
	 *
	 * @since 1.0.14
	 * @return string
	 */
	public function get_custom_help_url(): string {
		return 'https://vaneekerenindustries.nl';
	}

	/**
	 * Stylesheet handles to enqueue when the widget renders on a page.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_style_depends(): array {
		return array( 'wc-product-card-elementor' );
	}

	/**
	 * Scripts to enqueue when the widget renders.
	 *
	 * get_script_depends() is called by Elementor during early script enqueueing,
	 * before widget settings are initialized. Calling get_settings_for_display()
	 * here causes a fatal because settings are null at that point. We therefore
	 * always declare wc-add-to-cart, which is a small WooCommerce script already
	 * loaded on most WooCommerce pages anyway.
	 *
	 * @since 1.0.3
	 * @return array
	 */
	public function get_script_depends(): array {
		return array( 'wc-add-to-cart' );
	}

	/*
	 * Elementor compatibility note:
	 * Older plugin builds overrode has_widget_inner_wrapper() and
	 * is_dynamic_content(). Elementor 4 changed enough internals that overriding
	 * those methods can trigger fatal method-signature conflicts on some installs.
	 * We intentionally avoid those overrides and instead keep the widget output
	 * simple and dynamic at render time.
	 */

	/**
	 * Registers all editor controls (Content tab + Style tab).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function register_controls(): void {
		$this->register_query_controls();
		$this->register_card_element_controls();
		$this->register_discount_controls();
		$this->register_layout_controls();
		$this->register_card_style_controls();
		$this->register_typography_controls();
		$this->register_color_controls();
		$this->register_badge_style_controls();
		$this->register_custom_label_style_controls();
		$this->register_pagination_style_controls();
		$this->register_rating_style_controls();
	}

	/**
	 * JavaScript template for the Elementor editor live preview.
	 *
	 * This widget queries the database server-side, so a meaningful JS template
	 * is not practical. Declaring an explicit empty method signals to Elementor
	 * that it should re-render via PHP/AJAX when controls change, rather than
	 * attempting a client-side render with a missing JS template.
	 *
	 * @since 1.0.14
	 * @return void
	 */
	protected function content_template(): void {}

	/**
	 * Content tab: query and source controls.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_query_controls(): void {
		$this->start_controls_section(
			'section_query',
			array(
				'label' => esc_html__( 'Query', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'source',
			array(
				'label'       => esc_html__( 'Source', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'   => esc_html__( 'Current archive (auto)', 'woo-card-chef' ),
					'manual' => esc_html__( 'Manual category', 'woo-card-chef' ),
				),
				'description' => esc_html__( 'Auto rides the main archive query. Pagination and sorting widgets keep working. Use Manual on regular pages or to feature a specific category.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'category',
			array(
				'label'       => esc_html__( 'Category', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_product_category_options_lazy(),
				'condition'   => array(
					'source' => 'manual',
				),
				'description' => esc_html__( 'One or more categories to pull products from.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'     => esc_html__( 'Number of products', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 12,
				'min'       => 1,
				'max'       => 48,
				'condition' => array(
					'source' => 'manual',
				),
			)
		);


		$this->add_control(
			'orderby',
			array(
				'label'     => esc_html__( 'Order by', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'menu_order',
				'options'   => array(
					'menu_order' => esc_html__( 'Menu order', 'woo-card-chef' ),
					'date'       => esc_html__( 'Newest first', 'woo-card-chef' ),
					'price'      => esc_html__( 'Price: low to high', 'woo-card-chef' ),
					'price-desc' => esc_html__( 'Price: high to low', 'woo-card-chef' ),
					'popularity' => esc_html__( 'Best selling', 'woo-card-chef' ),
					'rating'     => esc_html__( 'Average rating', 'woo-card-chef' ),
					'rand'       => esc_html__( 'Random', 'woo-card-chef' ),
				),
				'condition' => array(
					'source' => 'manual',
				),
			)
		);

		// Q1: Sale only filter.
		$this->add_control(
			'filter_sale_only',
			array(
				'label'        => esc_html__( 'Sale products only', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Only show products currently on sale. Useful for campaign and sale pages.', 'woo-card-chef' ),
				'condition'    => array(
					'source' => 'manual',
				),
			)
		);

		// Q2: Featured only filter.
		$this->add_control(
			'filter_featured_only',
			array(
				'label'        => esc_html__( 'Featured products only', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Only show products marked as featured in WooCommerce.', 'woo-card-chef' ),
				'condition'    => array(
					'source' => 'manual',
				),
			)
		);

		// Q3: Stock filter.
		$this->add_control(
			'filter_stock',
			array(
				'label'   => esc_html__( 'Stock status', 'woo-card-chef' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'any',
				'options' => array(
					'any'      => esc_html__( 'Any (respect WooCommerce settings)', 'woo-card-chef' ),
					'instock'  => esc_html__( 'In stock only', 'woo-card-chef' ),
					'outstock' => esc_html__( 'Out of stock only', 'woo-card-chef' ),
				),
				'condition' => array(
					'source' => 'manual',
				),
			)
		);

		// Q4: Manual include by product ID.
		$this->add_control(
			'include_ids',
			array(
				'label'       => esc_html__( 'Include specific products (IDs)', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => esc_html__( '12, 34, 56', 'woo-card-chef' ),
				'label_block' => true,
				'description' => esc_html__( 'Comma-separated product IDs to show. Leave empty to show all. Find product IDs in WooCommerce > Products.', 'woo-card-chef' ),
				'condition'   => array(
					'source' => 'manual',
				),
			)
		);

		// Q5: Manual exclude by product ID.
		$this->add_control(
			'exclude_ids',
			array(
				'label'       => esc_html__( 'Exclude specific products (IDs)', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => esc_html__( '12, 34, 56', 'woo-card-chef' ),
				'label_block' => true,
				'description' => esc_html__( 'Comma-separated product IDs to exclude. Leave empty to exclude none.', 'woo-card-chef' ),
				'condition'   => array(
					'source' => 'manual',
				),
			)
		);

		// E1 + E2: Empty state controls.
		$this->add_control(
			'empty_state_heading',
			array(
				'label'     => esc_html__( 'Empty state', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'source' => 'manual',
				),
			)
		);

		$this->add_control(
			'show_empty_state',
			array(
				'label'        => esc_html__( 'Show message when no products found', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Turn off to show nothing when the query returns no products.', 'woo-card-chef' ),
				'condition'    => array(
					'source' => 'manual',
				),
			)
		);

		$this->add_control(
			'empty_state_text',
			array(
				'label'       => esc_html__( 'Empty state message', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'Geen producten gevonden.', 'woo-card-chef' ),
				'label_block' => true,
				'condition'   => array(
					'source'           => 'manual',
					'show_empty_state' => 'yes',
				),
			)
		);

		$this->add_control(
			'archive_query_notice',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'In Auto mode, products come from the main WooCommerce archive query. Product count, sorting and pagination are controlled by WooCommerce, the theme, Customizer settings or filter plugins.', 'woo-card-chef' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'       => array(
					'source' => 'auto',
				),
			)
		);

		// P1: Pagination controls.
		$this->add_control(
			'pagination_heading',
			array(
				'label'     => esc_html__( 'Pagination', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'enable_pagination',
			array(
				'label'        => esc_html__( 'Enable pagination', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Show pagination below the grid. Auto mode uses WooCommerce archive pages; Manual mode uses Woo Card Chef pagination.', 'woo-card-chef' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content tab: which card elements to show.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_card_element_controls(): void {
		$this->start_controls_section(
			'section_card_elements',
			array(
				'label' => esc_html__( 'Card Elements', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_rating',
			array(
				'label'        => esc_html__( 'Show Lipscore rating', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Renders a Lipscore small-rating placeholder. Lipscore must be loaded site-wide for stars to appear.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'show_savings_line',
			array(
				'label'        => esc_html__( 'Show savings line', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'show_shipping',
			array(
				'label'        => esc_html__( 'Show free shipping pill', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'shipping_threshold',
			array(
				'label'       => esc_html__( 'Free shipping threshold (€)', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => 25,
				'min'         => 0,
				'max'         => 1000,
				'step'        => 1,
				'description' => esc_html__( 'Pill renders only when the displayed product price is at or above this amount.', 'woo-card-chef' ),
				'condition'   => array(
					'show_shipping' => 'yes',
				),
			)
		);

		$this->add_control(
			'shipping_label',
			array(
				'label'     => esc_html__( 'Shipping pill text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Gratis verzending', 'woo-card-chef' ),
				'condition' => array(
					'show_shipping' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_usps',
			array(
				'label'        => esc_html__( 'Show USP list', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'usp_count',
			array(
				'label'     => esc_html__( 'Maximum USPs to show', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 3,
				'min'       => 1,
				'max'       => 3,
				'condition' => array(
					'show_usps' => 'yes',
				),
			)
		);
		$this->add_control(
			'show_usps_mobile',
			array(
				'label'        => esc_html__( 'Show USP list on mobile', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array(
					'show_usps' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_out_of_stock_label',
			array(
				'label'        => esc_html__( 'Show out of stock label', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'out_of_stock_label',
			array(
				'label'     => esc_html__( 'Out of stock label text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Tijdelijk uitverkocht', 'woo-card-chef' ),
				'condition' => array(
					'show_out_of_stock_label' => 'yes',
				),
			)
		);

		$this->add_control(
			'action_type',
			array(
				'label'       => esc_html__( 'Action button', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'none',
				'options'     => array(
					'none'       => esc_html__( 'None', 'woo-card-chef' ),
					'view'       => esc_html__( 'View product', 'woo-card-chef' ),
					'add_to_cart'=> esc_html__( 'Add to cart / choose options', 'woo-card-chef' ),
				),
				'description' => esc_html__( 'Optional. Leave off if you want the card to stay clean.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'action_label_view',
			array(
				'label'     => esc_html__( 'View product button text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Bekijk product', 'woo-card-chef' ),
				'condition' => array(
					'action_type' => 'view',
				),
			)
		);

		$this->add_control(
			'action_label_add_to_cart',
			array(
				'label'     => esc_html__( 'Add to cart button text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'In winkelwagen', 'woo-card-chef' ),
				'condition' => array(
					'action_type' => 'add_to_cart',
				),
			)
		);

		$this->add_control(
			'action_label_options',
			array(
				'label'     => esc_html__( 'Variable product button text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Kies opties', 'woo-card-chef' ),
				'condition' => array(
					'action_type' => 'add_to_cart',
				),
			)
		);

		$this->add_control(
			'show_hover_swap',
			array(
				'label'        => esc_html__( 'Hover image swap', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Shows the second gallery image on hover. Off by default to keep page weight low and avoid loading images visitors do not see. Turn on for landing pages where the visual interaction adds value. No effect if the product has only one image.', 'woo-card-chef' ),
			)
		);
		$this->add_control(
			'show_hover_swap_mobile',
			array(
				'label'        => esc_html__( 'Hover image swap on mobile', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array(
					'show_hover_swap' => 'yes',
				),
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
				'description' => esc_html__( 'Use WooCommerce thumbnail for the best default performance.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'badges_heading',
			array(
				'label'     => esc_html__( 'Product badges', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'show_custom_labels',
			array(
				'label'        => esc_html__( 'Toon herbruikbare productlabels', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Toont de labels die onder Producten > Productlabels aan producten zijn gekoppeld.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'custom_label_limit',
			array(
				'label'       => esc_html__( 'Maximum aantal productlabels', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => 3,
				'min'         => 1,
				'max'         => 10,
				'step'        => 1,
				'condition'   => array( 'show_custom_labels' => 'yes' ),
				'description' => esc_html__( 'Prioriteit bepaalt welke labels worden getoond wanneer er meer labels gekoppeld zijn.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'show_badge_nieuw',
			array(
				'label'        => esc_html__( 'Toon "Nieuw" badge', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Toont de rode "Nieuw" badge voor producten waarbij het ACF veld "Nieuw" is ingeschakeld. Alleen zichtbaar als het kortingsbadge niet actief is.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'badge_nieuw_label',
			array(
				'label'     => esc_html__( '"Nieuw" badge tekst', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Nieuw', 'woo-card-chef' ),
				'condition' => array( 'show_badge_nieuw' => 'yes' ),
			)
		);

		$this->add_control(
			'show_badge_pfas',
			array(
				'label'        => esc_html__( 'Toon "PFAS-vrij" badge', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Toont de groene PFAS-vrij badge met bladicoon linksonder voor producten waarbij het ACF veld "PFAS-vrij" is ingeschakeld.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'badge_pfas_label',
			array(
				'label'     => esc_html__( '"PFAS-vrij" badge tekst', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'PFAS-vrij', 'woo-card-chef' ),
				'condition' => array( 'show_badge_pfas' => 'yes' ),
			)
		);

		$this->add_control(
			'show_badge_niet_leverbaar',
			array(
				'label'        => esc_html__( 'Toon "Niet meer leverbaar" badge', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Toont een zwarte overlay over de afbeelding voor producten waarbij het ACF veld "Niet meer leverbaar" is ingeschakeld. De afbeelding wordt ook gedimd en grijs, net als bij tijdelijk uitverkocht.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'badge_niet_leverbaar_label',
			array(
				'label'     => esc_html__( '"Niet meer leverbaar" badge tekst', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Niet meer leverbaar', 'woo-card-chef' ),
				'condition' => array( 'show_badge_niet_leverbaar' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Content tab: discount badge configuration.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_discount_controls(): void {
		$this->start_controls_section(
			'section_discount',
			array(
				'label' => esc_html__( 'Discount Badge', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_badge',
			array(
				'label'        => esc_html__( 'Show discount badge', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'badge_format',
			array(
				'label'       => esc_html__( 'Badge format', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'smart',
				'options'     => array(
					'percent' => esc_html__( 'Percentage (-20%)', 'woo-card-chef' ),
					'amount'  => esc_html__( 'Amount (-€25)', 'woo-card-chef' ),
					'smart'   => esc_html__( 'Smart (Rule of 100)', 'woo-card-chef' ),
				),
				'description' => esc_html__( 'Smart shows percentage under €100 and amount at €100 and above, because the bigger number wins psychologically.', 'woo-card-chef' ),
				'condition'   => array(
					'show_badge' => 'yes',
				),
			)
		);

		$this->add_control(
			'badge_threshold',
			array(
				'label'       => esc_html__( 'Minimum discount percentage', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => 0,
				'min'         => 0,
				'max'         => 100,
				'step'        => 1,
				'description' => esc_html__( 'Products with a discount below this percentage do not show a badge. Set to 0 to always show a badge on any sale product.', 'woo-card-chef' ),
				'condition'   => array(
					'show_badge' => 'yes',
				),
			)
		);

		$this->add_control(
			'badge_variable_prefix',
			array(
				'label'        => esc_html__( 'Add "Tot" prefix for variable products', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'Off', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Variable products with mixed discounts across variations show "Tot -X%" so shoppers know not all variations have the maximum discount.', 'woo-card-chef' ),
				'condition'    => array(
					'show_badge' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: grid layout (columns, gaps).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_layout_controls(): void {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Layout', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'           => esc_html__( 'Columns', 'woo-card-chef' ),
				'type'            => \Elementor\Controls_Manager::NUMBER,
				'default'         => 3,
				'tablet_default'  => 2,
				'mobile_default'  => 2,
				'min'             => 1,
				'max'             => 6,
				'selectors'       => array(
					'{{WRAPPER}} .wc-card-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
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
					'px' => array(
						'min'  => 0,
						'max'  => 60,
						'step' => 1,
					),
				),
				'default'        => array( 'unit' => 'px', 'size' => 20 ),
				'tablet_default' => array( 'unit' => 'px', 'size' => 16 ),
				'mobile_default' => array( 'unit' => 'px', 'size' => 12 ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-card-grid' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => esc_html__( 'Card body padding', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'        => array( 'top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20', 'unit' => 'px', 'isLinked' => true ),
				'mobile_default' => array( 'top' => '14', 'right' => '14', 'bottom' => '14', 'left' => '14', 'unit' => 'px', 'isLinked' => true ),
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
				'default'        => array( 'unit' => 'px', 'size' => 10 ),
				'mobile_default' => array( 'unit' => 'px', 'size' => 8 ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-card__body' => 'gap: {{SIZE}}{{UNIT}};',
				),
				'description' => esc_html__( 'Vertical space between title, rating, price, USPs and shipping inside the card body.', 'woo-card-chef' ),
			)
		);

		$this->add_responsive_control(
			'title_clamp_lines',
			array(
				'label'           => esc_html__( 'Title clamp (lines)', 'woo-card-chef' ),
				'type'            => \Elementor\Controls_Manager::NUMBER,
				'default'         => 3,
				'mobile_default'  => 3,
				'min'             => 1,
				'max'             => 5,
				'selectors'       => array(
					'{{WRAPPER}} .wc-card__title' => '-webkit-line-clamp: {{VALUE}}; min-height: calc(1em * 1.4 * {{VALUE}});',
				),
				'description'     => esc_html__( 'Maximum number of lines before the title is truncated with an ellipsis. Also reserves vertical space so cards in the same row stay aligned.', 'woo-card-chef' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: card chrome (background, border, radius, shadow, hover).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_card_style_controls(): void {
		$this->start_controls_section(
			'section_card_style',
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
				'description' => esc_html__( 'Leave empty for no border. Set a border width below to make it visible.', 'woo-card-chef' ),
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
				'default'    => array( 'unit' => 'px', 'size' => 0 ),
				'selectors'  => array(
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
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 10 ),
				'selectors'  => array(
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
			'hover_heading',
			array(
				'label'     => esc_html__( 'Hover effect', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'hover_lift',
			array(
				'label'        => esc_html__( 'Lift on hover', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_hover_shadow',
				'label'    => esc_html__( 'Shadow on hover', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wc-card:hover, {{WRAPPER}} .wc-card:focus-visible',
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
					'4 / 3'  => esc_html__( 'Landscape (4:3)', 'woo-card-chef' ),
					'3 / 4'  => esc_html__( 'Portrait (3:4)', 'woo-card-chef' ),
					'16 / 9' => esc_html__( 'Wide (16:9)', 'woo-card-chef' ),
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
					'px' => array( 'min' => 100, 'max' => 600, 'step' => 10 ),
				),
				// 240px targets a total card height of ~520px on a 3-column 1440px
				// desktop grid, keeping the first product row fully visible above
				// the fold on typical laptop viewports (700px usable height).
				'default'    => array( 'unit' => 'px', 'size' => 240 ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-card__media' => 'max-height: {{SIZE}}{{UNIT}};',
				),
				'description' => esc_html__( 'Caps the image area height regardless of card width. Keeps cards compact on wide desktop grids while leaving mobile unaffected. Increase for more image prominence, decrease for a more compact grid.', 'woo-card-chef' ),
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
				// 8px gives products more room to breathe within the constrained
				// image height. 16px was proportionally too large at 240px max-height.
				'default'    => array( 'unit' => 'px', 'size' => 8 ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-card__image' => 'padding: {{SIZE}}{{UNIT}};',
				),
				'description' => esc_html__( 'White space around the product photo inside the image area. Increase for more breathing room, decrease for edge-to-edge images.', 'woo-card-chef' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: typography for title, price, USPs.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_typography_controls(): void {
		$this->start_controls_section(
			'section_typography',
			array(
				'label' => esc_html__( 'Typography', 'woo-card-chef' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => esc_html__( 'Title', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wc-card__title',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'      => 'price_typography',
				'label'     => esc_html__( 'Price', 'woo-card-chef' ),
				'selector'  => '{{WRAPPER}} .wc-card__price-current, {{WRAPPER}} .wc-card__price-sale',
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'      => 'usp_typography',
				'label'     => esc_html__( 'USP text', 'woo-card-chef' ),
				'selector'  => '{{WRAPPER}} .wc-card__usp',
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: colors for title, prices, savings, USP icon.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_color_controls(): void {
		$this->start_controls_section(
			'section_colors',
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
					'{{WRAPPER}} .wc-card__title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'price_current_color',
			array(
				'label'     => esc_html__( 'Current/regular price', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#2a2a2a',
				'selectors' => array(
					'{{WRAPPER}} .wc-card__price-current' => 'color: {{VALUE}};',
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
			'savings_color',
			array(
				'label'     => esc_html__( 'Savings line', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#2a2a2a',
				'selectors' => array(
					'{{WRAPPER}} .wc-card__savings' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'shipping_pill_bg',
			array(
				'label'     => esc_html__( 'Shipping pill background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#e8f7ee',
				'selectors' => array(
					'{{WRAPPER}} .wc-card__shipping' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'shipping_pill_color',
			array(
				'label'     => esc_html__( 'Shipping pill text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1e7a3a',
				'selectors' => array(
					'{{WRAPPER}} .wc-card__shipping' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'shipping_pill_font_size',
			array(
				'label'      => esc_html__( 'Shipping pill font size', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 8, 'max' => 18, 'step' => 1 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 11 ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-card__shipping' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'shipping_pill_icon_size',
			array(
				'label'      => esc_html__( 'Shipping pill icon size', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 8, 'max' => 24, 'step' => 1 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 14 ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-card__shipping-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'shipping_pill_padding_v',
			array(
				'label'      => esc_html__( 'Shipping pill vertical padding', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 16, 'step' => 1 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 4 ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-card__shipping' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'shipping_pill_padding_h',
			array(
				'label'      => esc_html__( 'Shipping pill horizontal padding', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 24, 'step' => 1 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 10 ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-card__shipping' => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'shipping_pill_border_radius',
			array(
				'label'      => esc_html__( 'Shipping pill border radius', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 100, 'step' => 1 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 6 ),
				'description' => esc_html__( 'Set to 6px to match badge corners, or 100px for a full pill shape.', 'woo-card-chef' ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-card__shipping' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'usp_icon_color',
			array(
				'label'     => esc_html__( 'USP checkmark', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#3EC26D',
				'selectors' => array(
					'{{WRAPPER}} .wc-card__usp-icon' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'usp_text_color',
			array(
				'label'     => esc_html__( 'USP text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#5a5a5a',
				'selectors' => array(
					'{{WRAPPER}} .wc-card__usp' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'out_of_stock_heading',
			array(
				'label'     => esc_html__( 'Out of stock label', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'show_out_of_stock_label' => 'yes',
				),
			)
		);

		$this->add_control(
			'out_of_stock_label_bg',
			array(
				'label'     => esc_html__( 'Label background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.62)',
				'selectors' => array(
					'{{WRAPPER}} .wc-card__stock-label' => 'background-color: {{VALUE}};',
				),
				'condition' => array(
					'show_out_of_stock_label' => 'yes',
				),
			)
		);

		$this->add_control(
			'out_of_stock_label_color',
			array(
				'label'     => esc_html__( 'Label text color', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wc-card__stock-label' => 'color: {{VALUE}};',
				),
				'condition' => array(
					'show_out_of_stock_label' => 'yes',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: discount badge appearance.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_badge_style_controls(): void {
		$this->start_controls_section(
			'section_badge_style',
			array(
				'label'     => esc_html__( 'Discount Badge Style', 'woo-card-chef' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_badge' => 'yes',
				),
			)
		);

		$this->add_control(
			'badge_bg_color',
			array(
				'label'     => esc_html__( 'Background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#3EC26D',
				'selectors' => array(
					'{{WRAPPER}} .wc-card__badge:not(.wc-card__badge--pfas):not(.wc-card__badge--nieuw)' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_text_color',
			array(
				'label'     => esc_html__( 'Text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wc-card__badge:not(.wc-card__badge--pfas):not(.wc-card__badge--nieuw)' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_position',
			array(
				'label'   => esc_html__( 'Position', 'woo-card-chef' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'top-left',
				'options' => array(
					'top-left'  => esc_html__( 'Top left', 'woo-card-chef' ),
					'top-right' => esc_html__( 'Top right', 'woo-card-chef' ),
				),
				'prefix_class' => 'wc-card-badge-pos-',
			)
		);

		$this->add_control(
			'badge_border_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'woo-card-chef' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 100, 'step' => 1 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 6 ),
				'selectors'  => array(
					'{{WRAPPER}} .wc-card__badge:not(.wc-card__badge--pfas):not(.wc-card__badge--nieuw)' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
				'description' => esc_html__( 'Set to 100px for a pill shape, 2-4px for a rectangular badge.', 'woo-card-chef' ),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'badge_typography',
				'label'    => esc_html__( 'Typography', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wc-card__badge:not(.wc-card__badge--pfas):not(.wc-card__badge--nieuw)',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: pagination colours and typography.
	 *
	 * Only visible when pagination is enabled.
	 *
	 * @since 1.0.36
	 * @return void
	 */
	private function register_pagination_style_controls(): void {
		$this->start_controls_section(
			'section_pagination_style',
			array(
				'label'     => esc_html__( 'Pagination', 'woo-card-chef' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'enable_pagination' => 'yes',
				),
			)
		);

		$this->add_control(
			'pagination_active_bg',
			array(
				'label'     => esc_html__( 'Active page background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#3EC26D',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-pagination .wcpce-page-link--current' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pagination_active_color',
			array(
				'label'     => esc_html__( 'Active page text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-pagination .wcpce-page-link--current' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pagination_inactive_bg',
			array(
				'label'     => esc_html__( 'Inactive page background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#f0f0f0',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-pagination .wcpce-page-link:not(.wcpce-page-link--current):not(.wcpce-page-link--prev):not(.wcpce-page-link--next)' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pagination_inactive_color',
			array(
				'label'     => esc_html__( 'Inactive page text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#2a2a2a',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-pagination .wcpce-page-link:not(.wcpce-page-link--current):not(.wcpce-page-link--prev):not(.wcpce-page-link--next)' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pagination_hover_bg',
			array(
				'label'     => esc_html__( 'Hover background', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#d4f0e1',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-pagination .wcpce-page-link:not(.wcpce-page-link--current):not(.wcpce-page-link--prev):not(.wcpce-page-link--next):hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pagination_hover_color',
			array(
				'label'     => esc_html__( 'Hover text', 'woo-card-chef' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#2a2a2a',
				'selectors' => array(
					'{{WRAPPER}} .wcpce-pagination .wcpce-page-link:not(.wcpce-page-link--current):not(.wcpce-page-link--prev):not(.wcpce-page-link--next):hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'pagination_typography',
				'label'    => esc_html__( 'Typography', 'woo-card-chef' ),
				'selector' => '{{WRAPPER}} .wcpce-pagination .wcpce-page-link',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style tab: Lipscore rating filter.
	 *
	 * @since 1.0.79.1
	 * @return void
	 */
	private function register_rating_style_controls(): void {
		$this->start_controls_section(
			'section_rating_style',
			array(
				'label'     => esc_html__( 'Rating', 'woo-card-chef' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_rating' => 'yes',
				),
			)
		);

		$this->add_control(
			'rating_filter_enabled',
			array(
				'label'        => esc_html__( 'Apply CSS filter to stars', 'woo-card-chef' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'woo-card-chef' ),
				'label_off'    => esc_html__( 'No', 'woo-card-chef' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Adjusts star brightness and saturation via a CSS filter. Off = native Lipscore colours.', 'woo-card-chef' ),
			)
		);

		$this->add_control(
			'rating_filter_intensity',
			array(
				'label'       => esc_html__( 'Filter intensity', 'woo-card-chef' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 200,
				'step'        => 1,
				'default'     => 100,
				'selectors'   => array(
					'{{WRAPPER}} .wc-card__rating .lipscore-rating-small' => 'filter: saturate({{VALUE}}%) brightness({{VALUE}}%);',
				),
				'condition'   => array(
					'rating_filter_enabled' => 'yes',
				),
				'description' => esc_html__( '100 = no change. Below 100 = more muted. Above 100 = more vivid/bright.', 'woo-card-chef' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 *
	 * Elementor internals can differ between versions and request types. Accessing
	 * editor or preview services directly without guards can cause frontend fatals
	 * when those services are not initialized.
	 *
	 * @since 1.0.4
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
	 * Returns category options for the Select2 control, but only when needed.
	 *
	 * On the frontend (where the manual category control is never rendered) we
	 * skip the get_terms() query entirely, because register_controls() runs on
	 * every page load. On admin/editor contexts we fetch and return the full
	 * list of product categories.
	 *
	 * @since 1.0.1
	 * @return array
	 */
	private function get_product_category_options_lazy(): array {
		// Only fetch terms in contexts where the editor panel will actually display them.
		$is_editor_context = is_admin() || $this->is_elementor_editor_or_preview() || ( defined( 'DOING_AJAX' ) && DOING_AJAX );

		if ( ! $is_editor_context ) {
			return array();
		}

		return $this->get_product_category_options();
	}

	/**
	 * Builds the options array for the manual category Select2 control.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_product_category_options(): array {
		$options = array();

		// Guard against running this in contexts where WC isn't loaded yet.
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return $options;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[ $term->term_id ] = $term->name;
		}

		return $options;
	}

	/**
	 * Renders the widget on the frontend (and in the editor preview).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function render(): void {
		// Bail early if WooCommerce is not loaded for some reason.
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();
		$result   = $this->get_products( $settings );
		$products = $result['products'];

		// Editor notices come after the query so render_editor_notices() can
		// check whether the query returned results (needed for the N1 notice).
		$this->render_editor_notices( $settings, $products );

		$source             = isset( $settings['source'] ) ? $settings['source'] : 'auto';
		$pagination_enabled = in_array( $source, array( 'auto', 'manual' ), true ) && 'yes' === ( $settings['enable_pagination'] ?? '' );

		echo '<div class="wcpce-grid-section">';

		if ( empty( $products ) ) {
			$this->render_empty_state( $settings );
			echo '</div>';
			return;
		}

		// Output the SVG sprite once before the grid.
		WCPCE_Card_Renderer::render_svg_sprite();

		echo '<ul class="products wc-card-grid" role="list">';

		// PERF P-A + P-B: bulk-prime all attachment posts and metadata before
		// rendering the cards. Without this, wp_get_attachment_image() and
		// wp_attachment_is() each trigger their own get_post() / meta lookups
		// per card — for a 12-card grid that's 24-48 individual queries on a
		// cold cache. After priming they all read from the WP object cache.
		// Delegated to WCPCE_Image_Helper (Phase 6, R4) — behaviour unchanged.
		WCPCE_Image_Helper::prime_attachment_caches( $products, $settings );
		if ( 'yes' === ( $settings['show_custom_labels'] ?? 'yes' ) && class_exists( 'WCPCE_Product_Labels' ) ) {
			WCPCE_Product_Labels::prime_product_label_caches( $products );
		}

		$widget_id = $this->get_id();

		$index = 0;
		foreach ( $products as $product ) {
			echo '<li class="product wcpce-grid-item" role="listitem">';
			WCPCE_Card_Renderer::render_card( $product, $settings, $index, $widget_id );
			echo '</li>';
			$index++;
		}
		echo '</ul>';

		if ( $pagination_enabled ) {
			$this->render_pagination( $result['paged'], $result['max_num_pages'], $settings );
		}

		echo '</div>';
	}

	/**
	 * Outputs an inline SVG sprite with all icons used by the cards.
	 *
	 * Visually hidden via inline attributes (width=0, height=0, position:absolute).
	 * Cards reference symbols from this sprite using <use href="#wcpce-icon-..."/>
	 * which renders identically to inline SVGs but without per-card duplication.
	 *
	 * The sprite is output at most once per page-load regardless of how many
	 * widget instances appear, so multi-grid pages don't get duplicate symbol IDs.
	 *
	 * @since 1.0.21
	 * @return void
	 */
	private function render_svg_sprite(): void {
		static $rendered = false;
		if ( $rendered ) {
			return;
		}
		$rendered = true;

		echo '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute;width:0;height:0;overflow:hidden;" aria-hidden="true" focusable="false">';
		echo '<defs>';
		// Check icon (USP bullets).
		echo '<symbol id="wcpce-icon-check" viewBox="0 0 16 16">';
		echo '<path d="M3 8.5L6.5 12L13 4.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>';
		echo '</symbol>';
		// Truck icon (shipping pill).
		echo '<symbol id="wcpce-icon-truck" viewBox="0 0 16 16">';
		echo '<path d="M1.5 4.5h8v6h-8v-6zM9.5 7h3l2 2v1.5h-5V7z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>';
		echo '<circle cx="4.5" cy="11.5" r="1" fill="none" stroke="currentColor" stroke-width="1.4"/>';
		echo '<circle cx="11.5" cy="11.5" r="1" fill="none" stroke="currentColor" stroke-width="1.4"/>';
		echo '</symbol>';
		// Leaf icon (PFAS-vrij badge). Original from uploaded leaf-icon-35699.svg,
		// viewBox normalised to 0 0 3 3.35, fill set to currentColor for CSS inheritance.
		echo '<symbol id="wcpce-icon-leaf" viewBox="0 0 2.996769 3.3520503">';
		echo '<path fill="currentColor" d="M 0.11367774,3.3348666 C 0.02320601,3.2929796 0.00890051,3.1786555 0.08832631,3.1322685 0.10391023,3.1231682 0.13260497,3.1181068 0.19506976,3.1134495 0.29652413,3.1058845 0.33914794,3.0987479 0.41633848,3.0764122 0.58186152,3.0285133 0.78884167,2.9229141 1.0409714,2.7577303 l 0.1132021,-0.074165 -0.029791,-0.0056 C 0.89175531,2.6342447 0.64950905,2.4417489 0.53054831,2.2060889 0.47187517,2.0898579 0.4486248,1.9900765 0.44822565,1.8527917 0.44779733,1.705458 0.47530363,1.5906616 0.54655753,1.4424094 0.68311158,1.1582923 0.92335288,0.89743049 1.2030447,0.72957453 1.3955841,0.61402279 1.5537682,0.54886654 1.9505712,0.42166842 2.2190293,0.33561226 2.3110565,0.30175807 2.421363,0.24847776 2.5480886,0.18726658 2.6454532,0.11580257 2.7124265,0.0348413 2.7323853,0.0107136 2.7506579,-0.00675553 2.7530322,-0.00397989 2.760681,0.00496356 2.8264941,0.23670288 2.8672214,0.39810327 2.9817216,0.85186416 3.0333776,1.2295717 3.0331945,1.6116957 3.0331031,1.8023051 3.0278164,1.8945448 3.0092418,2.0296231 2.9230828,2.6561979 2.6237012,3.0912894 2.1998487,3.2059148 2.1429872,3.2212921 2.1232265,3.2232829 2.0287943,3.2231472 1.9312935,3.2230117 1.9153688,3.2212167 1.8452901,3.2026511 1.6564554,3.1525904 1.5237147,3.0750108 1.4428613,2.9674522 L 1.4125374,2.9271126 1.534072,2.805339 C 1.9661414,2.372421 2.2887695,1.81821 2.4826704,1.1758359 2.5025544,1.1099621 2.5178789,1.0551211 2.5167249,1.053967 2.5155666,1.0528208 2.4986456,1.088693 2.4791133,1.1337001 2.3377437,1.4594496 2.1378337,1.7941674 1.9161261,2.0763332 1.7675395,2.2654387 1.5155865,2.5302833 1.3481611,2.6733585 1.086292,2.8971422 0.66337924,3.1659788 0.37329167,3.2930623 0.24743492,3.3481985 0.16955853,3.3607387 0.11367774,3.3348666 Z"/>';
		echo '</symbol>';
		echo '</defs>';
		echo '</svg>';
	}

	/**
	 * Returns products and pagination data for the current widget settings.
	 *
	 * Auto mode uses the main WooCommerce archive query including its native
	 * pagination metadata. Manual mode returns the full result from
	 * run_manual_query() using the widget's own pagination.
	 *
	 * @since 1.0.0
	 * @param array $settings Widget settings.
	 * @return array { products: WC_Product[], max_num_pages: int, paged: int }
	 */
	private function get_products( array $settings ): array {
		$source = isset( $settings['source'] ) ? $settings['source'] : 'auto';

		// Detect Elementor editor / preview context so we can show fallback content
		// when no real archive query is available (e.g. designing the template before
		// any category has been picked in Preview Settings).
		$is_editor = $this->is_elementor_editor_or_preview();

		if ( 'auto' === $source ) {
			global $wp_query;
			// Use the main archive query if it has products and we're on an archive.
			if ( ( is_shop() || is_product_taxonomy() || is_post_type_archive( 'product' ) ) && ! empty( $wp_query->posts ) ) {
				return array(
					'products'      => $this->posts_to_products( $wp_query->posts ),
					'max_num_pages' => max( 1, (int) $wp_query->max_num_pages ),
					'paged'         => $this->get_current_archive_pagination_page(),
				);
			}

			// Fallback for the editor preview: show recent products so the user has
			// something to design against.
			if ( $is_editor ) {
				$fallback_limit = 12;
				return array(
					'products'      => $this->run_fallback_query( $fallback_limit ),
					'max_num_pages' => 1,
					'paged'         => 1,
				);
			}

			// Otherwise nothing to show.
			return array( 'products' => array(), 'max_num_pages' => 1, 'paged' => 1 );
		}

		// Manual source: run a custom WP_Query.
		return $this->run_manual_query( $settings );
	}

	/**
	 * Converts an array of post objects into WC_Product instances, skipping invalid ones.
	 *
	 * Primes the WooCommerce product object cache before iterating so all product
	 * data is fetched in a single query rather than one per product.
	 *
	 * @since 1.0.0
	 * @param array $posts Array of WP_Post objects.
	 * @return WC_Product[]
	 */
	private function posts_to_products( array $posts ): array {
		// Bulk-prime the WC product cache. Available since WooCommerce 3.9.
		// Without this, wc_get_product() hits the DB individually for each product.
		if ( ! empty( $posts ) && function_exists( 'wc_prime_caches_for_products' ) ) {
			wc_prime_caches_for_products( wp_list_pluck( $posts, 'ID' ) );
		}

		$products = array();
		foreach ( $posts as $post ) {
			$product = wc_get_product( $post );
			if ( $product && $product->is_visible() ) {
				$products[] = $product;
			}
		}
		return $products;
	}

	/**
	 * Runs a fallback query for the editor preview when no archive context exists.
	 *
	 * Applies the same catalog visibility filter as the main query so the editor
	 * preview doesn't show products that are hidden from the catalog.
	 *
	 * @since 1.0.0
	 * @param int $limit Number of products.
	 * @return WC_Product[]
	 */
	private function run_fallback_query( int $limit = 12 ): array {
		$tax_query = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'exclude-from-catalog' ),
				'operator' => 'NOT IN',
			),
		);

		// Respect the WooCommerce "Hide out of stock items" catalog setting.
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'outofstock' ),
				'operator' => 'NOT IN',
			);
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'product',
				'posts_per_page' => absint( $limit ),
				'post_status'    => 'publish',
				'no_found_rows'  => true,
				'tax_query'      => $tax_query,
			)
		);
		return $this->posts_to_products( $query->posts );
	}

	/**
	 * Validates and sanitises manual-mode widget settings before query execution.
	 *
	 * Settings are saved as Elementor JSON in wp_postmeta and are only writable
	 * by authenticated editors. Validation here guards against corrupted database
	 * values, direct postmeta edits, and import/export anomalies — not against
	 * unauthenticated user input (there is no live user input path to this method).
	 *
	 * Returns a clean copy of $settings with guaranteed types and whitelisted
	 * values. Unknown keys are passed through unchanged so other callers are
	 * unaffected.
	 *
	 * @since 1.0.75
	 * @param array $settings Raw widget settings from get_settings_for_display().
	 * @return array Sanitised settings array.
	 */
	private function validate_manual_settings( array $settings ): array {
		// Limit: positive integer, clamped to 1–48.
		if ( isset( $settings['limit'] ) ) {
			$settings['limit'] = max( 1, min( 48, absint( $settings['limit'] ) ) );
		}

		// Orderby: must be one of the values registered in the SELECT control.
		$allowed_orderby = array( 'menu_order', 'date', 'price', 'price-desc', 'popularity', 'rating', 'rand' );
		if ( isset( $settings['orderby'] ) && ! in_array( $settings['orderby'], $allowed_orderby, true ) ) {
			$settings['orderby'] = 'menu_order';
		}

		// Stock filter: must be one of the three registered options.
		$allowed_stock = array( 'any', 'instock', 'outstock' );
		if ( isset( $settings['filter_stock'] ) && ! in_array( $settings['filter_stock'], $allowed_stock, true ) ) {
			$settings['filter_stock'] = 'any';
		}

		// Switcher fields: Elementor stores 'yes' or ''. Any other value is coerced.
		foreach ( array( 'filter_sale_only', 'filter_featured_only', 'enable_pagination' ) as $switcher ) {
			if ( isset( $settings[ $switcher ] ) && 'yes' !== $settings[ $switcher ] ) {
				$settings[ $switcher ] = '';
			}
		}

		// Category: array of positive integers only.
		if ( isset( $settings['category'] ) && is_array( $settings['category'] ) ) {
			$settings['category'] = array_values(
				array_filter( array_map( 'absint', $settings['category'] ) )
			);
		}

		// Include / exclude IDs: comma-separated string → validated back to string.
		// absint() each token, drop zeros, cap at 200 entries to bound query size.
		foreach ( array( 'include_ids', 'exclude_ids' ) as $id_field ) {
			if ( isset( $settings[ $id_field ] ) && '' !== $settings[ $id_field ] ) {
				$ids = array_filter(
					array_map( 'absint', explode( ',', (string) $settings[ $id_field ] ) )
				);
				$ids = array_slice( array_values( $ids ), 0, 200 );
				$settings[ $id_field ] = implode( ',', $ids );
			}
		}

		return $settings;
	}

	/**
	 * Runs a custom query based on the manual-source widget settings.
	 *
	 * When pagination is enabled, no_found_rows is set to false so WP_Query
	 * calculates max_num_pages. Uses wcpce_paged query var instead of paged
	 * to avoid conflicts with the main WooCommerce archive loop.
	 *
	 * @since 1.0.0
	 * @param array $settings Widget settings.
	 * @return array { products: WC_Product[], query: WP_Query }
	 */
	private function run_manual_query( array $settings ): array {
		$settings           = $this->validate_manual_settings( $settings );
		$pagination_enabled = 'yes' === ( $settings['enable_pagination'] ?? '' );
		$per_page           = isset( $settings['limit'] ) ? absint( $settings['limit'] ) : 12;

		$paged = $pagination_enabled ? $this->get_current_pagination_page() : 1;

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'no_found_rows'  => ! $pagination_enabled,
			'paged'          => $paged,
		);

		// Map the widget orderby setting to WP_Query args using WooCommerce's own
		// catalog ordering helper where possible. This future-proofs against WC
		// storage changes (e.g. HPOS, custom tables) rather than hardcoding meta keys.
		$orderby = isset( $settings['orderby'] ) ? $settings['orderby'] : 'menu_order';
		switch ( $orderby ) {
			case 'date':
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;
			case 'price':
			case 'price-desc':
				// WC's get_catalog_ordering_args() correctly resolves the price meta key
				// for the current WC version including any custom-table migration.
				// We use a named callback so we can remove only our own filter, not all
				// registered hooks on woocommerce_default_catalog_orderby.
				$wc_order         = 'price-desc' === $orderby ? 'DESC' : 'ASC';
				$price_orderby_cb = function() use ( $orderby ) {
					return $orderby;
				};
				add_filter( 'woocommerce_default_catalog_orderby', $price_orderby_cb );
				$ordering_args = WC()->query->get_catalog_ordering_args( 'price', $wc_order );
				remove_filter( 'woocommerce_default_catalog_orderby', $price_orderby_cb );
				$args = array_merge( $args, $ordering_args );
				break;
			case 'popularity':
				$ordering_args = WC()->query->get_catalog_ordering_args( 'popularity', 'DESC' );
				$args          = array_merge( $args, $ordering_args );
				break;
			case 'rating':
				$ordering_args = WC()->query->get_catalog_ordering_args( 'rating', 'DESC' );
				$args          = array_merge( $args, $ordering_args );
				break;
			case 'rand':
				$args['orderby'] = 'rand';
				break;
			case 'menu_order':
			default:
				$args['orderby'] = 'menu_order title';
				$args['order']   = 'ASC';
				break;
		}

		// Category tax query.
		if ( ! empty( $settings['category'] ) && is_array( $settings['category'] ) ) {
			$category_ids = array_map( 'absint', $settings['category'] );
			$category_ids = array_filter( $category_ids );
			if ( ! empty( $category_ids ) ) {
				$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $category_ids,
					),
				);
			}
		}

		// Q1: Sale only filter — restrict to products currently on sale.
		if ( 'yes' === ( $settings['filter_sale_only'] ?? '' ) ) {
			$sale_ids = wc_get_product_ids_on_sale();
			if ( empty( $sale_ids ) ) {
				// No products on sale: bail before running the query.
				return array( 'products' => array(), 'max_num_pages' => 1, 'paged' => $paged );
			}
			$existing_include = ! empty( $args['post__in'] ) ? $args['post__in'] : array();
			$args['post__in'] = empty( $existing_include )
				? $sale_ids
				: array_intersect( $existing_include, $sale_ids );
		}

		// Q2: Featured only filter.
		if ( 'yes' === ( $settings['filter_featured_only'] ?? '' ) ) {
			$args['tax_query'] = isset( $args['tax_query'] ) ? $args['tax_query'] : array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			$args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'featured',
				'operator' => 'IN',
			);
		}

		// Q3: Stock status filter.
		$filter_stock = isset( $settings['filter_stock'] ) ? $settings['filter_stock'] : 'any';
		if ( 'instock' === $filter_stock ) {
			$args['meta_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'key'   => '_stock_status',
				'value' => 'instock',
			);
		} elseif ( 'outstock' === $filter_stock ) {
			$args['meta_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'key'   => '_stock_status',
				'value' => 'outofstock',
			);
		}

		// Q4: Include specific product IDs.
		if ( ! empty( $settings['include_ids'] ) ) {
			$include_ids = array_filter( array_map( 'absint', explode( ',', $settings['include_ids'] ) ) );
			if ( ! empty( $include_ids ) ) {
				$existing = ! empty( $args['post__in'] ) ? $args['post__in'] : array();
				if ( ! empty( $existing ) ) {
					$merged = array_intersect( $existing, $include_ids );
					// Empty intersection means no products can match — bail early.
					if ( empty( $merged ) ) {
						return array( 'products' => array(), 'max_num_pages' => 1, 'paged' => $paged );
					}
					$args['post__in'] = $merged;
				} else {
					$args['post__in'] = $include_ids;
				}
			}
		}

		// Q5: Exclude specific product IDs.
		if ( ! empty( $settings['exclude_ids'] ) ) {
			$exclude_ids = array_filter( array_map( 'absint', explode( ',', $settings['exclude_ids'] ) ) );
			if ( ! empty( $exclude_ids ) ) {
				$existing_exclude = ! empty( $args['post__not_in'] ) ? $args['post__not_in'] : array();
				$args['post__not_in'] = array_merge( $existing_exclude, $exclude_ids );
			}
		}

		// Respect the WC catalog visibility taxonomy by excluding hidden products.
		$args['tax_query'] = isset( $args['tax_query'] ) ? $args['tax_query'] : array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		$args['tax_query'][] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'exclude-from-catalog' ),
			'operator' => 'NOT IN',
		);

		// Respect the WooCommerce "Hide out of stock items" catalog setting.
		// Note: if filter_stock is set to 'outstock' and this option is also on,
		// the query will return zero results — the WC site-wide setting takes precedence.
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'outofstock' ),
				'operator' => 'NOT IN',
			);
		}

		// PF1: Transient caching for manual mode queries.
		//
		// We cache product IDs and the pagination metadata, NOT the WC_Product
		// objects themselves. Caching objects is fragile — they can hold
		// closures, resource handles, or runtime-only state that doesn't
		// survive serialise/unserialise. IDs are always safe to serialise,
		// and posts_to_products() handles cache priming on the way back out.
		//
		// The cache key is an MD5 hash of the fully-resolved WP_Query args, so
		// different widgets with different settings get different cache entries.
		// Random order is never cached — the result changes on every request.
		// TTL is 5 minutes; the cache is also flushed when any product is saved
		// (see WC_Product_Card_Elementor_Plugin::flush_query_cache()).
		//
		// We skip caching in the Elementor editor (preview context) so settings
		// changes are reflected immediately without having to wait out the TTL.
		$is_editor = $this->is_elementor_editor_or_preview();
		$use_cache = ! $is_editor && ( $args['orderby'] ?? '' ) !== 'rand';
		$cache_key = '';

		if ( $use_cache ) {
			// Build the cache key from the resolved args. Using serialize + md5 is
			// safe here — we control the args array and it contains no objects.
			$cache_key = 'wcpce_q_' . md5( serialize( $args ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			$cached    = get_transient( $cache_key );
			if ( is_array( $cached ) && isset( $cached['ids'], $cached['max_num_pages'] ) ) {
				$cached_max_pages = max( 1, (int) $cached['max_num_pages'] );
				if ( $pagination_enabled && $paged > $cached_max_pages ) {
					$paged         = 1;
					$args['paged'] = 1;
					$cache_key     = 'wcpce_q_' . md5( serialize( $args ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
					$cached        = get_transient( $cache_key );
					if ( ! is_array( $cached ) || ! isset( $cached['ids'], $cached['max_num_pages'] ) ) {
						$cached = null;
					}
				}
			}

			if ( is_array( $cached ) && isset( $cached['ids'], $cached['max_num_pages'] ) ) {
				// Hydrate IDs back into WC_Product objects, with cache priming.
				return array(
					'products'      => $this->ids_to_products( $cached['ids'] ),
					'max_num_pages' => (int) $cached['max_num_pages'],
					'paged'         => $paged,
				);
			}
		}

		$query    = new WP_Query( $args );
		if ( $pagination_enabled && $paged > 1 && $query->max_num_pages > 0 && $paged > (int) $query->max_num_pages ) {
			$paged         = 1;
			$args['paged'] = 1;

			if ( $use_cache ) {
				$cache_key = 'wcpce_q_' . md5( serialize( $args ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
				$cached    = get_transient( $cache_key );
				if ( is_array( $cached ) && isset( $cached['ids'], $cached['max_num_pages'] ) ) {
					return array(
						'products'      => $this->ids_to_products( $cached['ids'] ),
						'max_num_pages' => (int) $cached['max_num_pages'],
						'paged'         => $paged,
					);
				}
			}

			$query = new WP_Query( $args );
		}

		$products = $this->posts_to_products( $query->posts );
		$result   = array(
			'products'      => $products,
			'max_num_pages' => (int) $query->max_num_pages,
			'paged'         => $paged,
		);

		if ( $use_cache && $cache_key ) {
			// Cache only the IDs and max_num_pages — never the WC_Product objects.
			$cache_payload = array(
				'ids'           => array_map(
					static function ( $p ) {
						return $p->get_id();
					},
					$products
				),
				'max_num_pages' => (int) $query->max_num_pages,
			);
			set_transient( $cache_key, $cache_payload, 5 * MINUTE_IN_SECONDS );

			// Register the key so the flush routine can find and delete it.
			// We keep a flat array of active keys in a single wp_option rather
			// than using a prefix scan, which has no native WP API support.
			$known_keys   = get_option( 'wcpce_query_cache_keys', array() );
			$known_keys[] = $cache_key;
			$known_keys   = array_unique( $known_keys );
			// Cap at 500 entries to prevent unbounded option growth on high-traffic sites.
			if ( count( $known_keys ) > 500 ) {
				$known_keys = array_slice( $known_keys, -500 );
			}
			update_option( 'wcpce_query_cache_keys', $known_keys, false );
		}

		return $result;
	}

	/**
	 * Hydrates an array of product IDs into WC_Product instances.
	 *
	 * Used by the transient cache layer in run_manual_query() to convert
	 * cached IDs back into product objects without bypassing visibility checks.
	 * Primes the WC product cache in bulk so wc_get_product() reads from
	 * cache rather than running per-product queries.
	 *
	 * @since 1.0.48
	 * @param int[] $ids Array of product IDs from a cache hit.
	 * @return WC_Product[]
	 */
	private function ids_to_products( array $ids ): array {
		if ( empty( $ids ) ) {
			return array();
		}
		if ( function_exists( 'wc_prime_caches_for_products' ) ) {
			wc_prime_caches_for_products( $ids );
		}
		$products = array();
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product && $product->is_visible() ) {
				$products[] = $product;
			}
		}
		return $products;
	}

	/**
	 * Reads the current manual pagination page directly from the request.
	 *
	 * @since 1.0.62
	 * @return int
	 */
	private function get_current_pagination_page(): int {
		if ( isset( $_GET['wcpce_paged'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return max( 1, absint( wp_unslash( $_GET['wcpce_paged'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		return max( 1, absint( get_query_var( 'wcpce_paged', 1 ) ) );
	}

	/**
	 * Reads the current native WooCommerce archive pagination page.
	 *
	 * @since 1.0.71
	 * @return int
	 */
	private function get_current_archive_pagination_page(): int {
		$paged = max( 1, absint( get_query_var( 'paged', 1 ) ) );

		return $paged;
	}

	/**
	 * Builds a pagination URL from the current request path only.
	 *
	 * This deliberately ignores WordPress URL helpers and the current query string.
	 * The namespaced manual pagination markup should not inherit native archive
	 * pagination state or trigger WooCommerce/theme pagination handlers.
	 *
	 * Page 1 never contains wcpce_paged. Page 2+ explicitly adds wcpce_paged=N.
	 *
	 * @since 1.0.70
	 * @param int   $page     Target page number.
	 * @param array $settings Widget settings, reserved for future per-widget URL rules.
	 * @return string
	 */
	private function build_pagination_url( int $page, array $settings = array() ): string {
		unset( $settings );

		$page     = max( 1, absint( $page ) );
		$base_url = $this->get_pagination_current_path_base_url();

		if ( $page <= 1 ) {
			return $base_url;
		}

		return $base_url . '?wcpce_paged=' . $page;
	}

	/**
	 * Builds a native WooCommerce archive pagination URL for auto mode.
	 *
	 * @since 1.0.71
	 * @param int $page Target page number.
	 * @return string
	 */
	private function build_archive_pagination_url( int $page ): string {
		$page = max( 1, absint( $page ) );

		if ( function_exists( 'get_pagenum_link' ) ) {
			$url = get_pagenum_link( $page, false );
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		$base_url = $this->get_pagination_current_path_base_url();

		return $page <= 1 ? $base_url : $base_url . '?paged=' . $page;
	}

	/**
	 * Selects the correct pagination URL builder for the current source mode.
	 *
	 * @since 1.0.71
	 * @param int   $page     Target page number.
	 * @param array $settings Widget settings.
	 * @return string
	 */
	private function build_pagination_link_url( int $page, array $settings ): string {
		$source = isset( $settings['source'] ) ? $settings['source'] : 'auto';

		if ( 'auto' === $source ) {
			return $this->build_archive_pagination_url( $page );
		}

		return $this->build_pagination_url( $page, $settings );
	}

	/**
	 * Gets the current request path without query args.
	 *
	 * @since 1.0.70
	 * @return string
	 */
	private function get_pagination_current_path_base_url(): string {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_uri = is_string( $request_uri ) ? $request_uri : '/';
		$path        = preg_replace( '/[?#].*$/', '', $request_uri );

		if ( ! is_string( $path ) || '' === $path ) {
			$path = '/';
		}

		return '/' . ltrim( $path, '/' );
	}

	/**
	 * Renders numbered pagination below the product grid.
	 *
	 * Custom implementation with namespaced markup. Manual mode uses the widget's
	 * wcpce_paged query var; auto mode uses native WooCommerce archive page URLs.
	 *
	 * Page links are still rendered with wcpce-page-link classes so themes and
	 * filter plugins do not mistake them for WooCommerce page-numbers markup.
	 *
	 * @since 1.0.36
	 * @param int   $paged         Current page number.
	 * @param int   $max_num_pages Total number of pages.
	 * @param array $settings      Widget settings (unused; kept for future use).
	 * @return void
	 */
	private function render_pagination( int $paged, int $max_num_pages, array $settings = array() ): void {
		if ( $max_num_pages <= 1 ) {
			return;
		}

		$current = max( 1, (int) $paged );
		$total   = max( 1, (int) $max_num_pages );

		// Determine which page numbers to show: page 1, current ± 2, and last page.
		$end_size = 1;
		$mid_size = 2;
		$visible  = array();
		for ( $n = 1; $n <= $total; $n++ ) {
			$is_edge   = $n <= $end_size || $n > $total - $end_size;
			$is_middle = $n >= $current - $mid_size && $n <= $current + $mid_size;
			if ( $is_edge || $is_middle ) {
				$visible[] = $n;
			}
		}

		$prev_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>';
		$next_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>';

		echo '<nav class="wcpce-pagination" aria-label="' . esc_attr__( 'Product pagination', 'woo-card-chef' ) . '">';
		echo '<ul class="wcpce-pagination__list">';

		if ( $current > 1 ) {
			$target_page = max( 1, (int) $current - 1 );
			$href        = $this->build_pagination_link_url( $target_page, $settings );
			echo '<li class="wcpce-pagination__item"><a class="wcpce-page-link wcpce-page-link--prev" href="' . esc_url( $href ) . '" aria-label="' . esc_attr__( 'Vorige pagina', 'woo-card-chef' ) . '">' . $prev_svg . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$prev_n = 0;
		foreach ( $visible as $n ) {
			if ( $prev_n && $n - $prev_n > 1 ) {
				echo '<li class="wcpce-pagination__item"><span class="wcpce-page-link wcpce-page-link--dots">&hellip;</span></li>';
			}

			if ( $n === $current ) {
				echo '<li class="wcpce-pagination__item"><span aria-current="page" aria-label="' . esc_attr( sprintf( /* translators: page number */ __( 'Huidige pagina, pagina %d', 'woo-card-chef' ), $n ) ) . '" class="wcpce-page-link wcpce-page-link--current">' . (int) $n . '</span></li>';
			} else {
				$target_page = max( 1, (int) $n );
				$href        = $this->build_pagination_link_url( $target_page, $settings );
				echo '<li class="wcpce-pagination__item"><a class="wcpce-page-link" href="' . esc_url( $href ) . '" aria-label="' . esc_attr( sprintf( /* translators: page number */ __( 'Pagina %d', 'woo-card-chef' ), $n ) ) . '">' . (int) $n . '</a></li>';
			}
			$prev_n = $n;
		}

		if ( $current < $total ) {
			$target_page = max( 1, (int) $current + 1 );
			$href        = $this->build_pagination_link_url( $target_page, $settings );
			echo '<li class="wcpce-pagination__item"><a class="wcpce-page-link wcpce-page-link--next" href="' . esc_url( $href ) . '" aria-label="' . esc_attr__( 'Volgende pagina', 'woo-card-chef' ) . '">' . $next_svg . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</ul>';
		echo '</nav>';
	}

	/**
	 * Renders a placeholder when no products match.
	 *
	 * Respects the E1 (configurable text) and E2 (hide option) settings.
	 * Auto mode uses the default customer-facing message. Manual mode respects
	 * the show_empty_state toggle and empty_state_text controls.
	 *
	 * @since 1.0.0
	 * @param array $settings Widget settings.
	 * @return void
	 */
	private function render_empty_state( array $settings = array() ): void {
		$source = isset( $settings['source'] ) ? $settings['source'] : 'auto';

		// E2: in manual mode, allow hiding the empty state entirely.
		if ( 'manual' === $source && 'yes' !== ( $settings['show_empty_state'] ?? 'yes' ) ) {
			return;
		}

		// E1: use configurable text in manual mode, fallback message otherwise.
		// Only esc_html() at output is needed — sanitize_text_field() here would
		// double-encode characters like apostrophes if the editor uses one.
		if ( 'manual' === $source && ! empty( $settings['empty_state_text'] ) ) {
			$message = $settings['empty_state_text'];
		} else {
			$message = __( 'Geen producten gevonden.', 'woo-card-chef' );
		}

		echo '<ul class="products wc-card-grid wc-card-grid--empty" role="list">';
		echo '<li class="product wcpce-grid-item wcpce-grid-item--empty" role="listitem">';
		echo '<p>' . esc_html( $message ) . '</p>';
		echo '</li>';
		echo '</ul>';
	}

	/**
	 * Renders editor-only notices that explain dynamic dependencies.
	 *
	 * @since 1.0.3
	 * @param array        $settings Widget settings.
	 * @param WC_Product[] $products The products returned by the query (may be empty).
	 * @return void
	 */
	private function render_editor_notices( array $settings, array $products = array() ): void {
		if ( ! $this->is_elementor_editor_or_preview() ) {
			return;
		}

		$messages = array();

		if ( 'auto' === ( $settings['source'] ?? 'auto' ) && ! ( is_shop() || is_product_taxonomy() ) ) {
			$messages[] = esc_html__( 'Editor preview: Auto mode is showing fallback products because no product archive query is active here.', 'woo-card-chef' );
		}

		// N1: manual query returned zero products.
		if ( 'manual' === ( $settings['source'] ?? 'auto' ) && empty( $products ) ) {
			$messages[] = esc_html__( 'Manual query returned no products. Check your category selection, product IDs, sale/featured filters, and stock settings.', 'woo-card-chef' );
		}

		if ( 'yes' === ( $settings['show_usps'] ?? 'yes' ) && ! function_exists( 'get_fields' ) ) {
			$messages[] = esc_html__( 'ACF or ACF Pro is not active, so product USPs will not render.', 'woo-card-chef' );
		}

		if ( 'yes' === ( $settings['show_rating'] ?? 'yes' ) ) {
			$messages[] = esc_html__( 'Lipscore ratings are filled by Lipscore JavaScript on the frontend. Empty products may show no stars in the editor.', 'woo-card-chef' );
		}

		if ( empty( $messages ) ) {
			return;
		}

		echo '<div class="wc-card-editor-notices">';
		foreach ( $messages as $message ) {
			echo '<div class="wc-card-editor-notice">' . esc_html( $message ) . '</div>';
		}
		echo '</div>';
	}

	/**
	 * Renders a single product card by including the template partial.
	 *
	 * The template receives $product, $settings, and a $card data array with
	 * pre-computed values like the discount percentage and savings amount.
	 *
	 * @since 1.0.0
	 * @param WC_Product $product   The product instance.
	 * @param array      $settings  Widget settings.
	 * @param int        $index     Zero-based position of this card in the grid.
	 *                              Used to apply fetchpriority=high to the first
	 *                              few above-the-fold images for LCP optimization.
	 * @return void
	 */
	private function render_card( \WC_Product $product, array $settings, int $index = 0, string $widget_id = '' ): void {
		// E4: cache the file_exists check — the template path never changes within
		// a request, so checking it once per card is unnecessary filesystem overhead.
		static $template_exists = null;
		if ( null === $template_exists ) {
			$template_exists = file_exists( WCPCE_PLUGIN_DIR . 'templates/card.php' );
		}
		if ( ! $template_exists ) {
			return;
		}
		$card     = $this->compute_card_data( $product, $settings, $index );
		$template = WCPCE_PLUGIN_DIR . 'templates/card.php';
		include $template;
	}

	/**
	 * Computes derived data for the card template (discount, badge text, savings, etc).
	 *
	 * H9: get_variation_prices() is only called when discount-dependent features
	 * are enabled (badge or savings line). When both are off, variable products
	 * skip the full variation price traversal and use get_price() instead, which
	 * is a single cached meta read. The shipping pill threshold and aria-label
	 * both fall back to display_price in that case — functionally identical for
	 * non-sale products.
	 *
	 * @since 1.0.0
	 * @param WC_Product $product  The product instance.
	 * @param array      $settings Widget settings.
	 * @param int        $index    Zero-based position of this card in the grid.
	 *                             Used to apply fetchpriority=high to the first few
	 *                             above-the-fold images for LCP optimisation.
	 * @return array
	 */
	private function compute_card_data( \WC_Product $product, array $settings, int $index = 0 ): array {
		$data = array(
			'is_on_sale'       => false,
			'is_variable'      => $product->is_type( 'variable' ),
			'regular_price'    => 0.0,
			'sale_price'       => 0.0,
			'display_price'    => 0.0,
			'discount_percent' => 0,
			'savings_amount'   => 0.0,
			'show_badge'       => false,
			'badge_text'       => '',
			'index'            => $index,
		);

		// H9: only run the full price calculation (which calls get_variation_prices()
		// for variable products) when at least one discount-dependent feature is on.
		// The badge and savings line are the only features that need discount_percent
		// or savings_amount. The shipping pill and aria-label use display_price, which
		// is always populated via the cheap get_price() path below.
		$needs_discount_calc = 'yes' === ( $settings['show_badge'] ?? 'yes' )
			|| 'yes' === ( $settings['show_savings_line'] ?? '' );

		if ( $needs_discount_calc ) {
			// Compute regular and sale prices, including the variable-product max discount
			// logic. mixed_discounts is only used here to drive the "Tot " badge prefix.
			// Delegated to WCPCE_Price_Helper (Phase 6, R2) — behaviour unchanged.
			$prices                   = WCPCE_Price_Helper::get_product_price_data( $product );
			$data['regular_price']    = $prices['regular_price'];
			$data['sale_price']       = $prices['sale_price'];
			$data['display_price']    = $prices['display_price'];
			$data['discount_percent'] = $prices['discount_percent'];
			$data['savings_amount']   = $prices['savings_amount'];
			$mixed_discounts          = $prices['mixed_discounts'];

			// is_on_sale reflects computed reality: WC's flag PLUS a verified positive
			// discount. Guards against products WC marks on-sale but with no valid
			// variation sale price (misconfigured products, or expired sales).
			$data['is_on_sale'] = $product->is_on_sale() && $data['discount_percent'] > 0;
		} else {
			// Discount features off: skip get_variation_prices() entirely.
			// Populate display_price from the cheap cached get_price() call so the
			// shipping pill threshold and aria-label still have a price to work with.
			$data['display_price'] = floatval( $product->get_price() );
			$mixed_discounts       = false;
		}

		// Cache get_price_html() for non-sale products. This method runs through WC's full
		// filter chain and is the most expensive call on a typical card render — especially
		// for variable products where it calls get_variation_prices() internally.
		// For sale products, the template uses wc_price() directly so we skip it here.
		// Both the price block and the aria-label computation in card.php read this value,
		// so caching it once saves a second invocation per card.
		$data['price_html'] = $data['is_on_sale'] ? '' : $product->get_price_html();

		// Decide whether to show the discount badge and produce its text.
		// Delegated to WCPCE_Badge_Helper (Phase 6, R1) — behaviour unchanged.
		// $mixed_discounts is a by-product of the price computation above and is
		// passed in because it drives the variable-product "Tot " prefix.
		$badge = WCPCE_Badge_Helper::compute_badge_data( $data, $settings, $mixed_discounts );
		$data['show_badge'] = $badge['show_badge'];
		$data['badge_text'] = $badge['badge_text'];

		return $data;
	}

}
