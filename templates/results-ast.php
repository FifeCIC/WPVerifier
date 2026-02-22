<?php
/**
 * AST (Accordion Sidebar Table) Results Template
 * 
 * @package WPVerifier
 */
?>

<div class="wpv-ast-container">
	<div class="wpv-ast-layout">
		<div class="wpv-ast-table-container">
			<div class="wpv-ast-table" id="wpv-ast-results"></div>
		</div>
		<div class="wpv-ast-sidebar">
			<div class="wpv-ast-ignored-folders" id="wpv-ast-ignored-folders" style="margin-bottom: 20px; padding: 15px; background: #f6f7f7; border-radius: 4px; display: none;">
				<h4 style="margin: 0 0 10px 0; font-size: 14px;"><?php esc_html_e( 'Ignored Folders', 'wp-verifier' ); ?></h4>
				<ul id="wpv-ignored-folders-list" style="margin: 0; padding: 0; list-style: none; font-size: 12px;"></ul>
			</div>
			<div class="wpv-ast-details" id="wpv-ast-details">
				<div class="wpv-ast-placeholder">
					<p><?php esc_html_e( 'Select an issue to view details', 'wp-verifier' ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>
