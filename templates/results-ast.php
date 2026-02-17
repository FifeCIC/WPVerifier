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
			<div class="wpv-ast-details" id="wpv-ast-details">
				<div class="wpv-ast-placeholder">
					<p><?php esc_html_e( 'Select an issue to view details', 'wp-verifier' ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>
