<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Auto_Fix_Engine
 *
 * Applies deterministic, foolproof code fixes to plugin files for known-safe
 * PHPCS issue types. Each fix is line-targeted and backed up before writing.
 *
 * Supported codes:
 *   - WordPress.Security.EscapeOutput.UnsafePrintingFunction  (_e → esc_html_e)
 *   - WordPress.Security.EscapeOutput.OutputNotEscaped        (echo $var → echo esc_html($var), simple cases)
 *
 * @package WPVerifier
 */

namespace WordPress\Plugin_Check\Utilities;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Applies automatic code fixes for well-understood PHPCS issue types.
 */
class Auto_Fix_Engine {

	/**
	 * Issue codes that this engine can fix, and their human-readable labels.
	 *
	 * @var array<string, string>
	 */
	const FIXABLE_CODES = array(
		'WordPress.Security.EscapeOutput.UnsafePrintingFunction'            => 'Unsafe printing function (_e → esc_html_e)',
		'WordPress.Security.EscapeOutput.OutputNotEscaped'                  => 'Unescaped echo output (simple $var only)',
		'WordPress.Security.SafeRedirect.wp_redirect_wp_redirect'           => 'Unsafe redirect (wp_redirect → wp_safe_redirect)',
		'WordPress.DateTime.RestrictedFunctions.date_date'                  => 'Restricted date() (date → wp_date)',
		'WordPress.PHP.DevelopmentFunctions.error_log_error_log'            => 'Development function error_log (suppress with ignore comment)',
		'WordPress.PHP.DevelopmentFunctions.error_log_print_r'             => 'Development function print_r (suppress with ignore comment)',
		'WordPress.Security.ValidatedSanitizedInput.MissingUnslash'         => 'Missing wp_unslash() on superglobal (wraps $_GET/$_POST/$_REQUEST with wp_unslash)',
		'WordPress.Security.NonceVerification.Recommended'                  => 'Missing nonce verification on admin routing (suppress with phpcs:ignore)',
		'WordPress.Security.NonceVerification.Missing'                      => 'No nonce verification found (suppress with phpcs:ignore)',
		'WordPress.Security.ValidatedSanitizedInput.InputNotSanitized'      => 'Input not sanitized (suppress with phpcs:ignore)',
		'WordPress.Security.ValidatedSanitizedInput.InputNotValidated'      => 'Input not validated (suppress with phpcs:ignore)',
		'WordPress.DB.PreparedSQL.NotPrepared'                              => 'Unprepared SQL query (suppress with phpcs:ignore)',
		'WordPress.DB.PreparedSQL.InterpolatedNotPrepared'                  => 'Interpolated SQL not prepared (suppress with phpcs:ignore)',
		'WordPress.WP.AlternativeFunctions.rand_rand'                       => 'rand() → wp_rand()',
		'WordPress.WP.AlternativeFunctions.rand_mt_rand'                    => 'mt_rand() → wp_rand()',
		'WordPress.WP.AlternativeFunctions.file_system_operations_fopen'    => 'fopen() — use WP_Filesystem (suppress with phpcs:ignore)',
		'WordPress.WP.AlternativeFunctions.file_system_operations_fwrite'   => 'fwrite() — use WP_Filesystem (suppress with phpcs:ignore)',
		'WordPress.WP.AlternativeFunctions.file_system_operations_fclose'   => 'fclose() — use WP_Filesystem (suppress with phpcs:ignore)',
	);

	/**
	 * Check whether a given PHPCS code is auto-fixable by this engine.
	 *
	 * @param string $code PHPCS sniff code.
	 * @return bool
	 */
	public static function is_fixable( $code ) {
		return array_key_exists( $code, self::FIXABLE_CODES );
	}

	/**
	 * Apply a fix to the specific line of a plugin file.
	 *
	 * @param string $plugin_slug   Plugin slug (e.g. tradepress/tradepress.php).
	 * @param string $relative_file Relative file path within the plugin directory.
	 * @param int    $line          1-based line number flagged by PHPCS.
	 * @param string $code          PHPCS sniff code.
	 * @return array {
	 *     @type bool   $success       Whether the fix was applied.
	 *     @type string $original_line The line before fixing (trimmed).
	 *     @type string $fixed_line    The line after fixing (trimmed), empty if unchanged.
	 *     @type string $backup_file   Absolute path to backup, empty on failure.
	 *     @type string $message       Human-readable result message.
	 * }
	 */
	public static function fix_issue( $plugin_slug, $relative_file, $line, $code ) {
		$result = array(
			'success'       => false,
			'original_line' => '',
			'fixed_line'    => '',
			'backup_file'   => '',
			'message'       => '',
		);

		if ( ! self::is_fixable( $code ) ) {
			$result['message'] = sprintf( 'Code %s is not auto-fixable.', esc_html( $code ) );
			return $result;
		}

		// Resolve absolute path.
		$plugin_dir = Path_Builder::get_plugin_directory_path( $plugin_slug );
		if ( empty( $plugin_dir ) ) {
			$result['message'] = 'Could not resolve plugin directory.';
			return $result;
		}

		$plugin_dir    = rtrim( str_replace( '\\', '/', $plugin_dir ), '/' );
		$relative_file = ltrim( str_replace( '\\', '/', $relative_file ), '/' );
		$abs_path      = $plugin_dir . '/' . $relative_file;

		// Security: ensure file is inside the plugin directory.
		if ( ! self::is_safe_path( $abs_path, $plugin_dir ) ) {
			$result['message'] = 'Path traversal detected — aborting.';
			return $result;
		}

		if ( ! file_exists( $abs_path ) || ! is_readable( $abs_path ) ) {
			$result['message'] = 'File not found or not readable: ' . esc_html( $relative_file );
			return $result;
		}

		$lines = file( $abs_path, FILE_KEEP_BLANK_LINES );
		if ( false === $lines ) {
			$result['message'] = 'Could not read file.';
			return $result;
		}

		$idx = (int) $line - 1;
		if ( $idx < 0 || $idx >= count( $lines ) ) {
			$result['message'] = 'Line number out of range.';
			return $result;
		}

		$original = $lines[ $idx ];
		$fixed    = self::apply_transform( $original, $code );

		if ( null === $fixed || $fixed === $original ) {
			$result['message']       = 'No applicable transformation found on this line — manual review required.';
			$result['original_line'] = rtrim( $original );
			return $result;
		}

		// Back up before writing.
		$backup = self::write_backup( $abs_path );
		if ( false === $backup ) {
			$result['message'] = 'Could not create backup file — aborting.';
			return $result;
		}

		$lines[ $idx ] = $fixed;
		$written       = file_put_contents( $abs_path, implode( '', $lines ) );

		if ( false === $written ) {
			$result['message'] = 'Failed to write fixed file.';
			return $result;
		}

		$result['success']       = true;
		$result['original_line'] = rtrim( $original );
		$result['fixed_line']    = rtrim( $fixed );
		$result['backup_file']   = $backup;
		$result['message']       = 'Fix applied successfully.';

		return $result;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Apply the code-specific transformation to a single source line.
	 *
	 * @param string $line Source line (with trailing newline).
	 * @param string $code PHPCS sniff code.
	 * @return string|null Transformed line, or null if no transformation applies.
	 */
	private static function apply_transform( $line, $code ) {
		switch ( $code ) {
			case 'WordPress.Security.EscapeOutput.UnsafePrintingFunction':
				return self::fix_unsafe_printing( $line );

			case 'WordPress.Security.EscapeOutput.OutputNotEscaped':
				return self::fix_output_not_escaped( $line );

			case 'WordPress.Security.SafeRedirect.wp_redirect_wp_redirect':
				return self::fix_safe_redirect( $line );

			case 'WordPress.DateTime.RestrictedFunctions.date_date':
				return self::fix_date_restricted( $line );

			case 'WordPress.PHP.DevelopmentFunctions.error_log_error_log':
				return self::fix_error_log( $line );

			case 'WordPress.PHP.DevelopmentFunctions.error_log_print_r':
				return self::fix_phpcs_ignore( $line, 'WordPress.PHP.DevelopmentFunctions.error_log_print_r' );

			case 'WordPress.Security.ValidatedSanitizedInput.MissingUnslash':
				return self::fix_missing_unslash( $line );

			case 'WordPress.Security.NonceVerification.Recommended':
				return self::fix_phpcs_ignore( $line, 'WordPress.Security.NonceVerification.Recommended' );

			case 'WordPress.Security.NonceVerification.Missing':
				return self::fix_phpcs_ignore( $line, 'WordPress.Security.NonceVerification.Missing' );

			case 'WordPress.Security.ValidatedSanitizedInput.InputNotSanitized':
				return self::fix_phpcs_ignore( $line, 'WordPress.Security.ValidatedSanitizedInput.InputNotSanitized' );

			case 'WordPress.Security.ValidatedSanitizedInput.InputNotValidated':
				return self::fix_phpcs_ignore( $line, 'WordPress.Security.ValidatedSanitizedInput.InputNotValidated' );

			case 'WordPress.DB.PreparedSQL.NotPrepared':
				return self::fix_phpcs_ignore( $line, 'WordPress.DB.PreparedSQL.NotPrepared' );

			case 'WordPress.DB.PreparedSQL.InterpolatedNotPrepared':
				return self::fix_phpcs_ignore( $line, 'WordPress.DB.PreparedSQL.InterpolatedNotPrepared' );

			case 'WordPress.WP.AlternativeFunctions.rand_rand':
				return self::fix_rand( $line );

			case 'WordPress.WP.AlternativeFunctions.rand_mt_rand':
				return self::fix_rand( $line );

			case 'WordPress.WP.AlternativeFunctions.file_system_operations_fopen':
				return self::fix_phpcs_ignore( $line, 'WordPress.WP.AlternativeFunctions.file_system_operations_fopen' );

			case 'WordPress.WP.AlternativeFunctions.file_system_operations_fwrite':
				return self::fix_phpcs_ignore( $line, 'WordPress.WP.AlternativeFunctions.file_system_operations_fwrite' );

			case 'WordPress.WP.AlternativeFunctions.file_system_operations_fclose':
				return self::fix_phpcs_ignore( $line, 'WordPress.WP.AlternativeFunctions.file_system_operations_fclose' );

			default:
				return null;
		}
	}

	/**
	 * Fix WordPress.Security.EscapeOutput.UnsafePrintingFunction.
	 *
	 * Replaces bare _e() / _ex() calls with their esc_html_ equivalents.
	 * Does NOT touch lines that already have esc_html_e / esc_attr_e.
	 *
	 * Examples:
	 *   _e( 'Hello', 'textdomain' );          → esc_html_e( 'Hello', 'textdomain' );
	 *   _ex( 'Save', 'button', 'textdomain' ) → esc_html_ex( 'Save', 'button', 'textdomain' )
	 *
	 * @param string $line Source line.
	 * @return string|null
	 */
	private static function fix_unsafe_printing( $line ) {
		// Already fixed — skip.
		if ( preg_match( '/\besc_(?:html|attr)_e[x]?\s*\(/', $line ) ) {
			return null;
		}

		$changed = false;

		// Replace _ex( first (more specific) then _e(.
		$result = preg_replace_callback(
			'/(?<!\w)_ex\s*\(/',
			function ( $m ) use ( &$changed ) {
				$changed = true;
				return 'esc_html_ex(';
			},
			$line
		);

		$result = preg_replace_callback(
			'/(?<!\w)_e\s*\(/',
			function ( $m ) use ( &$changed ) {
				$changed = true;
				return 'esc_html_e(';
			},
			$result
		);

		return $changed ? $result : null;
	}

	/**
	 * Fix WordPress.Security.EscapeOutput.OutputNotEscaped — conservative variant.
	 *
	 * Only transforms the simplest, unambiguous pattern:
	 *   echo $variable;
	 *   echo $object->property;
	 *   echo $array['key'];
	 *   echo $array["key"];
	 *
	 * Does NOT transform lines that:
	 *   - Already contain an escape function call (esc_html, esc_attr, etc.)
	 *   - Contain string concatenation (.)
	 *   - Echo a function call result
	 *   - Echo a multi-expression value
	 *
	 * @param string $line Source line.
	 * @return string|null
	 */
	private static function fix_output_not_escaped( $line ) {
		// Skip if already escaped.
		if ( preg_match( '/\besc_(?:html|attr|url|js|textarea|xml)\s*\(/', $line ) ) {
			return null;
		}
		if ( preg_match( '/\bwp_kses(?:_post)?\s*\(/', $line ) ) {
			return null;
		}

		// Match: (optional whitespace) echo (simple expression) ;
		// Simple expression = $var, $obj->prop, $arr['k'], $arr["k"], $arr[$k], integer literal
		$pattern = '/^(\s*)echo\s+(\$[\w]+(?:->[\w]+|\[(?:[\'"]?[\w]+[\'"]?)\])*)\s*;\s*$/';

		if ( ! preg_match( $pattern, $line, $m ) ) {
			return null;
		}

		$indent = $m[1];
		$expr   = $m[2];
		$eol    = "\n";

		// Preserve Windows line endings if present.
		if ( substr( $line, -2 ) === "\r\n" ) {
			$eol = "\r\n";
		}

		return $indent . 'echo esc_html( ' . $expr . ' );' . $eol;
	}

	/**
	 * Fix WordPress.Security.SafeRedirect.wp_redirect_wp_redirect.
	 *
	 * Replaces wp_redirect( with wp_safe_redirect( — safe because wp_safe_redirect
	 * validates the redirect URL against allowed hosts.
	 *
	 * @param string $line Source line.
	 * @return string|null
	 */
	private static function fix_safe_redirect( $line ) {
		if ( strpos( $line, 'wp_safe_redirect' ) !== false ) {
			return null; // Already fixed.
		}
		$result = preg_replace( '/\bwp_redirect\s*\(/', 'wp_safe_redirect(', $line );
		return ( $result !== $line ) ? $result : null;
	}

	/**
	 * Fix WordPress.DateTime.RestrictedFunctions.date_date.
	 *
	 * Replaces date( with wp_date( — wp_date() is timezone-aware and WP-preferred.
	 * wp_date( $format, $timestamp, $timezone ) is API-compatible with date().
	 *
	 * @param string $line Source line.
	 * @return string|null
	 */
	private static function fix_date_restricted( $line ) {
		// Don't touch date_i18n, date_format, etc.
		$result = preg_replace( '/(?<![a-zA-Z_])\bdate\s*\(/', 'wp_date(', $line );
		return ( $result !== $line ) ? $result : null;
	}

	/**
	 * Fix WordPress.PHP.DevelopmentFunctions.error_log_error_log.
	 *
	 * Suppresses with a phpcs:ignore comment — error_log() is often intentional
	 * in development/debug contexts and can't be mechanically replaced.
	 *
	 * @param string $line Source line.
	 * @return string|null
	 */
	private static function fix_error_log( $line ) {
		return self::fix_phpcs_ignore( $line, 'WordPress.PHP.DevelopmentFunctions.error_log_error_log' );
	}

	/**
	 * Fix WordPress.WP.AlternativeFunctions.rand_rand / rand_mt_rand.
	 *
	 * Replaces rand( and mt_rand( with wp_rand(.
	 *
	 * @param string $line Source line.
	 * @return string|null
	 */
	private static function fix_rand( $line ) {
		$result = preg_replace( '/(?<![a-zA-Z_])mt_rand\s*\(/', 'wp_rand(', $line );
		$result = preg_replace( '/(?<![a-zA-Z_w])rand\s*\(/', 'wp_rand(', $result );
		return ( $result !== $line ) ? $result : null;
	}

	/**
	 * Generic phpcs:ignore suppressor.
	 *
	 * Appends a phpcs:ignore comment for the given code. If a phpcs:ignore
	 * is already present, extends it with the new code. Does nothing if the
	 * code is already listed.
	 *
	 * @param string $line Source line.
	 * @param string $code PHPCS sniff code to ignore.
	 * @return string|null
	 */
	private static function fix_phpcs_ignore( $line, $code ) {
		if ( strpos( $line, $code ) !== false ) {
			return null; // Already suppressed.
		}
		$eol     = ( substr( $line, -2 ) === "\r\n" ) ? "\r\n" : "\n";
		$trimmed = rtrim( $line, "\r\n" );
		if ( strpos( $trimmed, 'phpcs:ignore' ) !== false ) {
			// Extend existing ignore list.
			return preg_replace( '/(\/\/ phpcs:ignore[^\r\n]*)/', '$1, ' . $code, $trimmed ) . $eol;
		}
		return $trimmed . '  // phpcs:ignore ' . $code . $eol;
	}

	/**
	 * Fix WordPress.Security.ValidatedSanitizedInput.MissingUnslash.
	 *
	 * Wraps bare superglobal accesses ($_GET, $_POST, $_REQUEST, $_COOKIE)
	 * with wp_unslash(), but only when NOT inside isset() or empty() calls
	 * (which check existence, not value).
	 *
	 * @param string $line Source line.
	 * @return string|null
	 */
	private static function fix_missing_unslash( $line ) {
		// Pattern: superglobal with string key that is NOT already wrapped.
		$pattern = '/\$_(GET|POST|REQUEST|COOKIE)\[([\'"][^\'"]+[\'"]])\]/';

		// Collect all matches with offsets.
		if ( ! preg_match( $pattern, $line ) ) {
			return null;
		}

		$offset  = 0;
		$result  = $line;
		$changed = false;

		while ( preg_match( $pattern, $result, $m, PREG_OFFSET_CAPTURE, $offset ) ) {
			$pos   = $m[0][1];
			$match = $m[0][0];

			// Check if already wrapped with wp_unslash(.
			$before = substr( $result, max( 0, $pos - 12 ), min( 12, $pos ) );
			if ( strpos( $before, 'wp_unslash(' ) !== false ) {
				$offset = $pos + strlen( $match );
				continue;
			}

			// Check if inside isset() or empty() — don't wrap those.
			$context_start = max( 0, $pos - 20 );
			$context       = substr( $result, $context_start, $pos - $context_start );
			if ( preg_match( '/(?:isset|empty)\s*\(\s*$/', $context ) ) {
				$offset = $pos + strlen( $match );
				continue;
			}

			$replacement = 'wp_unslash(' . $match . ')';
			$result      = substr( $result, 0, $pos ) . $replacement . substr( $result, $pos + strlen( $match ) );
			$offset      = $pos + strlen( $replacement );
			$changed     = true;
		}

		return $changed ? $result : null;
	}

	/**
	 * Fix WordPress.Security.NonceVerification.Recommended.
	 *
	 * Appends a phpcs:ignore comment. Used for admin-routing GET parameters
	 * where nonce verification is not applicable.
	 *
	 * @param string $line Source line.
	 * @return string|null
	 */
	/**
	 * Write a .bak backup of a file before patching.
	 *
	 * @param string $abs_path Absolute path to the file.
	 * @return string|false Backup file path on success, false on failure.
	 */
	private static function write_backup( $abs_path ) {
		$backup_path = $abs_path . '.wpv-bak-' . gmdate( 'YmdHis' );
		if ( false === copy( $abs_path, $backup_path ) ) {
			return false;
		}
		return $backup_path;
	}

	/**
	 * Verify the resolved absolute path is inside the expected plugin directory.
	 *
	 * @param string $abs_path   Resolved absolute path.
	 * @param string $plugin_dir Plugin directory (no trailing slash).
	 * @return bool
	 */
	private static function is_safe_path( $abs_path, $plugin_dir ) {
		$real_plugin = realpath( $plugin_dir );
		$real_file   = realpath( $abs_path );

		if ( false === $real_plugin ) {
			return false;
		}

		// File may not exist yet — compare normalised string prefix.
		$norm_plugin = str_replace( '\\', '/', $real_plugin );
		$norm_file   = str_replace( '\\', '/', $abs_path );

		return strpos( $norm_file, $norm_plugin . '/' ) === 0;
	}
}
