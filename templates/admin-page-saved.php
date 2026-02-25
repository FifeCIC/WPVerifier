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
	<div style="display: flex; gap: 15px; flex-wrap: wrap; padding: 10px 0;">
		<?php foreach ( $saved_results as $result ) : ?>
			<div class="result-box" data-path="<?php echo esc_attr( $result['path'] ); ?>" style="width: 200px; height: 200px; padding: 15px; border: 2px solid #ddd; border-radius: 4px; background: #fff; display: flex; flex-direction: column; justify-content: space-between;">
				<div>
					<strong style="display: block; margin-bottom: 10px; font-size: 14px;"><?php echo esc_html( $result['plugin'] ); ?></strong>
					<div style="font-size: 13px; color: #646970; line-height: 1.6;">
						<div style="margin-bottom: 5px;">
							<span style="font-weight: 600;"><?php echo esc_html( $result['issues'] ); ?></span> 
							<?php esc_html_e( 'issues', 'wp-verifier' ); ?>
						</div>
						<div style="margin-bottom: 5px;">
							<span style="font-weight: 600;"><?php echo esc_html( $result['files'] ); ?></span> 
							<?php esc_html_e( 'files', 'wp-verifier' ); ?>
						</div>
						<?php if ( $result['ignored'] > 0 ) : ?>
							<div style="color: #dba617;">
								<span style="font-weight: 600;"><?php echo esc_html( $result['ignored'] ); ?></span> 
								<?php esc_html_e( 'ignored', 'wp-verifier' ); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<div style="display: flex; gap: 5px;">
					<button class="button button-primary load-result" style="flex: 1; font-size: 12px; padding: 4px 8px;"><?php esc_html_e( 'Load', 'wp-verifier' ); ?></button>
					<button class="button delete-result" style="flex: 1; font-size: 12px; padding: 4px 8px;"><?php esc_html_e( 'Delete', 'wp-verifier' ); ?></button>
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
			<h3><?php esc_html_e( 'Selected Issue Details', 'wp-verifier' ); ?></h3>
			<div id="saved-results-ignored-count" style="display: none;"></div>
			<div class="wpv-ast-details" id="saved-results-details">
				<div class="wpv-ast-placeholder">
					<p><?php esc_html_e( 'Select a result above', 'wp-verifier' ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>
