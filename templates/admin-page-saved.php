<?php
/**
 * Template for the Saved Results page.
 *
 * @package plugin-check
 */

// Get last selected plugin
$last_plugin = get_user_meta( get_current_user_id(), 'wpv_last_selected_plugin', true );
$plugin_name = '';
if ( $last_plugin ) {
	$plugins = get_plugins();
	if ( isset( $plugins[ $last_plugin ] ) ) {
		$plugin_name = $plugins[ $last_plugin ]['Name'];
	}
}
?>

<div class="wpv-ast-container">
	<div class="wpv-ast-layout">
		<div class="wpv-ast-table-container">
			<h3 id="files-with-issues-heading"><?php echo $plugin_name ? esc_html( sprintf( __( 'Files with Issues - %s', 'wp-verifier' ), $plugin_name ) ) : esc_html__( 'Files with Issues', 'wp-verifier' ); ?></h3>
			<div id="no-results-notice" style="display: none; margin: 20px 0; padding: 20px; background: #fff; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<h4 style="margin: 0 0 10px 0;"><?php esc_html_e( 'No Verification Results Found', 'wp-verifier' ); ?></h4>
				<p style="margin: 0 0 15px 0;"><?php esc_html_e( 'To see verification results here, you need to run a plugin check first.', 'wp-verifier' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'plugins.php?page=wp-verifier&tab=verify' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Go to Advanced Verification', 'wp-verifier' ); ?>
				</a>
			</div>
			<div id="results-table-wrapper" style="display: none;">
				<div style="background: #f1f1f1; padding: 12px 15px; font-weight: 600; border: 1px solid #c3c4c7; display: flex; gap: 15px;">
					<div style="flex: 2;"><?php esc_html_e( 'File', 'wp-verifier' ); ?></div>
					<div style="flex: 1;"><?php esc_html_e( 'Issues', 'wp-verifier' ); ?></div>
				</div>
				<div class="wpv-ast-table" id="saved-results-table"></div>
			</div>
		</div>
		
		<div class="wpv-ast-sidebar">
			<h3 class="wpv-accordion-header" data-target="pan01-content" style="cursor: pointer;"><?php wpverifier_header( __( 'Selected Issue Details', 'wp-verifier' ), 'PAN01' ); ?> <span class="dashicons dashicons-arrow-down-alt2" style="float: right;"></span></h3>
			<div id="pan01-content" class="wpv-accordion-content">
				<div id="saved-results-ignored-count" style="display: none;"></div>
				
				<!-- OLD: JavaScript-generated content container -->
				<div class="wpv-ast-details" id="saved-results-details">
					<div class="wpv-ast-placeholder">
						<p><?php esc_html_e( 'Select a result above', 'wp-verifier' ); ?></p>
					</div>
				</div>
			</div>
			
			<h3 class="wpv-accordion-header" data-target="pan02-content" style="margin-top: 30px; cursor: pointer;"><?php wpverifier_header( __( 'AI Prompt', 'wp-verifier' ), 'PAN02' ); ?> <span class="dashicons dashicons-arrow-down-alt2" style="float: right;"></span></h3>
			<div id="pan02-content" class="wpv-accordion-content">
				<div class="wpv-ast-details" id="wpv-ai-guidance-panel">
					<div class="wpv-ast-placeholder">
						<p><?php esc_html_e( 'Select an issue to see AI guidance', 'wp-verifier' ); ?></p>
					</div>
				</div>
			</div>
			
			<!-- NEW: HTML-structured content -->
			<div class="wpv-ast-details-structured" id="saved-results-details-structured" style="display: none;">
				<div class="wpv-ast-detail-group">
					<label><?php esc_html_e( 'Issue ID:', 'wp-verifier' ); ?></label>
					<p><code data-field="issue_id"></code></p>
				</div>
				<div class="wpv-ast-detail-group">
					<label><?php esc_html_e( 'Filename:', 'wp-verifier' ); ?></label>
					<span><strong data-field="filename"></strong></span>
				</div>
				<div class="wpv-ast-detail-group">
					<label><?php esc_html_e( 'Path:', 'wp-verifier' ); ?></label>
					<p><code style="font-size: 11px; word-break: break-all;" data-field="path"></code></p>
				</div>
				<div class="wpv-ast-detail-group">
					<label><?php esc_html_e( 'Type:', 'wp-verifier' ); ?></label>
					<span class="wpv-ast-badge" data-field="type"></span>
				</div>
				<div class="wpv-ast-detail-group">
					<label><?php esc_html_e( 'Line:', 'wp-verifier' ); ?></label>
					<span data-field="line"></span>
				</div>
				<div class="wpv-ast-detail-group">
					<label><?php esc_html_e( 'Code:', 'wp-verifier' ); ?></label>
					<p><code data-field="code"></code></p>
				</div>
				<div class="wpv-ast-detail-group">
					<label><?php esc_html_e( 'Message:', 'wp-verifier' ); ?></label>
					<p data-field="message"></p>
				</div>
				
				<div class="wpv-ast-detail-actions">
					<button type="button" class="button wpv-copy-ai">
						<span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy for AI', 'wp-verifier' ); ?>
					</button>
					<a href="#" class="button wpv-vscode-link">
						<span class="dashicons dashicons-editor-code"></span> <?php esc_html_e( 'VSCode', 'wp-verifier' ); ?>
					</a>
					<button type="button" class="button wpv-recheck-file">
						<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Recheck File', 'wp-verifier' ); ?>
					</button>
					<a href="#" class="button button-primary wpv-fixed-link">
						<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Fixed', 'wp-verifier' ); ?>
					</a>
					<a href="#" class="button wpv-ignore-link">
						<span class="dashicons dashicons-hidden"></span> <?php esc_html_e( 'Ignore Code', 'wp-verifier' ); ?>
					</a>
					<a href="#" class="button wpv-docs-link" style="display: none;" target="_blank"><?php esc_html_e( 'Learn More', 'wp-verifier' ); ?></a>
				</div>
			</div>
		</div>
	</div>
</div>
