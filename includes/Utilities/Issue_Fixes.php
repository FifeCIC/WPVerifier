<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Issue_Fixes
 *
 * @package WPVerifier
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Manages fixed issues tracking.
 */
class Issue_Fixes {

	/**
	 * Mark issue as fixed.
	 *
	 * @param string $plugin Plugin slug.
	 * @param string $issue_id Issue ID.
	 * @return bool Success.
	 */
	public static function mark_fixed( $plugin, $issue_id ) {
		$fixes = self::get_fixes( $plugin );
		
		$fixes[ $issue_id ] = array(
			'fixed_at' => current_time( 'mysql' ),
			'fixed_by' => get_current_user_id(),
		);
		
		return self::save_fixes( $plugin, $fixes );
	}

	/**
	 * Check if issue is fixed.
	 *
	 * @param string $plugin Plugin slug.
	 * @param string $issue_id Issue ID.
	 * @return bool True if fixed.
	 */
	public static function is_fixed( $plugin, $issue_id ) {
		$fixes = self::get_fixes( $plugin );
		return isset( $fixes[ $issue_id ] );
	}

	/**
	 * Get all fixes for plugin.
	 *
	 * @param string $plugin Plugin slug.
	 * @return array Fixes.
	 */
	public static function get_fixes( $plugin ) {
		$file = self::get_fixes_file( $plugin );
		
		if ( ! file_exists( $file ) ) {
			return array();
		}
		
		$json = file_get_contents( $file );
		$data = json_decode( $json, true );
		
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Save fixes for plugin.
	 *
	 * @param string $plugin Plugin slug.
	 * @param array  $fixes Fixes data.
	 * @return bool Success.
	 */
	private static function save_fixes( $plugin, $fixes ) {
		$file = self::get_fixes_file( $plugin );
		$dir  = dirname( $file );
		
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		
		$json = wp_json_encode( $fixes, JSON_PRETTY_PRINT );
		return file_put_contents( $file, $json ) !== false;
	}

	/**
	 * Get fixes file path.
	 *
	 * @param string $plugin Plugin slug.
	 * @return string File path.
	 */
	private static function get_fixes_file( $plugin ) {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . '/verifier-results/' . $plugin . '/fixes.json';
	}
}
