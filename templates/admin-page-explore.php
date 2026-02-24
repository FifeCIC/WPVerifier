<?php
/**
 * Template for the Explore Plugin tab.
 *
 * @package wp-verifier
 */

// Handle form submission
$selected_plugin = isset( $_GET['explore_plugin'] ) ? sanitize_text_field( $_GET['explore_plugin'] ) : '';
$validation_results = null;

if ( ! empty( $selected_plugin ) && isset( $available_plugins[ $selected_plugin ] ) ) {
	// Get plugin directory
	$plugin_dir = dirname( WP_PLUGIN_DIR . '/' . $selected_plugin );
	
	// Run structure validation
	$validator_path = plugin_dir_path( __FILE__ ) . '../includes/Utilities/Structure_Validator.php';
	if ( file_exists( $validator_path ) ) {
		require_once $validator_path;
		$validator = new WPVerifier\Utilities\Structure_Validator();
		$validation_results = $validator->validate_plugin_structure( $plugin_dir, $selected_plugin );
	}
}

?>

<div class="plugin-explore-content">
	<h2><?php esc_html_e( 'Plugin Structure Validation', 'wp-verifier' ); ?></h2>
	<p><?php esc_html_e( 'Check if your plugin has the required files for WordPress.org submission.', 'wp-verifier' ); ?></p>
	
	<?php if ( ! empty( $available_plugins ) ) { ?>
		<form method="get" action="" style="margin: 20px 0;">
			<input type="hidden" name="page" value="wp-verifier" />
			<input type="hidden" name="tab" value="explore" />
			
			<label for="explore_plugin"><strong><?php esc_html_e( 'Select Plugin:', 'wp-verifier' ); ?></strong></label>
			<select id="explore_plugin" name="explore_plugin" style="min-width: 300px; margin-left: 10px;">
				<option value=""><?php esc_html_e( 'Select Plugin', 'wp-verifier' ); ?></option>
				<?php foreach ( $available_plugins as $plugin_basename => $plugin_data ) { ?>
					<option value="<?php echo esc_attr( $plugin_basename ); ?>" <?php selected( $selected_plugin, $plugin_basename ); ?>>
						<?php echo esc_html( $plugin_data['Name'] ); ?>
					</option>
				<?php } ?>
			</select>
			<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Check Structure', 'wp-verifier' ); ?>" style="margin-left: 10px;" />
		</form>

		<?php if ( $validation_results ) { ?>
			<?php
			$checks = array(
				array( 'key' => 'readme_file', 'label' => 'README File', 'data' => $validation_results['readme_file'] ),
				array( 'key' => 'license_file', 'label' => 'LICENSE File', 'data' => $validation_results['license_file'] ),
				array( 'key' => 'language_folder', 'label' => 'Language Folder', 'data' => $validation_results['language_folder'] ),
				array( 'key' => 'language_files', 'label' => 'Language Files (.pot)', 'data' => $validation_results['language_files'] ),
			);
			
			$all_pass = true;
			foreach ( $checks as $check ) {
				if ( $check['data']['status'] !== 'pass' ) {
					$all_pass = false;
					break;
				}
			}
			
			$status_color = $all_pass ? '#00a32a' : '#dba617';
			$status_text = $all_pass ? 'All Required Files Present' : 'Some Files Missing or Incomplete';
			?>
			
			<div style="margin-top: 30px; padding: 25px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,0.04);">
				<h3 style="margin: 0 0 20px 0; font-size: 18px; color: <?php echo esc_attr( $status_color ); ?>;"><?php echo esc_html( $status_text ); ?></h3>
				<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
					<?php foreach ( $checks as $check ) { ?>
						<?php
						$icon = $check['data']['status'] === 'pass' ? '✓' : ( $check['data']['status'] === 'warning' ? '⚠' : '✗' );
						$color = $check['data']['status'] === 'pass' ? '#00a32a' : ( $check['data']['status'] === 'warning' ? '#dba617' : '#d63638' );
						$detail = ! empty( $check['data']['file'] ) ? $check['data']['file'] : ( ! empty( $check['data']['path'] ) ? $check['data']['path'] : ( ! empty( $check['data']['message'] ) ? $check['data']['message'] : '' ) );
						?>
						<div style="padding: 15px; border-left: 4px solid <?php echo esc_attr( $color ); ?>; background: #f9f9f9; border-radius: 3px;">
							<div style="font-weight: 600; font-size: 14px; color: <?php echo esc_attr( $color ); ?>; margin-bottom: 5px;"><?php echo esc_html( $icon . ' ' . $check['label'] ); ?></div>
							<div style="font-size: 12px; color: #666;"><?php echo esc_html( $detail ); ?></div>
						</div>
					<?php } ?>
				</div>
			</div>
		<?php } ?>
	<?php } else { ?>
		<p><?php esc_html_e( 'No plugins available.', 'wp-verifier' ); ?></p>
	<?php } ?>
</div>
