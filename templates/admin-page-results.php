<?php
/**
 * Results Tab - Unified file-grouped issue display with sidebar
 * PHASE 5: Replaces admin-page-issues-byfile.php (TAB04) and admin-page-issues.php (TAB05)
 *
 * Pure PHP rendering. No JavaScript panel replacement.
 * Sidebar populated via $_GET['issue_id'] parameter.
 *
 * @package wp-verifier
 */

use WordPress\Plugin_Check\Admin\Saved_Results_Handler;
use WordPress\Plugin_Check\Utilities\Path_Builder;
use WordPress\Plugin_Check\Utilities\AI_Guidance;

if ( ! class_exists( 'WordPress\\Plugin_Check\\Admin\\Saved_Results_Handler' ) ) {
	require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Saved_Results_Handler.php';
}
if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\AI_Guidance' ) ) {
	require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/AI_Guidance.php';
}

$plugin_info  = Saved_Results_Handler::get_last_selected_plugin();
$results_data = $plugin_info ? Saved_Results_Handler::load_plugin_results( $plugin_info['slug'] ) : null;

// Determine selected issue from URL
$selected_issue_id = isset( $_GET['issue_id'] ) ? sanitize_text_field( wp_unslash( $_GET['issue_id'] ) ) : '';
$selected_issue    = null;
$selected_file     = '';

// Find the selected issue in results
if ( $selected_issue_id && $results_data && ! empty( $results_data['results'] ) ) {
	foreach ( $results_data['results'] as $file => $issues ) {
		foreach ( $issues as $issue ) {
			if ( isset( $issue['issue_id'] ) && $issue['issue_id'] === $selected_issue_id ) {
				$selected_issue = $issue;
				$selected_file  = $file;
				break 2;
			}
		}
	}
}

// Sort results: files with stale ignored_files entries (hash mismatch) first,
// then active files ordered by error count descending, warnings descending.
if ( ! empty( $results_data['results'] ) ) {
	$verification_file = Path_Builder::get_verification_file_path( $plugin_info['slug'] );
	$ignored_files     = array();
	if ( $verification_file && file_exists( $verification_file ) ) {
		$vdata = json_decode( file_get_contents( $verification_file ), true );
		if ( is_array( $vdata ) ) {
			$ignored_files = $vdata['ignored_files'] ?? array();
		}
	}

	$stale   = array();
	$normal  = array();

	foreach ( $results_data['results'] as $file => $issues ) {
		if ( isset( $ignored_files[ $file ] ) ) {
			$stale[ $file ] = $issues;
		} else {
			$normal[ $file ] = $issues;
		}
	}

	// Sort normal files: fewest active issues first (closest to resolved at top),
	// ties broken by error count descending.
	uasort(
		$normal,
		static function ( $a, $b ) {
			$a_errors   = count( array_filter( $a, static fn( $i ) => empty( $i['ignored'] ) && ( $i['type'] ?? '' ) === 'ERROR' ) );
			$b_errors   = count( array_filter( $b, static fn( $i ) => empty( $i['ignored'] ) && ( $i['type'] ?? '' ) === 'ERROR' ) );
			$a_warnings = count( array_filter( $a, static fn( $i ) => empty( $i['ignored'] ) && ( $i['type'] ?? '' ) === 'WARNING' ) );
			$b_warnings = count( array_filter( $b, static fn( $i ) => empty( $i['ignored'] ) && ( $i['type'] ?? '' ) === 'WARNING' ) );
			$a_total    = $a_errors + $a_warnings;
			$b_total    = $b_errors + $b_warnings;
			if ( $a_total !== $b_total ) {
				return $a_total - $b_total; // Fewest issues first.
			}
			// Tie-break: more errors = higher priority.
			return $b_errors - $a_errors;
		}
	);

	// Stale-ignored files bubble to the top so the developer notices them.
	$results_data['results'] = array_merge( $stale, $normal );
}

// Load AI guidance for selected issue
$ai_guidance_text = '';
if ( $selected_issue ) {
	$code     = $selected_issue['code'] ?? '';
	$guidance = AI_Guidance::get_guidance( $code );

	$plugin_slug       = $plugin_info['slug'] ?? '';
	$ai_guidance_text  = 'Plugin: ' . $plugin_slug . "\n";
	$ai_guidance_text .= 'Issue ID: ' . $selected_issue_id . "\n";
	$ai_guidance_text .= 'File: ' . $selected_file . "\n";
	$ai_guidance_text .= 'Line: ' . ( $selected_issue['line'] ?? '' ) . "\n";
	$ai_guidance_text .= 'Code: ' . $code . "\n";
	$ai_guidance_text .= 'Type: ' . ( $selected_issue['type'] ?? '' ) . "\n";
	$ai_guidance_text .= 'Message: ' . ( $selected_issue['message'] ?? '' ) . "\n\n";
	$ai_guidance_text .= "Please review this WordPress coding standards issue and provide a fix or suggest the issue is ignored.\n";
	if ( $guidance && ! empty( $guidance['ai_guidance'] ) ) {
		$ai_guidance_text .= "\nAI Guidance:\n" . $guidance['ai_guidance'];
	}
	$readme_path       = Path_Builder::get_plugin_file_path( $plugin_slug, 'readme.txt' );
	$ai_guidance_text .= "\nPlugin readme.txt: " . ( $readme_path ? wp_normalize_path( $readme_path ) : 'not found' ) . "\n";
	$ai_guidance_text .= "\nDo ALL of the following:\n";
	$ai_guidance_text .= "- Update the plugin readme.txt changelog to record this fix: " . wp_normalize_path( $readme_path ) . "\n";
	$ai_guidance_text .= "- Update the @version tag on any modified function, method, or class to the current plugin version\n";
	$ai_guidance_text .= "- Add or update inline comments on changed lines to explain why the change was made, not just what it does\n";
	$ai_guidance_text .= "\nDo NOT do any of the following:\n";
	$ai_guidance_text .= "- Add phpcs:ignore or phpcs:disable comments — fix the code properly\n";
	$ai_guidance_text .= "- Leave // TODO comments — implement the fix fully\n";
	$ai_guidance_text .= "- Add error_log() calls\n";
	$ai_guidance_text .= "- use British English\n";
	$ai_guidance_text .= "- Skip PHPDoc — all new or modified functions must have PHPDoc blocks\n";
	$ai_guidance_text .= "- Modify any files outside the plugin directory: " . wp_normalize_path( WP_PLUGIN_DIR . '/' . $plugin_slug ) . "\n";
	$ai_guidance_text .= "- Touch WP Verifier or any other plugin's files\n";
}
?>

<?php if ( ! $plugin_info ) : ?>
	<div class="notice notice-info">
		<p><?php esc_html_e( 'No plugin selected. Please run a verification first.', 'wpverifier' ); ?></p>
	</div>
<?php elseif ( ! $results_data || empty( $results_data['results'] ) ) : ?>
	<div class="notice notice-warning">
		<p><?php echo esc_html( sprintf( __( 'No results found for %s. Please run a verification first.', 'wpverifier' ), $plugin_info['name'] ) ); ?></p>
	</div>
<?php else : ?>

	<?php if ( isset( $results_data['readiness'] ) ) : ?>
		<div class="wpv-readiness-score">
			<strong><?php esc_html_e( 'Readiness Score:', 'wpverifier' ); ?></strong>
			<?php echo esc_html( $results_data['readiness']['overall'] ); ?>%
			(<?php echo esc_html( $results_data['readiness']['errors'] ); ?> errors,
			<?php echo esc_html( $results_data['readiness']['warnings'] ); ?> warnings)
		</div>
	<?php endif; ?>

	<div class="wpv-ast-container">
		<div class="wpv-ast-layout">
			<!-- FILE ACCORDION (left) -->
			<div class="wpv-ast-table-container">
				<h3><?php echo esc_html( sprintf( __( 'Files with Issues - %s', 'wpverifier' ), $plugin_info['name'] ) ); ?></h3>

				<div class="wpv-table-header">
					<div class="wpv-table-header-file"><?php esc_html_e( 'File', 'wpverifier' ); ?></div>
					<div class="wpv-table-header-issues"><?php esc_html_e( 'Issues', 'wpverifier' ); ?></div>
				</div>
				<div class="wpv-ast-table">
					<?php
					$results_url = add_query_arg(
						array(
							'page' => 'wp-verifier',
							'tab'  => 'results',
						),
						admin_url( 'plugins.php' )
					);

					foreach ( $results_data['results'] as $file => $issues ) :
						$error_count   = 0;
						$warning_count = 0;
						$ignored_count = 0;
						foreach ( $issues as $issue ) {
							if ( ! empty( $issue['ignored'] ) ) {
								$ignored_count++;
								continue;
							}
							if ( 'ERROR' === $issue['type'] ) {
								$error_count++;
							} elseif ( 'WARNING' === $issue['type'] ) {
								$warning_count++;
							}
						}
						// Auto-open the accordion if it contains the selected issue
						$has_selected = $selected_file === $file;
						?>
						<div class="accordion-row">
							<div class="accordion-header <?php echo $has_selected ? 'active' : ''; ?>">
								<div class="wpv-ast-file-name"><?php echo esc_html( $file ); ?></div>
								<div class="wpv-ast-severity">
									<?php if ( $error_count > 0 ) : ?>
										<span class="wpv-ast-badge error"><?php echo esc_html( $error_count ); ?> error<?php echo 1 !== $error_count ? 's' : ''; ?></span>
									<?php endif; ?>
									<?php if ( $warning_count > 0 ) : ?>
										<span class="wpv-ast-badge warning"><?php echo esc_html( $warning_count ); ?> warning<?php echo 1 !== $warning_count ? 's' : ''; ?></span>
									<?php endif; ?>
									<?php if ( $ignored_count > 0 ) : ?>
										<span class="wpv-ast-badge ignored"><?php echo esc_html( $ignored_count ); ?> ignored</span>
									<?php endif; ?>
								</div>
							</div>
							<div class="accordion-content" <?php echo $has_selected ? 'style="display:block;"' : ''; ?>>
								<ul class="wpv-ast-issue-list">
									<?php foreach ( $issues as $issue ) :
										$iid       = $issue['issue_id'] ?? '';
										$is_active = ( $iid === $selected_issue_id );
										$is_ignored = isset( $issue['ignored'] ) && $issue['ignored'];
										$issue_url = add_query_arg( 'issue_id', $iid, $results_url );
										$item_classes = array( 'wpv-ast-issue-item' );
										if ( $is_active ) $item_classes[] = 'active';
										if ( $is_ignored ) $item_classes[] = 'ignored';
										?>
										<li class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>">
											<a href="<?php echo esc_url( $issue_url ); ?>" style="text-decoration:none; color:inherit; display:block;">
												<span class="wpv-ast-badge <?php echo esc_attr( strtolower( $issue['type'] ) ); ?>"><?php echo esc_html( $issue['type'] ); ?></span>
												<?php if ( $is_ignored ) : ?><span class="wpv-ast-badge ignored"><?php esc_html_e( 'IGNORED', 'wpverifier' ); ?></span><?php endif; ?>
												<code style="font-size:11px; color:#666;">[<?php echo esc_html( $iid ); ?>]</code>
												Line <?php echo esc_html( $issue['line'] ); ?>: <?php echo esc_html( $issue['message'] ); ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- SIDEBAR (right) -->
			<div class="wpv-ast-sidebar">

				<!-- PAN01: Issue Details -->
				<h3 class="wpv-accordion-header active" data-target="pan01-content">
					<?php wpverifier_header( __( 'Issue Details', 'wpverifier' ), 'PAN01' ); ?>
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</h3>
				<div id="pan01-content" class="wpv-accordion-content" style="display:block;">
					<?php if ( $selected_issue ) : ?>
						<div class="wpv-ast-details">
							<div class="wpv-issue-details-content">
								<div class="wpv-issue-header">
									<h4><?php esc_html_e( 'Issue Details', 'wpverifier' ); ?></h4>
									<span class="wpv-issue-id-display">ID: <code><?php echo esc_html( $selected_issue_id ); ?></code></span>
								</div>
								<div class="wpv-issue-info">
									<?php if ( isset( $selected_issue['ignored'] ) && $selected_issue['ignored'] ) : ?>
										<div class="wpv-issue-field wpv-ignored-status">
											<strong><?php esc_html_e( 'Status:', 'wpverifier' ); ?></strong>
											<span class="wpv-ast-badge ignored"><?php esc_html_e( 'IGNORED', 'wpverifier' ); ?></span>
										</div>
									<?php endif; ?>
									<div class="wpv-issue-field">
										<strong><?php esc_html_e( 'Type:', 'wpverifier' ); ?></strong>
										<span class="wpv-ast-badge <?php echo esc_attr( strtolower( $selected_issue['type'] ) ); ?>"><?php echo esc_html( $selected_issue['type'] ); ?></span>
									</div>
									<div class="wpv-issue-field">
										<strong><?php esc_html_e( 'File:', 'wpverifier' ); ?></strong>
										<code><?php echo esc_html( $selected_file ); ?></code>
									</div>
									<div class="wpv-issue-field">
										<strong><?php esc_html_e( 'Line:', 'wpverifier' ); ?></strong>
										<?php echo esc_html( $selected_issue['line'] ?? '' ); ?>
									</div>
									<div class="wpv-issue-field">
										<strong><?php esc_html_e( 'Code:', 'wpverifier' ); ?></strong>
										<code><?php echo esc_html( $selected_issue['code'] ?? '' ); ?></code>
									</div>
									<div class="wpv-issue-field">
										<strong><?php esc_html_e( 'Message:', 'wpverifier' ); ?></strong>
										<div><?php echo esc_html( $selected_issue['message'] ?? '' ); ?></div>
									</div>
								</div>
								<div class="wpv-issue-actions">
									<?php
									$is_ignored       = isset( $selected_issue['ignored'] ) && $selected_issue['ignored'];
									$issue_code       = $selected_issue['code'] ?? '';
									$issue_line       = (int) ( $selected_issue['line'] ?? 0 );
									$is_auto_fixable  = class_exists( 'WordPress\\Plugin_Check\\Utilities\\Auto_Fix_Engine' )
										? \WordPress\Plugin_Check\Utilities\Auto_Fix_Engine::is_fixable( $issue_code )
										: false;
									?>
									<a href="#" id="wpv-current-fixed-btn" class="button button-primary wpv-fixed-btn"
										data-issue-id="<?php echo esc_attr( $selected_issue_id ); ?>"
										title="<?php esc_attr_e( 'Permanently removes this issue from results', 'wpverifier' ); ?>">
										<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Fixed', 'wpverifier' ); ?>
									</a>
									<?php if ( $is_auto_fixable ) : ?>
										<a href="#" id="wpv-current-autofix-btn" class="button button-secondary wpv-autofix-btn"
											data-issue-id="<?php echo esc_attr( $selected_issue_id ); ?>"
											data-file="<?php echo esc_attr( $selected_file ); ?>"
											data-line="<?php echo esc_attr( $issue_line ); ?>"
											data-code="<?php echo esc_attr( $issue_code ); ?>"
											title="<?php esc_attr_e( 'Apply deterministic code fix to the source file', 'wpverifier' ); ?>">
											<span class="dashicons dashicons-hammer"></span> <?php esc_html_e( 'Auto Fix', 'wpverifier' ); ?>
										</a>
									<?php endif; ?>
									<?php if ( $is_ignored ) : ?>
										<a href="#" id="wpv-current-ignore-btn" class="button wpv-unignore-btn"
											data-issue-id="<?php echo esc_attr( $selected_issue_id ); ?>"
											title="<?php esc_attr_e( 'Remove ignored status from this issue', 'wpverifier' ); ?>">
											<span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Unignore', 'wpverifier' ); ?>
										</a>
									<?php else : ?>
										<a href="#" id="wpv-current-ignore-btn" class="button wpv-ignore-btn"
											data-issue-id="<?php echo esc_attr( $selected_issue_id ); ?>"
											title="<?php esc_attr_e( 'Marks as ignored but keeps in results (for false positives)', 'wpverifier' ); ?>">
											<span class="dashicons dashicons-hidden"></span> <?php esc_html_e( 'Ignore', 'wpverifier' ); ?>
										</a>
									<?php endif; ?>
									<?php
									$vscode_url = Path_Builder::get_vscode_url(
										$plugin_info['basename'],
										$selected_file,
										$issue_line,
										(int) ( $selected_issue['column'] ?? 0 )
									);
									if ( $vscode_url ) :
										?>
										<a href="<?php echo esc_attr( $vscode_url ); ?>" class="button" title="<?php esc_attr_e( 'Open in VSCode', 'wpverifier' ); ?>">
											<span class="dashicons dashicons-editor-code"></span> <?php esc_html_e( 'VSCode', 'wpverifier' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php else : ?>
						<div class="wpv-ast-placeholder">
							<p><?php esc_html_e( 'Select an issue to see details', 'wpverifier' ); ?></p>
						</div>
					<?php endif; ?>
				</div>

				<!-- PAN02: AI Prompt -->
				<h3 class="wpv-accordion-header panel-spacing <?php echo $selected_issue ? 'active' : ''; ?>" data-target="pan02-content">
					<?php wpverifier_header( __( 'AI Prompt', 'wpverifier' ), 'PAN02' ); ?>
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</h3>
				<div id="pan02-content" class="wpv-accordion-content" <?php echo $selected_issue ? 'style="display:block;"' : 'style="display:none;"'; ?>>
					<?php if ( $selected_issue ) : ?>
						<div class="wpv-ast-details">
							<div class="wpv-ai-prompt-content">
								<label for="wpv-ai-prompt-text"><?php esc_html_e( 'Copy this prompt to AI:', 'wpverifier' ); ?></label>
								<textarea id="wpv-ai-prompt-text" readonly class="wpv-ai-prompt-textarea"><?php echo esc_textarea( $ai_guidance_text ); ?></textarea>
								<div class="wpv-ai-prompt-actions">
									<button type="button" class="button button-primary wpv-copy-prompt" data-target="wpv-ai-prompt-text">
										<span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy AI Prompt', 'wpverifier' ); ?>
									</button>
								</div>
							</div>
						</div>
					<?php else : ?>
						<div class="wpv-ast-placeholder">
							<p><?php esc_html_e( 'Select an issue to generate AI prompt', 'wpverifier' ); ?></p>
						</div>
					<?php endif; ?>
				</div>

			</div>
		</div>
	</div>

<?php endif; ?>
