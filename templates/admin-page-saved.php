<?php
/**
 * Template for the Saved Results page.
 *
 * @package plugin-check
 */

use WordPress\Plugin_Check\Admin\Saved_Results_Handler;

$saved_results = Saved_Results_Handler::get_saved_results();
?>

<div style="margin-bottom: 20px;">
	<h3><?php esc_html_e( 'Saved Results', 'wp-verifier' ); ?></h3>
	<div style="display: flex; gap: 10px; overflow-x: auto; padding: 10px 0;">
		<?php foreach ( $saved_results as $result ) : ?>
			<div class="result-box" data-path="<?php echo esc_attr( $result['path'] ); ?>" style="min-width: 200px; max-width: 200px; padding: 15px; border: 2px solid #ddd; border-radius: 4px; background: #fff; display: flex; flex-direction: column; gap: 10px;">
				<div>
					<strong><?php echo esc_html( $result['plugin'] ); ?></strong><br>
					<small><?php echo esc_html( $result['files'] ); ?> <?php esc_html_e( 'files', 'wp-verifier' ); ?></small>
				</div>
				<div style="display: flex; gap: 5px;">
					<button class="button button-primary load-result" style="flex: 1;"><?php esc_html_e( 'Load', 'wp-verifier' ); ?></button>
					<button class="button delete-result" style="flex: 1;"><?php esc_html_e( 'Delete', 'wp-verifier' ); ?></button>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<div class="wpv-ast-container">
	<div class="wpv-ast-layout">
		<div class="wpv-ast-table-container">
			<h3 id="files-with-issues-heading"><?php esc_html_e( 'Files with Issues', 'wp-verifier' ); ?></h3>
			<div style="background: #f1f1f1; padding: 12px 15px; font-weight: 600; border: 1px solid #c3c4c7; display: flex; gap: 15px;">
				<div style="flex: 2;"><?php esc_html_e( 'File', 'wp-verifier' ); ?></div>
				<div style="flex: 1;"><?php esc_html_e( 'Issues', 'wp-verifier' ); ?></div>
			</div>
			<div class="wpv-ast-table" id="saved-results-table"></div>
		</div>
		
		<div class="wpv-ast-sidebar">
			<h3><?php esc_html_e( 'Issue Details', 'wp-verifier' ); ?></h3>
			<div id="saved-results-ignored-count" style="display: none;"></div>
			<div id="saved-results-actions-container" style="padding: 12px; background: #f9f9f9; border: 1px solid #ddd; margin-bottom: 15px;">
				<h4 style="margin: 0 0 10px 0;"><?php esc_html_e( 'Actions', 'wp-verifier' ); ?></h4>
			</div>
			<div class="wpv-ast-details" id="saved-results-details">
				<div class="wpv-ast-placeholder">
					<p><?php esc_html_e( 'Select a result above', 'wp-verifier' ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>
