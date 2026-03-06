<?php
/**
 * Hash Generator for function-level verification tracking
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Verification;

/**
 * Generates hashes for functions and files to track verification status
 */
class Hash_Generator {

	/**
	 * Generate hash for entire file
	 *
	 * @param string $file_path Path to PHP file
	 * @return string|false SHA256 hash (8 chars) or false on error
	 */
	public function generate_file_hash( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		$content = file_get_contents( $file_path );
		if ( false === $content ) {
			return false;
		}

		$normalized = $this->normalize_content( $content );
		return substr( hash( 'sha256', $normalized ), 0, 8 );
	}

	/**
	 * Generate hash for specific function
	 *
	 * @param string $file_path Path to PHP file
	 * @param string $function_name Function name (Class::method or function_name)
	 * @return string|false SHA256 hash (8 chars) or false on error
	 */
	public function generate_function_hash( $file_path, $function_name ) {
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		$content = file_get_contents( $file_path );
		if ( false === $content ) {
			return false;
		}

		$function_body = $this->extract_function_body( $content, $function_name );
		if ( false === $function_body ) {
			return false;
		}

		$normalized = $this->normalize_content( $function_body );
		return substr( hash( 'sha256', $normalized ), 0, 8 );
	}

	/**
	 * Generate hashes for all functions in a file
	 *
	 * @param string $file_path Path to PHP file
	 * @return array Array of function_name => hash pairs
	 */
	public function generate_function_hashes( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return array();
		}

		$content = file_get_contents( $file_path );
		if ( false === $content ) {
			return array();
		}

		$functions = $this->extract_all_functions( $content );
		$hashes = array();

		foreach ( $functions as $function_name => $function_body ) {
			$normalized = $this->normalize_content( $function_body );
			$hashes[ $function_name ] = substr( hash( 'sha256', $normalized ), 0, 8 );
		}

		return $hashes;
	}

	/**
	 * Extract all functions from PHP content
	 *
	 * @param string $content PHP file content
	 * @return array Array of function_name => function_body pairs
	 */
	protected function extract_all_functions( $content ) {
		$tokens = token_get_all( $content );
		$functions = array();
		$current_class = '';

		for ( $i = 0; $i < count( $tokens ); $i++ ) {
			$token = $tokens[ $i ];

			if ( is_array( $token ) ) {
				// Track current class
				if ( T_CLASS === $token[0] ) {
					$current_class = $this->get_class_name_from_tokens( $tokens, $i );
				}
				// Find function declarations
				elseif ( T_FUNCTION === $token[0] ) {
					$function_name = $this->get_function_name_from_tokens( $tokens, $i );
					$function_body = $this->extract_function_body_from_index( $tokens, $i );
					
					if ( $function_name && $function_body ) {
						// Use class::method format if inside a class
						if ( $current_class && strpos( $function_name, '::' ) === false ) {
							$function_name = $current_class . '::' . $function_name;
						}
						$functions[ $function_name ] = $function_body;
					}
				}
			}
		}

		return $functions;
	}

	/**
	 * Get class name from tokens starting at T_CLASS
	 *
	 * @param array $tokens Token array
	 * @param int   $start_index Index of T_CLASS token
	 * @return string Class name
	 */
	protected function get_class_name_from_tokens( $tokens, $start_index ) {
		for ( $i = $start_index + 1; $i < count( $tokens ); $i++ ) {
			if ( is_array( $tokens[ $i ] ) && T_STRING === $tokens[ $i ][0] ) {
				return $tokens[ $i ][1];
			}
		}
		return '';
	}

	/**
	 * Extract function body from token index
	 *
	 * @param array $tokens Token array
	 * @param int   $function_index Index of T_FUNCTION token
	 * @return string Function body
	 */
	protected function extract_function_body_from_index( $tokens, $function_index ) {
		$brace_count = 0;
		$function_body = '';
		$found_opening_brace = false;

		for ( $i = $function_index; $i < count( $tokens ); $i++ ) {
			$token = $tokens[ $i ];

			if ( ! is_array( $token ) ) {
				if ( '{' === $token ) {
					$brace_count++;
					$found_opening_brace = true;
					if ( 1 === $brace_count ) {
						continue; // Skip opening brace
					}
				} elseif ( '}' === $token ) {
					$brace_count--;
					if ( 0 === $brace_count && $found_opening_brace ) {
						break; // End of function
					}
				}

				if ( $found_opening_brace && $brace_count > 0 ) {
					$function_body .= $token;
				}
			} else {
				if ( $found_opening_brace && $brace_count > 0 ) {
					$function_body .= $token[1];
				}
			}
		}

		return $function_body;
	}

	/**
	 * Extract function body from PHP content
	 *
	 * @param string $content PHP file content
	 * @param string $function_name Function name to extract
	 * @return string|false Function body or false if not found
	 */
	protected function extract_function_body( $content, $function_name ) {
		$tokens = token_get_all( $content );
		$in_function = false;
		$brace_count = 0;
		$function_body = '';
		$found_function = false;

		for ( $i = 0; $i < count( $tokens ); $i++ ) {
			$token = $tokens[ $i ];

			if ( is_array( $token ) ) {
				// Look for function/method declaration
				if ( T_FUNCTION === $token[0] ) {
					$func_name = $this->get_function_name_from_tokens( $tokens, $i );
					if ( $func_name === $function_name ) {
						$found_function = true;
						$in_function = true;
					}
				}
			} else {
				// Track braces when in target function
				if ( $found_function && $in_function ) {
					if ( '{' === $token ) {
						$brace_count++;
						if ( 1 === $brace_count ) {
							continue; // Skip opening brace
						}
					} elseif ( '}' === $token ) {
						$brace_count--;
						if ( 0 === $brace_count ) {
							break; // End of function
						}
					}

					if ( $brace_count > 0 ) {
						$function_body .= $token;
					}
				}
			}

			// Add token content to function body if we're inside the target function
			if ( $found_function && $in_function && $brace_count > 0 && is_array( $token ) ) {
				$function_body .= $token[1];
			}
		}

		return $found_function ? $function_body : false;
	}

	/**
	 * Get function name from token stream
	 *
	 * @param array $tokens Token array
	 * @param int   $start_index Index of T_FUNCTION token
	 * @return string Function name (Class::method or function_name)
	 */
	protected function get_function_name_from_tokens( $tokens, $start_index ) {
		$class_name = '';
		$function_name = '';

		// Look backwards for class name
		for ( $i = $start_index - 1; $i >= 0; $i-- ) {
			if ( is_array( $tokens[ $i ] ) && T_CLASS === $tokens[ $i ][0] ) {
				// Find class name
				for ( $j = $i + 1; $j < count( $tokens ); $j++ ) {
					if ( is_array( $tokens[ $j ] ) && T_STRING === $tokens[ $j ][0] ) {
						$class_name = $tokens[ $j ][1];
						break;
					}
				}
				break;
			}
		}

		// Look forward for function name
		for ( $i = $start_index + 1; $i < count( $tokens ); $i++ ) {
			if ( is_array( $tokens[ $i ] ) && T_STRING === $tokens[ $i ][0] ) {
				$function_name = $tokens[ $i ][1];
				break;
			}
		}

		return $class_name ? $class_name . '::' . $function_name : $function_name;
	}

	/**
	 * Normalize content for consistent hashing
	 *
	 * @param string $content Content to normalize
	 * @return string Normalized content
	 */
	protected function normalize_content( $content ) {
		// Normalize line endings
		$content = str_replace( array( "\r\n", "\r" ), "\n", $content );
		
		// Trim whitespace
		$content = trim( $content );
		
		return $content;
	}
}