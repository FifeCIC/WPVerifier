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
	 * Admin AJAX class instance.
	 *
	 * @since 1.0.0
	 * @var Admin_AJAX
	 */
	protected $admin_ajax;

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
	 * @param Admin_AJAX $admin_ajax Instance of Admin_AJAX.
	 */
	public function __construct( Admin_AJAX $admin_ajax ) {
		$this->admin_ajax = $admin_ajax;
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

		$this->admin_ajax->add_hooks();
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

		$this->add_help_tab();
	}

	/**
	 * Adds the plugin help tab.
	 *
	 * @since 1.1.0
	 */
	public function add_help_tab() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$screen->add_help_tab(
			array(
				'id'       => 'wp-verifier',
				'title'    => __( 'Checks', 'wp-verifier' ),
				'content'  => '',
				'callback' => array( $this, 'render_help_tab' ),
			)
		);

		$screen->add_help_tab(
			array(
				'id'       => 'wp-verifier-setup',
				'title'    => __( 'Setup', 'wp-verifier' ),
				'content'  => '',
				'callback' => array( $this, 'render_setup_help_tab' ),
			)
		);
	}

	/**
	 * Renders the plugin help tab.
	 *
	 * @since 1.1.0
	 */
	public function render_help_tab() {
		$check_repo = new Default_Check_Repository();
		$collection = $check_repo->get_checks( Check_Repository::TYPE_ALL );

		if ( empty( $collection ) ) {
			return;
		}

		$category_labels = Check_Categories::get_categories();

		echo '<dl>';

		/**
		 * All checks to list.
		 *
		 * @var Check $check
		 */
		foreach ( $collection as $key => $check ) {
			$categories = array_map(
				static function ( $category ) use ( $category_labels ) {
					return $category_labels[ $category ] ?? $category;
				},
				$check->get_categories()
			);
			$categories = join( ', ', $categories );
			?>
			<dt>
				<code><?php echo esc_html( $key ); ?></code>
				(<?php echo esc_html( $categories ); ?>)
			</dt>
			<dd>
				<?php echo wp_kses( $check->get_description(), array( 'code' => array() ) ); ?>
				<br>
				<a href="<?php echo esc_url( $check->get_documentation_url() ); ?>">
					<?php esc_html_e( 'Learn more', 'wp-verifier' ); ?>
				</a>
			</dd>
			<?php
		}

		echo '</dl>';
	}

	public function render_setup_help_tab() {
		$settings = get_option( 'plugin_check_settings', array() );
		$setup_complete = get_option( 'wp_verifier_setup_complete' );
		$providers = require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/AI_Providers.php';
		
		if ( isset( $_GET['ai-config-saved'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'AI configuration saved successfully.', 'wp-verifier' ) . '</p></div>';
		}
		?>
		<h3><?php esc_html_e( 'Installation Status', 'wp-verifier' ); ?></h3>
		<p>
			<strong><?php esc_html_e( 'Setup Status:', 'wp-verifier' ); ?></strong>
			<?php
			if ( 'yes' === $setup_complete ) {
				echo '<span style="color: green;">✓ ' . esc_html__( 'Complete', 'wp-verifier' ) . '</span>';
			} elseif ( 'skipped' === $setup_complete ) {
				echo '<span style="color: orange;">⊘ ' . esc_html__( 'Skipped', 'wp-verifier' ) . '</span>';
			} else {
				echo '<span style="color: red;">✗ ' . esc_html__( 'Not Complete', 'wp-verifier' ) . '</span>';
			}
			?>
		</p>
		<?php if ( ! $setup_complete || 'skipped' === $setup_complete ) : ?>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?action=wp_verifier_setup' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Run Setup Wizard', 'wp-verifier' ); ?>
				</a>
			</p>
		<?php endif; ?>

		<h3><?php esc_html_e( 'AI Configuration', 'wp-verifier' ); ?></h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'wp_verifier_save_ai_config', 'wp_verifier_ai_nonce' ); ?>
			<input type="hidden" name="action" value="wp_verifier_save_ai_config" />
			<table class="form-table">
				<tr>
					<th scope="row"><label for="ai_provider"><?php esc_html_e( 'AI Provider', 'wp-verifier' ); ?></label></th>
					<td>
						<select id="ai_provider" name="ai_provider">
							<option value=""><?php esc_html_e( 'None', 'wp-verifier' ); ?></option>
							<?php foreach ( $providers as $key => $provider ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['ai_provider'] ?? '', $key ); ?>>
									<?php echo esc_html( $provider['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ai_api_key"><?php esc_html_e( 'API Key', 'wp-verifier' ); ?></label></th>
					<td>
						<input type="password" id="ai_api_key" name="ai_api_key" class="regular-text" value="<?php echo esc_attr( $settings['ai_api_key'] ?? '' ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ai_model"><?php esc_html_e( 'Model', 'wp-verifier' ); ?></label></th>
					<td>
						<input type="text" id="ai_model" name="ai_model" class="regular-text" value="<?php echo esc_attr( $settings['ai_model'] ?? '' ); ?>" placeholder="gpt-4" />
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Configuration', 'wp-verifier' ); ?>" />
			</p>
		</form>
		<?php
	}

	/**
	 * Loads the check's script.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$current_tab = isset( $_GET['tab'] ) ? sanitize_title( wp_unslash( $_GET['tab'] ) ) : 'verify';

		wp_enqueue_script(
			'plugin-check-admin',
			WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/plugin-check-admin.js',
			array(
				'wp-util',
			),
			WP_PLUGIN_CHECK_VERSION,
			true
		);

		wp_enqueue_script(
			'wp-verifier-ast',
			WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/wp-verifier-ast.js',
			array('jquery'),
			WP_PLUGIN_CHECK_VERSION,
			true
		);

		wp_enqueue_style(
			'plugin-check-admin',
			WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/css/plugin-check-admin.css',
			array(),
			WP_PLUGIN_CHECK_VERSION
		);

		wp_enqueue_style(
			'wp-verifier-tabs',
			WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/css/wp-verifier-tabs.css',
			array(),
			WP_PLUGIN_CHECK_VERSION
		);

		wp_enqueue_style(
			'wp-verifier-ast',
			WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/css/wp-verifier-ast.css',
			array(),
			WP_PLUGIN_CHECK_VERSION
		);

		// Enqueue namer scripts if on namer tab
		if ( 'namer' === $current_tab ) {
			wp_enqueue_script(
				'plugin-check-namer',
				WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/plugin-check-namer.js',
				array(),
				WP_PLUGIN_CHECK_VERSION,
				true
			);

			wp_localize_script(
				'plugin-check-namer',
				'pluginCheckNamer',
				array(
					'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'plugin_check_namer_ajax' ),
					'messages' => array(
						'missingName'  => __( 'Please enter a plugin name.', 'wp-verifier' ),
						'genericError' => __( 'An unexpected error occurred.', 'wp-verifier' ),
					),
				)
			);
		}

		// Enqueue settings scripts if on settings tab
		if ( 'settings' === $current_tab ) {
			wp_enqueue_script(
				'plugin-check-admin-settings',
				WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/admin-settings.js',
				array(),
				WP_PLUGIN_CHECK_VERSION,
				true
			);

			wp_localize_script(
				'plugin-check-admin-settings',
				'pluginCheckSettings',
				array(
					'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
					'nonce'           => wp_create_nonce( 'plugin_check_get_models' ),
					'loadingText'     => __( 'Loading models...', 'wp-verifier' ),
					'selectModelText' => __( '-- Select Model --', 'wp-verifier' ),
					'noModelsText'    => __( 'No models available. Please check your API key.', 'wp-verifier' ),
					'errorText'       => __( 'Error loading models', 'wp-verifier' ),
				)
			);
		}

		wp_add_inline_script(
			'plugin-check-admin',
			'const PLUGIN_CHECK = ' . json_encode(
				array(
					'nonce'                           => $this->admin_ajax->get_nonce(),
					'actionGetChecksToRun'            => Admin_AJAX::ACTION_GET_CHECKS_TO_RUN,
					'actionSetUpRuntimeEnvironment'   => Admin_AJAX::ACTION_SET_UP_ENVIRONMENT,
					'actionRunChecks'                 => Admin_AJAX::ACTION_RUN_CHECKS,
					'actionCleanUpRuntimeEnvironment' => Admin_AJAX::ACTION_CLEAN_UP_ENVIRONMENT,
					'actionExportResults'             => Admin_AJAX::ACTION_EXPORT_RESULTS,
					'actionSaveResults'               => Admin_AJAX::ACTION_SAVE_RESULTS,
					'actionLoadResults'               => Admin_AJAX::ACTION_LOAD_RESULTS,
					'successMessage'                  => __( 'No errors found.', 'wp-verifier' ),
					'errorMessage'                    => __( 'Errors were found.', 'wp-verifier' ),
					'strings'                         => array(
						'downloadCsv'      => __( 'Download CSV', 'wp-verifier' ),
						'downloadJson'     => __( 'Download JSON', 'wp-verifier' ),
						'downloadMarkdown' => __( 'Download Markdown', 'wp-verifier' ),
						'saveCsv'          => __( 'CSV File', 'wp-verifier' ),
						'saveJson'         => __( 'JSON File', 'wp-verifier' ),
						'saveMarkdown'     => __( 'Markdown File', 'wp-verifier' ),
						'exporting'        => __( 'Preparing export…', 'wp-verifier' ),
						'saving'           => __( 'Saving…', 'wp-verifier' ),
						'exportError'      => __( 'Export failed.', 'wp-verifier' ),
						'saveError'        => __( 'Save failed.', 'wp-verifier' ),
						'saveSuccess'      => __( 'File saved successfully.', 'wp-verifier' ),
						'noResults'        => __( 'There are no results to export yet.', 'wp-verifier' ),
					),
				)
			),
			'before'
		);

		$known_libraries = require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/known-libraries.php';
		wp_add_inline_script(
			'wp-verifier-ast',
			'const WPVerifierLibraries = ' . json_encode( $known_libraries ) . ';',
			'before'
		);
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
		echo '<h1>' . esc_html__( 'Verify Plugins', 'wp-verifier' ) . '</h1>';
		Admin_Page_Tabs::render_tabs();

		// Render tab content
		switch ( $current_tab ) {
			case 'verify':
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page.php';
				break;
			case 'namer':
				if ( ! class_exists( 'WordPress\\Plugin_Check\\Admin\\Namer_Page' ) ) {
					require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Namer_Page.php';
				}
				$namer_page = new Namer_Page();
				$namer_page->render_evaluate_tab();
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
			case 'dashboard':
			case 'history':
			case 'rulesets':
				Admin_Page_Tabs::render_coming_soon();
				break;
			default:
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page.php';
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
		<style>
			#plugin-check__results .notice,
			#plugin-check__results .notice + h4 {
				margin-top: 20px;
			}
			#plugin-check__results h4:first-child {
				margin-top: 80.5px;
			}
			@media ( max-width: 782px ) {
				#plugin-check__results h4:first-child {
					margin-top: 88.5px;
				}
			}
			.plugin-check__export-controls {
				margin-top: 24px;
				display: flex;
				gap: 8px;
				flex-wrap: wrap;
			}
			.plugin-check__export-controls.is-hidden {
				display: none;
			}
		</style>
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
}
