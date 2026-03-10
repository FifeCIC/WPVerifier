<?php
/**
 * Results AJAX Handler
 *
 * Handles AJAX requests related to results management and storage.
 * Extracted from Admin_AJAX.php as part of consolidation effort.
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Admin;

use InvalidArgumentException;
use WordPress\Plugin_Check\Verification\Results_Storage;
use WordPress\Plugin_Check\Utilities\Results_Exporter;

/**
 * Handles results-related AJAX requests
 */
class Results_AJAX_Handler {

	/**
	 * Nonce key for results actions
	 */
	const NONCE_KEY = 'plugin-check-run-checks';

	/**
	 * Register AJAX hooks for results actions
	 */
	public function add_hooks() {
		add_action( 'wp_ajax_plugin_check_save_results', array( $this, 'save_results' ) );
		add_action( 'wp_ajax_plugin_check_load_results', array( $this, 'load_results' ) );
		add_action( 'wp_ajax_plugin_check_list_saved_results', array( $this, 'list_saved_results' ) );
		add_action( 'wp_ajax_plugin_check_export_results', array( $this, 'export_results' ) );
		add_action( 'wp_ajax_plugin_check_delete_results', array( $this, 'delete_results' ) );
		add_action( 'wp_ajax_wpv_recheck_file', array( $this, 'recheck_file' ) );
	}

	/**
	 * Save results to storage
	 */
	public function save_results() {
		$this->check_request_validity();

		try {
			$results_payload = $this->extract_results_payload();
			$export_metadata = $this->prepare_export_metadata();
			
			$plugin_slug = $export_metadata['slug'];
			$results_storage = new Results_Storage( $plugin_slug );
			
			// Process and save results using storage class
			$processed_data = $this->process_results_data( $results_payload, $export_metadata );
			$result = $results_storage->save_results_data( $processed_data );
			
			if ( ! $result ) {
				throw new InvalidArgumentException( __( 'Failed to save results.', 'wp-verifier' ) );
			}

			$plugin_folder = strpos( $plugin_slug, '/' ) !== false ? dirname( $plugin_slug ) : $plugin_slug;
			$json_file = WP_PLUGIN_DIR . '/' . $plugin_folder . '/.wpv-results.json';
			
			wp_send_json_success( array(
				'message' => __( 'Results saved successfully.', 'wp-verifier' ),
				'path' => $json_file
			) );
			
		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Load results from storage
	 */
	public function load_results() {
		$this->check_request_validity();

		try {
			$plugin_slug = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin_slug ) ) {
				throw new InvalidArgumentException( __( 'Plugin slug is required.', 'wp-verifier' ) );
			}
			
			$results_storage = new Results_Storage( $plugin_slug );
			$data = $results_storage->load_results_data();
			
			if ( empty( $data ) || ! isset( $data['results'] ) ) {
				throw new InvalidArgumentException( __( 'No saved results found.', 'wp-verifier' ) );
			}
			
			// Load and merge AI guidance
			if ( ! class_exists( 'WordPress\\Plugin_Check\\Admin\\Saved_Results_Handler' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Saved_Results_Handler.php';
			}
			$data = Saved_Results_Handler::merge_ai_guidance( $data );
			
			$plugin_folder = strpos( $plugin_slug, '/' ) !== false ? dirname( $plugin_slug ) : $plugin_slug;
			$json_file = WP_PLUGIN_DIR . '/' . $plugin_folder . '/.wpv-results.json';
			
			wp_send_json_success( array(
				'path' => $json_file,
				'modified' => file_exists( $json_file ) ? filemtime( $json_file ) : time(),
				'data' => $data
			) );
			
		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * List all saved results
	 */
	public function list_saved_results() {
		$this->check_request_validity();

		try {
			$verifier_base_dir = WP_PLUGIN_DIR;
			
			if ( ! file_exists( $verifier_base_dir ) ) {
				wp_send_json_success( array( 'results' => array() ) );
				return;
			}
			
			$results = array();
			$dirs = glob( $verifier_base_dir . '/*', GLOB_ONLYDIR );
			
			foreach ( $dirs as $dir ) {
				$json_file = $dir . '/.wpv-results.json';
				if ( file_exists( $json_file ) ) {
					$plugin_name = basename( $dir );
					$results[] = array(
						'plugin' => $plugin_name,
						'path' => $json_file,
						'modified' => filemtime( $json_file ),
					);
				}
			}
			
			wp_send_json_success( array( 'results' => $results ) );
			
		} catch ( \Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Export results in various formats
	 */
	public function export_results() {
		$this->check_request_validity();

		try {
			$format = $this->determine_export_format();
			$results_payload = $this->extract_results_payload();
			$export_metadata = $this->prepare_export_metadata();
			$payload = $this->build_export_payload( $results_payload, $format, $export_metadata );
		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}

		wp_send_json_success( $payload );
	}

	/**
	 * Delete saved results
	 */
	public function delete_results() {
		$this->check_request_validity();

		try {
			$plugin_slug = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin_slug ) ) {
				throw new InvalidArgumentException( __( 'Plugin slug is required.', 'wp-verifier' ) );
			}

			$plugin_folder = strpos( $plugin_slug, '/' ) !== false ? dirname( $plugin_slug ) : $plugin_slug;
			$verifier_dir = WP_PLUGIN_DIR . '/' . $plugin_folder;

			if ( ! file_exists( $verifier_dir ) ) {
				throw new InvalidArgumentException( __( 'Results folder not found.', 'wp-verifier' ) );
			}

			$this->delete_directory( $verifier_dir );

			wp_send_json_success( array(
				'message' => __( 'Results deleted successfully.', 'wp-verifier' ),
			) );

		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Recheck a single file
	 */
	public function recheck_file() {
		$this->check_request_validity();

		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$file = isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';

			if ( empty( $plugin ) || empty( $file ) ) {
				throw new InvalidArgumentException( __( 'Plugin and file are required.', 'wp-verifier' ) );
			}

			if ( ! file_exists( $file ) ) {
				throw new InvalidArgumentException( __( 'File does not exist.', 'wp-verifier' ) );
			}

			// Run PHPCS directly on single file
			$results = $this->run_phpcs_on_file( $file );

			// Update saved results and regenerate hashes
			$this->update_file_results( $plugin, $file, $results['errors'], $results['warnings'] );

			wp_send_json_success( array(
				'message' => __( 'File rechecked successfully.', 'wp-verifier' ),
				'errors' => $results['errors'],
				'warnings' => $results['warnings'],
			) );

		} catch ( \Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Extract results payload from request
	 */
	private function extract_results_payload() {
		$raw_results = isset( $_POST['results'] ) ? wp_unslash( $_POST['results'] ) : '';
		if ( '' === $raw_results ) {
			throw new InvalidArgumentException( __( 'Invalid or empty results payload.', 'wp-verifier' ) );
		}

		$decoded_results = json_decode( $raw_results, true );
		if ( null === $decoded_results || JSON_ERROR_NONE !== json_last_error() ) {
			throw new InvalidArgumentException( __( 'Malformed results payload.', 'wp-verifier' ) );
		}

		return array(
			'errors'   => isset( $decoded_results['errors'] ) && is_array( $decoded_results['errors'] ) ? $decoded_results['errors'] : array(),
			'warnings' => isset( $decoded_results['warnings'] ) && is_array( $decoded_results['warnings'] ) ? $decoded_results['warnings'] : array(),
		);
	}

	/**
	 * Prepare export metadata
	 */
	private function prepare_export_metadata() {
		$plugin_slug  = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$plugin_label = isset( $_POST['plugin_label'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_label'] ) ) : '';
		if ( empty( $plugin_label ) ) {
			$plugin_label = $plugin_slug;
		}

		return array(
			'plugin'          => $plugin_label,
			'slug'            => $plugin_slug,
			'timestamp'       => current_time( 'Ymd-His' ),
			'timestamp_human' => current_time( 'mysql' ),
		);
	}

	/**
	 * Process results data for storage
	 */
	private function process_results_data( $results_payload, $export_metadata ) {
		// Convert grouped format to flat format and process
		$errors_flat = $this->convert_grouped_to_flat( $results_payload['errors'] );
		$warnings_flat = $this->convert_grouped_to_flat( $results_payload['warnings'] );
		
		// Calculate readiness score
		if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Readiness_Score' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Readiness_Score.php';
		}
		$readiness = \WordPress\Plugin_Check\Utilities\Readiness_Score::calculate( $errors_flat, $warnings_flat );
		
		// Group by file in simple format
		$results_by_file = array();
		$this->add_issues_to_results( $results_by_file, $errors_flat, 'ERROR' );
		$this->add_issues_to_results( $results_by_file, $warnings_flat, 'WARNING' );
		
		return array(
			'generated_at' => $export_metadata['timestamp_human'],
			'updated_at' => $export_metadata['timestamp_human'],
			'plugin' => $export_metadata['plugin'],
			'readiness' => $readiness,
			'results' => empty( $results_by_file ) ? new \stdClass() : $results_by_file,
		);
	}

	/**
	 * Convert grouped format to flat format
	 */
	private function convert_grouped_to_flat( $grouped ) {
		$flat = array();
		foreach ( $grouped as $file => $lines ) {
			if ( empty( $file ) ) continue;
			foreach ( $lines as $line => $columns ) {
				foreach ( $columns as $column => $issues ) {
					foreach ( $issues as $issue ) {
						if ( empty( $issue['message'] ) && empty( $issue['code'] ) ) continue;
						$flat[] = array_merge( $issue, array(
							'file' => $file,
							'line' => (int) $line,
							'column' => (int) $column,
						) );
					}
				}
			}
		}
		return $flat;
	}

	/**
	 * Add issues to results array
	 */
	private function add_issues_to_results( &$results, $issues, $type ) {
		foreach ( $issues as $issue ) {
			$file = $issue['file'];
			if ( empty( $file ) ) continue;
			
			if ( ! isset( $results[ $file ] ) ) {
				$results[ $file ] = array();
			}
			
			$issue_id = ( 'ERROR' === $type ? 'E-' : 'W-' ) . substr( md5( basename( $file ) . '-' . $issue['line'] ), 0, 8 );
			$results[ $file ][] = array(
				'issue_id' => $issue_id,
				'message' => $issue['message'] ?? '',
				'code' => $issue['code'] ?? '',
				'type' => $type,
				'line' => $issue['line'],
				'column' => $issue['column'],
				'ignored' => false,
				'resolved' => false,
			);
		}
	}

	/**
	 * Determine export format
	 */
	private function determine_export_format() {
		$format = filter_input( INPUT_POST, 'format', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( empty( $format ) ) {
			return Results_Exporter::FORMAT_JSON;
		}
		return strtolower( $format );
	}

	/**
	 * Build export payload
	 */
	private function build_export_payload( array $results_payload, $format, array $metadata ) {
		return Results_Exporter::export(
			$results_payload['errors'],
			$results_payload['warnings'],
			$format,
			$metadata
		);
	}

	/**
	 * Run PHP_CodeSniffer on a single file
	 */
	private function run_phpcs_on_file( $file ) {
		if ( ! class_exists( 'PHP_CodeSniffer\\Runner' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'vendor/squizlabs/php_codesniffer/autoload.php';
		}

		// Set required PHPCS constants
		if ( ! defined( 'PHP_CODESNIFFER_VERBOSITY' ) ) {
			define( 'PHP_CODESNIFFER_VERBOSITY', 0 );
		}
		if ( ! defined( 'PHP_CODESNIFFER_CBF' ) ) {
			define( 'PHP_CODESNIFFER_CBF', false );
		}
		if ( ! isset( $_SERVER['argv'] ) ) {
			$_SERVER['argv'] = array();
		}

		$runner = new \PHP_CodeSniffer\Runner();
		$runner->config = new \PHP_CodeSniffer\Config( array( $file, '--standard=WordPress', '--report=json' ) );
		$runner->ruleset = new \PHP_CodeSniffer\Ruleset( $runner->config );

		ob_start();
		$runner->runPHPCS();
		$output = ob_get_clean();

		$json_data = json_decode( $output, true );
		$errors = array();
		$warnings = array();

		if ( isset( $json_data['files'][ $file ]['messages'] ) ) {
			foreach ( $json_data['files'][ $file ]['messages'] as $message ) {
				$line = $message['line'];
				$column = $message['column'];
				$issue = array(
					'message' => $message['message'],
					'code' => $message['source'],
					'severity' => $message['severity'],
					'link' => null,
					'docs' => '',
				);

				if ( 'ERROR' === $message['type'] ) {
					if ( ! isset( $errors[ $file ] ) ) {
						$errors[ $file ] = array();
					}
					if ( ! isset( $errors[ $file ][ $line ] ) ) {
						$errors[ $file ][ $line ] = array();
					}
					if ( ! isset( $errors[ $file ][ $line ][ $column ] ) ) {
						$errors[ $file ][ $line ][ $column ] = array();
					}
					$errors[ $file ][ $line ][ $column ][] = $issue;
				} else {
					if ( ! isset( $warnings[ $file ] ) ) {
						$warnings[ $file ] = array();
					}
					if ( ! isset( $warnings[ $file ][ $line ] ) ) {
						$warnings[ $file ][ $line ] = array();
					}
					if ( ! isset( $warnings[ $file ][ $line ][ $column ] ) ) {
						$warnings[ $file ][ $line ][ $column ] = array();
					}
					$warnings[ $file ][ $line ][ $column ][] = $issue;
				}
			}
		}

		return array(
			'errors' => $errors,
			'warnings' => $warnings,
		);
	}

	/**
	 * Update file results in saved JSON
	 */
	private function update_file_results( $plugin, $file, $errors, $warnings ) {
		$plugin_folder = strpos( $plugin, '/' ) !== false ? dirname( $plugin ) : $plugin;
		$json_file = WP_PLUGIN_DIR . '/' . $plugin_folder . '/.wpv-results.json';

		if ( ! file_exists( $json_file ) ) {
			return;
		}

		$data = json_decode( file_get_contents( $json_file ), true );
		if ( ! $data || ! isset( $data['results'] ) ) {
			return;
		}

		// Remove old results for this file
		unset( $data['results'][ $file ] );

		// Convert grouped format to flat and use existing processing logic
		$errors_flat = $this->convert_grouped_to_flat( $errors );
		$warnings_flat = $this->convert_grouped_to_flat( $warnings );
		
		// Process using same logic as save_results
		$this->add_issues_to_results( $data['results'], $errors_flat, 'ERROR' );
		$this->add_issues_to_results( $data['results'], $warnings_flat, 'WARNING' );

		$data['updated_at'] = current_time( 'mysql' );
		file_put_contents( $json_file, wp_json_encode( $data, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Recursively delete a directory
	 */
	private function delete_directory( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$files = array_diff( scandir( $dir ), array( '.', '..' ) );
		foreach ( $files as $file ) {
			$path = $dir . '/' . $file;
			if ( is_dir( $path ) ) {
				$this->delete_directory( $path );
			} else {
				unlink( $path );
			}
		}
		rmdir( $dir );
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