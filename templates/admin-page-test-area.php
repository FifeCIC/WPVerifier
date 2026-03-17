<?php
/**
 * Template for Test Area tab - Path Builder Testing
 *
 * @package wp-verifier
 */

// Load Path_Builder for testing
if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Path_Builder' ) ) {
	require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Path_Builder.php';
}

use WordPress\Plugin_Check\Utilities\Path_Builder;

$test_results = array();
$current_plugin = get_user_meta( get_current_user_id(), 'wpv_last_selected_plugin', true );

// Test 1: Plugin Path Building
$plugin_slug = $current_plugin ?: 'makeiteasy-slider/makeiteasy-slider.php';
$plugin_path = Path_Builder::get_plugin_file_path( $plugin_slug );
$test_results[] = array(
	'name' => 'Plugin Base Path',
	'status' => $plugin_path !== false ? 'pass' : 'fail',
	'result' => $plugin_path ?: 'Failed to build path',
	'details' => 'Testing: ' . $plugin_slug,
);

// Test 2: Plugin File Path Building
$file_path = 'build/slide/render.php';
$full_file_path = Path_Builder::get_plugin_file_path( $plugin_slug, $file_path );
$test_results[] = array(
	'name' => 'Plugin File Path (Subdirectory)',
	'status' => $full_file_path !== false ? 'pass' : 'fail',
	'result' => $full_file_path ?: 'Failed to build path',
	'details' => 'Testing: ' . $file_path,
);

// Test 3: VSCode URL Generation
$vscode_url = Path_Builder::get_vscode_url( $plugin_slug, $file_path, 5, 10 );
$test_results[] = array(
	'name' => 'VSCode URL Generation',
	'status' => $vscode_url !== false ? 'pass' : 'fail',
	'result' => $vscode_url ?: 'Failed to generate URL',
	'details' => 'Testing: Line 5, Column 10',
);

// Test 4: Results File Path
$results_path = Path_Builder::get_results_file_path( $plugin_slug );
$test_results[] = array(
	'name' => 'Results File Path',
	'status' => $results_path !== false ? 'pass' : 'fail',
	'result' => $results_path ?: 'Failed to build path',
	'details' => 'Testing: .wpv-results.json',
);

// Test 5: Plugin Existence Check
$plugin_exists = Path_Builder::plugin_exists( $plugin_slug );
$test_results[] = array(
	'name' => 'Plugin Existence Check',
	'status' => $plugin_exists ? 'pass' : 'fail',
	'result' => $plugin_exists ? 'Plugin directory exists' : 'Plugin directory not found',
	'details' => 'Testing: Directory existence',
);

// Test 6: File Existence Check
$file_exists = Path_Builder::plugin_file_exists( $plugin_slug, $file_path );
$test_results[] = array(
	'name' => 'File Existence Check',
	'status' => $file_exists ? 'pass' : 'info',
	'result' => $file_exists ? 'File exists' : 'File not found (expected for test)',
	'details' => 'Testing: ' . $file_path,
);

// Test 7: Invalid Plugin Slug Handling
$invalid_path = Path_Builder::get_plugin_file_path( '' );
$test_results[] = array(
	'name' => 'Invalid Plugin Slug Handling',
	'status' => $invalid_path === false ? 'pass' : 'fail',
	'result' => $invalid_path === false ? 'Correctly returned false' : 'Should have returned false',
	'details' => 'Testing: Empty plugin slug',
);

// Test 8: Current Plugin Detection
$current_detected = Path_Builder::get_current_plugin_slug();
$test_results[] = array(
	'name' => 'Current Plugin Detection',
	'status' => $current_detected !== false ? 'pass' : 'info',
	'result' => $current_detected ?: 'No current plugin selected',
	'details' => 'Testing: User meta retrieval',
);

// Test 9: Path Normalization (Windows vs Unix)
$test_path = 'test\\path\\with\\backslashes.php';
$normalized_vscode = Path_Builder::get_vscode_url( $plugin_slug, $test_path );
$has_forward_slashes = strpos( $normalized_vscode, '/' ) !== false;
$has_backslashes = strpos( $normalized_vscode, '\\' ) !== false;
$test_results[] = array(
	'name' => 'Path Normalization',
	'status' => $has_forward_slashes && !$has_backslashes ? 'pass' : 'fail',
	'result' => $normalized_vscode ?: 'Failed to generate URL',
	'details' => 'Testing: Backslash to forward slash conversion',
);

// Test 10: Compare with Old System
$old_vscode_url = '';
if ( function_exists( 'wpv_get_vscode_url' ) ) {
	$plugin_folder = dirname( $plugin_slug );
	$old_vscode_url = wpv_get_vscode_url( $file_path, 5, 10, $plugin_folder );
}
$new_vscode_url = Path_Builder::get_vscode_url( $plugin_slug, $file_path, 5, 10 );
$urls_different = $old_vscode_url !== $new_vscode_url;
$test_results[] = array(
	'name' => 'Old vs New System Comparison',
	'status' => $urls_different ? 'info' : 'info',
	'result' => sprintf( 'Old: %s | New: %s', $old_vscode_url ?: 'N/A', $new_vscode_url ?: 'N/A' ),
	'details' => 'Comparing old wpv_get_vscode_url() with new Path_Builder',
);

?>

<div class="wrap">
	<h2><?php esc_html_e( 'Test Area - Path Builder Testing', 'wp-verifier' ); ?></h2>
	
	<div style="margin: 20px 0; padding: 15px; background: #e7f3ff; border-left: 4px solid #2271b1;">
		<h3><?php esc_html_e( 'Phase 2.2: Path Builder Validation', 'wp-verifier' ); ?></h3>
		<p><?php esc_html_e( 'Testing the new Path_Builder utility class before replacing existing path building code.', 'wp-verifier' ); ?></p>
		<p><strong><?php esc_html_e( 'Current Plugin:', 'wp-verifier' ); ?></strong> <?php echo esc_html( $current_plugin ?: 'None selected' ); ?></p>
	</div>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width: 25%;"><?php esc_html_e( 'Test Name', 'wp-verifier' ); ?></th>
				<th style="width: 10%;"><?php esc_html_e( 'Status', 'wp-verifier' ); ?></th>
				<th style="width: 35%;"><?php esc_html_e( 'Result', 'wp-verifier' ); ?></th>
				<th style="width: 30%;"><?php esc_html_e( 'Details', 'wp-verifier' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $test_results as $test ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $test['name'] ); ?></strong></td>
					<td>
						<?php if ( 'pass' === $test['status'] ) : ?>
							<span style="color: #00a32a; font-weight: bold;">✓ PASS</span>
						<?php elseif ( 'fail' === $test['status'] ) : ?>
							<span style="color: #d63638; font-weight: bold;">✗ FAIL</span>
						<?php else : ?>
							<span style="color: #666; font-weight: bold;">— INFO</span>
						<?php endif; ?>
					</td>
					<td><code style="word-break: break-all; font-size: 11px;"><?php echo esc_html( $test['result'] ); ?></code></td>
					<td><?php echo esc_html( $test['details'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div style="margin-top: 30px; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1;">
		<h3><?php esc_html_e( 'Test Summary', 'wp-verifier' ); ?></h3>
		<?php
		$passed = count( array_filter( $test_results, function( $t ) { return 'pass' === $t['status']; } ) );
		$failed = count( array_filter( $test_results, function( $t ) { return 'fail' === $t['status']; } ) );
		$total = $passed + $failed;
		?>
		<p>
			<strong><?php echo esc_html( sprintf( '%d / %d tests passed', $passed, $total ) ); ?></strong>
			<?php if ( $failed === 0 ) : ?>
				<span style="color: #00a32a; margin-left: 10px;">✓ All tests passed!</span>
			<?php else : ?>
				<span style="color: #d63638; margin-left: 10px;">✗ <?php echo esc_html( $failed ); ?> test(s) failed</span>
			<?php endif; ?>
		</p>
	</div>
	
	<hr style="margin: 30px 0;">
	
	<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
		<h3><?php esc_html_e( 'VSCode Button Test', 'wp-verifier' ); ?></h3>
		<p><?php esc_html_e( 'Test the VSCode button functionality with the new Path_Builder system.', 'wp-verifier' ); ?></p>
		
		<?php if ( $current_plugin ) : ?>
			<div style="margin: 15px 0;">
				<h4><?php esc_html_e( 'Test VSCode URLs:', 'wp-verifier' ); ?></h4>
				<table class="wp-list-table widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'File Path', 'wp-verifier' ); ?></th>
							<th><?php esc_html_e( 'VSCode Button (New)', 'wp-verifier' ); ?></th>
							<th><?php esc_html_e( 'Generated URL', 'wp-verifier' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php 
						$test_files = array(
							'makeiteasy-slider.php',
							'readme.txt',
							'build/slide/render.php',
							'build/slide/index.js',
							'build/slide/block.json',
							'build/index.js',
							'build/swiper-init.js'
						);
						foreach ( $test_files as $test_file ) :
							$test_url = Path_Builder::get_vscode_url( $current_plugin, $test_file, 10 );
						?>
							<tr>
								<td><code><?php echo esc_html( $test_file ); ?></code></td>
								<td>
									<?php if ( $test_url ) : ?>
										<a href="<?php echo esc_attr( $test_url ); ?>" class="button">
											<span class="dashicons dashicons-editor-code"></span> VSCode
										</a>
									<?php else : ?>
										<span style="color: #d63638;">Failed</span>
									<?php endif; ?>
								</td>
								<td><code style="font-size: 10px; word-break: break-all;"><?php echo esc_html( $test_url ?: 'N/A' ); ?></code></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<div class="notice notice-warning inline">
				<p><?php esc_html_e( 'No plugin selected. Please go to TAB01 to select a plugin first.', 'wp-verifier' ); ?></p>
			</div>
		<?php endif; ?>
		
		<h4><?php esc_html_e( 'Key Improvements:', 'wp-verifier' ); ?></h4>
		<ul>
			<li><?php esc_html_e( '✓ No more illogical WPVerifier defaults', 'wp-verifier' ); ?></li>
			<li><?php esc_html_e( '✓ Proper subdirectory path handling (build/slide/render.php)', 'wp-verifier' ); ?></li>
			<li><?php esc_html_e( '✓ Consistent path normalization for VSCode URIs', 'wp-verifier' ); ?></li>
			<li><?php esc_html_e( '✓ Centralized error handling', 'wp-verifier' ); ?></li>
			<li><?php esc_html_e( '✓ Single source of truth for all path building', 'wp-verifier' ); ?></li>
		</ul>
	</div>
</div>
