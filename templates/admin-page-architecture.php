<?php
/**
 * Template for Architecture tab - Plugin Check Process Flow
 *
 * TAB STRUCTURE (CURRENT):
 * TAB01 = Select Plugin
 * TAB02 = Configure
 * TAB03 = Verification
 * TAB04 = Results (unified - was TAB04 Files + TAB05 Issues, merged in PHASE 5)
 * TAB05 = REMOVED
 * TAB06 = Plugin Monitoring
 * TAB07 = Test Area
 * TAB08 = Error Codes
 * TAB09 = Settings
 * TAB10 = Assets
 * TAB11 = Architecture (this tab)
 * TAB12 = Roadmap
 *
 * @package wp-verifier
 */

use WordPress\Plugin_Check\Utilities\Path_Builder;

$current_plugin = null;
$last_plugin = get_user_meta( get_current_user_id(), 'wpv_last_selected_plugin', true );
if ( $last_plugin ) {
	$plugins = get_plugins();
	if ( isset( $plugins[ $last_plugin ] ) ) {
		$current_plugin = array(
			'slug'   => $last_plugin,
			'name'   => $plugins[ $last_plugin ]['Name'],
			'folder' => strpos( $last_plugin, '/' ) !== false ? dirname( $last_plugin ) : $last_plugin,
		);
	}
}

// Live file validation
function validate_plugin_files( $plugin_folder ) {
	if ( ! $plugin_folder ) {
		return array( array( 'status' => 'error', 'message' => 'No active plugin selected' ) );
	}
	$plugin_path      = Path_Builder::get_plugin_directory_path( $plugin_folder );
	$results_file     = $plugin_path . '/.wpv-results.json';
	$verification_file = $plugin_path . '/.wpv-verification.json';
	$validations      = array();

	if ( file_exists( $results_file ) ) {
		$data = json_decode( file_get_contents( $results_file ), true );
		if ( $data ) {
			$total_issues = 0;
			foreach ( $data['results'] ?? array() as $issues ) {
				$total_issues += count( $issues );
			}
			$validations[] = array(
				'status'  => 'success',
				'message' => '.wpv-results.json exists and valid',
				'details' => sprintf( 'Files: %d, Issues: %d, Errors: %d, Warnings: %d',
					count( $data['results'] ?? array() ),
					$total_issues,
					$data['readiness']['errors'] ?? 0,
					$data['readiness']['warnings'] ?? 0
				),
			);
		} else {
			$validations[] = array( 'status' => 'error', 'message' => '.wpv-results.json contains invalid JSON' );
		}
	} else {
		$validations[] = array( 'status' => 'warning', 'message' => '.wpv-results.json not found' );
	}

	if ( file_exists( $verification_file ) ) {
		$vdata = json_decode( file_get_contents( $verification_file ), true );
		if ( $vdata ) {
			$ignored_count = count( $vdata['ignored_files'] ?? array() );
			$validations[] = array(
				'status'  => 'success',
				'message' => '.wpv-verification.json exists and valid',
				'details' => "Ignored files: {$ignored_count}",
			);
		} else {
			$validations[] = array( 'status' => 'error', 'message' => '.wpv-verification.json contains invalid JSON' );
		}
	} else {
		$validations[] = array( 'status' => 'info', 'message' => '.wpv-verification.json not found - no file ignores recorded yet' );
	}

	return $validations;
}

$validations = validate_plugin_files( $current_plugin['folder'] ?? null );
?>

<div class="wrap">
	<h2><?php wpverifier_header( 'Plugin Check Architecture', 'TAB11-01' ); ?></h2>

	<?php if ( $current_plugin ) : ?>
		<p><strong>Active Plugin:</strong> <?php echo esc_html( $current_plugin['name'] ); ?> (<?php echo esc_html( $current_plugin['folder'] ); ?>)</p>
	<?php else : ?>
		<p><strong>No active plugin selected. Run a scan first.</strong></p>
	<?php endif; ?>

	<div class="wpv-arch-grid">

		<!-- LEFT: Process Flow -->
		<div class="wpv-arch-panel">
			<h3><?php wpverifier_header( 'Verification Process Flow', 'TAB11-02' ); ?></h3>
			<div class="wpv-arch-flow">

				<div class="wpv-arch-step">
					<strong>1. SCAN INITIATION</strong><br>
					→ <?php echo wpv_get_vscode_button( 'includes/Admin/Admin_AJAX.php', 264, 0, null, 'Admin_AJAX::run_checks()', 'button-link' ); ?><br>
					→ <?php echo wpv_get_vscode_button( 'includes/Checker/AJAX_Runner.php', 0, 0, null, 'AJAX_Runner::run()', 'button-link' ); ?>
				</div>

				<div class="wpv-arch-step">
					<strong>2. FILE FILTERING</strong><br>
					→ <?php echo wpv_get_vscode_button( 'includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php', 189, 0, null, 'get_files_to_scan()', 'button-link' ); ?><br>
					→ Loads <code>.wpv-verification.json</code> ignored_files<br>
					→ For each file: compute MD5 hash<br>
					→ Hash matches stored → SKIP file<br>
					→ Hash differs → INVALIDATE ignore, scan normally<br>
					→ <?php echo wpv_get_vscode_button( 'includes/Utilities/Plugin_Request_Utility.php', 0, 0, null, 'get_directories_to_ignore()', 'button-link' ); ?>
				</div>

				<div class="wpv-arch-step">
					<strong>3. PHPCS EXECUTION</strong><br>
					→ <?php echo wpv_get_vscode_button( 'includes/Checker/Checks.php', 30, 0, null, 'Checks::run_checks()', 'button-link' ); ?><br>
					→ Stops at 20 issues if limit_results enabled<br>
					→ WordPress coding standards applied
				</div>

				<div class="wpv-arch-step">
					<strong>4. RESULTS PROCESSING</strong><br>
					→ <?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 200, 0, null, 'run_checks()', 'button-link' ); ?><br>
					→ issue_id = md5(file + line + column + code + counter)<br>
					→ Calculate readiness score<br>
					→ Save to <code>.wpv-results.json</code>
				</div>

			</div>
		</div>

		<!-- RIGHT: Live Validation -->
		<div class="wpv-arch-panel">
			<h3><?php wpverifier_header( 'Live Configuration Validation', 'TAB11-03' ); ?></h3>
			<?php foreach ( $validations as $v ) : ?>
				<div class="wpv-arch-validation wpv-arch-validation-<?php echo esc_attr( $v['status'] ); ?>">
					<div class="wpv-arch-validation-header">
						<?php
						switch ( $v['status'] ) {
							case 'success': echo '✅'; break;
							case 'error':   echo '❌'; break;
							case 'warning': echo '⚠️'; break;
							default:        echo 'ℹ️'; break;
						}
						?>
						<?php echo esc_html( $v['message'] ); ?>
					</div>
					<?php if ( isset( $v['details'] ) ) : ?>
						<div class="wpv-arch-validation-details"><?php echo esc_html( $v['details'] ); ?></div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- JSON Files -->
	<div class="wpv-arch-json-panel">
		<h3><?php wpverifier_header( 'JSON Files & Data Flow', 'TAB11-04' ); ?></h3>
		<div class="wpv-arch-json-files">

			<div class="wpv-arch-json-file">
				<h5><code>.wpv-results.json</code> — Active Task List</h5>
				<strong>Purpose:</strong> Only contains actionable issues. Acts as a clean task list.<br>
				<strong>Created by:</strong> <?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 575, 0, null, 'save_results_to_json()', 'button-link' ); ?><br>
				<strong>Read by:</strong> <?php echo wpv_get_vscode_button( 'templates/admin-page-results.php', 0, 0, null, 'admin-page-results.php (TAB04)', 'button-link' ); ?><br>
				<strong>Modified by:</strong> Fixed button (removes issue), Ignore button (sets ignored:true), File-ignore (removes all file issues)<br>
				<pre>{
  "generated_at": "2026-03-20 12:00:00",
  "plugin": "wpseed/wpseed.php",
  "readiness": { "overall": 0, "errors": 40, "warnings": 300 },
  "results": {
    "includes/admin/admin-settings.php": [
      {
        "issue_id": "E-0a19dbdf",
        "message": "...",
        "code": "WordPress.Security.EscapeOutput",
        "type": "ERROR",
        "line": 354,
        "column": 58,
        "ignored": false
      }
    ]
  }
}</pre>
			</div>

			<div class="wpv-arch-json-file">
				<h5><code>.wpv-verification.json</code> — Ignore & Hash Tracking</h5>
				<strong>Purpose:</strong> Tracks which files are fully ignored and their hash at time of ignore.<br>
				<strong>Created by:</strong> <?php echo wpv_get_vscode_button( 'includes/Verification/JSON_Storage.php', 0, 0, null, 'JSON_Storage.php', 'button-link' ); ?><br>
				<strong>Read by:</strong> <?php echo wpv_get_vscode_button( 'includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php', 189, 0, null, 'get_files_to_scan()', 'button-link' ); ?> during scan<br>
				<strong>Modified by:</strong> Auto file-ignore (when all issues ignored), manual unignore, hash invalidation<br>
				<pre>{
  "ignored_files": {
    "includes/admin/admin-settings.php": {
      "hash": "d41d8cd98f00b204e9800998ecf8427e",
      "ignored_at": "2026-03-20 20:00:00",
      "ignored_by": 1
    }
  }
}</pre>
			</div>

			<div class="wpv-arch-json-file">
				<h5><code>.wpv-config.json</code> — Scan Configuration</h5>
				<strong>Purpose:</strong> Stores ignored vendor paths and scan configuration.<br>
				<strong>Created by:</strong> TAB02 Configuration interface<br>
				<strong>Read by:</strong> <?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 0, 0, null, 'apply_ignored_paths_filter()', 'button-link' ); ?>
			</div>
		</div>
	</div>

	<!-- Button Behaviour -->
	<div class="wpv-arch-json-panel">
		<h3><?php wpverifier_header( 'TAB04 Button Behaviour', 'TAB11-05' ); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th>Button</th><th>AJAX Action</th><th>Handler</th><th>Effect on .wpv-results.json</th><th>Effect on .wpv-verification.json</th></tr></thead>
			<tbody>
				<tr>
					<td><strong>Fixed</strong></td>
					<td><code>wpv_mark_resolved</code></td>
					<td><?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 1062, 0, null, 'mark_issue_as_fixed()', 'button-link' ); ?></td>
					<td>Issue permanently removed. Readiness recalculated.</td>
					<td>No change</td>
				</tr>
				<tr>
					<td><strong>Ignore</strong></td>
					<td><code>wpv_mark_ignored</code></td>
					<td><?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 1011, 0, null, 'mark_issue_as_ignored()', 'button-link' ); ?></td>
					<td>Sets <code>ignored:true</code> on issue. If ALL issues in file now ignored → all file issues deleted.</td>
					<td>If all file issues ignored → adds entry to <code>ignored_files</code> with MD5 hash.</td>
				</tr>
				<tr>
					<td><strong>Unignore</strong></td>
					<td><code>wpv_mark_unignored</code></td>
					<td><?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 1060, 0, null, 'mark_issue_as_unignored()', 'button-link' ); ?></td>
					<td>Sets <code>ignored:false</code> on issue.</td>
					<td>No change</td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Key Functions -->
	<div class="wpv-arch-functions-panel">
		<h3><?php wpverifier_header( 'Key Functions', 'TAB11-06' ); ?></h3>
		<table class="wp-list-table widefat fixed striped wpv-arch-functions-table">
			<thead><tr><th>Function</th><th>File</th><th>Purpose</th></tr></thead>
			<tbody>
				<tr>
					<td><code>mark_issue_as_fixed()</code></td>
					<td>Verification_AJAX_Handler.php</td>
					<td>Removes single issue from .wpv-results.json. Uses explicit array assignment (not reference) to avoid PHP reference corruption bug.</td>
				</tr>
				<tr>
					<td><code>mark_issue_as_ignored()</code></td>
					<td>Verification_AJAX_Handler.php</td>
					<td>Sets ignored:true. Then checks if ALL issues in file are ignored → triggers mark_file_as_ignored().</td>
				</tr>
				<tr>
					<td><code>mark_file_as_ignored()</code></td>
					<td>Verification_AJAX_Handler.php</td>
					<td>PHASE 6: Computes file MD5, writes to .wpv-verification.json ignored_files, deletes all file issues from .wpv-results.json.</td>
				</tr>
				<tr>
					<td><code>get_files_to_scan()</code></td>
					<td>Abstract_PHP_CodeSniffer_Check.php</td>
					<td>PHASE 6: Loads ignored_files from .wpv-verification.json. Skips files with matching hash. Invalidates (removes) entries where hash differs.</td>
				</tr>
				<tr>
					<td><code>save_results_to_json()</code></td>
					<td>Verification_AJAX_Handler.php</td>
					<td>Writes full scan results to .wpv-results.json. issue_id = md5(file+line+column+code+counter).</td>
				</tr>
				<tr>
					<td><code>get_directories_to_ignore()</code></td>
					<td>Plugin_Request_Utility.php</td>
					<td>Returns vendor/library directory patterns to exclude from PHPCS scan.</td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Known Bugs Fixed -->
	<div class="wpv-arch-issue-panel">
		<h3><?php wpverifier_header( 'Known Bugs Fixed', 'TAB11-07' ); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th>Bug</th><th>Root Cause</th><th>Fix</th></tr></thead>
			<tbody>
				<tr>
					<td>Fixed button removed ALL issues with same code</td>
					<td>issue_id = md5(file+line+code) — identical for issues sharing file/line/code</td>
					<td>issue_id = md5(file+line+column+code+counter) — guaranteed unique</td>
				</tr>
				<tr>
					<td>Fixed button replaced all file issues with last issue in JSON</td>
					<td>PHP <code>foreach ($array as $key => &$value)</code> reference + <code>break 2</code> corrupts array data</td>
					<td>Removed <code>&</code> reference. After removal: <code>$results_data['results'][$file_path] = $issues</code> (explicit assignment)</td>
				</tr>
				<tr>
					<td>showDetails() in wp-verifier-ast.js overrode PHP-rendered PAN01 sidebar</td>
					<td>JavaScript innerHTML replacement always won over PHP rendering</td>
					<td>PHASE 5: Eliminated JavaScript panel replacement entirely. URL-driven state (?issue_id=X) lets PHP render sidebar.</td>
				</tr>
			</tbody>
		</table>
	</div>

	<p><strong>See:</strong> <?php echo wpv_get_vscode_button( 'docs/ROADMAP.md', 0, 0, null, 'ROADMAP.md', 'button-link' ); ?> for full development roadmap.</p>
</div>
 * 
 * TAB04 (Files) vs TAB05 (Issues) - BOTH USE AST BUT COMPLETELY DIFFERENT!
 * 
 * TAB04 (Files Tab):
 * - Template: admin-page-saved.php
 * - JavaScript: Uses AST templates (wp-verifier-ast.js)
 * - UI: File accordion + sidebar with panels (PAN00, PAN01, PAN02)
 * - Fixed Button Location: In PAN01 sidebar panel
 * - Data Flow: Click file → show in sidebar → click issue → show details in PAN01
 * 
 * TAB05 (Issues Tab):
 * - Template: admin-page-issues.php  
 * - JavaScript: issues-tab.js (NOT AST)
 * - UI: Flat table of all issues, no sidebar
 * - Fixed Button Location: In expandable row details
 * - Data Flow: Click row → expand details → show Fixed button
 * 
 * NEVER CONFUSE THESE TWO TABS - THEY ARE COMPLETELY DIFFERENT INTERFACES!
 */

use WordPress\Plugin_Check\Utilities\Path_Builder;

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
	
	$plugin_path = Path_Builder::get_plugin_directory_path( $plugin_folder );
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
		<div class="wpv-arch-tab-warning">
			<h4>🚨 CRITICAL: Tab Structure for AI Development</h4>
			<div class="wpv-arch-tab-structure">
				<div class="wpv-arch-tab-item">
					<strong>TAB04 (Files)</strong> - FILE-GROUPED VIEW<br>
					• Template: <code>admin-page-saved.php</code><br>
					• JavaScript: Uses AST templates<br>
					• UI: File accordion + sidebar (PAN00/PAN01/PAN02)<br>
					• Fixed Button: In PAN01 sidebar panel
				</div>
				<div class="wpv-arch-tab-item">
					<strong>TAB05 (Issues)</strong> - FLAT LIST VIEW<br>
					• Template: <code>admin-page-issues.php</code><br>
					• JavaScript: <code>issues-tab.js</code> (NOT AST)<br>
					• UI: Flat table, no sidebar<br>
					• Fixed Button: In expandable row details
				</div>
			</div>
			<p><strong>NEVER CONFUSE THESE - THEY ARE COMPLETELY DIFFERENT!</strong></p>
		</div>
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
					→ <?php echo wpv_get_vscode_button( 'includes/Admin/Admin_AJAX.php', 264, 0, null, 'Admin_AJAX::run_checks()', 'button-link' ); ?><br>
					→ <?php echo wpv_get_vscode_button( 'includes/Checker/AJAX_Runner.php', 0, 0, null, 'AJAX_Runner::run()', 'button-link' ); ?><br>
					→ <?php echo wpv_get_vscode_button( 'includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php', 62, 0, null, 'Abstract_PHP_CodeSniffer_Check::run()', 'button-link' ); ?>
				</div>
				
				<div class="wpv-arch-step wpv-arch-step-warning">
					<strong>2. FILE FILTERING ⚠️</strong><br>
					→ <?php echo wpv_get_vscode_button( 'includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php', 189, 0, null, 'get_files_to_scan()', 'button-link' ); ?><br>
					→ <?php echo wpv_get_vscode_button( 'includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php', 230, 0, null, 'get_php_files()', 'button-link' ); ?><br>
					→ <?php echo wpv_get_vscode_button( 'includes/Utilities/Plugin_Request_Utility.php', 0, 0, null, 'Plugin_Request_Utility::get_directories_to_ignore()', 'button-link' ); ?><br>
					<span class="wpv-arch-step-issue"><strong>ISSUE LOCATION</strong></span>
				</div>
				
				<div class="wpv-arch-step">
					<strong>3. PHPCS EXECUTION & EARLY TERMINATION</strong><br>
					→ <?php echo wpv_get_vscode_button( 'includes/Checker/Checks.php', 30, 0, null, 'Checks::run_checks()', 'button-link' ); ?> - Main check orchestrator<br>
					→ <?php echo wpv_get_vscode_button( 'includes/Checker/Checks.php', 60, 0, null, 'run_check_with_result()', 'button-link' ); ?> - Individual check execution<br>
					→ <?php echo wpv_get_vscode_button( 'includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php', 62, 0, null, 'Abstract_PHP_CodeSniffer_Check::run()', 'button-link' ); ?> - PHPCS wrapper<br>
					<div class="wpv-arch-substep">
						<strong>✅ NEW: Early Termination Implementation (Option 3):</strong><br>
						• <?php echo wpv_get_vscode_button( 'includes/Checker/Checks.php', 35, 0, null, 'get_issue_limit_from_request()', 'button-link' ); ?> - Reads limit_results from check_options<br>
						• <?php echo wpv_get_vscode_button( 'includes/Checker/Checks.php', 50, 0, null, 'count_issues_in_result()', 'button-link' ); ?> - Counts issues after each check<br>
						• Stops processing when 20 issues reached<br>
						• Processes checks one by one instead of batch<br>
						• Provides detailed debug logging<br>
						<strong>Key Classes & Functions:</strong><br>
						• <?php echo wpv_get_vscode_button( 'includes/Checker/AJAX_Runner.php', 0, 0, null, 'AJAX_Runner::run()', 'button-link' ); ?> - Main execution controller<br>
						• <?php echo wpv_get_vscode_button( 'includes/Checker/Abstract_Check_Runner.php', 200, 0, null, 'Abstract_Check_Runner::run()', 'button-link' ); ?> - Orchestrates check execution<br>
						• <code>get_files_to_scan()</code> - Determines which files to process<br>
						• <code>get_argv_defaults()</code> - Builds PHPCS command arguments<br>
						• <code>runPHPCS()</code> - External PHPCS execution<br>
						<strong>Configuration Applied:</strong><br>
						• Ignored directories from <?php echo wpv_get_vscode_button( 'includes/Utilities/Plugin_Request_Utility.php', 0, 0, null, 'get_directories_to_ignore()', 'button-link' ); ?><br>
						• WordPress coding standards from rulesets<br>
						• File extensions (.php, .inc, .module)<br>
						• JSON ignored paths via wp_plugin_check_ignore_directories filter<br>
						<strong>🟢 SOLUTION IMPLEMENTED:</strong><br>
						• Early termination now occurs at check execution level<br>
						• Stops processing additional checks when limit reached<br>
						• Provides performance benefits for large codebases
					</div>
				</div>
				
				<div class="wpv-arch-step">
					<strong>4. HASH GENERATION</strong><br>
					→ <?php echo wpv_get_vscode_button( 'includes/Verification/Hash_Generator.php', 0, 0, null, 'Hash_Generator::generate_file_hash()', 'button-link' ); ?><br>
					→ <?php echo wpv_get_vscode_button( 'includes/Verification/JSON_Storage.php', 0, 0, null, 'JSON_Storage::initialize_verification_file()', 'button-link' ); ?><br>
					→ Store in verification tracking
				</div>
				
				<div class="wpv-arch-step">
					<strong>5. RESULTS PROCESSING</strong><br>
					→ <?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 200, 0, null, 'Verification_AJAX_Handler::run_checks()', 'button-link' ); ?><br>
					→ <?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 1598, 0, null, 'apply_ignored_paths_filter()', 'button-link' ); ?><br>
					→ Calculate readiness score<br>
					→ Save to <code>.wpv-results.json</code><br>
					<div class="wpv-arch-substep">
						<strong>🟢 UPDATED: Issue Limiting Implementation:</strong><br>
						• Early termination now occurs in <?php echo wpv_get_vscode_button( 'includes/Checker/Checks.php', 30, 0, null, 'Checks::run_checks()', 'button-link' ); ?><br>
						• <?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 180, 0, null, 'limit_issues_to_count()', 'button-link' ); ?> - Still available for display limiting<br>
						• Processing stops when 20 issues found (not just display)<br>
						• Prioritizes errors over warnings during execution<br>
						• Maintains file/line/column structure<br>
						<strong>🟢 PERFORMANCE BENEFITS:</strong><br>
						• Stops PHPCS execution early when limit reached<br>
						• Reduces processing time for large codebases<br>
						• Saves system resources during testing<br>
						<strong>Debug Logging:</strong><br>
						• "Issue limiting enabled - will stop after 20 issues"<br>
						• "Check [ClassName] found X issues (total: Y)"<br>
						• "Issue limit reached (20/20) - stopping check execution"
					</div>
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
		<h3 class="wpv-arch-issue-header"><?php wpverifier_header( 'Issue Analysis: 20 Issue Limit - IMPLEMENTED ✅', 'TAB12-08A' ); ?></h3>
		
		<div class="wpv-arch-issue-analysis">
			<h4>✅ Solution Implemented (Option 3):</h4>
			<p><strong>What's happening now:</strong> The "Limit to 20 issues" checkbox now stops check execution early when 20 issues are found, providing real performance benefits.</p>
			
			<h4>Updated Process Flow:</h4>
			<ol>
				<li><strong>User clicks "Run Verification"</strong> with "Limit to 20 issues" checked</li>
				<li><strong>JavaScript sends</strong> <code>limit_results: true</code> to backend</li>
				<li><strong>Checks::run_checks() reads limit</strong> from check_options JSON</li>
				<li><strong>Checks run one by one</strong> with issue counting after each check</li>
				<li><strong>Execution stops</strong> when 20 issues are reached</li>
				<li><strong>User sees</strong> faster processing and only relevant issues</li>
			</ol>
			
			<h4>Implementation Details:</h4>
			<div class="wpv-arch-termination-points">
				<div class="wpv-arch-termination-option wpv-arch-termination-recommended">
					<strong>✅ Implemented: Check Execution Level</strong><br>
					<code>Checks::run_checks()</code><br>
					• Replaced array_walk() with foreach loop<br>
					• Counts issues after each check execution<br>
					• Breaks loop when limit reached<br>
					• Provides detailed debug logging
				</div>
				
				<div class="wpv-arch-termination-option">
					<strong>Key Methods Added:</strong><br>
					<code>get_issue_limit_from_request()</code><br>
					• Reads check_options from POST data<br>
					• Extracts limit_results boolean<br>
					• Returns true if limiting enabled
				</div>
				
				<div class="wpv-arch-termination-option">
					<strong>Issue Counting:</strong><br>
					<code>count_issues_in_result()</code><br>
					• Counts errors and warnings in Check_Result<br>
					• Handles nested array structure<br>
					• Tracks cumulative issue count
				</div>
			</div>
			
			<h4>Expected Debug Output:</h4>
			<div class="wpv-arch-debug-output">
				<code>WPV Debug: Issue limiting enabled - will stop after 20 issues</code><br>
				<code>WPV Debug: Check "WordPress\Plugin_Check\Checker\Checks\Plugin_Header_Check" found 3 issues (total: 3)</code><br>
				<code>WPV Debug: Check "WordPress\Plugin_Check\Checker\Checks\PHP_CodeSniffer_Check" found 17 issues (total: 20)</code><br>
				<code>WPV Debug: Issue limit reached (20/20) - stopping check execution</code><br>
				<code>WPV Debug: Check execution completed with 20 total issues (limit was 20)</code>
			</div>
		</div>
	</div>

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
			$plugin_path = Path_Builder::get_plugin_directory_path( $current_plugin['folder'] );
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
		<h3><?php wpverifier_header( 'Testing Workflow & Tab Operations', 'TAB12-10' ); ?></h3>
		
		<div class="wpv-arch-testing-workflow">
			<div class="wpv-arch-workflow-step">
				<h5>Step 1: TAB01 - Plugin Selection & Initial Scan</h5>
				<ul>
					<li><strong>Function:</strong> Initiates verification process for selected plugin</li>
					<li><strong>Key Files:</strong> 
						<ul>
							<li><?php echo wpv_get_vscode_button( 'templates/admin-page-verification.php', 0, 0, null, 'admin-page-verification.php', 'button-link' ); ?> - Main verification interface</li>
							<li><?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 0, 0, null, 'Verification_AJAX_Handler.php', 'button-link' ); ?> - Handles scan requests</li>
							<li><?php echo wpv_get_vscode_button( 'includes/Checker/Checks.php', 0, 0, null, 'Checks.php', 'button-link' ); ?> - Orchestrates all verification checks</li>
						</ul>
					</li>
					<li><strong>Output:</strong> Creates 3 JSON files:
						<ul>
							<li><code>.wpv-config.json</code> - Scan configuration and ignored paths</li>
							<li><code>.wpv-results.json</code> - All coding standards issues found</li>
							<li><code>.wpv-verification.json</code> - File hashes for change detection</li>
						</ul>
					</li>
					<li><strong>Test Checkpoints:</strong> Verify all 3 JSON files created with correct structure</li>
				</ul>
			</div>
			
			<div class="wpv-arch-workflow-step">
				<h5>Step 2: TAB02 - Configure Ignored Paths</h5>
				<ul>
					<li><strong>Function:</strong> Manages vendor folders and ignored paths via drag-and-drop interface</li>
					<li><strong>Key Files:</strong>
						<ul>
							<li><?php echo wpv_get_vscode_button( 'templates/admin-page-configuration.php', 0, 0, null, 'admin-page-configuration.php', 'button-link' ); ?> - Configuration interface</li>
							<li><?php echo wpv_get_vscode_button( 'assets/js/admin-configuration.js', 0, 0, null, 'admin-configuration.js', 'button-link' ); ?> - Drag-and-drop functionality</li>
							<li><?php echo wpv_get_vscode_button( 'includes/Admin/Configuration_AJAX_Handler.php', 0, 0, null, 'Configuration_AJAX_Handler.php', 'button-link' ); ?> - Saves configuration</li>
						</ul>
					</li>
					<li><strong>Updates:</strong> Modifies <code>.wpv-config.json</code> with new ignored paths</li>
					<li><strong>Test Checkpoints:</strong> 
						<ul>
							<li>✅ Vendor libraries detected automatically</li>
							<li>✅ Drag-and-drop interface functional</li>
							<li>✅ Save button updates .wpv-config.json correctly</li>
							<li>Verify ignored paths are respected in subsequent scans</li>
						</ul>
					</li>
				</ul>
			</div>
			
			<div class="wpv-arch-workflow-step">
				<h5>Step 2b: TAB02b - Hash Generation (Future Enhancement)</h5>
				<ul>
					<li><strong>Current Status:</strong> Hash Generation panel currently located on TAB02 Configure</li>
					<li><strong>Planned Enhancement:</strong> Move to dedicated TAB02b between Configure and Verification</li>
					<li><strong>Function:</strong> Generates file hashes for incremental scanning and change detection</li>
					<li><strong>Key Files:</strong>
						<ul>
							<li><?php echo wpv_get_vscode_button( 'includes/Verification/Hash_Generator.php', 0, 0, null, 'Hash_Generator.php', 'button-link' ); ?> - Hash generation logic</li>
							<li><?php echo wpv_get_vscode_button( 'includes/Verification/JSON_Storage.php', 0, 0, null, 'JSON_Storage.php', 'button-link' ); ?> - Stores hashes in verification file</li>
							<li><strong>Future:</strong> <code>templates/admin-page-hash-generation.php</code> - Dedicated template</li>
						</ul>
					</li>
					<li><strong>Updates:</strong> Creates/updates <code>.wpv-verification.json</code> with file hashes</li>
					<li><strong>Test Checkpoints:</strong> 
						<ul>
							<li>✅ Hash generation completes successfully</li>
							<li>✅ Verification JSON file created/updated</li>
							<li>✅ File count matches expected plugin files</li>
							<li>✅ Ignored paths excluded from hash generation</li>
						</ul>
					</li>
					<li><strong>Benefits of Separate Tab:</strong>
						<ul>
							<li>Clearer workflow separation between configuration and file tracking</li>
							<li>Better user experience with focused functionality per tab</li>
							<li>Easier to find and manage hash generation independently</li>
							<li>Preparation for advanced hash management features</li>
						</ul>
					</li>
				</ul>
			</div>
			
			<div class="wpv-arch-workflow-step">
				<h5>Step 3: TAB03 - Verification Execution</h5>
				<ul>
					<li><strong>Function:</strong> Runs PHPCS verification and generates results</li>
					<li><strong>Key Files:</strong>
						<ul>
							<li><?php echo wpv_get_vscode_button( 'templates/admin-page-verification.php', 0, 0, null, 'admin-page-verification.php', 'button-link' ); ?> - Verification interface</li>
							<li><?php echo wpv_get_vscode_button( 'assets/js/admin-verification.js', 0, 0, null, 'admin-verification.js', 'button-link' ); ?> - Frontend verification logic</li>
							<li><?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 200, 0, null, 'run_checks()', 'button-link' ); ?> - Backend verification handler</li>
						</ul>
					</li>
					<li><strong>Updates:</strong> Populates <code>.wpv-results.json</code> with scan results and readiness scores</li>
					<li><strong>Test Checkpoints:</strong> 
						<ul>
							<li>✅ Verification process completes successfully</li>
							<li>✅ Issues detected and displayed in results</li>
							<li>✅ Ignored paths respected (vendor folders excluded)</li>
							<li>✅ Results JSON populated with issues and readiness scores</li>
							<li>✅ Console output cleaned up for production</li>
						</ul>
					</li>
					<li><strong>Known Issues Fixed:</strong>
						<ul>
							<li>✅ Removed "ignored_paths" duplication from results JSON (now only in config JSON)</li>
							<li>✅ Cleaned up excessive console logging from verification scripts</li>
						</ul>
					</li>
				</ul>
			</div>
			<div class="wpv-arch-workflow-step">
				<h5>Step 4: TAB04 (Files) - FILE-GROUPED VIEW WITH SIDEBAR ❌ CURRENT FOCUS</h5>
				<ul>
					<li><strong>Function:</strong> Shows files with accordion expansion and sidebar panels</li>
					<li><strong>Key Files:</strong>
						<ul>
							<li><?php echo wpv_get_vscode_button( 'templates/admin-page-saved.php', 0, 0, null, 'admin-page-saved.php', 'button-link' ); ?> - File accordion template</li>
							<li><?php echo wpv_get_vscode_button( 'assets/js/wp-verifier-ast.js', 0, 0, null, 'wp-verifier-ast.js', 'button-link' ); ?> - AST JavaScript for sidebar</li>
							<li><?php echo wpv_get_vscode_button( 'templates/results-ast.php', 0, 0, null, 'results-ast.php', 'button-link' ); ?> - AST template for PAN01</li>
						</ul>
					</li>
					<li><strong>UI Flow:</strong> Click file → sidebar shows file details → click issue → PAN01 shows issue details with Fixed button</li>
					<li><strong>❌ CURRENT ISSUE:</strong> User reports Fixed button not working - no changes visible after clicking</li>
					<li><strong>Investigation Needed:</strong>
						<ul>
							<li>Check if AST JavaScript correctly handles issue IDs in TAB04 context</li>
							<li>Verify AJAX calls from TAB04 sidebar use correct endpoints</li>
							<li>Confirm Fixed button in PAN01 panel works properly</li>
							<li>Check for browser/WordPress caching preventing updates</li>
						</ul>
					</li>
				</ul>
			</div>
			
			<div class="wpv-arch-workflow-step">
				<h5>Step 5: TAB05 (Issues) - FLAT LIST VIEW ✅ WORKING</h5>
				<ul>
					<li><strong>Function:</strong> Displays all issues in flat table without grouping</li>
					<li><strong>Key Files:</strong>
						<ul>
							<li><?php echo wpv_get_vscode_button( 'templates/admin-page-issues.php', 0, 0, null, 'admin-page-issues.php', 'button-link' ); ?> - Issues table template</li>
							<li><?php echo wpv_get_vscode_button( 'assets/js/issues-tab.js', 0, 0, null, 'issues-tab.js', 'button-link' ); ?> - Table interaction JavaScript</li>
							<li><?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 2100, 0, null, 'mark_issue_as_fixed()', 'button-link' ); ?> - Fixed button handler</li>
						</ul>
					</li>
					<li><strong>UI Flow:</strong> Click row → expand details → show Fixed button in expanded row</li>
					<li><strong>✅ STATUS:</strong> Working correctly - Fixed button removes issues properly</li>
				</ul>
			</div>
			
			<div class="wpv-arch-workflow-step">
				<h5>Step 6: Re-scan Testing</h5>
				<ul>
					<li><strong>Function:</strong> Verify that fixed issues don't reappear and ignored paths work</li>
					<li><strong>Test Process:</strong>
						<ol>
							<li>Mark several issues as "Fixed"</li>
							<li>Run new verification scan</li>
							<li>Confirm fixed issues don't reappear</li>
							<li>Verify ignored paths are respected</li>
						</ol>
					</li>
				</ul>
			</div>
			
			<h4>Key Functions & Their Roles:</h4>
			<ul>
				<li><strong>mark_issue_as_fixed():</strong> Removes entire issue from .wpv-results.json</li>
				<li><strong>process_files():</strong> File-by-file processing with early termination</li>
				<li><strong>should_ignore_path():</strong> Path filtering logic for ignored directories</li>
				<li><strong>generate_issue_id():</strong> Creates unique identifiers for each issue</li>
			</ul>
		</div>
		
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

	<!-- JSON Files & Button Behavior Diagram -->
	<div class="wpv-arch-json-panel">
		<h3><?php wpverifier_header( 'JSON Files & Button Behavior', 'TAB12-11' ); ?></h3>
		
		<div class="wpv-arch-json-flow">
			<h4>📁 JSON File Structure & Usage</h4>
			
			<div class="wpv-arch-json-files">
				<div class="wpv-arch-json-file">
					<h5><code>.wpv-results.json</code> - Main Results Storage</h5>
					<div class="wpv-arch-json-content">
						<strong>Purpose:</strong> Stores all scan results, readiness scores, and issue data<br>
						<strong>Created by:</strong> <?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 1400, 0, null, 'save_results_to_json()', 'button-link' ); ?><br>
						<strong>Read by:</strong> <?php echo wpv_get_vscode_button( 'templates/admin-page-issues.php', 25, 0, null, 'TAB05 Issues Display', 'button-link' ); ?><br>
						<strong>Structure:</strong>
						<pre>{
  "generated_at": "2024-01-01 12:00:00",
  "plugin": "plugin-name/plugin-name.php",
  "readiness": {
    "overall": 85,
    "errors": 2,
    "warnings": 5
  },
  "results": {
    "file.php": [
      {
        "issue_id": "W-b59cea4b",
        "message": "Global variable issue",
        "code": "WordPress.NamingConventions.PrefixAllGlobals",
        "type": "WARNING",
        "line": 327,
        "column": 5,
        "ignored": false,
        "resolved": false
      }
    ]
  }
}</pre>
					</div>
				</div>
				
				<div class="wpv-arch-json-file">
					<h5><code>.wpv-config.json</code> - Configuration Storage</h5>
					<div class="wpv-arch-json-content">
						<strong>Purpose:</strong> Stores ignored paths and plugin configuration<br>
						<strong>Created by:</strong> TAB02 Configuration interface<br>
						<strong>Read by:</strong> <?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 1598, 0, null, 'apply_ignored_paths_filter()', 'button-link' ); ?><br>
						<strong>Structure:</strong>
						<pre>{
  "ignored_paths": [
    {
      "path": "vendor/action-scheduler",
      "reason": "Third-party library"
    }
  ],
  "configuration": {
    "wporg_preparation": true,
    "skipped_rules": []
  }
}</pre>
					</div>
				</div>
				
				<div class="wpv-arch-json-file">
					<h5><code>.wpv-verification.json</code> - Tracking Storage</h5>
					<div class="wpv-arch-json-content">
						<strong>Purpose:</strong> Tracks file hashes and verification status<br>
						<strong>Created by:</strong> Hash generation process<br>
						<strong>Used for:</strong> Incremental scanning and change detection
					</div>
				</div>
			</div>
			
			<h4>🔘 Button Behavior & Data Flow</h4>
			
			<div class="wpv-arch-button-flow">
				<div class="wpv-arch-button-section">
					<h5>TAB04 (Verification) - AST Interface</h5>
					
					<div class="wpv-arch-button-item wpv-arch-button-fixed">
						<div class="wpv-arch-button-header">
							<span class="wpv-arch-button-icon">✅</span>
							<strong>"Fixed" Button</strong>
							<span class="wpv-arch-button-tooltip">Permanently removes issue from results</span>
						</div>
						<div class="wpv-arch-button-details">
							<strong>Function:</strong> <?php echo wpv_get_vscode_button( 'assets/js/wp-verifier-ast.js', 400, 0, null, 'markComplete()', 'button-link' ); ?><br>
							<strong>AJAX Action:</strong> <code>plugin_check_mark_complete</code><br>
							<strong>Data Modified:</strong> Removes entire issue from <code>.wpv-results.json</code><br>
							<strong>Use Case:</strong> When you've actually fixed the code issue<br>
							<strong>Result:</strong> Issue disappears from all displays, readiness score recalculated
						</div>
					</div>
					
					<div class="wpv-arch-button-item wpv-arch-button-ignore">
						<div class="wpv-arch-button-header">
							<span class="wpv-arch-button-icon">👁️</span>
							<strong>"Ignore" Button</strong>
							<span class="wpv-arch-button-tooltip">Marks as ignored but keeps in results</span>
						</div>
						<div class="wpv-arch-button-details">
							<strong>Function:</strong> URL redirect to ignore action<br>
							<strong>AJAX Action:</strong> <code>ignore_code</code> (URL parameter)<br>
							<strong>Data Modified:</strong> Sets <code>"ignored": true</code> in <code>.wpv-results.json</code><br>
							<strong>Use Case:</strong> For false positives or acceptable violations<br>
							<strong>Result:</strong> Issue remains but marked as ignored, excluded from readiness score
						</div>
					</div>
				</div>
				
				<div class="wpv-arch-button-section">
					<h5>TAB05 (Issues) - Table Interface</h5>
					
					<div class="wpv-arch-button-item wpv-arch-button-fixed">
						<div class="wpv-arch-button-header">
							<span class="wpv-arch-button-icon">✅</span>
							<strong>"Fixed" Button</strong>
							<span class="wpv-arch-button-tooltip">Permanently removes issue from results</span>
						</div>
						<div class="wpv-arch-button-details">
							<strong>Function:</strong> <?php echo wpv_get_vscode_button( 'assets/js/issues-tab.js', 0, 0, null, 'wpv-fixed-link handler', 'button-link' ); ?><br>
							<strong>AJAX Action:</strong> <code>wpv_mark_resolved</code><br>
							<strong>Handler:</strong> <?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 2100, 0, null, 'mark_issue_as_fixed()', 'button-link' ); ?><br>
							<strong>Data Modified:</strong> Removes entire issue from <code>.wpv-results.json</code><br>
							<strong>Use Case:</strong> When you've actually fixed the code issue<br>
							<strong>Result:</strong> Issue disappears from all displays, readiness score recalculated
						</div>
					</div>
				</div>
			</div>
			
			<h4>🔄 Data Flow Diagram</h4>
			
			<div class="wpv-arch-data-flow">
				<div class="wpv-arch-flow-step">
					<div class="wpv-arch-flow-number">1</div>
					<div class="wpv-arch-flow-content">
						<strong>Scan Execution</strong><br>
						PHPCS finds issues → Results saved to <code>.wpv-results.json</code>
					</div>
				</div>
				
				<div class="wpv-arch-flow-arrow">↓</div>
				
				<div class="wpv-arch-flow-step">
					<div class="wpv-arch-flow-number">2</div>
					<div class="wpv-arch-flow-content">
						<strong>Display Loading</strong><br>
						TAB04 & TAB05 read from <code>.wpv-results.json</code> → Show issues in UI
					</div>
				</div>
				
				<div class="wpv-arch-flow-arrow">↓</div>
				
				<div class="wpv-arch-flow-step wpv-arch-flow-decision">
					<div class="wpv-arch-flow-number">3</div>
					<div class="wpv-arch-flow-content">
						<strong>User Action</strong><br>
						User clicks "Fixed" or "Ignore" button
					</div>
				</div>
				
				<div class="wpv-arch-flow-split">
					<div class="wpv-arch-flow-branch wpv-arch-flow-fixed">
						<div class="wpv-arch-flow-arrow">↙</div>
						<div class="wpv-arch-flow-step">
							<div class="wpv-arch-flow-number">4a</div>
							<div class="wpv-arch-flow-content">
								<strong>"Fixed" Button</strong><br>
								AJAX call → <code>mark_issue_as_fixed()</code><br>
								→ Issue <strong>REMOVED</strong> from JSON<br>
								→ Readiness score recalculated
							</div>
						</div>
					</div>
					
					<div class="wpv-arch-flow-branch wpv-arch-flow-ignore">
						<div class="wpv-arch-flow-arrow">↘</div>
						<div class="wpv-arch-flow-step">
							<div class="wpv-arch-flow-number">4b</div>
							<div class="wpv-arch-flow-content">
								<strong>"Ignore" Button</strong><br>
								URL redirect → ignore handler<br>
								→ Issue <strong>FLAGGED</strong> as ignored<br>
								→ Remains in JSON with <code>"ignored": true</code>
							</div>
						</div>
					</div>
				</div>
				
				<div class="wpv-arch-flow-arrow">↓</div>
				
				<div class="wpv-arch-flow-step">
					<div class="wpv-arch-flow-number">5</div>
					<div class="wpv-arch-flow-content">
						<strong>Next Page Load</strong><br>
						TAB04 & TAB05 reload → Read updated <code>.wpv-results.json</code><br>
						→ Fixed issues: <strong>Gone completely</strong><br>
						→ Ignored issues: <strong>Shown but marked</strong>
					</div>
				</div>
			</div>
			
			<h4>⚠️ Current Issue Investigation</h4>
			
			<div class="wpv-arch-issue-investigation">
				<div class="wpv-arch-issue-problem">
					<strong>Problem:</strong> Issue <code>W-b59cea4b</code> exists in JSON but not displayed in TAB05
				</div>
				
				<div class="wpv-arch-issue-suspects">
					<strong>Possible Causes:</strong>
					<ul>
						<li><strong>Template filtering:</strong> <?php echo wpv_get_vscode_button( 'templates/admin-page-issues.php', 35, 0, null, 'Issue merging logic', 'button-link' ); ?> may be filtering out issues</li>
						<li><strong>ID mismatch:</strong> Generated <code>issue_id</code> in template doesn't match stored <code>issue_id</code> in JSON</li>
						<li><strong>JSON structure:</strong> Issue may be in wrong file path key or malformed</li>
						<li><strong>Caching:</strong> Browser or server-side caching of JSON file</li>
						<li><strong>Path normalization:</strong> File path format differences (Windows vs Unix)</li>
					</ul>
				</div>
				
				<div class="wpv-arch-issue-debug">
					<strong>Debug Steps:</strong>
					<ol>
						<li>Check <code>$plugin_data['results']</code> array in <?php echo wpv_get_vscode_button( 'templates/admin-page-issues.php', 25, 0, null, 'admin-page-issues.php', 'button-link' ); ?></li>
						<li>Verify <code>$merged_issues</code> array contains the missing issue</li>
						<li>Compare generated <code>issue_id</code> with stored <code>issue_id</code></li>
						<li>Check file path key matching between JSON and template</li>
						<li>Add debug output to trace data flow</li>
					</ol>
				</div>
			</div>
		</div>
	</div>
</div>