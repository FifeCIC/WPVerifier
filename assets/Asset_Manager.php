<?php
/**
 * Centralized Asset Management
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Assets;

if (!defined('ABSPATH')) {
    exit;
}

class Asset_Manager {
    
    private $assets = array();
    private $ajax_manager;
    
    public function __construct( $ajax_manager = null ) {
        $this->ajax_manager = $ajax_manager;
        $this->init_assets();
    }
    
    private function init_assets() {
        $this->assets = array(
            'css' => require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'assets/style-assets.php',
            'js' => require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'assets/script-assets.php'
        );
    }
    
    public function enqueue_for_tab( $tab ) {
        $this->enqueue_global_assets();
        
        switch ( $tab ) {
            case 'preparation':
                $this->enqueue_preparation_assets();
                break;
            case 'verify':
                $this->enqueue_verification_assets();
                break;
            case 'saved':
            case 'results':
                $this->enqueue_saved_results_assets();
                break;
            case 'issues':
                $this->enqueue_issues_assets();
                break;
            case 'monitoring':
                $this->enqueue_monitoring_assets();
                break;
            case 'settings':
                $this->enqueue_settings_assets();
                break;
        }
    }
    
    private function enqueue_global_assets() {
        // Core scripts
        wp_enqueue_script(
            'plugin-check-admin',
            WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/plugin-check-admin.js',
            array('wp-util'),
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
        
        wp_enqueue_script(
            'wp-verifier-ai-guidance',
            WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/ai-guidance.js',
            array('jquery'),
            WP_PLUGIN_CHECK_VERSION,
            true
        );
        
        // Core styles
        wp_enqueue_style(
            'plugin-check-admin',
            WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/css/plugin-check-admin.css',
            array(),
            WP_PLUGIN_CHECK_VERSION
        );
        
        wp_enqueue_style(
            'admin-help-tabs',
            WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/css/admin-help-tabs.css',
            array(),
            WP_PLUGIN_CHECK_VERSION
        );
        
        wp_enqueue_style(
            'admin-footer',
            WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/css/admin-footer.css',
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
        
        $this->add_global_inline_scripts();
    }
    
    private function enqueue_preparation_assets() {
        wp_enqueue_script(
            'wpv-ajax',
            WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/wpv-ajax.js',
            array('jquery'),
            WP_PLUGIN_CHECK_VERSION,
            true
        );
        
        wp_enqueue_script(
            'admin-configuration',
            WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/admin-configuration.js',
            array('jquery', 'wpv-ajax'),
            WP_PLUGIN_CHECK_VERSION,
            true
        );
        
        wp_enqueue_style(
            'admin-configuration',
            WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/css/admin-configuration.css',
            array(),
            WP_PLUGIN_CHECK_VERSION
        );
        
        $current_plugin = get_user_meta( get_current_user_id(), 'wpv_last_selected_plugin', true );
        wp_localize_script(
            'wpv-ajax',
            'wpv_ajax_object',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => $this->ajax_manager ? $this->ajax_manager->get_nonce() : '',
                'current_plugin' => $current_plugin,
            )
        );
    }
    
    private function enqueue_verification_assets() {
        wp_enqueue_script(
            'wpv-ajax',
            WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/wpv-ajax.js',
            array('jquery'),
            WP_PLUGIN_CHECK_VERSION,
            true
        );
        
        wp_enqueue_script(
            'admin-verification',
            WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/admin-verification.js',
            array('jquery', 'wpv-ajax'),
            WP_PLUGIN_CHECK_VERSION,
            true
        );
        
        wp_enqueue_style(
            'admin-verification',
            WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/css/admin-verification.css',
            array(),
            WP_PLUGIN_CHECK_VERSION
        );
        
        $current_plugin = get_user_meta( get_current_user_id(), 'wpv_last_selected_plugin', true );
        wp_localize_script(
            'wpv-ajax',
            'wpv_ajax_object',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => $this->ajax_manager ? $this->ajax_manager->get_nonce() : '',
                'current_plugin' => $current_plugin,
            )
        );
    }
    
    private function enqueue_saved_results_assets() {
        wp_enqueue_script(
            'plugin-check-saved',
            WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/plugin-check-saved.js',
            array('jquery'),
            WP_PLUGIN_CHECK_VERSION,
            true
        );
        
        wp_enqueue_script(
            'admin-page-saved',
            WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/admin-page-saved.js',
            array('jquery'),
            WP_PLUGIN_CHECK_VERSION,
            true
        );
        
        // Get saved results for localization
        if ( ! class_exists( 'WordPress\\Plugin_Check\\Admin\\Saved_Results_Handler' ) ) {
            require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Saved_Results_Handler.php';
        }
        $plugin_info = \WordPress\Plugin_Check\Admin\Saved_Results_Handler::get_last_selected_plugin();
        $results_data = $plugin_info ? \WordPress\Plugin_Check\Admin\Saved_Results_Handler::load_plugin_results( $plugin_info['slug'] ) : null;
        
        wp_localize_script(
            'admin-page-saved',
            'wpvSavedData',
            array(
                'results' => $results_data ? $results_data['results'] : array(),
            )
        );
        
        // Add AI Guidance configuration
        if ( ! class_exists( 'WordPress\\Plugin_Check\\Utilities\\AI_Guidance' ) ) {
            require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Utilities/AI_Guidance.php';
        }
        $ai_guidance = \WordPress\Plugin_Check\Utilities\AI_Guidance::get_all_guidance();
        wp_add_inline_script(
            'plugin-check-saved',
            'const wpvAiGuidance = ' . json_encode( $ai_guidance ) . ';',
            'before'
        );
        
        wp_localize_script(
            'plugin-check-saved',
            'wpvConfig',
            array(
                'pluginUrl' => WP_PLUGIN_CHECK_PLUGIN_DIR_URL,
                'pluginDir' => WP_PLUGIN_DIR,
                'nonce' => $this->ajax_manager ? $this->ajax_manager->get_nonce() : '',
            )
        );
    }
    
    private function enqueue_issues_assets() {
        wp_enqueue_style(
            'wpv-issues-tab',
            WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/css/issues-tab.css',
            array(),
            WP_PLUGIN_CHECK_VERSION
        );
        
        wp_enqueue_script(
            'wpv-issues-tab',
            WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/js/issues-tab.js',
            array('jquery'),
            WP_PLUGIN_CHECK_VERSION,
            true
        );
        
        $current_plugin = get_user_meta( get_current_user_id(), 'wpv_last_selected_plugin', true );
        wp_localize_script(
            'wpv-issues-tab',
            'wpv_ajax_object',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => $this->ajax_manager ? $this->ajax_manager->get_nonce() : '',
                'current_plugin' => $current_plugin,
            )
        );
    }
    
    private function enqueue_monitoring_assets() {
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
                'nonce'              => $this->ajax_manager ? $this->ajax_manager->get_nonce() : '',
                'actionLoadResults'  => 'plugin_check_load_results',
                'actionStartMonitor' => 'plugin_check_start_monitoring',
                'actionStopMonitor'  => 'plugin_check_stop_monitoring',
                'actionViewLog'      => 'plugin_check_monitor_log',
                'verifyTabUrl'       => admin_url( 'plugins.php?page=wp-verifier&tab=verify' ),
                'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
            )
        );
    }
    
    private function enqueue_settings_assets() {
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
    
    private function add_global_inline_scripts() {
        wp_add_inline_script(
            'plugin-check-admin',
            'const PLUGIN_CHECK = ' . json_encode(
                array(
                    'nonce'                           => $this->ajax_manager ? $this->ajax_manager->get_nonce() : '',
                    'actionGetChecksToRun'            => 'plugin_check_get_checks_to_run',
                    'actionSetUpRuntimeEnvironment'   => 'plugin_check_set_up_environment',
                    'actionRunChecks'                 => 'plugin_check_run_checks',
                    'actionCleanUpRuntimeEnvironment' => 'plugin_check_clean_up_environment',
                    'actionExportResults'             => 'plugin_check_export_results',
                    'actionSaveResults'               => 'plugin_check_save_results',
                    'actionLoadResults'               => 'plugin_check_load_results',
                    'actionListSavedResults'          => 'plugin_check_list_saved_results',
                    'actionAddIgnoreRule'             => 'plugin_check_add_ignore_rule',
                    'actionAddIgnoreDirectory'        => 'plugin_check_add_ignore_directory',
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
            'const WPVerifierLibraries = ' . json_encode( $known_libraries ) . '; const wpvPluginUrl = ' . json_encode( WP_PLUGIN_CHECK_PLUGIN_DIR_URL ) . ';',
            'before'
        );
        
        $ignore_rules = get_option( 'wpv_ignore_rules', array() );
        wp_add_inline_script(
            'wp-verifier-ast',
            'const wpvIgnoreRules = ' . json_encode( $ignore_rules ) . ';',
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
    
    private function get_auto_save_setting() {
        $settings = get_option( 'plugin_check_settings', array() );
        return isset( $settings['auto_save_results'] ) ? (bool) $settings['auto_save_results'] : true;
    }
}