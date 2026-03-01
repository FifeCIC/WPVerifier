<?php
/**
 * Testing Tab - Load and merge wpseed JSON with AI guidance
 *
 * @package wp-verifier
 */

// Load wpseed JSON from verifier-results
$wpseed_file = WP_CONTENT_DIR . '/verifier-results/wpseed/results.json';
$wpseed_data = array();
if ( file_exists( $wpseed_file ) ) {
	$wpseed_json = file_get_contents( $wpseed_file );
	$wpseed_data = json_decode( $wpseed_json, true );
}

// Load AI guidance
if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\AI_Guidance' ) ) {
	require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/AI_Guidance.php';
}
$ai_guidance = \WordPress\Plugin_Check\Utilities\AI_Guidance::get_all_guidance();

// Merge AI guidance into wpseed issues
$merged_issues = array();
if ( ! empty( $wpseed_data['results'] ) ) {
	foreach ( $wpseed_data['results'] as $file => $issues ) {
		foreach ( $issues as $issue ) {
			$code = $issue['code'] ?? '';
			if ( $code && isset( $ai_guidance[ $code ] ) ) {
				$issue['ai_guidance'] = $ai_guidance[ $code ]['ai_guidance'] ?? '';
			}
			$issue['file'] = $file;
			$merged_issues[] = $issue;
		}
	}
}

// Sort by severity (ERROR first, then WARNING)
usort( $merged_issues, function( $a, $b ) {
	$type_a = $a['type'] ?? 'ERROR';
	$type_b = $b['type'] ?? 'ERROR';
	if ( $type_a === $type_b ) {
		return 0;
	}
	return ( $type_a === 'ERROR' ) ? -1 : 1;
} );
?>

<div class="wpv-testing-tab">
	<div class="wpv-test-section">
		<?php if ( ! empty( $merged_issues ) ) : ?>
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
						$has_guidance = ! empty( $issue['ai_guidance'] );
						$file_path = $issue['file'] ?? '';
						$code = $issue['code'] ?? '';
						$message = $issue['message'] ?? '';
						$line = $issue['line'] ?? '';
						$type = $issue['type'] ?? 'ERROR';
						$ai_guidance = $issue['ai_guidance'] ?? '';
						
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
							<td><code><?php echo esc_html( basename( $file_path ) ); ?></code></td>
							<td><?php echo esc_html( $line ); ?></td>
							<td><code><?php echo esc_html( $code ); ?></code></td>
							<td><?php echo esc_html( $message ); ?></td>
						</tr>
						<tr class="wpv-issue-details" id="wpv-details-<?php echo esc_attr( $index ); ?>" style="display: none;">
							<td colspan="6" style="background: #f9f9f9; padding: 20px;">
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
									<a href="vscode://file/<?php echo esc_attr( $file_path ); ?>:<?php echo esc_attr( $line ); ?>" class="button">
										<span class="dashicons dashicons-editor-code"></span> <?php esc_html_e( 'VSCode', 'wp-verifier' ); ?>
									</a>
									<a href="#" class="button button-primary wpv-fixed-link" data-file="<?php echo esc_attr( $file_path ); ?>" data-code="<?php echo esc_attr( $code ); ?>">
										<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Mark As Fixed', 'wp-verifier' ); ?>
									</a>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No issues found in wpseed.json', 'wp-verifier' ); ?></p>
		<?php endif; ?>
	</div>
</div>
