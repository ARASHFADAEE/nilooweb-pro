<?php
/**
 * Optional validation rules.
 *
 * @package Mino\Option
 */

declare(strict_types=1);

namespace Mino\Option\Validation;

defined( 'ABSPATH' ) || exit;

use Mino\Option\Registry\PageRegistry;
use Mino\Option\Storage\OptionStore;
use Mino\Option\Support\ConditionalEvaluator;

/**
 * Collects validation errors without throwing.
 */
final class Validator {

	/**
	 * @param string               $page_id Page ID.
	 * @param array<string, mixed> $values  Sanitized values.
	 * @param string|null          $tab_id  Optional tab ID to scope validation.
	 * @return array<string, string> Field ID => error message.
	 */
	public static function validate_page( string $page_id, array $values, ?string $tab_id = null ): array {
		$errors   = array();
		$registry = PageRegistry::instance();
		$tabs     = $registry->get_tabs( $page_id );
		$fields   = ( null !== $tab_id && '' !== $tab_id && isset( $tabs[ $tab_id ] ) )
			? $registry->get_tab_fields( $page_id, $tab_id )
			: $registry->get_fields( $page_id );

		$effective = array_merge( OptionStore::instance()->all( $page_id ), $values );

		foreach ( $fields as $field_id => $field ) {
			$dep = ! empty( $field['require'] ) ? $field['require'] : ( $field['dependency'] ?? array() );
			if ( ! empty( $dep ) && ! ConditionalEvaluator::passes( $dep, $effective ) ) {
				continue;
			}

			$value = $values[ $field_id ] ?? null;

			if ( ! empty( $field['required'] ) && self::is_empty( $value ) ) {
				$errors[ $field_id ] = sprintf(
					/* translators: %s: field title */
					__( '%s is required.', 'mino-option' ),
					(string) $field['title']
				);
				continue;
			}

			$type = (string) $field['type'];
			if ( 'email' === $type && is_string( $value ) && '' !== $value && ! is_email( $value ) ) {
				$errors[ $field_id ] = __( 'Please enter a valid email address.', 'mino-option' );
			}
			if ( 'url' === $type && is_string( $value ) && '' !== $value && ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
				$errors[ $field_id ] = __( 'Please enter a valid URL.', 'mino-option' );
			}

			if ( isset( $field['validate'] ) && is_callable( $field['validate'] ) ) {
				$result = call_user_func( $field['validate'], $value, $field, $values );
				if ( is_string( $result ) && '' !== $result ) {
					$errors[ $field_id ] = $result;
				} elseif ( false === $result ) {
					$errors[ $field_id ] = __( 'Invalid value.', 'mino-option' );
				}
			}
		}

		/**
		 * Filters validation errors.
		 *
		 * @param array  $errors  Errors.
		 * @param array  $values  Values.
		 * @param string $page_id Page ID.
		 */
		return apply_filters( 'mino_validate_page', $errors, $values, $page_id );
	}

	/**
	 * @param mixed $value Value.
	 */
	public static function is_empty( $value ): bool {
		if ( null === $value ) {
			return true;
		}
		if ( is_string( $value ) ) {
			return '' === $value;
		}
		if ( is_array( $value ) ) {
			return array() === $value;
		}
		// false, 0, "0" are NOT empty for required checks of booleans/numbers.
		return false;
	}
}
