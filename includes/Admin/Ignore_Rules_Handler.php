<?php
/**
 * Class WordPress\Plugin_Check\Admin\Ignore_Rules_Handler
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Handles ignore rules functionality.
 *
 * @since 1.0.0
 */
final class Ignore_Rules_Handler {

	/**
	 * Registers WordPress hooks for ignore rules.
	 *
	 * @since 1.0.0
	 */
	public static function add_hooks() {
		add_action( 'admin_init', array( __CLASS__, 'handle_ignore_code_request' ) );
		add_action( 'admin_post_wpv_add_ignore_rule', array( __CLASS__, 'add_ignore_rule' ) );
		add_action( 'admin_post_wpv_remove_ignore_rule', array( __CLASS__, 'remove_ignore_rule' ) );
		add_action( 'admin_post_wpv_export_ignore_rules', array( __CLASS__, 'export_ignore_rules' ) );
		add_action( 'admin_post_wpv_import_ignore_rules', array( __CLASS__, 'import_ignore_rules' ) );
		add_action( 'admin_post_wpv_mark_fixed', array( __CLASS__, 'mark_issue_fixed' ) );
	}

	public static function handle_ignore_code_request() {
		if ( ! isset( $_GET['page'] ) || 'wp-verifier' !== $_GET['page'] ) {
			return;
		}
		
		if ( ! isset( $_GET['action'] ) || 'ignore_code' !== $_GET['action'] ) {
			return;
		}
		
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'plugin-check-run-checks' ) ) {
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

	public static function add_ignore_rule() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wpverifier' ) );
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

	public static function remove_ignore_rule() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wpverifier' ) );
		}

		$rule_id = isset( $_GET['rule_id'] ) ? sanitize_text_field( wp_unslash( $_GET['rule_id'] ) ) : '';
		check_admin_referer( 'wpv_remove_rule_' . $rule_id );

		\WordPress\Plugin_Check\Utilities\Ignore_Rules::remove_rule( $rule_id );

		wp_safe_redirect( admin_url( 'plugins.php?page=wp-verifier&tab=preparation&removed=1' ) );
		exit;
	}

	public static function export_ignore_rules() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wpverifier' ) );
		}

		check_admin_referer( 'wpv_export_rules' );

		$json = \WordPress\Plugin_Check\Utilities\Ignore_Rules::export_rules();

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="wpv-ignore-rules.json"' );
		echo wp_json_encode( json_decode( $json ) );
		exit;
	}

	/**
	 * Import ignore rules from an uploaded JSON file.
	 *
	 * @since 1.0.0
	 * @version 1.9.0 Added isset() validation for $_FILES sub-key before access.
	 *
	 * @return void Redirects on success, calls wp_die() on failure.
	 */
	public static function import_ignore_rules() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wpverifier' ) );
		}

		check_admin_referer( 'wpv_import_rules', 'wpv_nonce' );

		if ( ! isset( $_FILES['rules_file']['error'] ) || $_FILES['rules_file']['error'] !== UPLOAD_ERR_OK ) {
			wp_die( esc_html__( 'File upload failed.', 'wpverifier' ) );
		}

		// Validate tmp_name exists before access — bail early with a clear error if missing.
		if ( empty( $_FILES['rules_file']['tmp_name'] ) ) {
			wp_die( esc_html__( 'Uploaded file path is missing.', 'wpverifier' ) );
		}

		$json = file_get_contents( sanitize_text_field( wp_unslash( $_FILES['rules_file']['tmp_name'] ) ) );
		$success = \WordPress\Plugin_Check\Utilities\Ignore_Rules::import_rules( $json );

		if ( ! $success ) {
			wp_die( esc_html__( 'Invalid rules file.', 'wpverifier' ) );
		}

		wp_safe_redirect( admin_url( 'plugins.php?page=wp-verifier&tab=preparation&imported=1' ) );
		exit;
	}

	public static function mark_issue_fixed() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wpverifier' ) );
		}

		check_admin_referer( 'wpv_mark_fixed' );

		$plugin = isset( $_GET['plugin'] ) ? sanitize_text_field( wp_unslash( $_GET['plugin'] ) ) : '';
		$issue_id = isset( $_GET['issue_id'] ) ? sanitize_text_field( wp_unslash( $_GET['issue_id'] ) ) : '';

		if ( empty( $plugin ) || empty( $issue_id ) ) {
			wp_die( esc_html__( 'Missing required parameters.', 'wpverifier' ) );
		}

		\WordPress\Plugin_Check\Utilities\Issue_Fixes::mark_fixed( $plugin, $issue_id );

		wp_safe_redirect( admin_url( 'plugins.php?page=wp-verifier&tab=results&plugin=' . urlencode( $plugin ) . '&fixed=1' ) );
		exit;
	}
}
