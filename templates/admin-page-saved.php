<?php
/**
 * Template for the Saved Results page.
 *
 * @package plugin-check
 */

if ( ! class_exists( 'WordPress\\Plugin_Check\\Admin\\Saved_Results_Handler' ) ) {
	require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Saved_Results_Handler.php';
}

$plugin_info = \WordPress\Plugin_Check\Admin\Saved_Results_Handler::get_last_selected_plugin();
$results_data = $plugin_info ? \WordPress\Plugin_Check\Admin\Saved_Results_Handler::load_plugin_results( $plugin_info['slug'] ) : null;
?>

<?php if ( ! $plugin_info ) : ?>
	<div class="notice notice-info">
		<p><?php esc_html_e( 'No plugin selected. Please run a verification first.', 'wp-verifier' ); ?></p>
	</div>
<?php elseif ( ! $results_data ) : ?>
	<div class="notice notice-warning">
		<p><?php echo esc_html( sprintf( __( 'No results found for %s. Please run a verification first.', 'wp-verifier' ), $plugin_info['name'] ) ); ?></p>
	</div>
<?php else : ?>
	<?php if ( isset( $results_data['readiness'] ) ) : ?>
		<div style="margin: 20px 0; padding: 15px; background: #fff; border-left: 4px solid #2271b1;">
			<strong><?php esc_html_e( 'Readiness Score:', 'wp-verifier' ); ?></strong> 
			<?php echo esc_html( $results_data['readiness']['overall'] ); ?>% 
			(<?php echo esc_html( $results_data['readiness']['errors'] ); ?> errors, 
			<?php echo esc_html( $results_data['readiness']['warnings'] ); ?> warnings)
		</div>
	<?php endif; ?>

	<div class="wpv-ast-container">
		<div class="wpv-ast-layout">
			<div class="wpv-ast-table-container">
				<h3><?php wpverifier_header( sprintf( __( 'Files with Issues - %s', 'wp-verifier' ), $plugin_info['name'] ), 'FT02' ); ?></h3>
				
				<?php if ( isset( $results_data['results'] ) && is_array( $results_data['results'] ) ) : ?>
					<div style="background: #f1f1f1; padding: 12px 15px; font-weight: 600; border: 1px solid #c3c4c7; display: flex; gap: 15px;">
						<div style="flex: 2;"><?php esc_html_e( 'File', 'wp-verifier' ); ?></div>
						<div style="flex: 1;"><?php esc_html_e( 'Issues', 'wp-verifier' ); ?></div>
					</div>
					<div class="wpv-ast-table">
						<?php foreach ( $results_data['results'] as $file => $issues ) : 
							$error_count = 0;
							$warning_count = 0;
							foreach ( $issues as $issue ) {
								if ( $issue['type'] === 'ERROR' ) {
									$error_count++;
								} elseif ( $issue['type'] === 'WARNING' ) {
									$warning_count++;
								}
							}
						?>
							<div class="accordion-row" style="border: 1px solid #c3c4c7; border-top: none; background: #fff;">
								<div class="accordion-header" style="display: flex; gap: 15px; padding: 12px 15px; cursor: pointer;">
									<div class="wpv-ast-file-name" style="flex: 2;"><?php echo esc_html( basename( $file ) ); ?></div>
									<div class="wpv-ast-severity" style="flex: 1;">
										<?php if ( $error_count > 0 ) : ?>
											<span class="wpv-ast-badge error"><?php echo esc_html( $error_count ); ?> errors</span>
										<?php endif; ?>
										<?php if ( $warning_count > 0 ) : ?>
											<span class="wpv-ast-badge warning"><?php echo esc_html( $warning_count ); ?> warnings</span>
										<?php endif; ?>
										<span class="wpv-ast-badge fixed">0 fixed</span>
									</div>
								</div>
								<div class="accordion-content" style="display: none; padding: 15px; background: #f9f9f9;">
									<ul class="wpv-ast-issue-list" style="list-style: none; margin: 0; padding: 0;">
										<?php foreach ( $issues as $idx => $issue ) : ?>
											<li class="wpv-ast-issue-item" data-file="<?php echo esc_attr( $file ); ?>" data-idx="<?php echo esc_attr( $idx ); ?>" style="padding: 10px; margin-bottom: 5px; background: #fff; border: 1px solid #ddd; cursor: pointer;">
												<span class="wpv-ast-badge <?php echo esc_attr( strtolower( $issue['type'] ) ); ?>"><?php echo esc_html( $issue['type'] ); ?></span>
												Line <?php echo esc_html( $issue['line'] ); ?>: <?php echo esc_html( $issue['message'] ); ?>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p><?php esc_html_e( 'No issues found.', 'wp-verifier' ); ?></p>
				<?php endif; ?>
			</div>
			
			<div class="wpv-ast-sidebar">
				<h3 class="wpv-accordion-header" data-target="pan00-content" style="cursor: pointer;"><?php wpverifier_header( __( 'File Details', 'wp-verifier' ), 'PAN00' ); ?> <span class="dashicons dashicons-arrow-down-alt2" style="float: right;"></span></h3>
				<div id="pan00-content" class="wpv-accordion-content">
					<div class="wpv-ast-details" id="file-details">
						<div class="wpv-ast-placeholder">
							<p><?php esc_html_e( 'Select a file to see details', 'wp-verifier' ); ?></p>
						</div>
					</div>
				</div>
				
				<h3 class="wpv-accordion-header" data-target="pan01-content" style="margin-top: 30px; cursor: pointer;"><?php wpverifier_header( __( 'Selected Issue Details', 'wp-verifier' ), 'PAN01' ); ?> <span class="dashicons dashicons-arrow-down-alt2" style="float: right;"></span></h3>
				<div id="pan01-content" class="wpv-accordion-content">
					<div class="wpv-ast-details" id="saved-results-details">
						<div class="wpv-ast-placeholder">
							<p><?php esc_html_e( 'Select an issue to see details', 'wp-verifier' ); ?></p>
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
			</div>
		</div>
	</div>
	
	<script type="text/javascript">
	const wpvSavedResults = <?php echo wp_json_encode( $results_data['results'] ); ?>;
	</script>
<?php endif; ?>
