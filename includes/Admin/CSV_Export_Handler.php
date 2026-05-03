<?php
/**
 * CSV Export Handler
 *
 * Handles the admin-post action that streams verification results as a CSV download.
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Admin;

use WordPress\Plugin_Check\Utilities\Path_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV Export Handler
 *
 * Registers an admin_post action that reads the saved .wpv-results.json for the
 * currently-selected plugin and streams it as a UTF-8 CSV download.
 *
 * Columns: file_path, line, column, code, type, message, status, scan_date
 */
final class CSV_Export_Handler {

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public static function add_hooks() {
		add_action( 'admin_post_wpv_export_csv', array( __CLASS__, 'export_csv' ) );
	}

	/**
	 * Stream the verification results CSV to the browser.
	 *
	 * Validates nonce and capability before reading the results file.
	 * Terminates the request via exit after streaming.
	 *
	 * @return void
	 */
	public static function export_csv() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wpverifier' ) );
		}

		$plugin_slug = isset( $_GET['plugin'] ) ? sanitize_text_field( wp_unslash( $_GET['plugin'] ) ) : '';
		if ( empty( $plugin_slug ) ) {
			wp_die( esc_html__( 'Plugin parameter is required.', 'wpverifier' ) );
		}

		check_admin_referer( 'wpv_export_csv_' . $plugin_slug );

		$results_file = Path_Builder::get_results_file_path( $plugin_slug );
		if ( ! $results_file || ! file_exists( $results_file ) ) {
			wp_die( esc_html__( 'No results file found for this plugin. Please run a verification first.', 'wpverifier' ) );
		}

		$raw = file_get_contents( $results_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) || empty( $data['results'] ) ) {
			wp_die( esc_html__( 'Results file is empty or invalid.', 'wpverifier' ) );
		}

		$scan_date = isset( $data['generated_at'] ) ? sanitize_text_field( $data['generated_at'] ) : '';
		$filename  = sanitize_file_name( str_replace( '/', '-', $plugin_slug ) ) . '-issues-' . gmdate( 'Ymd-His' ) . '.csv';

		// Output headers — no buffering allowed before this point.
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		// UTF-8 BOM so Excel opens the file correctly.
		fwrite( $output, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

		fputcsv( $output, array( 'file_path', 'line', 'column', 'code', 'type', 'message', 'status', 'scan_date' ) );

		foreach ( $data['results'] as $file_path => $issues ) {
			if ( ! is_array( $issues ) ) {
				continue;
			}
			foreach ( $issues as $issue ) {
				if ( ! empty( $issue['ignored'] ) ) {
					$status = 'ignored';
				} elseif ( ! empty( $issue['resolved'] ) ) {
					$status = 'fixed';
				} else {
					$status = 'open';
				}

				// Strip HTML entities from the message before writing to CSV.
				$message = html_entity_decode( $issue['message'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8' );

				fputcsv(
					$output,
					array(
						$file_path,
						$issue['line']   ?? '',
						$issue['column'] ?? '',
						$issue['code']   ?? '',
						$issue['type']   ?? '',
						$message,
						$status,
						$scan_date,
					)
				);
			}
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}
