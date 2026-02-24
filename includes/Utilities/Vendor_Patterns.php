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
	 * Get vendor folder patterns.
	 *
	 * @return array List of vendor folder patterns.
	 */
	public static function get_patterns() {
		return self::PATTERNS;
	}
}
