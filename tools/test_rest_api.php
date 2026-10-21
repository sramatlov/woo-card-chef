<?php
/**
 * Standalone REST contract regressions using the real plugin field definitions.
 * WordPress/ACF persistence doubles do not replace staging integration tests.
 */
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['routes'] = $GLOBALS['terms'] = $GLOBALS['term_meta'] = $GLOBALS['relationships'] = $GLOBALS['acf_values'] = $GLOBALS['acf_defs'] = array();
$GLOBALS['role'] = 'admin';
$GLOBALS['pro'] = true;
$GLOBALS['acf_available'] = true;
$GLOBALS['validation_errors'] = array();
$GLOBALS['writes'] = $GLOBALS['saves'] = $GLOBALS['flushes'] = $GLOBALS['assertions'] = 0;
$GLOBALS['fail_key'] = '';

class WP_Error {
	public $errors = array();
	public $data = array();
	public function __construct( $code = '', $message = '', $data = array() ) { if ( $code ) { $this->add( $code, $message, $data ); } }
	public function add( $code, $message, $data = array() ): void { $this->errors[$code][] = $message; $this->data[$code] = $data; }
	public function has_errors(): bool { return (bool) $this->errors; }
	public function get_error_codes(): array { return array_keys( $this->errors ); }
	public function get_error_code(): string { return array_key_first( $this->errors ) ?? ''; }
	public function get_error_messages( $code ): array { return $this->errors[$code] ?? array(); }
	public function get_error_message(): string { return $this->errors[$this->get_error_code()][0] ?? ''; }
}
class WP_REST_Response {
	public $data; public $status; public $headers = array();
	public function __construct( $data, $status = 200 ) { $this->data = $data; $this->status = $status; }
	public function header( $key, $value ): void { $this->headers[$key] = $value; }
}
class WP_REST_Request implements ArrayAccess {
	public $params; public $body;
	public function __construct( array $params = array(), string $body = '{}' ) { $this->params = $params; $this->body = $body; }
	public function get_body(): string { return $this->body; }
	public function get_url_params(): array { return $this->params; }
	#[ReturnTypeWillChange]
	public function offsetGet( $key ) { return $this->params[$key] ?? null; }
	public function offsetSet( $key, $value ): void { $this->params[$key] = $value; }
	public function offsetUnset( $key ): void { unset( $this->params[$key] ); }
	public function offsetExists( $key ): bool { return isset( $this->params[$key] ); }
}
class WP_Term {
	public $term_id; public $name; public $taxonomy;
	public function __construct( int $id, string $name, string $taxonomy ) { $this->term_id = $id; $this->name = $name; $this->taxonomy = $taxonomy; }
}
class WC_Product {
	public function read_meta_data( $force ): void {}
	public function set_date_modified( $time ): void { $GLOBALS['modified'] = $time; }
	public function save(): int { ++$GLOBALS['saves']; return 100; }
}
class WC_Product_Card_Elementor_Plugin {
	public static function instance(): self { return new self(); }
	public function flush_query_cache(): void { ++$GLOBALS['flushes']; }
}
function __( $text, $domain = '' ): string { return $text; }
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
function rest_convert_error_to_response( WP_Error $error ): WP_REST_Response { return new WP_REST_Response( array( 'code' => $error->get_error_code(), 'data' => $error->data[$error->get_error_code()] ), $error->data[$error->get_error_code()]['status'] ?? 500 ); }
function current_user_can( $cap, $id = 0 ): bool { return 'admin' === $GLOBALS['role'] && ( 'edit_post' !== $cap || 101 !== $id ) || ( 'manager' === $GLOBALS['role'] && 'manage_woocommerce' === $cap ); }
function register_rest_route( $ns, $route, $args ): void { $GLOBALS['routes'][$ns . $route] = $args; }
function rest_url( $path ): string { return 'https://staging.example/wp-json/' . $path; }
function absint( $value ): int { return abs( (int) $value ); }
function sanitize_text_field( $value ): string { return trim( strip_tags( $value ) ); }
function sanitize_hex_color( $value ): string { return preg_match( '/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/i', $value ) ? $value : ''; }
function wp_strip_all_tags( $value, $breaks = false ): string { return strip_tags( $value ); }
function wp_slash( $value ) { return is_array( $value ) ? array_map( 'wp_slash', $value ) : ( is_string( $value ) ? addslashes( $value ) : $value ); }
function wp_unslash( $value ) { return is_array( $value ) ? array_map( 'wp_unslash', $value ) : ( is_string( $value ) ? stripslashes( $value ) : $value ); }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ): void { $GLOBALS['filters'][$hook][$priority][] = $callback; }
function remove_filter( $hook, $callback, $priority = 10 ): void { foreach ( $GLOBALS['filters'][$hook][$priority] ?? array() as $i => $existing ) { if ( $existing === $callback ) { unset( $GLOBALS['filters'][$hook][$priority][$i] ); } } }
function apply_filters( $hook, $value, ...$args ) { $priorities = $GLOBALS['filters'][$hook] ?? array(); ksort( $priorities ); foreach ( $priorities as $callbacks ) { foreach ( $callbacks as $callback ) { $value = $callback( $value, ...$args ); } } return $value; }
function wp_kses_post( $value ): string { return preg_replace( '/\s+on\w+="[^"]*"|<script\b[^>]*>.*?<\/script>/is', '', $value ); }
function wp_timezone(): DateTimeZone { return new DateTimeZone( 'Europe/Amsterdam' ); }
function current_datetime(): DateTimeImmutable { return new DateTimeImmutable( '2026-10-21T12:00:00', wp_timezone() ); }
function get_post_type( $id ): string { return in_array( $id, array( 100, 101 ), true ) ? 'product' : ( in_array( $id, array( 200, 201, 202 ), true ) ? 'attachment' : 'page' ); }
function wp_attachment_is_image( $id ): bool { return 200 === $id; }
function get_post_mime_type( $id ): string { return 201 === $id ? 'application/pdf' : 'text/plain'; }
function wc_get_product( $id ) { return 100 === $id ? new WC_Product() : false; }
function taxonomy_exists( $taxonomy ): bool { return true; }
function get_term( $id, $taxonomy ) { $term = $GLOBALS['terms'][$id] ?? null; return $term && $taxonomy === $term->taxonomy ? $term : null; }
function term_exists( $id, $taxonomy ): bool { return (bool) get_term( $id, $taxonomy ); }
function wp_insert_term( $name, $taxonomy ) {
	foreach ( $GLOBALS['terms'] as $term ) { if ( $term->name === $name && $term->taxonomy === $taxonomy ) { return new WP_Error( 'term_exists', 'Duplicate label' ); } }
	$id = count( $GLOBALS['terms'] ) + 1; $GLOBALS['terms'][$id] = new WP_Term( $id, $name, $taxonomy ); ++$GLOBALS['writes']; return array( 'term_id' => $id );
}
function wp_update_term( $id, $taxonomy, $data ) { $GLOBALS['terms'][$id]->name = $data['name']; ++$GLOBALS['writes']; return array( 'term_id' => $id ); }
function wp_delete_term( $id, $taxonomy ): void { unset( $GLOBALS['terms'][$id], $GLOBALS['term_meta'][$id] ); ++$GLOBALS['writes']; }
function update_term_meta( $id, $key, $value ): bool { if ( $key === $GLOBALS['fail_key'] ) { return false; } $GLOBALS['term_meta'][$id][$key] = wp_unslash( $value ); ++$GLOBALS['writes']; return true; }
function delete_term_meta( $id, $key ): void { unset( $GLOBALS['term_meta'][$id][$key] ); ++$GLOBALS['writes']; }
function get_term_meta( $id, $key, $single = true ) { return $GLOBALS['term_meta'][$id][$key] ?? ''; }
function wp_set_object_terms( $id, $ids, $taxonomy, $append ) { $GLOBALS['relationships'][$id] = $ids; ++$GLOBALS['writes']; return $ids; }
function wp_get_object_terms( $id, $taxonomy, $args ): array { return $GLOBALS['relationships'][$id] ?? array(); }
function get_terms( $args ): array { $terms = array_values( array_filter( $GLOBALS['terms'], static function ( $term ) use ( $args ) { return $term->taxonomy === $args['taxonomy'] && ( ! $args['search'] || false !== strpos( $term->name, $args['search'] ) ); } ) ); return array_slice( $terms, $args['offset'] ?? 0, $args['number'] ?? 100 ); }
function wp_count_terms( $args ): int { return count( get_terms( $args ) ); }
function get_the_terms( $id, $taxonomy ): array { return array_values( array_filter( $GLOBALS['terms'], static function ( $term ) use ( $id ) { return in_array( $term->term_id, $GLOBALS['relationships'][$id] ?? array(), true ); } ) ); }
function update_termmeta_cache( $ids ): void {}
function wp_list_pluck( $objects, $key ): array { return array_map( static function ( $object ) use ( $key ) { return $object->$key; }, $objects ); }

// Capture actual local definitions, including subfield keys; no duplicate test schema.
function acf_get_field_types(): array { return $GLOBALS['pro'] ? array( 'repeater' => true ) : array(); }
function acf_add_local_field_group( $group ): void {
	foreach ( $group['fields'] as $field ) { $field['_name'] = $field['name']; $GLOBALS['acf_defs'][$field['key']] = $field; }
}
function acf_get_field( $key ) { return $GLOBALS['acf_available'] ? ( $GLOBALS['acf_defs'][$key] ?? false ) : false; }
function get_post_meta( $id, $name, $single = true ) { return $GLOBALS['post_meta'][$id][$name] ?? ''; }
function metadata_exists( $type, $id, $name ): bool { return array_key_exists( $name, $GLOBALS['post_meta'][$id] ?? array() ); }
function mock_acf_read( $field, $id ) {
	$value = get_post_meta( $id, $field['name'] );
	if ( 'repeater' !== $field['type'] ) { return $value; }
	$rows = array();
	for ( $i = 0; $i < (int) $value; ++$i ) {
		$row = array(); foreach ( $field['sub_fields'] as $child ) { $child['name'] = $field['name'] . '_' . $i . '_' . $child['name']; $row[$child['key']] = mock_acf_read( $child, $id ); } $rows[] = $row;
	}
	return $rows;
}
function get_field( $key, $id, $format = true ) { return mock_acf_read( acf_get_field( $key ), $id ); }
function mock_acf_write( $field, $value, $id ): bool {
	$original = get_post_meta( $id, $field['name'] );
	if ( 'repeater' === $field['type'] ) {
		foreach ( $value as $i => $row ) { foreach ( $field['sub_fields'] as $child ) { $child['name'] = $field['name'] . '_' . $i . '_' . $child['name']; mock_acf_write( $child, $row[$child['key']], $id ); } }
		for ( $i = count( $value ); $i < (int) $original; ++$i ) { foreach ( $field['sub_fields'] as $child ) { unset( $GLOBALS['post_meta'][$id][$field['name'] . '_' . $i . '_' . $child['name']], $GLOBALS['post_meta'][$id]['_' . $field['name'] . '_' . $i . '_' . $child['name']] ); } }
		$value = count( $value );
	} else { $value = apply_filters( 'acf/update_value', $value, $id, $field ); }
	if ( null === $value ) {
		foreach ( array( false, true ) as $hidden ) { apply_filters( 'acf/pre_delete_metadata', null, $id, $field['name'], $hidden ); unset( $GLOBALS['post_meta'][$id][( $hidden ? '_' : '' ) . $field['name']] ); }
		return true;
	}
	apply_filters( 'acf/pre_update_metadata', null, $id, $field['name'], $value, false );
	$failed = in_array( $GLOBALS['fail_key'], array( $field['key'], $field['name'] ), true );
	if ( ! $failed ) { $GLOBALS['post_meta'][$id][$field['name']] = wp_unslash( $value ); ++$GLOBALS['writes']; }
	apply_filters( 'acf/pre_update_metadata', null, $id, $field['name'], $field['key'], true );
	$GLOBALS['post_meta'][$id]['_' . $field['name']] = $field['key'];
	$GLOBALS['acf_references'][$id][$field['key']] = true;
	return ! $failed && $original !== wp_unslash( $value );
}
function update_field( $key, $value, $id ): bool { return mock_acf_write( acf_get_field( $key ), $value, $id ); }
// ACF returns false (not an empty array) when there are no validation errors.
function acf_get_validation_errors() { return $GLOBALS['validation_errors'] ? $GLOBALS['validation_errors'] : false; }
function acf_reset_validation_errors(): void { $GLOBALS['validation_errors'] = array(); }
function acf_add_validation_error( $input, $message ): void { $GLOBALS['validation_errors'][] = array( 'input' => $input, 'message' => $message ); }
function acf_validate_value( $value, $field, $input ): bool {
	$GLOBALS['validation_calls'][] = $input;
	if ( 'repeater' === $field['type'] ) {
		foreach ( $value as $i => $row ) { foreach ( $field['sub_fields'] as $child ) { acf_validate_value( $row[$child['key']], $child, $input . '[' . $i . '][' . $child['key'] . ']' ); } }
	}
	if ( isset( $field['maxlength'] ) && strlen( $value ) > $field['maxlength'] ) { acf_add_validation_error( $input, 'Text too long' ); }
	if ( 'url' === $field['type'] && $value && ! filter_var( $value, FILTER_VALIDATE_URL ) ) { acf_add_validation_error( $input, 'Invalid URL' ); }
	if ( 'BLOCKED BY SITE FILTER' === $value ) { acf_add_validation_error( $input, 'Site field filter rejected value' ); }
	return true;
}

require __DIR__ . '/../wc-product-card-elementor/includes/class-product-labels.php';
require __DIR__ . '/../wc-product-card-elementor/includes/class-acf-fields.php';
require __DIR__ . '/../wc-product-card-elementor/includes/class-product-fields.php';
require __DIR__ . '/../wc-product-card-elementor/includes/class-rest-api.php';
WC_Product_Card_Elementor_ACF_Fields::register();
WCPCE_REST_API::register_routes();

function check( bool $condition, string $message ): void { ++$GLOBALS['assertions']; if ( ! $condition ) { throw new RuntimeException( $message ); } }
function req( array $body, int $id = 100 ): WP_REST_Request { return new WP_REST_Request( array( 'id' => $id ), json_encode( $body, JSON_FORCE_OBJECT ) ); }
function json_req( string $json, int $id = 100 ): WP_REST_Request { return new WP_REST_Request( array( 'id' => $id ), $json ); }
function reject_product( string $json, string $message ): void {
	$before = $GLOBALS['writes']; $flushes = $GLOBALS['flushes'];
	$result = WCPCE_REST_API::update_product( json_req( $json ) );
	check( 400 === $result->status, $message );
	check( $before === $GLOBALS['writes'] && $flushes === $GLOBALS['flushes'], $message . ': validation must precede every write/purge' );
}

check( 4 === count( $GLOBALS['routes'] ), 'Route shapes' );
foreach ( array( 'anonymous', 'customer', 'editor', 'manager' ) as $role ) {
	$GLOBALS['role'] = $role;
	foreach ( $GLOBALS['routes'] as $route => $handlers ) {
		if ( isset( $handlers['methods'] ) ) { $handlers = array( $handlers ); }
		foreach ( $handlers as $handler ) { check( false === call_user_func( $handler['permission_callback'], json_req( '{}' ) ), $role . ' must not access ' . $route ); }
	}
}
$GLOBALS['role'] = 'admin';
check( WCPCE_REST_API::can_manage(), 'Admin access' );
check( ! WCPCE_REST_API::can_manage_product( json_req( '{}', 101 ) ), 'Per-product capability' );
check( 11 === count( WCPCE_Product_Fields::definitions() ), 'All owned fields registered' );
check( false === acf_get_validation_errors(), 'Match native ACF no-error sentinel' );
$before = $GLOBALS['writes'];
$schema = WCPCE_REST_API::read_schema();
check( 'Europe/Amsterdam' === $schema->data['site_timezone'], 'Schema timezone' );
check( 8 === $schema->data['fields']['properties']->pdp_usps['maxItems'], 'Schema uses real repeater definition' );
check( false === strpos( json_encode( $schema->data ), 'product_faq' ), 'External field excluded' );
check( $before === $GLOBALS['writes'], 'GET schema never writes' );

$label = WCPCE_REST_API::create_label( json_req( '{"name":"Campagne","active":false,"position":"below-image","priority":0,"visible_from":"2026-11-01T10:00","visible_until":"2026-11-01T10:00","pdp_details":"<p onclick=\"bad()\">Voorwaarden</p><script>bad()</script>"}' ) );
check( 201 === $label->status, 'Label creation' );
$label_id = $label->data['id'];
check( false === $label->data['active'] && 0 === $label->data['priority'], 'False and zero preserved' );
check( '2026-11-01 10:00:59' === get_term_meta( $label_id, 'wcpce_label_visible_until' ), 'Inclusive end minute' );
check( '<p>Voorwaarden</p>' === $label->data['pdp_details'], 'Safe PDP explanation' );
check( 409 === WCPCE_REST_API::create_label( json_req( '{"name":"Campagne"}' ) )->status, 'Duplicate creation conflict' );
$updated = WCPCE_REST_API::update_label( json_req( '{"color":"#123456"}', $label_id ) );
check( false === $updated->data['active'] && 'below-image' === $updated->data['position'], 'PATCH preserves omitted label settings' );
$before = $GLOBALS['writes'];
foreach ( array( '{"active":"false"}', '{"priority":-1}', '{"position":"left"}', '{"color":"pink"}', '{"visible_from":"2026-02-30T12:00"}', '{"visible_from":"2026-03-29T02:30"}', '{"visible_until":"2026-10-01T12:00"}', '{"name":""}', '{"meta":{"secret":"x"}}', '[]' ) as $json ) {
	check( 400 === WCPCE_REST_API::update_label( json_req( $json, $label_id ) )->status, 'Invalid label payload: ' . $json );
}
check( $before === $GLOBALS['writes'], 'Invalid labels never write' );
check( 404 === WCPCE_REST_API::read_label( json_req( '{}', 999 ) )->status, 'Missing label' );
check( 404 === WCPCE_REST_API::read_product( json_req( '{}', 200 ) )->status, 'Attachment is not a product' );
$listing = WCPCE_REST_API::list_labels( new WP_REST_Request( array( 'page' => 1, 'per_page' => 20, 'search' => 'Campagne' ) ) );
check( 1 === count( $listing->data ) && '1' === $listing->headers['X-WP-Total'], 'Listing inactive/unused labels and totals' );

$write = WCPCE_REST_API::update_product( json_req( '{"label_ids":[' . $label_id . ',' . $label_id . '],"fields":{"card_title":"Korte titel","card_hover_image":200,"badge_nieuw":false,"badge_niet_leverbaar":true,"pdp_usps":[{"usp_text":"Eerste"},{"usp_text":"Tweede"}],"pdp_gallery_videos":[{"youtube_url":"https://youtu.be/abcdefghijk","video_thumbnail":200}],"product_manual":201}}' ) );
check( 200 === $write->status, 'Rich product write' );
check( array( $label_id ) === $write->data['label_ids'], 'Inactive label remains assigned, deduplicated' );
check( array( array( 'usp_text' => 'Eerste' ), array( 'usp_text' => 'Tweede' ) ) === $write->data['fields']->pdp_usps, 'Repeater round trip' );
check( '' === $write->data['fields']->pdp_gallery_videos[0]['video_title'], 'Optional child default' );
check( true === $GLOBALS['acf_references'][100]['field_wcpce_pdp_usps'], 'First save uses field key' );
check( 1 === $GLOBALS['saves'] && 1 === $GLOBALS['flushes'], 'Finish real product save and invalidate queries' );
check( false !== strpos( $write->headers['Cache-Control'], 'no-store' ), 'Private cache headers' );
check( 'Korte titel' === WCPCE_REST_API::update_product( json_req( '{"fields":{"pdp_usps":[{"usp_text":"Nog één"}]}}' ) )->data['fields']->card_title, 'Omitted field preserved while repeater shrinks' );
$cleared = WCPCE_REST_API::update_product( json_req( '{"label_ids":[],"fields":{"card_hover_image":null,"product_manual":null,"card_title":"","pdp_usps":[],"pdp_gallery_videos":[]}}' ) );
check( array() === $cleared->data['fields']->pdp_usps && array() === $cleared->data['fields']->pdp_gallery_videos && array() === $cleared->data['label_ids'], 'Lists clear explicitly' );
check( null === $cleared->data['fields']->card_hover_image && null === $cleared->data['fields']->product_manual, 'Media clear explicitly' );
check( 200 === WCPCE_REST_API::update_product( json_req( '{"fields":{"card_title":""}}' ) )->status, 'Unchanged update succeeds' );
$escaped_text = "Chef's \\\\ test";
$escaped_write = WCPCE_REST_API::update_product( json_req( json_encode( array( 'fields' => array( 'card_title' => $escaped_text ) ) ) ) );
check( $escaped_text === $escaped_write->data['fields']->card_title, 'Quotes and backslashes survive WordPress metadata unslashing' );
$normalize = static function ( $value, $id, $field ) { return is_string( $value ) ? trim( $value ) : $value; };
add_filter( 'acf/update_value', $normalize );
check( 'Trimmed' === WCPCE_REST_API::update_product( json_req( '{"fields":{"card_title":"  Trimmed  "}}' ) )->data['fields']->card_title, 'ACF normalization is a successful write' );
remove_filter( 'acf/update_value', $normalize );
$delete_filter = static function ( $value, $id, $field ) { return 'Delete me' === $value ? null : $value; };
add_filter( 'acf/update_value', $delete_filter );
check( '' === WCPCE_REST_API::update_product( json_req( '{"fields":{"card_title":"Delete me"}}' ) )->data['fields']->card_title, 'Native ACF filter can delete a value' );
remove_filter( 'acf/update_value', $delete_filter );
WCPCE_REST_API::update_product( json_req( '{"fields":{"pdp_usps":[{"usp_text":"Before"}]}}' ) );
$GLOBALS['fail_key'] = 'pdp_usps_0_usp_text';
check( 500 === WCPCE_REST_API::update_product( json_req( '{"fields":{"pdp_usps":[{"usp_text":"After"}]}}' ) )->status, 'Detect a failed repeater child even when the row count is unchanged' );
$GLOBALS['fail_key'] = '';
WCPCE_REST_API::update_product( json_req( '{"fields":{"pdp_usps":[]}}' ) );
check( ! isset( $GLOBALS['post_meta'][100]['pdp_usps_0_usp_text'] ), 'Clearing repeater removes old child metadata through ACF' );

foreach ( array( '{"fields":{"card_title":false}}', '{"fields":{"card_hover_image":201}}', '{"fields":{"product_manual":200}}', '{"fields":{"card_hover_image":"200"}}', '{"fields":{"product_manual":202}}', '{"fields":{"badge_nieuw":"false"}}', '{"fields":{"product_faq":[]}}', '{"fields":{"pdp_usps":[{"wrong":"x"}]}}', '{"fields":{"pdp_usps":[[]]}}', '{"fields":{"pdp_gallery_videos":[{}]}}', '{"fields":[]}', '{"label_ids":[999]}', '{"label_ids":["1"]}', '{"unknown":true}', '[]', '{broken' ) as $json ) { reject_product( $json, 'Reject ' . $json ); }
reject_product( '{"fields":{"card_title":"' . str_repeat( 'x', 81 ) . '"}}', 'ACF maximum text length' );
reject_product( '{"fields":{"pdp_usps":' . json_encode( array_fill( 0, 9, array( 'usp_text' => 'x' ) ) ) . '}}', 'Repeater maximum rows' );
reject_product( '{"fields":{"pdp_usps":[{"usp_text":"' . str_repeat( 'x', 141 ) . '"}]}}', 'Native nested ACF validation' );
reject_product( '{"fields":{"pdp_usps":[{"usp_text":"BLOCKED BY SITE FILTER"}]}}', 'Custom nested ACF validation' );
reject_product( '{"label_ids":[],"fields":{"card_title":"BLOCKED BY SITE FILTER"}}', 'Field failure prevents relationship writes too' );
$GLOBALS['validation_errors'] = array( array( 'input' => 'previous', 'message' => 'outer request' ) );
reject_product( '{"fields":{"card_title":"BLOCKED BY SITE FILTER"}}', 'Preserve prior validation state' );
check( 'previous' === $GLOBALS['validation_errors'][0]['input'] && 1 === count( $GLOBALS['validation_errors'] ), 'ACF error state restored' );
$GLOBALS['validation_errors'] = array();

$GLOBALS['acf_available'] = false;
$without_acf = WCPCE_REST_API::read_product( json_req( '{}' ) );
check( array() === get_object_vars( $without_acf->data['fields'] ) && array() === $without_acf->data['available_fields'], 'Unavailable is not an empty repeater' );
check( 200 === WCPCE_REST_API::update_product( json_req( '{"label_ids":[' . $label_id . ']}' ) )->status, 'Labels independent of ACF' );
reject_product( '{"fields":{"card_title":"x"}}', 'No raw meta fallback without ACF' );
$GLOBALS['acf_available'] = true;
$GLOBALS['pro'] = false; $GLOBALS['acf_defs'] = array(); WC_Product_Card_Elementor_ACF_Fields::register();
check( 9 === count( WCPCE_Product_Fields::definitions() ), 'ACF Free field support' );
reject_product( '{"fields":{"pdp_usps":[]}}', 'ACF Free rejects unavailable repeater' );
$GLOBALS['pro'] = true; $GLOBALS['acf_defs'] = array(); WC_Product_Card_Elementor_ACF_Fields::register();

$GLOBALS['fail_key'] = 'field_wcpce_card_title';
$failed = WCPCE_REST_API::update_product( json_req( '{"fields":{"card_title":"Cannot store"}}' ) );
check( 500 === $failed->status, 'No false success after ACF write failure' );
$GLOBALS['fail_key'] = 'wcpce_label_color';
check( 500 === WCPCE_REST_API::update_label( json_req( '{"color":"#ffffff"}', $label_id ) )->status, 'No false success after label meta failure' );
$GLOBALS['fail_key'] = '';

// Management reads must not use the frontend's filtered request cache.
$GLOBALS['relationships'][100] = array( $label_id );
check( array() === WCPCE_Product_Labels::get_product_labels( 100 ), 'Inactive/future label hidden on frontend' );
check( array( $label_id ) === WCPCE_REST_API::read_product( json_req( '{}' ) )->data['label_ids'], 'Management retains hidden assignment' );
WCPCE_Product_Labels::save_settings( $label_id, array( 'active' => true, 'visible_from' => '', 'visible_until' => '' ) );
check( 1 === count( WCPCE_Product_Labels::get_product_labels( 100 ) ), 'Definition write invalidates request cache' );
WCPCE_Product_Labels::assign_labels( 100, array() );
check( array() === WCPCE_Product_Labels::get_product_labels( 100 ), 'Assignment write invalidates request cache' );

echo 'REST contract regressions passed (' . $GLOBALS['assertions'] . " assertions).\n";
