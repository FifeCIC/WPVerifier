<?php
/**
 * Issues Tab - Load and merge wpverifier JSON with AI guidance
 *
 * @package wp-verifier
 */

// Load Path_Builder for new path building approach
if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Path_Builder' ) ) {
	require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Path_Builder.php';
}

use WordPress\Plugin_Check\Utilities\Path_Builder;

// Get current plugin slug
$current_plugin_slug = Path_Builder::get_current_plugin_slug();

// If no plugin selected, show message
if ( ! $current_plugin_slug ) {
	echo '<div class="notice notice-warning"><p>' . esc_html__( 'No plugin selected. Please go to TAB01 to select a plugin first.', 'wp-verifier' ) . '</p></div>';
	return;
}

// Load plugin JSON from verifier-results using Path_Builder
$plugin_file = Path_Builder::get_results_file_path( $current_plugin_slug );
$plugin_data = array();
if ( file_exists( $plugin_file ) ) {
	$plugin_json = file_get_contents( $plugin_file );
	$plugin_data = json_decode( $plugin_json, true );
	
	// DEBUG: Log what we loaded from JSON
	error_log( 'WPV TAB05 DEBUG: JSON file loaded: ' . $plugin_file );
	error_log( 'WPV TAB05 DEBUG: JSON decode success: ' . ( $plugin_data ? 'YES' : 'NO' ) );
	if ( $plugin_data && isset( $plugin_data['results'] ) ) {
		$total_issues_in_json = 0;
		foreach ( $plugin_data['results'] as $file => $issues ) {
			$total_issues_in_json += count( $issues );
		}
		error_log( 'WPV TAB05 DEBUG: Total issues in JSON: ' . $total_issues_in_json );
		error_log( 'WPV TAB05 DEBUG: Files in JSON: ' . implode( ', ', array_keys( $plugin_data['results'] ) ) );
	}
}

// Load AI guidance
if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\AI_Guidance' ) ) {
	require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/AI_Guidance.php';
}
$ai_guidance = \WordPress\Plugin_Check\Utilities\AI_Guidance::get_all_guidance();

// Merge AI guidance into plugin issues
$merged_issues = array();
if ( ! empty( $plugin_data['results'] ) ) {
	foreach ( $plugin_data['results'] as $file => $issues ) {
		error_log( 'WPV TAB05 DEBUG: Processing file: ' . $file . ' with ' . count( $issues ) . ' issues' );
		foreach ( $issues as $issue ) {
			$code = $issue['code'] ?? '';
			$issue_id = $issue['issue_id'] ?? 'NO_ID';
			error_log( 'WPV TAB05 DEBUG: Processing issue: ' . $issue_id . ' (' . $code . ')' );
			
			if ( $code && isset( $ai_guidance[ $code ] ) ) {
				$issue['ai_guidance'] = $ai_guidance[ $code ]['ai_guidance'] ?? '';
			}
			$issue['file'] = $file;
			$merged_issues[] = $issue;
		}
	}
}

error_log( 'WPV TAB05 DEBUG: Final merged_issues count: ' . count( $merged_issues ) );

// Sort by severity (ERROR first, then WARNING)
usort( $merged_issues, function( $a, $b ) {
	$type_a = $a['type'] ?? 'ERROR';
	$type_b = $b['type'] ?? 'ERROR';
	if ( $type_a === $type_b ) {
		return 0;
	}
	return ( $type_a === 'ERROR' ) ? -1 : 1;
} );

// DEBUG: Check for duplicate issue_ids
$issue_ids = array();
$duplicates = array();
foreach ( $merged_issues as $issue ) {
	$issue_id = $issue['issue_id'] ?? 'NO_ID';
	if ( isset( $issue_ids[ $issue_id ] ) ) {
		$duplicates[] = $issue_id;
	}
	$issue_ids[ $issue_id ] = true;
}
if ( ! empty( $duplicates ) ) {
	error_log( 'WPV TAB05 DEBUG: DUPLICATE ISSUE IDs FOUND: ' . implode( ', ', array_unique( $duplicates ) ) );
	error_log( 'WPV TAB05 DEBUG: Total unique issue_ids: ' . count( $issue_ids ) );
}
?>

<div class="wpv-testing-tab">
	<div class="wpv-test-section">
		<?php if ( ! empty( $merged_issues ) ) : ?>
			<?php error_log( 'WPV TAB05 DEBUG: About to display ' . count( $merged_issues ) . ' issues in table' ); ?>
			<table class="wp-list-table widefat fixed striped wpv-issues-table">
				<thead>
					<tr>
						<th class="wpv-sortable" data-sort="severity" style="cursor: pointer;">
							<?php esc_html_e( 'Severity', 'wp-verifier' ); ?> <span class="dashicons dashicons-arrow-down" style="font-size: 14px; vertical-align: middle;"></span>
						</th>
						<th><?php esc_html_e( 'File', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Line', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Code', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Message', 'wp-verifier' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $merged_issues as $index => $issue ) : 
						// Debug: Log each issue being displayed
						if ( $index < 5 ) { // Only log first 5 to avoid spam
							error_log( 'WPV TAB05 DEBUG: Displaying issue #' . $index . ': ' . ($issue['issue_id'] ?? 'NO_ID') . ' (' . ($issue['code'] ?? 'NO_CODE') . ')' );
						}
						
						$has_guidance = ! empty( $issue['ai_guidance'] );
						$file_path = $issue['file'] ?? '';
						$code = $issue['code'] ?? '';
						$message = $issue['message'] ?? '';
						$line = $issue['line'] ?? '';
						$type = $issue['type'] ?? 'ERROR';
						$ai_guidance = $issue['ai_guidance'] ?? '';
						
						// Use stored issue_id if available, otherwise generate one
						$issue_id = $issue['issue_id'] ?? null;
						if ( ! $issue_id ) {
							// Fallback: Generate issue_id using Path_Builder approach
							$plugin_base_path = Path_Builder::get_plugin_file_path( $current_plugin_slug );
							$relative_file = str_replace( $plugin_base_path . '/', '', $file_path );
							$issue_id = ( $type === 'ERROR' ? 'E-' : 'W-' ) . substr( md5( $relative_file . $line . $code ), 0, 8 );
						}
						
						// Build AI prompt
						$ai_prompt = "File: " . basename( $file_path ) . "\n";
						$ai_prompt .= "Line: " . $line . "\n";
						$ai_prompt .= "Code: " . $code . "\n";
						$ai_prompt .= "Message: " . $message . "\n\n";
						$ai_prompt .= "Instructions for AI:\n";
						$ai_prompt .= "Please review this WordPress coding standards issue and provide a fix.\n";
						if ( $has_guidance ) {
							$ai_prompt .= "\nAI Guidance:\n" . $ai_guidance;
						}
					?>
						<tr class="wpv-issue-row" data-index="<?php echo esc_attr( $index ); ?>" data-severity="<?php echo esc_attr( $type ); ?>" style="cursor: pointer;">
							<td><span class="wpv-ast-badge <?php echo esc_attr( strtolower( $type ) ); ?>"><?php echo esc_html( $type ); ?></span></td>
							<td><code><?php echo esc_html( $file_path ); ?></code></td>
							<td><?php echo esc_html( $line ); ?></td>
							<td><code><?php echo esc_html( $code ); ?></code></td>
							<td><?php echo esc_html( $message ); ?></td>
						</tr>
						<tr class="wpv-issue-details" id="wpv-details-<?php echo esc_attr( $index ); ?>" style="display: none;">
							<td colspan="5" style="background: #f9f9f9; padding: 20px;">
								<div style="margin-bottom: 15px;">
									<strong><?php esc_html_e( 'Full Path:', 'wp-verifier' ); ?></strong><br>
									<code style="font-size: 11px; word-break: break-all;"><?php echo esc_html( $file_path ); ?></code>
								</div>
								
								<div style="margin-bottom: 15px;">
									<strong><?php esc_html_e( 'AI Prompt:', 'wp-verifier' ); ?></strong><br>
									<textarea readonly style="width: 100%; height: 150px; font-family: monospace; font-size: 12px; padding: 10px; border: 1px solid #ddd; background: #fff;"><?php echo esc_textarea( $ai_prompt ); ?></textarea>
								</div>
								
								<div style="display: flex; gap: 8px; flex-wrap: wrap;">
									<button type="button" class="button wpv-copy-prompt" data-prompt="<?php echo esc_attr( $ai_prompt ); ?>">
										<span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy AI Prompt', 'wp-verifier' ); ?>
									</button>
									<?php 
									// Generate VSCode button using Path_Builder
									$vscode_url = Path_Builder::get_vscode_url( $current_plugin_slug, $file_path, $line );
									if ( $vscode_url ) :
									?>
										<a href="<?php echo esc_attr( $vscode_url ); ?>" class="button">
											<span class="dashicons dashicons-editor-code"></span> <?php esc_html_e( 'VSCode', 'wp-verifier' ); ?>
										</a>
									<?php else : ?>
										<span class="button button-disabled">
											<span class="dashicons dashicons-editor-code"></span> <?php esc_html_e( 'VSCode (Error)', 'wp-verifier' ); ?>
										</span>
									<?php endif; ?>
									<a href="#" class="button button-primary wpv-fixed-link" data-issue-id="<?php echo esc_attr( $issue_id ); ?>" data-file="<?php echo esc_attr( $file_path ); ?>" data-code="<?php echo esc_attr( $code ); ?>" title="<?php esc_attr_e( 'Permanently removes this issue from results (use when code is actually fixed)', 'wp-verifier' ); ?>">
										<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Fixed', 'wp-verifier' ); ?>
									</a>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No issues found in plugin results', 'wp-verifier' ); ?></p>
		<?php endif; ?>
	</div>
</div>
