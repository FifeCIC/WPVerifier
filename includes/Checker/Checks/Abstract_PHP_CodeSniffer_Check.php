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
use WordPress\Plugin_Check\Verification\Hash_Generator;

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
	 *
	 * @throws Exception Thrown when the check fails with a critical error (unrelated to any errors detected as part of
	 *                   the check).
	 */
	final public function run( Check_Result $result ) {
		// Include the PHPCS autoloader.
		$autoloader = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'vendor/squizlabs/php_codesniffer/autoload.php';

		if ( file_exists( $autoloader ) ) {
			include_once $autoloader;
		}

		if ( ! class_exists( Runner::class ) ) {
			throw new Exception(
				__( 'Unable to find PHPCS Runner class.', 'wp-verifier' )
			);
		}

		if ( ! class_exists( Config::class ) ) {
			throw new Exception(
				__( 'Unable to find PHPCS Config class.', 'wp-verifier' )
			);
		}

		// Step 1: Check for unchanged files and skip them
		$files_to_scan = $this->get_files_to_scan( $result );
		if ( empty( $files_to_scan ) ) {
			// All files unchanged, skip PHPCS entirely
			return;
		}

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

		foreach ( $reports['files'] as $file_name => $file_results ) {
			if ( empty( $file_results['messages'] ) ) {
				continue;
			}

			// Step 2: Generate hashes for files with issues (non-breaking logging)
			if ( class_exists( 'WordPress\\Plugin_Check\\Verification\\Hash_Generator' ) ) {
				try {
					$hash_generator = new \WordPress\Plugin_Check\Verification\Hash_Generator();
					$file_hash = $hash_generator->generate_file_hash( $file_name );
					// Hash generated silently for verification tracking
				} catch ( Exception $e ) {
					// Silent fail - don't break existing functionality
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
	 * Gets files that need to be scanned (changed files only)
	 *
	 * @param Check_Result $result The check result context
	 * @return array Array of file paths that need scanning
	 */
	private function get_files_to_scan( Check_Result $result ) {
		try {
			$hash_generator = new Hash_Generator();
			$plugin_path = $result->plugin()->location();
			$results_file = $plugin_path . '/.wpv-results.json';
			
			// Load existing hashes
			$existing_hashes = array();
			$has_existing_results = false;
			if ( file_exists( $results_file ) ) {
				$data = json_decode( file_get_contents( $results_file ), true );
				if ( isset( $data['file_hashes'] ) && ! empty( $data['file_hashes'] ) ) {
					$existing_hashes = $data['file_hashes'];
					$has_existing_results = true;
				}
			}
			
			// If no existing results, scan all files (first run)
			if ( ! $has_existing_results ) {
				return array( $plugin_path ); // Scan entire plugin
			}
			
			// Get all PHP files in plugin
			$php_files = $this->get_php_files( $plugin_path );
			$changed_files = array();
			
			foreach ( $php_files as $file_path ) {
				$current_hash = $hash_generator->generate_file_hash( $file_path );
				$relative_path = str_replace( $plugin_path . '/', '', $file_path );
				
				// Compare with existing hash
				if ( ! isset( $existing_hashes[ $relative_path ] ) || 
					 $existing_hashes[ $relative_path ] !== $current_hash ) {
					$changed_files[] = $file_path;
				}
			}
			
			// If no files changed, still return plugin path to maintain compatibility
			// This ensures we don't break existing functionality
			return empty( $changed_files ) ? array( $plugin_path ) : $changed_files;
			
		} catch ( Exception $e ) {
			// On error, scan all files (fallback to original behavior)
			return array( $result->plugin()->location() );
		}
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
		
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $plugin_path, \RecursiveDirectoryIterator::SKIP_DOTS )
		);
		
		foreach ( $iterator as $file ) {
			if ( $file->getExtension() !== 'php' ) {
				continue;
			}
			
			$file_path = $file->getPathname();
			$relative_path = str_replace( $plugin_path . '/', '', $file_path );
			
			// Check ignore patterns
			$should_ignore = false;
			
			// Check directory ignores
			foreach ( $directories_to_ignore as $ignore_dir ) {
				if ( strpos( $relative_path, $ignore_dir . '/' ) === 0 ) {
					$should_ignore = true;
					break;
				}
			}
			
			// Check file ignores
			if ( ! $should_ignore ) {
				foreach ( $files_to_ignore as $ignore_file ) {
					if ( basename( $file_path ) === $ignore_file ) {
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
		if ( null === $files_to_scan ) {
			// Ignore directories.
			if ( ! empty( $directories_to_ignore ) ) {
				$ignore_patterns[] = '*/' . implode( '/*,*/', $directories_to_ignore ) . '/*';
			}

			// Ignore files.
			if ( ! empty( $files_to_ignore ) ) {
				$ignore_patterns[] = '/' . implode( ',/', $files_to_ignore );
			}

			if ( ! empty( $ignore_patterns ) ) {
				$defaults[] = '--ignore=' . implode( ',', $ignore_patterns );
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
