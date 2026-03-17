<?php
/**
 * Saved Results Handler
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

use WordPress\Plugin_Check\Utilities\Path_Builder;

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
		$saved_results = array();
		$plugins_dir = WP_PLUGIN_DIR;

		if ( ! is_dir( $plugins_dir ) ) {
			return $saved_results;
		}

		$plugins = glob( $plugins_dir . '/*', GLOB_ONLYDIR );
		foreach ( $plugins as $plugin_dir ) {
			$json_file = $plugin_dir . '/.wpv-results.json';
			if ( file_exists( $json_file ) ) {
				$data = json_decode( file_get_contents( $json_file ), true );
				if ( $data && ! empty( $data['results'] ) ) {
					$saved_results[] = self::format_result_data( $plugin_dir, $json_file, $data );
				}
			}
		}

		return $saved_results;
	}

	/**
	 * Load results for a specific plugin.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @return array|null Results data or null if not found.
	 */
	public static function load_plugin_results( $plugin_slug ) {
		$json_file = Path_Builder::get_results_file_path( $plugin_slug );
		if ( ! file_exists( $json_file ) ) {
			return null;
		}
		$data = json_decode( file_get_contents( $json_file ), true );
		return $data ? $data : null;
	}

	/**
	 * Get last selected plugin info.
	 *
	 * @return array|null Array with 'basename', 'name', 'slug' or null.
	 */
	public static function get_last_selected_plugin() {
		$plugin_basename = get_user_meta( get_current_user_id(), 'wpv_last_selected_plugin', true );
		if ( ! $plugin_basename ) {
			return null;
		}
		$plugins = get_plugins();
		if ( ! isset( $plugins[ $plugin_basename ] ) ) {
			return null;
		}
		return array(
			'basename' => $plugin_basename,
			'name'     => $plugins[ $plugin_basename ]['Name'],
			'slug'     => strpos( $plugin_basename, '/' ) !== false ? dirname( $plugin_basename ) : $plugin_basename,
		);
	}

	/**
	 * Merge AI guidance into results data.
	 *
	 * @param array $data Results data.
	 * @return array Results with AI guidance merged.
	 */
	public static function merge_ai_guidance( $data ) {
		if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\AI_Guidance' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/AI_Guidance.php';
		}
		$ai_guidance = \WordPress\Plugin_Check\Utilities\AI_Guidance::get_all_guidance();
		
		if ( ! empty( $data['results'] ) ) {
			foreach ( $data['results'] as $file => &$issues ) {
				foreach ( $issues as &$issue ) {
					$code = $issue['code'] ?? '';
					if ( $code && isset( $ai_guidance[ $code ] ) ) {
						$issue['ai_guidance'] = $ai_guidance[ $code ]['ai_guidance'] ?? '';
					}
				}
			}
		}
		
		return $data;
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
