<?php
/**
 * Verification Matcher for checking verification status
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Verification;

/**
 * Matches issues against verification data
 */
class Verification_Matcher {

	/**
	 * JSON Storage instance
	 *
	 * @var JSON_Storage
	 */
	protected $storage;

	/**
	 * Hash Generator instance
	 *
	 * @var Hash_Generator
	 */
	protected $hash_generator;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->storage = new JSON_Storage();
		$this->hash_generator = new Hash_Generator();
	}

	/**
	 * Check if an issue is verified
	 *
	 * @param array  $issue Issue data with file, line, type
	 * @param string $function_name Function name where issue occurs
	 * @return array Verification status with metadata
	 */
	public function is_verified( $issue, $function_name = '' ) {
		$verification_data = $this->storage->load_verification_data();
		
		// Check file-level verification first
		$file_verification = $this->check_file_verification( $issue, $verification_data );
		if ( $file_verification['verified'] ) {
			return $file_verification;
		}

		// Check function-level verification
		if ( ! empty( $function_name ) ) {
			return $this->check_function_verification( $issue, $function_name, $verification_data );
		}

		return array(
			'verified' => false,
			'status' => 'unverified',
			'reason' => 'No verification found',
		);
	}

	/**
	 * Check file-level verification
	 *
	 * @param array $issue Issue data
	 * @param array $verification_data Verification data
	 * @return array Verification result
	 */
	protected function check_file_verification( $issue, $verification_data ) {
		$file_path = $issue['file'] ?? '';
		
		if ( empty( $file_path ) || ! isset( $verification_data['file_level'][ $file_path ] ) ) {
			return array( 'verified' => false );
		}

		$file_verification = $verification_data['file_level'][ $file_path ];
		$stored_hash = $file_verification['hash'] ?? '';
		
		// Generate current file hash
		$current_hash = $this->hash_generator->generate_file_hash( $file_path );
		
		if ( $current_hash !== $stored_hash ) {
			return array(
				'verified' => false,
				'status' => 'stale',
				'reason' => 'File has been modified since verification',
				'stored_hash' => $stored_hash,
				'current_hash' => $current_hash,
			);
		}

		return array(
			'verified' => true,
			'status' => 'file_verified',
			'verified_by' => $file_verification['verified_by'] ?? '',
			'verified_at' => $file_verification['verified_at'] ?? '',
			'note' => $file_verification['note'] ?? '',
		);
	}

	/**
	 * Check function-level verification
	 *
	 * @param array  $issue Issue data
	 * @param string $function_name Function name
	 * @param array  $verification_data Verification data
	 * @return array Verification result
	 */
	protected function check_function_verification( $issue, $function_name, $verification_data ) {
		if ( ! isset( $verification_data['function_level'][ $function_name ] ) ) {
			return array( 'verified' => false );
		}

		$function_verification = $verification_data['function_level'][ $function_name ];
		$stored_hash = $function_verification['hash'] ?? '';
		$file_path = $function_verification['file'] ?? '';
		
		// Generate current function hash
		$current_hash = $this->hash_generator->generate_function_hash( $file_path, $function_name );
		
		if ( $current_hash !== $stored_hash ) {
			return array(
				'verified' => false,
				'status' => 'stale',
				'reason' => 'Function has been modified since verification',
				'stored_hash' => $stored_hash,
				'current_hash' => $current_hash,
			);
		}

		// Check if this specific issue is verified
		$issues = $function_verification['issues'] ?? array();
		$issue_line = $issue['line'] ?? 0;
		$issue_type = $issue['type'] ?? '';

		foreach ( $issues as $verified_issue ) {
			if ( $verified_issue['line'] == $issue_line && $verified_issue['type'] === $issue_type ) {
				return array(
					'verified' => true,
					'status' => 'function_verified',
					'verified_by' => $verified_issue['verified_by'] ?? '',
					'verified_at' => $verified_issue['verified_at'] ?? '',
					'note' => $verified_issue['note'] ?? '',
				);
			}
		}

		return array( 'verified' => false );
	}

	/**
	 * Get verification coverage for a file
	 *
	 * @param string $file_path File path
	 * @param array  $all_issues All issues in the file
	 * @return array Coverage statistics
	 */
	public function get_verification_coverage( $file_path, $all_issues ) {
		$total_issues = count( $all_issues );
		$verified_count = 0;
		$stale_count = 0;

		foreach ( $all_issues as $issue ) {
			$verification = $this->is_verified( $issue );
			if ( $verification['verified'] ) {
				$verified_count++;
			} elseif ( 'stale' === ( $verification['status'] ?? '' ) ) {
				$stale_count++;
			}
		}

		return array(
			'total_issues' => $total_issues,
			'verified_count' => $verified_count,
			'stale_count' => $stale_count,
			'coverage_percentage' => $total_issues > 0 ? round( ( $verified_count / $total_issues ) * 100, 2 ) : 0,
		);
	}
}