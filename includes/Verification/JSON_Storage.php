<?php
/**
 * JSON Storage Handler for Verification Tracking
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Verification;

/**
 * Handles reading and writing .wpv-verification.json files
 */
class JSON_Storage {

	/**
	 * Get verification file path for a plugin
	 *
	 * @param string $plugin_slug Plugin slug
	 * @return string Full path to verification JSON file
	 */
	public static function get_verification_file_path( $plugin_slug ) {
		$plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_slug;
		return $plugin_dir . '/.wpv-verification.json';
	}

	/**
	 * Initialize verification file for a plugin
	 *
	 * @param string $plugin_slug Plugin slug
	 * @return bool True on success, false on failure
	 */
	public static function initialize_verification_file( $plugin_slug ) {
		$file_path = self::get_verification_file_path( $plugin_slug );
		
		if ( file_exists( $file_path ) ) {
			return true;
		}

		$initial_data = array(
			'version'        => '1.0',
			'plugin'         => $plugin_slug,
			'created_at'     => current_time( 'mysql' ),
			'readiness'      => 0,
			'file_level'     => array(),
			'function_level' => array(),
		);

		return self::write_verification_data( $plugin_slug, $initial_data );
	}

	/**
	 * Read verification data from JSON file
	 *
	 * @param string $plugin_slug Plugin slug
	 * @return array|null Verification data or null on failure
	 */
	public static function read_verification_data( $plugin_slug ) {
		$file_path = self::get_verification_file_path( $plugin_slug );

		if ( ! file_exists( $file_path ) ) {
			return null;
		}

		$json_content = file_get_contents( $file_path );
		if ( false === $json_content ) {
			return null;
		}

		$data = json_decode( $json_content, true );
		if ( null === $data ) {
			return null;
		}

		return self::validate_structure( $data ) ? $data : null;
	}

	/**
	 * Write verification data to JSON file
	 *
	 * @param string $plugin_slug Plugin slug
	 * @param array  $data Verification data
	 * @return bool True on success, false on failure
	 */
	public static function write_verification_data( $plugin_slug, $data ) {
		$file_path = self::get_verification_file_path( $plugin_slug );
		
		$json_content = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( false === $json_content ) {
			return false;
		}

		$result = file_put_contents( $file_path, $json_content, LOCK_EX );
		return false !== $result;
	}

	/**
	 * Validate verification data structure
	 *
	 * @param array $data Verification data
	 * @return bool True if valid, false otherwise
	 */
	private static function validate_structure( $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		}

		$required_keys = array( 'version', 'file_level', 'function_level' );
		foreach ( $required_keys as $key ) {
			if ( ! isset( $data[ $key ] ) ) {
				return false;
			}
		}

		return true;
	}
}
