<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Ignore_Rules
 *
 * @package WPVerifier
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Manages ignore rules for filtering verification results.
 */
class Ignore_Rules {

	const OPTION_NAME = 'wpv_ignore_rules';
	const SCOPE_DIRECTORY = 'directory';
	const SCOPE_FILE = 'file';
	const SCOPE_CODE = 'code';
	const REASON_VENDOR = 'vendor';
	const REASON_OTHER = 'other';

	/**
	 * Add an ignore rule.
	 *
	 * @param string $scope Scope: directory, file, or code.
	 * @param string $path Path or pattern to ignore.
	 * @param string $reason Reason: vendor or other.
	 * @param string $code Optional error code for code-level ignores.
	 * @param string $note Optional note.
	 * @return bool Success.
	 */
	public static function add_rule( $scope, $path, $reason = self::REASON_OTHER, $code = '', $note = '' ) {
		$rules = self::get_rules();
		$rule_id = md5( $scope . $path . $code );

		$rules[ $rule_id ] = array(
			'scope'   => $scope,
			'path'    => $path,
			'reason'  => $reason,
			'code'    => $code,
			'note'    => $note,
			'created' => time(),
		);

		return update_option( self::OPTION_NAME, $rules );
	}

	/**
	 * Remove an ignore rule.
	 *
	 * @param string $rule_id Rule ID.
	 * @return bool Success.
	 */
	public static function remove_rule( $rule_id ) {
		$rules = self::get_rules();
		unset( $rules[ $rule_id ] );
		return update_option( self::OPTION_NAME, $rules );
	}

	/**
	 * Get all ignore rules.
	 *
	 * @return array Ignore rules.
	 */
	public static function get_rules() {
		return get_option( self::OPTION_NAME, array() );
	}

	/**
	 * Check if a file should be ignored.
	 *
	 * @param string $file File path.
	 * @param string $code Optional error code.
	 * @return bool True if should be ignored.
	 */
	public static function should_ignore( $file, $code = '' ) {
		// Skip if file is empty
		if ( empty( $file ) ) {
			return false;
		}

		$rules = self::get_rules();
		$file = wp_normalize_path( $file );

		foreach ( $rules as $rule ) {
			// Ensure rule has scope key
			if ( ! isset( $rule['scope'] ) ) {
				continue;
			}

			// Directory scope
			if ( self::SCOPE_DIRECTORY === $rule['scope'] ) {
				$pattern = wp_normalize_path( $rule['path'] );
				if ( strpos( $file, $pattern ) === 0 ) {
					return true;
				}
			}

			// File scope
			if ( self::SCOPE_FILE === $rule['scope'] ) {
				$pattern = wp_normalize_path( $rule['path'] );
				if ( $file === $pattern ) {
					return true;
				}
			}

			// Code scope
			if ( self::SCOPE_CODE === $rule['scope'] && $code ) {
				$pattern = wp_normalize_path( $rule['path'] );
				if ( ( $file === $pattern || strpos( $file, $pattern ) === 0 ) && $rule['code'] === $code ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Auto-detect vendor directories.
	 *
	 * @param string $plugin_dir Plugin directory path.
	 * @return array Detected vendor directories.
	 */
	public static function detect_vendor_dirs( $plugin_dir ) {
		$vendor_dirs = array();
		$common_dirs = array( 'vendor', 'node_modules', 'libraries', 'lib', 'libs', 'third-party' );

		foreach ( $common_dirs as $dir ) {
			$path = trailingslashit( $plugin_dir ) . $dir;
			if ( is_dir( $path ) ) {
				$vendor_dirs[] = $path;
			}
		}

		return $vendor_dirs;
	}

	/**
	 * Export rules as JSON.
	 *
	 * @return string JSON string.
	 */
	public static function export_rules() {
		return wp_json_encode( self::get_rules(), JSON_PRETTY_PRINT );
	}

	/**
	 * Import rules from JSON.
	 *
	 * @param string $json JSON string.
	 * @return bool Success.
	 */
	public static function import_rules( $json ) {
		$imported = json_decode( $json, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $imported ) ) {
			return false;
		}

		$rules = self::get_rules();
		$rules = array_merge( $rules, $imported );
		return update_option( self::OPTION_NAME, $rules );
	}
}
