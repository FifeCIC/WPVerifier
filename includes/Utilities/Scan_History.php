<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Scan_History
 *
 * @package WPVerifier
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Manages scan history and comparisons.
 */
class Scan_History {

	const OPTION_PREFIX = 'wpv_scan_history_';
	const MAX_HISTORY = 10;

	/**
	 * Save scan results to history.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @param array  $errors      Errors array.
	 * @param array  $warnings    Warnings array.
	 * @return bool Success.
	 */
	public static function save_scan( $plugin_slug, $errors, $warnings ) {
		$history = self::get_history( $plugin_slug );

		$scan = array(
			'timestamp' => time(),
			'errors'    => $errors,
			'warnings'  => $warnings,
			'counts'    => array(
				'errors'   => count( $errors ),
				'warnings' => count( $warnings ),
			),
		);

		array_unshift( $history, $scan );

		if ( count( $history ) > self::MAX_HISTORY ) {
			$history = array_slice( $history, 0, self::MAX_HISTORY );
		}

		return update_option( self::OPTION_PREFIX . sanitize_key( $plugin_slug ), $history );
	}

	/**
	 * Get scan history for a plugin.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @return array Scan history.
	 */
	public static function get_history( $plugin_slug ) {
		return get_option( self::OPTION_PREFIX . sanitize_key( $plugin_slug ), array() );
	}

	/**
	 * Get the last scan for a plugin.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @return array|null Last scan or null.
	 */
	public static function get_last_scan( $plugin_slug ) {
		$history = self::get_history( $plugin_slug );
		return ! empty( $history ) ? $history[0] : null;
	}

	/**
	 * Compare current results with last scan.
	 *
	 * @param array $current_errors   Current errors.
	 * @param array $current_warnings Current warnings.
	 * @param array $last_scan        Last scan data.
	 * @return array Comparison data.
	 */
	public static function compare_scans( $current_errors, $current_warnings, $last_scan ) {
		if ( ! $last_scan ) {
			return array(
				'new_errors'     => $current_errors,
				'new_warnings'   => $current_warnings,
				'fixed_errors'   => array(),
				'fixed_warnings' => array(),
				'is_first_scan'  => true,
			);
		}

		$last_errors   = isset( $last_scan['errors'] ) ? $last_scan['errors'] : array();
		$last_warnings = isset( $last_scan['warnings'] ) ? $last_scan['warnings'] : array();

		return array(
			'new_errors'     => self::find_new_issues( $current_errors, $last_errors ),
			'new_warnings'   => self::find_new_issues( $current_warnings, $last_warnings ),
			'fixed_errors'   => self::find_new_issues( $last_errors, $current_errors ),
			'fixed_warnings' => self::find_new_issues( $last_warnings, $current_warnings ),
			'is_first_scan'  => false,
			'last_scan_time' => isset( $last_scan['timestamp'] ) ? $last_scan['timestamp'] : 0,
		);
	}

	/**
	 * Find new issues by comparing current with previous.
	 *
	 * @param array $current Current issues.
	 * @param array $previous Previous issues.
	 * @return array New issues.
	 */
	private static function find_new_issues( $current, $previous ) {
		$new = array();
		$previous_keys = array();

		foreach ( $previous as $issue ) {
			$key = self::get_issue_key( $issue );
			$previous_keys[ $key ] = true;
		}

		foreach ( $current as $issue ) {
			$key = self::get_issue_key( $issue );
			if ( ! isset( $previous_keys[ $key ] ) ) {
				$new[] = $issue;
			}
		}

		return $new;
	}

	/**
	 * Generate unique key for an issue.
	 *
	 * @param array $issue Issue data.
	 * @return string Unique key.
	 */
	private static function get_issue_key( $issue ) {
		$file = isset( $issue['file'] ) ? $issue['file'] : '';
		$line = isset( $issue['line'] ) ? $issue['line'] : 0;
		$code = isset( $issue['code'] ) ? $issue['code'] : '';
		return md5( $file . '|' . $line . '|' . $code );
	}

	/**
	 * Clear history for a plugin.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @return bool Success.
	 */
	public static function clear_history( $plugin_slug ) {
		return delete_option( self::OPTION_PREFIX . sanitize_key( $plugin_slug ) );
	}

	/**
	 * Get summary statistics for history.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @return array Statistics.
	 */
	public static function get_statistics( $plugin_slug ) {
		$history = self::get_history( $plugin_slug );

		if ( empty( $history ) ) {
			return array(
				'total_scans' => 0,
				'trend'       => 'none',
			);
		}

		$total = count( $history );
		$latest = $history[0];
		$oldest = end( $history );

		$latest_total = $latest['counts']['errors'] + $latest['counts']['warnings'];
		$oldest_total = $oldest['counts']['errors'] + $oldest['counts']['warnings'];

		$trend = 'stable';
		if ( $latest_total < $oldest_total ) {
			$trend = 'improving';
		} elseif ( $latest_total > $oldest_total ) {
			$trend = 'declining';
		}

		return array(
			'total_scans'    => $total,
			'latest_errors'  => $latest['counts']['errors'],
			'latest_warnings' => $latest['counts']['warnings'],
			'trend'          => $trend,
			'first_scan'     => $oldest['timestamp'],
			'last_scan'      => $latest['timestamp'],
		);
	}
}
