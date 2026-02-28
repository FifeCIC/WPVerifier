<?php
/**
 * WordPress.org Specific Rules
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Class for managing WordPress.org specific rules.
 *
 * @since 1.9.0
 */
class WPOrg_Rules {

	/**
	 * Get list of WordPress.org specific error codes.
	 *
	 * @since 1.9.0
	 *
	 * @return array List of error codes specific to WordPress.org.
	 */
	public static function get_wporg_codes() {
		return array(
			'hidden_files',
			'application_detected',
			'plugin_updater_detected',
			'outdated_tested_upto_header',
			'stable_tag_mismatch',
			'readme_mismatched_header_requires',
			'mismatched_tested_up_to_header',
			'missing_direct_file_access_protection',
		);
	}

	/**
	 * Check if an error code is WordPress.org specific.
	 *
	 * @since 1.9.0
	 *
	 * @param string $code Error code.
	 * @return bool True if WordPress.org specific.
	 */
	public static function is_wporg_code( $code ) {
		return in_array( $code, self::get_wporg_codes(), true );
	}

	/**
	 * Filter results to exclude WordPress.org specific issues.
	 *
	 * @since 1.9.0
	 *
	 * @param array $results Results array (errors or warnings).
	 * @return array Filtered results.
	 */
	public static function filter_results( $results ) {
		$filtered = array();
		
		foreach ( $results as $file => $lines ) {
			foreach ( $lines as $line => $columns ) {
				foreach ( $columns as $column => $issues ) {
					foreach ( $issues as $issue ) {
						$code = isset( $issue['code'] ) ? $issue['code'] : '';
						
						if ( ! self::is_wporg_code( $code ) ) {
							if ( ! isset( $filtered[ $file ] ) ) {
								$filtered[ $file ] = array();
							}
							if ( ! isset( $filtered[ $file ][ $line ] ) ) {
								$filtered[ $file ][ $line ] = array();
							}
							if ( ! isset( $filtered[ $file ][ $line ][ $column ] ) ) {
								$filtered[ $file ][ $line ][ $column ] = array();
							}
							$filtered[ $file ][ $line ][ $column ][] = $issue;
						}
					}
				}
			}
		}
		
		return $filtered;
	}
}
