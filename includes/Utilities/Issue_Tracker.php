<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Issue_Tracker
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Issue Tracker utility class.
 *
 * @since 1.9.0
 */
class Issue_Tracker {

	const OPTION_KEY = 'wpv_completed_issues';

	/**
	 * Mark issue as complete.
	 *
	 * @param string $plugin Plugin basename.
	 * @param string $file   File path.
	 * @param int    $line   Line number.
	 * @param string $code   Error code.
	 */
	public static function mark_complete( $plugin, $file, $line, $code ) {
		$completed = get_option( self::OPTION_KEY, array() );
		
		if ( ! isset( $completed[ $plugin ] ) ) {
			$completed[ $plugin ] = array();
		}

		$key = self::get_issue_key( $file, $line, $code );
		$completed[ $plugin ][ $key ] = array(
			'file' => $file,
			'line' => $line,
			'code' => $code,
			'completed_at' => current_time( 'mysql' ),
		);

		update_option( self::OPTION_KEY, $completed );
	}

	/**
	 * Check if issue is complete.
	 *
	 * @param string $plugin Plugin basename.
	 * @param string $file   File path.
	 * @param int    $line   Line number.
	 * @param string $code   Error code.
	 * @return bool True if complete.
	 */
	public static function is_complete( $plugin, $file, $line, $code ) {
		$completed = get_option( self::OPTION_KEY, array() );
		
		if ( ! isset( $completed[ $plugin ] ) ) {
			return false;
		}

		$key = self::get_issue_key( $file, $line, $code );
		return isset( $completed[ $plugin ][ $key ] );
	}

	/**
	 * Get all completed issues for plugin.
	 *
	 * @param string $plugin Plugin basename.
	 * @return array Completed issues.
	 */
	public static function get_completed( $plugin ) {
		$completed = get_option( self::OPTION_KEY, array() );
		return isset( $completed[ $plugin ] ) ? $completed[ $plugin ] : array();
	}

	/**
	 * Compare results with completed issues.
	 *
	 * @param string $plugin  Plugin basename.
	 * @param array  $errors  Current errors.
	 * @param array  $warnings Current warnings.
	 * @return array Rediscovered issues.
	 */
	public static function find_rediscovered( $plugin, $errors, $warnings ) {
		$completed = self::get_completed( $plugin );
		$rediscovered = array();

		foreach ( array( 'errors' => $errors, 'warnings' => $warnings ) as $type => $issues ) {
			foreach ( $issues as $file => $lines ) {
				foreach ( $lines as $line => $columns ) {
					foreach ( $columns as $column => $items ) {
						foreach ( $items as $item ) {
							$key = self::get_issue_key( $file, $line, $item['code'] );
							if ( isset( $completed[ $key ] ) ) {
								$rediscovered[] = array(
									'file' => $file,
									'line' => $line,
									'code' => $item['code'],
									'type' => $type,
								);
							}
						}
					}
				}
			}
		}

		return $rediscovered;
	}

	/**
	 * Generate unique key for issue.
	 *
	 * @param string $file File path.
	 * @param int    $line Line number.
	 * @param string $code Error code.
	 * @return string Issue key.
	 */
	private static function get_issue_key( $file, $line, $code ) {
		return md5( $file . '|' . $line . '|' . $code );
	}
}
