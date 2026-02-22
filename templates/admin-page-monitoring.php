<?php
/**
 * Template for the Plugin Monitoring tab.
 *
 * @package wp-verifier
 */

?>

<div class="plugin-monitor-content">
	<div class="plugin-monitor-table-container">
		<h2><?php esc_html_e( 'Plugin Monitoring', 'wp-verifier' ); ?></h2>
		<p><?php esc_html_e( 'Monitor plugins for file changes and track verification status.', 'wp-verifier' ); ?></p>
		
		<?php if ( ! empty( $available_plugins ) ) { ?>
			<table class="wp-list-table widefat fixed striped plugin-monitor-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Plugin', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Status', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Errors', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Warnings', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Last Check', 'wp-verifier' ); ?></th>
					</tr>
				</thead>
				<tbody id="plugin-monitor-list">
					<?php foreach ( $available_plugins as $plugin_basename => $plugin_data ) { ?>
						<tr class="plugin-monitor-row" data-plugin="<?php echo esc_attr( $plugin_basename ); ?>">
							<td>
								<strong><?php echo esc_html( $plugin_data['Name'] ); ?></strong>
								<?php if ( is_plugin_active( $plugin_basename ) ) { ?>
									<span class="plugin-active">— <?php esc_html_e( 'Active', 'wp-verifier' ); ?></span>
								<?php } ?>
							</td>
							<td class="monitor-status" data-plugin="<?php echo esc_attr( $plugin_basename ); ?>">
								<span class="dashicons dashicons-minus"></span> <?php esc_html_e( 'Not Monitored', 'wp-verifier' ); ?>
							</td>
							<td class="error-count">—</td>
							<td class="warning-count">—</td>
							<td class="last-check">—</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		<?php } else { ?>
			<p><?php esc_html_e( 'No plugins available to monitor.', 'wp-verifier' ); ?></p>
		<?php } ?>
	</div>

	<div class="plugin-monitor-sidebar-container">
		<div id="plugin-monitor-sidebar">
			<div id="plugin-monitor-placeholder">
				<p><?php esc_html_e( 'Select a plugin to view details and actions', 'wp-verifier' ); ?></p>
			</div>
			<div id="plugin-monitor-details" style="display: none;">
				<h3 id="monitor-plugin-name"></h3>
				
				<div class="monitor-actions">
					<h4><?php esc_html_e( 'Actions', 'wp-verifier' ); ?></h4>
					<p>
						<button type="button" id="plugin-check__start-monitor" class="button button-primary"><?php esc_html_e( 'Start Monitoring', 'wp-verifier' ); ?></button>
						<button type="button" id="plugin-check__stop-monitor" class="button" style="display: none;"><?php esc_html_e( 'Stop Monitoring', 'wp-verifier' ); ?></button>
						<button type="button" id="plugin-check__run-check" class="button button-secondary"><?php esc_html_e( 'Run Check Now', 'wp-verifier' ); ?></button>
						<button type="button" id="plugin-check__view-log" class="button button-secondary"><?php esc_html_e( 'View Log', 'wp-verifier' ); ?></button>
					</p>
				</div>

				<div id="plugin-check__monitor-status"></div>

				<div id="monitor-top-issues">
					<h4><?php esc_html_e( 'Top Issues', 'wp-verifier' ); ?></h4>
					<div id="monitor-issues-list">
						<p><?php esc_html_e( 'No issues found', 'wp-verifier' ); ?></p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
