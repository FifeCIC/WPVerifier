<?php
/**
 * Basic Verification - Raw output for debugging
 */

$available_plugins = array();
if ( function_exists( 'get_plugins' ) ) {
	$all_plugins = get_plugins();
	foreach ( $all_plugins as $plugin_basename => $plugin_data ) {
		$available_plugins[ $plugin_basename ] = $plugin_data;
	}
}
?>

<div style="max-width: 800px;">
	<h2>Basic Verification</h2>
	<p>Simple, raw output to verify the checking process works correctly.</p>
	
	<?php if ( ! empty( $available_plugins ) ) : ?>
		<form id="basic-verify-form">
			<table class="form-table">
				<tr>
					<th><label for="basic-plugin-select">Select Plugin:</label></th>
					<td>
						<select id="basic-plugin-select" name="plugin" style="min-width: 300px;">
							<option value="">-- Select Plugin --</option>
							<?php foreach ( $available_plugins as $basename => $data ) : ?>
								<option value="<?php echo esc_attr( $basename ); ?>">
									<?php echo esc_html( $data['Name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label>Check Types:</label></th>
					<td>
						<label><input type="checkbox" name="types[]" value="error" checked> Errors</label><br>
						<label><input type="checkbox" name="types[]" value="warning" checked> Warnings</label>
					</td>
				</tr>
			</table>
			<p>
				<button type="submit" class="button button-primary">Run Basic Check</button>
				<span id="basic-spinner" class="spinner" style="float: none; display: none;"></span>
			</p>
		</form>
		
		<div id="basic-results" style="display: none; margin-top: 30px;">
			<h3>Raw Results</h3>
			<div style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
				<pre id="basic-output" style="white-space: pre-wrap; word-wrap: break-word; max-height: 600px; overflow-y: auto;"></pre>
			</div>
		</div>
	<?php else : ?>
		<p>No plugins found.</p>
	<?php endif; ?>
</div>
