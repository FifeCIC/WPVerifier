<?php
/**
 * Template for the Admin page.
 *
 * @package plugin-check
 */

// Check which plugins have saved results
$results_dir = WP_CONTENT_DIR . '/verifier-results';
$plugins_with_results = array();
if (is_dir($results_dir)) {
	$plugin_dirs = glob($results_dir . '/*', GLOB_ONLYDIR);
	foreach ($plugin_dirs as $plugin_dir) {
		if (file_exists($plugin_dir . '/results.json')) {
			$plugins_with_results[] = basename($plugin_dir);
		}
	}
}

?>

<div class="plugin-check-content" style="display: flex; gap: 20px;">

	<div style="flex: 0 0 auto; display: flex; gap: 20px;">
		<div style="flex: 1;">
			<?php if ( ! empty( $available_plugins ) ) { ?>
				<form>
					<h2><?php esc_html_e( 'Rulesets', 'wp-verifier' ); ?></h2>
					<div class="plugin-check__options">
						<div>
							<h4><?php esc_attr_e( 'Categories', 'wp-verifier' ); ?></h4>
							<?php if ( ! empty( $categories ) ) : ?>
								<table id="plugin-check__categories">
									<?php foreach ( $categories as $category => $label ) : ?>
										<tr>
											<td>
												<fieldset>
													<legend class="screen-reader-text"><?php echo esc_html( $category ); ?></legend>
													<label for="<?php echo esc_attr( $category ); ?>">
														<input type="checkbox" id="<?php echo esc_attr( $category ); ?>" name="categories" value="<?php echo esc_attr( $category ); ?>" <?php checked( in_array( $category, $user_enabled_categories, true ) ); ?> />
														<?php echo esc_html( $label ); ?>
													</label>
												</fieldset>
											</td>
										</tr>
									<?php endforeach; ?>
								</table>
							<?php endif; ?>
						</div>
						<div id="plugin-check__types-container">
							<h4><?php esc_attr_e( 'Types', 'wp-verifier' ); ?></h4>
							<?php if ( ! empty( $types ) ) : ?>
								<table id="plugin-check__types">
									<?php foreach ( $types as $type => $label ) : ?>
										<tr>
											<td>
												<fieldset>
													<legend class="screen-reader-text"><?php echo esc_html( $type ); ?></legend>
													<label for="<?php echo esc_attr( $type ); ?>">
														<input type="checkbox" id="<?php echo esc_attr( $type ); ?>" name="types" value="<?php echo esc_attr( $type ); ?>" checked="checked" />
														<?php echo esc_html( $label ); ?>
													</label>
												</fieldset>
											</td>
										</tr>
									<?php endforeach; ?>
								</table>
							<?php endif; ?>
						</div>
					</div>
					<?php if ( $has_experimental_checks ) { ?>
						<h4><?php esc_attr_e( 'Other Options', 'wp-verifier' ); ?></h4>
						<p>
							<label><input type="checkbox" value="include-experimental" id="plugin-check__include-experimental" /> <?php esc_html_e( 'Include Experimental Checks', 'wp-verifier' ); ?></label>
						</p>
					<?php } else { ?>
						<h4><?php esc_attr_e( 'Other Options', 'wp-verifier' ); ?></h4>
					<?php } ?>
					<p>
						<label><input type="checkbox" value="wporg-prep" id="plugin-check__wporg-prep" checked /> <?php esc_html_e( 'WordPress.org Preparation', 'wp-verifier' ); ?></label>
					</p>
					<p>
						<label><input type="checkbox" value="limit-results" id="plugin-check__limit-results" /> <?php esc_html_e( 'Limit to 10 issues (testing)', 'wp-verifier' ); ?></label>
					</p>
				</form>
			<?php } else { ?>
				<h2><?php esc_html_e( 'No plugins available.', 'wp-verifier' ); ?></h2>
			<?php } ?>
		</div>

		<div style="flex: 0 0 300px;">
			<?php if ( ! empty( $available_plugins ) ) { ?>
				<h2>
					<label class="title" for="plugin-check__plugins-dropdown">
						<?php esc_html_e( 'Select Plugin', 'wp-verifier' ); ?>
					</label>
				</h2>
				<p id="plugin-check__description">
					<?php esc_html_e( 'Select a plugin to check it for best practices in several categories and security issues.', 'wp-verifier' ); ?>
				</p>
				<select id="plugin-check__plugins-dropdown" name="plugin_check_plugins" aria-describedby="plugin-check__description">
					<?php if ( 1 !== count( $available_plugins ) ) { ?>
						<option value=""><?php esc_html_e( 'Select Plugin', 'wp-verifier' ); ?></option>
					<?php } ?>
					<?php foreach ( $available_plugins as $plugin_basename => $available_plugin ) {
						$plugin_folder = strpos($plugin_basename, '/') !== false ? dirname($plugin_basename) : $plugin_basename;
						$has_report = in_array($plugin_folder, $plugins_with_results);
					?>
						<option value="<?php echo esc_attr( $plugin_basename ); ?>"<?php selected( $selected_plugin_basename, $plugin_basename ); ?>>
							<?php echo esc_html( $available_plugin['Name'] ); ?><?php echo $has_report ? ' ✓' : ''; ?>
						</option>
					<?php } ?>
				</select>
				<p>
					<input type="submit" value="<?php esc_attr_e( 'Check it!', 'wp-verifier' ); ?>" id="plugin-check__submit" class="button button-primary" />
					<span id="plugin-check__spinner" class="spinner" style="float: none;"></span>
				</p>
			<?php } ?>
		</div>
	</div>
</div>

<div id="plugin-check__export-controls" class="plugin-check__export-controls"></div>
<div id="plugin-check__results"></div>
