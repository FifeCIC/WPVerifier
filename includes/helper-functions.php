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
 * @return void
 */
function wpverifier_header( $text, $code = '' ) {
	$show_codes = get_option( 'wpverifier_show_header_codes', false );
	
	if ( $show_codes && ! empty( $code ) ) {
		echo esc_html( $text ) . ' <code style="font-size: 0.7em; color: #666;">' . esc_html( $code ) . '</code>';
	} else {
		echo esc_html( $text );
	}
}
