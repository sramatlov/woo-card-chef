<?php
/** Standalone card-label regression checks; --preview emits the rendered fixtures. */
define( 'ABSPATH', __DIR__ . '/' );

function __( $text, $domain = '' ): string { return $text; }
function esc_html( $text ): string { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ): string { return esc_html( $text ); }
function esc_url( $text ): string { return esc_attr( $text ); }
function esc_html__( $text, $domain = '' ): string { return esc_html( $text ); }
function esc_html_e( $text, $domain = '' ): void { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ): void { echo esc_attr( $text ); }
function absint( $value ): int { return abs( (int) $value ); }
function sanitize_key( $text ): string { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $text ) ); }
function sanitize_hex_color( $text ): string { return preg_match( '/^#[a-f0-9]{6}$/i', $text ) ? $text : ''; }
function wp_kses_post( $text ): string { return $text; }
function wp_kses( $text, $allowed ): string { return $text; }
function wp_strip_all_tags( $text ): string { return strip_tags( $text ); }
function get_permalink( $id ): string { return '#product-' . $id; }
function wc_price( $price ): string { return '€ ' . number_format( $price, 2, ',', '.' ); }
function wc_get_image_size( $size ): array { return array( 'width' => 300, 'height' => 300 ); }
function wc_placeholder_img_src( $size ): string { return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300"%3E%3Crect x="70" y="70" width="160" height="140" rx="16" fill="%23dedede"/%3E%3Cpath d="M80 110h140M80 150h140" stroke="%23999" stroke-width="8"/%3E%3C/svg%3E'; }
function get_post_meta( $id ): array { return $GLOBALS['label_fixture_acf'] ?? array(); }
function taxonomy_exists( $name ): bool { return true; }
function get_the_terms( $id, $name ): array { return $GLOBALS['label_fixture_terms'] ?? array(); }
function is_wp_error( $value ): bool { return false; }
function update_termmeta_cache( $ids ): void {}
function wp_list_pluck( $objects, $key ): array { return array_map( static function ( $object ) use ( $key ) { return $object->$key; }, $objects ); }
function get_term_meta( $id, $key, $single = true ) { return $GLOBALS['label_fixture_meta'][ $id ][ $key ] ?? ''; }
function current_datetime(): DateTimeImmutable { return new DateTimeImmutable( '2026-10-06' ); }

class WP_Term {
	public $term_id;
	public $name;
	public function __construct( int $id, string $name ) { $this->term_id = $id; $this->name = $name; }
}
class WC_Product {
	private $id;
	public function __construct( int $id ) { $this->id = $id; }
	public function get_id(): int { return $this->id; }
	public function get_name(): string { return 'Contact Grill PURE – PFAS-vrij'; }
	public function get_image_id(): int { return 0; }
	public function is_in_stock(): bool { return empty( $GLOBALS['label_fixture_oos'] ); }
}

require __DIR__ . '/../wc-product-card-elementor/includes/class-product-labels.php';
foreach ( array( 'image', 'acf', 'stock', 'badge' ) as $helper ) {
	require __DIR__ . '/../wc-product-card-elementor/includes/Helpers/class-' . $helper . '-helper.php';
}

function render_label_fixture( array $labels, array $overrides = array(), array $acf = array(), bool $oos = false ): string {
	static $id = 0;
	$product = new WC_Product( ++$id );
	$GLOBALS['label_fixture_acf'] = $acf;
	$GLOBALS['label_fixture_oos'] = $oos;
	$GLOBALS['label_fixture_terms'] = array();
	foreach ( $labels as $index => $label ) {
		$term_id = $id * 100 + $index;
		$GLOBALS['label_fixture_terms'][] = new WP_Term( $term_id, $label[1] );
		$GLOBALS['label_fixture_meta'][ $term_id ] = array( 'wcpce_label_position' => $label[0], 'wcpce_label_color' => '#000000', 'wcpce_label_priority' => $index );
	}
	$settings = array_merge( array( 'custom_label_limit' => 10, 'show_usps' => 'no', 'show_hover_swap' => 'no', 'action_type' => 'view', 'action_label_view' => 'Bekijk deal' ), $overrides );
	$card = array( 'regular_price' => 69.99, 'sale_price' => 55, 'display_price' => 55, 'is_on_sale' => true, 'show_badge' => true, 'badge_text' => '-21%', 'is_variable' => false, 'price_html' => '', 'savings_amount' => 14.99 );
	$widget_id = 'fixture';
	ob_start();
	include __DIR__ . '/../wc-product-card-elementor/templates/card.php';
	return ob_get_clean();
}

function label_check( bool $ok, string $message ): void {
	if ( ! $ok ) { throw new RuntimeException( $message ); }
}

$fixtures = array();
foreach ( WCPCE_Product_Labels::get_card_positions() as $position => $title ) {
	$html = render_label_fixture( array( array( $position, 'Black Friday deal' ) ) );
	label_check( false !== strpos( $html, 'wc-card__labels--' . $position ), 'Position lost: ' . $position );
	if ( 'below-image' === $position ) {
		label_check( strpos( $html, 'wc-card__body' ) < strpos( $html, 'Black Friday deal' ) && strpos( $html, 'Black Friday deal' ) < strpos( $html, '<h3' ), 'Below-image label must precede title in body' );
	}
	$fixtures[ $title ] = $html;
}
$fixtures['Beide hoeken + PFAS + voorraad'] = render_label_fixture( array( array( 'top-left', 'Nieuw assortiment' ), array( 'top-right', 'Black Friday deal' ), array( 'bottom-left', 'Duurzame keuze' ), array( 'bottom-right', 'Online exclusief' ) ), array(), array( 'badge_pfas_vrij' => array( '1' ) ), true );
$fixtures['Lang label + korting rechts'] = render_label_fixture( array( array( 'top-left', str_repeat( 'LangLabel', 8 ) ), array( 'top-right', 'Black Friday deal' ) ), array( 'badge_position' => 'top-right' ) );
$fixtures['Meerdere labels in dezelfde hoek'] = render_label_fixture( array( array( 'top-right', 'Black Friday deal' ), array( 'top-right', 'Online exclusief' ), array( 'top-right', 'Tijdelijke actie' ) ) );
$fixtures['Afbeelding 16:9, maximaal 120px'] = str_replace( 'class="wc-card__media"', 'class="wc-card__media" style="--wcpce-image-ratio:16/9;--wcpce-image-max-height:120px"', render_label_fixture( array() ) );
$fixtures['Grotere typografie en padding'] = str_replace( 'class="wc-card ', 'class="wc-card fixture-custom-style ', render_label_fixture( array( array( 'top-left', 'Black Friday deal' ), array( 'top-right', 'Online exclusief' ) ) ) );
$fallback = render_label_fixture( array( array( 'invalid', 'Fallback' ) ) );
label_check( false !== strpos( $fallback, 'wc-card__labels--top-left' ), 'Unknown position must retain legacy fallback' );
$limited = render_label_fixture( array( array( 'top-left', 'First' ), array( 'below-image', 'Second' ) ), array( 'custom_label_limit' => 1 ) );
label_check( false !== strpos( $limited, 'First' ) && false === strpos( $limited, 'Second' ), 'Label limit must apply across all positions' );
$hidden = render_label_fixture( array( array( 'below-image', 'Hidden campaign' ) ), array(), array( 'badge_niet_leverbaar' => array( '1' ) ) );
label_check( false === strpos( $hidden, 'Hidden campaign' ) && false === strpos( $hidden, '21% korting' ), 'Discontinued must suppress commercial badges' );
$hidden = render_label_fixture( array( array( 'top-left', 'Hidden campaign' ) ), array( 'show_custom_labels' => 'no' ) );
label_check( false === strpos( $hidden, 'Hidden campaign' ), 'Widget toggle must hide custom labels' );
$escaped = render_label_fixture( array( array( 'below-image', '<script>alert(1)</script>' ) ) );
label_check( false !== strpos( $escaped, '&lt;script&gt;' ) && false === strpos( $escaped, '<script>' ), 'Label text must be escaped' );

if ( in_array( '--preview', $argv, true ) ) {
	echo '<!doctype html><html lang="nl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Productlabels – layoutcontrole</title><style>';
	echo file_get_contents( __DIR__ . '/../wc-product-card-elementor/assets/css/product-card.css' );
	echo '*{box-sizing:border-box}body{font-family:Arial,sans-serif;margin:20px;background:#f4f4f4}main{max-width:1100px;margin:auto}section{margin-bottom:28px}h2{font-size:18px}.wc-card-grid{grid-template-columns:repeat(4,minmax(0,1fr))}@media(max-width:1024px){.wc-card-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}.wc-card__body{padding:12px}.wc-card__title{font-weight:400}.wc-card__button{background:#b4211c;color:white}.fixture-custom-style .wc-card__custom-label{font-size:18px;padding:12px 16px}.fixture-custom-style .wc-card__badge{font-size:20px;padding:10px}</style><main><h1>Productlabels</h1><p>Werkende kaarttemplate met vijf posities en automatische rijverdeling.</p>';
	foreach ( $fixtures as $title => $html ) {
		echo '<section><h2>' . esc_html( $title ) . '</h2><div class="wc-card-grid">' . $html . str_replace( 'wcpce-title-fixture-', 'wcpce-title-fixture-copy-', $html ) . '</div></section>';
	}
	echo '</main></html>';
} else {
	echo "Product label regression checks passed.\n";
}
