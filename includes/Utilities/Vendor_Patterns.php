<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Vendor_Patterns
 *
 * @package WPVerifier
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Centralized vendor folder pattern list for detection across the plugin.
 */
class Vendor_Patterns {

	/**
	 * Common vendor/library folder patterns.
	 */
	const PATTERNS = array( 'vendor', 'vendors', 'library', 'libraries' );

	/**
	 * Asset directory patterns where JS libraries are commonly stored.
	 */
	const ASSET_PATTERNS = array(
		'assets/js/libs',
		'assets/js/vendor',
		'assets/js/libraries',
		'assets/libraries',
		'assets/vendor',
		'js/vendor',
		'js/libs',
		'js/libraries',
		'lib',
		'libs'
	);

	/**
	 * Get vendor folder patterns.
	 *
	 * @return array List of vendor folder patterns.
	 */
	public static function get_patterns() {
		return self::PATTERNS;
	}

	/**
	 * Get asset directory patterns.
	 *
	 * @return array List of asset directory patterns.
	 */
	public static function get_asset_patterns() {
		return self::ASSET_PATTERNS;
	}

	/**
	 * Get all patterns (vendor + asset).
	 *
	 * @return array Combined list of all patterns.
	 */
	public static function get_all_patterns() {
		return array_merge( self::PATTERNS, self::ASSET_PATTERNS );
	}
}
