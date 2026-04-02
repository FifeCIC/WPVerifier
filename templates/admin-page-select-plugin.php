<?php
/**
 * Select Plugin Tab - Choose active plugin for verification
 *
 * @package wp-verifier
 */

use WordPress\Plugin_Check\Admin\Saved_Results_Handler;
use WordPress\Plugin_Check\Utilities\Path_Builder;

// Handle form submission
if ( isset( $_POST['set_active_plugin'] ) && isset( $_POST['plugin'] ) && ! empty( $_POST['plugin'] ) ) {
	$plugin_basename = sanitize_text_field( $_POST['plugin'] );
	update_user_meta( get_current_user_id(), 'wpv_last_selected_plugin', $plugin_basename );
	
	// Create missing WPV files
	$plugin_folder = strpos( $plugin_basename, '/' ) !== false ? dirname( $plugin_basename ) : $plugin_basename;
	
	// Initialize verification file
	if ( ! class_exists( 'WordPress\\Plugin_Check\\Verification\\JSON_Storage' ) ) {
		require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Verification/JSON_Storage.php';
	}
	\WordPress\Plugin_Check\Verification\JSON_Storage::initialize_verification_file( $plugin_folder );
	
	// Initialize results file
	$results_file = Path_Builder::get_results_file_path( $plugin_basename );
	if ( ! file_exists( $results_file ) ) {
		$default_results = array(
			'version' => '1.0',
			'plugin' => $plugin_basename,
			'timestamp' => current_time( 'c' ),
			'results' => array(),
			'summary' => array(
				'total_files' => 0,
				'total_issues' => 0,
				'errors' => 0,
				'warnings' => 0
			)
		);
		file_put_contents( $results_file, wp_json_encode( $default_results, JSON_PRETTY_PRINT ) );
	}
	
	// Initialize config file
	if ( ! class_exists( 'WordPress\\Plugin_Check\\Verification\\Config_Storage' ) ) {
		require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Verification/Config_Storage.php';
	}
	$config_storage = new \WordPress\Plugin_Check\Verification\Config_Storage( $plugin_basename );
	$config_storage->save_config_data( $config_storage->load_config_data() );
	
	$success_message = __( 'Plugin selected successfully and WPV files initialized. Go to the Configuration tab to view the results.', 'wpverifier' );
}

$available_plugins = array();
if ( function_exists( 'get_plugins' ) ) {
	$all_plugins = get_plugins();
	foreach ( $all_plugins as $plugin_basename => $plugin_data ) {
		$available_plugins[ $plugin_basename ] = $plugin_data;
	}
}

$saved_results = Saved_Results_Handler::get_saved_results();
$scanned_plugins = array();
foreach ( $saved_results as $result ) {
	$plugin_slug = strtolower( str_replace( ' ', '-', $result['plugin'] ) );
	$scanned_plugins[ $plugin_slug ] = $result;
}

$current_plugin = get_user_meta( get_current_user_id(), 'wpv_last_selected_plugin', true );
$selected_plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( $_POST['plugin'] ) : '';

// Function to check if WPV files exist
function wpv_check_files_exist( $plugin_basename ) {
	$plugin_dir = Path_Builder::get_plugin_directory_path( $plugin_basename );
	$results_file = $plugin_dir . '/.wpv-results.json';
	$verification_file = $plugin_dir . '/.wpv-verification.json';
	$config_file = $plugin_dir . '/.wpv-config.json';
	
	return array(
		'results' => file_exists( $results_file ),
		'verification' => file_exists( $verification_file ),
		'config' => file_exists( $config_file )
	 );
}
?>

<div class="wrap">
	<h2><?php wpverifier_header( 'Select Plugin - Choose Active Plugin', 'TAB01' ); ?></h2>
	<p><?php esc_html_e( 'Select the plugin you want to verify. This will be used across all verification tabs.', 'wpverifier' ); ?></p>

	<?php if ( isset( $success_message ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( $success_message ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $available_plugins ) ) : ?>
		<form method="post" action="">
			<div style="max-width: 800px;">
				<table class="form-table">
					<tr>
						<th><label for="plugin-select"><?php esc_html_e( 'Available Plugins:', 'wpverifier' ); ?></label></th>
						<td>
							<select id="plugin-select" name="plugin" style="min-width: 400px;" onchange="this.form.submit()">
								<option value=""><?php esc_html_e( '-- Select Plugin --', 'wpverifier' ); ?></option>
								<?php foreach ( $available_plugins as $basename => $data ) : ?>
									<option value="<?php echo esc_attr( $basename ); ?>" <?php selected( $selected_plugin, $basename ); ?>>
										<?php echo esc_html( $data['Name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>

				<p>
					<button type="submit" name="set_active_plugin" class="button button-primary" <?php echo empty( $selected_plugin ) ? 'disabled' : ''; ?>>
						<?php esc_html_e( 'Set as Active Plugin', 'wpverifier' ); ?>
					</button>
				</p>

				<?php if ( ! empty( $selected_plugin ) ) : 
					$plugin_data = $available_plugins[ $selected_plugin ] ?? null;
					if ( $plugin_data ) :
						$file_status = wpv_check_files_exist( $selected_plugin );
					?>
					<div style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">
						<h3><?php wpverifier_header( 'Plugin Information', 'PAN03' ); ?></h3>
						<table class="form-table" style="margin-top: 0;">
							<tr>
								<th style="width: 150px;">Plugin Name</th>
								<td><?php echo esc_html( $plugin_data['Name'] ); ?></td>
							</tr>
							<tr>
								<th>Plugin File</th>
								<td><code><?php echo esc_html( $selected_plugin ); ?></code></td>
							</tr>
							<tr>
								<th>WPV Files Status</th>
								<td>
									<div style="font-family: monospace; font-size: 12px;">
										<div>📄 .wpv-results.json: 
											<?php if ( $file_status['results'] ) : ?>
												<span style="color: green;">✓ File exists</span>
											<?php else : ?>
												<span style="color: #0073aa;">ℹ Will be created</span>
											<?php endif; ?>
										</div>
										<div>📄 .wpv-verification.json: 
											<?php if ( $file_status['verification'] ) : ?>
												<span style="color: green;">✓ File exists</span>
											<?php else : ?>
												<span style="color: #0073aa;">ℹ Will be created</span>
											<?php endif; ?>
										</div>										
										<div>📄 .wpv-config.json: 
											<?php if ( $file_status['config'] ) : ?>
												<span style="color: green;">✓ File exists</span>
											<?php else : ?>
												<span style="color: #0073aa;">ℹ Will be created</span>
											<?php endif; ?>
										</div>
									</div>
								</td>
							</tr>
						</table>
					</div>
					<?php endif;
				endif; ?>
			</div>
		</form>
	<?php else : ?>
		<p><?php esc_html_e( 'No plugins found.', 'wpverifier' ); ?></p>
	<?php endif; ?>

</div>