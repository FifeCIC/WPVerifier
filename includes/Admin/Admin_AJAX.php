<?php
/**
 * DEPRECATED: Admin_AJAX class
 *
 * This class has been replaced by specialized AJAX handlers for better code organization.
 * It now only contains constants for backward compatibility and a deprecation notice.
 *
 * @package plugin-check
 * @deprecated Use AJAX_Handler_Manager and specialized handlers instead
 */

namespace WordPress\Plugin_Check\Admin;

/**
 * DEPRECATED: Use AJAX_Handler_Manager instead
 *
 * @deprecated 1.0.0 Use specialized AJAX handlers via AJAX_Handler_Manager
 */
final class Admin_AJAX {

	/**
	 * Nonce key - kept for backward compatibility
	 */
	const NONCE_KEY = 'plugin-check-run-checks';

	// Action constants kept for backward compatibility
	const ACTION_CLEAN_UP_ENVIRONMENT = 'plugin_check_clean_up_environment';
	const ACTION_SET_UP_ENVIRONMENT = 'plugin_check_set_up_environment';
	const ACTION_GET_CHECKS_TO_RUN = 'plugin_check_get_checks_to_run';
	const ACTION_RUN_CHECKS = 'plugin_check_run_checks';
	const ACTION_EXPORT_RESULTS = 'plugin_check_export_results';
	const ACTION_SAVE_RESULTS = 'plugin_check_save_results';
	const ACTION_LOAD_RESULTS = 'plugin_check_load_results';
	const ACTION_LIST_SAVED_RESULTS = 'plugin_check_list_saved_results';
	const ACTION_ADD_IGNORE_RULE = 'plugin_check_add_ignore_rule';
	const ACTION_ADD_IGNORE_DIRECTORY = 'plugin_check_add_ignore_directory';
	const ACTION_GET_SCAN_HISTORY = 'plugin_check_get_scan_history';
	const ACTION_CLEAR_SCAN_HISTORY = 'plugin_check_clear_scan_history';
	const ACTION_GENERATE_REPORT = 'plugin_check_generate_report';
	const ACTION_CHECK_DOMAINS = 'plugin_check_domains';
	const ACTION_SAVE_NAME = 'plugin_check_save_name';
	const ACTION_GET_SAVED_NAMES = 'plugin_check_get_saved_names';
	const ACTION_CHECK_CONFLICTS = 'plugin_check_name_conflicts';
	const ACTION_ANALYZE_SEO = 'plugin_check_analyze_seo';
	const ACTION_CHECK_TRADEMARKS = 'plugin_check_check_trademarks';
	const ACTION_START_MONITORING = 'plugin_check_start_monitoring';
	const ACTION_STOP_MONITORING = 'plugin_check_stop_monitoring';
	const ACTION_CHECK_FILE_CHANGES = 'plugin_check_file_changes';
	const ACTION_GET_MONITOR_LOG = 'plugin_check_monitor_log';
	const ACTION_DELETE_RESULTS = 'plugin_check_delete_results';
	const ACTION_MARK_COMPLETE = 'plugin_check_mark_complete';
	const ACTION_DETECT_VENDORS = 'plugin_check_detect_vendors';
	const ACTION_SAVE_IGNORED_PATHS = 'plugin_check_save_ignored_paths';
	const ACTION_BASIC_CHECK = 'plugin_check_basic_check';
	const ACTION_VALIDATE_STRUCTURE = 'plugin_check_validate_structure';
	const ACTION_RECHECK_FILE = 'wpv_recheck_file';
	const ACTION_MARK_IGNORED = 'wpv_mark_ignored';
	const ACTION_MARK_RESOLVED = 'wpv_mark_resolved';
	const ACTION_UPDATE_CONFIG = 'plugin_check_update_config';
	const ACTION_GENERATE_HASHES = 'wpv_generate_hashes';

	/**
	 * DEPRECATED: This class has been replaced
	 *
	 * @deprecated Use AJAX_Handler_Manager instead
	 */
	public function add_hooks() {
		_deprecated_function( __METHOD__, '1.0.0', 'AJAX_Handler_Manager::add_hooks()' );
		// No hooks registered - functionality moved to AJAX_Handler_Manager
	}

	/**
	 * DEPRECATED: Get nonce
	 *
	 * @deprecated Use AJAX_Handler_Manager::get_nonce() instead
	 */
	public function get_nonce() {
		_deprecated_function( __METHOD__, '1.0.0', 'AJAX_Handler_Manager::get_nonce()' );
		return wp_create_nonce( self::NONCE_KEY );
	}
}