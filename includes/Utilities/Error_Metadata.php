<?php
/**
 * Error Metadata Helper
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Manages enhanced metadata for PHPCS error codes
 */
class Error_Metadata {

	/**
	 * Cached metadata
	 */
	private static $metadata = null;

	/**
	 * Load metadata configuration
	 */
	private static function load_metadata() {
		if ( null === self::$metadata ) {
			$config_file = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'error-metadata-config.json';
			if ( file_exists( $config_file ) ) {
				$json = file_get_contents( $config_file );
				self::$metadata = json_decode( $json, true ) ?: array();
			} else {
				self::$metadata = array();
			}
		}
		return self::$metadata;
	}

	/**
	 * Get metadata for a specific error code
	 */
	public static function get_metadata( $error_code ) {
		$metadata = self::load_metadata();
		return isset( $metadata[ $error_code ] ) ? $metadata[ $error_code ] : null;
	}

	/**
	 * Get all metadata entries
	 */
	public static function get_all_metadata() {
		return self::load_metadata();
	}

	/**
	 * Get icon HTML for error code
	 */
	public static function get_icon_html( $error_code ) {
		$metadata = self::get_metadata( $error_code );
		if ( ! $metadata || ! isset( $metadata['icon'] ) ) {
			return '<span class="dashicons dashicons-warning" style="color: #666;"></span>';
		}

		$icon = $metadata['icon'];
		$color = $metadata['color'] ?? '#666';
		
		return sprintf(
			'<span class="dashicons dashicons-%s" style="color: %s;" title="%s"></span>',
			esc_attr( $icon ),
			esc_attr( $color ),
			esc_attr( $metadata['description'] ?? $error_code )
		);
	}
}