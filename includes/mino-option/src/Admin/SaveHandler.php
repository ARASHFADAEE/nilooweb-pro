<?php
/**
 * Form save / reset handler (POST + AJAX).
 *
 * @package Mino\Option
 */

declare(strict_types=1);

namespace Mino\Option\Admin;

defined( 'ABSPATH' ) || exit;

use Mino\Option\Registry\PageRegistry;
use Mino\Option\Security\RequestGuard;
use Mino\Option\Storage\OptionStore;
use Mino\Option\Validation\Sanitizer;
use Mino\Option\Validation\Validator;

/**
 * Processes settings writes via classic POST redirect or admin-ajax.
 */
final class SaveHandler {

	public const AJAX_ACTION = 'mino_save_settings';

	/**
	 * Handle non-AJAX POST save/reset (progressive enhancement fallback).
	 */
	public static function maybe_handle(): void {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( empty( $_POST['mino_action'] ) || empty( $_POST['mino_page_id'] ) ) {
			return;
		}

		// Prefer AJAX; allow classic submit only when JS marks the form.
		if ( empty( $_POST['mino_no_js'] ) ) {
			// Still process classic posts for accessibility / no-JS users.
		}

		$page_id = sanitize_key( wp_unslash( (string) $_POST['mino_page_id'] ) );
		$action  = sanitize_key( wp_unslash( (string) $_POST['mino_action'] ) );
		$tab_id  = isset( $_POST['mino_tab'] ) ? sanitize_key( wp_unslash( (string) $_POST['mino_tab'] ) ) : '';

		$result = self::process_request( $page_id, $action, $_POST );

		$page = PageRegistry::instance()->get_page( $page_id );
		$slug = $page ? (string) $page['menu_slug'] : $page_id;

		if ( empty( $result['ok'] ) ) {
			if ( ! empty( $result['errors'] ) ) {
				set_transient(
					self::notice_key( $page_id ),
					array(
						'type'   => 'error',
						'errors' => $result['errors'],
					),
					45
				);
			}
			self::redirect( $slug, 'error', $tab_id );
			return;
		}

		self::redirect( $slug, 'reset' === $action ? 'reset' : 'saved', $tab_id );
	}

	/**
	 * AJAX endpoint for save / reset.
	 */
	public static function ajax_handle(): void {
		$page_id = isset( $_POST['mino_page_id'] ) ? sanitize_key( wp_unslash( (string) $_POST['mino_page_id'] ) ) : '';
		$action  = isset( $_POST['mino_action'] ) ? sanitize_key( wp_unslash( (string) $_POST['mino_action'] ) ) : 'save';

		$result = self::process_request( $page_id, $action, $_POST );

		if ( empty( $result['ok'] ) ) {
			wp_send_json_error(
				array(
					'message' => (string) ( $result['message'] ?? __( 'Save failed.', 'mino-option' ) ),
					'errors'  => $result['errors'] ?? array(),
				),
				! empty( $result['http'] ) ? (int) $result['http'] : 400
			);
		}

		wp_send_json_success(
			array(
				'message' => (string) ( $result['message'] ?? __( 'Settings saved.', 'mino-option' ) ),
				'action'  => $action,
			)
		);
	}

	/**
	 * Shared write pipeline.
	 *
	 * @param string               $page_id Page ID.
	 * @param string               $action  save|reset.
	 * @param array<string, mixed> $source  Request payload (typically $_POST).
	 * @return array{ok:bool,message?:string,errors?:array<string,string>,http?:int}
	 */
	public static function process_request( string $page_id, string $action, array $source ): array {
		if ( '' === $page_id || ! PageRegistry::instance()->has_page( $page_id ) ) {
			return array(
				'ok'      => false,
				'message' => __( 'Invalid settings page.', 'mino-option' ),
				'http'    => 400,
			);
		}

		if ( ! RequestGuard::authorize_write( $page_id ) ) {
			return array(
				'ok'      => false,
				'message' => __( 'You are not allowed to save these settings.', 'mino-option' ),
				'http'    => 403,
			);
		}

		if ( 'reset' === $action ) {
			OptionStore::instance()->reset( $page_id );
			return array(
				'ok'      => true,
				'message' => __( 'Settings reset to defaults.', 'mino-option' ),
			);
		}

		if ( 'save' !== $action ) {
			return array(
				'ok'      => false,
				'message' => __( 'Unknown action.', 'mino-option' ),
				'http'    => 400,
			);
		}

		$raw    = wp_unslash( $source );
		$tab_id = isset( $raw['mino_tab'] ) ? sanitize_key( (string) $raw['mino_tab'] ) : '';
		$tabs   = PageRegistry::instance()->get_tabs( $page_id );
		if ( '' === $tab_id || ! isset( $tabs[ $tab_id ] ) ) {
			$tab_id = null;
		}

		unset( $raw['mino_action'], $raw['mino_page_id'], $raw['mino_tab'], $raw['mino_nonce'], $raw['_wp_http_referer'], $raw['action'], $raw['mino_no_js'] );

		$fields = null !== $tab_id
			? PageRegistry::instance()->get_tab_fields( $page_id, $tab_id )
			: PageRegistry::instance()->get_fields( $page_id );

		foreach ( $fields as $fid => $field ) {
			if ( 'gallery' === $field['type'] && isset( $raw[ $fid ] ) && is_string( $raw[ $fid ] ) ) {
				$raw[ $fid ] = array_filter( array_map( 'absint', explode( ',', $raw[ $fid ] ) ) );
			}
		}

		$sanitized = Sanitizer::sanitize_page( $page_id, $raw, $tab_id );

		foreach ( $fields as $fid => $field ) {
			if ( in_array( (string) $field['type'], array( 'heading', 'notice', 'html' ), true ) ) {
				unset( $sanitized[ $fid ] );
			}
			if ( array_key_exists( $fid, $sanitized ) && null === $sanitized[ $fid ] ) {
				unset( $sanitized[ $fid ] );
			}
		}

		$errors = Validator::validate_page( $page_id, $sanitized, $tab_id );
		if ( ! empty( $errors ) ) {
			return array(
				'ok'      => false,
				'message' => __( 'Please fix the errors and try again.', 'mino-option' ),
				'errors'  => $errors,
				'http'    => 422,
			);
		}

		OptionStore::instance()->save( $page_id, $sanitized );

		return array(
			'ok'      => true,
			'message' => __( 'Settings saved.', 'mino-option' ),
		);
	}

	/**
	 * @param string $page_id Page ID.
	 */
	public static function notice_key( string $page_id ): string {
		return 'mino_notice_' . $page_id . '_' . get_current_user_id();
	}

	/**
	 * @param string $menu_slug Menu slug.
	 * @param string $status    Status flag.
	 * @param string $tab_id    Active tab ID.
	 */
	private static function redirect( string $menu_slug, string $status, string $tab_id = '' ): void {
		$args = array(
			'page'        => $menu_slug,
			'mino_status' => $status,
		);
		if ( '' !== $tab_id ) {
			$args['mino_tab'] = $tab_id;
		}
		$url = add_query_arg( $args, admin_url( 'admin.php' ) );
		wp_safe_redirect( $url );
		exit;
	}
}
