<?php
/**
 * Configuration AJAX Handler
 *
 * Handles AJAX requests related to plugin configuration management.
 * Extracted from Admin_AJAX.php as part of consolidation effort.
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Admin;

use InvalidArgumentException;
use WordPress\Plugin_Check\Verification\Config_Storage;
use WordPress\Plugin_Check\Admin\Vendor_Detector;
use WordPress\Plugin_Check\Utilities\Path_Builder;

/**
 * Handles configuration-related AJAX requests
 */
class Config_AJAX_Handler {

	/**
	 * Nonce key for configuration actions
	 */
	const NONCE_KEY = 'plugin-check-run-checks';

	/**
	 * Register AJAX hooks for configuration actions
	 */
	public function add_hooks() {
		add_action( 'wp_ajax_wpv_load_config', array( $this, 'load_config' ) );
		add_action( 'wp_ajax_wpv_save_config', array( $this, 'save_config' ) );
		add_action( 'wp_ajax_wpv_detect_vendors', array( $this, 'detect_vendors_simple' ) );
		add_action( 'wp_ajax_plugin_check_update_config', array( $this, 'update_config' ) );
		add_action( 'wp_ajax_plugin_check_detect_vendors', array( $this, 'detect_vendors' ) );
		add_action( 'wp_ajax_plugin_check_save_ignored_paths', array( $this, 'save_ignored_paths' ) );
		add_action( 'wp_ajax_plugin_check_detect_folders', array( $this, 'detect_folders' ) );
	}

	/**
	 * Load plugin configuration
	 */
	public function load_config() {
		$this->check_request_validity();

		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin ) ) {
				throw new InvalidArgumentException( __( 'Plugin is required.', 'wp-verifier' ) );
			}

			$config_storage = new Config_Storage( $plugin );
			$config = $config_storage->load_config_data();

			wp_send_json_success( $config );

		} catch ( \Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Save plugin configuration (simplified version)
	 */
	public function save_config() {
		$this->check_request_validity();

		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$config_json = isset( $_POST['config_data'] ) ? wp_unslash( $_POST['config_data'] ) : '';
			
			// Debug logging
			error_log( 'WPV DEBUG: Raw config_json from POST: ' . $config_json );

			if ( empty( $plugin ) || empty( $config_json ) ) {
				throw new InvalidArgumentException( __( 'Plugin and config data are required.', 'wp-verifier' ) );
			}

			$form_config = json_decode( $config_json, true );
			if ( ! $form_config ) {
				throw new InvalidArgumentException( __( 'Invalid config data.', 'wp-verifier' ) );
			}
			
			error_log( 'WPV DEBUG: Decoded form_config: ' . print_r( $form_config, true ) );

			$config_storage = new Config_Storage( $plugin );
			$full_config = $config_storage->load_config_data();
			
			// Update configuration section with form data
			$full_config['configuration']['wporg_preparation'] = $form_config['wporg_preparation'] ?? false;
			
			// Handle skipped rules based on wporg_preparation
			if ( ! $form_config['wporg_preparation'] ) {
				// When WordPress.org prep is disabled, skip strict WordPress.org repository rules
				$wporg_rules = array(
					'hidden_files',
					'application_detected',
					'plugin_updater_detected',
					'outdated_tested_upto_header',
					'stable_tag_mismatch',
					'readme_mismatched_header_requires',
					'mismatched_tested_up_to_header',
					'missing_direct_file_access_protection'
				);
				$manual_rules = $form_config['skipped_rules'] ?? array();
				$full_config['configuration']['skipped_rules'] = array_merge( $wporg_rules, $manual_rules );
			} else {
				// When WordPress.org prep is enabled, only use manual rules
				$full_config['configuration']['skipped_rules'] = $form_config['skipped_rules'] ?? array();
			}
			
			// Handle vendor folders as ignored paths
			if ( ! empty( $form_config['vendor_folders'] ) ) {
				// Debug logging
				error_log( 'WPV DEBUG: Raw vendor_folders from form: ' . print_r( $form_config['vendor_folders'], true ) );
				
				$full_config['ignored_paths'] = array();
				foreach ( $form_config['vendor_folders'] as $folder ) {
					error_log( 'WPV DEBUG: Original folder path: ' . $folder );
					
					// Remove escaped slashes and normalize path
					$clean_path = str_replace( '\/', '/', $folder );
					$normalized_path = wp_normalize_path( $clean_path );
					error_log( 'WPV DEBUG: Clean path: ' . $clean_path );
					error_log( 'WPV DEBUG: Normalized path: ' . $normalized_path );
					
					$full_config['ignored_paths'][] = array(
						'path' => $normalized_path,
						'reason' => 'vendor',
						'added_at' => current_time( 'mysql' )
					);
				}
				
				error_log( 'WPV DEBUG: Final ignored_paths before save: ' . print_r( $full_config['ignored_paths'], true ) );
			}
			
			$result = $config_storage->save_config_data( $full_config );

			if ( ! $result ) {
				throw new InvalidArgumentException( __( 'Failed to save configuration.', 'wp-verifier' ) );
			}

			// Build detailed response
			$wporg_status = $form_config['wporg_preparation'] ? 'enabled' : 'disabled';
			$total_rules = count( $full_config['configuration']['skipped_rules'] );
			$rules_text = $total_rules > 0 ? $total_rules . ' rules will be skipped' : 'no rules will be skipped';
			
			wp_send_json_success( array(
				'message' => sprintf(
					'Configuration saved: WordPress.org preparation %s, %s during verification.',
					$wporg_status,
					$rules_text
				),
			) );

		} catch ( \Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Update plugin configuration
	 */
	public function update_config() {
		$this->check_request_validity();

		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$config_json = isset( $_POST['config'] ) ? wp_unslash( $_POST['config'] ) : '';

			if ( empty( $plugin ) || empty( $config_json ) ) {
				throw new InvalidArgumentException( __( 'Plugin and config are required.', 'wp-verifier' ) );
			}

			$config = json_decode( $config_json, true );
			if ( ! $config ) {
				throw new InvalidArgumentException( __( 'Invalid config data.', 'wp-verifier' ) );
			}

			$config_storage = new Config_Storage( $plugin );
			$result = $config_storage->save_config_data( $config );

			if ( ! $result ) {
				throw new InvalidArgumentException( __( 'Failed to save configuration file.', 'wp-verifier' ) );
			}

			// Initialize verification file
			if ( ! class_exists( 'WordPress\\Plugin_Check\\Verification\\JSON_Storage' ) ) {
				require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Verification/JSON_Storage.php';
			}
			$plugin_folder = strpos( $plugin, '/' ) !== false ? dirname( $plugin ) : $plugin;
			\WordPress\Plugin_Check\Verification\JSON_Storage::initialize_verification_file( $plugin_folder );

			wp_send_json_success( array(
				'message' => __( 'Configuration saved to .wpv-config.json successfully.', 'wp-verifier' ),
			) );

		} catch ( \Exception $exception ) {
			wp_send_json_error(
				array( 'message' => $exception->getMessage() ),
				400
			);
		}
	}

	/**
	 * Detect vendor folders (simplified version)
	 */
	public function detect_vendors_simple() {
		$this->check_request_validity();

		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin ) ) {
				throw new InvalidArgumentException( __( 'Plugin is required.', 'wp-verifier' ) );
			}

			$vendors_raw = Vendor_Detector::detect_vendors( $plugin );
			$vendors = array();

			// Convert to expected format for drag-and-drop interface
			foreach ( $vendors_raw as $path => $subdirs ) {
				if ( ! empty( $subdirs ) ) {
					$vendors[] = array(
						'path' => $path,
						'reason' => 'vendor library detected',
						'subdirs' => $subdirs
					);
				} else {
					// Handle case where path itself is a vendor folder
					$vendors[] = array(
						'path' => $path,
						'reason' => 'vendor library detected',
						'subdirs' => array()
					);
				}
			}

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
	 * Detect vendor folders in plugin
	 */
	public function detect_vendors() {
		$this->check_request_validity();

		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $plugin ) ) {
				throw new InvalidArgumentException( __( 'Plugin is required.', 'wp-verifier' ) );
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
	 * Save ignored paths to configuration
	 */
	public function save_ignored_paths() {
		$this->check_request_validity();

		try {
			$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$paths = isset( $_POST['paths'] ) ? json_decode( wp_unslash( $_POST['paths'] ), true ) : array();

			if ( empty( $plugin ) || empty( $paths ) ) {
				throw new InvalidArgumentException( __( 'Plugin and paths are required.', 'wp-verifier' ) );
			}

			$ignored_paths = array();
			foreach ( $paths as $path ) {
				// Normalize path to prevent escaped slashes
				$normalized_path = wp_normalize_path( $path );
				$ignored_paths[] = array(
					'path' => $normalized_path,
					'reason' => 'vendor',
					'added_by' => wp_get_current_user()->user_login,
					'added_at' => current_time( 'mysql' ),
				);
			}

			$config_storage = new Config_Storage( $plugin );
			$result = $config_storage->set_ignored_paths( $ignored_paths );

			if ( ! $result ) {
				throw new InvalidArgumentException( __( 'Failed to save ignored paths.', 'wp-verifier' ) );
			}

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
	 * Detect vendor/libraries/library folders in plugin
	 */
	public function detect_folders() {
		$plugin = filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $plugin ) {
			wp_send_json_error();
		}

		if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Vendor_Patterns' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Vendor_Patterns.php';
		}

			$plugin_dir = Path_Builder::get_plugin_directory_path( $plugin );
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