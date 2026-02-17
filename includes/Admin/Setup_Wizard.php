<?php
/**
 * Setup Wizard for WP Verifier installation.
 *
 * @package WPVerifier
 */

namespace WordPress\Plugin_Check\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setup_Wizard Class
 */
class Setup_Wizard {

	private $step = '';
	private $steps = array();

	public function __construct() {
		if ( apply_filters( 'wp_verifier_enable_setup_wizard', true ) && current_user_can( 'manage_options' ) ) {
			if ( ! get_option( 'wp_verifier_setup_complete' ) ) {
				add_action( 'admin_notices', array( $this, 'setup_wizard_notice' ) );
				add_action( 'admin_init', array( $this, 'hide_setup_notice' ) );
			}
		}
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-verifier' ) );
		}

		$this->steps = array(
			'introduction' => array(
				'name'    => __( 'Introduction', 'wp-verifier' ),
				'view'    => array( $this, 'step_introduction' ),
				'handler' => '',
			),
			'ai_config'    => array(
				'name'    => __( 'AI Setup', 'wp-verifier' ),
				'view'    => array( $this, 'step_ai_config' ),
				'handler' => array( $this, 'step_ai_config_save' ),
			),
			'features'     => array(
				'name'    => __( 'Features', 'wp-verifier' ),
				'view'    => array( $this, 'step_features' ),
				'handler' => array( $this, 'step_features_save' ),
			),
			'ready'        => array(
				'name'    => __( 'Ready!', 'wp-verifier' ),
				'view'    => array( $this, 'step_ready' ),
				'handler' => '',
			),
		);

		$this->step = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : current( array_keys( $this->steps ) );

		wp_enqueue_style( 'wp-verifier-setup', plugins_url( 'assets/css/wp-verifier-setup.css', WP_PLUGIN_CHECK_MAIN_FILE ), array( 'dashicons', 'install' ), WP_PLUGIN_CHECK_VERSION );

		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'] );
		}

		ob_start();
		$this->setup_wizard_header();
		$this->setup_wizard_steps();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	}

	public function get_next_step_link() {
		$keys = array_keys( $this->steps );
		return add_query_arg( 'step', $keys[ array_search( $this->step, array_keys( $this->steps ), true ) + 1 ] );
	}

	public function setup_wizard_header() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php esc_html_e( 'WP Verifier &rsaquo; Setup Wizard', 'wp-verifier' ); ?></title>
			<?php do_action( 'admin_print_styles' ); ?>
		</head>
		<body class="wp-verifier-setup wp-core-ui">
			<h1 id="wp-verifier-logo"><?php esc_html_e( 'WP Verifier', 'wp-verifier' ); ?></h1>
		<?php
	}

	public function setup_wizard_footer() {
		?>
			<?php if ( 'ready' === $this->step ) : ?>
				<a class="wp-verifier-return-to-dashboard" href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Return to Dashboard', 'wp-verifier' ); ?></a>
			<?php endif; ?>
			</body>
		</html>
		<?php
	}

	public function setup_wizard_steps() {
		$output_steps = $this->steps;
		array_shift( $output_steps );
		?>
		<ol class="wp-verifier-setup-steps">
			<?php foreach ( $output_steps as $step_key => $step ) : ?>
				<li class="<?php
				if ( $step_key === $this->step ) {
					echo 'active';
				} elseif ( array_search( $this->step, array_keys( $this->steps ), true ) > array_search( $step_key, array_keys( $this->steps ), true ) ) {
					echo 'done';
				}
				?>"><?php echo esc_html( $step['name'] ); ?></li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	public function setup_wizard_content() {
		echo '<div class="wp-verifier-setup-content">';
		call_user_func( $this->steps[ $this->step ]['view'] );
		echo '</div>';
	}

	public function step_introduction() {
		?>
		<h1><?php esc_html_e( 'Welcome to WP Verifier', 'wp-verifier' ); ?></h1>
		<p><?php esc_html_e( 'Thank you for installing WP Verifier! This wizard will help you configure the essential settings.', 'wp-verifier' ); ?></p>
		<p><?php esc_html_e( 'The setup is optional and takes less than 2 minutes. You can skip it and configure settings later.', 'wp-verifier' ); ?></p>
		<p class="wp-verifier-setup-actions step">
			<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large button-next"><?php esc_html_e( 'Let\'s Go!', 'wp-verifier' ); ?></a>
			<a href="<?php echo esc_url( admin_url() ); ?>" class="button button-large"><?php esc_html_e( 'Skip Setup', 'wp-verifier' ); ?></a>
		</p>
		<?php
	}

	public function step_ai_config() {
		$settings = get_option( 'plugin_check_settings', array() );
		$providers = require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/AI_Providers.php';
		?>
		<h1><?php esc_html_e( 'AI Configuration', 'wp-verifier' ); ?></h1>
		<p><?php esc_html_e( 'Configure AI settings for the Plugin Namer tool (optional).', 'wp-verifier' ); ?></p>
		<form method="post">
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
			<p class="wp-verifier-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'wp-verifier' ); ?>" name="save_step" />
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php esc_html_e( 'Skip', 'wp-verifier' ); ?></a>
				<?php wp_nonce_field( 'wp-verifier-setup' ); ?>
			</p>
		</form>
		<?php
	}

	public function step_ai_config_save() {
		check_admin_referer( 'wp-verifier-setup' );
		$settings                = get_option( 'plugin_check_settings', array() );
		$settings['ai_provider'] = isset( $_POST['ai_provider'] ) ? sanitize_text_field( wp_unslash( $_POST['ai_provider'] ) ) : '';
		$settings['ai_api_key']  = isset( $_POST['ai_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['ai_api_key'] ) ) : '';
		$settings['ai_model']    = isset( $_POST['ai_model'] ) ? sanitize_text_field( wp_unslash( $_POST['ai_model'] ) ) : '';
		update_option( 'plugin_check_settings', $settings );
		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	public function step_features() {
		?>
		<h1><?php esc_html_e( 'Configure Features', 'wp-verifier' ); ?></h1>
		<p><?php esc_html_e( 'Enable or disable key features.', 'wp-verifier' ); ?></p>
		<form method="post">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="enable_namer"><?php esc_html_e( 'Plugin Namer', 'wp-verifier' ); ?></label></th>
					<td>
						<input type="checkbox" id="enable_namer" name="enable_namer" value="1" checked />
						<label for="enable_namer"><?php esc_html_e( 'Enable Plugin Namer tool', 'wp-verifier' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="enable_assets"><?php esc_html_e( 'Asset Tracking', 'wp-verifier' ); ?></label></th>
					<td>
						<input type="checkbox" id="enable_assets" name="enable_assets" value="1" checked />
						<label for="enable_assets"><?php esc_html_e( 'Enable asset management system', 'wp-verifier' ); ?></label>
					</td>
				</tr>
			</table>
			<p class="wp-verifier-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'wp-verifier' ); ?>" name="save_step" />
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php esc_html_e( 'Skip', 'wp-verifier' ); ?></a>
				<?php wp_nonce_field( 'wp-verifier-setup' ); ?>
			</p>
		</form>
		<?php
	}

	public function step_features_save() {
		check_admin_referer( 'wp-verifier-setup' );
		update_option( 'wp_verifier_enable_namer', ! empty( $_POST['enable_namer'] ) ? 'yes' : 'no' );
		update_option( 'wp_verifier_enable_assets', ! empty( $_POST['enable_assets'] ) ? 'yes' : 'no' );
		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	public function step_ready() {
		update_option( 'wp_verifier_setup_complete', 'yes' );
		?>
		<h1><?php esc_html_e( 'WP Verifier is Ready!', 'wp-verifier' ); ?></h1>
		<div class="wp-verifier-setup-next-steps">
			<div class="wp-verifier-setup-next-steps-first">
				<h2><?php esc_html_e( 'Next Steps', 'wp-verifier' ); ?></h2>
				<ul>
					<li class="setup-thing"><a class="button button-primary button-large" href="<?php echo esc_url( admin_url( 'plugins.php?page=plugin-check' ) ); ?>"><?php esc_html_e( 'Verify a Plugin', 'wp-verifier' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'plugins.php?page=plugin-check&tab=namer' ) ); ?>"><?php esc_html_e( 'Try Plugin Namer', 'wp-verifier' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'plugins.php?page=plugin-check&tab=settings' ) ); ?>"><?php esc_html_e( 'Configure Settings', 'wp-verifier' ); ?></a></li>
				</ul>
			</div>
		</div>
		<?php
	}

	public function setup_wizard_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! empty( $screen->id ) && strpos( $screen->id, 'wp-verifier-setup' ) !== false ) {
			return;
		}

		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Welcome to WP Verifier!', 'wp-verifier' ); ?></strong>
				<?php esc_html_e( 'Run the setup wizard to configure essential settings.', 'wp-verifier' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?action=wp_verifier_setup' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Run Setup Wizard', 'wp-verifier' ); ?></a>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wp-verifier-hide-notice', 'setup' ), 'wp_verifier_hide_notices_nonce', '_wp_verifier_notice_nonce' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Skip Setup', 'wp-verifier' ); ?></a>
			</p>
		</div>
		<?php
	}

	public function hide_setup_notice() {
		if ( isset( $_GET['wp-verifier-hide-notice'] ) && 'setup' === $_GET['wp-verifier-hide-notice'] ) {
			if ( ! isset( $_GET['_wp_verifier_notice_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wp_verifier_notice_nonce'] ) ), 'wp_verifier_hide_notices_nonce' ) ) {
				wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'wp-verifier' ) );
			}
			update_option( 'wp_verifier_setup_complete', 'skipped' );
			wp_safe_redirect( remove_query_arg( array( 'wp-verifier-hide-notice', '_wp_verifier_notice_nonce' ) ) );
			exit;
		}
	}
}

new Setup_Wizard();
