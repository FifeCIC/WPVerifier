<?php
/**
 * Class WordPress\Plugin_Check\Admin\Plugin_Namer_Tab
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

/**
 * Plugin Namer tab for the admin interface.
 *
 * @since 1.9.0
 */
class Plugin_Namer_Tab {

	/**
	 * Renders the Plugin Namer tab content.
	 *
	 * @since 1.9.0
	 */
	public static function render() {
		?>
		<div class="wpv-plugin-namer-container">
			<div class="wpv-namer-header">
				<h2><?php esc_html_e( 'Plugin Name Analyzer', 'wp-verifier' ); ?></h2>
				<p><?php esc_html_e( 'Analyze plugin names for availability, conflicts, SEO, and trademark issues.', 'wp-verifier' ); ?></p>
			</div>

			<div class="wpv-namer-input-section">
				<label for="wpv-plugin-name-input">
					<?php esc_html_e( 'Plugin Name:', 'wp-verifier' ); ?>
				</label>
				<div class="wpv-input-group">
					<input 
						type="text" 
						id="wpv-plugin-name-input" 
						class="wpv-name-input" 
						placeholder="<?php esc_attr_e( 'Enter plugin name...', 'wp-verifier' ); ?>"
					/>
					<button type="button" id="wpv-analyze-name-btn" class="button button-primary">
						<?php esc_html_e( 'Analyze Name', 'wp-verifier' ); ?>
					</button>
				</div>
				<div id="wpv-analysis-loading" class="wpv-loading" style="display:none;">
					<span class="spinner is-active"></span>
					<span><?php esc_html_e( 'Analyzing...', 'wp-verifier' ); ?></span>
				</div>
			</div>

			<div id="wpv-analysis-results" style="display:none;">
				
				<!-- Domain Availability Section -->
				<div class="wpv-result-section wpv-domains-section">
					<h3><?php esc_html_e( 'Domain Availability', 'wp-verifier' ); ?></h3>
					<div id="wpv-domains-grid" class="wpv-domains-grid">
						<!-- Populated via JavaScript -->
					</div>
					<div id="wpv-domains-cached" class="wpv-cached-notice" style="display:none;">
						<span class="dashicons dashicons-clock"></span>
						<?php esc_html_e( 'Cached results', 'wp-verifier' ); ?>
					</div>
				</div>

				<!-- Conflict Detection Section -->
				<div class="wpv-result-section wpv-conflicts-section">
					<h3><?php esc_html_e( 'WordPress.org Conflicts', 'wp-verifier' ); ?></h3>
					<div id="wpv-conflicts-content">
						<!-- Populated via JavaScript -->
					</div>
				</div>

				<!-- SEO Analysis Section -->
				<div class="wpv-result-section wpv-seo-section">
					<h3><?php esc_html_e( 'SEO Analysis', 'wp-verifier' ); ?></h3>
					<div id="wpv-seo-content">
						<!-- Populated via JavaScript -->
					</div>
				</div>

				<!-- Trademark Checker Section -->
				<div class="wpv-result-section wpv-trademark-section">
					<h3><?php esc_html_e( 'Trademark Check', 'wp-verifier' ); ?></h3>
					<div id="wpv-trademark-content">
						<!-- Populated via JavaScript -->
					</div>
				</div>

				<!-- Save Section -->
				<div class="wpv-result-section wpv-save-section">
					<h3><?php esc_html_e( 'Save Evaluation', 'wp-verifier' ); ?></h3>
					<div class="wpv-save-form">
						<label for="wpv-save-note"><?php esc_html_e( 'Notes (optional):', 'wp-verifier' ); ?></label>
						<textarea id="wpv-save-note" rows="3" placeholder="<?php esc_attr_e( 'Add notes about this name...', 'wp-verifier' ); ?>"></textarea>
						<button type="button" id="wpv-save-evaluation-btn" class="button button-secondary">
							<?php esc_html_e( 'Save Evaluation', 'wp-verifier' ); ?>
						</button>
					</div>
				</div>

			</div>

			<!-- Saved Names Section -->
			<div class="wpv-saved-names-section">
				<h3><?php esc_html_e( 'Saved Names', 'wp-verifier' ); ?></h3>
				<div id="wpv-saved-names-list">
					<!-- Populated via JavaScript -->
				</div>
			</div>

		</div>
		<?php
	}
}