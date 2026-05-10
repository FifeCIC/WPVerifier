<?php
/**
 * Class WordPress\Plugin_Check\Admin\JS_Library_Detector
 *
 * @package WPVerifier
 */

namespace WordPress\Plugin_Check\Admin;

use WordPress\Plugin_Check\Utilities\JS_Library_Signatures;
use WordPress\Plugin_Check\Utilities\Path_Builder;

/**
 * Detects JavaScript libraries in plugin files.
 */
class JS_Library_Detector {

	/**
	 * Detect JS libraries in a plugin directory.
	 *
	 * @param string $plugin_slug Plugin slug or basename.
	 * @return array Detected libraries with file paths and versions.
	 */
	public static function detect_libraries( $plugin_slug ) {
		$plugin_dir = self::get_plugin_directory( $plugin_slug );
		
		if ( ! $plugin_dir || ! is_dir( $plugin_dir ) ) {
			return array();
		}

		$js_files = self::find_js_files( $plugin_dir );
		$detected = array();

		foreach ( $js_files as $file_path ) {
			$libraries = self::scan_file( $file_path );
			
			foreach ( $libraries as $library ) {
				$key = $library['key'];
				
				if ( ! isset( $detected[ $key ] ) ) {
					$detected[ $key ] = array(
						'name' => $library['name'],
						'files' => array(),
					);
				}
				
				$detected[ $key ]['files'][] = array(
					'path' => str_replace( $plugin_dir . '/', '', $file_path ),
					'version' => $library['version'],
				);
			}
		}

		return $detected;
	}

	/**
	 * Scan a single JS file for library signatures.
	 *
	 * @param string $file_path Full path to JS file.
	 * @return array Detected libraries in this file.
	 */
	private static function scan_file( $file_path ) {
		$content = file_get_contents( $file_path );
		
		if ( false === $content ) {
			return array();
		}

		// Only scan first 50KB for performance
		$content = substr( $content, 0, 51200 );
		
		$signatures = JS_Library_Signatures::get_signatures();
		$detected = array();

		foreach ( $signatures as $key => $signature ) {
			foreach ( $signature['patterns'] as $pattern ) {
				if ( preg_match( $pattern, $content, $matches ) ) {
					$version = isset( $matches[1] ) ? $matches[1] : 'unknown';
					
					$detected[] = array(
						'key' => $key,
						'name' => $signature['name'],
						'version' => $version,
					);
					
					break; // Found this library, move to next
				}
			}
		}

		return $detected;
	}

	/**
	 * Find all JS files in plugin directory.
	 *
	 * @param string $plugin_dir Plugin directory path.
	 * @return array List of JS file paths.
	 */
	private static function find_js_files( $plugin_dir ) {
		$js_files = array();
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $plugin_dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && preg_match( '/\.js$/i', $file->getFilename() ) ) {
				$js_files[] = $file->getPathname();
			}
		}

		return $js_files;
	}

	/**
	 * Get plugin directory path from slug.
	 *
	 * @param string $plugin_slug Plugin slug or basename.
	 * @return string|false Plugin directory path or false.
	 */
	private static function get_plugin_directory( $plugin_slug ) {
		$plugin_dir = Path_Builder::get_plugin_directory_path( $plugin_slug );
		return is_dir( $plugin_dir ) ? $plugin_dir : false;
	}
}
