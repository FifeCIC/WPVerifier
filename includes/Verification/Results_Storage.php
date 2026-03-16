<?php
/**
 * Results Storage handler for plugin verification data
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Verification;

/**
 * Handles JSON storage for plugin results data
 */
class Results_Storage {

	/**
	 * Results file name
	 */
	const RESULTS_FILE = '.wpv-results.json';

	/**
	 * Plugin directory path
	 */
	private $plugin_dir;

	/**
	 * Constructor
	 *
	 * @param string $plugin_basename Plugin basename (e.g., 'wpverifier/plugin.php')
	 */
	public function __construct( $plugin_basename ) {
		$plugin_folder = dirname( $plugin_basename );
		$this->plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_folder;
	}

	/**
	 * Get results file path
	 *
	 * @return string Full path to results file
	 */
	protected function get_results_file_path() {
		return $this->plugin_dir . '/' . self::RESULTS_FILE;
	}

	/**
	 * Load results data from JSON file
	 *
	 * @return array Results data structure
	 */
	public function load_results_data() {
		$file_path = $this->get_results_file_path();
		
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
	 * Save results data to JSON file
	 *
	 * @param array $data Results data to save
	 * @return bool True on success, false on failure
	 */
	public function save_results_data( $data ) {
		$file_path = $this->get_results_file_path();
		
		// Create backup if file exists
		if ( file_exists( $file_path ) ) {
			$backup_path = $file_path . '.backup';
			copy( $file_path, $backup_path );
		}

		$json = wp_json_encode( $data, JSON_PRETTY_PRINT );
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
		$data = $this->load_results_data();
		
		if ( ! isset( $data['configuration'] ) ) {
			$data['configuration'] = array();
		}

		$data['configuration'] = array_merge( $data['configuration'], $config_updates );
		
		return $this->save_results_data( $data );
	}

	/**
	 * Add ignored path without losing other data
	 *
	 * @param array $ignored_path Ignored path data
	 * @return bool True on success
	 */
	public function add_ignored_path( $ignored_path ) {
		$data = $this->load_results_data();
		
		if ( ! isset( $data['ignored_paths'] ) ) {
			$data['ignored_paths'] = array();
		}

		$data['ignored_paths'][] = $ignored_path;
		
		return $this->save_results_data( $data );
	}

	/**
	 * Set specific field without losing other data
	 *
	 * @param string $field Field name
	 * @param mixed  $value Field value
	 * @return bool True on success
	 */
	public function set_field( $field, $value ) {
		$data = $this->load_results_data();
		$data[ $field ] = $value;
		return $this->save_results_data( $data );
	}

	/**
	 * Remove specific field
	 *
	 * @param string $field Field name to remove
	 * @return bool True on success
	 */
	public function remove_field( $field ) {
		$data = $this->load_results_data();
		unset( $data[ $field ] );
		return $this->save_results_data( $data );
	}

	/**
	 * Get default results data structure
	 *
	 * @return array Default structure
	 */
	protected function get_default_structure() {
		return array(
			'configuration' => array(),
			'ignored_paths' => array(),
		);
	}

	/**
	 * Validate and fix results data structure
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