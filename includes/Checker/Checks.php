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
		
		$issue_limit = $this->get_issue_limit_from_request();

		foreach ( $checks as $check ) {
			if ( $issue_limit > 0 && ( $result->get_error_count() + $result->get_warning_count() ) >= $issue_limit ) {
				break;
			}

			$this->run_check_with_result( $check, $result, $issue_limit > 0 ? $issue_limit : null );
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
	 * Get issue limit from current request.
	 *
	 * @since 1.0.0
	 *
	 * @return int Issue limit (0 = no limit).
	 */
	private function get_issue_limit_from_request() {
		$check_options_json = filter_input( INPUT_POST, 'check_options', FILTER_UNSAFE_RAW );
		if ( $check_options_json ) {
			$check_options_json = stripslashes( $check_options_json );
			$check_options_json = html_entity_decode( $check_options_json, ENT_QUOTES, 'UTF-8' );
		}

		if ( ! $check_options_json ) {
			return 0;
		}

		$check_options = json_decode( $check_options_json, true );
		if ( ! $check_options || ! is_array( $check_options ) ) {
			return 0;
		}

		// Radio-based max_issues (new)
		if ( isset( $check_options['max_issues'] ) && (int) $check_options['max_issues'] > 0 ) {
			return (int) $check_options['max_issues'];
		}

		// Legacy checkbox fallback
		if ( ! empty( $check_options['limit_results'] ) ) {
			return 20;
		}

		return 0;
	}


}
