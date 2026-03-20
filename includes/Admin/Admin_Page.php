<?php
/**
 * Class WordPress\Plugin_Check\Admin\Admin_Page
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

use WordPress\Plugin_Check\Checker\Check;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Repository;
use WordPress\Plugin_Check\Checker\Check_Types;
use WordPress\Plugin_Check\Checker\Default_Check_Repository;

/**
 * Class is handling admin tools page functionality.
 *
 * @since 1.0.0
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class Admin_Page {

	/**
	 * AJAX Handler Manager instance.
	 *
	 * @since 1.0.0
	 * @var AJAX_Handler_Manager
	 */
	protected $ajax_manager;

	/**
	 * Admin page hook suffix.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $hook_suffix = '';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param AJAX_Handler_Manager $ajax_manager Instance of AJAX_Handler_Manager.
	 */
	public function __construct( AJAX_Handler_Manager $ajax_manager ) {
		$this->ajax_manager = $ajax_manager;
	}

	/**
	 * Registers WordPress hooks for the admin page.
	 *
	 * @since 1.0.0
	 */
	public function add_hooks() {
		add_action( 'admin_menu', array( $this, 'add_and_initialize_page' ) );
		add_filter( 'plugin_action_links', array( $this, 'filter_plugin_action_links' ), 10, 4 );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_jump_to_line_code_editor' ) );
		add_action( 'admin_post_wp_verifier_save_ai_config', array( $this, 'save_ai_config' ) );
		add_action( 'admin_action_wp_verifier_setup', array( $this, 'render_setup_wizard' ) );

		// Initialize ignore rules handler
		if ( ! class_exists( 'WordPress\\Plugin_Check\\Admin\\Ignore_Rules_Handler' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Ignore_Rules_Handler.php';
		}
		Ignore_Rules_Handler::add_hooks();

		$this->ajax_manager->add_hooks();
	}

	/**
	 * Adds the admin page under the plugins menu.
	 *
	 * @since 1.0.0
	 */
	public function add_page() {
		$this->hook_suffix = add_plugins_page(
			__( 'Verify Plugins', 'wp-verifier' ),
			__( 'Verify Plugins', 'wp-verifier' ),
			'activate_plugins',
			'wp-verifier',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Adds and initializes the admin page under the plugins menu.
	 *
	 * @since 1.0.0
	 */
	public function add_and_initialize_page() {
		$this->add_page();
		add_action( 'load-' . $this->get_hook_suffix(), array( $this, 'initialize_page' ) );
	}

	/**
	 * Initializes page hooks.
	 *
	 * @since 1.0.0
	 */
	public function initialize_page() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_footer', array( $this, 'admin_footer' ) );

		if ( ! class_exists( 'WordPress\\Plugin_Check\\Admin\\Help_Tabs' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Help_Tabs.php';
		}
		Help_Tabs::add_help_tabs();
	}

	/**
	 * Loads the check's script.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_title( wp_unslash( $_GET['tab'] ) ) : 'verify';

		if ( ! class_exists( 'WordPress\\Plugin_Check\\Assets\\Asset_Manager' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'assets/Asset_Manager.php';
		}
		
		$asset_manager = new \WordPress\Plugin_Check\Assets\Asset_Manager( $this->ajax_manager );
		$asset_manager->enqueue_for_tab( $current_tab );
	}

	/**
	 * Enqueue a script in the WordPress admin on plugin-editor.php.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function add_jump_to_line_code_editor( $hook_suffix ) {
		if ( 'plugin-editor.php' !== $hook_suffix ) {
			return;
		}

		$line = (int) ( $_GET['line'] ?? 0 );
		if ( ! $line ) {
			return;
		}

		wp_add_inline_script(
			'wp-theme-plugin-editor',
			sprintf(
				'
					(
						( originalInitCodeEditor ) => {
							wp.themePluginEditor.initCodeEditor = function() {
								originalInitCodeEditor.apply( this, arguments );
								this.instance.codemirror.doc.setCursor( %d - 1 );
							};
						}
					)( wp.themePluginEditor.initCodeEditor );
				',
				wp_json_encode( $line )
			)
		);
	}

	/**
	 * Returns the list of plugins.
	 *
	 * @since 1.0.0
	 *
	 * @return array List of available plugins.
	 */
	private function get_available_plugins() {
		$available_plugins = get_plugins();

		if ( empty( $available_plugins ) ) {
			return array();
		}

		$plugin_check_base_name = plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE );

		if ( isset( $available_plugins[ $plugin_check_base_name ] ) ) {
			unset( $available_plugins[ $plugin_check_base_name ] );
		}

		return $available_plugins;
	}

	/**
	 * Get last selected plugin from user meta.
	 *
	 * @since 1.9.0
	 *
	 * @return array|null Plugin data or null.
	 */
	private function get_last_selected_plugin() {
		$plugin_slug = get_user_meta( get_current_user_id(), 'wpv_last_selected_plugin', true );
		if ( ! $plugin_slug ) {
			return null;
		}
		
		$plugins = get_plugins();
		if ( ! isset( $plugins[ $plugin_slug ] ) ) {
			return null;
		}
		
		return array(
			'slug' => $plugin_slug,
			'name' => $plugins[ $plugin_slug ]['Name'],
		);
	}

	/**
	 * Renders the "Plugin Check" page.
	 *
	 * @since 1.0.0
	 *
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function render_page() {
		$available_plugins = $this->get_available_plugins();

		$selected_plugin_basename = filter_input( INPUT_GET, 'plugin', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		$categories = Check_Categories::get_categories();
		$types      = Check_Types::get_types();

		// Get user settings for category preferences.
		$user_enabled_categories = get_user_setting( 'plugin_check_category_preferences', implode( '__', $this->get_default_check_categories_to_be_selected() ) );
		$user_enabled_categories = explode( '__', $user_enabled_categories );

		$check_repo = new Default_Check_Repository();

		$collection = $check_repo->get_checks( Check_Repository::TYPE_ALL | Check_Repository::INCLUDE_EXPERIMENTAL )->filter(
			static function ( Check $check ) {
				return $check->get_stability() === Check::STABILITY_EXPERIMENTAL;
			}
		);

		$has_experimental_checks = count( $collection ) > 0;

		// Get current tab
		$current_tab = isset( $_GET['tab'] ) ? sanitize_title( wp_unslash( $_GET['tab'] ) ) : 'verify';

		// Render tabs
		if ( ! class_exists( 'WordPress\\Plugin_Check\\Admin\\Admin_Page_Tabs' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Admin_Page_Tabs.php';
		}

		echo '<div class="wrap">';
		
		$page_title = __( 'Verify Plugins', 'wp-verifier' );
		$last_plugin = $this->get_last_selected_plugin();
		if ( $last_plugin ) {
			$page_title .= ': ' . esc_html( $last_plugin['name'] );
		}
		
		echo '<h1>' . $page_title . '</h1>';
		
		if ( isset( $_GET['ignored'] ) && '1' === $_GET['ignored'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Issue ignored successfully. Run a new scan to see updated results.', 'wp-verifier' ) . '</p></div>';
		}
		
		Admin_Page_Tabs::render_tabs();

		// Render tab content
		switch ( $current_tab ) {
			case 'select':
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page-select-plugin.php';
				break;
			case 'preparation':
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page-configuration.php';
				break;
			case 'verify':
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page-verification.php';
				break;
			case 'results':
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page-results.php';
				break;
			case 'monitoring':
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page-monitoring.php';
				break;
			case 'test-area':
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page-test-area.php';
				break;
			case 'settings':
				if ( ! class_exists( 'WordPress\\Plugin_Check\\Admin\\Settings_Page' ) ) {
					require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Settings_Page.php';
				}
				$settings_page = new Settings_Page();
				$settings_page->render_ai_tab();
				break;
			case 'assets':
				if ( ! class_exists( 'WordPress\\Plugin_Check\\Admin\\Assets_Tab' ) ) {
					require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Assets_Tab.php';
				}
				Assets_Tab::render();
				break;
			case 'error-codes':
				$this->render_error_codes_tab();
				break;
			case 'architecture':
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page-architecture.php';
				break;
			case 'roadmap':
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page-roadmap.php';
				break;
			default:
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page-verification.php';
				break;
		}

		echo '</div>';
	}

	/**
	 * Adds "check this plugin" link in the plugins list table.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $actions     List of actions.
	 * @param string $plugin_file Plugin main file.
	 * @param array  $plugin_data An array of plugin data.
	 * @param string $context     The plugin context. By default this can include 'all',
	 *                            'active', 'inactive', 'recently_activated', 'upgrade',
	 *                            'mustuse', 'dropins', and 'search'.
	 * @return array The modified list of actions.
	 */
	public function filter_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {

		if ( in_array( $context, array( 'mustuse', 'dropins' ), true ) ) {
			return $actions;
		}

		$plugin_check_base_name = plugin_basename( WP_PLUGIN_CHECK_MAIN_FILE );
		if ( $plugin_check_base_name === $plugin_file ) {
			$actions[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url( 'plugins.php?page=wp-verifier' ) ),
				esc_html__( 'Check a plugin', 'wp-verifier' )
			);
			return $actions;
		}

		if ( current_user_can( 'activate_plugins' ) ) {
			$actions[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url( "plugins.php?page=wp-verifier&plugin={$plugin_file}" ) ),
				esc_html__( 'Check this plugin', 'wp-verifier' )
			);
		}

		return $actions;
	}

	/**
	 * Render the results table templates in the footer.
	 *
	 * @since 1.0.0
	 */
	public function admin_footer() {
		ob_start();
		require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/results-ast.php';
		$ast_template = ob_get_clean();
		wp_print_inline_script_tag(
			$ast_template,
			array(
				'id'   => 'wpv-ast-template',
				'type' => 'text/template',
			)
		);

		ob_start();
		require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/results-table.php';
		$results_table_template = ob_get_clean();
		wp_print_inline_script_tag(
			$results_table_template,
			array(
				'id'   => 'tmpl-plugin-check-results-table',
				'type' => 'text/template',
			)
		);

		ob_start();
		require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/results-row.php';
		$results_row_template = ob_get_clean();
		wp_print_inline_script_tag(
			$results_row_template,
			array(
				'id'   => 'tmpl-plugin-check-results-row',
				'type' => 'text/template',
			)
		);

		ob_start();
		require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/results-complete.php';
		$results_row_template = ob_get_clean();
		wp_print_inline_script_tag(
			$results_row_template,
			array(
				'id'   => 'tmpl-plugin-check-results-complete',
				'type' => 'text/template',
			)
		);
		?>
		<?php
	}

	/**
	 * Gets the hook suffix under which the admin page is added.
	 *
	 * @since 1.0.0
	 *
	 * @return string Hook suffix, or empty string if admin page was not added.
	 */
	public function get_hook_suffix() {
		return $this->hook_suffix;
	}

	/**
	 * Gets default check categories to be selected.
	 *
	 * @since 1.0.2
	 *
	 * @return string[] An array of category slugs.
	 */
	private function get_default_check_categories_to_be_selected() {
		$default_check_categories = array(
			'plugin_repo',
		);

		/**
		 * Filters the default check categories to be selected.
		 *
		 * @since 1.0.2
		 *
		 * @param string[] $default_check_categories An array of category slugs.
		 */
		$default_categories = (array) apply_filters( 'wp_plugin_check_default_categories', $default_check_categories );

		return $default_categories;
	}

	/**
	 * Gets auto-save setting.
	 *
	 * @since 1.9.0
	 *
	 * @return bool Auto-save enabled.
	 */
	private function get_auto_save_setting() {
		$settings = get_option( 'plugin_check_settings', array() );
		return isset( $settings['auto_save_results'] ) ? (bool) $settings['auto_save_results'] : true;
	}

	public function save_ai_config() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-verifier' ) );
		}

		check_admin_referer( 'wp_verifier_save_ai_config', 'wp_verifier_ai_nonce' );

		$settings                = get_option( 'plugin_check_settings', array() );
		$settings['ai_provider'] = isset( $_POST['ai_provider'] ) ? sanitize_text_field( wp_unslash( $_POST['ai_provider'] ) ) : '';
		$settings['ai_api_key']  = isset( $_POST['ai_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['ai_api_key'] ) ) : '';
		$settings['ai_model']    = isset( $_POST['ai_model'] ) ? sanitize_text_field( wp_unslash( $_POST['ai_model'] ) ) : '';

		update_option( 'plugin_check_settings', $settings );

		wp_safe_redirect( add_query_arg( 'ai-config-saved', '1', wp_get_referer() ) );
		exit;
	}

	public function render_setup_wizard() {
		if ( ! class_exists( 'WordPress\\Plugin_Check\\Admin\\Setup_Wizard' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Setup_Wizard.php';
		}
		$wizard = new \WordPress\Plugin_Check\Admin\Setup_Wizard();
		$wizard->render();
	}



	/**
	 * Render Error Codes tab
	 */
	public function render_error_codes_tab() {
		if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\AI_Guidance' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/AI_Guidance.php';
		}
		if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Error_Metadata' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Error_Metadata.php';
		}
		
		// Load all PHPCS error codes
		$phpcs_codes_file = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'phpcs-error-codes.json';
		$phpcs_codes = array();
		if ( file_exists( $phpcs_codes_file ) ) {
			$phpcs_json = file_get_contents( $phpcs_codes_file );
			$phpcs_codes = json_decode( $phpcs_json, true );
		}
		
		$guidance_data = \WordPress\Plugin_Check\Utilities\AI_Guidance::get_all_guidance();
		$metadata = \WordPress\Plugin_Check\Utilities\Error_Metadata::get_all_metadata();
		
		// Get all unique error codes from all sources
		$all_codes = array_unique( array_merge( array_keys( $phpcs_codes ), array_keys( $guidance_data ), array_keys( $metadata ) ) );
		sort( $all_codes );
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Error Codes Reference', 'wp-verifier' ); ?></h2>
			<p><?php esc_html_e( 'This table shows all error codes with their AI guidance and visual metadata. When you "Copy for AI", the guidance will be appended to help AI make better decisions.', 'wp-verifier' ); ?></p>
			
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Error Code', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Icon', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Category', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Original Message', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'AI Guidance', 'wp-verifier' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php 
					if ( empty( $all_codes ) ) : ?>
						<tr>
							<td colspan="5"><?php esc_html_e( 'No error codes configured yet.', 'wp-verifier' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $all_codes as $error_code ) : 
							$guidance = $guidance_data[ $error_code ] ?? array();
							$meta = $metadata[ $error_code ] ?? array();
							$phpcs_desc = $phpcs_codes[ $error_code ] ?? '';
						?>
							<tr>
								<td><code><?php echo esc_html( $error_code ); ?></code></td>
								<td>
									<?php 
									if ( ! empty( $meta ) ) {
										echo \WordPress\Plugin_Check\Utilities\Error_Metadata::get_icon_html( $error_code );
										echo '<br><small style="color: #666;">' . esc_html( $meta['severity'] ?? '' ) . '</small>';
									} else {
										echo '<span class="dashicons dashicons-warning" style="color: #666;"></span>';
									}
									?>
								</td>
								<td><?php echo esc_html( $meta['category'] ?? 'General' ); ?></td>
								<td><?php echo esc_html( $guidance['message'] ?? $meta['description'] ?? $phpcs_desc ); ?></td>
								<td><?php echo esc_html( $guidance['ai_guidance'] ?? '' ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			
			<h3><?php esc_html_e( 'How to Use', 'wp-verifier' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'Run a plugin verification to generate PHPCS results', 'wp-verifier' ); ?></li>
				<li><?php esc_html_e( 'In the results, errors will display with colored icons based on their category and severity', 'wp-verifier' ); ?></li>
				<li><?php esc_html_e( 'Click "Copy for AI" on any issue to copy the enhanced message with AI guidance', 'wp-verifier' ); ?></li>
			</ol>
		</div>
		<?php
	}
}
