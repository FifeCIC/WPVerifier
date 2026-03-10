<?php
/**
 * Template for Architecture tab - Plugin Check Process Flow
 *
 * @package wp-verifier
 */

// Get current active plugin for validation
$current_plugin = null;
$last_plugin = get_user_meta( get_current_user_id(), 'wpv_last_selected_plugin', true );
if ( $last_plugin ) {
	$plugins = get_plugins();
	if ( isset( $plugins[ $last_plugin ] ) ) {
		$current_plugin = array(
			'slug' => $last_plugin,
			'name' => $plugins[ $last_plugin ]['Name'],
			'folder' => strpos( $last_plugin, '/' ) !== false ? dirname( $last_plugin ) : $last_plugin,
		);
	}
}

// Validation functions
function validate_plugin_files( $plugin_folder ) {
	$validations = array();
	
	if ( ! $plugin_folder ) {
		return array( array( 'status' => 'error', 'message' => 'No active plugin selected' ) );
	}
	
	$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_folder;
	$results_file = $plugin_path . '/.wpv-results.json';
	$verification_file = $plugin_path . '/.wpv-verification.json';
	
	// Check .wpv-results.json
	if ( file_exists( $results_file ) ) {
		$results_data = json_decode( file_get_contents( $results_file ), true );
		if ( $results_data ) {
			$validations[] = array(
				'status' => 'success',
				'message' => '.wpv-results.json exists and valid',
				'details' => sprintf( 'Errors: %d, Warnings: %d, Ignored paths: %d', 
					$results_data['readiness']['errors'] ?? 0,
					$results_data['readiness']['warnings'] ?? 0,
					count( $results_data['ignored_paths'] ?? array() )
				)
			);
			
			// Check ignored paths configuration
			if ( isset( $results_data['ignored_paths'] ) && ! empty( $results_data['ignored_paths'] ) ) {
				$ignored_count = count( $results_data['ignored_paths'] );
				$validations[] = array(
					'status' => 'info',
					'message' => "Ignored paths configured: {$ignored_count} paths",
					'details' => implode( ', ', array_column( $results_data['ignored_paths'], 'path' ) )
				);
				
				// Check if ActionScheduler is in ignored paths but still appears in results
				$action_scheduler_ignored = false;
				foreach ( $results_data['ignored_paths'] as $ignored ) {
					if ( strpos( $ignored['path'], 'action-scheduler' ) !== false ) {
						$action_scheduler_ignored = true;
						break;
					}
				}
				
				if ( $action_scheduler_ignored && isset( $results_data['results'] ) ) {
					$action_scheduler_in_results = false;
					foreach ( array_keys( $results_data['results'] ) as $file_path ) {
						if ( strpos( $file_path, 'action-scheduler' ) !== false ) {
							$action_scheduler_in_results = true;
							break;
						}
					}
					
					if ( $action_scheduler_in_results ) {
						$validations[] = array(
							'status' => 'error',
							'message' => '🔴 ISSUE CONFIRMED: ActionScheduler in results despite being ignored',
							'details' => 'This confirms the ignored paths bug - files are being scanned despite ignore configuration'
						);
					} else {
						$validations[] = array(
							'status' => 'success',
							'message' => 'ActionScheduler properly ignored in results'
						);
					}
				}
			} else {
				$validations[] = array(
					'status' => 'warning',
					'message' => 'No ignored paths configured'
				);
			}
			
			// Check file hashes
			if ( isset( $results_data['file_hashes'] ) ) {
				$hash_count = count( $results_data['file_hashes'] );
				$validations[] = array(
					'status' => 'success',
					'message' => "File hashes stored: {$hash_count} files",
					'details' => 'Hash-based incremental scanning enabled'
				);
			} else {
				$validations[] = array(
					'status' => 'warning',
					'message' => 'No file hashes found - incremental scanning disabled'
				);
			}
		} else {
			$validations[] = array(
				'status' => 'error',
				'message' => '.wpv-results.json exists but contains invalid JSON'
			);
		}
	} else {
		$validations[] = array(
			'status' => 'warning',
			'message' => '.wpv-results.json not found - no scan results available'
		);
	}
	
	// Check .wpv-verification.json
	if ( file_exists( $verification_file ) ) {
		$verification_data = json_decode( file_get_contents( $verification_file ), true );
		if ( $verification_data ) {
			$file_hash_count = count( $verification_data['file_hashes'] ?? array() );
			$function_level_count = count( $verification_data['function_level'] ?? array() );
			$validations[] = array(
				'status' => 'success',
				'message' => '.wpv-verification.json exists and valid',
				'details' => "File hashes: {$file_hash_count}, Function level: {$function_level_count}"
			);
		} else {
			$validations[] = array(
				'status' => 'error',
				'message' => '.wpv-verification.json exists but contains invalid JSON'
			);
		}
	} else {
		$validations[] = array(
			'status' => 'info',
			'message' => '.wpv-verification.json not found - verification tracking not initialized'
		);
	}
	
	return $validations;
}

$validations = validate_plugin_files( $current_plugin['folder'] ?? null );

?>

<div class="wrap">
	<h2><?php wpverifier_header( 'Plugin Check Architecture', 'TAB12-01' ); ?></h2>
	
	<div class="wpv-arch-intro">
		<p><?php esc_html_e( 'This tab shows the complete plugin verification process flow and validates the current active plugin configuration.', 'wp-verifier' ); ?></p>
		<?php if ( $current_plugin ) : ?>
			<p><strong><?php esc_html_e( 'Active Plugin:', 'wp-verifier' ); ?></strong> <?php echo esc_html( $current_plugin['name'] ); ?> (<?php echo esc_html( $current_plugin['folder'] ); ?>)</p>
		<?php else : ?>
			<p class="wpv-arch-no-plugin"><strong><?php esc_html_e( 'No active plugin selected. Please run a scan first.', 'wp-verifier' ); ?></strong></p>
		<?php endif; ?>
	</div>

	<!-- Two Column Layout -->
	<div class="wpv-arch-grid">
		
		<!-- Left Column: Process Flow -->
		<div class="wpv-arch-panel">
			<h3><?php wpverifier_header( 'Verification Process Flow', 'TAB12-02' ); ?></h3>
			
			<div class="wpv-arch-flow">
				<div class="wpv-arch-step">
					<strong>1. SCAN INITIATION</strong><br>
					→ <a href="vscode://file/c:/wamp64/www/Ecosystem/wp-content/plugins/WPVerifier/includes/Admin/Admin_AJAX.php:264"><code>Admin_AJAX::run_checks()</code></a><br>
					→ <a href="vscode://file/c:/wamp64/www/Ecosystem/wp-content/plugins/WPVerifier/includes/Checker/AJAX_Runner.php"><code>AJAX_Runner::run()</code></a><br>
					→ <a href="vscode://file/c:/wamp64/www/Ecosystem/wp-content/plugins/WPVerifier/includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php:62"><code>Abstract_PHP_CodeSniffer_Check::run()</code></a>
				</div>
				
				<div class="wpv-arch-step wpv-arch-step-warning">
					<strong>2. FILE FILTERING ⚠️</strong><br>
					→ <a href="vscode://file/c:/wamp64/www/Ecosystem/wp-content/plugins/WPVerifier/includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php:189"><code>get_files_to_scan()</code></a><br>
					→ <a href="vscode://file/c:/wamp64/www/Ecosystem/wp-content/plugins/WPVerifier/includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php:230"><code>get_php_files()</code></a><br>
					→ <a href="vscode://file/c:/wamp64/www/Ecosystem/wp-content/plugins/WPVerifier/includes/Utilities/Plugin_Request_Utility.php"><code>Plugin_Request_Utility::get_directories_to_ignore()</code></a><br>
					<span class="wpv-arch-step-issue"><strong>ISSUE LOCATION</strong></span>
				</div>
				
				<div class="wpv-arch-step">
					<strong>3. PHPCS EXECUTION</strong><br>
					→ <a href="vscode://file/c:/wamp64/www/Ecosystem/wp-content/plugins/WPVerifier/includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php:285"><code>get_argv_defaults()</code></a><br>
					→ <code>PHP_CodeSniffer\Runner::runPHPCS()</code><br>
					→ Parse JSON results
				</div>
				
				<div class="wpv-arch-step">
					<strong>4. HASH GENERATION</strong><br>
					→ <a href="vscode://file/c:/wamp64/www/Ecosystem/wp-content/plugins/WPVerifier/includes/Verification/Hash_Generator.php"><code>Hash_Generator::generate_file_hash()</code></a><br>
					→ <a href="vscode://file/c:/wamp64/www/Ecosystem/wp-content/plugins/WPVerifier/includes/Verification/JSON_Storage.php"><code>JSON_Storage::initialize_verification_file()</code></a><br>
					→ Store in verification tracking
				</div>
				
				<div class="wpv-arch-step">
					<strong>5. RESULTS PROCESSING</strong><br>
					→ <a href="vscode://file/c:/wamp64/www/Ecosystem/wp-content/plugins/WPVerifier/includes/Admin/Admin_AJAX.php:1050"><code>Admin_AJAX::save_results()</code></a><br>
					→ <a href="vscode://file/c:/wamp64/www/Ecosystem/wp-content/plugins/WPVerifier/includes/Admin/Admin_AJAX.php:1598"><code>apply_ignored_paths_filter()</code></a><br>
					→ Calculate readiness score<br>
					→ Save to <code>.wpv-results.json</code>
				</div>
			</div>
		</div>
		
		<!-- Right Column: Live Validation -->
		<div class="wpv-arch-panel">
			<h3><?php wpverifier_header( 'Live Configuration Validation', 'TAB12-03' ); ?></h3>
			
			<?php if ( $current_plugin ) : ?>
				<div class="wpv-arch-plugin-info">
					<strong><?php echo esc_html( $current_plugin['name'] ); ?></strong><br>
					<small>Plugin Folder: <?php echo esc_html( $current_plugin['folder'] ); ?></small>
				</div>
				
				<?php foreach ( $validations as $validation ) : ?>
					<div class="wpv-arch-validation wpv-arch-validation-<?php echo esc_attr( $validation['status'] ); ?>">
						<div class="wpv-arch-validation-header">
							<?php 
							switch ( $validation['status'] ) {
								case 'success': echo '✅'; break;
								case 'error': echo '❌'; break;
								case 'warning': echo '⚠️'; break;
								default: echo 'ℹ️'; break;
							}
							?>
							<?php echo esc_html( $validation['message'] ); ?>
						</div>
						<?php if ( isset( $validation['details'] ) ) : ?>
							<div class="wpv-arch-validation-details"><?php echo esc_html( $validation['details'] ); ?></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
				
			<?php else : ?>
				<div class="wpv-arch-no-selection">
					<p><?php esc_html_e( 'No active plugin selected.', 'wp-verifier' ); ?></p>
					<p><?php esc_html_e( 'Please run a verification scan to see live validation results.', 'wp-verifier' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Key Functions Analysis -->
	<div class="wpv-arch-functions-panel">
		<h3><?php wpverifier_header( 'Key Functions & Files', 'TAB12-04' ); ?></h3>
		
		<table class="wp-list-table widefat fixed striped wpv-arch-functions-table">
			<thead>
				<tr>
					<th><?php wpverifier_header( 'Function/Method', 'TAB12-05' ); ?></th>
					<th><?php wpverifier_header( 'File Location', 'TAB12-06' ); ?></th>
					<th><?php wpverifier_header( 'Purpose & Configuration', 'TAB12-07' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>get_files_to_scan()</code></td>
					<td>Abstract_PHP_CodeSniffer_Check.php</td>
					<td>Hash comparison for incremental scanning. <strong>May bypass ignore filters.</strong></td>
				</tr>
				<tr>
					<td><code>get_php_files()</code></td>
					<td>Abstract_PHP_CodeSniffer_Check.php</td>
					<td>Discovers PHP files, applies ignore patterns. <strong>Check ignore logic here.</strong></td>
				</tr>
				<tr>
					<td><code>get_directories_to_ignore()</code></td>
					<td>Plugin_Request_Utility.php</td>
					<td>Returns ignored directory patterns (vendor/, node_modules/, etc.)</td>
				</tr>
				<tr>
					<td><code>apply_ignored_paths_filter()</code></td>
					<td>Admin_AJAX.php</td>
					<td>Applies JSON-configured ignored paths via WordPress filter</td>
				</tr>
				<tr>
					<td><code>get_argv_defaults()</code></td>
					<td>Abstract_PHP_CodeSniffer_Check.php</td>
					<td>Builds PHPCS --ignore patterns. <strong>May not include JSON ignored paths.</strong></td>
				</tr>
				<tr>
					<td><code>JSON_Storage::initialize_verification_file()</code></td>
					<td>Verification/JSON_Storage.php</td>
					<td>Creates .wpv-verification.json for tracking file/function verification status</td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Issue Analysis -->
	<div class="wpv-arch-issue-panel">
		<h3 class="wpv-arch-issue-header"><?php wpverifier_header( 'Current Issue: ActionScheduler.php Being Scanned', 'TAB12-08' ); ?></h3>
		
		<p><strong>Problem:</strong> Files in ignored paths (action-scheduler, carbon-fields) are appearing in scan results despite being configured as ignored.</p>
		
		<h4><?php wpverifier_header( 'Investigation Steps:', 'TAB12-09B' ); ?></h4>
		<ol>
			<li>Check if <code>get_files_to_scan()</code> respects ignore patterns when returning specific files</li>
			<li>Verify <code>get_argv_defaults()</code> includes JSON ignored paths in --ignore parameter</li>
			<li>Test path format consistency (forward vs backslashes)</li>
			<li>Confirm filter application order in scan pipeline</li>
		</ol>
	</div>

	<!-- Files Actually Ignored Panel -->
	<div class="wpv-arch-ignored-panel">
		<h3><?php wpverifier_header( 'Files Actually Ignored During Processing', 'TAB12-09A' ); ?></h3>
		
		<?php if ( $current_plugin ) : ?>
			<?php 
			$plugin_path = WP_PLUGIN_DIR . '/' . $current_plugin['folder'];
			$results_file = $plugin_path . '/.wpv-results.json';
			$ignored_files_found = array();
			
			if ( file_exists( $results_file ) ) {
				$results_data = json_decode( file_get_contents( $results_file ), true );
				if ( $results_data && isset( $results_data['ignored_paths'] ) && isset( $results_data['file_hashes'] ) ) {
					// Check which files in file_hashes match ignored paths
					foreach ( $results_data['file_hashes'] as $file_path => $hash ) {
						foreach ( $results_data['ignored_paths'] as $ignored_path ) {
							$pattern = str_replace( '\/', '/', $ignored_path['path'] );
							if ( strpos( str_replace( '\\', '/', $file_path ), $pattern ) !== false ) {
								$ignored_files_found[] = array(
									'file' => basename( $file_path ),
									'path' => $file_path,
									'matched_pattern' => $ignored_path['path'],
									'reason' => $ignored_path['reason']
								);
							}
						}
					}
				}
			}
			?>
			
			<?php if ( ! empty( $ignored_files_found ) ) : ?>
				<div class="wpv-arch-ignored-error">
					<strong>⚠️ ISSUE CONFIRMED:</strong> <?php echo count( $ignored_files_found ); ?> files from ignored paths were processed and appear in file_hashes
				</div>
				
				<table class="wp-list-table widefat fixed striped wpv-arch-ignored-table">
					<thead>
						<tr>
							<th>File</th>
							<th>Matched Pattern</th>
							<th>Reason</th>
							<th>Full Path</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $ignored_files_found as $file ) : ?>
							<tr>
								<td><code><?php echo esc_html( $file['file'] ); ?></code></td>
								<td><code><?php echo esc_html( $file['matched_pattern'] ); ?></code></td>
								<td><?php echo esc_html( $file['reason'] ); ?></td>
								<td class="wpv-arch-ignored-path"><?php echo esc_html( $file['path'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="wpv-arch-ignored-success">
					<strong>✅ GOOD:</strong> No files from ignored paths found in processing results
				</div>
			<?php endif; ?>
		<?php else : ?>
			<div class="wpv-arch-no-plugin-dashed">
				<p><strong>No active plugin selected.</strong></p>
				<p>Please run a verification scan to see ignored files analysis.</p>
			</div>
		<?php endif; ?>
	</div>

	<!-- Development Guidance -->
	<div class="wpv-arch-guidance-panel">
		<h3><?php wpverifier_header( 'Development Guidance', 'TAB12-10' ); ?></h3>
		
		<p><strong>For AI Development:</strong></p>
		<ul>
			<li>Focus on the file filtering pipeline in <code>Abstract_PHP_CodeSniffer_Check.php</code></li>
			<li>Ensure ignored paths are respected in both full scans and incremental (hash-based) scans</li>
			<li>Test path format consistency across Windows/Unix systems</li>
			<li>Verify PHPCS --ignore parameter includes all ignore sources</li>
			<li>Check filter application order and timing</li>
		</ul>
		
		<p><strong>See:</strong> <code>@ROADMAP.md</code> for complete development roadmap and implementation priorities.</p>
	</div>
</div>