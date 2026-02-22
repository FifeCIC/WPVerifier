<?php
/**
 * Template for the Explore Plugin tab.
 *
 * @package wp-verifier
 */

?>

<div class="plugin-explore-content">
	<div class="plugin-explore-main">
		<h2><?php esc_html_e( 'Explore Plugin Files', 'wp-verifier' ); ?></h2>
		<p><?php esc_html_e( 'Browse plugin files and manage ignore rules before running checks.', 'wp-verifier' ); ?></p>
		
		<?php if ( ! empty( $available_plugins ) ) { ?>
			<div style="margin: 20px 0;">
				<label for="plugin-explore-dropdown"><strong><?php esc_html_e( 'Select Plugin:', 'wp-verifier' ); ?></strong></label>
				<select id="plugin-explore-dropdown" style="min-width: 300px; margin-left: 10px;">
					<option value=""><?php esc_html_e( 'Select Plugin', 'wp-verifier' ); ?></option>
					<?php foreach ( $available_plugins as $plugin_basename => $plugin_data ) { ?>
						<option value="<?php echo esc_attr( $plugin_basename ); ?>">
							<?php echo esc_html( $plugin_data['Name'] ); ?>
						</option>
					<?php } ?>
				</select>
			</div>

			<div id="plugin-explore-tree" style="display: none; margin-top: 20px;">
				<div style="background: #fff; border: 1px solid #ddd; padding: 20px; border-radius: 4px;">
					<h3 id="plugin-explore-title"></h3>
					<div id="plugin-tree-container" style="font-family: monospace; font-size: 13px; line-height: 1.8;"></div>
				</div>
			</div>
		<?php } else { ?>
			<p><?php esc_html_e( 'No plugins available.', 'wp-verifier' ); ?></p>
		<?php } ?>
	</div>

	<div class="plugin-explore-sidebar">
		<div id="plugin-explore-sidebar-content">
			<div id="plugin-explore-placeholder">
				<p><?php esc_html_e( 'Select a file or folder to manage ignore rules', 'wp-verifier' ); ?></p>
			</div>
			<div id="plugin-explore-details" style="display: none;">
				<h3 id="explore-item-name"></h3>
				<p id="explore-item-path" style="font-size: 12px; color: #666; word-break: break-all;"></p>
				
				<div class="explore-actions">
					<h4><?php esc_html_e( 'Ignore Rules', 'wp-verifier' ); ?></h4>
					<p>
						<button type="button" id="explore-ignore-file" class="button" style="display: none;"><?php esc_html_e( 'Ignore This File', 'wp-verifier' ); ?></button>
						<button type="button" id="explore-ignore-folder" class="button" style="display: none;"><?php esc_html_e( 'Ignore This Folder', 'wp-verifier' ); ?></button>
					</p>
				</div>

				<div id="explore-current-rules" style="margin-top: 20px;">
					<h4><?php esc_html_e( 'Current Ignore Rules', 'wp-verifier' ); ?></h4>
					<div id="explore-rules-list"></div>
				</div>
			</div>
		</div>
	</div>
</div>
