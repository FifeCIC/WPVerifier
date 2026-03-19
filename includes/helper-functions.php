<?php
/**
 * Helper functions for WPVerifier
 *
 * @package wp-verifier
 */

/**
 * Generate VSCode URL for opening a file at specific line
 * 
 * UPDATED: Now uses Path_Builder for consistent path handling
 * Maintains backward compatibility with existing calls
 *
 * @param string $file_path Relative path from plugin directory or absolute path
 * @param int $line Line number (optional)
 * @param int $column Column number (optional)
 * @param string $plugin_folder Plugin folder name (optional, will try to detect)
 * @return string VSCode URL
 */
function wpv_get_vscode_url( $file_path, $line = 0, $column = 0, $plugin_folder = null ) {
	// Load Path_Builder if not already loaded
	if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Path_Builder' ) ) {
		require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Path_Builder.php';
	}
	
	// Determine plugin slug
	if ( $plugin_folder ) {
		// Convert folder to slug format (assume main plugin file has same name)
		$plugin_slug = $plugin_folder . '/' . $plugin_folder . '.php';
		// Check if this plugin file exists, if not try common patterns
		if ( ! \WordPress\Plugin_Check\Utilities\Path_Builder::plugin_file_exists( $plugin_slug, '' ) ) {
			// Try just the folder name as slug
			$plugin_slug = $plugin_folder;
		}
	} else {
		// Get current plugin if not specified
		$plugin_slug = \WordPress\Plugin_Check\Utilities\Path_Builder::get_current_plugin_slug();
		if ( ! $plugin_slug ) {
			return false;
		}
	}
	
	// Use Path_Builder for consistent URL generation
	return \WordPress\Plugin_Check\Utilities\Path_Builder::get_vscode_url( $plugin_slug, $file_path, $line, $column );
}

/**
 * Generate VSCode button HTML
 * 
 * UPDATED: Now uses Path_Builder through wpv_get_vscode_url()
 *
 * @param string $file_path Relative path from plugin directory or absolute path
 * @param int $line Line number (optional)
 * @param int $column Column number (optional)
 * @param string $plugin_folder Plugin folder name (optional)
 * @param string $button_text Button text (optional)
 * @param string $css_class Additional CSS classes (optional)
 * @return string Button HTML
 */
function wpv_get_vscode_button( $file_path, $line = 0, $column = 0, $plugin_folder = null, $button_text = null, $css_class = '' ) {
	if ( ! $button_text ) {
		$button_text = __( 'VSCode', 'wp-verifier' );
	}
	
	$vscode_url = wpv_get_vscode_url( $file_path, $line, $column, $plugin_folder );
	
	// Handle case where URL generation fails
	if ( ! $vscode_url ) {
		return sprintf(
			'<span class="button button-disabled %s"><span class="dashicons dashicons-editor-code"></span> %s (Error)</span>',
			esc_attr( trim( 'button ' . $css_class ) ),
			esc_html( $button_text )
		);
	}
	
	$css_classes = 'button ' . $css_class;
	
	return sprintf(
		'<a href="%s" class="%s"><span class="dashicons dashicons-editor-code"></span> %s</a>',
		esc_attr( $vscode_url ),
		esc_attr( trim( $css_classes ) ),
		esc_html( $button_text )
	);
}

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
		'TAB02-IGNORED' => 'Files Ignored During Processing (Advanced Verification)',
		'TAB12-01'      => 'Plugin Check Architecture main header',
		'TAB12-02'      => 'Verification Process Flow',
		'TAB12-03'      => 'Live Configuration Validation',
		'TAB12-04'      => 'Key Functions & Files',
		'TAB12-05'      => 'Function/Method table header',
		'TAB12-06'      => 'File Location table header',
		'TAB12-07'      => 'Purpose & Configuration table header',
		'TAB12-08'      => 'Current Issue: ActionScheduler.php Being Scanned',
		'TAB12-09A'     => 'Files Actually Ignored In Processing (Architecture)',
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
			$vscode_url = wpv_get_vscode_url( $caller_file, $caller_line );
			echo esc_html( $text ) . ' <a href="' . esc_url( $vscode_url ) . '" style="text-decoration: none;"><code style="font-size: 0.7em; color: #666; cursor: pointer; vertical-align: baseline;">' . esc_html( $code ) . '</code></a>';
		}
	} else {
		echo esc_html( $text );
	}
}
