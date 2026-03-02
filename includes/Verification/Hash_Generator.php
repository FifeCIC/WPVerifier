<?php
/**
 * Hash Generator for Verification Tracking
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Verification;

/**
 * Generates hashes for files and functions to track verification status.
 */
class Hash_Generator {

	/**
	 * Generate hash for entire file.
	 *
	 * @param string $file_path Absolute path to file.
	 * @return string|false 8-character hash or false on failure.
	 */
	public static function generate_file_hash( $file_path ) {
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return false;
		}

		$content = file_get_contents( $file_path );
		if ( false === $content ) {
			return false;
		}

		$normalized = self::normalize_content( $content );
		return substr( hash( 'sha256', $normalized ), 0, 8 );
	}

	/**
	 * Generate hash for specific function.
	 *
	 * @param string $file_path     Absolute path to file.
	 * @param string $function_name Function name (e.g., 'my_function' or 'MyClass::my_method').
	 * @return string|false 8-character hash or false on failure.
	 */
	public static function generate_function_hash( $file_path, $function_name ) {
		$function_content = self::extract_function_content( $file_path, $function_name );
		
		if ( false === $function_content ) {
			return false;
		}

		$normalized = self::normalize_content( $function_content );
		return substr( hash( 'sha256', $normalized ), 0, 8 );
	}

	/**
	 * Extract function content from file.
	 *
	 * @param string $file_path     Absolute path to file.
	 * @param string $function_name Function name.
	 * @return string|false Function body content or false on failure.
	 */
	private static function extract_function_content( $file_path, $function_name ) {
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return false;
		}

		$content = file_get_contents( $file_path );
		if ( false === $content ) {
			return false;
		}

		$tokens = token_get_all( $content );
		$function_content = '';
		$in_function = false;
		$brace_count = 0;
		$found_function = false;

		for ( $i = 0; $i < count( $tokens ); $i++ ) {
			$token = $tokens[ $i ];

			// Look for function declaration
			if ( is_array( $token ) && T_FUNCTION === $token[0] ) {
				// Find function name
				$j = $i + 1;
				while ( $j < count( $tokens ) ) {
					if ( is_array( $tokens[ $j ] ) && T_STRING === $tokens[ $j ][0] ) {
						$current_function = $tokens[ $j ][1];
						
						// Check if this is the function we're looking for
						if ( $current_function === $function_name || 
							 strpos( $function_name, '::' . $current_function ) !== false ) {
							$found_function = true;
							$in_function = true;
							break;
						}
						break;
					}
					$j++;
				}
			}

			// Capture function body
			if ( $in_function ) {
				if ( is_string( $token ) && '{' === $token ) {
					$brace_count++;
				} elseif ( is_string( $token ) && '}' === $token ) {
					$brace_count--;
					if ( 0 === $brace_count ) {
						$function_content .= $token;
						break;
					}
				}
				
				$function_content .= is_array( $token ) ? $token[1] : $token;
			}
		}

		return $found_function ? $function_content : false;
	}

	/**
	 * Normalize content for consistent hashing.
	 *
	 * @param string $content Content to normalize.
	 * @return string Normalized content.
	 */
	private static function normalize_content( $content ) {
		// Remove all whitespace variations
		$content = preg_replace( '/\s+/', ' ', $content );
		
		// Normalize line endings
		$content = str_replace( array( "\r\n", "\r" ), "\n", $content );
		
		// Trim
		$content = trim( $content );
		
		return $content;
	}
}
