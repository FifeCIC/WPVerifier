<?php
/**
 * Helper functions for WPVerifier
 *
 * @package wp-verifier
 */

/**
 * Output a header with optional identifier code
 *
 * @param string $text Header text
 * @param string $code Identifier code (e.g., 'PAN01')
 * @param bool $inline_only If true, outputs non-clickable inline code for tabs
 * @return void
 */
function wpverifier_header( $text, $code = '', $inline_only = false ) {
	$show_codes = get_option( 'wpverifier_show_header_codes', false );
	
	if ( $show_codes && ! empty( $code ) ) {
		if ( $inline_only ) {
			echo esc_html( $text ) . ' <code style="font-size: 0.7em; color: #666;">' . esc_html( $code ) . '</code>';
		} else {
			$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 1 );
			$caller_file = $backtrace[0]['file'] ?? '';
			$caller_line = $backtrace[0]['line'] ?? '';
			$vscode_link = 'vscode://file/' . $caller_file . ':' . $caller_line;
			echo esc_html( $text ) . ' <a href="' . esc_url( $vscode_link ) . '" style="text-decoration: none;"><code style="font-size: 0.7em; color: #666; cursor: pointer; vertical-align: baseline;">' . esc_html( $code ) . '</code></a>';
		}
	} else {
		echo esc_html( $text );
	}
}
