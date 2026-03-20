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

// Load AI guidance for selected issue
$ai_guidance_text = '';
if ( $selected_issue ) {
	$code     = $selected_issue['code'] ?? '';
	$guidance = AI_Guidance::get_guidance( $code );

	$ai_guidance_text  = 'Issue ID: ' . $selected_issue_id . "\n";
	$ai_guidance_text .= 'File: ' . $selected_file . "\n";
	$ai_guidance_text .= 'Line: ' . ( $selected_issue['line'] ?? '' ) . "\n";
	$ai_guidance_text .= 'Code: ' . $code . "\n";
	$ai_guidance_text .= 'Type: ' . ( $selected_issue['type'] ?? '' ) . "\n";
	$ai_guidance_text .= 'Message: ' . ( $selected_issue['message'] ?? '' ) . "\n\n";
	$ai_guidance_text .= "Please review this WordPress coding standards issue and provide a fix.\n";
	if ( $guidance && ! empty( $guidance['ai_guidance'] ) ) {
		$ai_guidance_text .= "\nAI Guidance:\n" . $guidance['ai_guidance'];
	}
}
?>

<?php if ( ! $plugin_info ) : ?>
	<div class="notice notice-info">
		<p><?php esc_html_e( 'No plugin selected. Please run a verification first.', 'wp-verifier' ); ?></p>
	</div>
<?php elseif ( ! $results_data || empty( $results_data['results'] ) ) : ?>
	<div class="notice notice-warning">
		<p><?php echo esc_html( sprintf( __( 'No results found for %s. Please run a verification first.', 'wp-verifier' ), $plugin_info['name'] ) ); ?></p>
	</div>
<?php else : ?>

	<?php if ( isset( $results_data['readiness'] ) ) : ?>
		<div class="wpv-readiness-score">
			<strong><?php esc_html_e( 'Readiness Score:', 'wp-verifier' ); ?></strong>
			<?php echo esc_html( $results_data['readiness']['overall'] ); ?>%
			(<?php echo esc_html( $results_data['readiness']['errors'] ); ?> errors,
			<?php echo esc_html( $results_data['readiness']['warnings'] ); ?> warnings)
		</div>
	<?php endif; ?>

	<div class="wpv-ast-container">
		<div class="wpv-ast-layout">
			<!-- FILE ACCORDION (left) -->
			<div class="wpv-ast-table-container">
				<h3><?php echo esc_html( sprintf( __( 'Files with Issues - %s', 'wp-verifier' ), $plugin_info['name'] ) ); ?></h3>

				<div class="wpv-table-header">
					<div class="wpv-table-header-file"><?php esc_html_e( 'File', 'wp-verifier' ); ?></div>
					<div class="wpv-table-header-issues"><?php esc_html_e( 'Issues', 'wp-verifier' ); ?></div>
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
						foreach ( $issues as $issue ) {
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
										<span class="wpv-ast-badge error"><?php echo esc_html( $error_count ); ?> errors</span>
									<?php endif; ?>
									<?php if ( $warning_count > 0 ) : ?>
										<span class="wpv-ast-badge warning"><?php echo esc_html( $warning_count ); ?> warnings</span>
									<?php endif; ?>
								</div>
							</div>
							<div class="accordion-content" <?php echo $has_selected ? 'style="display:block;"' : ''; ?>>
								<ul class="wpv-ast-issue-list">
									<?php foreach ( $issues as $issue ) :
										$iid       = $issue['issue_id'] ?? '';
										$is_active = ( $iid === $selected_issue_id );
										$issue_url = add_query_arg( 'issue_id', $iid, $results_url );
										?>
										<li class="wpv-ast-issue-item <?php echo $is_active ? 'active' : ''; ?>">
											<a href="<?php echo esc_url( $issue_url ); ?>" style="text-decoration:none; color:inherit; display:block;">
												<span class="wpv-ast-badge <?php echo esc_attr( strtolower( $issue['type'] ) ); ?>"><?php echo esc_html( $issue['type'] ); ?></span>
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
					<?php wpverifier_header( __( 'Issue Details', 'wp-verifier' ), 'PAN01' ); ?>
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</h3>
				<div id="pan01-content" class="wpv-accordion-content" style="display:block;">
					<?php if ( $selected_issue ) : ?>
						<div class="wpv-ast-details">
							<div class="wpv-issue-details-content">
								<div class="wpv-issue-header">
									<h4><?php esc_html_e( 'Issue Details', 'wp-verifier' ); ?></h4>
									<span class="wpv-issue-id-display">ID: <code><?php echo esc_html( $selected_issue_id ); ?></code></span>
								</div>
								<div class="wpv-issue-info">
									<div class="wpv-issue-field">
										<strong><?php esc_html_e( 'Type:', 'wp-verifier' ); ?></strong>
										<span class="wpv-ast-badge <?php echo esc_attr( strtolower( $selected_issue['type'] ) ); ?>"><?php echo esc_html( $selected_issue['type'] ); ?></span>
									</div>
									<div class="wpv-issue-field">
										<strong><?php esc_html_e( 'File:', 'wp-verifier' ); ?></strong>
										<code><?php echo esc_html( $selected_file ); ?></code>
									</div>
									<div class="wpv-issue-field">
										<strong><?php esc_html_e( 'Line:', 'wp-verifier' ); ?></strong>
										<?php echo esc_html( $selected_issue['line'] ?? '' ); ?>
									</div>
									<div class="wpv-issue-field">
										<strong><?php esc_html_e( 'Code:', 'wp-verifier' ); ?></strong>
										<code><?php echo esc_html( $selected_issue['code'] ?? '' ); ?></code>
									</div>
									<div class="wpv-issue-field">
										<strong><?php esc_html_e( 'Message:', 'wp-verifier' ); ?></strong>
										<div><?php echo esc_html( $selected_issue['message'] ?? '' ); ?></div>
									</div>
								</div>
								<div class="wpv-issue-actions">
									<a href="#" class="button button-primary wpv-fixed-btn"
										data-issue-id="<?php echo esc_attr( $selected_issue_id ); ?>"
										title="<?php esc_attr_e( 'Permanently removes this issue from results', 'wp-verifier' ); ?>">
										<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Fixed', 'wp-verifier' ); ?>
									</a>
									<a href="#" class="button wpv-ignore-btn"
										data-issue-id="<?php echo esc_attr( $selected_issue_id ); ?>"
										title="<?php esc_attr_e( 'Marks as ignored but keeps in results (for false positives)', 'wp-verifier' ); ?>">
										<span class="dashicons dashicons-hidden"></span> <?php esc_html_e( 'Ignore', 'wp-verifier' ); ?>
									</a>
									<?php
									$vscode_url = Path_Builder::get_vscode_url(
										$plugin_info['basename'],
										$selected_file,
										(int) ( $selected_issue['line'] ?? 0 ),
										(int) ( $selected_issue['column'] ?? 0 )
									);
									if ( $vscode_url ) :
										?>
										<a href="<?php echo esc_attr( $vscode_url ); ?>" class="button" title="<?php esc_attr_e( 'Open in VSCode', 'wp-verifier' ); ?>">
											<span class="dashicons dashicons-editor-code"></span> <?php esc_html_e( 'VSCode', 'wp-verifier' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php else : ?>
						<div class="wpv-ast-placeholder">
							<p><?php esc_html_e( 'Select an issue to see details', 'wp-verifier' ); ?></p>
						</div>
					<?php endif; ?>
				</div>

				<!-- PAN02: AI Prompt -->
				<h3 class="wpv-accordion-header panel-spacing <?php echo $selected_issue ? 'active' : ''; ?>" data-target="pan02-content">
					<?php wpverifier_header( __( 'AI Prompt', 'wp-verifier' ), 'PAN02' ); ?>
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</h3>
				<div id="pan02-content" class="wpv-accordion-content" <?php echo $selected_issue ? 'style="display:block;"' : 'style="display:none;"'; ?>>
					<?php if ( $selected_issue ) : ?>
						<div class="wpv-ast-details">
							<div class="wpv-ai-prompt-content">
								<label for="wpv-ai-prompt-text"><?php esc_html_e( 'Copy this prompt to AI:', 'wp-verifier' ); ?></label>
								<textarea id="wpv-ai-prompt-text" readonly class="wpv-ai-prompt-textarea"><?php echo esc_textarea( $ai_guidance_text ); ?></textarea>
								<div class="wpv-ai-prompt-actions">
									<button type="button" class="button button-primary wpv-copy-prompt" data-target="wpv-ai-prompt-text">
										<span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy AI Prompt', 'wp-verifier' ); ?>
									</button>
								</div>
							</div>
						</div>
					<?php else : ?>
						<div class="wpv-ast-placeholder">
							<p><?php esc_html_e( 'Select an issue to generate AI prompt', 'wp-verifier' ); ?></p>
						</div>
					<?php endif; ?>
				</div>

			</div>
		</div>
	</div>

<?php endif; ?>
