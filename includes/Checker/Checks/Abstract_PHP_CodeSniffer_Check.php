<?php
/**
 * Class WordPress\Plugin_Check\Checker\Checks\Abstract_PHP_CodeSniffer_Check
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker\Checks;

use Exception;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Runner;
use WordPress\Plugin_Check\Checker\Check_Result;
use WordPress\Plugin_Check\Checker\Static_Check;
use WordPress\Plugin_Check\Traits\Amend_Check_Result;
use WordPress\Plugin_Check\Utilities\Plugin_Request_Utility;

/**
 * Check for running one or more PHP CodeSniffer sniffs.
 *
 * @since 1.0.0
 */
abstract class Abstract_PHP_CodeSniffer_Check implements Static_Check {

	use Amend_Check_Result;

	/**
	 * List of allowed PHPCS arguments.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $allowed_args = array(
		'standard'    => true,
		'extensions'  => true,
		'sniffs'      => true,
		'runtime-set' => true,
		'exclude'     => true, //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
	);

	/**
	 * Returns an associative array of arguments to pass to PHPCS.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @return array {
	 *    An associative array of PHPCS CLI arguments. Can include one or more of the following options.
	 *
	 *    @type string $standard   The name or path to the coding standard to check against.
	 *    @type string $extensions A comma separated list of file extensions to check against.
	 *    @type string $sniffs     A comma separated list of sniff codes to include from checks.
	 *    @type string $exclude    A comma separated list of sniff codes to exclude from checks.
	 * }
	 */
	abstract protected function get_args( Check_Result $result );

	/**
	 * Amends the given result by running the check on the associated plugin.
	 *
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param int|null $issue_limit Optional. Maximum number of issues to find before stopping. Null for no limit.
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	final public function run( Check_Result $result, $issue_limit = null ) {
		// Include the PHPCS autoloader.
		$autoloader = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'vendor/squizlabs/php_codesniffer/autoload.php';

		if ( file_exists( $autoloader ) ) {
			include_once $autoloader;
		}

		if ( ! class_exists( Runner::class ) ) {
			throw new Exception(
				__( 'Unable to find PHPCS Runner class.', 'wpverifier' )
			);
		}

		if ( ! class_exists( Config::class ) ) {
			throw new Exception(
				__( 'Unable to find PHPCS Config class.', 'wpverifier' )
			);
		}

		// Step 1: Check for unchanged files and skip them
		$files_to_scan = $this->get_files_to_scan( $result );
		if ( empty( $files_to_scan ) ) {
			return;
		}

		// If issue limiting is enabled, process files individually
		if ( $issue_limit !== null && $issue_limit > 0 ) {
			$this->run_with_issue_limit( $result, $files_to_scan, $issue_limit );
			return;
		}

		// Original behavior - process all files at once
		$this->run_phpcs_on_files( $result, $files_to_scan );
	}

	/**
	 * Run PHPCS with issue limiting - processes files one at a time until limit is reached.
	 *
	 * @param Check_Result $result The check result to amend.
	 * @param array $files_to_scan Array of files to scan.
	 * @param int $issue_limit Maximum number of issues to find.
	 */
	private function run_with_issue_limit( Check_Result $result, $files_to_scan, $issue_limit ) {
		if ( count( $files_to_scan ) === 1 && is_dir( $files_to_scan[0] ) ) {
			$files_to_scan = $this->get_php_files( $files_to_scan[0] );
		}

		foreach ( $files_to_scan as $file_path ) {
			if ( ( $result->get_error_count() + $result->get_warning_count() ) >= $issue_limit ) {
				break;
			}

			$this->run_phpcs_on_files( $result, array( $file_path ) );
		}
	}

	/**
	 * Run PHPCS on specified files (original behavior)
	 *
	 * @param Check_Result $result The check result to amend
	 * @param array $files_to_scan Array of files to scan
	 */
	private function run_phpcs_on_files( Check_Result $result, $files_to_scan ) {
		// Backup the original command line arguments.
		$orig_cmd_args = $_SERVER['argv'] ?? '';

		$args = $this->get_args( $result );

		// Reset PHP_CodeSniffer config.
		$this->reset_php_codesniffer_config();

		// Get current installed_paths config.
		$installed_paths = Config::getConfigData( 'installed_paths' );

		// Override installed_paths to load custom sniffs.
		if ( isset( $args['installed_paths'] ) && is_array( $args['installed_paths'] ) ) {
			Config::setConfigData( 'installed_paths', implode( ',', $args['installed_paths'] ), true );
		}

		// Create the default arguments for PHPCS.
		$defaults = $this->get_argv_defaults( $result, $files_to_scan );

		// Set the check arguments for PHPCS.
		$_SERVER['argv'] = $this->parse_argv( $args, $defaults );

		// Run PHPCS.
		try {
			ob_start();
			$runner = new Runner();
			$runner->runPHPCS();
			$reports = ob_get_clean();
		} catch ( Exception $e ) {
			$_SERVER['argv'] = $orig_cmd_args;
			throw $e;
		}

		// Reset installed_paths.
		Config::setConfigData( 'installed_paths', $installed_paths, true );

		// Restore original arguments.
		$_SERVER['argv'] = $orig_cmd_args;

		// Parse the reports into data to add to the overall $result.
		$reports = json_decode( trim( $reports ), true );

		if ( empty( $reports['files'] ) ) {
			return;
		}

		// Instantiate Hash_Generator once outside the loop (Bottleneck #4 fix)
		$hash_generator = null;
		if ( class_exists( 'WordPress\\Plugin_Check\\Verification\\Hash_Generator' ) ) {
			try {
				$hash_generator = new \WordPress\Plugin_Check\Verification\Hash_Generator();
			} catch ( Exception $e ) {
				// Silent fail
			}
		}

		foreach ( $reports['files'] as $file_name => $file_results ) {
			if ( empty( $file_results['messages'] ) ) {
				continue;
			}

			if ( $hash_generator ) {
				try {
					$hash_generator->generate_file_hash( $file_name );
				} catch ( Exception $e ) {
					// Silent fail
				}
			}

			foreach ( $file_results['messages'] as $file_message ) {
				$this->add_result_message_for_file(
					$result,
					strtoupper( $file_message['type'] ) === 'ERROR',
					esc_html( $file_message['message'] ),
					$file_message['source'],
					$file_name,
					$file_message['line'],
					$file_message['column'],
					'',
					$file_message['severity']
				);
			}
		}
	}

	/**
	 * Parse the command arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array $argv     An array of arguments to pass.
	 * @param array $defaults An array of default arguments.
	 * @return array An indexed array of PHPCS CLI arguments.
	 */
	private function parse_argv( $argv, $defaults ) {
		// Only accept allowed PHPCS arguments from check arguments array.
		$check_args = array_intersect_key( $argv, $this->allowed_args );

		// Format check arguments for PHPCS.
		foreach ( $check_args as $key => $value ) {
			if ( 'runtime-set' === $key ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $item_key => $item_value ) {
						$defaults = array_merge( $defaults, array( "--{$key}", $item_key, $item_value ) );
					}
				}
			} else {
				$defaults[] = "--{$key}=$value";
			}
		}

		return $defaults;
	}

	/**
	 * Gets files that need to be scanned, excluding files ignored via the
	 * file-level ignore system in `.wpv-verification.json`.
	 *
	 * Passes the full plugin directory to PHPCS (preserving all check types)
	 * and appends ignored file paths as additional --ignore patterns.
	 * Stale ignores (hash mismatch) are automatically cleared.
	 *
	 * @param Check_Result $result The check result context.
	 * @return array Array containing the plugin directory path to scan.
	 */
	private function get_files_to_scan( Check_Result $result ) {
		try {
			$plugin_path      = $result->plugin()->location();
			$plugin_path_norm = rtrim( str_replace( '\\', '/', $plugin_path ), '/' );

			// Load ignored_files from .wpv-verification.json.
			$verification_file = $plugin_path . '/.wpv-verification.json';
			$ignored_files     = array();
			$verification_data = array();
			if ( file_exists( $verification_file ) ) {
				$decoded = json_decode( file_get_contents( $verification_file ), true );
				if ( is_array( $decoded ) ) {
					$verification_data = $decoded;
					$ignored_files     = $decoded['ignored_files'] ?? array();
				}
			}

			if ( empty( $ignored_files ) ) {
				return array( $plugin_path );
			}

			// Validate hashes — clear stale entries, collect valid ignore patterns.
			$stale_cleared    = false;
			$ignore_patterns  = array();

			foreach ( $ignored_files as $relative_path => $entry ) {
				$absolute_path = $plugin_path_norm . '/' . $relative_path;
				$stored_hash   = $entry['hash'] ?? '';
				$current_hash  = file_exists( $absolute_path ) ? md5_file( $absolute_path ) : '';

				if ( $stored_hash !== '' && $stored_hash === $current_hash ) {
					// Hash matches — add as PHPCS ignore pattern.
					$ignore_patterns[] = $absolute_path;
				} else {
					// Hash mismatch or empty — clear the stale entry.
					unset( $verification_data['ignored_files'][ $relative_path ] );
					$stale_cleared = true;
				}
			}

			// Persist cleared stale ignores.
			if ( $stale_cleared ) {
				file_put_contents(
					$verification_file,
					wp_json_encode( $verification_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
				);
			}

			// Register valid ignored files as additional PHPCS ignore patterns.
			if ( ! empty( $ignore_patterns ) ) {
				add_filter(
					'wp_plugin_check_ignore_files',
					static function ( $files ) use ( $ignore_patterns, $plugin_path_norm ) {
						$glob_patterns = array();
						foreach ( $ignore_patterns as $absolute ) {
							// Strip plugin root to get relative path, then build a
							// forward-slash glob pattern PHPCS fnmatch() can handle.
							$relative = ltrim(
								str_replace( $plugin_path_norm, '', str_replace( '\\', '/', $absolute ) ),
								'/'
							);
							// Use the full forward-slash path as the pattern so PHPCS
							// can match it after normalising its own paths.
							$glob_patterns[] = $plugin_path_norm . '/' . $relative;
						}
						return array_unique( array_merge( $files, $glob_patterns ) );
					}
				);
			}

			// Always return the full plugin directory so all check types run.
			return array( $plugin_path );

		} catch ( Exception $e ) {
			return array( $result->plugin()->location() );
		}
	}

	/**
	 * Get ignored paths from plugin config file
	 *
	 * @param string $plugin_path Plugin directory path
	 * @return array Array of ignored paths
	 */
	private function get_config_ignored_paths( $plugin_path ) {
		$ignored_paths = array();
		$config_file = $plugin_path . '/.wpv-config.json';
		
		if ( ! file_exists( $config_file ) ) {
			return $ignored_paths;
		}
		
		$config_content = file_get_contents( $config_file );
		if ( false === $config_content ) {
			return $ignored_paths;
		}
		
		$config_data = json_decode( $config_content, true );
		if ( null === $config_data ) {
			return $ignored_paths;
		}
		
		if ( ! isset( $config_data['ignored_paths'] ) || ! is_array( $config_data['ignored_paths'] ) ) {
			return $ignored_paths;
		}
		
		foreach ( $config_data['ignored_paths'] as $ignored_path_data ) {
			if ( isset( $ignored_path_data['path'] ) ) {
				$ignored_paths[] = $ignored_path_data['path'];
			}
		}
		
		return $ignored_paths;
	}

	/**
	 * Get all PHP files in directory (respecting ignore patterns)
	 *
	 * @param string $plugin_path Plugin directory path
	 * @return array Array of PHP file paths
	 */
	private function get_php_files( $plugin_path ) {
		$php_files = array();
		$directories_to_ignore = Plugin_Request_Utility::get_directories_to_ignore();
		$files_to_ignore = Plugin_Request_Utility::get_files_to_ignore();
		
		// Load ignored paths from config file
		$config_ignored_paths = $this->get_config_ignored_paths( $plugin_path );
		
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $plugin_path, \RecursiveDirectoryIterator::SKIP_DOTS )
		);
		
		foreach ( $iterator as $file ) {
			if ( $file->getExtension() !== 'php' ) {
				continue;
			}
			
			$file_path = $file->getPathname();
			// Normalize path separators for consistent comparison
			$relative_path = str_replace( array( $plugin_path . '/', $plugin_path . '\\' ), '', str_replace( '\\', '/', $file_path ) );
			
			// Check ignore patterns
			$should_ignore = false;
			
			// Check directory ignores from Plugin_Request_Utility
			foreach ( $directories_to_ignore as $ignore_dir ) {
				if ( strpos( $relative_path, $ignore_dir . '/' ) === 0 ) {
					$should_ignore = true;
					break;
				}
			}
			
			// Check file ignores from Plugin_Request_Utility
			if ( ! $should_ignore ) {
				foreach ( $files_to_ignore as $ignore_file ) {
					if ( basename( $file_path ) === $ignore_file ) {
						$should_ignore = true;
						break;
					}
				}
			}
			
			// Check config ignored paths
			if ( ! $should_ignore ) {
				foreach ( $config_ignored_paths as $ignored_path ) {
					// Normalize ignored path for comparison
					$normalized_ignored_path = str_replace( '\\', '/', $ignored_path );
					
					if ( strpos( $relative_path, $normalized_ignored_path . '/' ) === 0 ) {
						$should_ignore = true;
						break;
					}
				}
			}
			
			if ( ! $should_ignore ) {
				$php_files[] = $file_path;
			}
		}
		
		return $php_files;
	}

	/**
	 * Gets the default command arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param Check_Result $result The check result to amend, including the plugin context to check.
	 * @param array        $files_to_scan Optional. Specific files to scan instead of entire plugin.
	 * @return array An indexed array of PHPCS CLI arguments.
	 */
	private function get_argv_defaults( Check_Result $result, $files_to_scan = null ): array {
		// Use specific files if provided, otherwise scan entire plugin
		$scan_target = $files_to_scan ? implode( ',', $files_to_scan ) : $result->plugin()->location();
		
		$defaults = array(
			'',
			$scan_target,
			'--report=Json',
			'--report-width=9999',
		);

		$ignore_patterns = array();

		$directories_to_ignore = Plugin_Request_Utility::get_directories_to_ignore();
		$files_to_ignore       = Plugin_Request_Utility::get_files_to_ignore();

		// Only add ignore patterns if scanning entire plugin (not specific files)
		// Check if we're scanning the entire plugin directory vs specific files
		$scanning_entire_plugin = ( null === $files_to_scan || 
									( count( $files_to_scan ) === 1 && is_dir( $files_to_scan[0] ) ) );
		
		if ( $scanning_entire_plugin ) {
			// Ignore directories.
			if ( ! empty( $directories_to_ignore ) ) {
				$dir_pattern = '*/' . implode( '/*,*/', $directories_to_ignore ) . '/*';
				$ignore_patterns[] = $dir_pattern;
			}

			// Ignore files.
			if ( ! empty( $files_to_ignore ) ) {
				$ignore_patterns[] = implode( ',', $files_to_ignore );
			}
			
			// Add config ignored paths
			$plugin_path = $result->plugin()->location();
			$config_ignored_paths = $this->get_config_ignored_paths( $plugin_path );
			
			if ( ! empty( $config_ignored_paths ) ) {
				foreach ( $config_ignored_paths as $config_path ) {
					// Convert config path to PHPCS ignore pattern
					$phpcs_pattern = '*/' . $config_path . '/*';
					$ignore_patterns[] = $phpcs_pattern;
				}
			}

			if ( ! empty( $ignore_patterns ) ) {
				$final_ignore_string = '--ignore=' . implode( ',', $ignore_patterns );
				$defaults[] = $final_ignore_string;
			}
		}

		// Set the Minimum WP version supported for the plugin.
		if ( $result->plugin()->minimum_supported_wp() ) {
			// Due to the syntax of runtime-set, these must be passed as individual args.
			$defaults[] = '--runtime-set';
			$defaults[] = 'minimum_wp_version';
			$defaults[] = $result->plugin()->minimum_supported_wp();
		}

		return $defaults;
	}

	/**
	 * Resets \PHP_CodeSniffer\Config::$overriddenDefaults to prevent
	 * incorrect results when running multiple checks.
	 *
	 * @since 1.0.0
	 */
	private function reset_php_codesniffer_config() {
		if ( class_exists( Config::class ) ) {
			/*
			 * PHPStan ignore reason: PHPStan raised an issue because we can't
			 * use class in ReflectionClass.
			 *
			 * @phpstan-ignore-next-line
			 */
			$reflected_phpcs_config = new \ReflectionClass( Config::class );
			$overridden_defaults    = $reflected_phpcs_config->getProperty( 'overriddenDefaults' );

			/*
			 * The setAccessible function has no effect in PHP >= 8.1, and it is marked as deprecated in PHP 8.5.
			 * Since the tests are also run on PHP 7.4 and 8.0, we can only call the function if the php version is lower than 8.1.
			*/
			if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
				$overridden_defaults->setAccessible( true );
			}
			$overridden_defaults->setValue( $reflected_phpcs_config, array() );

			if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
				$overridden_defaults->setAccessible( false );
			}
		}
	}
}
