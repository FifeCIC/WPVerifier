<?php
/**
 * Class WordPress\Plugin_Check\Utilities\File_Monitor
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * File Monitor utility class.
 *
 * @since 1.9.0
 */
class File_Monitor {

	const OPTION_KEY = 'wpv_file_monitor';
	const LOG_KEY = 'wpv_monitor_log';

	/**
	 * Get monitored plugin.
	 *
	 * @return string Plugin basename or empty string.
	 */
	public static function get_monitored_plugin() {
		$data = get_option( self::OPTION_KEY, array() );
		return isset( $data['plugin'] ) ? $data['plugin'] : '';
	}

	/**
	 * Set monitored plugin.
	 *
	 * @param string $plugin_basename Plugin basename.
	 */
	public static function set_monitored_plugin( $plugin_basename ) {
		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_basename;
		$checksums = self::calculate_checksums( $plugin_path );

		update_option( self::OPTION_KEY, array(
			'plugin' => $plugin_basename,
			'checksums' => $checksums,
			'last_check' => time(),
		) );

		self::log( 'Monitoring started for: ' . $plugin_basename );
	}

	/**
	 * Stop monitoring.
	 */
	public static function stop_monitoring() {
		$data = get_option( self::OPTION_KEY, array() );
		if ( isset( $data['plugin'] ) ) {
			self::log( 'Monitoring stopped for: ' . $data['plugin'] );
		}
		delete_option( self::OPTION_KEY );
	}

	/**
	 * Check for file changes.
	 *
	 * @return array|false Array with 'changed' and 'files' keys, or false if no changes.
	 */
	public static function check_changes() {
		$data = get_option( self::OPTION_KEY, array() );
		if ( empty( $data['plugin'] ) ) {
			return false;
		}

		$plugin_path = WP_PLUGIN_DIR . '/' . $data['plugin'];
		$current_checksums = self::calculate_checksums( $plugin_path );
		$stored_checksums = isset( $data['checksums'] ) ? $data['checksums'] : array();

		$changed_files = array();
		foreach ( $current_checksums as $file => $checksum ) {
			if ( ! isset( $stored_checksums[ $file ] ) || $stored_checksums[ $file ] !== $checksum ) {
				$changed_files[] = $file;
			}
		}

		// Check for deleted files
		foreach ( $stored_checksums as $file => $checksum ) {
			if ( ! isset( $current_checksums[ $file ] ) ) {
				$changed_files[] = $file . ' (deleted)';
			}
		}

		if ( ! empty( $changed_files ) ) {
			$data['checksums'] = $current_checksums;
			$data['last_check'] = time();
			update_option( self::OPTION_KEY, $data );

			self::log( 'Changes detected: ' . count( $changed_files ) . ' file(s)' );
			return array(
				'changed' => true,
				'files' => $changed_files,
			);
		}

		$data['last_check'] = time();
		update_option( self::OPTION_KEY, $data );

		return false;
	}

	/**
	 * Calculate checksums for all PHP files in plugin.
	 *
	 * @param string $path Plugin path.
	 * @return array File checksums.
	 */
	private static function calculate_checksums( $path ) {
		$checksums = array();
		$files = self::get_php_files( $path );

		foreach ( $files as $file ) {
			$relative = str_replace( $path . '/', '', $file );
			$checksums[ $relative ] = md5_file( $file );
		}

		return $checksums;
	}

	/**
	 * Get all PHP files in directory.
	 *
	 * @param string $dir Directory path.
	 * @return array File paths.
	 */
	private static function get_php_files( $dir ) {
		$files = array();
		if ( ! is_dir( $dir ) ) {
			return $files;
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && $file->getExtension() === 'php' ) {
				$files[] = $file->getPathname();
			}
		}

		return $files;
	}

	/**
	 * Log monitoring activity.
	 *
	 * @param string $message Log message.
	 */
	public static function log( $message ) {
		$log = get_option( self::LOG_KEY, array() );
		$log[] = array(
			'time' => current_time( 'mysql' ),
			'message' => $message,
		);

		// Keep only last 100 entries
		if ( count( $log ) > 100 ) {
			$log = array_slice( $log, -100 );
		}

		update_option( self::LOG_KEY, $log );
	}

	/**
	 * Get monitoring log.
	 *
	 * @param int $limit Number of entries to return.
	 * @return array Log entries.
	 */
	public static function get_log( $limit = 50 ) {
		$log = get_option( self::LOG_KEY, array() );
		return array_slice( array_reverse( $log ), 0, $limit );
	}

	/**
	 * Clear log.
	 */
	public static function clear_log() {
		delete_option( self::LOG_KEY );
	}
}
