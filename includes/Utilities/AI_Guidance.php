<?php
/**
 * AI Guidance Helper
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Manages AI guidance for PHPCS messages
 */
class AI_Guidance {

	/**
	 * Cached guidance data
	 */
	private static $guidance_data = null;

	/**
	 * Load guidance configuration
	 */
	private static function load_guidance() {
		if ( null === self::$guidance_data ) {
			$config_file = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'ai-guidance-config.json';
			if ( file_exists( $config_file ) ) {
				$json = file_get_contents( $config_file );
				self::$guidance_data = json_decode( $json, true ) ?: array();
			} else {
				self::$guidance_data = array();
			}
		}
		return self::$guidance_data;
	}

	/**
	 * Get AI guidance for a specific error code
	 */
	public static function get_guidance( $error_code ) {
		$guidance = self::load_guidance();
		return isset( $guidance[ $error_code ] ) ? $guidance[ $error_code ] : null;
	}

	/**
	 * Get all guidance entries for admin display
	 */
	public static function get_all_guidance() {
		return self::load_guidance();
	}

	/**
	 * Format message with AI guidance for copy-paste
	 */
	public static function format_for_ai( $message, $error_code ) {
		$guidance = self::get_guidance( $error_code );
		if ( $guidance && isset( $guidance['ai_guidance'] ) ) {
			return $message . ' AI: ' . $guidance['ai_guidance'];
		}
		return $message;
	}
}