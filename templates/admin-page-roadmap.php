<?php
/**
 * Template for Roadmap tab - Development Planning & Task Management
 *
 * @package wp-verifier
 */

use WordPress\Plugin_Check\Utilities\Path_Builder;

// Get current active plugin for context
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

?>

<div class="wrap">
	<h2><?php wpverifier_header( 'Development Roadmap & Task Planning', 'ROADMAP-01' ); ?></h2>
	
	<div class="wpv-roadmap-intro">
		<p><?php esc_html_e( 'This roadmap integrates development tasks with the existing plugin architecture, allowing for strategic planning and implementation tracking.', 'wp-verifier' ); ?></p>
		<?php if ( $current_plugin ) : ?>
			<p><strong><?php esc_html_e( 'Context Plugin:', 'wp-verifier' ); ?></strong> <?php echo esc_html( $current_plugin['name'] ); ?> (<?php echo esc_html( $current_plugin['folder'] ); ?>)</p>
		<?php endif; ?>
	</div>

	<!-- Phase Status Overview -->
	<div class="wpv-roadmap-status-grid">
		<div class="wpv-roadmap-status-card wpv-status-completed">
			<h3>🎉 PHASE 2</h3>
			<div class="wpv-status-title">Path Building Consolidation</div>
			<div class="wpv-status-badge">✅ COMPLETED</div>
			<div class="wpv-status-details">17/17 components updated, VSCode integration fixed</div>
		</div>
		
		<div class="wpv-roadmap-status-card wpv-status-active">
			<h3>🚀 PHASE 3</h3>
			<div class="wpv-status-title">Function-Based Issue Management</div>
			<div class="wpv-status-badge">🔄 IN PLANNING</div>
			<div class="wpv-status-details">Transform TAB05 to function-centric workflow</div>
		</div>
		
		<div class="wpv-roadmap-status-card wpv-status-pending">
			<h3>⏳ PHASE 4</h3>
			<div class="wpv-status-title">Real-Time Progress Tracking</div>
			<div class="wpv-status-badge">📋 PLANNED</div>
			<div class="wpv-status-details">Replace simulated progress with actual file tracking</div>
		</div>
	</div>

	<!-- Main Roadmap Content -->
	<div class="wpv-roadmap-main">
		
		<!-- PHASE 3: Function-Based Issue Management -->
		<div class="wpv-roadmap-phase wpv-roadmap-phase-active">
			<div class="wpv-roadmap-phase-header" data-phase="phase3">
				<h2>🚀 PHASE 3: Function-Based Issue Management</h2>
				<div class="wpv-roadmap-phase-toggle">▼</div>
			</div>
			
			<div class="wpv-roadmap-phase-content" id="phase3-content">
				<div class="wpv-roadmap-objective">
					<strong>Objective:</strong> Transform TAB05 from generic "Issues" to function-centric "Functions" tab with enhanced developer workflow.
				</div>
				
				<!-- Phase 3.1: JSON Schema Enhancement -->
				<div class="wpv-roadmap-section">
					<div class="wpv-roadmap-section-header">
						<h3>Phase 3.1: JSON Schema Enhancement</h3>
						<span class="wpv-priority-badge wpv-priority-high">HIGH PRIORITY</span>
					</div>
					
					<div class="wpv-roadmap-tasks-grid">
						<div class="wpv-roadmap-tasks-column">
							<h4>📋 Tasks</h4>
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-json-extend" class="wpv-task-checkbox">
								<label for="task-json-extend">Extend <code>.wpv-results.json</code> structure</label>
								<div class="wpv-task-details">
									Add function_name, function_hash, class_name, function_signature fields
								</div>
							</div>
							
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-function-detection" class="wpv-task-checkbox">
								<label for="task-function-detection">Function Detection System</label>
								<div class="wpv-task-details">
									Use token_get_all() to parse PHP files and map line numbers to functions
								</div>
							</div>
							
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-backward-compat" class="wpv-task-checkbox">
								<label for="task-backward-compat">Backward Compatibility</label>
								<div class="wpv-task-details">
									Handle existing results without function data, migration strategy
								</div>
							</div>
						</div>
						
						<div class="wpv-roadmap-architecture-column">
							<h4>🏗️ Architecture Integration</h4>
							<div class="wpv-roadmap-arch-item">
								<strong>Primary Files:</strong><br>
								<?php echo wpv_get_vscode_button( 'includes/Verification/JSON_Storage.php', 0, 0, null, 'JSON_Storage.php', 'button-link' ); ?><br>
								<?php echo wpv_get_vscode_button( 'includes/Verification/Hash_Generator.php', 0, 0, null, 'Hash_Generator.php', 'button-link' ); ?>
							</div>
							
							<div class="wpv-roadmap-arch-item">
								<strong>New Components:</strong><br>
								<code>Function_Parser.php</code> - PHP function detection<br>
								<code>Function_Hash_Generator.php</code> - Function-specific hashing
							</div>
							
							<div class="wpv-roadmap-arch-item">
								<strong>Integration Points:</strong><br>
								• Results processing pipeline<br>
								• Hash generation system<br>
								• JSON storage structure
							</div>
						</div>
					</div>
				</div>
				
				<!-- Phase 3.2: Function-Centric UI -->
				<div class="wpv-roadmap-section">
					<div class="wpv-roadmap-section-header">
						<h3>Phase 3.2: Function-Centric UI</h3>
						<span class="wpv-priority-badge wpv-priority-medium">MEDIUM PRIORITY</span>
					</div>
					
					<div class="wpv-roadmap-tasks-grid">
						<div class="wpv-roadmap-tasks-column">
							<h4>📋 Tasks</h4>
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-copy-tab04" class="wpv-task-checkbox">
								<label for="task-copy-tab04">Copy TAB04 Architecture</label>
								<div class="wpv-task-details">
									Duplicate accordion structure and styling for function-based grouping
								</div>
							</div>
							
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-accordion-headers" class="wpv-task-checkbox">
								<label for="task-accordion-headers">Accordion Headers</label>
								<div class="wpv-task-details">
									Function signature, file path, line range, issue count badges
								</div>
							</div>
							
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-function-actions" class="wpv-task-checkbox">
								<label for="task-function-actions">Function-Level Actions</label>
								<div class="wpv-task-details">
									Mark as Fixed, Ignore Function, Copy Function Prompt, VSCode integration
								</div>
							</div>
						</div>
						
						<div class="wpv-roadmap-architecture-column">
							<h4>🏗️ Architecture Integration</h4>
							<div class="wpv-roadmap-arch-item">
								<strong>Template Files:</strong><br>
								<?php echo wpv_get_vscode_button( 'templates/admin-page-issues.php', 0, 0, null, 'admin-page-issues.php', 'button-link' ); ?> → Rename to functions<br>
								<?php echo wpv_get_vscode_button( 'templates/admin-page-architecture.php', 0, 0, null, 'admin-page-architecture.php', 'button-link' ); ?> - Copy accordion pattern
							</div>
							
							<div class="wpv-roadmap-arch-item">
								<strong>JavaScript Files:</strong><br>
								<?php echo wpv_get_vscode_button( 'assets/js/admin-issues.js', 0, 0, null, 'admin-issues.js', 'button-link' ); ?> → Update for functions<br>
								<code>admin-functions.js</code> - New function-based logic
							</div>
							
							<div class="wpv-roadmap-arch-item">
								<strong>CSS Updates:</strong><br>
								<?php echo wpv_get_vscode_button( 'assets/css/wp-verifier-tabs.css', 0, 0, null, 'wp-verifier-tabs.css', 'button-link' ); ?><br>
								Function accordion styling, badges, action buttons
							</div>
						</div>
					</div>
				</div>
				
				<!-- Phase 3.3: Enhanced Function Management -->
				<div class="wpv-roadmap-section">
					<div class="wpv-roadmap-section-header">
						<h3>Phase 3.3: Enhanced Function Management</h3>
						<span class="wpv-priority-badge wpv-priority-medium">MEDIUM PRIORITY</span>
					</div>
					
					<div class="wpv-roadmap-tasks-grid">
						<div class="wpv-roadmap-tasks-column">
							<h4>📋 Tasks</h4>
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-function-status" class="wpv-task-checkbox">
								<label for="task-function-status">Function Status Tracking</label>
								<div class="wpv-task-details">
									Store function-level ignore/fixed status with hash validation
								</div>
							</div>
							
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-function-hierarchy" class="wpv-task-checkbox">
								<label for="task-function-hierarchy">Function Hierarchy Display</label>
								<div class="wpv-task-details">
									Show class context, group by class → method structure
								</div>
							</div>
							
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-function-stats" class="wpv-task-checkbox">
								<label for="task-function-stats">Function-Level Statistics</label>
								<div class="wpv-task-details">
									Issues per function, complexity indicators, problematic functions ranking
								</div>
							</div>
						</div>
						
						<div class="wpv-roadmap-architecture-column">
							<h4>🏗️ Architecture Integration</h4>
							<div class="wpv-roadmap-arch-item">
								<strong>Storage System:</strong><br>
								<?php echo wpv_get_vscode_button( 'includes/Verification/JSON_Storage.php', 0, 0, null, 'JSON_Storage.php', 'button-link' ); ?><br>
								<code>.wpv-verification.json</code> - Function status tracking
							</div>
							
							<div class="wpv-roadmap-arch-item">
								<strong>AJAX Handlers:</strong><br>
								<?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 0, 0, null, 'Verification_AJAX_Handler.php', 'button-link' ); ?><br>
								New function-level action endpoints
							</div>
							
							<div class="wpv-roadmap-arch-item">
								<strong>Hash System:</strong><br>
								<?php echo wpv_get_vscode_button( 'includes/Verification/Hash_Generator.php', 0, 0, null, 'Hash_Generator.php', 'button-link' ); ?><br>
								Function-specific hash generation and validation
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<!-- PHASE 4: Real-Time Progress Tracking -->
		<div class="wpv-roadmap-phase">
			<div class="wpv-roadmap-phase-header" data-phase="phase4">
				<h2>⏳ PHASE 4: Real-Time Progress Tracking</h2>
				<div class="wpv-roadmap-phase-toggle">▶</div>
			</div>
			
			<div class="wpv-roadmap-phase-content" id="phase4-content" style="display: none;">
				<div class="wpv-roadmap-objective">
					<strong>Objective:</strong> Replace simulated progress with actual file-based progress tracking for accurate verification progress indication.
				</div>
				
				<!-- Phase 4.1: Server-Side Progress Tracking -->
				<div class="wpv-roadmap-section">
					<div class="wpv-roadmap-section-header">
						<h3>Phase 4.1: Server-Side Progress Tracking</h3>
						<span class="wpv-priority-badge wpv-priority-high">HIGH PRIORITY</span>
					</div>
					
					<div class="wpv-roadmap-tasks-grid">
						<div class="wpv-roadmap-tasks-column">
							<h4>📋 Tasks</h4>
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-progress-storage" class="wpv-task-checkbox">
								<label for="task-progress-storage">Progress Storage System</label>
								<div class="wpv-task-details">
									Create <code>.wpv-progress.json</code> with phase, files processed, total files
								</div>
							</div>
							
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-file-counting" class="wpv-task-checkbox">
								<label for="task-file-counting">File Counting Integration</label>
								<div class="wpv-task-details">
									Count total files before scanning, update progress per file
								</div>
							</div>
							
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-progress-api" class="wpv-task-checkbox">
								<label for="task-progress-api">Progress API Endpoint</label>
								<div class="wpv-task-details">
									AJAX endpoint to read progress, return structured data with ETA
								</div>
							</div>
						</div>
						
						<div class="wpv-roadmap-architecture-column">
							<h4>🏗️ Architecture Integration</h4>
							<div class="wpv-roadmap-arch-item">
								<strong>Core Integration:</strong><br>
								<?php echo wpv_get_vscode_button( 'includes/Checker/Checks.php', 30, 0, null, 'Checks::run_checks()', 'button-link' ); ?><br>
								Progress updates during check execution
							</div>
							
							<div class="wpv-roadmap-arch-item">
								<strong>AJAX System:</strong><br>
								<?php echo wpv_get_vscode_button( 'includes/Admin/Verification_AJAX_Handler.php', 0, 0, null, 'Verification_AJAX_Handler.php', 'button-link' ); ?><br>
								New progress polling endpoint
							</div>
							
							<div class="wpv-roadmap-arch-item">
								<strong>File Processing:</strong><br>
								<?php echo wpv_get_vscode_button( 'includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php', 189, 0, null, 'get_files_to_scan()', 'button-link' ); ?><br>
								File counting and progress tracking
							</div>
						</div>
					</div>
				</div>
				
				<!-- Phase 4.2: Client-Side Real-Time Updates -->
				<div class="wpv-roadmap-section">
					<div class="wpv-roadmap-section-header">
						<h3>Phase 4.2: Client-Side Real-Time Updates</h3>
						<span class="wpv-priority-badge wpv-priority-high">HIGH PRIORITY</span>
					</div>
					
					<div class="wpv-roadmap-tasks-grid">
						<div class="wpv-roadmap-tasks-column">
							<h4>📋 Tasks</h4>
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-replace-simulation" class="wpv-task-checkbox">
								<label for="task-replace-simulation">Replace Simulation with Polling</label>
								<div class="wpv-task-details">
									Remove startProgressSimulation(), implement pollProgressStatus()
								</div>
							</div>
							
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-enhanced-display" class="wpv-task-checkbox">
								<label for="task-enhanced-display">Enhanced Progress Display</label>
								<div class="wpv-task-details">
									Show current file, phase, ETA, cancel option
								</div>
							</div>
							
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-error-handling" class="wpv-task-checkbox">
								<label for="task-error-handling">Error Handling & Fallback</label>
								<div class="wpv-task-details">
									Fallback to time-based estimation, handle timeouts gracefully
								</div>
							</div>
						</div>
						
						<div class="wpv-roadmap-architecture-column">
							<h4>🏗️ Architecture Integration</h4>
							<div class="wpv-roadmap-arch-item">
								<strong>JavaScript Files:</strong><br>
								<?php echo wpv_get_vscode_button( 'assets/js/plugin-check.js', 0, 0, null, 'plugin-check.js', 'button-link' ); ?><br>
								Progress simulation replacement
							</div>
							
							<div class="wpv-roadmap-arch-item">
								<strong>UI Templates:</strong><br>
								<?php echo wpv_get_vscode_button( 'templates/admin-page-verification.php', 0, 0, null, 'admin-page-verification.php', 'button-link' ); ?><br>
								Enhanced progress display elements
							</div>
							
							<div class="wpv-roadmap-arch-item">
								<strong>CSS Styling:</strong><br>
								<?php echo wpv_get_vscode_button( 'assets/css/wp-verifier-tabs.css', 0, 0, null, 'wp-verifier-tabs.css', 'button-link' ); ?><br>
								Progress bar enhancements, phase indicators
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Additional Features Section -->
		<div class="wpv-roadmap-phase">
			<div class="wpv-roadmap-phase-header" data-phase="additional">
				<h2>🔧 Additional Features & Enhancements</h2>
				<div class="wpv-roadmap-phase-toggle">▶</div>
			</div>
			
			<div class="wpv-roadmap-phase-content" id="additional-content" style="display: none;">
				
				<!-- Internationalization Tool -->
				<div class="wpv-roadmap-section">
					<div class="wpv-roadmap-section-header">
						<h3>🌍 Internationalization Tool (i18n)</h3>
						<span class="wpv-priority-badge wpv-priority-medium">MEDIUM PRIORITY</span>
					</div>
					
					<div class="wpv-roadmap-tasks-grid">
						<div class="wpv-roadmap-tasks-column">
							<h4>📋 Tasks</h4>
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-js-translation" class="wpv-task-checkbox">
								<label for="task-js-translation">JavaScript Translation</label>
								<div class="wpv-task-details">
									Create translation objects, update all JS files with localized strings
								</div>
							</div>
							
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-php-translation" class="wpv-task-checkbox">
								<label for="task-php-translation">PHP Translation</label>
								<div class="wpv-task-details">
									Ensure all strings use __(), _e(), add text domain 'wp-verifier'
								</div>
							</div>
						</div>
						
						<div class="wpv-roadmap-architecture-column">
							<h4>🏗️ Architecture Integration</h4>
							<div class="wpv-roadmap-arch-item">
								<strong>JavaScript Files:</strong><br>
								<?php echo wpv_get_vscode_button( 'assets/js/plugin-check-preparation.js', 0, 0, null, 'plugin-check-preparation.js', 'button-link' ); ?><br>
								<?php echo wpv_get_vscode_button( 'assets/js/plugin-check.js', 0, 0, null, 'plugin-check.js', 'button-link' ); ?>
							</div>
							
							<div class="wpv-roadmap-arch-item">
								<strong>Asset Management:</strong><br>
								<?php echo wpv_get_vscode_button( 'includes/Admin/Asset_Manager.php', 0, 0, null, 'Asset_Manager.php', 'button-link' ); ?><br>
								Localization object creation
							</div>
						</div>
					</div>
				</div>
				
				<!-- Developer Guidance Panel -->
				<div class="wpv-roadmap-section">
					<div class="wpv-roadmap-section-header">
						<h3>👨‍💻 Developer Guidance Panel</h3>
						<span class="wpv-priority-badge wpv-priority-high">HIGH PRIORITY</span>
					</div>
					
					<div class="wpv-roadmap-tasks-grid">
						<div class="wpv-roadmap-tasks-column">
							<h4>📋 Tasks</h4>
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-footer-panel" class="wpv-task-checkbox">
								<label for="task-footer-panel">Foundation Setup</label>
								<div class="wpv-task-details">
									Footer panel for WP_DEVELOPMENT_MODE, template tracking
								</div>
							</div>
							
							<div class="wpv-roadmap-task">
								<input type="checkbox" id="task-hash-workflow" class="wpv-task-checkbox">
								<label for="task-hash-workflow">Hash-Aware Workflow</label>
								<div class="wpv-task-details">
									Mark as Verified action, auto-expire stale ignores
								</div>
							</div>
						</div>
						
						<div class="wpv-roadmap-architecture-column">
							<h4>🏗️ Architecture Integration</h4>
							<div class="wpv-roadmap-arch-item">
								<strong>Template System:</strong><br>
								All template files - Footer panel integration<br>
								Template hierarchy tracking
							</div>
							
							<div class="wpv-roadmap-arch-item">
								<strong>Verification System:</strong><br>
								<?php echo wpv_get_vscode_button( 'includes/Verification/JSON_Storage.php', 0, 0, null, 'JSON_Storage.php', 'button-link' ); ?><br>
								Enhanced verification tracking
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Task Progress Summary -->
	<div class="wpv-roadmap-summary">
		<h3>📊 Development Progress Summary</h3>
		<div class="wpv-roadmap-progress-bars">
			<div class="wpv-progress-item">
				<label>Phase 3.1 - JSON Schema Enhancement</label>
				<div class="wpv-progress-bar">
					<div class="wpv-progress-fill" style="width: 0%"></div>
				</div>
				<span class="wpv-progress-text">0/3 tasks completed</span>
			</div>
			
			<div class="wpv-progress-item">
				<label>Phase 3.2 - Function-Centric UI</label>
				<div class="wpv-progress-bar">
					<div class="wpv-progress-fill" style="width: 0%"></div>
				</div>
				<span class="wpv-progress-text">0/3 tasks completed</span>
			</div>
			
			<div class="wpv-progress-item">
				<label>Phase 4.1 - Server-Side Progress</label>
				<div class="wpv-progress-bar">
					<div class="wpv-progress-fill" style="width: 0%"></div>
				</div>
				<span class="wpv-progress-text">0/3 tasks completed</span>
			</div>
		</div>
	</div>
</div>