<?php
/**
 * Preparation Tab - Vendor/Library Detection & Ignore Rules
 *
 * @package wp-verifier
 */

use WordPress\Plugin_Check\Utilities\Ignore_Rules;

$available_plugins = array();
if ( function_exists( 'get_plugins' ) ) {
	$all_plugins = get_plugins();
	foreach ( $all_plugins as $plugin_basename => $plugin_data ) {
		$available_plugins[ $plugin_basename ] = $plugin_data;
	}
}

$rules = Ignore_Rules::get_rules();
?>

<div class="wrap">
	<h2><?php esc_html_e( 'Preparation - Vendor Detection', 'wp-verifier' ); ?></h2>
	<p><?php esc_html_e( 'Detect and exclude vendor/library folders before running verification.', 'wp-verifier' ); ?></p>

	<?php if ( ! empty( $available_plugins ) ) : ?>
		<div style="max-width: 800px;">
			<table class="form-table">
				<tr>
					<th><label for="prep-plugin-select"><?php esc_html_e( 'Select Plugin:', 'wp-verifier' ); ?></label></th>
					<td>
						<select id="prep-plugin-select" name="plugin" style="min-width: 300px;">
							<option value=""><?php esc_html_e( '-- Select Plugin --', 'wp-verifier' ); ?></option>
							<?php foreach ( $available_plugins as $basename => $data ) : ?>
								<option value="<?php echo esc_attr( $basename ); ?>">
									<?php echo esc_html( $data['Name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>

			<div id="vendor-detection-results" style="display: none; margin-top: 20px;">
				<h3><?php esc_html_e( 'Detected Vendor Folders', 'wp-verifier' ); ?></h3>
				<div id="vendor-list"></div>
				<p>
					<button type="button" id="confirm-ignore-vendors" class="button button-primary">
						<?php esc_html_e( 'Confirm Ignore', 'wp-verifier' ); ?>
					</button>
					<span id="prep-spinner" class="spinner" style="float: none;"></span>
				</p>
			</div>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'No plugins found.', 'wp-verifier' ); ?></p>
	<?php endif; ?>

	<hr style="margin: 40px 0;">

	<h2><?php esc_html_e( 'Ignore Rules Management', 'wp-verifier' ); ?></h2>
	<p><?php esc_html_e( 'Manage rules to filter out third-party code and false positives from verification results.', 'wp-verifier' ); ?></p>

	<div style="display: flex; gap: 20px; margin: 20px 0;">
		<button type="button" class="button" onclick="document.getElementById('add-rule-form').style.display='block'"><?php esc_html_e( 'Add Rule', 'wp-verifier' ); ?></button>
		<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wpv_export_ignore_rules' ), 'wpv_export_rules' ) ); ?>" class="button"><?php esc_html_e( 'Export Rules', 'wp-verifier' ); ?></a>
		<button type="button" class="button" onclick="document.getElementById('import-form').style.display='block'"><?php esc_html_e( 'Import Rules', 'wp-verifier' ); ?></button>
	</div>

	<div id="add-rule-form" style="display:none; background: #fff; padding: 20px; border: 1px solid #ccc; margin: 20px 0;">
		<h3><?php esc_html_e( 'Add Ignore Rule', 'wp-verifier' ); ?></h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'wpv_add_ignore_rule', 'wpv_nonce' ); ?>
			<input type="hidden" name="action" value="wpv_add_ignore_rule" />
			<table class="form-table">
				<tr>
					<th><label for="scope"><?php esc_html_e( 'Scope', 'wp-verifier' ); ?></label></th>
					<td>
						<select name="scope" id="scope" required>
							<option value="directory"><?php esc_html_e( 'Directory', 'wp-verifier' ); ?></option>
							<option value="file"><?php esc_html_e( 'File', 'wp-verifier' ); ?></option>
							<option value="code"><?php esc_html_e( 'Error Code', 'wp-verifier' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="plugin"><?php esc_html_e( 'Plugin', 'wp-verifier' ); ?></label></th>
					<td>
						<select name="plugin" id="plugin" style="width: 300px;">
							<option value=""><?php esc_html_e( 'Select plugin...', 'wp-verifier' ); ?></option>
							<?php 
							$last_plugin = get_user_meta( get_current_user_id(), 'wpv_last_selected_plugin', true );
							$last_plugin_dir = $last_plugin ? dirname( $last_plugin ) : '';
							foreach ( $available_plugins as $plugin_file => $plugin_data ) : 
								$plugin_dir = dirname( $plugin_file );
								$selected = ( $last_plugin_dir && $plugin_dir === $last_plugin_dir ) ? ' selected' : '';
							?>
								<option value="<?php echo esc_attr( $plugin_dir ); ?>"<?php echo $selected; ?>><?php echo esc_html( $plugin_data['Name'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Optional: Select a plugin to auto-fill the base path', 'wp-verifier' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="path"><?php esc_html_e( 'Path', 'wp-verifier' ); ?></label></th>
					<td>
						<input type="text" name="path" id="path" class="regular-text" required placeholder="includes/libraries/vendor/" />
						<p class="description"><?php esc_html_e( 'Relative path from plugin root (e.g., includes/libraries/vendor/ or vendor/)', 'wp-verifier' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="code"><?php esc_html_e( 'Error Code', 'wp-verifier' ); ?></label></th>
					<td><input type="text" name="code" id="code" class="regular-text" placeholder="WordPress.Security.EscapeOutput" /></td>
				</tr>
				<tr>
					<th><label for="reason"><?php esc_html_e( 'Reason', 'wp-verifier' ); ?></label></th>
					<td>
						<select name="reason" id="reason">
							<option value="vendor"><?php esc_html_e( 'Vendor/Library', 'wp-verifier' ); ?></option>
							<option value="other"><?php esc_html_e( 'Other', 'wp-verifier' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="note"><?php esc_html_e( 'Note', 'wp-verifier' ); ?></label></th>
					<td><input type="text" name="note" id="note" class="regular-text" /></td>
				</tr>
			</table>
			<p>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Rule', 'wp-verifier' ); ?></button>
				<button type="button" class="button" onclick="document.getElementById('add-rule-form').style.display='none'"><?php esc_html_e( 'Cancel', 'wp-verifier' ); ?></button>
			</p>
		</form>
	</div>

	<div id="import-form" style="display:none; background: #fff; padding: 20px; border: 1px solid #ccc; margin: 20px 0;">
		<h3><?php esc_html_e( 'Import Rules', 'wp-verifier' ); ?></h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<?php wp_nonce_field( 'wpv_import_rules', 'wpv_nonce' ); ?>
			<input type="hidden" name="action" value="wpv_import_ignore_rules" />
			<p>
				<input type="file" name="rules_file" accept=".json" required />
			</p>
			<p>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'wp-verifier' ); ?></button>
				<button type="button" class="button" onclick="document.getElementById('import-form').style.display='none'"><?php esc_html_e( 'Cancel', 'wp-verifier' ); ?></button>
			</p>
		</form>
	</div>

	<h3><?php esc_html_e( 'Active Rules', 'wp-verifier' ); ?></h3>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Scope', 'wp-verifier' ); ?></th>
				<th><?php esc_html_e( 'Path', 'wp-verifier' ); ?></th>
				<th><?php esc_html_e( 'Code', 'wp-verifier' ); ?></th>
				<th><?php esc_html_e( 'Reason', 'wp-verifier' ); ?></th>
				<th><?php esc_html_e( 'Note', 'wp-verifier' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'wp-verifier' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $rules ) ) : ?>
				<tr>
					<td colspan="6"><?php esc_html_e( 'No ignore rules defined.', 'wp-verifier' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $rules as $id => $rule ) : ?>
					<tr>
						<td><?php echo esc_html( ucfirst( $rule['scope'] ?? '' ) ); ?></td>
						<td><code><?php echo esc_html( $rule['path'] ?? '' ); ?></code></td>
						<td><?php echo esc_html( $rule['code'] ?? '' ); ?></td>
						<td><?php echo esc_html( ucfirst( $rule['reason'] ?? '' ) ); ?></td>
						<td><?php echo esc_html( $rule['note'] ?? '' ); ?></td>
						<td>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wpv_remove_ignore_rule&rule_id=' . $id ), 'wpv_remove_rule_' . $id ) ); ?>" 
							   onclick="return confirm('<?php esc_attr_e( 'Remove this rule?', 'wp-verifier' ); ?>');">
								<?php esc_html_e( 'Remove', 'wp-verifier' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<script>
jQuery(document).ready(function($) {
	$('#plugin').on('change', function() {
		var plugin = $(this).val();
		if (plugin) {
			var currentPath = $('#path').val();
			if (!currentPath || currentPath === 'includes/libraries/vendor/' || currentPath === 'vendor/') {
				$('#path').val(plugin + '/');
			}
		}
	});
});
</script>

<style>
.vendor-folder {
	background: #f5f5f5;
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 15px;
	margin-bottom: 10px;
}
.vendor-folder h4 {
	margin: 0 0 10px 0;
}
.vendor-item {
	padding: 5px 0;
}
.vendor-item label {
	display: flex;
	align-items: center;
	gap: 8px;
}
</style>
