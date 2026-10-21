<?php
/**
 * Typed management adapter for the plugin-owned ACF fields.
 *
 * @package WC_Product_Card_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WCPCE_Product_Fields {

	/** Explicit ownership boundary; external fields such as product_faq are excluded. */
	private const FIELDS = array(
		'card_title'           => 'text',
		'card_hover_image'     => 'image',
		'usp_1'                => 'text',
		'usp_2'                => 'text',
		'usp_3'                => 'text',
		'badge_nieuw'          => 'true_false',
		'badge_pfas_vrij'      => 'true_false',
		'badge_niet_leverbaar' => 'true_false',
		'product_manual'       => 'file',
		'pdp_usps'             => 'repeater',
		'pdp_gallery_videos'   => 'repeater',
	);

	/** Returns available registered definitions, never fabricating ACF support. */
	public static function definitions(): array {
		$fields = array();
		if ( ! function_exists( 'acf_get_field' ) || ! function_exists( 'get_field' ) || ! function_exists( 'update_field' ) || ! function_exists( 'acf_validate_value' ) ) {
			return $fields;
		}
		foreach ( self::FIELDS as $name => $type ) {
			$field = acf_get_field( 'field_wcpce_' . $name );
			if ( $field && $type === $field['type'] ) {
				$fields[ $name ] = $field;
			}
		}
		return $fields;
	}

	/** Returns a schema from the same definitions used in the product editor. */
	public static function schema(): array {
		$properties = array();
		$fields     = self::definitions();
		foreach ( $fields as $name => $field ) {
			$properties[ $name ] = self::field_schema( $field );
		}
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => (object) $properties,
			'unavailable_fields'   => array_values( array_diff( array_keys( self::FIELDS ), array_keys( $fields ) ) ),
		);
	}

	/** Produces a JSON schema without exposing any stored product values. */
	private static function field_schema( array $field ): array {
		$type = $field['type'];
		if ( 'repeater' === $type ) {
			$properties = array();
			$required   = array();
			foreach ( $field['sub_fields'] as $child ) {
				$properties[ $child['name'] ] = self::field_schema( $child );
				if ( ! empty( $child['required'] ) ) {
					$required[] = $child['name'];
				}
			}
			$schema = array(
				'type'  => 'array',
				'items' => array(
					'type'                 => 'object',
					'properties'           => $properties,
					'additionalProperties' => false,
					'required'             => $required,
				),
			);
			if ( ! empty( $field['max'] ) ) {
				$schema['maxItems'] = (int) $field['max'];
			}
			if ( ! empty( $field['min'] ) ) {
				$schema['minItems'] = (int) $field['min'];
			}
			return $schema;
		}
		if ( 'image' === $type || 'file' === $type ) {
			return array(
				'type'       => array( 'integer', 'null' ),
				'minimum'    => 1,
				'media_type' => 'file' === $type ? 'application/pdf' : 'image',
			);
		}
		if ( 'true_false' === $type ) {
			return array( 'type' => 'boolean' );
		}
		$schema = array( 'type' => 'string' );
		if ( ! empty( $field['maxlength'] ) ) {
			$schema['maxLength'] = (int) $field['maxlength'];
		}
		return $schema;
	}

	/** Reads unformatted ACF content, mapping stored subfield keys to public names. */
	public static function read( int $product_id ): array {
		$values = array();
		foreach ( self::definitions() as $name => $field ) {
			$values[ $name ] = self::encode_value( $field, get_field( $field['key'], $product_id, false ) )['value'];
		}
		return $values;
	}

	/** Wraps mixed values to remain compatible with PHP 7.4 return types. */
	private static function encode_value( array $field, $value ): array {
		if ( 'repeater' === $field['type'] ) {
			$rows = array();
			foreach ( is_array( $value ) ? $value : array() as $row ) {
				$decoded = array();
				foreach ( $field['sub_fields'] as $child ) {
					$decoded[ $child['name'] ] = self::encode_value( $child, $row[ $child['key'] ] ?? $row[ $child['name'] ] ?? null )['value'];
				}
				$rows[] = $decoded;
			}
			return array( 'value' => $rows );
		}
		if ( 'true_false' === $field['type'] ) {
			return array( 'value' => (bool) $value );
		}
		if ( in_array( $field['type'], array( 'image', 'file' ), true ) ) {
			return array( 'value' => $value ? (int) $value : null );
		}
		return array( 'value' => (string) $value );
	}

	/** Validates every supplied value before the controller starts any write. */
	public static function prepare( \stdClass $input, int $product_id, \WP_Error $errors ): array {
		$definitions = self::definitions();
		$prepared    = array();
		foreach ( get_object_vars( $input ) as $name => $value ) {
			if ( ! isset( $definitions[ $name ] ) ) {
				$errors->add( 'fields.' . $name, isset( self::FIELDS[ $name ] ) ? __( 'Dit veld vereist beschikbare ACF-ondersteuning.', 'woo-card-chef' ) : __( 'Onbekend pluginveld.', 'woo-card-chef' ) );
				continue;
			}
			$prepared[ $definitions[ $name ]['key'] ] = self::prepare_value( $definitions[ $name ], $value, 'fields.' . $name, $errors )['value'];
		}
		if ( $errors->has_errors() || ! $prepared ) {
			return $prepared;
		}

		// Preserve ACF validation state for nested REST dispatches in the same process.
		$previous = acf_get_validation_errors();
		acf_reset_validation_errors();
		$has_form_data = function_exists( 'acf_get_form_data' ) && function_exists( 'acf_set_form_data' );
		$previous_id   = $has_form_data ? acf_get_form_data( 'post_id' ) : null;
		if ( $has_form_data ) {
			acf_set_form_data( 'post_id', $product_id );
		}
		try {
			foreach ( $prepared as $key => $value ) {
				$field = acf_get_field( $key );
				acf_validate_value( $value, $field, 'acf[' . $key . ']' );
			}
			foreach ( (array) acf_get_validation_errors() as $error ) {
				$errors->add( (string) $error['input'], $error['message'] );
			}
		} finally {
			acf_reset_validation_errors();
			foreach ( (array) $previous as $error ) {
				acf_add_validation_error( $error['input'], $error['message'] );
			}
			if ( $has_form_data ) {
				acf_set_form_data( 'post_id', $previous_id );
			}
		}
		return $prepared;
	}

	/** Normalizes API input into ACF field-key payloads, including repeater rows. */
	private static function prepare_value( array $field, $value, string $path, \WP_Error $errors ): array {
		$type = $field['type'];
		if ( 'repeater' === $type ) {
			if ( ! is_array( $value ) ) {
				$errors->add( $path, __( 'Verwacht een lijst.', 'woo-card-chef' ) );
				return array( 'value' => array() );
			}
			if ( ( ! empty( $field['max'] ) && count( $value ) > (int) $field['max'] ) || count( $value ) < (int) ( $field['min'] ?? 0 ) ) {
				$errors->add( $path, __( 'Aantal rijen valt buiten de veldlimieten.', 'woo-card-chef' ) );
			}
			$rows = array();
			foreach ( $value as $index => $row ) {
				if ( ! $row instanceof \stdClass ) {
					$errors->add( $path . '.' . $index, __( 'Verwacht een object per rij.', 'woo-card-chef' ) );
					continue;
				}
				$children = array_column( $field['sub_fields'], null, 'name' );
				foreach ( get_object_vars( $row ) as $key => $unused ) {
					if ( ! isset( $children[ $key ] ) ) {
						$errors->add( $path . '.' . $index . '.' . $key, __( 'Onbekend rijveld.', 'woo-card-chef' ) );
					}
				}
				$prepared = array();
				foreach ( $children as $name => $child ) {
					$default     = in_array( $child['type'], array( 'image', 'file' ), true ) ? null : '';
					$child_value = property_exists( $row, $name ) ? $row->$name : $default;
					if ( ! empty( $child['required'] ) && ( null === $child_value || '' === $child_value ) ) {
						$errors->add( $path . '.' . $index . '.' . $name, __( 'Dit veld is verplicht.', 'woo-card-chef' ) );
					}
					$prepared[ $child['key'] ] = self::prepare_value( $child, $child_value, $path . '.' . $index . '.' . $name, $errors )['value'];
				}
				$rows[] = $prepared;
			}
			return array( 'value' => $rows );
		}
		$valid = 'true_false' === $type ? is_bool( $value ) : is_string( $value );
		if ( in_array( $type, array( 'image', 'file' ), true ) ) {
			$valid = null === $value || ( is_int( $value ) && $value > 0 && 'attachment' === get_post_type( $value ) && ( 'image' === $type ? wp_attachment_is_image( $value ) : 'application/pdf' === get_post_mime_type( $value ) ) );
		}
		if ( ! $valid ) {
			$errors->add( $path, __( 'Verkeerd gegevenstype of ongeschikt media-ID.', 'woo-card-chef' ) );
		}
		// An empty attachment is represented by an empty ACF value, not deletion of its field reference.
		return array( 'value' => null === $value ? '' : $value );
	}

	/** Saves validated ACF values; verifies final values rather than false/no-op returns. */
	public static function save( int $product_id, array $prepared ): bool {
		foreach ( $prepared as $key => $value ) {
			// Observe the final metadata after ACF update filters (including every
			// repeater child and field reference). The repeater's root return value
			// alone cannot report a failed child write when its row count is unchanged.
			$expected_meta  = array();
			$deleted_meta   = array();
			$observe        = static function ( $check, $post_id, $name, $meta_value, $hidden ) use ( $product_id, &$expected_meta ) {
				if ( (string) $post_id === (string) $product_id ) {
					$expected_meta[ ( $hidden ? '_' : '' ) . $name ] = wp_unslash( $meta_value );
				}
				return $check;
			};
			$observe_delete = static function ( $check, $post_id, $name, $hidden ) use ( $product_id, &$deleted_meta ) {
				if ( (string) $post_id === (string) $product_id ) {
					$deleted_meta[] = ( $hidden ? '_' : '' ) . $name;
				}
				return $check;
			};
			add_filter( 'acf/pre_update_metadata', $observe, PHP_INT_MAX, 5 );
			add_filter( 'acf/pre_delete_metadata', $observe_delete, PHP_INT_MAX, 4 );
			try {
				// Like a WP admin submission, ACF metadata expects slashed input.
				update_field( $key, wp_slash( $value ), $product_id );
			} finally {
				remove_filter( 'acf/pre_update_metadata', $observe, PHP_INT_MAX );
				remove_filter( 'acf/pre_delete_metadata', $observe_delete, PHP_INT_MAX );
			}
			foreach ( $deleted_meta as $name ) {
				if ( metadata_exists( 'post', $product_id, $name ) ) {
					return false;
				}
			}
			foreach ( $expected_meta as $name => $expected_value ) {
				$actual_value = get_post_meta( $product_id, $name, true );
				if ( is_array( $expected_value ) ? $actual_value !== $expected_value : (string) $actual_value !== (string) $expected_value ) {
					return false;
				}
			}
			$field    = acf_get_field( $key );
			$expected = self::encode_value( $field, $value );
			$actual   = self::encode_value( $field, get_field( $key, $product_id, false ) );
			// A pre-update short circuit may bypass ACF metadata altogether.
			if ( ! $expected_meta && ! $deleted_meta && $actual !== $expected ) {
				return false;
			}
		}
		return true;
	}
}
