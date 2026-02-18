<?php
/**
 * Class WordPress\Plugin_Check\Utilities\Report_Generator
 *
 * @package WPVerifier
 */

namespace WordPress\Plugin_Check\Utilities;

/**
 * Generates detailed verification reports.
 */
class Report_Generator {

	/**
	 * Generate a detailed HTML report.
	 *
	 * @param array  $errors      Errors array.
	 * @param array  $warnings    Warnings array.
	 * @param array  $metadata    Report metadata.
	 * @param array  $comparison  Optional comparison data.
	 * @return string HTML report.
	 */
	public static function generate_html_report( $errors, $warnings, $metadata, $comparison = null ) {
		$plugin_name = isset( $metadata['plugin'] ) ? $metadata['plugin'] : 'Unknown Plugin';
		$timestamp = isset( $metadata['timestamp_human'] ) ? $metadata['timestamp_human'] : current_time( 'mysql' );
		
		$total_errors = count( $errors );
		$total_warnings = count( $warnings );
		$total_issues = $total_errors + $total_warnings;

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<title>WP Verifier Report - <?php echo esc_html( $plugin_name ); ?></title>
			<style>
				body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 40px; background: #f5f5f5; }
				.container { max-width: 1200px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
				h1 { color: #23282d; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
				.summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
				.summary-card { background: #f8f9fa; padding: 20px; border-radius: 4px; border-left: 4px solid #0073aa; }
				.summary-card.errors { border-left-color: #dc3232; }
				.summary-card.warnings { border-left-color: #ffb900; }
				.summary-card h3 { margin: 0 0 10px 0; font-size: 14px; color: #666; text-transform: uppercase; }
				.summary-card .count { font-size: 36px; font-weight: bold; color: #23282d; }
				.issue-section { margin: 30px 0; }
				.issue-section h2 { color: #23282d; font-size: 20px; margin-bottom: 20px; }
				.issue { background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin-bottom: 15px; }
				.issue.error { border-left: 4px solid #dc3232; }
				.issue.warning { border-left: 4px solid #ffb900; }
				.issue-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px; }
				.issue-title { font-weight: 600; color: #23282d; }
				.issue-severity { padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: 600; }
				.issue-severity.error { background: #dc3232; color: white; }
				.issue-severity.warning { background: #ffb900; color: white; }
				.issue-meta { color: #666; font-size: 14px; margin: 5px 0; }
				.issue-message { color: #555; line-height: 1.6; margin-top: 10px; }
				.code-ref { background: #f1f1f1; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-size: 13px; }
				.footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px; }
				.comparison { background: #e7f5fe; border: 1px solid #0073aa; border-radius: 4px; padding: 15px; margin: 20px 0; }
				.comparison h3 { margin: 0 0 10px 0; color: #0073aa; }
				.trend { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: 600; margin-left: 10px; }
				.trend.improving { background: #46b450; color: white; }
				.trend.declining { background: #dc3232; color: white; }
			</style>
		</head>
		<body>
			<div class="container">
				<h1>WP Verifier Report</h1>
				
				<div style="margin: 20px 0;">
					<strong>Plugin:</strong> <?php echo esc_html( $plugin_name ); ?><br>
					<strong>Scan Date:</strong> <?php echo esc_html( $timestamp ); ?>
				</div>

				<?php if ( $comparison && ! $comparison['is_first_scan'] ) : ?>
					<div class="comparison">
						<h3>📊 Comparison with Previous Scan</h3>
						<p>
							<strong>New Issues:</strong> 
							<?php echo count( $comparison['new_errors'] ); ?> errors, 
							<?php echo count( $comparison['new_warnings'] ); ?> warnings
							<?php if ( count( $comparison['fixed_errors'] ) + count( $comparison['fixed_warnings'] ) > 0 ) : ?>
								<span class="trend improving">
									✓ <?php echo count( $comparison['fixed_errors'] ) + count( $comparison['fixed_warnings'] ); ?> Fixed
								</span>
							<?php endif; ?>
						</p>
					</div>
				<?php endif; ?>

				<div class="summary">
					<div class="summary-card">
						<h3>Total Issues</h3>
						<div class="count"><?php echo $total_issues; ?></div>
					</div>
					<div class="summary-card errors">
						<h3>Errors</h3>
						<div class="count"><?php echo $total_errors; ?></div>
					</div>
					<div class="summary-card warnings">
						<h3>Warnings</h3>
						<div class="count"><?php echo $total_warnings; ?></div>
					</div>
				</div>

				<?php if ( ! empty( $errors ) ) : ?>
					<div class="issue-section">
						<h2>🔴 Errors (<?php echo count( $errors ); ?>)</h2>
						<?php foreach ( $errors as $error ) : ?>
							<div class="issue error">
								<div class="issue-header">
									<div class="issue-title"><?php echo esc_html( $error['message'] ?? 'Error' ); ?></div>
									<span class="issue-severity error">ERROR</span>
								</div>
								<div class="issue-meta">
									<span class="code-ref"><?php echo esc_html( $error['file'] ?? '' ); ?></span>
									<?php if ( isset( $error['line'] ) ) : ?>
										: Line <?php echo esc_html( $error['line'] ); ?>
									<?php endif; ?>
								</div>
								<?php if ( isset( $error['code'] ) ) : ?>
									<div class="issue-meta">Code: <code><?php echo esc_html( $error['code'] ); ?></code></div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $warnings ) ) : ?>
					<div class="issue-section">
						<h2>⚠️ Warnings (<?php echo count( $warnings ); ?>)</h2>
						<?php foreach ( $warnings as $warning ) : ?>
							<div class="issue warning">
								<div class="issue-header">
									<div class="issue-title"><?php echo esc_html( $warning['message'] ?? 'Warning' ); ?></div>
									<span class="issue-severity warning">WARNING</span>
								</div>
								<div class="issue-meta">
									<span class="code-ref"><?php echo esc_html( $warning['file'] ?? '' ); ?></span>
									<?php if ( isset( $warning['line'] ) ) : ?>
										: Line <?php echo esc_html( $warning['line'] ); ?>
									<?php endif; ?>
								</div>
								<?php if ( isset( $warning['code'] ) ) : ?>
									<div class="issue-meta">Code: <code><?php echo esc_html( $warning['code'] ); ?></code></div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( empty( $errors ) && empty( $warnings ) ) : ?>
					<div style="text-align: center; padding: 60px 20px; color: #46b450;">
						<h2 style="color: #46b450;">✓ No Issues Found</h2>
						<p>Your plugin passed all verification checks!</p>
					</div>
				<?php endif; ?>

				<div class="footer">
					<p>Generated by WP Verifier on <?php echo esc_html( current_time( 'F j, Y g:i a' ) ); ?></p>
					<p>This report is for informational purposes only. Manual review may still be required.</p>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generate a text summary report.
	 *
	 * @param array $errors   Errors array.
	 * @param array $warnings Warnings array.
	 * @param array $metadata Report metadata.
	 * @return string Text report.
	 */
	public static function generate_text_report( $errors, $warnings, $metadata ) {
		$plugin_name = isset( $metadata['plugin'] ) ? $metadata['plugin'] : 'Unknown Plugin';
		$timestamp = isset( $metadata['timestamp_human'] ) ? $metadata['timestamp_human'] : current_time( 'mysql' );
		
		$report = "WP VERIFIER REPORT\n";
		$report .= str_repeat( '=', 80 ) . "\n\n";
		$report .= "Plugin: {$plugin_name}\n";
		$report .= "Scan Date: {$timestamp}\n\n";
		$report .= str_repeat( '-', 80 ) . "\n\n";
		
		$report .= "SUMMARY\n";
		$report .= "Total Issues: " . ( count( $errors ) + count( $warnings ) ) . "\n";
		$report .= "Errors: " . count( $errors ) . "\n";
		$report .= "Warnings: " . count( $warnings ) . "\n\n";
		
		if ( ! empty( $errors ) ) {
			$report .= str_repeat( '-', 80 ) . "\n\n";
			$report .= "ERRORS (" . count( $errors ) . ")\n\n";
			foreach ( $errors as $i => $error ) {
				$report .= ( $i + 1 ) . ". " . ( $error['message'] ?? 'Error' ) . "\n";
				$report .= "   File: " . ( $error['file'] ?? 'Unknown' ) . "\n";
				if ( isset( $error['line'] ) ) {
					$report .= "   Line: " . $error['line'] . "\n";
				}
				if ( isset( $error['code'] ) ) {
					$report .= "   Code: " . $error['code'] . "\n";
				}
				$report .= "\n";
			}
		}
		
		if ( ! empty( $warnings ) ) {
			$report .= str_repeat( '-', 80 ) . "\n\n";
			$report .= "WARNINGS (" . count( $warnings ) . ")\n\n";
			foreach ( $warnings as $i => $warning ) {
				$report .= ( $i + 1 ) . ". " . ( $warning['message'] ?? 'Warning' ) . "\n";
				$report .= "   File: " . ( $warning['file'] ?? 'Unknown' ) . "\n";
				if ( isset( $warning['line'] ) ) {
					$report .= "   Line: " . $warning['line'] . "\n";
				}
				if ( isset( $warning['code'] ) ) {
					$report .= "   Code: " . $warning['code'] . "\n";
				}
				$report .= "\n";
			}
		}
		
		if ( empty( $errors ) && empty( $warnings ) ) {
			$report .= "✓ No issues found. Your plugin passed all verification checks!\n\n";
		}
		
		$report .= str_repeat( '=', 80 ) . "\n";
		$report .= "Generated by WP Verifier\n";
		
		return $report;
	}

	/**
	 * Generate a PDF report.
	 *
	 * @param array  $errors      Errors array.
	 * @param array  $warnings    Warnings array.
	 * @param array  $metadata    Report metadata.
	 * @param array  $comparison  Optional comparison data.
	 * @return string PDF content or error message.
	 */
	public static function generate_pdf_report( $errors, $warnings, $metadata, $comparison = null ) {
		$html = self::generate_html_report( $errors, $warnings, $metadata, $comparison );

		if ( class_exists( 'Mpdf\\Mpdf' ) ) {
			try {
				$mpdf = new \Mpdf\Mpdf( array(
					'mode' => 'utf-8',
					'format' => 'A4',
				) );
				$mpdf->WriteHTML( $html );
				return $mpdf->Output( '', 'S' );
			} catch ( \Exception $e ) {
				return 'PDF generation failed: ' . $e->getMessage();
			}
		}

		return "<!-- PDF generation requires mPDF library. Install via: composer require mpdf/mpdf -->\n" . $html;
	}
}
