<?php
/**
 * Class WordPress\Plugin_Check\Admin\Ignore_Rules_Page
 *
 * @package WPVerifier
 */

namespace WordPress\Plugin_Check\Admin;

use WordPress\Plugin_Check\Utilities\Ignore_Rules;

/**
 * Ignore Rules management page.
 */
class Ignore_Rules_Page {

	/**
	 * Initialize the page.
	 */
	public function init() {
		add_action( 'admin_post_wpv_add_ignore_rule', array( $this, 'add_rule' ) );
		add_action( 'admin_post_wpv_remove_ignore_rule', array( $this, 'remove_rule' ) );
		add_action( 'admin_post_wpv_export_ignore_rules', array( $this, 'export_rules' ) );
		add_action( 'admin_post_wpv_import_ignore_rules', array( $this, 'import_rules' ) );
		add_action( 'admin_post_wpv_detect_vendors', array( $this, 'detect_vendors' ) );
	}

	/**
	 * Render the page.
	 */
	public function render_page() {
		$rules = Ignore_Rules::get_rules();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ignore Rules', 'wp-verifier' ); ?></h1>
			<p><?php esc_html_e( 'Manage rules to filter out third-party code and false positives from verification results.', 'wp-verifier' ); ?></p>

			<div style="display: flex; gap: 20px; margin: 20px 0;">
				<button type="button" class="button" onclick="document.getElementById('add-rule-form').style.display='block'"><?php esc_html_e( 'Add Rule', 'wp-verifier' ); ?></button>
				<button type="button" class="button" onclick="document.getElementById('detect-form').style.display='block'"><?php esc_html_e( 'Detect Vendors', 'wp-verifier' ); ?></button>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wpv_export_ignore_rules' ), 'wpv_export_rules' ) ); ?>" class="button"><?php esc_html_e( 'Export Rules', 'wp-verifier' ); ?></a>
				<button type="button" class="button" onclick="document.getElementById('import-form').style.display='block'"><?php esc_html_e( 'Import Rules', 'wp-verifier' ); ?></button>
			</div>

			<div id="add-rule-form" style="display:none; background: #fff; padding: 20px; border: 1px solid #ccc; margin: 20px 0;">
				<h2><?php esc_html_e( 'Add Ignore Rule', 'wp-verifier' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'wpv_add_ignore_rule', 'wpv_nonce' ); ?>
					<input type="hidden" name="action" value="wpv_add_ignore_rule" />
					<table class="form-table">
						<tr>
							<th><label for="scope"><?php esc_html_e( 'Scope', 'wp-verifier' ); ?></label></th>
							<td>
								<select name="scope" id="scope" required>
									<option value="directory"><?php esc_html_e( 'Directory', 'wp-verifier' ); ?></option>
									<option value="file"><?php esc_html_e( 'File', 'wp-verifier' ); ?></option>
									<option value="code"><?php esc_html_e( 'Error Code', 'wp-verifier' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="path"><?php esc_html_e( 'Path', 'wp-verifier' ); ?></label></th>
							<td><input type="text" name="path" id="path" class="regular-text" required placeholder="vendor/" /></td>
						</tr>
						<tr>
							<th><label for="code"><?php esc_html_e( 'Error Code', 'wp-verifier' ); ?></label></th>
							<td><input type="text" name="code" id="code" class="regular-text" placeholder="WordPress.Security.EscapeOutput" /></td>
						</tr>
						<tr>
							<th><label for="reason"><?php esc_html_e( 'Reason', 'wp-verifier' ); ?></label></th>
							<td>
								<select name="reason" id="reason">
									<option value="vendor"><?php esc_html_e( 'Vendor/Library', 'wp-verifier' ); ?></option>
									<option value="other"><?php esc_html_e( 'Other', 'wp-verifier' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="note"><?php esc_html_e( 'Note', 'wp-verifier' ); ?></label></th>
							<td><input type="text" name="note" id="note" class="regular-text" /></td>
						</tr>
					</table>
					<p>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Rule', 'wp-verifier' ); ?></button>
						<button type="button" class="button" onclick="document.getElementById('add-rule-form').style.display='none'"><?php esc_html_e( 'Cancel', 'wp-verifier' ); ?></button>
					</p>
				</form>
			</div>

			<div id="detect-form" style="display:none; background: #fff; padding: 20px; border: 1px solid #ccc; margin: 20px 0;">
				<h2><?php esc_html_e( 'Detect Vendor Directories', 'wp-verifier' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'wpv_detect_vendors', 'wpv_nonce' ); ?>
					<input type="hidden" name="action" value="wpv_detect_vendors" />
					<p>
						<label for="plugin_dir"><?php esc_html_e( 'Plugin Directory', 'wp-verifier' ); ?></label><br>
						<input type="text" name="plugin_dir" id="plugin_dir" class="large-text" value="<?php echo esc_attr( WP_PLUGIN_DIR ); ?>" required />
					</p>
					<p>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Detect & Add', 'wp-verifier' ); ?></button>
						<button type="button" class="button" onclick="document.getElementById('detect-form').style.display='none'"><?php esc_html_e( 'Cancel', 'wp-verifier' ); ?></button>
					</p>
				</form>
			</div>

			<div id="import-form" style="display:none; background: #fff; padding: 20px; border: 1px solid #ccc; margin: 20px 0;">
				<h2><?php esc_html_e( 'Import Rules', 'wp-verifier' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<?php wp_nonce_field( 'wpv_import_rules', 'wpv_nonce' ); ?>
					<input type="hidden" name="action" value="wpv_import_ignore_rules" />
					<p>
						<input type="file" name="rules_file" accept=".json" required />
					</p>
					<p>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'wp-verifier' ); ?></button>
						<button type="button" class="button" onclick="document.getElementById('import-form').style.display='none'"><?php esc_html_e( 'Cancel', 'wp-verifier' ); ?></button>
					</p>
				</form>
			</div>

			<h2><?php esc_html_e( 'Active Rules', 'wp-verifier' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Scope', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Path', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Code', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Reason', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Note', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'wp-verifier' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rules ) ) : ?>
						<tr>
							<td colspan="6"><?php esc_html_e( 'No ignore rules defined.', 'wp-verifier' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $rules as $id => $rule ) : ?>
							<tr>
								<td><?php echo esc_html( ucfirst( $rule['scope'] ) ); ?></td>
								<td><code><?php echo esc_html( $rule['path'] ); ?></code></td>
								<td><?php echo esc_html( $rule['code'] ); ?></td>
								<td><?php echo esc_html( ucfirst( $rule['reason'] ) ); ?></td>
								<td><?php echo esc_html( $rule['note'] ); ?></td>
								<td>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wpv_remove_ignore_rule&rule_id=' . $id ), 'wpv_remove_rule_' . $id ) ); ?>" 
									   onclick="return confirm('<?php esc_attr_e( 'Remove this rule?', 'wp-verifier' ); ?>');">
										<?php esc_html_e( 'Remove', 'wp-verifier' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Add a rule.
	 */
	public function add_rule() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-verifier' ) );
		}

		check_admin_referer( 'wpv_add_ignore_rule', 'wpv_nonce' );

		$scope = isset( $_POST['scope'] ) ? sanitize_text_field( wp_unslash( $_POST['scope'] ) ) : '';
		$path = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';
		$code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
		$reason = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : 'other';
		$note = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '';

		Ignore_Rules::add_rule( $scope, $path, $reason, $code, $note );

		wp_safe_redirect( admin_url( 'tools.php?page=wp-verifier-ignore-rules&added=1' ) );
		exit;
	}

	/**
	 * Remove a rule.
	 */
	public function remove_rule() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-verifier' ) );
		}

		$rule_id = isset( $_GET['rule_id'] ) ? sanitize_text_field( wp_unslash( $_GET['rule_id'] ) ) : '';
		check_admin_referer( 'wpv_remove_rule_' . $rule_id );

		Ignore_Rules::remove_rule( $rule_id );

		wp_safe_redirect( admin_url( 'tools.php?page=wp-verifier-ignore-rules&removed=1' ) );
		exit;
	}

	/**
	 * Export rules.
	 */
	public function export_rules() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-verifier' ) );
		}

		check_admin_referer( 'wpv_export_rules' );

		$json = Ignore_Rules::export_rules();

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="wpv-ignore-rules.json"' );
		echo $json;
		exit;
	}

	/**
	 * Import rules.
	 */
	public function import_rules() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-verifier' ) );
		}

		check_admin_referer( 'wpv_import_rules', 'wpv_nonce' );

		if ( ! isset( $_FILES['rules_file'] ) || $_FILES['rules_file']['error'] !== UPLOAD_ERR_OK ) {
			wp_die( esc_html__( 'File upload failed.', 'wp-verifier' ) );
		}

		$json = file_get_contents( $_FILES['rules_file']['tmp_name'] );
		$success = Ignore_Rules::import_rules( $json );

		if ( ! $success ) {
			wp_die( esc_html__( 'Invalid rules file.', 'wp-verifier' ) );
		}

		wp_safe_redirect( admin_url( 'tools.php?page=wp-verifier-ignore-rules&imported=1' ) );
		exit;
	}

	/**
	 * Detect vendor directories.
	 */
	public function detect_vendors() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-verifier' ) );
		}

		check_admin_referer( 'wpv_detect_vendors', 'wpv_nonce' );

		$plugin_dir = isset( $_POST['plugin_dir'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_dir'] ) ) : '';
		$vendors = Ignore_Rules::detect_vendor_dirs( $plugin_dir );

		foreach ( $vendors as $vendor ) {
			Ignore_Rules::add_rule( 'directory', $vendor, 'vendor', '', 'Auto-detected' );
		}

		wp_safe_redirect( admin_url( 'tools.php?page=wp-verifier-ignore-rules&detected=' . count( $vendors ) ) );
		exit;
	}
}
