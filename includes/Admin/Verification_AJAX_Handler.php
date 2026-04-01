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
		add_action( 'wp_ajax_wpv_mark_resolved', array( $this, 'mark_issue_as_fixed' ) );
		add_action( 'wp_ajax_wpv_mark_ignored', array( $this, 'mark_issue_as_ignored' ) );
		add_action( 'wp_ajax_wpv_mark_unignored', array( $this, 'mark_issue_as_unignored' ) );
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

		$start_time = microtime( true );
		$log_file   = WP_CONTENT_DIR . '/wpv-scan-timing.log';
		$this->timing_log( $log_file, $start_time, 'START run_checks' );

		// Allow up to 5 minutes for large scans
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 );
		}

		$runner = $this->get_ajax_runner();

		if ( is_wp_error( $runner ) ) {
			wp_send_json_error( $runner, 500 );
		}

		$types = filter_input( INPUT_POST, 'types', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		$types = is_null( $types ) ? array( 'error', 'warning' ) : $types;

		// Get limit_results option from check_options JSON
		$check_options_json = filter_input( INPUT_POST, 'check_options', FILTER_UNSAFE_RAW );
		// Handle double-escaped JSON and HTML entities
		if ( $check_options_json ) {
			$check_options_json = stripslashes( $check_options_json );
			$check_options_json = html_entity_decode( $check_options_json, ENT_QUOTES, 'UTF-8' );
		}
		$check_options = $check_options_json ? json_decode( $check_options_json, true ) : array();

		// Determine issue limit from radio selection (new) or legacy checkboxes
		$max_issues = 0;
		if ( isset( $check_options['max_issues'] ) && (int) $check_options['max_issues'] > 0 ) {
			$max_issues = (int) $check_options['max_issues'];
		} elseif ( ! empty( $check_options['limit_results'] ) ) {
			$max_issues = 20;
		} elseif ( ! empty( $check_options['limit_500'] ) ) {
			$max_issues = 500;
		}

		$this->timing_log( $log_file, $start_time, 'Options parsed, max_issues=' . $max_issues );

		try {
			$config = $this->configure_runner( $runner );
			$this->timing_log( $log_file, $start_time, 'Runner configured for: ' . $config['plugin'] );
			
			// Apply ignored_paths from JSON for Advanced Verification
			$this->apply_ignored_paths_filter( $config['plugin'] );
			$this->timing_log( $log_file, $start_time, 'Ignored paths applied, starting $runner->run()' );
			
			$results = $runner->run();
			$this->timing_log( $log_file, $start_time, '$runner->run() COMPLETED' );
			
			$errors = $results->get_errors();
			$warnings = $results->get_warnings();
			$this->timing_log( $log_file, $start_time, 'get_errors/get_warnings done — errors=' . $this->count_issues( $errors ) . ' warnings=' . $this->count_issues( $warnings ) );
			
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
				$this->timing_log( $log_file, $start_time, 'WPOrg filter applied' );
			}
		} catch ( Exception $error ) {
			$this->timing_log( $log_file, $start_time, 'EXCEPTION: ' . $error->getMessage() );
			wp_send_json_error(
				new WP_Error( 'invalid-request', $error->getMessage() ),
				400
			);
		}

		// Apply issue limit if selected
		$total_before_limit = $this->count_issues( $errors ) + $this->count_issues( $warnings );
		$was_limited = false;
		if ( $max_issues > 0 && $total_before_limit > $max_issues ) {
			$limited  = $this->limit_issues_to_count( $errors, $warnings, $max_issues );
			$errors   = $limited['errors'];
			$warnings = $limited['warnings'];
			$was_limited = true;
			$this->timing_log( $log_file, $start_time, 'Limited from ' . $total_before_limit . ' to ' . $max_issues );
		}

		$elapsed_seconds = round( microtime( true ) - $start_time, 2 );

		$this->timing_log( $log_file, $start_time, 'Building response HTML' );

		$response_data = $this->prepare_results_response_with_arrays(
			$errors,
			$warnings,
			$types,
			$was_limited,
			$elapsed_seconds,
			$was_limited ? $total_before_limit : 0,
			$max_issues
		);
		$this->timing_log( $log_file, $start_time, 'Response prepared' );
		
		// Save results to JSON file
		$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( $plugin ) {
			$this->save_results_to_json( $plugin, $errors, $warnings );
			$this->timing_log( $log_file, $start_time, 'Results saved to JSON' );
		}

		$this->timing_log( $log_file, $start_time, 'DONE — sending response (' . $elapsed_seconds . 's total)' );
		wp_send_json_success( $response_data );
	}

	/**
	 * Write a timestamped line to the timing log.
	 */
	private function timing_log( $log_file, $start_time, $message ) {
		$elapsed = round( microtime( true ) - $start_time, 2 );
		$line    = '[' . gmdate( 'Y-m-d H:i:s' ) . '] +' . str_pad( $elapsed, 7, ' ', STR_PAD_LEFT ) . 's  ' . $message . "\n";
		file_put_contents( $log_file, $line, FILE_APPEND );
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
	private function generate_results_html( $errors, $warnings, $elapsed_seconds = 0 ) {
		$total_errors = $this->count_issues( $errors );
		$total_warnings = $this->count_issues( $warnings );
		$total_issues = $total_errors + $total_warnings;

		if ( $total_issues === 0 ) {
			$time_note = $elapsed_seconds > 0 ? ' ' . sprintf( __( 'Completed in %s seconds.', 'wp-verifier' ), $elapsed_seconds ) : '';
			return '<div class="notice notice-success"><p><strong>' . __( 'Great! No issues found.', 'wp-verifier' ) . '</strong>' . esc_html( $time_note ) . '</p></div>';
		}

		ob_start();
		?>
		<div class="plugin-check-results">
			<div class="results-summary" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 20px;">
				<h3>
					<?php esc_html_e( 'Verification Results Summary', 'wp-verifier' ); ?>
					<?php if ( $elapsed_seconds > 0 ) : ?>
						<span style="font-size: 13px; font-weight: normal; color: #646970; margin-left: 10px;">
							<?php
							if ( $elapsed_seconds >= 60 ) {
								$minutes = floor( $elapsed_seconds / 60 );
								$seconds = round( $elapsed_seconds - ( $minutes * 60 ), 1 );
								printf( esc_html__( 'Completed in %1$dm %2$ss', 'wp-verifier' ), $minutes, $seconds );
							} else {
								printf( esc_html__( 'Completed in %ss', 'wp-verifier' ), $elapsed_seconds );
							}
							?>
						</span>
					<?php endif; ?>
				</h3>
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

		// Convert to original flat format per file
		$results_by_file = array();
		
		$id_counter = 0;

		// Process errors
		foreach ( $errors as $file => $lines ) {
			$relative_file = $this->get_relative_path( $file, $plugin_path );
			foreach ( $lines as $line => $columns ) {
				foreach ( $columns as $column => $issues ) {
					foreach ( $issues as $issue ) {
						if ( ! isset( $results_by_file[ $relative_file ] ) ) {
							$results_by_file[ $relative_file ] = array();
						}
						$id_counter++;
						$results_by_file[ $relative_file ][] = array(
							'issue_id' => 'E-' . substr( md5( $relative_file . $line . $column . $issue['code'] . $id_counter ), 0, 8 ),
							'message'  => $issue['message'] ?? '',
							'code'     => $issue['code'] ?? '',
							'type'     => 'ERROR',
							'line'     => (int) $line,
							'column'   => (int) $column,
							'ignored'  => false,
						);
					}
				}
			}
		}

		// Process warnings
		foreach ( $warnings as $file => $lines ) {
			$relative_file = $this->get_relative_path( $file, $plugin_path );
			foreach ( $lines as $line => $columns ) {
				foreach ( $columns as $column => $issues ) {
					foreach ( $issues as $issue ) {
						if ( ! isset( $results_by_file[ $relative_file ] ) ) {
							$results_by_file[ $relative_file ] = array();
						}
						$id_counter++;
						$results_by_file[ $relative_file ][] = array(
							'issue_id' => 'W-' . substr( md5( $relative_file . $line . $column . $issue['code'] . $id_counter ), 0, 8 ),
							'message'  => $issue['message'] ?? '',
							'code'     => $issue['code'] ?? '',
							'type'     => 'WARNING',
							'line'     => (int) $line,
							'column'   => (int) $column,
							'ignored'  => false,
						);
					}
				}
			}
		}
		
		// Post-scan filter: remove results for files in ignored_files with a matching hash.
		// This catches what PHPCS --ignore= misses: root-level files and non-PHPCS checks.
		$verification_file = Path_Builder::get_verification_file_path( $plugin_slug );
		if ( $verification_file && file_exists( $verification_file ) ) {
			$vdata = json_decode( file_get_contents( $verification_file ), true );
			if ( is_array( $vdata ) && ! empty( $vdata['ignored_files'] ) ) {
				$plugin_path_norm = rtrim( str_replace( '\\', '/', $plugin_path ), '/' );
				foreach ( $vdata['ignored_files'] as $relative_path => $entry ) {
					if ( ! isset( $results_by_file[ $relative_path ] ) ) {
						continue;
					}
					$stored_hash  = $entry['hash'] ?? '';
					$absolute     = $plugin_path_norm . '/' . $relative_path;
					$current_hash = file_exists( $absolute ) ? md5_file( $absolute ) : '';
					if ( $stored_hash !== '' && $stored_hash === $current_hash ) {
						unset( $results_by_file[ $relative_path ] );
					}
				}
			}
		}

		$total_errors   = $this->count_issues( $errors );
		$total_warnings = $this->count_issues( $warnings );
		
		$results_data = array(
			'generated_at' => gmdate( 'Y-m-d H:i:s' ),
			'plugin'       => $plugin_slug,
			'readiness'    => array(
				'overall'  => min( 100, max( 0, 100 - ( $total_errors * 2 + $total_warnings ) ) ),
				'errors'   => $total_errors,
				'warnings' => $total_warnings,
			),
			'results' => $results_by_file,
		);
		
		file_put_contents( $json_file, wp_json_encode( $results_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
	}

	/**
	 * Apply ignored_paths from JSON to directory filter for Advanced Verification
	 */
	private function apply_ignored_paths_filter( $plugin_slug ) {
		$config_file = Path_Builder::get_plugin_directory_path( $plugin_slug ) . '/.wpv-config.json';
		
		// Load ignored paths from config file only
		$ignored_paths = array();
		if ( file_exists( $config_file ) ) {
			$ignored_paths = $this->load_ignored_paths_from_config( $config_file );
		} else {
			return;
		}
		
		if ( empty( $ignored_paths ) ) {
			return;
		}
		
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
			return;
		}
		
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
	 * Load ignored_paths from config file
	 */
	private function load_ignored_paths_from_config( $config_file ) {
		if ( ! file_exists( $config_file ) ) {
			return array();
		}

		$config_content = file_get_contents( $config_file );
		if ( false === $config_content ) {
			return array();
		}

		$config_data = json_decode( $config_content, true );
		if ( null === $config_data ) {
			return array();
		}

		if ( ! isset( $config_data['ignored_paths'] ) || ! is_array( $config_data['ignored_paths'] ) ) {
			return array();
		}

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
		$absolute_path = str_replace( '\\', '/', $absolute_path );
		$plugin_path   = rtrim( str_replace( '\\', '/', $plugin_path ), '/' );

		if ( strpos( $absolute_path, $plugin_path . '/' ) === 0 ) {
			return substr( $absolute_path, strlen( $plugin_path ) + 1 );
		}

		return $absolute_path;
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
		
		return array(
			'errors' => $limited_errors,
			'warnings' => $limited_warnings
		);
	}

	/**
	 * Prepare the results response with array-based errors and warnings
	 */
	private function prepare_results_response_with_arrays( $errors, $warnings, array $types, $is_limited = false, $elapsed_seconds = 0, $total_before_limit = 0, $max_issues = 0 ) {
		$response = array(
			'message'  => __( 'Checks run successfully', 'wp-verifier' ),
			'errors'   => array(),
			'warnings' => array(),
			'html_output' => '',
			'limited' => $is_limited,
			'elapsed_seconds' => $elapsed_seconds,
			'total_before_limit' => $total_before_limit,
		);

		if ( in_array( 'error', $types, true ) ) {
			$response['errors'] = $errors;
		}

		if ( in_array( 'warning', $types, true ) ) {
			$response['warnings'] = $warnings;
		}

		// Generate HTML output with limitation notice
		$response['html_output'] = $this->generate_results_html_with_limit_notice( $errors, $warnings, $is_limited, $elapsed_seconds, $total_before_limit, $max_issues );
		
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
	private function generate_results_html_with_limit_notice( $errors, $warnings, $is_limited = false, $elapsed_seconds = 0, $total_before_limit = 0, $max_issues = 0 ) {
		$total_errors = $this->count_issues( $errors );
		$total_warnings = $this->count_issues( $warnings );
		$total_issues = $total_errors + $total_warnings;

		if ( $total_issues === 0 ) {
			$time_note = $elapsed_seconds > 0 ? ' ' . sprintf( __( 'Completed in %s seconds.', 'wp-verifier' ), $elapsed_seconds ) : '';
			return '<div class="notice notice-success"><p><strong>' . __( 'Great! No issues found.', 'wp-verifier' ) . '</strong>' . esc_html( $time_note ) . '</p></div>';
		}

		ob_start();
		?>
		<div class="plugin-check-results">
			<?php if ( $is_limited && $total_before_limit > 0 ) : ?>
				<div class="notice notice-warning" style="margin-bottom: 20px;">
					<p><strong><?php esc_html_e( 'Results Limited:', 'wp-verifier' ); ?></strong>
					<?php
						printf(
							esc_html__( '%1$d issues were found but only the first %2$d are shown. Resolve these issues and run verification again to see more.', 'wp-verifier' ),
							$total_before_limit,
							$max_issues
						);
					?>
					</p>
				</div>
			<?php endif; ?>
			
			<div class="results-summary" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 20px;">
				<h3>
					<?php esc_html_e( 'Verification Results Summary', 'wp-verifier' ); ?>
					<?php if ( $elapsed_seconds > 0 ) : ?>
						<span style="font-size: 13px; font-weight: normal; color: #646970; margin-left: 10px;">
							<?php
							if ( $elapsed_seconds >= 60 ) {
								$minutes = floor( $elapsed_seconds / 60 );
								$seconds = round( $elapsed_seconds - ( $minutes * 60 ), 1 );
								printf( esc_html__( 'Completed in %1$dm %2$ss', 'wp-verifier' ), $minutes, $seconds );
							} else {
								printf( esc_html__( 'Completed in %ss', 'wp-verifier' ), $elapsed_seconds );
							}
							?>
						</span>
					<?php endif; ?>
				</h3>
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

	/**
	 * Mark an issue as ignored (sets ignored flag, keeps in results).
	 * If all issues in the file are now ignored, triggers file-level ignore.
	 *
	 * @return void
	 */
	public function mark_issue_as_ignored() {
		$this->check_request_validity();

		$issue_id = filter_input( INPUT_POST, 'issue_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$plugin   = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( empty( $issue_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Issue ID is required.', 'wp-verifier' ) ), 400 );
		}
		if ( empty( $plugin ) ) {
			wp_send_json_error( array( 'message' => __( 'Plugin is required.', 'wp-verifier' ) ), 400 );
		}

		$results_file = Path_Builder::get_results_file_path( $plugin );
		if ( ! file_exists( $results_file ) ) {
			wp_send_json_error( array( 'message' => __( 'Results file not found.', 'wp-verifier' ) ), 404 );
		}

		$results_data = json_decode( file_get_contents( $results_file ), true );
		if ( ! $results_data ) {
			wp_send_json_error( array( 'message' => __( 'Invalid results file.', 'wp-verifier' ) ), 500 );
		}

		$found          = false;
		$affected_file  = null;
		if ( isset( $results_data['results'] ) ) {
			foreach ( $results_data['results'] as $file_path => &$issues ) {
				foreach ( $issues as &$issue ) {
					if ( isset( $issue['issue_id'] ) && $issue['issue_id'] === $issue_id ) {
						$issue['ignored'] = true;
						$found         = true;
						$affected_file = $file_path;
						break 2;
					}
				}
			}
			unset( $issues, $issue );
		}

		if ( ! $found ) {
			wp_send_json_error( array( 'message' => __( 'Issue not found.', 'wp-verifier' ) ), 404 );
		}

		// Check if every issue in the affected file is now ignored.
		$file_ignored = false;
		if ( $affected_file && isset( $results_data['results'][ $affected_file ] ) ) {
			$all_ignored = true;
			foreach ( $results_data['results'][ $affected_file ] as $issue ) {
				if ( empty( $issue['ignored'] ) ) {
					$all_ignored = false;
					break;
				}
			}
			if ( $all_ignored ) {
				$results_data = $this->mark_file_as_ignored( $plugin, $affected_file, $results_data );
				$file_ignored = true;
			}
		}

		file_put_contents( $results_file, wp_json_encode( $results_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );

		wp_send_json_success( array(
			'message'      => $file_ignored
				? __( 'All issues in file ignored — file marked as ignored.', 'wp-verifier' )
				: __( 'Issue marked as ignored.', 'wp-verifier' ),
			'issue_id'     => $issue_id,
			'file_ignored' => $file_ignored,
		) );
	}

	/**
	 * Mark a file as ignored at the file level.
	 *
	 * Computes the MD5 hash of the file on disk, writes an entry to
	 * `.wpv-verification.json` under `ignored_files`, removes all issues
	 * for that file from the results data, and recalculates readiness scores.
	 *
	 * @param string $plugin        Plugin slug.
	 * @param string $file_path     Relative file path within the plugin (as stored in results).
	 * @param array  $results_data  Current decoded results data array.
	 * @return array Updated results data with the file's issues removed.
	 */
	private function mark_file_as_ignored( $plugin, $file_path, array $results_data ) {
		$plugin_dir      = str_replace( '\\', '/', Path_Builder::get_plugin_directory_path( $plugin ) );
		$file_path       = str_replace( '\\', '/', $file_path );
		$absolute_path   = $plugin_dir . '/' . $file_path;
		$file_hash       = file_exists( $absolute_path ) ? md5_file( $absolute_path ) : '';

		// Load or initialise .wpv-verification.json.
		$verification_file = Path_Builder::get_verification_file_path( $plugin );
		$verification_data = array();
		if ( file_exists( $verification_file ) ) {
			$decoded = json_decode( file_get_contents( $verification_file ), true );
			if ( is_array( $decoded ) ) {
				$verification_data = $decoded;
			}
		}

		if ( ! isset( $verification_data['ignored_files'] ) ) {
			$verification_data['ignored_files'] = array();
		}

		$verification_data['ignored_files'][ $file_path ] = array(
			'hash'       => $file_hash,
			'ignored_at' => gmdate( 'Y-m-d H:i:s' ),
		);

		file_put_contents( $verification_file, wp_json_encode( $verification_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );

		// Remove all issues for this file from results.
		unset( $results_data['results'][ $file_path ] );

		// Recalculate readiness.
		if ( isset( $results_data['readiness'] ) ) {
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
		}

		return $results_data;
	}

	/**
	 * Mark issue as unignored (remove ignored flag)
	 */
	public function mark_issue_as_unignored() {
		$this->check_request_validity();

		$issue_id = filter_input( INPUT_POST, 'issue_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$plugin   = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( empty( $issue_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Issue ID is required.', 'wp-verifier' ) ), 400 );
		}

		if ( empty( $plugin ) ) {
			wp_send_json_error( array( 'message' => __( 'Plugin is required.', 'wp-verifier' ) ), 400 );
		}

		$results_file = Path_Builder::get_results_file_path( $plugin );

		if ( ! file_exists( $results_file ) ) {
			wp_send_json_error( array( 'message' => __( 'Results file not found.', 'wp-verifier' ) ), 404 );
		}

		$results_data = json_decode( file_get_contents( $results_file ), true );
		if ( ! $results_data ) {
			wp_send_json_error( array( 'message' => __( 'Invalid results file.', 'wp-verifier' ) ), 500 );
		}

		$found = false;
		if ( isset( $results_data['results'] ) ) {
			foreach ( $results_data['results'] as $file_path => &$issues ) {
				foreach ( $issues as &$issue ) {
					if ( isset( $issue['issue_id'] ) && $issue['issue_id'] === $issue_id ) {
						$issue['ignored'] = false;
						$found = true;
						break 2;
					}
				}
			}
			unset( $issues, $issue );
		}

		if ( ! $found ) {
			wp_send_json_error( array( 'message' => __( 'Issue not found.', 'wp-verifier' ) ), 404 );
		}

		file_put_contents( $results_file, wp_json_encode( $results_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );

		wp_send_json_success( array(
			'message'  => __( 'Issue unmarked as ignored.', 'wp-verifier' ),
			'issue_id' => $issue_id,
		) );
	}

	/**
	 * Mark an issue as fixed by removing it from the results JSON file.
	 * If the remaining issues in the file are all ignored (or none remain),
	 * triggers file-level ignore.
	 *
	 * @return void
	 */
	public function mark_issue_as_fixed() {
		$this->check_request_validity();

		$issue_id = filter_input( INPUT_POST, 'issue_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$plugin   = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( empty( $issue_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Issue ID is required.', 'wp-verifier' ) ), 400 );
		}

		if ( empty( $plugin ) ) {
			wp_send_json_error( array( 'message' => __( 'Plugin is required.', 'wp-verifier' ) ), 400 );
		}

		try {
			$results_file = Path_Builder::get_results_file_path( $plugin );

			if ( ! file_exists( $results_file ) ) {
				wp_send_json_error( array( 'message' => __( 'Results file not found.', 'wp-verifier' ) ), 404 );
			}

			$results_content = file_get_contents( $results_file );
			if ( false === $results_content ) {
				wp_send_json_error( array( 'message' => __( 'Failed to read results file.', 'wp-verifier' ) ), 500 );
			}

			$results_data = json_decode( $results_content, true );
			if ( null === $results_data ) {
				wp_send_json_error( array( 'message' => __( 'Invalid results file format.', 'wp-verifier' ) ), 500 );
			}

			$issue_found   = false;
			$affected_file = null;
			if ( isset( $results_data['results'] ) && is_array( $results_data['results'] ) ) {
				foreach ( $results_data['results'] as $file_path => $issues ) {
					if ( is_array( $issues ) ) {
						foreach ( $issues as $index => $issue ) {
							if ( isset( $issue['issue_id'] ) && $issue['issue_id'] === $issue_id ) {
								unset( $issues[ $index ] );
								$issues = array_values( $issues );
								if ( empty( $issues ) ) {
									unset( $results_data['results'][ $file_path ] );
								} else {
									$results_data['results'][ $file_path ] = $issues;
								}
								$issue_found   = true;
								$affected_file = $file_path;
								break 2;
							}
						}
					}
				}
			}

			if ( ! $issue_found ) {
				wp_send_json_error( array( 'message' => __( 'Issue not found in results.', 'wp-verifier' ) ), 404 );
			}

			// If remaining issues in the file are all ignored (or file is now empty), trigger file-level ignore.
			$file_ignored = false;
			if ( $affected_file ) {
				$remaining = $results_data['results'][ $affected_file ] ?? array();
				$all_ignored = true;
				foreach ( $remaining as $issue ) {
					if ( empty( $issue['ignored'] ) ) {
						$all_ignored = false;
						break;
					}
				}
				if ( $all_ignored ) {
					$results_data = $this->mark_file_as_ignored( $plugin, $affected_file, $results_data );
					$file_ignored = true;
				}
			}

			// Recalculate readiness (skip if mark_file_as_ignored already did it).
			if ( ! $file_ignored && isset( $results_data['readiness'] ) ) {
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
			}

			if ( false === file_put_contents( $results_file, wp_json_encode( $results_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) ) {
				wp_send_json_error( array( 'message' => __( 'Failed to save updated results.', 'wp-verifier' ) ), 500 );
			}

			wp_send_json_success( array(
				'message'      => $file_ignored
					? __( 'Issue fixed — all remaining issues in file were ignored, file marked as ignored.', 'wp-verifier' )
					: __( 'Issue marked as fixed and removed from results.', 'wp-verifier' ),
				'issue_id'     => $issue_id,
				'file_ignored' => $file_ignored,
			) );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
		}
	}
}