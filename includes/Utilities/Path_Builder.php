<?php
/**
 * Centralized Path Building Utility for WPVerifier
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Path Builder Utility Class
 * 
 * Provides centralized, consistent path building for all plugin file operations.
 * Eliminates multiple scattered approaches and illogical defaults.
 */
class Path_Builder {
	
	/**
	 * Build absolute path to plugin file
	 * 
	 * @param string $plugin_slug Plugin slug (e.g., 'makeiteasy-slider/makeiteasy-slider.php')
	 * @param string $file_path   Relative file path within plugin (e.g., 'build/slide/render.php')
	 * @return string|false Absolute path or false if invalid
	 */
	public static function get_plugin_file_path( $plugin_slug, $file_path = '' ) {
		if ( empty( $plugin_slug ) ) {
			return false;
		}
		
		$plugin_folder = self::extract_plugin_folder( $plugin_slug );
		if ( ! $plugin_folder ) {
			return false;
		}
		
		$base_path = WP_PLUGIN_DIR . '/' . $plugin_folder;
		
		if ( empty( $file_path ) ) {
			return $base_path;
		}
		
		return $base_path . '/' . ltrim( $file_path, '/' );
	}
	
	/**
	 * Build VSCode URL for opening files
	 * 
	 * @param string $plugin_slug Plugin slug
	 * @param string $file_path   Relative file path within plugin
	 * @param int    $line        Line number (optional)
	 * @param int    $column      Column number (optional)
	 * @return string|false VSCode URL or false if invalid
	 */
	public static function get_vscode_url( $plugin_slug, $file_path, $line = 0, $column = 0 ) {
		$absolute_path = self::get_plugin_file_path( $plugin_slug, $file_path );
		if ( ! $absolute_path ) {
			return false;
		}
		
		// Normalize path separators for VSCode URI (always use forward slashes)
		$absolute_path = str_replace( DIRECTORY_SEPARATOR, '/', $absolute_path );
		
		$vscode_url = 'vscode://file/' . $absolute_path;
		
		if ( $line > 0 ) {
			$vscode_url .= ':' . $line;
			if ( $column > 0 ) {
				$vscode_url .= ':' . $column;
			}
		}
		
		return $vscode_url;
	}
	
	/**
	 * Build path to plugin results file
	 * 
	 * @param string $plugin_slug Plugin slug
	 * @return string|false Results file path or false if invalid
	 */
	public static function get_results_file_path( $plugin_slug ) {
		return self::get_plugin_file_path( $plugin_slug, '.wpv-results.json' );
	}
	
	/**
	 * Build path to plugin config file
	 * 
	 * @param string $plugin_slug Plugin slug
	 * @return string|false Config file path or false if invalid
	 */
	public static function get_config_file_path( $plugin_slug ) {
		return self::get_plugin_file_path( $plugin_slug, '.wpv-config.json' );
	}
	
	/**
	 * Build path to plugin verification file
	 * 
	 * @param string $plugin_slug Plugin slug
	 * @return string|false Verification file path or false if invalid
	 */
	public static function get_verification_file_path( $plugin_slug ) {
		return self::get_plugin_file_path( $plugin_slug, '.wpv-verification.json' );
	}
	
	/**
	 * Get plugin directory path (alias for get_plugin_file_path with no file)
	 * 
	 * @param string $plugin_slug Plugin slug
	 * @return string|false Plugin directory path or false if invalid
	 */
	public static function get_plugin_directory_path( $plugin_slug ) {
		return self::get_plugin_file_path( $plugin_slug );
	}
	
	/**
	 * Extract plugin folder from plugin slug
	 * 
	 * @param string $plugin_slug Plugin slug (e.g., 'makeiteasy-slider/makeiteasy-slider.php')
	 * @return string|false Plugin folder name or false if invalid
	 */
	private static function extract_plugin_folder( $plugin_slug ) {
		if ( empty( $plugin_slug ) ) {
			return false;
		}
		
		// Handle both 'folder/file.php' and 'folder' formats
		return strpos( $plugin_slug, '/' ) !== false ? dirname( $plugin_slug ) : $plugin_slug;
	}
	
	/**
	 * Validate if plugin exists
	 * 
	 * @param string $plugin_slug Plugin slug
	 * @return bool True if plugin directory exists
	 */
	public static function plugin_exists( $plugin_slug ) {
		$plugin_path = self::get_plugin_file_path( $plugin_slug );
		return $plugin_path && is_dir( $plugin_path );
	}
	
	/**
	 * Validate if plugin file exists
	 * 
	 * @param string $plugin_slug Plugin slug
	 * @param string $file_path   Relative file path within plugin
	 * @return bool True if file exists
	 */
	public static function plugin_file_exists( $plugin_slug, $file_path ) {
		$absolute_path = self::get_plugin_file_path( $plugin_slug, $file_path );
		return $absolute_path && file_exists( $absolute_path );
	}
	
	/**
	 * Get current selected plugin slug from user meta
	 * 
	 * @return string|false Current plugin slug or false if none selected
	 */
	public static function get_current_plugin_slug() {
		return get_user_meta( get_current_user_id(), 'wpv_last_selected_plugin', true ) ?: false;
	}
	
	/**
	 * Build VSCode URL using current selected plugin
	 * 
	 * @param string $file_path Relative file path within current plugin
	 * @param int    $line      Line number (optional)
	 * @param int    $column    Column number (optional)
	 * @return string|false VSCode URL or false if no current plugin
	 */
	public static function get_current_plugin_vscode_url( $file_path, $line = 0, $column = 0 ) {
		$current_plugin = self::get_current_plugin_slug();
		if ( ! $current_plugin ) {
			return false;
		}
		
		return self::get_vscode_url( $current_plugin, $file_path, $line, $column );
	}
}