<?php
/**
 * Class WordPress\Plugin_Check\Admin\Vendor_Detector
 *
 * @package WPVerifier
 */

namespace WordPress\Plugin_Check\Admin;

use WordPress\Plugin_Check\Utilities\Vendor_Patterns;

/**
 * Detects vendor/library folders in plugin directories.
 */
class Vendor_Detector {

	/**
	 * Detect vendor folders in a plugin directory.
	 *
	 * @param string $plugin_slug Plugin slug or basename.
	 * @return array Structured array of vendor folders and their contents.
	 */
	public static function detect_vendors( $plugin_slug ) {
		$plugin_dir = self::get_plugin_directory( $plugin_slug );
		
		if ( ! $plugin_dir || ! is_dir( $plugin_dir ) ) {
			return array();
		}

		$patterns = Vendor_Patterns::get_patterns();
		$results = array();

		// Check root level
		foreach ( $patterns as $pattern ) {
			$vendor_path = $plugin_dir . '/' . $pattern;
			
			if ( is_dir( $vendor_path ) ) {
				$subdirs = self::get_subdirectories( $vendor_path );
				if ( ! empty( $subdirs ) ) {
					$results[ $pattern ] = $subdirs;
				}
			}
		}

		// Check includes/ subdirectory
		$includes_dir = $plugin_dir . '/includes';
		if ( is_dir( $includes_dir ) ) {
			foreach ( $patterns as $pattern ) {
				$vendor_path = $includes_dir . '/' . $pattern;
				
				if ( is_dir( $vendor_path ) ) {
					$subdirs = self::get_subdirectories( $vendor_path );
					if ( ! empty( $subdirs ) ) {
						$results[ 'includes/' . $pattern ] = $subdirs;
					}
				}
			}
		}

		return $results;
	}

	/**
	 * Get subdirectories in a folder.
	 *
	 * @param string $path Directory path.
	 * @return array List of subdirectory names.
	 */
	private static function get_subdirectories( $path ) {
		$subdirs = array();
		$items = scandir( $path );

		if ( false === $items ) {
			return $subdirs;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			$full_path = $path . '/' . $item;
			if ( is_dir( $full_path ) ) {
				$subdirs[] = $item;
			}
		}

		return $subdirs;
	}

	/**
	 * Get plugin directory path from slug.
	 *
	 * @param string $plugin_slug Plugin slug or basename.
	 * @return string|false Plugin directory path or false.
	 */
	private static function get_plugin_directory( $plugin_slug ) {
		if ( strpos( $plugin_slug, '/' ) !== false ) {
			$plugin_slug = dirname( $plugin_slug );
		}

		$plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_slug;

		return is_dir( $plugin_dir ) ? $plugin_dir : false;
	}
}
