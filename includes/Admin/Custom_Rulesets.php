<?php
/**
 * Class WordPress\Plugin_Check\Admin\Custom_Rulesets
 *
 * @package WPVerifier
 */

namespace WordPress\Plugin_Check\Admin;

use WordPress\Plugin_Check\Checker\Check_Categories;

/**
 * Custom Rulesets management class.
 */
class Custom_Rulesets {

	/**
	 * Option name for storing custom rulesets.
	 */
	const OPTION_NAME = 'wp_verifier_custom_rulesets';

	/**
	 * Initializes the custom rulesets functionality.
	 */
	public function init() {
		add_action( 'admin_post_save_custom_ruleset', array( $this, 'save_ruleset' ) );
		add_action( 'admin_post_delete_custom_ruleset', array( $this, 'delete_ruleset' ) );
		add_action( 'admin_post_export_custom_ruleset', array( $this, 'export_ruleset' ) );
		add_action( 'admin_post_import_custom_ruleset', array( $this, 'import_ruleset' ) );
	}

	/**
	 * Renders the custom rulesets page.
	 */
	public function render_page() {
		$rulesets = $this->get_rulesets();
		$action   = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		$ruleset_id = isset( $_GET['ruleset'] ) ? sanitize_text_field( wp_unslash( $_GET['ruleset'] ) ) : '';

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Custom Rulesets', 'wp-verifier' ); ?></h1>
			<?php
			if ( 'edit' === $action || 'new' === $action ) {
				$this->render_edit_form( $ruleset_id, $rulesets );
			} else {
				$this->render_list( $rulesets );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Renders the rulesets list.
	 *
	 * @param array $rulesets Array of custom rulesets.
	 */
	private function render_list( $rulesets ) {
		?>
		<p><?php esc_html_e( 'Create custom rulesets to enforce specific coding standards in your ecosystem.', 'wp-verifier' ); ?></p>
		<p>
			<a href="<?php echo esc_url( admin_url( 'tools.php?page=wp-verifier-rulesets&action=new' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Add New Ruleset', 'wp-verifier' ); ?>
			</a>
			<a href="#" class="button" id="import-ruleset-btn"><?php esc_html_e( 'Import Ruleset', 'wp-verifier' ); ?></a>
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" id="import-form" style="display:none;">
			<?php wp_nonce_field( 'import_custom_ruleset', 'import_nonce' ); ?>
			<input type="hidden" name="action" value="import_custom_ruleset" />
			<input type="file" name="ruleset_file" accept=".json" required />
			<button type="submit" class="button"><?php esc_html_e( 'Upload', 'wp-verifier' ); ?></button>
		</form>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'wp-verifier' ); ?></th>
					<th><?php esc_html_e( 'Description', 'wp-verifier' ); ?></th>
					<th><?php esc_html_e( 'Categories', 'wp-verifier' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'wp-verifier' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rulesets ) ) : ?>
					<tr>
						<td colspan="4"><?php esc_html_e( 'No custom rulesets found. Create your first ruleset to get started.', 'wp-verifier' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $rulesets as $id => $ruleset ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $ruleset['name'] ); ?></strong></td>
							<td><?php echo esc_html( $ruleset['description'] ); ?></td>
							<td><?php echo esc_html( implode( ', ', $ruleset['categories'] ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'tools.php?page=wp-verifier-rulesets&action=edit&ruleset=' . $id ) ); ?>">
									<?php esc_html_e( 'Edit', 'wp-verifier' ); ?>
								</a> |
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=export_custom_ruleset&ruleset=' . $id ), 'export_ruleset_' . $id ) ); ?>">
									<?php esc_html_e( 'Export', 'wp-verifier' ); ?>
								</a> |
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=delete_custom_ruleset&ruleset=' . $id ), 'delete_ruleset_' . $id ) ); ?>" 
								   onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this ruleset?', 'wp-verifier' ); ?>');">
									<?php esc_html_e( 'Delete', 'wp-verifier' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<script>
		document.getElementById('import-ruleset-btn').addEventListener('click', function(e) {
			e.preventDefault();
			document.getElementById('import-form').style.display = 'block';
		});
		</script>
		<?php
	}

	/**
	 * Renders the edit/new ruleset form.
	 *
	 * @param string $ruleset_id Ruleset ID.
	 * @param array  $rulesets   Array of all rulesets.
	 */
	private function render_edit_form( $ruleset_id, $rulesets ) {
		$ruleset = isset( $rulesets[ $ruleset_id ] ) ? $rulesets[ $ruleset_id ] : array(
			'name'        => '',
			'description' => '',
			'categories'  => array(),
			'rules'       => array(),
		);

		$is_new = empty( $ruleset_id );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'save_custom_ruleset', 'ruleset_nonce' ); ?>
			<input type="hidden" name="action" value="save_custom_ruleset" />
			<input type="hidden" name="ruleset_id" value="<?php echo esc_attr( $ruleset_id ); ?>" />

			<table class="form-table">
				<tr>
					<th><label for="ruleset_name"><?php esc_html_e( 'Ruleset Name', 'wp-verifier' ); ?></label></th>
					<td>
						<input type="text" id="ruleset_name" name="ruleset_name" value="<?php echo esc_attr( $ruleset['name'] ); ?>" class="regular-text" required />
					</td>
				</tr>
				<tr>
					<th><label for="ruleset_description"><?php esc_html_e( 'Description', 'wp-verifier' ); ?></label></th>
					<td>
						<textarea id="ruleset_description" name="ruleset_description" rows="3" class="large-text"><?php echo esc_textarea( $ruleset['description'] ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Check Categories', 'wp-verifier' ); ?></th>
					<td>
						<?php
						$available_categories = Check_Categories::get_categories();
						foreach ( $available_categories as $slug => $label ) :
							$checked = in_array( $slug, $ruleset['categories'], true );
							?>
							<label>
								<input type="checkbox" name="categories[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $checked ); ?> />
								<?php echo esc_html( $label ); ?>
							</label><br />
						<?php endforeach; ?>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary"><?php echo $is_new ? esc_html__( 'Create Ruleset', 'wp-verifier' ) : esc_html__( 'Update Ruleset', 'wp-verifier' ); ?></button>
				<a href="<?php echo esc_url( admin_url( 'tools.php?page=wp-verifier-rulesets' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'wp-verifier' ); ?></a>
			</p>
		</form>
		<?php
	}

	/**
	 * Saves a custom ruleset.
	 */
	public function save_ruleset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wp-verifier' ) );
		}

		check_admin_referer( 'save_custom_ruleset', 'ruleset_nonce' );

		$ruleset_id = isset( $_POST['ruleset_id'] ) ? sanitize_text_field( wp_unslash( $_POST['ruleset_id'] ) ) : '';
		$is_new     = empty( $ruleset_id );

		if ( $is_new ) {
			$ruleset_id = 'ruleset_' . time();
		}

		$rulesets = $this->get_rulesets();

		$rulesets[ $ruleset_id ] = array(
			'name'        => isset( $_POST['ruleset_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ruleset_name'] ) ) : '',
			'description' => isset( $_POST['ruleset_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ruleset_description'] ) ) : '',
			'categories'  => isset( $_POST['categories'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['categories'] ) ) : array(),
			'created'     => isset( $rulesets[ $ruleset_id ]['created'] ) ? $rulesets[ $ruleset_id ]['created'] : time(),
			'modified'    => time(),
		);

		update_option( self::OPTION_NAME, $rulesets );

		wp_safe_redirect( admin_url( 'tools.php?page=wp-verifier-rulesets&saved=1' ) );
		exit;
	}

	/**
	 * Deletes a custom ruleset.
	 */
	public function delete_ruleset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wp-verifier' ) );
		}

		$ruleset_id = isset( $_GET['ruleset'] ) ? sanitize_text_field( wp_unslash( $_GET['ruleset'] ) ) : '';
		check_admin_referer( 'delete_ruleset_' . $ruleset_id );

		$rulesets = $this->get_rulesets();
		unset( $rulesets[ $ruleset_id ] );
		update_option( self::OPTION_NAME, $rulesets );

		wp_safe_redirect( admin_url( 'tools.php?page=wp-verifier-rulesets&deleted=1' ) );
		exit;
	}

	/**
	 * Exports a custom ruleset as JSON.
	 */
	public function export_ruleset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wp-verifier' ) );
		}

		$ruleset_id = isset( $_GET['ruleset'] ) ? sanitize_text_field( wp_unslash( $_GET['ruleset'] ) ) : '';
		check_admin_referer( 'export_ruleset_' . $ruleset_id );

		$rulesets = $this->get_rulesets();
		if ( ! isset( $rulesets[ $ruleset_id ] ) ) {
			wp_die( esc_html__( 'Ruleset not found.', 'wp-verifier' ) );
		}

		$ruleset = $rulesets[ $ruleset_id ];
		$filename = sanitize_file_name( $ruleset['name'] ) . '-ruleset.json';

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo wp_json_encode( $ruleset, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Imports a custom ruleset from JSON.
	 */
	public function import_ruleset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'wp-verifier' ) );
		}

		check_admin_referer( 'import_custom_ruleset', 'import_nonce' );

		if ( ! isset( $_FILES['ruleset_file'] ) || $_FILES['ruleset_file']['error'] !== UPLOAD_ERR_OK ) {
			wp_die( esc_html__( 'File upload failed.', 'wp-verifier' ) );
		}

		$file_content = file_get_contents( $_FILES['ruleset_file']['tmp_name'] );
		$ruleset = json_decode( $file_content, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $ruleset ) ) {
			wp_die( esc_html__( 'Invalid ruleset file.', 'wp-verifier' ) );
		}

		$rulesets = $this->get_rulesets();
		$ruleset_id = 'ruleset_' . time();
		$rulesets[ $ruleset_id ] = $ruleset;

		update_option( self::OPTION_NAME, $rulesets );

		wp_safe_redirect( admin_url( 'tools.php?page=wp-verifier-rulesets&imported=1' ) );
		exit;
	}

	/**
	 * Gets all custom rulesets.
	 *
	 * @return array Array of custom rulesets.
	 */
	public function get_rulesets() {
		return get_option( self::OPTION_NAME, array() );
	}

	/**
	 * Gets a specific ruleset by ID.
	 *
	 * @param string $ruleset_id Ruleset ID.
	 * @return array|null Ruleset data or null if not found.
	 */
	public function get_ruleset( $ruleset_id ) {
		$rulesets = $this->get_rulesets();
		return isset( $rulesets[ $ruleset_id ] ) ? $rulesets[ $ruleset_id ] : null;
	}
}
