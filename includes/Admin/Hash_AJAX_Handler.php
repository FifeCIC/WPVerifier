<?php
/**
 * Hash AJAX Handler
 *
 * Handles AJAX requests related to hash generation and verification tracking.
 * Extracted from Admin_AJAX.php as part of consolidation effort.
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Admin;

use InvalidArgumentException;
use WordPress\Plugin_Check\Verification\Hash_Generator;
use WordPress\Plugin_Check\Verification\JSON_Storage;
use WordPress\Plugin_Check\Utilities\Path_Builder;

/**
 * Handles hash-related AJAX requests
 */
class Hash_AJAX_Handler {

	/**
	 * Nonce key for hash actions
	 */
	const NONCE_KEY = 'plugin-check-run-checks';

	/**
	 * Register AJAX hooks for hash actions
	 */
	public function add_hooks() {
		add_action( 'wp_ajax_wpv_generate_hashes', array( $this, 'generate_hashes' ) );
		add_action( 'wp_ajax_wpv_check_hashes', array( $this, 'check_hashes' ) );
		add_action( 'wp_ajax_wpv_mark_ignored', array( $this, 'mark_ignored' ) );
		add_action( 'wp_ajax_wpv_mark_resolved', array( $this, 'mark_resolved' ) );
	}

	/**
	 * Generate file hashes for verification tracking
	 */
	public function generate_hashes() {
		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin ) ) {
				throw new InvalidArgumentException( __( 'Plugin is required.', 'wp-verifier' ) );
			}

			$hash_generator = new Hash_Generator();

			$plugin_dir = Path_Builder::get_plugin_directory_path( $plugin );
			$verification_file = $plugin_dir . '/.wpv-verification.json';

			// Load existing verification data
			$verification_data = array();
			if ( file_exists( $verification_file ) ) {
				$verification_data = json_decode( file_get_contents( $verification_file ), true );
			}
			if ( ! is_array( $verification_data ) ) {
				$verification_data = array(
					'version' => '1.0',
					'file_level' => array(),
					'function_level' => array(),
				);
			}

			// Generate hashes for all PHP files in plugin
			$files = glob( $plugin_dir . '/*.php' );
			$files = array_merge( $files, glob( $plugin_dir . '/**/*.php' ) );

			$file_hashes = array();
			$function_hashes = array();

			foreach ( $files as $file ) {
				if ( file_exists( $file ) ) {
					$relative_path = str_replace( $plugin_dir . '/', '', $file );
					$file_hashes[ $relative_path ] = $hash_generator->generate_file_hash( $file );

					$func_hashes = $hash_generator->generate_function_hashes( $file );
					if ( ! empty( $func_hashes ) ) {
						$function_hashes[ $relative_path ] = $func_hashes;
					}
				}
			}

			// Update verification data
			$verification_data['file_hashes'] = $file_hashes;
			$verification_data['function_hashes'] = $function_hashes;
			$verification_data['updated_at'] = current_time( 'mysql' );

			// Save to verification file only
			file_put_contents( $verification_file, wp_json_encode( $verification_data, JSON_PRETTY_PRINT ) );

			wp_send_json_success( array(
				'message' => sprintf( __( 'Generated hashes for %d files', 'wp-verifier' ), count( $file_hashes ) ),
				'file_count' => count( $file_hashes ),
				'function_count' => array_sum( array_map( 'count', $function_hashes ) ),
			) );

		} catch ( \Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Check if hashes exist for the plugin
	 */
	public function check_hashes() {
		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin ) ) {
				throw new InvalidArgumentException( __( 'Plugin is required.', 'wp-verifier' ) );
			}

			$plugin_dir = Path_Builder::get_plugin_directory_path( $plugin );
			$verification_file = $plugin_dir . '/.wpv-verification.json';

			$has_hashes = false;
			$hash_count = 0;
			$function_count = 0;

			if ( file_exists( $verification_file ) ) {
				$verification_data = json_decode( file_get_contents( $verification_file ), true );
				if ( $verification_data && isset( $verification_data['file_hashes'] ) && ! empty( $verification_data['file_hashes'] ) ) {
					$has_hashes = true;
					$hash_count = count( $verification_data['file_hashes'] );
					if ( isset( $verification_data['function_hashes'] ) ) {
						$function_count = array_sum( array_map( 'count', $verification_data['function_hashes'] ) );
					}
				}
			}

			wp_send_json_success( array(
				'has_hashes' => $has_hashes,
				'hash_count' => $hash_count,
				'function_count' => $function_count,
				'verification_file' => $verification_file
			) );

		} catch ( \Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Mark issue as ignored
	 */
	public function mark_ignored() {
		$this->check_request_validity();

		try {
			$issue_id = isset( $_POST['issue_id'] ) ? sanitize_text_field( wp_unslash( $_POST['issue_id'] ) ) : '';
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

			if ( empty( $issue_id ) || empty( $plugin ) ) {
				throw new InvalidArgumentException( __( 'Issue ID and plugin are required.', 'wp-verifier' ) );
			}

			$json_file = Path_Builder::get_results_file_path( $plugin );

			if ( ! file_exists( $json_file ) ) {
				throw new InvalidArgumentException( __( 'Results file not found.', 'wp-verifier' ) );
			}

			$data = json_decode( file_get_contents( $json_file ), true );
			if ( ! $data || ! isset( $data['results'] ) ) {
				throw new InvalidArgumentException( __( 'Invalid results file.', 'wp-verifier' ) );
			}

			$updated = false;
			foreach ( $data['results'] as $file => &$issues ) {
				foreach ( $issues as &$issue ) {
					if ( $issue['issue_id'] === $issue_id ) {
						$issue['ignored'] = true;
						$issue['ignored_by'] = wp_get_current_user()->user_login;
						$updated = true;
						break 2;
					}
				}
			}

			if ( ! $updated ) {
				throw new InvalidArgumentException( __( 'Issue not found.', 'wp-verifier' ) );
			}

			$data['updated_at'] = current_time( 'mysql' );
			file_put_contents( $json_file, wp_json_encode( $data, JSON_PRETTY_PRINT ) );

			wp_send_json_success( array(
				'message' => __( 'Issue marked as ignored.', 'wp-verifier' ),
			) );

		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Mark issue as resolved
	 */
	public function mark_resolved() {
		$this->check_request_validity();

		try {
			$issue_id = isset( $_POST['issue_id'] ) ? sanitize_text_field( wp_unslash( $_POST['issue_id'] ) ) : '';
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

			if ( empty( $issue_id ) || empty( $plugin ) ) {
				throw new InvalidArgumentException( __( 'Issue ID and plugin are required.', 'wp-verifier' ) );
			}

			$json_file = Path_Builder::get_results_file_path( $plugin );

			if ( ! file_exists( $json_file ) ) {
				throw new InvalidArgumentException( __( 'Results file not found.', 'wp-verifier' ) );
			}

			$data = json_decode( file_get_contents( $json_file ), true );
			if ( ! $data || ! isset( $data['results'] ) ) {
				throw new InvalidArgumentException( __( 'Invalid results file.', 'wp-verifier' ) );
			}

			$updated = false;
			foreach ( $data['results'] as $file => &$issues ) {
				foreach ( $issues as &$issue ) {
					if ( $issue['issue_id'] === $issue_id ) {
						$issue['resolved'] = true;
						$issue['resolved_by'] = wp_get_current_user()->user_login;
						$updated = true;
						break 2;
					}
				}
			}

			if ( ! $updated ) {
				throw new InvalidArgumentException( __( 'Issue not found.', 'wp-verifier' ) );
			}

			$data['updated_at'] = current_time( 'mysql' );
			file_put_contents( $json_file, wp_json_encode( $data, JSON_PRETTY_PRINT ) );

			wp_send_json_success( array(
				'message' => __( 'Issue marked as resolved.', 'wp-verifier' ) ),
			);

		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Check if the request is valid
	 */
	private function check_request_validity() {
		$valid_request = $this->verify_request( filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );

		if ( is_wp_error( $valid_request ) ) {
			wp_send_json_error( $valid_request, 403 );
		}
	}

	/**
	 * Verify the request nonce and permissions
	 *
	 * @param string $nonce The request nonce passed
	 * @return bool|\WP_Error True if valid, WP_Error if invalid
	 */
	private function verify_request( $nonce ) {
		if ( ! wp_verify_nonce( $nonce, self::NONCE_KEY ) ) {
			return new \WP_Error( 'invalid-nonce', __( 'Invalid nonce', 'wp-verifier' ) );
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return new \WP_Error( 'invalid-permissions', __( 'Invalid user permissions, you are not allowed to perform this request.', 'wp-verifier' ) );
		}

		return true;
	}
}