<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Structure_Validator
 *
 * @package WPVerifier
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Validates plugin structure for required files and folders.
 */
class Structure_Validator {

	/**
	 * Validate plugin structure.
	 *
	 * @param string $plugin_slug Plugin slug.
	 * @return array Validation results.
	 */
	public static function validate( $plugin_slug ) {
		$plugin_dir = WP_PLUGIN_DIR . '/' . dirname( $plugin_slug );
		
		return array(
			'language_folder' => self::check_language_folder( $plugin_dir ),
			'language_files'  => self::check_language_files( $plugin_dir ),
			'license_file'    => self::check_license_file( $plugin_dir ),
			'readme_file'     => self::check_readme_file( $plugin_dir ),
		);
	}

	/**
	 * Check for language folder.
	 *
	 * @param string $plugin_dir Plugin directory path.
	 * @return array Check result.
	 */
	private static function check_language_folder( $plugin_dir ) {
		$folders = array( 'languages', 'lang' );
		foreach ( $folders as $folder ) {
			if ( is_dir( $plugin_dir . '/' . $folder ) ) {
				return array( 'status' => 'pass', 'path' => $folder );
			}
		}
		return array( 'status' => 'fail', 'message' => 'No language folder found' );
	}

	/**
	 * Check for language files.
	 *
	 * @param string $plugin_dir Plugin directory path.
	 * @return array Check result.
	 */
	private static function check_language_files( $plugin_dir ) {
		$folders = array( 'languages', 'lang' );
		$files   = array();
		
		foreach ( $folders as $folder ) {
			$lang_dir = $plugin_dir . '/' . $folder;
			if ( is_dir( $lang_dir ) ) {
				$pot_files = glob( $lang_dir . '/*.pot' );
				if ( ! empty( $pot_files ) ) {
					return array( 'status' => 'pass', 'count' => count( $pot_files ) );
				}
			}
		}
		return array( 'status' => 'warning', 'message' => 'No .pot file found' );
	}

	/**
	 * Check for license file.
	 *
	 * @param string $plugin_dir Plugin directory path.
	 * @return array Check result.
	 */
	private static function check_license_file( $plugin_dir ) {
		$files = array( 'LICENSE', 'LICENSE.txt', 'LICENSE.md', 'license.txt', 'license.md' );
		foreach ( $files as $file ) {
			if ( file_exists( $plugin_dir . '/' . $file ) ) {
				return array( 'status' => 'pass', 'file' => $file );
			}
		}
		return array( 'status' => 'warning', 'message' => 'No LICENSE file found' );
	}

	/**
	 * Check for readme file.
	 *
	 * @param string $plugin_dir Plugin directory path.
	 * @return array Check result.
	 */
	private static function check_readme_file( $plugin_dir ) {
		$files = array( 'readme.txt', 'README.md', 'readme.md' );
		foreach ( $files as $file ) {
			if ( file_exists( $plugin_dir . '/' . $file ) ) {
				return array( 'status' => 'pass', 'file' => $file );
			}
		}
		return array( 'status' => 'fail', 'message' => 'No README file found' );
	}
}
