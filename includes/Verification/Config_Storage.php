<?php
/**
 * Config Storage handler for plugin configuration data
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Verification;

/**
 * Handles JSON storage for plugin configuration data
 */
class Config_Storage {

	/**
	 * Config file name
	 */
	const CONFIG_FILE = '.wpv-config.json';

	/**
	 * Plugin directory path
	 */
	private $plugin_dir;

	/**
	 * Constructor
	 *
	 * @param string $plugin_basename Plugin basename (e.g., 'wpverifier/plugin.php').
	 * @throws \InvalidArgumentException If the resolved plugin directory is invalid.
	 */
	public function __construct( $plugin_basename ) {
		$plugin_folder = dirname( $plugin_basename );

		// dirname() returns '.' when there is no directory component, which would
		// resolve to WP_PLUGIN_DIR itself — an unsafe write target.
		if ( '.' === $plugin_folder || empty( $plugin_folder ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Invalid plugin basename supplied to Config_Storage: "%s"', $plugin_basename )
			);
		}

		$this->plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_folder;
	}

	/**
	 * Get config file path
	 *
	 * @return string Full path to config file
	 */
	protected function get_config_file_path() {
		return $this->plugin_dir . '/' . self::CONFIG_FILE;
	}

	/**
	 * Load config data from JSON file
	 *
	 * @return array Config data structure
	 */
	public function load_config_data() {
		$file_path = $this->get_config_file_path();
		
		if ( ! file_exists( $file_path ) ) {
			return $this->get_default_structure();
		}

		$content = file_get_contents( $file_path );
		if ( false === $content ) {
			return $this->get_default_structure();
		}

		$data = json_decode( $content, true );
		if ( null === $data ) {
			return $this->get_default_structure();
		}

		return $this->validate_structure( $data );
	}

	/**
	 * Assert that a resolved path is safe to write to.
	 *
	 * Prevents writes to any path that does not end with the expected
	 * WPVerifier config filename and is not inside WP_PLUGIN_DIR.
	 *
	 * @param string $file_path Absolute path to validate.
	 * @return bool True if safe, false otherwise.
	 */
	protected function is_safe_write_path( $file_path ) {
		$real_plugin_dir = realpath( WP_PLUGIN_DIR );
		$real_file_path  = realpath( dirname( $file_path ) );

		if ( false === $real_plugin_dir || false === $real_file_path ) {
			return false;
		}
		if ( strpos( $real_file_path, $real_plugin_dir ) !== 0 ) {
			return false;
		}
		if ( basename( $file_path ) !== self::CONFIG_FILE ) {
			return false;
		}

		return true;
	}

	/**
	 * Save config data to JSON file
	 *
	 * @param array $data Config data to save
	 * @return bool True on success, false on failure
	 */
	public function save_config_data( $data ) {
		$file_path = $this->get_config_file_path();

		// Abort if the resolved path is not a safe WPVerifier JSON target.
		if ( ! $this->is_safe_write_path( $file_path ) ) {
			return false;
		}

		// Create backup if file exists
		if ( file_exists( $file_path ) ) {
			$backup_path = $file_path . '.backup';
			copy( $file_path, $backup_path );
		}

		$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			return false;
		}
		
		// Atomic write using temp file
		$temp_path = $file_path . '.tmp';
		$result = file_put_contents( $temp_path, $json, LOCK_EX );
		
		if ( false === $result ) {
			return false;
		}

		return rename( $temp_path, $file_path );
	}

	/**
	 * Update configuration without losing other data
	 *
	 * @param array $config_updates Configuration updates to merge
	 * @return bool True on success
	 */
	public function update_configuration( $config_updates ) {
		$data = $this->load_config_data();
		
		if ( ! isset( $data['configuration'] ) ) {
			$data['configuration'] = array();
		}

		$data['configuration'] = array_merge( $data['configuration'], $config_updates );
		
		return $this->save_config_data( $data );
	}

	/**
	 * Add ignored path without losing other data
	 *
	 * @param array $ignored_path Ignored path data
	 * @return bool True on success
	 */
	public function add_ignored_path( $ignored_path ) {
		$data = $this->load_config_data();
		
		if ( ! isset( $data['ignored_paths'] ) ) {
			$data['ignored_paths'] = array();
		}

		$data['ignored_paths'][] = $ignored_path;
		
		return $this->save_config_data( $data );
	}

	/**
	 * Set ignored paths
	 *
	 * @param array $ignored_paths Array of ignored paths
	 * @return bool True on success
	 */
	public function set_ignored_paths( $ignored_paths ) {
		$data = $this->load_config_data();
		$data['ignored_paths'] = $ignored_paths;
		return $this->save_config_data( $data );
	}

	/**
	 * Get default config data structure
	 *
	 * @return array Default structure
	 */
	protected function get_default_structure() {
		return array(
			'configuration' => array(
				'wporg_preparation' => true,
				'skipped_rules' => array(),
				'excluded_directories' => array(),
				'scan_depth' => 'deep',
				'include_vendor' => false,
				'check_functions' => true,
				'check_classes' => true,
				'check_hooks' => true,
				'severity_threshold' => 'warning'
			),
			'ignored_paths' => array(),
			'verification_settings' => array(
				'auto_save' => true,
				'backup_results' => true,
				'incremental_scan' => true
			),
			'phpcs_settings' => array(
				'standard' => 'WordPress',
				'extensions' => array( 'php' ),
				'ignore_patterns' => array(
					'*/vendor/*',
					'*/node_modules/*',
					'*/tests/*'
				)
			)
		);
	}

	/**
	 * Validate and fix config data structure
	 *
	 * @param array $data Data to validate
	 * @return array Validated data
	 */
	protected function validate_structure( $data ) {
		if ( ! is_array( $data ) ) {
			return $this->get_default_structure();
		}

		// Ensure required keys exist
		$data['configuration'] = is_array( $data['configuration'] ?? null ) ? $data['configuration'] : array();
		$data['ignored_paths'] = is_array( $data['ignored_paths'] ?? null ) ? $data['ignored_paths'] : array();

		return $data;
	}
}