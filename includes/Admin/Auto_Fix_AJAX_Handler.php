<?php
/**
 * Auto Fix AJAX Handler
 *
 * Handles the wpv_auto_fix AJAX action: applies a deterministic code fix to a
 * single flagged issue in a plugin file, then removes the issue from the results JSON.
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

use WordPress\Plugin_Check\Utilities\Auto_Fix_Engine;
use WordPress\Plugin_Check\Utilities\Path_Builder;
use WP_Error;

/**
 * Handles AJAX requests for auto-fixing individual PHPCS issues.
 */
class Auto_Fix_AJAX_Handler {

	/**
	 * Nonce key (shared with Verification_AJAX_Handler).
	 */
	const NONCE_KEY = 'plugin-check-run-checks';

	/**
	 * Register AJAX hook.
	 */
	public function add_hooks() {
		add_action( 'wp_ajax_wpv_auto_fix', array( $this, 'auto_fix_issue' ) );
	}

	/**
	 * Handle wpv_auto_fix AJAX request.
	 *
	 * Expected POST params:
	 *   nonce    — WordPress nonce (plugin-check-run-checks)
	 *   plugin   — Plugin slug (e.g. tradepress/tradepress.php)
	 *   issue_id — Issue ID from .wpv-results.json
	 *   file     — Relative file path within the plugin
	 *   line     — 1-based line number
	 *   code     — PHPCS sniff code
	 *
	 * @return void  Sends JSON and exits.
	 */
	public function auto_fix_issue() {
		$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_KEY ) ) {
			wp_send_json_error( new WP_Error( 'invalid-nonce', __( 'Invalid nonce.', 'wpverifier' ) ), 403 );
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( new WP_Error( 'insufficient-permissions', __( 'You do not have permission to perform this action.', 'wpverifier' ) ), 403 );
		}

		$plugin   = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$issue_id = filter_input( INPUT_POST, 'issue_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$file     = filter_input( INPUT_POST, 'file', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$line     = filter_input( INPUT_POST, 'line', FILTER_VALIDATE_INT );
		$code     = filter_input( INPUT_POST, 'code', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( empty( $plugin ) || empty( $issue_id ) || empty( $file ) || ! $line || empty( $code ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Missing required parameters (plugin, issue_id, file, line, code).', 'wpverifier' ) ),
				400
			);
		}

		// Load the engine.
		if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Auto_Fix_Engine' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Auto_Fix_Engine.php';
		}

		if ( ! Auto_Fix_Engine::is_fixable( $code ) ) {
			wp_send_json_error(
				array( 'message' => sprintf( __( 'Code %s is not supported for auto-fix.', 'wpverifier' ), esc_html( $code ) ) ),
				400
			);
		}

		// Apply the fix.
		$fix_result = Auto_Fix_Engine::fix_issue( $plugin, $file, $line, $code );

		if ( ! $fix_result['success'] ) {
			wp_send_json_error(
				array( 'message' => $fix_result['message'] ),
				422
			);
		}

		// Remove the issue from the results JSON.
		$removed = $this->remove_issue_from_results( $plugin, $issue_id );

		wp_send_json_success(
			array(
				'message'       => __( 'Fix applied. Issue removed from results.', 'wpverifier' ),
				'original_line' => $fix_result['original_line'],
				'fixed_line'    => $fix_result['fixed_line'],
				'backup_file'   => basename( $fix_result['backup_file'] ),
				'issue_removed' => $removed,
			)
		);
	}

	/**
	 * Remove a single issue from the plugin's .wpv-results.json file.
	 *
	 * @param string $plugin   Plugin slug.
	 * @param string $issue_id Issue ID to remove.
	 * @return bool True if the issue was found and removed.
	 */
	private function remove_issue_from_results( $plugin, $issue_id ) {
		$results_file = Path_Builder::get_results_file_path( $plugin );

		if ( ! file_exists( $results_file ) ) {
			return false;
		}

		$results_data = json_decode( file_get_contents( $results_file ), true );
		if ( ! is_array( $results_data ) || empty( $results_data['results'] ) ) {
			return false;
		}

		$found = false;
		foreach ( $results_data['results'] as $file_path => &$issues ) {
			foreach ( $issues as $idx => $issue ) {
				if ( isset( $issue['issue_id'] ) && $issue['issue_id'] === $issue_id ) {
					unset( $issues[ $idx ] );
					$found = true;
					break 2;
				}
			}
		}
		unset( $issues );

		if ( ! $found ) {
			return false;
		}

		// Remove empty file entries.
		foreach ( $results_data['results'] as $fp => $issues ) {
			if ( empty( $issues ) ) {
				unset( $results_data['results'][ $fp ] );
			} else {
				// Re-index array.
				$results_data['results'][ $fp ] = array_values( $issues );
			}
		}

		// Recalculate readiness.
		$total_errors   = 0;
		$total_warnings = 0;
		foreach ( $results_data['results'] as $issues ) {
			foreach ( $issues as $issue ) {
				if ( ! empty( $issue['ignored'] ) ) {
					continue;
				}
				if ( isset( $issue['type'] ) && 'ERROR' === $issue['type'] ) {
					$total_errors++;
				} elseif ( isset( $issue['type'] ) && 'WARNING' === $issue['type'] ) {
					$total_warnings++;
				}
			}
		}
		$results_data['readiness']['errors']  = $total_errors;
		$results_data['readiness']['warnings'] = $total_warnings;
		$results_data['readiness']['overall']  = min( 100, max( 0, 100 - ( $total_errors * 2 + $total_warnings ) ) );

		file_put_contents( $results_file, wp_json_encode( $results_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );

		return true;
	}
}
