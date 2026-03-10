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
		<div class="wpv-readiness-score">
			<strong><?php esc_html_e( 'Readiness Score:', 'wp-verifier' ); ?></strong> 
			<?php echo esc_html( $results_data['readiness']['overall'] ); ?>% 
			(<?php echo esc_html( $results_data['readiness']['errors'] ); ?> errors, 
			<?php echo esc_html( $results_data['readiness']['warnings'] ); ?> warnings)
		</div>
	<?php endif; ?>

	<div class="wpv-ast-container">
		<div class="wpv-ast-layout">
			<div class="wpv-ast-table-container">
				<h3><?php wpverifier_header( sprintf( __( 'Files with Issues - %s', 'wp-verifier' ), $plugin_info['name'] ), 'FT02', true ); ?></h3>
				
				<?php if ( isset( $results_data['results'] ) && is_array( $results_data['results'] ) ) : ?>
					<div class="wpv-table-header">
						<div class="wpv-table-header-file"><?php esc_html_e( 'File', 'wp-verifier' ); ?></div>
						<div class="wpv-table-header-issues"><?php esc_html_e( 'Issues', 'wp-verifier' ); ?></div>
					</div>
					<div class="wpv-ast-table">
						<?php foreach ( $results_data['results'] as $file => $issues ) : 
							$error_count = 0;
							$warning_count = 0;
							$fixed_count = 0;
							$ignored_count = 0;
							foreach ( $issues as $issue ) {
								if ( $issue['type'] === 'ERROR' ) {
									$error_count++;
								} elseif ( $issue['type'] === 'WARNING' ) {
									$warning_count++;
								}
								if ( isset( $issue['resolved'] ) && $issue['resolved'] === true ) {
									$fixed_count++;
								}
								if ( isset( $issue['ignored'] ) && $issue['ignored'] === true ) {
									$ignored_count++;
								}
							}
						?>
							<div class="accordion-row">
								<div class="accordion-header">
									<div class="wpv-ast-file-name"><?php echo esc_html( basename( $file ) ); ?></div>
									<div class="wpv-ast-severity">
										<?php if ( $error_count > 0 ) : ?>
											<span class="wpv-ast-badge error"><?php echo esc_html( $error_count ); ?> errors</span>
										<?php endif; ?>
										<?php if ( $warning_count > 0 ) : ?>
											<span class="wpv-ast-badge warning"><?php echo esc_html( $warning_count ); ?> warnings</span>
										<?php endif; ?>
										<?php if ( $fixed_count > 0 ) : ?>
											<span class="wpv-ast-badge fixed"><?php echo esc_html( $fixed_count ); ?> fixed</span>
										<?php endif; ?>
										<?php if ( $ignored_count > 0 ) : ?>
											<span class="wpv-ast-badge ignored"><?php echo esc_html( $ignored_count ); ?> ignored</span>
										<?php endif; ?>
									</div>
								</div>
								<div class="accordion-content">
									<ul class="wpv-ast-issue-list">
										<?php foreach ( $issues as $idx => $issue ) : ?>
											<li class="wpv-ast-issue-item" data-file="<?php echo esc_attr( $file ); ?>" data-idx="<?php echo esc_attr( $idx ); ?>">
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
				<h3 class="wpv-accordion-header" data-target="pan00-content"><?php wpverifier_header( __( 'File Details', 'wp-verifier' ), 'PAN00' ); ?> <span class="dashicons dashicons-arrow-down-alt2"></span></h3>
				<div id="pan00-content" class="wpv-accordion-content">
					<div class="wpv-ast-details" id="file-details">
						<div class="wpv-ast-placeholder">
							<p><?php esc_html_e( 'Select a file to see details', 'wp-verifier' ); ?></p>
						</div>
					</div>
				</div>
				
				<h3 class="wpv-accordion-header panel-spacing" data-target="pan01-content"><?php wpverifier_header( __( 'Selected Issue Details', 'wp-verifier' ), 'PAN01' ); ?> <span class="dashicons dashicons-arrow-down-alt2"></span></h3>
				<div id="pan01-content" class="wpv-accordion-content">
					<div class="wpv-ast-details" id="saved-results-details">
						<div class="wpv-ast-placeholder">
							<p><?php esc_html_e( 'Select an issue to see details', 'wp-verifier' ); ?></p>
						</div>
					</div>
				</div>
				
				<h3 class="wpv-accordion-header panel-spacing" data-target="pan02-content"><?php wpverifier_header( __( 'AI Prompt', 'wp-verifier' ), 'PAN02' ); ?> <span class="dashicons dashicons-arrow-down-alt2"></span></h3>
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
	

<?php endif; ?>
