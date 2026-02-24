<?php
/**
 * Saved Results Handler
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

/**
 * Handles data preparation for saved results page.
 */
class Saved_Results_Handler {

	/**
	 * Get saved results data.
	 *
	 * @return array
	 */
	public static function get_saved_results() {
		$results_dir = WP_CONTENT_DIR . '/verifier-results';
		$saved_results = array();

		if ( ! is_dir( $results_dir ) ) {
			return $saved_results;
		}

		$plugins = glob( $results_dir . '/*', GLOB_ONLYDIR );
		foreach ( $plugins as $plugin_dir ) {
			$json_file = $plugin_dir . '/results.json';
			if ( file_exists( $json_file ) ) {
				$data = json_decode( file_get_contents( $json_file ), true );
				if ( $data ) {
					$saved_results[] = self::format_result_data( $plugin_dir, $json_file, $data );
				}
			}
		}

		return $saved_results;
	}

	/**
	 * Format result data.
	 *
	 * @param string $plugin_dir Plugin directory path.
	 * @param string $json_file JSON file path.
	 * @param array  $data Result data.
	 * @return array
	 */
	private static function format_result_data( $plugin_dir, $json_file, $data ) {
		$plugin_name = basename( $plugin_dir );
		
		// Use stored counts from JSON readiness
		$total_issues = 0;
		if ( isset( $data['readiness']['errors'] ) ) {
			$total_issues += (int) $data['readiness']['errors'];
		}
		if ( isset( $data['readiness']['warnings'] ) ) {
			$total_issues += (int) $data['readiness']['warnings'];
		}
		
		$file_count = isset( $data['results'] ) && is_array( $data['results'] ) ? count( $data['results'] ) : 0;
		
		// Count ignored from JSON
		$ignored_count = 0;
		if ( isset( $data['results'] ) && is_array( $data['results'] ) ) {
			foreach ( $data['results'] as $file_issues ) {
				if ( is_array( $file_issues ) ) {
					foreach ( $file_issues as $issue ) {
						if ( isset( $issue['ignored'] ) && $issue['ignored'] === true ) {
							$ignored_count++;
						}
					}
				}
			}
		}

		return array(
			'plugin'  => ucwords( str_replace( '-', ' ', $plugin_name ) ),
			'path'    => $json_file,
			'files'   => $file_count,
			'issues'  => $total_issues,
			'ignored' => $ignored_count,
		);
	}
}
