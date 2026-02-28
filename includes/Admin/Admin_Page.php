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
		add_action( 'admin_init', array( $this, 'handle_ignore_code_request' ) );
		add_action( 'admin_post_wpv_add_ignore_rule', array( $this, 'add_ignore_rule' ) );
		add_action( 'admin_post_wpv_remove_ignore_rule', array( $this, 'remove_ignore_rule' ) );
		add_action( 'admin_post_wpv_export_ignore_rules', array( $this, 'export_ignore_rules' ) );
		add_action( 'admin_post_wpv_import_ignore_rules', array( $this, 'import_ignore_rules' ) );
		add_action( 'admin_post_wpv_mark_fixed', array( $this, 'mark_issue_fixed' ) );

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

		// Enqueue basic verification scripts if on basic tab
		if ( 'basic' === $current_tab ) {
			wp_enqueue_script(
				'basic-verification',
				WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/basic-verification.js',
				array('jquery'),
				WP_PLUGIN_CHECK_VERSION,
				true
			);
		}

		// Enqueue preparation scripts if on preparation tab
		if ( 'preparation' === $current_tab ) {
			wp_enqueue_script(
				'plugin-check-preparation',
				WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/plugin-check-preparation.js',
				array('jquery'),
				WP_PLUGIN_CHECK_VERSION,
				true
			);
			
			wp_add_inline_script(
				'plugin-check-preparation',
				'const PLUGIN_CHECK = ' . json_encode(
					array(
						'nonce' => $this->admin_ajax->get_nonce(),
					)
				),
				'before'
			);
		}

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

		// Enqueue AI Guidance script
		wp_enqueue_script(
			'wp-verifier-ai-guidance',
			WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/ai-guidance.js',
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
			wp_enqueue_style(
				'wpv-plugin-namer',
				WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/css/admin-plugin-namer.css',
				array(),
				WP_PLUGIN_CHECK_VERSION
			);

			wp_enqueue_script(
				'wpv-plugin-namer',
				WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/admin-plugin-namer.js',
				array( 'jquery' ),
				WP_PLUGIN_CHECK_VERSION,
				true
			);

			wp_localize_script(
				'wpv-plugin-namer',
				'wpvPluginNamer',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => $this->admin_ajax->get_nonce(),
					'actions' => array(
						'checkDomains'    => Admin_AJAX::ACTION_CHECK_DOMAINS,
						'checkConflicts'  => Admin_AJAX::ACTION_CHECK_CONFLICTS,
						'analyzeSeo'      => Admin_AJAX::ACTION_ANALYZE_SEO,
						'checkTrademarks' => Admin_AJAX::ACTION_CHECK_TRADEMARKS,
						'saveName'        => Admin_AJAX::ACTION_SAVE_NAME,
						'getSavedNames'   => Admin_AJAX::ACTION_GET_SAVED_NAMES,
					),
					'enabledChecks' => $this->get_enabled_namer_checks(),
					'i18n' => array(
						'available'   => __( 'Available', 'wp-verifier' ),
						'taken'       => __( 'Taken', 'wp-verifier' ),
						'checking'    => __( 'Checking...', 'wp-verifier' ),
						'error'       => __( 'Error', 'wp-verifier' ),
						'noConflicts' => __( 'No conflicts found', 'wp-verifier' ),
						'exactMatch'  => __( 'Exact match found!', 'wp-verifier' ),
						'similar'     => __( 'Similar plugins found', 'wp-verifier' ),
						'saved'       => __( 'Evaluation saved successfully', 'wp-verifier' ),
						'saveFailed'  => __( 'Failed to save evaluation', 'wp-verifier' ),
					),
				)
			);
		}

		// Enqueue saved results scripts if on saved tab
		if ( 'saved' === $current_tab || 'results' === $current_tab ) {
			wp_enqueue_script(
				'plugin-check-saved',
				WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/plugin-check-saved.js',
				array('jquery'),
				WP_PLUGIN_CHECK_VERSION,
				true
			);
		}

		// Enqueue monitoring scripts if on monitoring tab
		if ( 'monitoring' === $current_tab ) {
			wp_enqueue_style(
				'plugin-monitoring',
				WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/css/plugin-monitoring.css',
				array(),
				WP_PLUGIN_CHECK_VERSION
			);

			wp_enqueue_script(
				'plugin-monitoring',
				WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/plugin-monitoring.js',
				array( 'jquery' ),
				WP_PLUGIN_CHECK_VERSION,
				true
			);

			wp_localize_script(
				'plugin-monitoring',
				'PluginMonitorConfig',
				array(
					'nonce'              => $this->admin_ajax->get_nonce(),
					'actionLoadResults'  => Admin_AJAX::ACTION_LOAD_RESULTS,
					'actionStartMonitor' => Admin_AJAX::ACTION_START_MONITORING,
					'actionStopMonitor'  => Admin_AJAX::ACTION_STOP_MONITORING,
					'actionViewLog'      => Admin_AJAX::ACTION_GET_MONITOR_LOG,
					'verifyTabUrl'       => admin_url( 'plugins.php?page=wp-verifier&tab=verify' ),
					'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
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
					'actionListSavedResults'          => Admin_AJAX::ACTION_LIST_SAVED_RESULTS,
					'actionAddIgnoreRule'             => Admin_AJAX::ACTION_ADD_IGNORE_RULE,
					'actionAddIgnoreDirectory'        => Admin_AJAX::ACTION_ADD_IGNORE_DIRECTORY,
					'autoSaveResults'                 => $this->get_auto_save_setting(),
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
			) . '; function getErrorIcon(code) { const meta = wpvErrorMetadata[code]; return meta ? `<span class="dashicons dashicons-${meta.icon}" style="color: ${meta.color};" title="${meta.description}"></span>` : `<span class="dashicons dashicons-warning" style="color: #666;"></span>`; }',
			'before'
		);

		$known_libraries = require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/known-libraries.php';
		wp_add_inline_script(
			'wp-verifier-ast',
			'const WPVerifierLibraries = ' . json_encode( $known_libraries ) . ';',
			'before'
		);
		
		$ignore_rules = get_option( 'wpv_ignore_rules', array() );
		wp_add_inline_script(
			'wp-verifier-ast',
			'const wpvIgnoreRules = ' . json_encode( $ignore_rules ) . ';',
			'before'
		);
		
		// Add AI Guidance configuration
		if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\AI_Guidance' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/AI_Guidance.php';
		}
		$ai_guidance = \WordPress\Plugin_Check\Utilities\AI_Guidance::get_all_guidance();
		wp_add_inline_script(
			'wp-verifier-ai-guidance',
			'const wpvAiGuidance = ' . json_encode( $ai_guidance ) . ';',
			'before'
		);
		
		// Add Error Metadata configuration
		if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Error_Metadata' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Error_Metadata.php';
		}
		$error_metadata = \WordPress\Plugin_Check\Utilities\Error_Metadata::get_all_metadata();
		wp_add_inline_script(
			'plugin-check-admin',
			'const wpvErrorMetadata = ' . json_encode( $error_metadata ) . ';',
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
			case 'preparation':
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page-preparation.php';
				break;
			case 'basic':
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page-basic.php';
				break;
			case 'verify':
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page.php';
				break;
			case 'results':
				if ( ! class_exists( 'WordPress\\Plugin_Check\\Admin\\Saved_Results_Handler' ) ) {
					require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Saved_Results_Handler.php';
				}
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page-saved.php';
				break;
			case 'monitoring':
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page-monitoring.php';
				break;
			case 'explore':
				require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'templates/admin-page-explore.php';
				break;
			case 'namer':
				if ( ! class_exists( 'WordPress\\Plugin_Check\\Admin\\Plugin_Namer_Tab' ) ) {
					require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Plugin_Namer_Tab.php';
				}
				Plugin_Namer_Tab::render();
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
			case 'ai-guidance':
				$this->render_ai_guidance_tab();
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

	/**
	 * Gets enabled namer checks from settings.
	 *
	 * @since 1.9.0
	 *
	 * @return array Enabled checks.
	 */
	private function get_enabled_namer_checks() {
		$settings = get_option( 'plugin_check_settings', array() );
		$checks = isset( $settings['namer_checks'] ) ? $settings['namer_checks'] : array(
			'domains'    => true,
			'conflicts'  => true,
			'seo'        => true,
			'trademarks' => true,
		);
		return $checks;
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

	public function handle_ignore_code_request() {
		if ( ! isset( $_GET['page'] ) || 'wp-verifier' !== $_GET['page'] ) {
			return;
		}
		
		if ( ! isset( $_GET['action'] ) || 'ignore_code' !== $_GET['action'] ) {
			return;
		}
		
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], Admin_AJAX::NONCE_KEY ) ) {
			wp_die( 'Invalid nonce' );
		}
		
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( 'Insufficient permissions' );
		}
		
		$plugin = isset( $_GET['plugin'] ) ? sanitize_text_field( wp_unslash( $_GET['plugin'] ) ) : '';
		$file = isset( $_GET['file'] ) ? sanitize_text_field( wp_unslash( $_GET['file'] ) ) : '';
		$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		
		if ( empty( $plugin ) || empty( $file ) || empty( $code ) ) {
			wp_die( 'Missing required parameters' );
		}
		
		$ignore_rules = get_option( 'wpv_ignore_rules', array() );
		
		if ( ! isset( $ignore_rules[ $plugin ] ) ) {
			$ignore_rules[ $plugin ] = array();
		}
		
		$ignore_rules[ $plugin ][] = array(
			'file' => $file,
			'code' => $code,
			'added' => current_time( 'mysql' ),
		);
		
		update_option( 'wpv_ignore_rules', $ignore_rules );
		
		wp_safe_redirect( admin_url( 'plugins.php?page=wp-verifier&tab=results&plugin=' . urlencode( $plugin ) . '&ignored=1' ) );
		exit;
	}

	public function add_ignore_rule() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-verifier' ) );
		}

		check_admin_referer( 'wpv_add_ignore_rule', 'wpv_nonce' );

		$scope = isset( $_POST['scope'] ) ? sanitize_text_field( wp_unslash( $_POST['scope'] ) ) : '';
		$path = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';
		$code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
		$reason = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : 'other';
		$note = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '';

		\WordPress\Plugin_Check\Utilities\Ignore_Rules::add_rule( $scope, $path, $reason, $code, $note );

		wp_safe_redirect( admin_url( 'plugins.php?page=wp-verifier&tab=preparation&added=1' ) );
		exit;
	}

	public function remove_ignore_rule() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-verifier' ) );
		}

		$rule_id = isset( $_GET['rule_id'] ) ? sanitize_text_field( wp_unslash( $_GET['rule_id'] ) ) : '';
		check_admin_referer( 'wpv_remove_rule_' . $rule_id );

		\WordPress\Plugin_Check\Utilities\Ignore_Rules::remove_rule( $rule_id );

		wp_safe_redirect( admin_url( 'plugins.php?page=wp-verifier&tab=preparation&removed=1' ) );
		exit;
	}

	public function export_ignore_rules() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-verifier' ) );
		}

		check_admin_referer( 'wpv_export_rules' );

		$json = \WordPress\Plugin_Check\Utilities\Ignore_Rules::export_rules();

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="wpv-ignore-rules.json"' );
		echo $json;
		exit;
	}

	public function import_ignore_rules() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-verifier' ) );
		}

		check_admin_referer( 'wpv_import_rules', 'wpv_nonce' );

		if ( ! isset( $_FILES['rules_file'] ) || $_FILES['rules_file']['error'] !== UPLOAD_ERR_OK ) {
			wp_die( esc_html__( 'File upload failed.', 'wp-verifier' ) );
		}

		$json = file_get_contents( $_FILES['rules_file']['tmp_name'] );
		$success = \WordPress\Plugin_Check\Utilities\Ignore_Rules::import_rules( $json );

		if ( ! $success ) {
			wp_die( esc_html__( 'Invalid rules file.', 'wp-verifier' ) );
		}

		wp_safe_redirect( admin_url( 'plugins.php?page=wp-verifier&tab=preparation&imported=1' ) );
		exit;
	}

	public function mark_issue_fixed() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-verifier' ) );
		}

		check_admin_referer( 'wpv_mark_fixed' );

		$plugin = isset( $_GET['plugin'] ) ? sanitize_text_field( wp_unslash( $_GET['plugin'] ) ) : '';
		$issue_id = isset( $_GET['issue_id'] ) ? sanitize_text_field( wp_unslash( $_GET['issue_id'] ) ) : '';

		if ( empty( $plugin ) || empty( $issue_id ) ) {
			wp_die( esc_html__( 'Missing required parameters.', 'wp-verifier' ) );
		}

		\WordPress\Plugin_Check\Utilities\Issue_Fixes::mark_fixed( $plugin, $issue_id );

		wp_safe_redirect( admin_url( 'plugins.php?page=wp-verifier&tab=results&plugin=' . urlencode( $plugin ) . '&fixed=1' ) );
		exit;
	}

	/**
	 * Render AI Guidance tab
	 */
	public function render_ai_guidance_tab() {
		if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\AI_Guidance' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/AI_Guidance.php';
		}
		if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\Error_Metadata' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/Error_Metadata.php';
		}
		
		$guidance_data = \WordPress\Plugin_Check\Utilities\AI_Guidance::get_all_guidance();
		$metadata = \WordPress\Plugin_Check\Utilities\Error_Metadata::get_all_metadata();
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'AI Guidance & Error Metadata', 'wp-verifier' ); ?></h2>
			<p><?php esc_html_e( 'This table shows the AI guidance and visual metadata paired with PHPCS messages. When you "Copy for AI", the guidance will be appended to help AI make better decisions.', 'wp-verifier' ); ?></p>
			
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
					$all_codes = array_unique( array_merge( array_keys( $guidance_data ), array_keys( $metadata ) ) );
					if ( empty( $all_codes ) ) : ?>
						<tr>
							<td colspan="5"><?php esc_html_e( 'No AI guidance or metadata configured yet.', 'wp-verifier' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $all_codes as $error_code ) : 
							$guidance = $guidance_data[ $error_code ] ?? array();
							$meta = $metadata[ $error_code ] ?? array();
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
								<td><?php echo esc_html( $guidance['message'] ?? $meta['description'] ?? '' ); ?></td>
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
