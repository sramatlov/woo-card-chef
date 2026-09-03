<?php
/**
 * Standalone regression checks for Product Card Grid category exclusions.
 *
 * Run from the repository root:
 * php tools/test_product_card_category_exclusion.php
 */

namespace Elementor {
	class Widget_Base {}
}

namespace {
	define( 'ABSPATH', __DIR__ . '/' );

	$GLOBALS['wcpce_test_get_terms_args'] = array();
	$GLOBALS['wcpce_test_query_observer'] = null;

	function absint( $value ): int {
		return abs( (int) $value );
	}

	function get_query_var( $key, $default = '' ) {
		return $GLOBALS['wp_query']->query_vars[ $key ] ?? $default;
	}

	function wp_parse_args( $args ): array {
		if ( is_array( $args ) ) {
			return $args;
		}

		parse_str( (string) $args, $parsed );

		return $parsed;
	}

	function is_admin(): bool {
		return true;
	}

	function is_shop(): bool {
		return true;
	}

	function is_product_taxonomy(): bool {
		return false;
	}

	function is_post_type_archive( $post_type ): bool {
		return 'product' === $post_type;
	}

	function taxonomy_exists( $taxonomy ): bool {
		return 'product_cat' === $taxonomy;
	}

	function get_terms( array $args ): array {
		$GLOBALS['wcpce_test_get_terms_args'][] = $args;

		return array(
			(object) array(
				'term_id' => 10,
				'name'    => 'Parent without direct products',
			),
		);
	}

	function is_wp_error( $value ): bool {
		return false;
	}

	function get_option( $key, $default = false ) {
		return 'woocommerce_hide_out_of_stock_items' === $key ? 'no' : $default;
	}

	function wc_get_product( $post ): WCPCE_Test_Product {
		$id = is_object( $post ) && isset( $post->ID ) ? (int) $post->ID : (int) $post;

		return new WCPCE_Test_Product( $id );
	}

	final class WCPCE_Test_Product {
		private $id;

		public function __construct( int $id ) {
			$this->id = $id;
		}

		public function get_id(): int {
			return $this->id;
		}

		public function is_visible(): bool {
			return true;
		}
	}

	class WP_Query {
		public $query = array();
		public $query_vars = array();
		public $posts = array();
		public $max_num_pages = 0;
		public static $last_args = array();

		public function __construct( $args = null ) {
			if ( is_array( $args ) ) {
				$this->query( $args );
			}
		}

		public function query( array $args ): array {
			$this->query      = $args;
			$this->query_vars = $args;

			if ( is_callable( $GLOBALS['wcpce_test_query_observer'] ) ) {
				call_user_func( $GLOBALS['wcpce_test_query_observer'], $this );
			}

			self::$last_args    = $this->query_vars;
			$this->posts        = array( (object) array( 'ID' => 501 ) );
			$this->max_num_pages = 4;

			return $this->posts;
		}

		public function is_main_query(): bool {
			return $this === $GLOBALS['wp_the_query'];
		}
	}

	function wcpce_test_assert( $condition, string $message ): void {
		if ( ! $condition ) {
			throw new \RuntimeException( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- standalone CLI assertion.
		}
	}

	function wcpce_test_private_method( string $name ): \ReflectionMethod {
		$method = new \ReflectionMethod( 'WC_Product_Card_Elementor_Widget', $name );
		$method->setAccessible( true );

		return $method;
	}

	require_once __DIR__ . '/../wc-product-card-elementor/includes/Traits/trait-custom-label-controls.php';
	require_once __DIR__ . '/../wc-product-card-elementor/includes/Widgets/class-product-card-widget.php';

	$widget = new WC_Product_Card_Elementor_Widget();

	$sanitize = wcpce_test_private_method( 'sanitize_category_ids' );
	$ids      = $sanitize->invoke( $widget, array( 7, '8', 0, -9, 'invalid', 8 ) );
	wcpce_test_assert( array( 7, 8, 9 ) === $ids, 'Category IDs must be positive, unique integers.' );

	$build_clause = wcpce_test_private_method( 'build_category_exclusion_clause' );
	$clause       = $build_clause->invoke( $widget, array( 21, 22 ) );
	wcpce_test_assert( 'product_cat' === $clause['taxonomy'], 'The exclusion must target product_cat.' );
	wcpce_test_assert( 'NOT IN' === $clause['operator'], 'The exclusion must use NOT IN.' );
	wcpce_test_assert( true === $clause['include_children'], 'Child categories must be excluded.' );

	$category_options = wcpce_test_private_method( 'get_product_category_options' );
	$options          = $category_options->invoke( $widget, false );
	wcpce_test_assert( isset( $options[10] ), 'Empty parent categories must remain selectable for exclusion.' );
	wcpce_test_assert( false === $GLOBALS['wcpce_test_get_terms_args'][0]['hide_empty'], 'The exclusion picker must request empty categories.' );

	$main_query                = new WP_Query();
	$main_query->query         = array(
		'post_type'     => 'product',
		'orderby'       => 'price',
		'custom_filter' => 'kept',
	);
	$main_query->query_vars    = array( 'paged' => 2 );
	$main_query->posts         = array( (object) array( 'ID' => 100 ) );
	$main_query->max_num_pages = 9;
	$GLOBALS['wp_query']       = $main_query;
	$GLOBALS['wp_the_query']   = $main_query;

	$replayed_as_main = false;
	$GLOBALS['wcpce_test_query_observer'] = static function ( WP_Query $query ) use ( &$replayed_as_main ): void {
		$replayed_as_main = $query->is_main_query();
		$query->query_vars['woocommerce_ordering_replayed'] = true;
	};

	$run_archive = wcpce_test_private_method( 'run_archive_query_with_exclusions' );
	$result      = $run_archive->invoke( $widget, array( 30 ) );

	wcpce_test_assert( $replayed_as_main, 'The Auto replay must pass main-query-only WooCommerce hooks.' );
	wcpce_test_assert( $GLOBALS['wp_query'] === $main_query, 'The global wp_query must be restored after Auto replay.' );
	wcpce_test_assert( $GLOBALS['wp_the_query'] === $main_query, 'The global wp_the_query must be restored after Auto replay.' );
	wcpce_test_assert( true === WP_Query::$last_args['woocommerce_ordering_replayed'], 'WooCommerce-style query changes must survive the replay.' );
	wcpce_test_assert( 'kept' === WP_Query::$last_args['custom_filter'], 'Original archive request arguments must be retained.' );
	wcpce_test_assert( 2 === WP_Query::$last_args['paged'], 'Native archive pagination must be retained.' );
	wcpce_test_assert( true === WP_Query::$last_args['wcpce_archive_exclusion_query'], 'The replay query must be identifiable.' );
	wcpce_test_assert( 'NOT IN' === WP_Query::$last_args['tax_query'][0]['operator'], 'Auto mode must append the category exclusion.' );
	wcpce_test_assert( 4 === $result['max_num_pages'] && 2 === $result['paged'], 'Auto mode must return replay pagination metadata.' );
	wcpce_test_assert( 501 === $result['products'][0]->get_id(), 'Auto replay posts must become visible WooCommerce products.' );

	$main_query->posts = array();
	$get_products      = wcpce_test_private_method( 'get_products' );
	$result            = $get_products->invoke( $widget, array( 'source' => 'auto', 'exclude_categories' => array( 30 ) ) );
	wcpce_test_assert( 501 === $result['products'][0]->get_id(), 'Auto exclusions must replay even when the original archive page is empty.' );
	$main_query->posts = array( (object) array( 'ID' => 100 ) );

	$GLOBALS['wcpce_test_query_observer'] = static function ( WP_Query $query ): void {
		if ( $query->is_main_query() ) {
			throw new \RuntimeException( 'Simulated query-hook failure.' );
		}
	};

	$thrown = false;
	try {
		$run_archive->invoke( $widget, array( 30 ) );
	} catch ( \RuntimeException $exception ) {
		$thrown = true;
	}
	wcpce_test_assert( $thrown, 'The simulated query failure must reach the caller.' );
	wcpce_test_assert( $GLOBALS['wp_query'] === $main_query, 'wp_query must also be restored when a query hook throws.' );
	wcpce_test_assert( $GLOBALS['wp_the_query'] === $main_query, 'wp_the_query must also be restored when a query hook throws.' );

	$GLOBALS['wcpce_test_query_observer'] = null;
	$run_manual = wcpce_test_private_method( 'run_manual_query' );
	$run_manual->invoke(
		$widget,
		array(
			'enable_pagination'  => '',
			'limit'              => 12,
			'orderby'            => 'rand',
			'category'           => array( 40 ),
			'exclude_categories' => array( 41 ),
			'filter_sale_only'   => '',
			'filter_featured_only' => '',
			'filter_stock'       => 'any',
			'include_ids'        => '',
			'exclude_ids'        => '',
		)
	);

	$product_category_clauses = array_values(
		array_filter(
			WP_Query::$last_args['tax_query'],
			static function ( array $tax_clause ): bool {
				return 'product_cat' === ( $tax_clause['taxonomy'] ?? '' );
			}
		)
	);
	wcpce_test_assert( 2 === count( $product_category_clauses ), 'Manual mode must contain include and exclude category clauses.' );
	wcpce_test_assert( 'NOT IN' === $product_category_clauses[1]['operator'], 'Manual mode must apply the shared exclusion clause.' );
	wcpce_test_assert( true === $product_category_clauses[1]['include_children'], 'Manual exclusions must include child categories.' );

	$run_fallback = wcpce_test_private_method( 'run_fallback_query' );
	$run_fallback->invoke( $widget, 12, array( 50 ) );
	wcpce_test_assert( 'NOT IN' === WP_Query::$last_args['tax_query'][1]['operator'], 'Editor fallback must apply category exclusions.' );

	echo "Product Card category exclusion regression checks passed.\n";
}
