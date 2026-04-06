<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Readiness_Score
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Readiness Score utility class.
 *
 * @since 1.9.0
 */
class Readiness_Score {

	/**
	 * Calculate readiness score from results.
	 *
	 * @since 1.9.0
	 * @version 1.9.0 Removed error_log() debug call for production safety.
	 *
	 * @param array $errors   Array of errors.
	 * @param array $warnings Array of warnings.
	 * @return array Readiness score data.
	 */
	public static function calculate( $errors, $warnings ) {
		$error_count   = self::count_issues( $errors );
		$warning_count = self::count_issues( $warnings );
		$overall_score = self::calculate_score( $error_count, $warning_count );
		
		$result = array(
			'overall' => $overall_score,
			'errors'  => $error_count,
			'warnings' => $warning_count,
			'status'  => self::get_status( $overall_score ),
		);

		// Score data is returned to the caller — no need for debug logging.
		return $result;
	}

	/**
	 * Count total issues.
	 *
	 * @since 1.9.0
	 *
	 * @param array $issues Array of issues.
	 * @return int Issue count.
	 */
	private static function count_issues( $issues ) {
		// Issues are passed as a flat array of issue objects
		return count( $issues );
	}

	/**
	 * Calculate score from error and warning counts.
	 *
	 * @since 1.9.0
	 *
	 * @param int $errors   Error count.
	 * @param int $warnings Warning count.
	 * @return int Score (0-100).
	 */
	private static function calculate_score( $errors, $warnings ) {
		// Score: 100 - (errors * 10 + warnings * 5), minimum 0
		return max( 0, 100 - ( $errors * 10 + $warnings * 5 ) );
	}

	/**
	 * Get readiness status based on score.
	 *
	 * @since 1.9.0
	 *
	 * @param int $score Overall score.
	 * @return string Status label.
	 */
	private static function get_status( $score ) {
		if ( $score >= 90 ) {
			return 'excellent';
		} elseif ( $score >= 75 ) {
			return 'good';
		} elseif ( $score >= 50 ) {
			return 'fair';
		} else {
			return 'needs-work';
		}
	}
}
