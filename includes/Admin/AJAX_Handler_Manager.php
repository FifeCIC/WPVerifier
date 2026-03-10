<?php
/**
 * AJAX Handler Manager
 *
 * Coordinates all specialized AJAX handlers to replace the monolithic Admin_AJAX class.
 * Part of the consolidation effort to reduce code duplication.
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Admin;

/**
 * Manages all AJAX handlers for the plugin
 */
class AJAX_Handler_Manager {

	/**
	 * Array of handler instances
	 */
	private $handlers = array();

	/**
	 * Constructor - Initialize all handlers
	 */
	public function __construct() {
		$this->init_handlers();
	}

	/**
	 * Initialize all AJAX handlers
	 */
	private function init_handlers() {
		$this->handlers = array(
			'config'       => new Config_AJAX_Handler(),
			'hash'         => new Hash_AJAX_Handler(),
			'results'      => new Results_AJAX_Handler(),
			'verification' => new Verification_AJAX_Handler(),
		);
	}

	/**
	 * Register all AJAX hooks
	 */
	public function add_hooks() {
		foreach ( $this->handlers as $handler ) {
			$handler->add_hooks();
		}

		// Keep some legacy methods that don't fit cleanly into the new structure
		$this->add_legacy_hooks();
	}

	/**
	 * Add legacy hooks that haven't been moved to specialized handlers yet
	 */
	private function add_legacy_hooks() {
		// Plugin namer and monitoring functionality
		add_action( 'wp_ajax_plugin_check_domains', array( $this, 'check_domains' ) );
		add_action( 'wp_ajax_plugin_check_save_name', array( $this, 'save_name' ) );
		add_action( 'wp_ajax_plugin_check_get_saved_names', array( $this, 'get_saved_names' ) );
		add_action( 'wp_ajax_plugin_check_name_conflicts', array( $this, 'check_conflicts' ) );
		add_action( 'wp_ajax_plugin_check_analyze_seo', array( $this, 'analyze_seo' ) );
		add_action( 'wp_ajax_plugin_check_check_trademarks', array( $this, 'check_trademarks' ) );
		
		// File monitoring
		add_action( 'wp_ajax_plugin_check_start_monitoring', array( $this, 'start_monitoring' ) );
		add_action( 'wp_ajax_plugin_check_stop_monitoring', array( $this, 'stop_monitoring' ) );
		add_action( 'wp_ajax_plugin_check_file_changes', array( $this, 'check_file_changes' ) );
		add_action( 'wp_ajax_plugin_check_monitor_log', array( $this, 'get_monitor_log' ) );
		
		// Issue tracking
		add_action( 'wp_ajax_plugin_check_mark_complete', array( $this, 'mark_complete' ) );
		add_action( 'wp_ajax_plugin_check_add_ignore_rule', array( $this, 'add_ignore_rule' ) );
		add_action( 'wp_ajax_plugin_check_add_ignore_directory', array( $this, 'add_ignore_directory' ) );
		
		// Scan history and reporting
		add_action( 'wp_ajax_plugin_check_get_scan_history', array( $this, 'get_scan_history' ) );
		add_action( 'wp_ajax_plugin_check_clear_scan_history', array( $this, 'clear_scan_history' ) );
		add_action( 'wp_ajax_plugin_check_generate_report', array( $this, 'generate_report' ) );
		
		// Utility functions
		add_action( 'wp_ajax_save_user_meta', array( $this, 'save_user_meta' ) );
		add_action( 'wp_ajax_wpv_get_mark_fixed_nonce', array( $this, 'get_mark_fixed_nonce' ) );
	}

	/**
	 * Get a specific handler instance
	 *
	 * @param string $handler_name Handler name (config, hash, results, verification)
	 * @return object|null Handler instance or null if not found
	 */
	public function get_handler( $handler_name ) {
		return isset( $this->handlers[ $handler_name ] ) ? $this->handlers[ $handler_name ] : null;
	}

	/**
	 * Create and return the nonce (legacy method)
	 */
	public function get_nonce() {
		return wp_create_nonce( 'plugin-check-run-checks' );
	}
	// Legacy methods that will be moved to specialized handlers in future iterations
	// These are kept here temporarily to maintain compatibility

	/**
	 * Check domains (legacy method - will move to Plugin_Namer_AJAX_Handler)
	 */
	public function check_domains() {
		$this->check_request_validity();

		try {
			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			if ( empty( $name ) ) {
				throw new \InvalidArgumentException( __( 'Name is required.', 'wp-verifier' ) );
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

		} catch ( \InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Save name (legacy method)
	 */
	public function save_name() {
		$this->check_request_validity();

		try {
			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$evaluation = isset( $_POST['evaluation'] ) ? json_decode( wp_unslash( $_POST['evaluation'] ), true ) : array();
			$note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

			if ( empty( $name ) ) {
				throw new \InvalidArgumentException( __( 'Name is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Saved_Names' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Saved_Names.php';
			}

			\WordPress\Plugin_Check\Utilities\Saved_Names::save_name( $name, $evaluation, $note );

			wp_send_json_success( array(
				'message' => __( 'Name saved successfully.', 'wp-verifier' ),
			) );

		} catch ( \InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Get saved names (legacy method)
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

		} catch ( \Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Check conflicts (legacy method)
	 */
	public function check_conflicts() {
		$this->check_request_validity();

		try {
			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			if ( empty( $name ) ) {
				throw new \InvalidArgumentException( __( 'Name is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Name_Conflict_Checker' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Name_Conflict_Checker.php';
			}

			$results = \WordPress\Plugin_Check\Utilities\Name_Conflict_Checker::check_wordpress_org( $name );

			wp_send_json_success( $results );

		} catch ( \InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Analyze SEO (legacy method)
	 */
	public function analyze_seo() {
		$this->check_request_validity();

		try {
			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			if ( empty( $name ) ) {
				throw new \InvalidArgumentException( __( 'Name is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\SEO_Analyzer' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/SEO_Analyzer.php';
			}

			$results = \WordPress\Plugin_Check\Utilities\SEO_Analyzer::analyze( $name );
			$total_score = $results['length']['score'] + $results['keywords']['score'] + $results['readability']['score'];
			$results['score'] = $total_score;

			wp_send_json_success( $results );

		} catch ( \InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Check trademarks (legacy method)
	 */
	public function check_trademarks() {
		$this->check_request_validity();

		try {
			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			if ( empty( $name ) ) {
				throw new \InvalidArgumentException( __( 'Name is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Trademark_Checker' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Trademark_Checker.php';
			}

			$results = \WordPress\Plugin_Check\Utilities\Trademark_Checker::check( $name );
			$guidelines = \WordPress\Plugin_Check\Utilities\Trademark_Checker::get_guidelines();
			$results['guidelines'] = $guidelines;

			wp_send_json_success( $results );

		} catch ( \InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Start monitoring (legacy method)
	 */
	public function start_monitoring() {
		$this->check_request_validity();

		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin ) ) {
				throw new \InvalidArgumentException( __( 'Plugin is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\File_Monitor' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/File_Monitor.php';
			}

			\WordPress\Plugin_Check\Utilities\File_Monitor::set_monitored_plugin( $plugin );

			wp_send_json_success( array(
				'message' => __( 'Monitoring started successfully.', 'wp-verifier' ),
			) );

		} catch ( \InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Stop monitoring (legacy method)
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

		} catch ( \Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Check file changes (legacy method)
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

		} catch ( \Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Get monitor log (legacy method)
	 */
	public function get_monitor_log() {
		$this->check_request_validity();

		try {
			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\File_Monitor' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/File_Monitor.php';
			}

			$log = \WordPress\Plugin_Check\Utilities\File_Monitor::get_log();

			wp_send_json_success( array( 'log' => $log ) );

		} catch ( \Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Mark complete (legacy method)
	 */
	public function mark_complete() {
		$this->check_request_validity();

		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$file = isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';
			$line = filter_input( INPUT_POST, 'line', FILTER_VALIDATE_INT );
			$code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';

			if ( empty( $plugin ) || empty( $file ) || empty( $code ) ) {
				throw new \InvalidArgumentException( __( 'Missing required parameters.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Issue_Tracker' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Issue_Tracker.php';
			}

			\WordPress\Plugin_Check\Utilities\Issue_Tracker::mark_complete( $plugin, $file, $line, $code );

			wp_send_json_success( array(
				'message' => __( 'Issue marked as complete.', 'wp-verifier' ),
			) );

		} catch ( \Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Add ignore rule (legacy method)
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
				throw new \InvalidArgumentException( __( 'Plugin, file, line, and code are required.', 'wp-verifier' ) );
			}
			
			// Load JSON file
			$plugin_folder = strpos( $plugin_slug, '/' ) !== false ? dirname( $plugin_slug ) : $plugin_slug;
			$json_file = WP_PLUGIN_DIR . '/' . $plugin_folder . '/.wpv-results.json';
			
			if ( ! file_exists( $json_file ) ) {
				throw new \InvalidArgumentException( __( 'Results file not found.', 'wp-verifier' ) );
			}
			
			$data = json_decode( file_get_contents( $json_file ), true );
			if ( ! $data || ! isset( $data['results'] ) ) {
				throw new \InvalidArgumentException( __( 'Invalid results file.', 'wp-verifier' ) );
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
				throw new \InvalidArgumentException( __( 'Issue not found in results.', 'wp-verifier' ) );
			}
			
			// Save updated JSON
			file_put_contents( $json_file, wp_json_encode( $data, JSON_PRETTY_PRINT ) );
			
			wp_send_json_success( array(
				'message' => __( 'Issue marked as ignored.', 'wp-verifier' ),
			) );
			
		} catch ( \InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Add ignore directory (legacy method)
	 */
	public function add_ignore_directory() {
		$this->check_request_validity();

		try {
			$plugin_slug = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$directory = isset( $_POST['directory'] ) ? sanitize_text_field( wp_unslash( $_POST['directory'] ) ) : '';
			
			if ( empty( $plugin_slug ) || empty( $directory ) ) {
				throw new \InvalidArgumentException( __( 'Plugin and directory are required.', 'wp-verifier' ) );
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
			
		} catch ( \InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Get scan history (legacy method)
	 */
	public function get_scan_history() {
		$this->check_request_validity();

		try {
			$plugin_slug = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin_slug ) ) {
				throw new \InvalidArgumentException( __( 'Plugin slug is required.', 'wp-verifier' ) );
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

		} catch ( \InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Clear scan history (legacy method)
	 */
	public function clear_scan_history() {
		$this->check_request_validity();

		try {
			$plugin_slug = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin_slug ) ) {
				throw new \InvalidArgumentException( __( 'Plugin slug is required.', 'wp-verifier' ) );
			}

			if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Scan_History' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Scan_History.php';
			}

			\WordPress\Plugin_Check\Utilities\Scan_History::clear_history( $plugin_slug );

			wp_send_json_success( array(
				'message' => __( 'Scan history cleared successfully.', 'wp-verifier' ),
			) );

		} catch ( \InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Generate report (legacy method)
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

		} catch ( \InvalidArgumentException $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Save user meta (legacy method)
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
	 * Get mark fixed nonce (legacy method)
	 */
	public function get_mark_fixed_nonce() {
		wp_send_json_success( array(
			'nonce' => wp_create_nonce( 'wpv_mark_fixed' )
		) );
	}

	/**
	 * Extract results payload from request (helper method)
	 */
	private function extract_results_payload() {
		$raw_results = isset( $_POST['results'] ) ? wp_unslash( $_POST['results'] ) : '';
		if ( '' === $raw_results ) {
			throw new \InvalidArgumentException( __( 'Invalid or empty results payload.', 'wp-verifier' ) );
		}

		$decoded_results = json_decode( $raw_results, true );
		if ( null === $decoded_results || JSON_ERROR_NONE !== json_last_error() ) {
			throw new \InvalidArgumentException( __( 'Malformed results payload.', 'wp-verifier' ) );
		}

		return array(
			'errors'   => isset( $decoded_results['errors'] ) && is_array( $decoded_results['errors'] ) ? $decoded_results['errors'] : array(),
			'warnings' => isset( $decoded_results['warnings'] ) && is_array( $decoded_results['warnings'] ) ? $decoded_results['warnings'] : array(),
		);
	}

	/**
	 * Prepare export metadata (helper method)
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
	 * Check if the request is valid (helper method)
	 */
	private function check_request_validity() {
		$valid_request = $this->verify_request( filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );

		if ( is_wp_error( $valid_request ) ) {
			wp_send_json_error( $valid_request, 403 );
		}
	}

	/**
	 * Verify the request nonce and permissions (helper method)
	 */
	private function verify_request( $nonce ) {
		if ( ! wp_verify_nonce( $nonce, 'plugin-check-run-checks' ) ) {
			return new \WP_Error( 'invalid-nonce', __( 'Invalid nonce', 'wp-verifier' ) );
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return new \WP_Error( 'invalid-permissions', __( 'Invalid user permissions, you are not allowed to perform this request.', 'wp-verifier' ) );
		}

		return true;
	}
}