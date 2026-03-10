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
 * 
 * 
 */
function wpverifier_header( $text, $code = '', $inline_only = false ) {
	$show_codes = get_option( 'wpverifier_show_header_codes', false );

	$used = array(
		// Tab Headers (TABxx)
		'TAB01'         => 'Select Plugin tab main header',
		'TAB02'         => 'Hash Generation tab main header',
		'TAB02-IGNORED' => 'Files Actually Ignored During Processing (Advanced Verification)',
		'TAB12-01'      => 'Plugin Check Architecture main header',
		'TAB12-02'      => 'Verification Process Flow',
		'TAB12-03'      => 'Live Configuration Validation',
		'TAB12-04'      => 'Key Functions & Files',
		'TAB12-05'      => 'Function/Method table header',
		'TAB12-06'      => 'File Location table header',
		'TAB12-07'      => 'Purpose & Configuration table header',
		'TAB12-08'      => 'Current Issue: ActionScheduler.php Being Scanned',
		'TAB12-09A'     => 'Files Actually Ignored During Processing (Architecture)',
		'TAB12-09B'     => 'Investigation Steps',
		'TAB12-10'      => 'Development Guidance',

		// Panel Headers (PANxx)
		'PAN00'         => 'File Details (accordion panel)',
		'PAN01'         => 'Selected Issue Details (accordion panel)',
		'PAN02'         => 'AI Prompt (accordion panel)',
		'PAN03'         => 'Plugin Information (Select Plugin tab)',

		// File/Feature Headers (FTxx)
		'FT01'          => 'FILE: (results template)',
		'FT02'          => 'Files with Issues (saved results)',
	);

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
