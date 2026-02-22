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
	 *
	 * @param array $errors   Array of errors.
	 * @param array $warnings Array of warnings.
	 * @return array Readiness score data.
	 */
	public static function calculate( $errors, $warnings ) {
		error_log( '=== Readiness_Score::calculate ===' );
		error_log( 'Input errors type: ' . gettype( $errors ) );
		error_log( 'Input warnings type: ' . gettype( $warnings ) );
		if ( is_array( $errors ) ) {
			error_log( 'Errors count: ' . count( $errors ) );
			if ( ! empty( $errors ) ) {
				error_log( 'First error key: ' . print_r( array_key_first( $errors ), true ) );
			}
		}
		if ( is_array( $warnings ) ) {
			error_log( 'Warnings count: ' . count( $warnings ) );
		}
		
		$error_count   = self::count_issues( $errors );
		$warning_count = self::count_issues( $warnings );
		
		error_log( 'Counted errors: ' . $error_count );
		error_log( 'Counted warnings: ' . $warning_count );
		
		$overall_score = self::calculate_score( $error_count, $warning_count );

		return array(
			'overall' => $overall_score,
			'errors'  => $error_count,
			'warnings' => $warning_count,
			'status'  => self::get_status( $overall_score ),
		);
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
		$count = 0;

		// Handle both flat array and grouped array formats
		foreach ( $issues as $file_or_issue ) {
			if ( is_array( $file_or_issue ) ) {
				// Grouped format: file => [line => [column => [issues]]]
				foreach ( $file_or_issue as $line_or_issues ) {
					if ( is_array( $line_or_issues ) ) {
						foreach ( $line_or_issues as $column_or_issues ) {
							if ( is_array( $column_or_issues ) ) {
								$count += count( $column_or_issues );
							} else {
								$count++;
							}
						}
					} else {
						$count++;
					}
				}
			} else {
				// Flat format
				$count++;
			}
		}

		return $count;
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
