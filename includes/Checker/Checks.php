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

		// Run the checks with early termination support
		foreach ( $checks as $check ) {
			// Check if we've reached the issue limit
			if ( $issue_limit > 0 && $issue_count >= $issue_limit ) {
				break;
			}
			
			// Store current issue count before running check
			$issues_before = $this->count_issues_in_result( $result );
			
			// Run the check
			$this->run_check_with_result( $check, $result, $issue_limit > 0 ? $issue_limit : null );
			
			// Update issue count after running check
			if ( $issue_limit > 0 ) {
				$issues_after = $this->count_issues_in_result( $result );
				$issue_count = $issues_after;
				
				// Check if we've reached the limit after this check
				if ( $issue_count >= $issue_limit ) {
					// Will stop after this check
				}
			}
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
	 * @param int|null     $issue_limit Optional. Maximum number of issues before stopping.
	 *
	 * @throws Exception Thrown when check fails with critical error.
	 */
	private function run_check_with_result( Check $check, Check_Result $result, $issue_limit = null ) {
		// If $check implements Preparation interface, ensure the preparation and clean up is run.
		if ( $check instanceof Preparation ) {
			$cleanup = $check->prepare();

			try {
				// Pass issue limit to checks that support it
				if ( method_exists( $check, 'run' ) && $issue_limit !== null ) {
					$reflection = new \ReflectionMethod( $check, 'run' );
					$parameters = $reflection->getParameters();
					// Check if the run method accepts an issue_limit parameter
					if ( count( $parameters ) >= 2 ) {
						$check->run( $result, $issue_limit );
					} else {
						$check->run( $result );
					}
				} else {
					$check->run( $result );
				}
			} catch ( Exception $e ) {
				// Run clean up in case of any exception thrown from check.
				$cleanup();
				throw $e;
			}

			$cleanup();
			return;
		}

		// Otherwise, just run the check with issue limit if supported
		if ( method_exists( $check, 'run' ) && $issue_limit !== null ) {
			try {
				$reflection = new \ReflectionMethod( $check, 'run' );
				$parameters = $reflection->getParameters();
				// Check if the run method accepts an issue_limit parameter
				if ( count( $parameters ) >= 2 ) {
					$check->run( $result, $issue_limit );
				} else {
					$check->run( $result );
				}
			} catch ( \ReflectionException $e ) {
				// Fallback to original method call
				$check->run( $result );
			}
		} else {
			$check->run( $result );
		}
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
		$check_options_json = filter_input( INPUT_POST, 'check_options', FILTER_UNSAFE_RAW );
		// Handle double-escaped JSON and HTML entities
		if ( $check_options_json ) {
			$check_options_json = stripslashes( $check_options_json );
			$check_options_json = html_entity_decode( $check_options_json, ENT_QUOTES, 'UTF-8' );
		}
		
		if ( ! $check_options_json ) {
			return false;
		}
		
		$check_options = json_decode( $check_options_json, true );
		
		if ( ! $check_options || ! is_array( $check_options ) ) {
			return false;
		}
		
		$limit_results = isset( $check_options['limit_results'] ) ? (bool) $check_options['limit_results'] : false;
		
		return $limit_results;
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
		
		$total = $error_count + $warning_count;
		
		return $total;
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
