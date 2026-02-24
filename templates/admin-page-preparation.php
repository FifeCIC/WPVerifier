<?php
/**
 * Preparation Tab - Vendor/Library Detection
 *
 * @package wp-verifier
 */

$available_plugins = array();
if ( function_exists( 'get_plugins' ) ) {
	$all_plugins = get_plugins();
	foreach ( $all_plugins as $plugin_basename => $plugin_data ) {
		$available_plugins[ $plugin_basename ] = $plugin_data;
	}
}
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
</div>

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
