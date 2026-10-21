<?php
/**
 * Private WordPress REST management API. Authentication is owned by WordPress.
 *
 * @package WC_Product_Card_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCPCE_REST_API {

	private const NS         = 'wcpce/v1';
	private const LABEL_KEYS = array( 'name', 'color', 'position', 'priority', 'active', 'visible_from', 'visible_until', 'pdp_details' );

	/** Registers routes only during REST initialization. */
	public static function register_routes(): void {
		register_rest_route(
			self::NS,
			'/labels',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( self::class, 'list_labels' ),
					'permission_callback' => array( self::class, 'can_manage' ),
					'args'                => array(
						'page'     => array(
							'type'    => 'integer',
							'minimum' => 1,
							'default' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'minimum' => 1,
							'maximum' => 100,
							'default' => 20,
						),
						'search'   => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( self::class, 'create_label' ),
					'permission_callback' => array( self::class, 'can_manage' ),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/labels/(?P<id>[1-9][0-9]*)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( self::class, 'read_label' ),
					'permission_callback' => array( self::class, 'can_manage' ),
				),
				array(
					'methods'             => 'PATCH',
					'callback'            => array( self::class, 'update_label' ),
					'permission_callback' => array( self::class, 'can_manage' ),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/products/(?P<id>[1-9][0-9]*)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( self::class, 'read_product' ),
					'permission_callback' => array( self::class, 'can_manage_product' ),
				),
				array(
					'methods'             => 'PATCH',
					'callback'            => array( self::class, 'update_product' ),
					'permission_callback' => array( self::class, 'can_manage_product' ),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/schema',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'read_schema' ),
				'permission_callback' => array( self::class, 'can_manage' ),
			)
		);
	}

	/** Both reads and writes require administrative and WooCommerce privileges. */
	public static function can_manage(): bool {
		return current_user_can( 'manage_options' ) && current_user_can( 'manage_woocommerce' );
	}

	/** Administrators must additionally be allowed to edit this individual product. */
	public static function can_manage_product( \WP_REST_Request $request ): bool {
		return self::can_manage() && current_user_can( 'edit_post', (int) $request->get_url_params()['id'] );
	}

	/** Private responses must not enter shared page caches. */
	private static function response( $data, int $status = 200 ): \WP_REST_Response {
		$response = is_wp_error( $data ) ? rest_convert_error_to_response( $data ) : new \WP_REST_Response( $data, $status );
		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		$response->header( 'Vary', 'Authorization, Cookie' );
		return $response;
	}

	private static function error( string $code, string $message, int $status, array $extra = array() ): \WP_REST_Response {
		return self::response( new \WP_Error( $code, $message, array_merge( $extra, array( 'status' => $status ) ) ) );
	}

	/** Only JSON objects are accepted; no query or form body can cause a write. */
	private static function body( \WP_REST_Request $request, array $allowed, \WP_Error $errors ): \stdClass {
		$body = json_decode( $request->get_body() );
		if ( ! $body instanceof \stdClass ) {
			$errors->add( 'body', __( 'Verwacht een JSON-object.', 'woo-card-chef' ) );
			return new \stdClass();
		}
		foreach ( get_object_vars( $body ) as $name => $value ) {
			if ( ! in_array( $name, $allowed, true ) ) {
				$errors->add( $name, __( 'Onbekend of niet-schrijfbaar veld.', 'woo-card-chef' ) );
			}
		}
		return $body;
	}

	private static function invalid( \WP_Error $errors ): \WP_REST_Response {
		$params = array();
		foreach ( $errors->get_error_codes() as $code ) {
			$params[ $code ] = $errors->get_error_messages( $code );
		}
		return self::error( 'wcpce_invalid_fields', __( 'Controleer de opgegeven velden.', 'woo-card-chef' ), 400, array( 'params' => $params ) );
	}

	public static function list_labels( \WP_REST_Request $request ): \WP_REST_Response {
		$args  = array(
			'taxonomy'   => WCPCE_Product_Labels::TAXONOMY,
			'hide_empty' => false,
			'search'     => $request['search'],
			'orderby'    => 'term_id',
			'order'      => 'ASC',
		);
		$total = wp_count_terms( $args );
		if ( is_wp_error( $total ) ) {
			return self::response( $total );
		}
		$args['number'] = (int) $request['per_page'];
		$args['offset'] = ( (int) $request['page'] - 1 ) * $args['number'];
		$terms          = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			return self::response( $terms );
		}
		$response = self::response( array_map( array( WCPCE_Product_Labels::class, 'get_definition' ), $terms ) );
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) ceil( (int) $total / $args['number'] ) );
		return $response;
	}

	public static function read_label( \WP_REST_Request $request ): \WP_REST_Response {
		$term = get_term( (int) $request->get_url_params()['id'], WCPCE_Product_Labels::TAXONOMY );
		return $term instanceof \WP_Term ? self::response( WCPCE_Product_Labels::get_definition( $term ) ) : self::error( 'wcpce_label_not_found', __( 'Productlabel niet gevonden.', 'woo-card-chef' ), 404 );
	}

	/** Validates transport types and planning without silently replacing invalid input. */
	private static function prepare_label( \stdClass $body, array $existing, \WP_Error $errors ): array {
		$data = get_object_vars( $body );
		foreach ( $data as $key => $value ) {
			if ( ! in_array( $key, self::LABEL_KEYS, true ) ) {
				continue;
			}
			$valid = is_string( $value );
			if ( 'priority' === $key ) {
				$valid = is_int( $value ) && $value >= 0 && $value <= 999;
			} elseif ( 'active' === $key ) {
				$valid = is_bool( $value );
			} elseif ( 'color' === $key ) {
					$valid = is_string( $value ) && (bool) sanitize_hex_color( $value );
			} elseif ( 'position' === $key ) {
					$valid = is_string( $value ) && array_key_exists( $value, WCPCE_Product_Labels::get_card_positions() );
			} elseif ( 'name' === $key ) {
							$valid = is_string( $value ) && '' !== trim( sanitize_text_field( $value ) ) && ( function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value ) ) <= 200;
				if ( $valid ) {
					$data[ $key ] = sanitize_text_field( $value );
				}
			} elseif ( in_array( $key, array( 'visible_from', 'visible_until' ), true ) ) {
				$valid = is_string( $value ) && ( '' === $value || self::valid_date( $value ) );
			}
			if ( ! $valid ) {
				$errors->add( $key, __( 'Ongeldige waarde voor dit labelveld.', 'woo-card-chef' ) );
			}
		}
		$merged = array_merge( $existing, $data );
		if ( ! isset( $merged['name'] ) ) {
			$errors->add( 'name', __( 'Een labelnaam is verplicht.', 'woo-card-chef' ) );
		}
		if ( ! $errors->has_errors() && ( isset( $data['visible_from'] ) || isset( $data['visible_until'] ) ) && ! empty( $merged['visible_from'] ) && ! empty( $merged['visible_until'] ) && $merged['visible_until'] < $merged['visible_from'] ) {
			$errors->add( 'visible_until', __( 'Het einde mag niet vóór het begin liggen.', 'woo-card-chef' ) );
		}
		return $data;
	}

	private static function valid_date( string $value ): bool {
		$date   = \DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $value, wp_timezone() );
		$errors = \DateTimeImmutable::getLastErrors();
		return false !== $date && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) && $date->format( 'Y-m-d\TH:i' ) === $value;
	}

	public static function create_label( \WP_REST_Request $request ): \WP_REST_Response {
		$errors = new \WP_Error();
		$body   = self::body( $request, self::LABEL_KEYS, $errors );
		$data   = self::prepare_label( $body, array(), $errors );
		if ( $errors->has_errors() ) {
			return self::invalid( $errors );
		}
		$created = wp_insert_term( $data['name'], WCPCE_Product_Labels::TAXONOMY );
		if ( is_wp_error( $created ) ) {
			return self::error( 'wcpce_label_conflict', $created->get_error_message(), 'term_exists' === $created->get_error_code() ? 409 : 500 );
		}
		$id       = (int) $created['term_id'];
		$settings = array_merge(
			array(
				'color'         => '#B4211C',
				'position'      => 'top-left',
				'priority'      => 10,
				'active'        => true,
				'visible_from'  => '',
				'visible_until' => '',
				'pdp_details'   => '',
			),
			$data
		);
		if ( ! WCPCE_Product_Labels::save_settings( $id, $settings ) ) {
			wp_delete_term( $id, WCPCE_Product_Labels::TAXONOMY );
			return self::error( 'wcpce_storage_error', __( 'Labelinstellingen konden niet volledig worden opgeslagen. Lees de actuele toestand opnieuw.', 'woo-card-chef' ), 500, array( 'label_id' => $id ) );
		}
		$response = self::response( WCPCE_Product_Labels::get_definition( get_term( $id, WCPCE_Product_Labels::TAXONOMY ) ), 201 );
		$response->header( 'Location', rest_url( self::NS . '/labels/' . $id ) );
		return $response;
	}

	public static function update_label( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = (int) $request->get_url_params()['id'];
		$term = get_term( $id, WCPCE_Product_Labels::TAXONOMY );
		if ( ! $term instanceof \WP_Term ) {
			return self::error( 'wcpce_label_not_found', __( 'Productlabel niet gevonden.', 'woo-card-chef' ), 404 );
		}
		$errors = new \WP_Error();
		$body   = self::body( $request, self::LABEL_KEYS, $errors );
		$data   = self::prepare_label( $body, WCPCE_Product_Labels::get_definition( $term ), $errors );
		if ( $errors->has_errors() ) {
			return self::invalid( $errors );
		}
		if ( isset( $data['name'] ) ) {
			$updated = wp_update_term( $id, WCPCE_Product_Labels::TAXONOMY, array( 'name' => $data['name'] ) );
			if ( is_wp_error( $updated ) ) {
				return self::error( 'wcpce_label_conflict', $updated->get_error_message(), 'term_exists' === $updated->get_error_code() ? 409 : 500 );
			}
		}
		if ( ! WCPCE_Product_Labels::save_settings( $id, $data ) ) {
			return self::error( 'wcpce_storage_error', __( 'Niet alle instellingen zijn opgeslagen. Lees het label opnieuw.', 'woo-card-chef' ), 500 );
		}
		return self::response( WCPCE_Product_Labels::get_definition( get_term( $id, WCPCE_Product_Labels::TAXONOMY ) ) );
	}

	public static function read_product( \WP_REST_Request $request ): \WP_REST_Response {
		$id = (int) $request->get_url_params()['id'];
		if ( 'product' !== get_post_type( $id ) ) {
			return self::error( 'wcpce_product_not_found', __( 'Product niet gevonden.', 'woo-card-chef' ), 404 );
		}
		$ids = wp_get_object_terms( $id, WCPCE_Product_Labels::TAXONOMY, array( 'fields' => 'ids' ) );
		if ( is_wp_error( $ids ) ) {
			return self::response( $ids );
		}
		return self::response(
			array(
				'id'               => $id,
				'label_ids'        => array_map( 'intval', $ids ),
				'fields'           => (object) WCPCE_Product_Fields::read( $id ),
				'available_fields' => array_keys( WCPCE_Product_Fields::definitions() ),
			)
		);
	}

	public static function update_product( \WP_REST_Request $request ): \WP_REST_Response {
		$id = (int) $request->get_url_params()['id'];
		if ( 'product' !== get_post_type( $id ) || ! wc_get_product( $id ) ) {
			return self::error( 'wcpce_product_not_found', __( 'Product niet gevonden.', 'woo-card-chef' ), 404 );
		}
		$errors   = new \WP_Error();
		$body     = self::body( $request, array( 'fields', 'label_ids' ), $errors );
		$prepared = array();
		if ( property_exists( $body, 'fields' ) ) {
			if ( ! $body->fields instanceof \stdClass ) {
				$errors->add( 'fields', __( 'Verwacht een object met pluginvelden.', 'woo-card-chef' ) );
			} else {
				$prepared = WCPCE_Product_Fields::prepare( $body->fields, $id, $errors );
			}
		}
		if ( property_exists( $body, 'label_ids' ) ) {
			if ( ! is_array( $body->label_ids ) ) {
				$errors->add( 'label_ids', __( 'Verwacht een lijst met bestaande label-ID’s.', 'woo-card-chef' ) );
			} else {
				foreach ( $body->label_ids as $index => $label_id ) {
					if ( ! is_int( $label_id ) || $label_id <= 0 || ! term_exists( $label_id, WCPCE_Product_Labels::TAXONOMY ) ) {
						$errors->add( 'label_ids.' . $index, __( 'Geen bestaand productlabel-ID.', 'woo-card-chef' ) );
					}
				}
			}
		}
		if ( $errors->has_errors() ) {
			return self::invalid( $errors );
		}
		$changed = (bool) $prepared || property_exists( $body, 'label_ids' );
		if ( ! $changed ) {
			return self::read_product( $request );
		}
		$success = false;
		try {
			$success = WCPCE_Product_Fields::save( $id, $prepared );
			if ( $success && property_exists( $body, 'label_ids' ) ) {
				$success = WCPCE_Product_Labels::assign_labels( $id, $body->label_ids );
			}
			if ( $success ) {
				// A real modified-date change forces the normal WP/WC save lifecycle,
				// even if only ACF metadata or taxonomy relationships were edited.
				$product = wc_get_product( $id );
				$product->read_meta_data( true );
				$product->set_date_modified( time() );
				$success = (bool) $product->save();
			}
		} catch ( \Throwable $exception ) {
			$success = false;
		} finally {
			// Also invalidate after a partial storage failure; never cache stale data.
			WCPCE_Product_Labels::clear_cache( $id );
			WC_Product_Card_Elementor_Plugin::instance()->flush_query_cache();
		}
		if ( ! $success ) {
			return self::error( 'wcpce_storage_error', __( 'De wijziging is niet volledig opgeslagen. Lees het product opnieuw voordat je opnieuw schrijft.', 'woo-card-chef' ), 500, array( 'product_id' => $id ) );
		}
		return self::read_product( $request );
	}

	public static function read_schema(): \WP_REST_Response {
		return self::response(
			array(
				'namespace'     => self::NS,
				'site_timezone' => wp_timezone()->getName(),
				'label'         => array(
					'name'          => array(
						'type'      => 'string',
						'maxLength' => 200,
					),
					'color'         => array(
						'type'   => 'string',
						'format' => 'hex-color',
					),
					'position'      => array(
						'type' => 'string',
						'enum' => array_keys( WCPCE_Product_Labels::get_card_positions() ),
					),
					'priority'      => array(
						'type'    => 'integer',
						'minimum' => 0,
						'maximum' => 999,
					),
					'active'        => array( 'type' => 'boolean' ),
					'visible_from'  => array(
						'type'   => 'string',
						'format' => 'Y-m-d\TH:i or empty',
					),
					'visible_until' => array(
						'type'             => 'string',
						'format'           => 'Y-m-d\TH:i or empty',
						'inclusive_minute' => true,
					),
					'pdp_details'   => array(
						'type'   => 'string',
						'format' => 'safe-html',
					),
				),
				'fields'        => WCPCE_Product_Fields::schema(),
				'updates'       => array(
					'omitted'      => 'unchanged',
					'arrays'       => 'replace',
					'clear_text'   => '',
					'clear_media'  => null,
					'clear_arrays' => array(),
				),
			)
		);
	}
}
