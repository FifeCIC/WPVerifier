<?php
/**
 * Class WordPress\Plugin_Check\Admin\Admin_AJAX
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

use Exception;
use InvalidArgumentException;
use WordPress\Plugin_Check\Checker\AJAX_Runner;
use WordPress\Plugin_Check\Checker\Runtime_Check;
use WordPress\Plugin_Check\Checker\Runtime_Environment_Setup;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;
use WordPress\Plugin_Check\Utilities\Results_Exporter;
use WP_Error;

/**
 * Class to handle the Admin AJAX requests.
 *
 * @since 1.0.0
 */
final class Admin_AJAX {

	/**
	 * Nonce key.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const NONCE_KEY = 'plugin-check-run-checks';

	/**
	 * Clean up Runtime Environment action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTION_CLEAN_UP_ENVIRONMENT = 'plugin_check_clean_up_environment';

	/**
	 * Set up Runtime Environment action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTION_SET_UP_ENVIRONMENT = 'plugin_check_set_up_environment';

	/**
	 * Get Checks to run action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTION_GET_CHECKS_TO_RUN = 'plugin_check_get_checks_to_run';

	/**
	 * Run Checks action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTION_RUN_CHECKS = 'plugin_check_run_checks';

	/**
	 * Export results action name.
	 *
	 * @since 1.8.0
	 * @var string
	 */
	const ACTION_EXPORT_RESULTS = 'plugin_check_export_results';

	/**
	 * Save results action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTION_SAVE_RESULTS = 'plugin_check_save_results';

	/**
	 * Load results action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTION_LOAD_RESULTS = 'plugin_check_load_results';

	/**
	 * List saved results action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTION_LIST_SAVED_RESULTS = 'plugin_check_list_saved_results';

	/**
	 * Add ignore rule action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTION_ADD_IGNORE_RULE = 'plugin_check_add_ignore_rule';

	/**
	 * Add directory ignore rule action name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ACTION_ADD_IGNORE_DIRECTORY = 'plugin_check_add_ignore_directory';

	/**
	 * Get scan history action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_GET_SCAN_HISTORY = 'plugin_check_get_scan_history';

	/**
	 * Clear scan history action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_CLEAR_SCAN_HISTORY = 'plugin_check_clear_scan_history';

	/**
	 * Generate report action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_GENERATE_REPORT = 'plugin_check_generate_report';

	/**
	 * Check domains action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_CHECK_DOMAINS = 'plugin_check_domains';

	/**
	 * Save name action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_SAVE_NAME = 'plugin_check_save_name';

	/**
	 * Get saved names action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_GET_SAVED_NAMES = 'plugin_check_get_saved_names';

	/**
	 * Check name conflicts action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_CHECK_CONFLICTS = 'plugin_check_name_conflicts';

	/**
	 * Analyze SEO action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_ANALYZE_SEO = 'plugin_check_analyze_seo';

	/**
	 * Check trademarks action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_CHECK_TRADEMARKS = 'plugin_check_check_trademarks';

	/**
	 * Start monitoring action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_START_MONITORING = 'plugin_check_start_monitoring';

	/**
	 * Stop monitoring action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_STOP_MONITORING = 'plugin_check_stop_monitoring';

	/**
	 * Check file changes action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_CHECK_FILE_CHANGES = 'plugin_check_file_changes';

	/**
	 * Get monitor log action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_GET_MONITOR_LOG = 'plugin_check_monitor_log';

	/**
	 * Delete saved results action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_DELETE_RESULTS = 'plugin_check_delete_results';

	/**
	 * Mark issue complete action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_MARK_COMPLETE = 'plugin_check_mark_complete';

	/**
	 * Detect vendors action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_DETECT_VENDORS = 'plugin_check_detect_vendors';

	/**
	 * Save ignored paths action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_SAVE_IGNORED_PATHS = 'plugin_check_save_ignored_paths';

	/**
	 * Basic check action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_BASIC_CHECK = 'plugin_check_basic_check';

	/**
	 * Validate structure action name.
	 *
	 * @since 1.9.0
	 * @var string
	 */
	const ACTION_VALIDATE_STRUCTURE = 'plugin_check_validate_structure';

	/**
	 * Registers WordPress hooks for the Admin AJAX.
	 *
	 * @since 1.0.0
	 */
	public function add_hooks() {
		add_action( 'wp_ajax_' . self::ACTION_CLEAN_UP_ENVIRONMENT, array( $this, 'clean_up_environment' ) );
		add_action( 'wp_ajax_' . self::ACTION_SET_UP_ENVIRONMENT, array( $this, 'set_up_environment' ) );
		add_action( 'wp_ajax_' . self::ACTION_GET_CHECKS_TO_RUN, array( $this, 'get_checks_to_run' ) );
		add_action( 'wp_ajax_' . self::ACTION_RUN_CHECKS, array( $this, 'run_checks' ) );
		add_action( 'wp_ajax_' . self::ACTION_EXPORT_RESULTS, array( $this, 'export_results' ) );
		add_action( 'wp_ajax_' . self::ACTION_SAVE_RESULTS, array( $this, 'save_results' ) );
		add_action( 'wp_ajax_' . self::ACTION_LOAD_RESULTS, array( $this, 'load_results' ) );
		add_action( 'wp_ajax_' . self::ACTION_LIST_SAVED_RESULTS, array( $this, 'list_saved_results' ) );
		add_action( 'wp_ajax_' . self::ACTION_ADD_IGNORE_RULE, array( $this, 'add_ignore_rule' ) );
		add_action( 'wp_ajax_' . self::ACTION_ADD_IGNORE_DIRECTORY, array( $this, 'add_ignore_directory' ) );
		add_action( 'wp_ajax_' . self::ACTION_GET_SCAN_HISTORY, array( $this, 'get_scan_history' ) );
		add_action( 'wp_ajax_' . self::ACTION_CLEAR_SCAN_HISTORY, array( $this, 'clear_scan_history' ) );
		add_action( 'wp_ajax_' . self::ACTION_GENERATE_REPORT, array( $this, 'generate_report' ) );
		add_action( 'wp_ajax_' . self::ACTION_CHECK_DOMAINS, array( $this, 'check_domains' ) );
		add_action( 'wp_ajax_' . self::ACTION_SAVE_NAME, array( $this, 'save_name' ) );
		add_action( 'wp_ajax_' . self::ACTION_GET_SAVED_NAMES, array( $this, 'get_saved_names' ) );
		add_action( 'wp_ajax_' . self::ACTION_CHECK_CONFLICTS, array( $this, 'check_conflicts' ) );
		add_action( 'wp_ajax_' . self::ACTION_ANALYZE_SEO, array( $this, 'analyze_seo' ) );
		add_action( 'wp_ajax_' . self::ACTION_CHECK_TRADEMARKS, array( $this, 'check_trademarks' ) );
		add_action( 'wp_ajax_' . self::ACTION_START_MONITORING, array( $this, 'start_monitoring' ) );
		add_action( 'wp_ajax_' . self::ACTION_STOP_MONITORING, array( $this, 'stop_monitoring' ) );
		add_action( 'wp_ajax_' . self::ACTION_CHECK_FILE_CHANGES, array( $this, 'check_file_changes' ) );
		add_action( 'wp_ajax_' . self::ACTION_GET_MONITOR_LOG, array( $this, 'get_monitor_log' ) );
		add_action( 'wp_ajax_' . self::ACTION_MARK_COMPLETE, array( $this, 'mark_complete' ) );
		add_action( 'wp_ajax_' . self::ACTION_DELETE_RESULTS, array( $this, 'delete_results' ) );
		add_action( 'wp_ajax_' . self::ACTION_BASIC_CHECK, array( $this, 'basic_check' ) );
		add_action( 'wp_ajax_' . self::ACTION_DETECT_VENDORS, array( $this, 'detect_vendors' ) );
		add_action( 'wp_ajax_' . self::ACTION_SAVE_IGNORED_PATHS, array( $this, 'save_ignored_paths' ) );
		add_action( 'wp_ajax_save_user_meta', array( $this, 'save_user_meta' ) );
		add_action( 'wp_ajax_plugin_check_detect_folders', array( $this, 'detect_folders' ) );
		add_action( 'wp_ajax_' . self::ACTION_VALIDATE_STRUCTURE, array( $this, 'validate_structure' ) );
		add_action( 'wp_ajax_wpv_get_mark_fixed_nonce', array( $this, 'get_mark_fixed_nonce' ) );
	}

	/**
	 * Creates and returns the nonce.
	 *
	 * @since 1.0.0
	 */
	public function get_nonce() {
		return wp_create_nonce( self::NONCE_KEY );
	}

	/**
	 * Check if the request is valid.
	 *
	 * @since 1.8.0
	 */
	private function check_request_validity() {
		// Verify the nonce before continuing.
		$valid_request = $this->verify_request( filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );

		if ( is_wp_error( $valid_request ) ) {
			wp_send_json_error( $valid_request, 403 );
		}
	}

	/**
	 * Configures the runner based on the current request.
	 *
	 * @since 1.8.0
	 *
	 * @param AJAX_Runner $runner The runner to configure.
	 * @return array The configuration used.
	 */
	private function configure_runner( $runner ) {
		$checks               = filter_input( INPUT_POST, 'checks', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		$checks               = is_null( $checks ) ? array() : $checks;
		$plugin               = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$include_experimental = 1 === filter_input( INPUT_POST, 'include-experimental', FILTER_VALIDATE_INT );

		$runner->set_experimental_flag( $include_experimental );
		$runner->set_check_slugs( $checks );
		$runner->set_plugin( $plugin );

		return array(
			'checks' => $checks,
			'plugin' => $plugin,
		);
	}

	/**
	 * Retrieves the AJAX Runner instance.
	 *
	 * @since 1.8.0
	 *
	 * @return AJAX_Runner|WP_Error The runner instance or WP_Error on failure.
	 */
	private function get_ajax_runner() {
		$runner = Plugin_Request_Utility::get_runner();

		if ( is_null( $runner ) ) {
			$runner = new AJAX_Runner();
		}

		if ( ! ( $runner instanceof AJAX_Runner ) ) {
			return new WP_Error( 'invalid-runner', __( 'AJAX Runner was not initialized correctly.', 'wp-verifier' ) );
		}

		return $runner;
	}

	/**
	 * Handles the AJAX request to setup the runtime environment if needed.
	 *
	 * @since 1.0.0
	 */
	public function set_up_environment() {
		$this->check_request_validity();

		$runner = $this->get_ajax_runner();

		if ( is_wp_error( $runner ) ) {
			wp_send_json_error( $runner, 500 );
		}

		try {
			$config = $this->configure_runner( $runner );

			$checks_to_run = $runner->get_checks_to_run();
		} catch ( Exception $error ) {
			wp_send_json_error(
				new WP_Error( 'invalid-request', $error->getMessage() ),
				400
			);
		}

		$message = __( 'No runtime checks, runtime environment was not setup.', 'wp-verifier' );

		if ( $this->has_runtime_check( $checks_to_run ) ) {
			$runtime = new Runtime_Environment_Setup();
			$runtime->set_up();
			$message = __( 'Runtime environment setup successful.', 'wp-verifier' );
		}

		wp_send_json_success(
			array(
				'message' => $message,
				'plugin'  => $config['plugin'],
				'checks'  => $config['checks'],
			)
		);
	}

	/**
	 * Handles the AJAX request to cleanup the runtime environment.
	 *
	 * @since 1.0.0
	 */
	public function clean_up_environment() {
		$this->check_request_validity();

		// Test if the runtime environment is prepared (and thus needs cleanup).
		$runtime = new Runtime_Environment_Setup();
		if ( $runtime->is_set_up() ) {
			$runtime->clean_up();
			$message = __( 'Runtime environment cleanup successful.', 'wp-verifier' );
		} else {
			$message = __( 'Runtime environment was not prepared, cleanup was not run.', 'wp-verifier' );
		}

		wp_send_json_success(
			array(
				'message' => $message,
			)
		);
	}

	/**
	 * Handles the AJAX request that returns the checks to run.
	 *
	 * @since 1.0.0
	 */
	public function get_checks_to_run() {
		$this->check_request_validity();

		$categories = filter_input( INPUT_POST, 'categories', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		$categories = is_null( $categories ) ? array() : $categories;

		$runner = $this->get_ajax_runner();

		if ( is_wp_error( $runner ) ) {
			wp_send_json_error( $runner, 403 );
		}

		try {
			$this->configure_runner( $runner );
			$runner->set_categories( $categories );

			$plugin_basename = $runner->get_plugin_basename();
			$checks_to_run   = $runner->get_checks_to_run();
		} catch ( Exception $error ) {
			wp_send_json_error(
				new WP_Error( 'invalid-checks', $error->getMessage() ),
				403
			);
		}

		wp_send_json_success(
			array(
				'plugin' => $plugin_basename,
				'checks' => array_keys( $checks_to_run ),
			)
		);
	}

	/**
	 * Run checks.
	 *
	 * @since 1.0.0
	 */
	public function run_checks() {
		$this->check_request_validity();

		$runner = $this->get_ajax_runner();

		if ( is_wp_error( $runner ) ) {
			wp_send_json_error( $runner, 500 );
		}

		$types = filter_input( INPUT_POST, 'types', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		$types = is_null( $types ) ? array() : $types;

		try {
			$config = $this->configure_runner( $runner );
			
			// Apply ignored_paths from JSON for Advanced Verification
			$this->apply_ignored_paths_filter( $config['plugin'] );
			
			$results = $runner->run();
		} catch ( Exception $error ) {
			wp_send_json_error(
				new WP_Error( 'invalid-request', $error->getMessage() ),
				400
			);
		}

		$response_data = $this->prepare_results_response( $results, $types );

		wp_send_json_success( $response_data );
	}

	/**
	 * Prepare the results response based on requested types.
	 *
	 * @since 1.8.0
	 *
	 * @param object $results The check results object.
	 * @param array  $types   The types of results to include (error, warning).
	 * @return array The prepared response data.
	 */
	private function prepare_results_response( $results, array $types ) {
		$response = array(
			'message'  => __( 'Checks run successfully', 'wp-verifier' ),
			'errors'   => array(),
			'warnings' => array(),
		);

		$errors = array();
		$warnings = array();

		if ( in_array( 'error', $types, true ) ) {
			$errors = $results->get_errors();
			$response['errors'] = $errors;
		}

		if ( in_array( 'warning', $types, true ) ) {
			$warnings = $results->get_warnings();
			$response['warnings'] = $warnings;
		}

		// DO NOT calculate readiness here - it will be wrong for individual checks
		// Readiness is calculated once in save_results() after all checks complete

		// Check for rediscovered issues
		$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( $plugin ) {
			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Issue_Tracker' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Issue_Tracker.php';
			}
			$response['rediscovered'] = \WordPress\Plugin_Check\Utilities\Issue_Tracker::find_rediscovered( $plugin, $errors, $warnings );
			$response['completed'] = \WordPress\Plugin_Check\Utilities\Issue_Tracker::get_completed( $plugin );
		}

		// Save to history and add comparison data
		if ( $plugin ) {
			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Scan_History' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Scan_History.php';
			}

			$last_scan = \WordPress\Plugin_Check\Utilities\Scan_History::get_last_scan( $plugin );
			$comparison = \WordPress\Plugin_Check\Utilities\Scan_History::compare_scans( $errors, $warnings, $last_scan );
			$response['comparison'] = $comparison;

			\WordPress\Plugin_Check\Utilities\Scan_History::save_scan( $plugin, $errors, $warnings );
		}

		return $response;
	}

	/**
	 * Filter out ignored results based on ignore rules.
	 *
	 * @since 1.9.0
	 *
	 * @param array $results Array of results to filter.
	 * @return array Filtered results.
	 */
	private function filter_ignored_results( array $results ) {
		if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Ignore_Rules' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Ignore_Rules.php';
		}

		$filtered = array();
		foreach ( $results as $result ) {
			$file = isset( $result['file'] ) && ! empty( $result['file'] ) ? $result['file'] : '';
			$code = isset( $result['code'] ) && ! empty( $result['code'] ) ? $result['code'] : '';

			// Skip results with no file path
			if ( empty( $file ) ) {
				continue;
			}

			if ( ! \WordPress\Plugin_Check\Utilities\Ignore_Rules::should_ignore( $file, $code ) ) {
				$filtered[] = $result;
			}
		}

		return $filtered;
	}

	/**
	 * Group results by file, line, and column.
	 *
	 * @since 1.9.0
	 *
	 * @param array $results Array of results to group.
	 * @return array Grouped results.
	 */
	private function group_results_by_file( array $results ) {
		$grouped = array();
		foreach ( $results as $result ) {
			$file = isset( $result['file'] ) ? $result['file'] : '';
			$line = isset( $result['line'] ) ? $result['line'] : 0;
			$column = isset( $result['column'] ) ? $result['column'] : 0;
			
			if ( ! isset( $grouped[ $file ] ) ) {
				$grouped[ $file ] = array();
			}
			if ( ! isset( $grouped[ $file ][ $line ] ) ) {
				$grouped[ $file ][ $line ] = array();
			}
			if ( ! isset( $grouped[ $file ][ $line ][ $column ] ) ) {
				$grouped[ $file ][ $line ][ $column ] = array();
			}
			
			$grouped[ $file ][ $line ][ $column ][] = $result;
		}
		return $grouped;
	}

	/**
	 * Flatten grouped results to flat array.
	 *
	 * @since 1.9.0
	 *
	 * @param array $grouped Grouped results.
	 * @return array Flat results.
	 */
	private function flatten_results( array $grouped ) {
		$flat = array();
		foreach ( $grouped as $file => $lines ) {
			foreach ( $lines as $line => $columns ) {
				foreach ( $columns as $column => $issues ) {
					foreach ( $issues as $issue ) {
						$flat[] = array_merge( $issue, array(
							'file' => $file,
							'line' => $line,
							'column' => $column,
						) );
					}
				}
			}
		}
		return $flat;
	}


	/**
	 * Handles exporting Plugin Check results.
	 *
	 * @since 1.8.0
	 */
	public function export_results() {
		$this->check_request_validity();

		try {
			$format          = $this->determine_export_format();
			$results_payload = $this->extract_results_payload();
			$export_metadata = $this->prepare_export_metadata();
			$payload         = $this->build_export_payload( $results_payload, $format, $export_metadata );
		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}

		wp_send_json_success( $payload );
	}

	/**
	 * Determines the requested export format.
	 *
	 * @since 1.8.0
	 *
	 * @return string Export format slug.
	 */
	private function determine_export_format() {
		$format = filter_input( INPUT_POST, 'format', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( empty( $format ) ) {
			return Results_Exporter::FORMAT_JSON;
		}

		return strtolower( $format );
	}

	/**
	 * Extracts errors and warnings payload from the request.
	 *
	 * @since 1.8.0
	 *
	 * @return array{
	 *     errors: array,
	 *     warnings: array,
	 * }
	 *
	 * @throws InvalidArgumentException When the payload is missing or malformed.
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
	 * Prepares metadata used for export filenames and headers.
	 *
	 * @since 1.8.0
	 *
	 * @return array Metadata values.
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
	 * Builds the export payload using the Results Exporter.
	 *
	 * @since 1.8.0
	 *
	 * @param array  $results_payload Payload containing errors and warnings.
	 * @param string $format          Export format slug.
	 * @param array  $metadata        Export metadata.
	 * @return array Export payload.
	 *
	 * @throws InvalidArgumentException If the payload cannot be generated.
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
	 * Verify the request.
	 *
	 * @since 1.0.0
	 *
	 * @param string $nonce The request nonce passed.
	 * @return bool|WP_Error True if the nonce is valid. WP_Error if invalid.
	 */
	private function verify_request( $nonce ) {
		if ( ! wp_verify_nonce( $nonce, self::NONCE_KEY ) ) {
			return new WP_Error( 'invalid-nonce', __( 'Invalid nonce', 'wp-verifier' ) );
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return new WP_Error( 'invalid-permissions', __( 'Invalid user permissions, you are not allowed to perform this request.', 'wp-verifier' ) );
		}

		return true;
	}

	/**
	 * Check for a Runtime_Check in a list of checks.
	 *
	 * @since 1.0.0
	 *
	 * @param array $checks An array of Check instances.
	 * @return bool True if a Runtime_Check exists in the array, false if not.
	 */
	private function has_runtime_check( array $checks ) {
		foreach ( $checks as $check ) {
			if ( $check instanceof Runtime_Check ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Handles saving Plugin Check results to file.
	 *
	 * @since 1.0.0
	 */
	public function save_results() {
		$this->check_request_validity();

		try {
			$results_payload = $this->extract_results_payload();
			
			$export_metadata = $this->prepare_export_metadata();
			
			$plugin_slug = $export_metadata['slug'];
			$plugin_folder = strpos( $plugin_slug, '/' ) !== false ? dirname( $plugin_slug ) : $plugin_slug;
			$verifier_dir = WP_CONTENT_DIR . '/verifier-results/' . $plugin_folder;
			
			if ( ! file_exists( $verifier_dir ) ) {
				wp_mkdir_p( $verifier_dir );
			}
			
			$file_path = $verifier_dir . '/results.json';
			
			// Load existing JSON to preserve ignored_paths
			$existing_ignored_paths = $this->load_existing_ignored_paths( $file_path );
			
			// Convert grouped format to flat format
			$errors_flat = array();
			foreach ( $results_payload['errors'] as $file => $lines ) {
				// Skip empty file names
				if ( empty( $file ) ) {
					continue;
				}
				foreach ( $lines as $line => $columns ) {
					foreach ( $columns as $column => $issues ) {
						foreach ( $issues as $issue ) {
							// Skip issues with empty messages
							if ( empty( $issue['message'] ) && empty( $issue['code'] ) ) {
								continue;
							}
							$errors_flat[] = array(
								'file' => $file,
								'line' => (int) $line,
								'column' => (int) $column,
								'message' => isset( $issue['message'] ) ? $issue['message'] : '',
								'code' => isset( $issue['code'] ) ? $issue['code'] : '',
								'link' => isset( $issue['link'] ) ? $issue['link'] : null,
								'docs' => isset( $issue['docs'] ) ? $issue['docs'] : '',
								'severity' => isset( $issue['severity'] ) ? $issue['severity'] : 5,
							);
						}
					}
				}
			}
			
			$warnings_flat = array();
			foreach ( $results_payload['warnings'] as $file => $lines ) {
				// Skip empty file names
				if ( empty( $file ) ) {
					continue;
				}
				foreach ( $lines as $line => $columns ) {
					foreach ( $columns as $column => $issues ) {
						foreach ( $issues as $issue ) {
							// Skip issues with empty messages
							if ( empty( $issue['message'] ) && empty( $issue['code'] ) ) {
								continue;
							}
							$warnings_flat[] = array(
								'file' => $file,
								'line' => (int) $line,
								'column' => (int) $column,
								'message' => isset( $issue['message'] ) ? $issue['message'] : '',
								'code' => isset( $issue['code'] ) ? $issue['code'] : '',
								'link' => isset( $issue['link'] ) ? $issue['link'] : null,
								'docs' => isset( $issue['docs'] ) ? $issue['docs'] : '',
								'severity' => isset( $issue['severity'] ) ? $issue['severity'] : 5,
							);
						}
					}
				}
			}
			
			// Calculate readiness score
			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Readiness_Score' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Readiness_Score.php';
			}
			$readiness = \WordPress\Plugin_Check\Utilities\Readiness_Score::calculate( $errors_flat, $warnings_flat );
			
			// Group by file in simple format
			$results_by_file = array();
			foreach ( $errors_flat as $error ) {
				$file = $error['file'];
				// Skip if file is empty or message/code are both empty
				if ( empty( $file ) || ( empty( $error['message'] ) && empty( $error['code'] ) ) ) {
					continue;
				}
				if ( ! isset( $results_by_file[ $file ] ) ) {
					$results_by_file[ $file ] = array();
				}
				// Generate unique issue ID
				$issue_id = 'E-' . substr( md5( basename( $file ) . '-' . $error['line'] ), 0, 8 );
				$results_by_file[ $file ][] = array(
					'issue_id' => $issue_id,
					'message' => $error['message'],
					'code' => $error['code'],
					'link' => $error['link'],
					'docs' => $error['docs'],
					'severity' => $error['severity'],
					'type' => 'ERROR',
					'line' => $error['line'],
					'column' => $error['column'],
					'ignored' => false,
					'ignored_by' => null,
					'resolved' => false,
					'resolved_by' => null,
				);
			}
			foreach ( $warnings_flat as $warning ) {
				$file = $warning['file'];
				// Skip if file is empty or message/code are both empty
				if ( empty( $file ) || ( empty( $warning['message'] ) && empty( $warning['code'] ) ) ) {
					continue;
				}
				if ( ! isset( $results_by_file[ $file ] ) ) {
					$results_by_file[ $file ] = array();
				}
				// Generate unique issue ID
				$issue_id = 'W-' . substr( md5( basename( $file ) . '-' . $warning['line'] ), 0, 8 );
				$results_by_file[ $file ][] = array(
					'issue_id' => $issue_id,
					'message' => $warning['message'],
					'code' => $warning['code'],
					'link' => $warning['link'],
					'docs' => $warning['docs'],
					'severity' => $warning['severity'],
					'type' => 'WARNING',
					'line' => $warning['line'],
					'column' => $warning['column'],
					'ignored' => false,
					'ignored_by' => null,
					'resolved' => false,
					'resolved_by' => null,
				);
			}
			
			// Build final structure with merged ignored_paths
			$final_results = array(
				'generated_at' => $export_metadata['timestamp_human'],
				'plugin' => $export_metadata['plugin'],
				'readiness' => $readiness,
				'ignored_paths' => $existing_ignored_paths,
				'results' => empty( $results_by_file ) ? new \stdClass() : $results_by_file,
			);
			
			$result = file_put_contents( $file_path, wp_json_encode( $final_results, JSON_PRETTY_PRINT ) );
			
			error_log( 'JSON saved to: ' . $file_path . ' - Readiness: ' . $readiness['overall'] . ' (Errors: ' . $readiness['errors'] . ', Warnings: ' . $readiness['warnings'] . ')' );
			
			if ( false === $result ) {
				throw new InvalidArgumentException( __( 'Failed to save file.', 'wp-verifier' ) );
			}
			
			wp_send_json_success( array(
				'message' => sprintf( __( 'Results saved to %s', 'wp-verifier' ), $file_path ),
				'path' => $file_path
			) );
			
		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Load existing ignored_paths from JSON file.
	 *
	 * @since 1.9.0
	 *
	 * @param string $file_path Path to JSON file.
	 * @return array Existing ignored paths or empty array.
	 */
	private function load_existing_ignored_paths( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return array();
		}

		$existing_data = json_decode( file_get_contents( $file_path ), true );
		if ( ! $existing_data || ! isset( $existing_data['ignored_paths'] ) ) {
			return array();
		}

		return is_array( $existing_data['ignored_paths'] ) ? $existing_data['ignored_paths'] : array();
	}

	/**
	 * Apply ignored_paths from JSON to directory filter for Advanced Verification.
	 *
	 * @since 1.9.0
	 *
	 * @param string $plugin_slug Plugin slug.
	 */
	private function apply_ignored_paths_filter( $plugin_slug ) {
		$plugin_folder = strpos( $plugin_slug, '/' ) !== false ? dirname( $plugin_slug ) : $plugin_slug;
		$json_file = WP_CONTENT_DIR . '/verifier-results/' . $plugin_folder . '/results.json';
		
		if ( ! file_exists( $json_file ) ) {
			return;
		}
		
		$ignored_paths = $this->load_existing_ignored_paths( $json_file );
		if ( empty( $ignored_paths ) ) {
			return;
		}
		
		// Extract just the path values
		$paths_to_ignore = array();
		foreach ( $ignored_paths as $item ) {
			if ( isset( $item['path'] ) ) {
				$paths_to_ignore[] = $item['path'];
			}
		}
		
		if ( empty( $paths_to_ignore ) ) {
			return;
		}
		
		// Apply filter to add ignored paths to directory exclusion list
		add_filter(
			'wp_plugin_check_ignore_directories',
			static function ( $dirs ) use ( $paths_to_ignore ) {
				return array_unique( array_merge( $dirs, $paths_to_ignore ) );
			}
		);
	}

	/**
	 * Handles loading saved Plugin Check results.
	 *
	 * @since 1.0.0
	 */
	public function load_results() {
		$this->check_request_validity();

		try {
			$plugin_slug = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin_slug ) ) {
				throw new InvalidArgumentException( __( 'Plugin slug is required.', 'wp-verifier' ) );
			}
			
			$plugin_folder = strpos( $plugin_slug, '/' ) !== false ? dirname( $plugin_slug ) : $plugin_slug;
			$verifier_dir = WP_CONTENT_DIR . '/verifier-results/' . $plugin_folder;
			$json_file = $verifier_dir . '/results.json';
			
			if ( ! file_exists( $json_file ) ) {
				throw new InvalidArgumentException( __( 'No saved results found.', 'wp-verifier' ) );
			}
			
			wp_send_json_success( array(
				'path' => $json_file,
				'modified' => filemtime( $json_file )
			) );
			
		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles listing all saved Plugin Check results.
	 *
	 * @since 1.0.0
	 */
	public function list_saved_results() {
		$this->check_request_validity();

		try {
			$verifier_base_dir = WP_CONTENT_DIR . '/verifier-results';
			
			if ( ! file_exists( $verifier_base_dir ) ) {
				wp_send_json_success( array( 'results' => array() ) );
				return;
			}
			
			$results = array();
			$dirs = glob( $verifier_base_dir . '/*', GLOB_ONLYDIR );
			
			foreach ( $dirs as $dir ) {
				$json_file = $dir . '/results.json';
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
			
		} catch ( Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles adding an ignore rule.
	 *
	 * @since 1.0.0
	 */
	public function add_ignore_rule() {
		$this->check_request_validity();

		try {
			$plugin_slug = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$file = isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';
			$line = filter_input( INPUT_POST, 'line', FILTER_VALIDATE_INT );
			$code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
			$ignored_by = isset( $_POST['ignored_by'] ) ? sanitize_text_field( wp_unslash( $_POST['ignored_by'] ) ) : wp_get_current_user()->user_login;
			
			if ( empty( $plugin_slug ) || empty( $file ) || empty( $code ) ) {
				throw new InvalidArgumentException( __( 'Plugin, file, line, and code are required.', 'wp-verifier' ) );
			}
			
			// Load JSON file
			$plugin_folder = strpos( $plugin_slug, '/' ) !== false ? dirname( $plugin_slug ) : $plugin_slug;
			$json_file = WP_CONTENT_DIR . '/verifier-results/' . $plugin_folder . '/results.json';
			
			if ( ! file_exists( $json_file ) ) {
				throw new InvalidArgumentException( __( 'Results file not found.', 'wp-verifier' ) );
			}
			
			$data = json_decode( file_get_contents( $json_file ), true );
			if ( ! $data || ! isset( $data['results'] ) ) {
				throw new InvalidArgumentException( __( 'Invalid results file.', 'wp-verifier' ) );
			}
			
			// Find and update the issue
			$updated = false;
			if ( isset( $data['results'][ $file ] ) ) {
				foreach ( $data['results'][ $file ] as &$issue ) {
					if ( $issue['line'] == $line && $issue['code'] === $code ) {
						$issue['ignored'] = true;
						$issue['ignored_by'] = $ignored_by;
						$updated = true;
					}
				}
			}
			
			if ( ! $updated ) {
				throw new InvalidArgumentException( __( 'Issue not found in results.', 'wp-verifier' ) );
			}
			
			// Save updated JSON
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
	 * Handles adding a directory ignore rule.
	 *
	 * @since 1.0.0
	 */
	public function add_ignore_directory() {
		$this->check_request_validity();

		try {
			$plugin_slug = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$directory = isset( $_POST['directory'] ) ? sanitize_text_field( wp_unslash( $_POST['directory'] ) ) : '';
			
			if ( empty( $plugin_slug ) || empty( $directory ) ) {
				throw new InvalidArgumentException( __( 'Plugin and directory are required.', 'wp-verifier' ) );
			}
			
			$ignore_rules = get_option( 'wpv_ignore_rules', array() );
			
			if ( ! isset( $ignore_rules[ $plugin_slug ] ) ) {
				$ignore_rules[ $plugin_slug ] = array();
			}
			
			$ignore_rules[ $plugin_slug ][] = array(
				'type' => 'directory',
				'path' => $directory,
				'added' => current_time( 'mysql' ),
			);
			
			update_option( 'wpv_ignore_rules', $ignore_rules );
			
			wp_send_json_success( array(
				'message' => __( 'Directory ignore rule added successfully.', 'wp-verifier' ),
			) );
			
		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles getting scan history for a plugin.
	 *
	 * @since 1.9.0
	 */
	public function get_scan_history() {
		$this->check_request_validity();

		try {
			$plugin_slug = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin_slug ) ) {
				throw new InvalidArgumentException( __( 'Plugin slug is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Scan_History' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Scan_History.php';
			}

			$history = \WordPress\Plugin_Check\Utilities\Scan_History::get_history( $plugin_slug );
			$stats = \WordPress\Plugin_Check\Utilities\Scan_History::get_statistics( $plugin_slug );

			wp_send_json_success( array(
				'history' => $history,
				'stats'   => $stats,
			) );

		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles clearing scan history for a plugin.
	 *
	 * @since 1.9.0
	 */
	public function clear_scan_history() {
		$this->check_request_validity();

		try {
			$plugin_slug = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin_slug ) ) {
				throw new InvalidArgumentException( __( 'Plugin slug is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Scan_History' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Scan_History.php';
			}

			\WordPress\Plugin_Check\Utilities\Scan_History::clear_history( $plugin_slug );

			wp_send_json_success( array(
				'message' => __( 'Scan history cleared successfully.', 'wp-verifier' ),
			) );

		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles generating a detailed report.
	 *
	 * @since 1.9.0
	 */
	public function generate_report() {
		$this->check_request_validity();

		try {
			$format = filter_input( INPUT_POST, 'format', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$format = $format ? $format : 'html';

			$results_payload = $this->extract_results_payload();
			$export_metadata = $this->prepare_export_metadata();

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Report_Generator' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Report_Generator.php';
			}

			$comparison = null;
			if ( isset( $_POST['comparison'] ) ) {
				$comparison = json_decode( wp_unslash( $_POST['comparison'] ), true );
			}

			if ( 'html' === $format ) {
				$content = \WordPress\Plugin_Check\Utilities\Report_Generator::generate_html_report(
					$results_payload['errors'],
					$results_payload['warnings'],
					$export_metadata,
					$comparison
				);
				$mime_type = 'text/html';
				$extension = 'html';
			} elseif ( 'pdf' === $format ) {
				$content = \WordPress\Plugin_Check\Utilities\Report_Generator::generate_pdf_report(
					$results_payload['errors'],
					$results_payload['warnings'],
					$export_metadata,
					$comparison
				);
				$mime_type = 'application/pdf';
				$extension = 'pdf';
			} else {
				$content = \WordPress\Plugin_Check\Utilities\Report_Generator::generate_text_report(
					$results_payload['errors'],
					$results_payload['warnings'],
					$export_metadata
				);
				$mime_type = 'text/plain';
				$extension = 'txt';
			}

			$filename = sanitize_file_name( $export_metadata['plugin'] ) . '-report-' . $export_metadata['timestamp'] . '.' . $extension;

			wp_send_json_success( array(
				'content'  => $content,
				'filename' => $filename,
				'mimeType' => $mime_type,
			) );

		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles checking domain availability.
	 *
	 * @since 1.9.0
	 */
	public function check_domains() {
		$this->check_request_validity();

		try {
			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			if ( empty( $name ) ) {
				throw new InvalidArgumentException( __( 'Name is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Domain_Checker' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Domain_Checker.php';
			}

			$domain_name = \WordPress\Plugin_Check\Utilities\Domain_Checker::format_domain_name( $name );
			$cached = \WordPress\Plugin_Check\Utilities\Domain_Checker::get_cached_results( $domain_name );

			if ( $cached ) {
				wp_send_json_success( array(
					'domains' => $cached,
					'cached'  => true,
				) );
				return;
			}

			$results = \WordPress\Plugin_Check\Utilities\Domain_Checker::check_domains( $domain_name );
			\WordPress\Plugin_Check\Utilities\Domain_Checker::cache_results( $domain_name, $results );

			wp_send_json_success( array(
				'domains' => $results,
				'cached'  => false,
			) );

		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles saving a name evaluation.
	 *
	 * @since 1.9.0
	 */
	public function save_name() {
		$this->check_request_validity();

		try {
			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$evaluation = isset( $_POST['evaluation'] ) ? json_decode( wp_unslash( $_POST['evaluation'] ), true ) : array();
			$note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

			if ( empty( $name ) ) {
				throw new InvalidArgumentException( __( 'Name is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Saved_Names' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Saved_Names.php';
			}

			\WordPress\Plugin_Check\Utilities\Saved_Names::save_name( $name, $evaluation, $note );

			wp_send_json_success( array(
				'message' => __( 'Name saved successfully.', 'wp-verifier' ),
			) );

		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles getting saved names.
	 *
	 * @since 1.9.0
	 */
	public function get_saved_names() {
		$this->check_request_validity();

		try {
			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Saved_Names' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Saved_Names.php';
			}

			$saved = \WordPress\Plugin_Check\Utilities\Saved_Names::get_all();

			wp_send_json_success( array(
				'names' => $saved,
			) );

		} catch ( Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles checking name conflicts.
	 *
	 * @since 1.9.0
	 */
	public function check_conflicts() {
		$this->check_request_validity();

		try {
			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			if ( empty( $name ) ) {
				throw new InvalidArgumentException( __( 'Name is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Name_Conflict_Checker' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Name_Conflict_Checker.php';
			}

			$results = \WordPress\Plugin_Check\Utilities\Name_Conflict_Checker::check_wordpress_org( $name );

			wp_send_json_success( $results );

		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles SEO analysis.
	 *
	 * @since 1.9.0
	 */
	public function analyze_seo() {
		$this->check_request_validity();

		try {
			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			if ( empty( $name ) ) {
				throw new InvalidArgumentException( __( 'Name is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\SEO_Analyzer' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/SEO_Analyzer.php';
			}

			$results = \WordPress\Plugin_Check\Utilities\SEO_Analyzer::analyze( $name );
			$total_score = $results['length']['score'] + $results['keywords']['score'] + $results['readability']['score'];
			$results['score'] = $total_score;

			wp_send_json_success( $results );

		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles trademark checking.
	 *
	 * @since 1.9.0
	 */
	public function check_trademarks() {
		$this->check_request_validity();

		try {
			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			if ( empty( $name ) ) {
				throw new InvalidArgumentException( __( 'Name is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Trademark_Checker' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Trademark_Checker.php';
			}

			$results = \WordPress\Plugin_Check\Utilities\Trademark_Checker::check( $name );
			$guidelines = \WordPress\Plugin_Check\Utilities\Trademark_Checker::get_guidelines();
			$results['guidelines'] = $guidelines;

			wp_send_json_success( $results );

		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles starting file monitoring.
	 *
	 * @since 1.9.0
	 */
	public function start_monitoring() {
		$this->check_request_validity();

		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin ) ) {
				throw new InvalidArgumentException( __( 'Plugin is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\File_Monitor' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/File_Monitor.php';
			}

			\WordPress\Plugin_Check\Utilities\File_Monitor::set_monitored_plugin( $plugin );

			wp_send_json_success( array(
				'message' => __( 'Monitoring started successfully.', 'wp-verifier' ),
			) );

		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles stopping file monitoring.
	 *
	 * @since 1.9.0
	 */
	public function stop_monitoring() {
		$this->check_request_validity();

		try {
			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\File_Monitor' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/File_Monitor.php';
			}

			\WordPress\Plugin_Check\Utilities\File_Monitor::stop_monitoring();

			wp_send_json_success( array(
				'message' => __( 'Monitoring stopped.', 'wp-verifier' ),
			) );

		} catch ( Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles checking for file changes.
	 *
	 * @since 1.9.0
	 */
	public function check_file_changes() {
		$this->check_request_validity();

		try {
			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\File_Monitor' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/File_Monitor.php';
			}

			$changes = \WordPress\Plugin_Check\Utilities\File_Monitor::check_changes();

			if ( $changes ) {
				wp_send_json_success( $changes );
			} else {
				wp_send_json_success( array( 'changed' => false ) );
			}

		} catch ( Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles getting monitor log.
	 *
	 * @since 1.9.0
	 */
	public function get_monitor_log() {
		$this->check_request_validity();

		try {
			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\File_Monitor' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/File_Monitor.php';
			}

			$log = \WordPress\Plugin_Check\Utilities\File_Monitor::get_log();

			wp_send_json_success( array( 'log' => $log ) );

		} catch ( Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles marking issue as complete.
	 *
	 * @since 1.9.0
	 */
	public function mark_complete() {
		$this->check_request_validity();

		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$file = isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';
			$line = filter_input( INPUT_POST, 'line', FILTER_VALIDATE_INT );
			$code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';

			if ( empty( $plugin ) || empty( $file ) || empty( $code ) ) {
				throw new InvalidArgumentException( __( 'Missing required parameters.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Issue_Tracker' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Issue_Tracker.php';
			}

			\WordPress\Plugin_Check\Utilities\Issue_Tracker::mark_complete( $plugin, $file, $line, $code );

			wp_send_json_success( array(
				'message' => __( 'Issue marked as complete.', 'wp-verifier' ),
			) );

		} catch ( Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Handles deleting saved results.
	 *
	 * @since 1.9.0
	 */
	public function delete_results() {
		$this->check_request_validity();

		try {
			$plugin_slug = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin_slug ) ) {
				throw new InvalidArgumentException( __( 'Plugin slug is required.', 'wp-verifier' ) );
			}

			$plugin_folder = strpos( $plugin_slug, '/' ) !== false ? dirname( $plugin_slug ) : $plugin_slug;
			$verifier_dir = WP_CONTENT_DIR . '/verifier-results/' . $plugin_folder;

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
	 * Recursively delete a directory.
	 *
	 * @since 1.9.0
	 *
	 * @param string $dir Directory path.
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
	 * Basic check - no filtering, raw results.
	 *
	 * @since 1.9.0
	 */
	public function basic_check() {
		$this->check_request_validity();

		$runner = $this->get_ajax_runner();
		if ( is_wp_error( $runner ) ) {
			wp_send_json_error( $runner, 500 );
		}

		$types = filter_input( INPUT_POST, 'types', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		$types = is_null( $types ) ? array() : $types;

		try {
			$this->configure_runner( $runner );
			$results = $runner->run();
		} catch ( Exception $error ) {
			wp_send_json_error(
				new WP_Error( 'invalid-request', $error->getMessage() ),
				400
			);
		}

		$response = array(
			'message'  => __( 'Checks run successfully', 'wp-verifier' ),
			'errors'   => array(),
			'warnings' => array(),
		);

		if ( in_array( 'error', $types, true ) ) {
			$response['errors'] = $results->get_errors();
		}

		if ( in_array( 'warning', $types, true ) ) {
			$response['warnings'] = $results->get_warnings();
		}

		wp_send_json_success( $response );
	}

	/**
	 * Save user meta.
	 *
	 * @since 1.9.0
	 */
	public function save_user_meta() {
		$meta_key = isset( $_POST['meta_key'] ) ? sanitize_text_field( wp_unslash( $_POST['meta_key'] ) ) : '';
		$meta_value = isset( $_POST['meta_value'] ) ? sanitize_text_field( wp_unslash( $_POST['meta_value'] ) ) : '';
		
		if ( $meta_key && 'wpv_last_selected_plugin' === $meta_key ) {
			update_user_meta( get_current_user_id(), $meta_key, $meta_value );
			wp_send_json_success();
		}
		
		wp_send_json_error();
	}

	/**
	 * Detect vendor folders in plugin.
	 *
	 * @since 1.9.0
	 */
	public function detect_vendors() {
		$this->check_request_validity();

		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin ) ) {
				throw new InvalidArgumentException( __( 'Plugin is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Admin\\Vendor_Detector' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Vendor_Detector.php';
			}

			$vendors = Vendor_Detector::detect_vendors( $plugin );

			wp_send_json_success( array(
				'vendors' => $vendors,
			) );

		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Save ignored paths to JSON.
	 *
	 * @since 1.9.0
	 */
	public function save_ignored_paths() {
		$this->check_request_validity();

		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$paths = isset( $_POST['paths'] ) ? json_decode( wp_unslash( $_POST['paths'] ), true ) : array();

			if ( empty( $plugin ) || empty( $paths ) ) {
				throw new InvalidArgumentException( __( 'Plugin and paths are required.', 'wp-verifier' ) );
			}

			$plugin_folder = strpos( $plugin, '/' ) !== false ? dirname( $plugin ) : $plugin;
			$json_file = WP_CONTENT_DIR . '/verifier-results/' . $plugin_folder . '/results.json';

			$data = array();
			if ( file_exists( $json_file ) ) {
				$data = json_decode( file_get_contents( $json_file ), true );
				if ( ! $data ) {
					$data = array();
				}
			}

			$ignored_paths = array();
			foreach ( $paths as $path ) {
				$ignored_paths[] = array(
					'path' => $path,
					'reason' => 'vendor',
					'added_by' => wp_get_current_user()->user_login,
					'added_at' => current_time( 'mysql' ),
				);
			}

			$data['ignored_paths'] = $ignored_paths;

			if ( ! file_exists( dirname( $json_file ) ) ) {
				wp_mkdir_p( dirname( $json_file ) );
			}

			file_put_contents( $json_file, wp_json_encode( $data, JSON_PRETTY_PRINT ) );

			wp_send_json_success( array(
				'message' => __( 'Ignored paths saved successfully.', 'wp-verifier' ),
			) );

		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Detect vendor/libraries/library folders in plugin.
	 *
	 * @since 1.9.0
	 */
	public function detect_folders() {
		$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $plugin ) {
			wp_send_json_error();
		}

		if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Vendor_Patterns' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Vendor_Patterns.php';
		}

		$plugin_dir = WP_PLUGIN_DIR . '/' . dirname( $plugin );
		$folders = array();
		$patterns = \WordPress\Plugin_Check\Utilities\Vendor_Patterns::get_patterns();
		$check_paths = array();

		// Build check paths from centralized patterns
		foreach ( $patterns as $pattern ) {
			$check_paths[] = $pattern;
			$check_paths[] = 'includes/' . $pattern;
		}

		foreach ( $check_paths as $path ) {
			if ( is_dir( $plugin_dir . '/' . $path ) ) {
				$folders[] = $path;
			}
		}

		wp_send_json_success( array( 'folders' => $folders ) );
	}

	/**
	 * Validate plugin structure.
	 *
	 * @since 1.9.0
	 */
	public function validate_structure() {
		$this->check_request_validity();

		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin ) ) {
				throw new InvalidArgumentException( __( 'Plugin is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Structure_Validator' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Structure_Validator.php';
			}

			$results = \WordPress\Plugin_Check\Utilities\Structure_Validator::validate( $plugin );

			wp_send_json_success( array( 'validation' => $results ) );

		} catch ( InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Get nonce for mark_fixed action.
	 *
	 * @since 1.9.0
	 */
	public function get_mark_fixed_nonce() {
		wp_send_json_success( array(
			'nonce' => wp_create_nonce( 'wpv_mark_fixed' )
		) );
	}
}

