<?php
/**
 * Class WordPress\Plugin_Check\Checker\Check_Types
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Checker;

/**
 * Check Type class.
 *
 * @since 1.8.0
 */
class Check_Types {

	// Constants for available check types.
	const TYPE_ERROR   = 'error';
	const TYPE_WARNING = 'warning';

	/**
	 * Returns an array of check types.
	 *
	 * @since 1.8.0
	 * @version 1.9.0 Renamed filter hook to use the wpverifier prefix.
	 *
	 * @return array An array of check types.
	 */
	public static function get_types() {
		$default_types = array(
			self::TYPE_ERROR   => __( 'Error', 'wpverifier' ),
			self::TYPE_WARNING => __( 'Warning', 'wpverifier' ),
		);

		/**
		 * Filters the check types.
		 *
		 * @since 1.8.0
		 * @since 1.9.0 Renamed from 'wp_plugin_check_types' to use plugin prefix.
		 *
		 * @param array<string, string> $default_types Associative array of type slugs to labels.
		 */
		// Prefixed with wpverifier_ to comply with WordPress global naming conventions.
		$check_types = (array) apply_filters( 'wpverifier_check_types', $default_types );

		return $check_types;
	}
}
