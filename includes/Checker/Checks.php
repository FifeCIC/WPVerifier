<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

use Exception;

/**
 * Class to run checks on a plugin.
 *
 * @since 1.0.0
 */
final class Checks {

	/**
	 * Array of all available Checks.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $checks;

	/**
	 * Runs checks against the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Context $context The check context for the plugin to be checked.
	 * @param array         $checks  An array of Check objects to run.
	 * @param Check_Runner  $runner  The runner instance that created this result.
	 * @return Check_Result Object containing all check results.
	 *
	 * @throws Exception Thrown when check fails with critical error.
	 */
	public function run_checks( Check_Context $context, array $checks, ?Check_Runner $runner = null ) {
		$result = new Check_Result( $context );
		
		// Check if issue limiting is enabled
		$limit_results = $this->get_issue_limit_from_request();
		$issue_limit = $limit_results ? 20 : 0; // 0 means no limit
		$issue_count = 0;
		
		if ( $issue_limit > 0 ) {
			error_log( 'WPV Debug: Issue limiting enabled - will stop after ' . $issue_limit . ' issues' );
		}

		// Run the checks with early termination support
		foreach ( $checks as $check ) {
			// Check if we've reached the issue limit
			if ( $issue_limit > 0 && $issue_count >= $issue_limit ) {
				error_log( 'WPV Debug: Issue limit reached (' . $issue_count . '/' . $issue_limit . ') - stopping check execution' );
				break;
			}
			
			// Store current issue count before running check
			$issues_before = $this->count_issues_in_result( $result );
			
			// Run the check
			$this->run_check_with_result( $check, $result );
			
			// Update issue count after running check
			if ( $issue_limit > 0 ) {
				$issues_after = $this->count_issues_in_result( $result );
				$new_issues = $issues_after - $issues_before;
				$issue_count = $issues_after;
				
				if ( $new_issues > 0 ) {
					error_log( 'WPV Debug: Check "' . get_class( $check ) . '" found ' . $new_issues . ' issues (total: ' . $issue_count . ')' );
				}
			}
		}
		
		if ( $issue_limit > 0 ) {
			error_log( 'WPV Debug: Check execution completed with ' . $issue_count . ' total issues (limit was ' . $issue_limit . ')' );
		}

		return $result;
	}

	/**
	 * Runs a given check with the given result object to amend.
	 *
	 * @since 1.0.0
	 *
	 * @param Check        $check  The check to run.
	 * @param Check_Result $result The result object to amend.
	 *
	 * @throws Exception Thrown when check fails with critical error.
	 */
	private function run_check_with_result( Check $check, Check_Result $result ) {
		// If $check implements Preparation interface, ensure the preparation and clean up is run.
		if ( $check instanceof Preparation ) {
			$cleanup = $check->prepare();

			try {
				$check->run( $result );
			} catch ( Exception $e ) {
				// Run clean up in case of any exception thrown from check.
				$cleanup();
				throw $e;
			}

			$cleanup();
			return;
		}

		// Otherwise, just run the check.
		$check->run( $result );
	}

	/**
	 * Get issue limit from current request
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if issue limiting is enabled
	 */
	private function get_issue_limit_from_request() {
		// Get check_options from POST request
		$check_options_json = filter_input( INPUT_POST, 'check_options', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $check_options_json ) {
			return false;
		}
		
		$check_options = json_decode( $check_options_json, true );
		if ( ! $check_options || ! is_array( $check_options ) ) {
			return false;
		}
		
		return isset( $check_options['limit_results'] ) ? (bool) $check_options['limit_results'] : false;
	}

	/**
	 * Count total issues in a Check_Result object
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The result object to count issues in
	 * @return int Total number of issues (errors + warnings)
	 */
	private function count_issues_in_result( Check_Result $result ) {
		$errors = $result->get_errors();
		$warnings = $result->get_warnings();
		
		$error_count = $this->count_issues_in_array( $errors );
		$warning_count = $this->count_issues_in_array( $warnings );
		
		return $error_count + $warning_count;
	}

	/**
	 * Count issues in a nested array structure
	 *
	 * @since 1.0.0
	 *
	 * @param array $issues Nested array of issues
	 * @return int Total count of issues
	 */
	private function count_issues_in_array( $issues ) {
		$count = 0;
		foreach ( $issues as $file => $lines ) {
			foreach ( $lines as $line => $columns ) {
				foreach ( $columns as $column => $issue_list ) {
					$count += count( $issue_list );
				}
			}
		}
		return $count;
	}
}
