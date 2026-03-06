<?php
/**
 * JSON Storage handler for verification tracking
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Verification;

/**
 * Handles JSON storage for verification data
 */
class JSON_Storage {

	/**
	 * Verification file name
	 */
	const VERIFICATION_FILE = '.wpv-verification.json';

	/**
	 * Get verification file path
	 *
	 * @return string Full path to verification file
	 */
	protected function get_verification_file_path() {
		return WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . self::VERIFICATION_FILE;
	}

	/**
	 * Load verification data from JSON file
	 *
	 * @return array Verification data structure
	 */
	public function load_verification_data() {
		$file_path = $this->get_verification_file_path();
		
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
	 * Save verification data to JSON file
	 *
	 * @param array $data Verification data to save
	 * @return bool True on success, false on failure
	 */
	public function save_verification_data( $data ) {
		$file_path = $this->get_verification_file_path();
		
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
	 * Mark function as verified
	 *
	 * @param string $function_name Function name (Class::method or function_name)
	 * @param string $file_path Relative file path
	 * @param string $hash Function hash
	 * @param array  $issues Array of issues to mark as verified
	 * @param string $note Optional verification note
	 * @return bool True on success
	 */
	public function mark_function_verified( $function_name, $file_path, $hash, $issues = array(), $note = '' ) {
		$data = $this->load_verification_data();
		
		if ( ! isset( $data['function_level'][ $function_name ] ) ) {
			$data['function_level'][ $function_name ] = array(
				'file' => $file_path,
				'hash' => $hash,
				'issues' => array(),
			);
		}

		$current_user = wp_get_current_user();
		$timestamp = current_time( 'c' );

		foreach ( $issues as $issue ) {
			$issue_data = array(
				'line' => $issue['line'] ?? 0,
				'type' => $issue['type'] ?? '',
				'status' => 'verified',
				'note' => $note,
				'verified_by' => $current_user->ID,
				'verified_at' => $timestamp,
			);

			$data['function_level'][ $function_name ]['issues'][] = $issue_data;
		}

		return $this->save_verification_data( $data );
	}

	/**
	 * Mark entire file as verified
	 *
	 * @param string $file_path Relative file path
	 * @param string $hash File hash
	 * @param string $note Optional verification note
	 * @return bool True on success
	 */
	public function mark_file_verified( $file_path, $hash, $note = '' ) {
		$data = $this->load_verification_data();
		
		$current_user = wp_get_current_user();
		$timestamp = current_time( 'c' );

		$data['file_level'][ $file_path ] = array(
			'hash' => $hash,
			'status' => 'verified',
			'note' => $note,
			'verified_by' => $current_user->ID,
			'verified_at' => $timestamp,
		);

		return $this->save_verification_data( $data );
	}

	/**
	 * Initialize verification file for a plugin
	 *
	 * @param string $plugin_folder Plugin folder name
	 * @return bool True on success, false on failure
	 */
	public static function initialize_verification_file( $plugin_folder ) {
		$file_path = WP_PLUGIN_DIR . '/' . $plugin_folder . '/' . self::VERIFICATION_FILE;
		
		if ( file_exists( $file_path ) ) {
			return true; // Already exists
		}
		
		$default_data = array(
			'version' => '1.0',
			'file_level' => array(),
			'function_level' => array(),
		);
		
		$json = wp_json_encode( $default_data, JSON_PRETTY_PRINT );
		if ( false === $json ) {
			return false;
		}
		
		return false !== file_put_contents( $file_path, $json );
	}

	/**
	 * Get default verification data structure
	 *
	 * @return array Default structure
	 */
	protected function get_default_structure() {
		return array(
			'version' => '1.0',
			'file_level' => array(),
			'function_level' => array(),
		);
	}

	/**
	 * Validate and fix verification data structure
	 *
	 * @param array $data Data to validate
	 * @return array Validated data
	 */
	protected function validate_structure( $data ) {
		$default = $this->get_default_structure();
		
		if ( ! is_array( $data ) ) {
			return $default;
		}

		// Ensure required keys exist
		$data['version'] = $data['version'] ?? $default['version'];
		$data['file_level'] = is_array( $data['file_level'] ?? null ) ? $data['file_level'] : array();
		$data['function_level'] = is_array( $data['function_level'] ?? null ) ? $data['function_level'] : array();

		return $data;
	}
}