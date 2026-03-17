<?php
/**
 * Verification AJAX Handler
 *
 * Handles AJAX requests related to verification and scan execution.
 * Extracted from Admin_AJAX.php as part of consolidation effort.
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Admin;

use Exception;
use InvalidArgumentException;
use WordPress\Plugin_Check\Checker\AJAX_Runner;
use WordPress\Plugin_Check\Checker\Runtime_Check;
use WordPress\Plugin_Check\Checker\Runtime_Environment_Setup;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;
use WordPress\Plugin_Check\Utilities\Path_Builder;
use WP_Error;

/**
 * Handles verification and scan-related AJAX requests
 */
class Verification_AJAX_Handler {

	/**
	 * Nonce key for verification actions
	 */
	const NONCE_KEY = 'plugin-check-run-checks';

	/**
	 * Register AJAX hooks for verification actions
	 */
	public function add_hooks() {
		add_action( 'wp_ajax_wpv_run_checks', array( $this, 'run_checks' ) );
		add_action( 'wp_ajax_plugin_check_clean_up_environment', array( $this, 'clean_up_environment' ) );
		add_action( 'wp_ajax_plugin_check_set_up_environment', array( $this, 'set_up_environment' ) );
		add_action( 'wp_ajax_plugin_check_get_checks_to_run', array( $this, 'get_checks_to_run' ) );
		add_action( 'wp_ajax_plugin_check_run_checks', array( $this, 'run_checks' ) );
		add_action( 'wp_ajax_plugin_check_basic_check', array( $this, 'basic_check' ) );
		add_action( 'wp_ajax_plugin_check_validate_structure', array( $this, 'validate_structure' ) );
	}

	/**
	 * Set up runtime environment if needed
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
	 * Clean up runtime environment
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
	 * Get checks to run
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
	 * Run checks
	 */
	public function run_checks() {
		$this->check_request_validity();

		$runner = $this->get_ajax_runner();

		if ( is_wp_error( $runner ) ) {
			wp_send_json_error( $runner, 500 );
		}

		$types = filter_input( INPUT_POST, 'types', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		$types = is_null( $types ) ? array( 'error', 'warning' ) : $types;

		// Get limit_results option from check_options JSON
		$check_options_json = filter_input( INPUT_POST, 'check_options', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$check_options = $check_options_json ? json_decode( $check_options_json, true ) : array();
		$limit_results = isset( $check_options['limit_results'] ) ? (bool) $check_options['limit_results'] : false;

		// Debug logging
		error_log( 'WPV Debug: Starting verification with types: ' . print_r( $types, true ) );
		error_log( 'WPV Debug: Limit results enabled: ' . ( $limit_results ? 'YES' : 'NO' ) );

		try {
			$config = $this->configure_runner( $runner );
			
			// Apply ignored_paths from JSON for Advanced Verification
			$this->apply_ignored_paths_filter( $config['plugin'] );
			
			error_log( 'WPV Debug: Running checks for plugin: ' . $config['plugin'] );
			$results = $runner->run();
			
			// Debug results
			$errors = $results->get_errors();
			$warnings = $results->get_warnings();
			error_log( 'WPV Debug: Found ' . $this->count_issues( $errors ) . ' errors and ' . $this->count_issues( $warnings ) . ' warnings' );
			
			// Apply issue limiting if requested
			if ( $limit_results ) {
				error_log( 'WPV Debug: Applying 20 issue limit' );
				$limited_results = $this->limit_issues_to_count( $errors, $warnings, 20 );
				$errors = $limited_results['errors'];
				$warnings = $limited_results['warnings'];
				error_log( 'WPV Debug: After limiting - ' . $this->count_issues( $errors ) . ' errors and ' . $this->count_issues( $warnings ) . ' warnings' );
			}
			
			// Filter WordPress.org specific rules if not preparing for WordPress.org
			$wporg_prep = filter_input( INPUT_POST, 'wporg_preparation', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( '0' === $wporg_prep ) {
				if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\WPOrg_Rules' ) ) {
					require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/WPOrg_Rules.php';
				}
				$filtered_errors = \WordPress\Plugin_Check\Utilities\WPOrg_Rules::filter_results( $errors );
				$filtered_warnings = \WordPress\Plugin_Check\Utilities\WPOrg_Rules::filter_results( $warnings );
				$errors = $filtered_errors;
				$warnings = $filtered_warnings;
				
				// Debug filtered results
				error_log( 'WPV Debug: After WPOrg filtering - ' . $this->count_issues( $errors ) . ' errors and ' . $this->count_issues( $warnings ) . ' warnings' );
			}
		} catch ( Exception $error ) {
			error_log( 'WPV Debug: Exception during verification: ' . $error->getMessage() );
			wp_send_json_error(
				new WP_Error( 'invalid-request', $error->getMessage() ),
				400
			);
		}

		$response_data = $this->prepare_results_response_with_arrays( $errors, $warnings, $types, $limit_results );
		error_log( 'WPV Debug: Response data keys: ' . implode( ', ', array_keys( $response_data ) ) );
		
		// Save results to JSON file
		$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( $plugin ) {
			$this->save_results_to_json( $plugin, $errors, $warnings );
		}

		wp_send_json_success( $response_data );
	}

	/**
	 * Basic check - no filtering, raw results
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
	 * Validate plugin structure
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
	 * Configure the runner based on the current request
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
	 * Get AJAX Runner instance
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
	 * Check for a Runtime_Check in a list of checks
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
	 * Filter WordPress.org specific results
	 */
	private function filter_wporg_results( $results ) {
		$errors = $results->get_errors();
		$warnings = $results->get_warnings();
		
		$filtered_errors = \WordPress\Plugin_Check\Utilities\WPOrg_Rules::filter_results( $errors );
		$filtered_warnings = \WordPress\Plugin_Check\Utilities\WPOrg_Rules::filter_results( $warnings );
		
		// Create new result object with filtered data
		$filtered_results = new \WordPress\Plugin_Check\Checker\Check_Result( $results->plugin() );
		
		// Re-add filtered errors and warnings
		foreach ( $filtered_errors as $file => $lines ) {
			foreach ( $lines as $line => $columns ) {
				foreach ( $columns as $column => $issues ) {
					foreach ( $issues as $issue ) {
						$filtered_results->add_message(
							true,
							$issue['message'],
							array(
								'code' => $issue['code'],
								'file' => $file,
								'line' => $line,
								'column' => $column,
								'severity' => $issue['severity'] ?? 5,
								'link' => $issue['link'] ?? null,
								'docs' => $issue['docs'] ?? '',
							)
						);
					}
				}
			}
		}
		
		foreach ( $filtered_warnings as $file => $lines ) {
			foreach ( $lines as $line => $columns ) {
				foreach ( $columns as $column => $issues ) {
					foreach ( $issues as $issue ) {
						$filtered_results->add_message(
							false,
							$issue['message'],
							array(
								'code' => $issue['code'],
								'file' => $file,
								'line' => $line,
								'column' => $column,
								'severity' => $issue['severity'] ?? 5,
								'link' => $issue['link'] ?? null,
								'docs' => $issue['docs'] ?? '',
							)
						);
					}
				}
			}
		}
		
		return $filtered_results;
	}

	/**
	 * Prepare the results response based on requested types
	 */
	private function prepare_results_response( $results, array $types ) {
		$response = array(
			'message'  => __( 'Checks run successfully', 'wp-verifier' ),
			'errors'   => array(),
			'warnings' => array(),
			'html_output' => '',
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

		// Generate HTML output
		$response['html_output'] = $this->generate_results_html( $errors, $warnings );
		
		// Generate export controls
		$response['export_controls'] = $this->generate_export_controls( $errors, $warnings );

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
	 * Generate HTML output for verification results
	 */
	private function generate_results_html( $errors, $warnings ) {
		$total_errors = $this->count_issues( $errors );
		$total_warnings = $this->count_issues( $warnings );
		$total_issues = $total_errors + $total_warnings;

		if ( $total_issues === 0 ) {
			return '<div class="notice notice-success"><p><strong>' . __( 'Great! No issues found.', 'wp-verifier' ) . '</strong></p></div>';
		}

		ob_start();
		?>
		<div class="plugin-check-results">
			<div class="results-summary" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 20px;">
				<h3><?php esc_html_e( 'Verification Results Summary', 'wp-verifier' ); ?></h3>
				<div style="display: flex; gap: 20px; margin: 15px 0;">
					<div style="flex: 1; text-align: center; padding: 15px; background: #dc3232; color: white; border-radius: 4px;">
						<div style="font-size: 24px; font-weight: bold;"><?php echo esc_html( $total_errors ); ?></div>
						<div><?php esc_html_e( 'Errors', 'wp-verifier' ); ?></div>
					</div>
					<div style="flex: 1; text-align: center; padding: 15px; background: #ffb900; color: white; border-radius: 4px;">
						<div style="font-size: 24px; font-weight: bold;"><?php echo esc_html( $total_warnings ); ?></div>
						<div><?php esc_html_e( 'Warnings', 'wp-verifier' ); ?></div>
					</div>
					<div style="flex: 1; text-align: center; padding: 15px; background: #666; color: white; border-radius: 4px;">
						<div style="font-size: 24px; font-weight: bold;"><?php echo esc_html( $total_issues ); ?></div>
						<div><?php esc_html_e( 'Total Issues', 'wp-verifier' ); ?></div>
					</div>
				</div>
			</div>

			<?php if ( ! empty( $errors ) ) : ?>
				<div class="errors-section" style="margin-bottom: 30px;">
					<h3 style="color: #dc3232;"><?php esc_html_e( 'Errors', 'wp-verifier' ); ?> (<?php echo esc_html( $total_errors ); ?>)</h3>
					<?php echo $this->render_issues_table( $errors, 'error' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $warnings ) ) : ?>
				<div class="warnings-section" style="margin-bottom: 30px;">
					<h3 style="color: #ffb900;"><?php esc_html_e( 'Warnings', 'wp-verifier' ); ?> (<?php echo esc_html( $total_warnings ); ?>)</h3>
					<?php echo $this->render_issues_table( $warnings, 'warning' ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render issues table
	 */
	private function render_issues_table( $issues, $type ) {
		if ( empty( $issues ) ) {
			return '';
		}

		ob_start();
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width: 40%;"><?php esc_html_e( 'File', 'wp-verifier' ); ?></th>
					<th style="width: 10%;"><?php esc_html_e( 'Line', 'wp-verifier' ); ?></th>
					<th style="width: 15%;"><?php esc_html_e( 'Code', 'wp-verifier' ); ?></th>
					<th style="width: 35%;"><?php esc_html_e( 'Message', 'wp-verifier' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $issues as $file => $lines ) : ?>
					<?php foreach ( $lines as $line => $columns ) : ?>
						<?php foreach ( $columns as $column => $issue_list ) : ?>
							<?php foreach ( $issue_list as $issue ) : ?>
								<tr>
									<td><code><?php echo esc_html( $file ); ?></code></td>
									<td><?php echo esc_html( $line ); ?></td>
									<td><code><?php echo esc_html( $issue['code'] ?? '' ); ?></code></td>
									<td><?php echo esc_html( $issue['message'] ?? '' ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endforeach; ?>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generate export controls HTML
	 */
	private function generate_export_controls( $errors, $warnings ) {
		$total_issues = $this->count_issues( $errors ) + $this->count_issues( $warnings );
		
		if ( $total_issues === 0 ) {
			return '';
		}
		
		ob_start();
		?>
		<div class="export-controls" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
			<h4><?php esc_html_e( 'Export Results', 'wp-verifier' ); ?></h4>
			<p><?php esc_html_e( 'Download or save verification results in different formats:', 'wp-verifier' ); ?></p>
			<div style="display: flex; gap: 10px; flex-wrap: wrap;">
				<button type="button" class="button button-secondary plugin-check__export-button" data-export-format="csv" data-export-action="download">
					<?php esc_html_e( 'Download CSV', 'wp-verifier' ); ?>
				</button>
				<button type="button" class="button button-secondary plugin-check__export-button" data-export-format="json" data-export-action="download">
					<?php esc_html_e( 'Download JSON', 'wp-verifier' ); ?>
				</button>
				<button type="button" class="button button-secondary plugin-check__export-button" data-export-format="markdown" data-export-action="download">
					<?php esc_html_e( 'Download Markdown', 'wp-verifier' ); ?>
				</button>
				<button type="button" class="button button-primary plugin-check__save-button" data-export-format="json" data-export-action="save">
					<?php esc_html_e( 'Save Results', 'wp-verifier' ); ?>
				</button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Save verification results to JSON file
	 */
	private function save_results_to_json( $plugin_slug, $errors, $warnings ) {
		$json_file = Path_Builder::get_results_file_path( $plugin_slug );
		$plugin_path = Path_Builder::get_plugin_directory_path( $plugin_slug );
		
		error_log( 'WPV Debug: Saving results to: ' . $json_file );
		error_log( 'WPV Debug: Plugin path: ' . $plugin_path );
		error_log( 'WPV Debug: Errors count: ' . $this->count_issues( $errors ) );
		error_log( 'WPV Debug: Warnings count: ' . $this->count_issues( $warnings ) );
		
		// Convert to original flat format per file
		$results_by_file = array();
		
		// Process errors
		foreach ( $errors as $file => $lines ) {
			// Convert absolute path to relative path from plugin directory
			$relative_file = $this->get_relative_path( $file, $plugin_path );
			
			foreach ( $lines as $line => $columns ) {
				foreach ( $columns as $column => $issues ) {
					foreach ( $issues as $issue ) {
						if ( ! isset( $results_by_file[ $relative_file ] ) ) {
							$results_by_file[ $relative_file ] = array();
						}
						$results_by_file[ $relative_file ][] = array(
							'issue_id' => 'E-' . substr( md5( $relative_file . $line . $issue['code'] ), 0, 8 ),
							'message' => $issue['message'] ?? '',
							'code' => $issue['code'] ?? '',
							'type' => 'ERROR',
							'line' => (int) $line,
							'column' => (int) $column,
							'ignored' => false,
							'resolved' => false
						);
					}
				}
			}
		}
		
		// Process warnings
		foreach ( $warnings as $file => $lines ) {
			// Convert absolute path to relative path from plugin directory
			$relative_file = $this->get_relative_path( $file, $plugin_path );
			
			foreach ( $lines as $line => $columns ) {
				foreach ( $columns as $column => $issues ) {
					foreach ( $issues as $issue ) {
						if ( ! isset( $results_by_file[ $relative_file ] ) ) {
							$results_by_file[ $relative_file ] = array();
						}
						$results_by_file[ $relative_file ][] = array(
							'issue_id' => 'W-' . substr( md5( $relative_file . $line . $issue['code'] ), 0, 8 ),
							'message' => $issue['message'] ?? '',
							'code' => $issue['code'] ?? '',
							'type' => 'WARNING',
							'line' => (int) $line,
							'column' => (int) $column,
							'ignored' => false,
							'resolved' => false
						);
					}
				}
			}
		}
		
		$total_errors = $this->count_issues( $errors );
		$total_warnings = $this->count_issues( $warnings );
		
		$results_data = array(
			'generated_at' => gmdate( 'Y-m-d H:i:s' ),
			'plugin' => $plugin_slug,
			'readiness' => array(
				'overall' => min( 100, max( 0, 100 - ( $total_errors * 2 + $total_warnings ) ) ),
				'errors' => $total_errors,
				'warnings' => $total_warnings
			),
			'configuration' => array(
				'wporg_preparation' => true,
				'skipped_rules' => array()
			),
			'ignored_paths' => array(),
			'results' => $results_by_file
		);
		
		if ( file_put_contents( $json_file, wp_json_encode( $results_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) ) {
			error_log( 'WPV Debug: Results saved successfully' );
		} else {
			error_log( 'WPV Debug: Failed to save results file' );
		}
	}

	/**
	 * Apply ignored_paths from JSON to directory filter for Advanced Verification
	 */
	private function apply_ignored_paths_filter( $plugin_slug ) {
		$results_file = Path_Builder::get_results_file_path( $plugin_slug );
		$config_file = Path_Builder::get_plugin_directory_path( $plugin_slug ) . '/.wpv-config.json';
		
		error_log( 'WPV DEBUG: apply_ignored_paths_filter called for plugin: ' . $plugin_slug );
		error_log( 'WPV DEBUG: Checking for ignored paths in results file: ' . $results_file );
		error_log( 'WPV DEBUG: Checking for ignored paths in config file: ' . $config_file );
		
		// First try to load from config file (preferred location)
		$ignored_paths = array();
		if ( file_exists( $config_file ) ) {
			error_log( 'WPV DEBUG: Config file exists, loading ignored paths from config' );
			$ignored_paths = $this->load_ignored_paths_from_config( $config_file );
		} elseif ( file_exists( $results_file ) ) {
			error_log( 'WPV DEBUG: Config file not found, trying results file' );
			$ignored_paths = $this->load_existing_ignored_paths( $results_file );
		} else {
			error_log( 'WPV DEBUG: Neither config nor results file found, no ignored paths to apply' );
			return;
		}
		
		if ( empty( $ignored_paths ) ) {
			error_log( 'WPV DEBUG: No ignored paths found in either file' );
			return;
		}
		
		error_log( 'WPV DEBUG: Found ignored_paths: ' . print_r( $ignored_paths, true ) );
		
		$paths_to_ignore = array();
		foreach ( $ignored_paths as $item ) {
			if ( isset( $item['path'] ) ) {
				$paths_to_ignore[] = $item['path'];
			} elseif ( is_string( $item ) ) {
				// Handle simple string format
				$paths_to_ignore[] = $item;
			}
		}
		
		if ( empty( $paths_to_ignore ) ) {
			error_log( 'WPV DEBUG: No valid paths found in ignored_paths array' );
			return;
		}
		
		error_log( 'WPV DEBUG: Applying filter for paths: ' . implode( ', ', $paths_to_ignore ) );
		
		add_filter(
			'wp_plugin_check_ignore_directories',
			static function ( $dirs ) use ( $paths_to_ignore ) {
				$merged_dirs = array_unique( array_merge( $dirs, $paths_to_ignore ) );
				return $merged_dirs;
			},
			10
		);
	}

	/**
	 * Load existing ignored_paths from JSON file
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
	 * Load ignored_paths from config file
	 */
	private function load_ignored_paths_from_config( $config_file ) {
		if ( ! file_exists( $config_file ) ) {
			return array();
		}

		$config_content = file_get_contents( $config_file );
		if ( false === $config_content ) {
			error_log( 'WPV DEBUG: Failed to read config file: ' . $config_file );
			return array();
		}

		$config_data = json_decode( $config_content, true );
		if ( null === $config_data ) {
			error_log( 'WPV DEBUG: Failed to decode config JSON from: ' . $config_file );
			return array();
		}

		if ( ! isset( $config_data['ignored_paths'] ) || ! is_array( $config_data['ignored_paths'] ) ) {
			error_log( 'WPV DEBUG: No ignored_paths found in config file: ' . $config_file );
			return array();
		}

		error_log( 'WPV DEBUG: Successfully loaded ignored_paths from config: ' . print_r( $config_data['ignored_paths'], true ) );
		return $config_data['ignored_paths'];
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
			return new WP_Error( 'invalid-nonce', __( 'Invalid nonce', 'wp-verifier' ) );
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return new WP_Error( 'invalid-permissions', __( 'Invalid user permissions, you are not allowed to perform this request.', 'wp-verifier' ) );
		}

		return true;
	}

	/**
	 * Get relative path from absolute path
	 */
	private function get_relative_path( $absolute_path, $plugin_path ) {
		// Normalize paths to use forward slashes
		$absolute_path = str_replace( '\\', '/', $absolute_path );
		$plugin_path = str_replace( '\\', '/', $plugin_path );
		
		// Remove plugin path from absolute path
		if ( strpos( $absolute_path, $plugin_path . '/' ) === 0 ) {
			return substr( $absolute_path, strlen( $plugin_path ) + 1 );
		}
		
		// If path doesn't start with plugin path, return basename
		return basename( $absolute_path );
	}

	/**
	 * Limit issues to a specific count, prioritizing errors over warnings
	 *
	 * @param array $errors Error results array
	 * @param array $warnings Warning results array
	 * @param int $max_issues Maximum number of issues to return
	 * @return array Limited results with 'errors' and 'warnings' keys
	 */
	private function limit_issues_to_count( $errors, $warnings, $max_issues ) {
		$limited_errors = array();
		$limited_warnings = array();
		$issue_count = 0;
		
		// First, add errors (higher priority)
		foreach ( $errors as $file => $lines ) {
			if ( $issue_count >= $max_issues ) {
				break;
			}
			
			foreach ( $lines as $line => $columns ) {
				if ( $issue_count >= $max_issues ) {
					break;
				}
				
				foreach ( $columns as $column => $issue_list ) {
					if ( $issue_count >= $max_issues ) {
						break;
					}
					
					foreach ( $issue_list as $issue ) {
						if ( $issue_count >= $max_issues ) {
							break;
						}
						
						if ( ! isset( $limited_errors[ $file ] ) ) {
							$limited_errors[ $file ] = array();
						}
						if ( ! isset( $limited_errors[ $file ][ $line ] ) ) {
							$limited_errors[ $file ][ $line ] = array();
						}
						if ( ! isset( $limited_errors[ $file ][ $line ][ $column ] ) ) {
							$limited_errors[ $file ][ $line ][ $column ] = array();
						}
						
						$limited_errors[ $file ][ $line ][ $column ][] = $issue;
						$issue_count++;
					}
				}
			}
		}
		
		// Then add warnings if we haven't reached the limit
		foreach ( $warnings as $file => $lines ) {
			if ( $issue_count >= $max_issues ) {
				break;
			}
			
			foreach ( $lines as $line => $columns ) {
				if ( $issue_count >= $max_issues ) {
					break;
				}
				
				foreach ( $columns as $column => $issue_list ) {
					if ( $issue_count >= $max_issues ) {
						break;
					}
					
					foreach ( $issue_list as $issue ) {
						if ( $issue_count >= $max_issues ) {
							break;
						}
						
						if ( ! isset( $limited_warnings[ $file ] ) ) {
							$limited_warnings[ $file ] = array();
						}
						if ( ! isset( $limited_warnings[ $file ][ $line ] ) ) {
							$limited_warnings[ $file ][ $line ] = array();
						}
						if ( ! isset( $limited_warnings[ $file ][ $line ][ $column ] ) ) {
							$limited_warnings[ $file ][ $line ][ $column ] = array();
						}
						
						$limited_warnings[ $file ][ $line ][ $column ][] = $issue;
						$issue_count++;
					}
				}
			}
		}
		
		error_log( 'WPV Debug: Limited to ' . $issue_count . ' issues out of ' . $max_issues . ' requested' );
		
		return array(
			'errors' => $limited_errors,
			'warnings' => $limited_warnings
		);
	}

	/**
	 * Prepare the results response with array-based errors and warnings
	 */
	private function prepare_results_response_with_arrays( $errors, $warnings, array $types, $is_limited = false ) {
		$response = array(
			'message'  => __( 'Checks run successfully', 'wp-verifier' ),
			'errors'   => array(),
			'warnings' => array(),
			'html_output' => '',
			'limited' => $is_limited
		);

		if ( in_array( 'error', $types, true ) ) {
			$response['errors'] = $errors;
		}

		if ( in_array( 'warning', $types, true ) ) {
			$response['warnings'] = $warnings;
		}

		// Generate HTML output with limitation notice
		$response['html_output'] = $this->generate_results_html_with_limit_notice( $errors, $warnings, $is_limited );
		
		// Generate export controls
		$response['export_controls'] = $this->generate_export_controls( $errors, $warnings );

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
	 * Generate HTML output for verification results with limitation notice
	 */
	private function generate_results_html_with_limit_notice( $errors, $warnings, $is_limited = false ) {
		$total_errors = $this->count_issues( $errors );
		$total_warnings = $this->count_issues( $warnings );
		$total_issues = $total_errors + $total_warnings;

		if ( $total_issues === 0 ) {
			return '<div class="notice notice-success"><p><strong>' . __( 'Great! No issues found.', 'wp-verifier' ) . '</strong></p></div>';
		}

		ob_start();
		?>
		<div class="plugin-check-results">
			<?php if ( $is_limited ) : ?>
				<div class="notice notice-info" style="margin-bottom: 20px;">
					<p><strong><?php esc_html_e( 'Limited Results:', 'wp-verifier' ); ?></strong> <?php esc_html_e( 'Showing first 20 issues only. Uncheck "Limit to 20 issues" to see all results.', 'wp-verifier' ); ?></p>
				</div>
			<?php endif; ?>
			
			<div class="results-summary" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 20px;">
				<h3><?php esc_html_e( 'Verification Results Summary', 'wp-verifier' ); ?></h3>
				<div style="display: flex; gap: 20px; margin: 15px 0;">
					<div style="flex: 1; text-align: center; padding: 15px; background: #dc3232; color: white; border-radius: 4px;">
						<div style="font-size: 24px; font-weight: bold;"><?php echo esc_html( $total_errors ); ?></div>
						<div><?php esc_html_e( 'Errors', 'wp-verifier' ); ?><?php echo $is_limited ? ' (limited)' : ''; ?></div>
					</div>
					<div style="flex: 1; text-align: center; padding: 15px; background: #ffb900; color: white; border-radius: 4px;">
						<div style="font-size: 24px; font-weight: bold;"><?php echo esc_html( $total_warnings ); ?></div>
						<div><?php esc_html_e( 'Warnings', 'wp-verifier' ); ?><?php echo $is_limited ? ' (limited)' : ''; ?></div>
					</div>
					<div style="flex: 1; text-align: center; padding: 15px; background: #666; color: white; border-radius: 4px;">
						<div style="font-size: 24px; font-weight: bold;"><?php echo esc_html( $total_issues ); ?></div>
						<div><?php esc_html_e( 'Total Issues', 'wp-verifier' ); ?><?php echo $is_limited ? ' (limited)' : ''; ?></div>
					</div>
				</div>
			</div>

			<?php if ( ! empty( $errors ) ) : ?>
				<div class="errors-section" style="margin-bottom: 30px;">
					<h3 style="color: #dc3232;"><?php esc_html_e( 'Errors', 'wp-verifier' ); ?> (<?php echo esc_html( $total_errors ); ?>)</h3>
					<?php echo $this->render_issues_table( $errors, 'error' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $warnings ) ) : ?>
				<div class="warnings-section" style="margin-bottom: 30px;">
					<h3 style="color: #ffb900;"><?php esc_html_e( 'Warnings', 'wp-verifier' ); ?> (<?php echo esc_html( $total_warnings ); ?>)</h3>
					<?php echo $this->render_issues_table( $warnings, 'warning' ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Count total issues in results array
	 */
	private function count_issues( $issues ) {
		$count = 0;
		foreach ( $issues as $file => $lines ) {
			foreach ( $lines as $line => $columns ) {
				foreach ( $columns as $column => $issue_list ) {
					$count += count( $issue_list );
				}
			}
		}
		return $count;
	}
}