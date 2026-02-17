<?php
/**
 * Class WordPress\Plugin_Check\Admin\Namer_Page
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

use WordPress\Plugin_Check\Traits\AI_Check_Names;
use WordPress\Plugin_Check\Traits\AI_Connect;
use WP_Error;

/**
 * Admin page for the Plugin Check Namer tool.
 *
 * @since 1.8.0
 */
final class Namer_Page {

	use AI_Connect;
	use AI_Check_Names;

	/**
	 * Menu slug.
	 *
	 * @since 1.8.0
	 * @var string
	 */
	const MENU_SLUG = 'plugin-check-namer';

	/**
	 * Option name used by Plugin Check settings.
	 *
	 * @since 1.8.0
	 * @var string
	 */
	const OPTION_NAME = 'plugin_check_settings';

	/**
	 * Admin-post action for analysis.
	 *
	 * @since 1.8.0
	 * @var string
	 */
	const ACTION_ANALYZE = 'plugin_check_namer_analyze';

	/**
	 * Hook suffix for the tools page.
	 *
	 * @since 1.8.0
	 * @var string
	 */
	protected $hook_suffix = '';

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 1.8.0
	 */
	public function add_hooks() {
		// Don't add separate namer page - now integrated into main page
		add_action( 'admin_post_' . self::ACTION_ANALYZE, array( $this, 'handle_analyze' ) );
		add_action( 'wp_ajax_plugin_check_namer_analyze', array( $this, 'ajax_analyze' ) );
	}

	/**
	 * Adds the tools page.
	 *
	 * @since 1.8.0
	 */
	public function add_page() {
		$this->hook_suffix = add_management_page(
			__( 'Plugin Check Namer', 'wp-verifier' ),
			__( 'Plugin Check Namer', 'wp-verifier' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueues scripts for the tools page.
	 *
	 * @since 1.8.0
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( $hook_suffix !== $this->hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'plugin-check-admin',
			plugins_url( 'assets/css/plugin-check-admin.css', WP_PLUGIN_CHECK_MAIN_FILE ),
			array(),
			WP_PLUGIN_CHECK_VERSION
		);

		wp_enqueue_script(
			'plugin-check-namer',
			plugins_url( 'assets/js/plugin-check-namer.js', WP_PLUGIN_CHECK_MAIN_FILE ),
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

	/**
	 * AJAX handler to analyze a plugin name.
	 *
	 * @since 1.8.0
	 */
	public function ajax_analyze() {
		check_ajax_referer( 'plugin_check_namer_ajax', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-verifier' ) ) );
		}

		$name = $this->get_plugin_name_from_request();
		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a plugin name.', 'wp-verifier' ) ) );
		}

		$author = $this->get_author_name_from_request();
		$check_wporg = isset( $_POST['check_wporg'] ) && $_POST['check_wporg'] === '1';
		$check_domain = isset( $_POST['check_domain'] ) && $_POST['check_domain'] === '1';

		$response = array();

		// Check WordPress.org if requested
		if ( $check_wporg ) {
			$wporg_result = $this->check_wporg_plugin( $name );
			$response['wporg'] = $wporg_result;
		}

		// Check domain if requested
		if ( $check_domain ) {
			$domain_result = $this->check_domain_availability( $name );
			$response['domain'] = $domain_result;
		}

		$ai_config = $this->get_ai_config();
		if ( is_wp_error( $ai_config ) ) {
			$response['ai_error'] = $ai_config->get_error_message();
			wp_send_json_success( $response );
			return;
		}

		$analysis = $this->run_name_analysis( $ai_config['provider'], $ai_config['api_key'], $ai_config['model'], $name, $author );
		if ( is_wp_error( $analysis ) ) {
			$response['ai_error'] = $analysis->get_error_message();
			wp_send_json_success( $response );
			return;
		}

		$parsed   = $this->parse_analysis( $analysis );
		$ai_response = $this->build_ajax_response( $parsed, $analysis, $ai_config );
		$response = array_merge( $response, $ai_response );

		wp_send_json_success( $response );
	}

	/**
	 * Check if plugin exists on WordPress.org
	 *
	 * @since 1.8.0
	 * @param string $name Plugin name
	 * @return array Result array
	 */
	private function check_wporg_plugin( $name ) {
		$slug = sanitize_title( $name );
		$api_url = 'https://api.wordpress.org/plugins/info/1.0/' . $slug . '.json';
		
		$response = wp_remote_get( $api_url, array( 'timeout' => 10 ) );
		
		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 'error',
				'message' => __( 'Could not check WordPress.org', 'wp-verifier' )
			);
		}
		
		$code = wp_remote_retrieve_response_code( $response );
		
		if ( $code === 200 ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			return array(
				'status' => 'taken',
				'message' => sprintf(
					__( 'Plugin slug "%s" is already taken on WordPress.org', 'wp-verifier' ),
					$slug
				),
				'plugin_name' => $body['name'] ?? '',
				'plugin_url' => 'https://wordpress.org/plugins/' . $slug . '/'
			);
		} else {
			return array(
				'status' => 'available',
				'message' => sprintf(
					__( 'Plugin slug "%s" appears to be available on WordPress.org', 'wp-verifier' ),
					$slug
				)
			);
		}
	}

	/**
	 * Check domain availability
	 *
	 * @since 1.8.0
	 * @param string $name Plugin name
	 * @return array Result array
	 */
	private function check_domain_availability( $name ) {
		$domain = sanitize_title( $name ) . '.com';
		
		// Simple DNS check
		$dns = @dns_get_record( $domain, DNS_A + DNS_AAAA );
		
		if ( $dns && count( $dns ) > 0 ) {
			return array(
				'status' => 'taken',
				'message' => sprintf(
					__( 'Domain %s appears to be registered', 'wp-verifier' ),
					$domain
				),
				'domain' => $domain
			);
		} else {
			return array(
				'status' => 'available',
				'message' => sprintf(
					__( 'Domain %s may be available (DNS check)', 'wp-verifier' ),
					$domain
				),
				'domain' => $domain,
				'note' => __( 'Note: This is a basic DNS check. Verify availability with a domain registrar.', 'wp-verifier' )
			);
		}
	}

	/**
	 * Builds AJAX response from parsed analysis.
	 *
	 * @since 1.8.0
	 *
	 * @param array        $parsed    Parsed analysis.
	 * @param string|array $analysis  Raw analysis.
	 * @param array        $ai_config AI configuration with provider and model info.
	 * @return array Response array.
	 */
	protected function build_ajax_response( $parsed, $analysis, $ai_config = array() ) {
		$raw_output = $this->get_raw_output( $parsed, $analysis );
		$raw_output = $this->format_json_output( $raw_output );

		$response = array(
			'verdict'     => $parsed['verdict'],
			'explanation' => $parsed['explanation'],
			'raw'         => $raw_output,
		);

		if ( ! empty( $parsed['confusion_existing_plugins'] ) ) {
			$response['confusion_existing_plugins'] = $parsed['confusion_existing_plugins'];
		}
		if ( ! empty( $parsed['confusion_existing_others'] ) ) {
			$response['confusion_existing_others'] = $parsed['confusion_existing_others'];
		}
		if ( ! empty( $parsed['token_usage'] ) ) {
			$response['token_usage'] = $parsed['token_usage'];
		}

		// Add AI model and provider information.
		if ( ! empty( $ai_config ) ) {
			$response['ai_info'] = array(
				'provider' => $ai_config['provider'],
				'model'    => $ai_config['model'],
			);
		}

		return $response;
	}

	/**
	 * Gets raw output from parsed or analysis data.
	 *
	 * @since 1.8.0
	 *
	 * @param array        $parsed   Parsed analysis.
	 * @param string|array $analysis Raw analysis.
	 * @return string Raw output.
	 */
	protected function get_raw_output( $parsed, $analysis ) {
		if ( ! empty( $parsed['raw'] ) ) {
			return $parsed['raw'];
		}

		if ( is_array( $analysis ) && isset( $analysis['text'] ) ) {
			return $analysis['text'];
		}

		if ( is_string( $analysis ) ) {
			return $analysis;
		}

		return '';
	}

	/**
	 * Gets plugin name from request.
	 *
	 * @since 1.8.0
	 *
	 * @return string Plugin name or empty string.
	 */
	protected function get_plugin_name_from_request() {
		$name = isset( $_POST['plugin_name'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_name'] ) ) : '';
		return trim( $name );
	}

	/**
	 * Gets author name from request.
	 *
	 * @since 1.8.0
	 *
	 * @return string Author name or empty string.
	 */
	protected function get_author_name_from_request() {
		$author = isset( $_POST['author_name'] ) ? sanitize_text_field( wp_unslash( $_POST['author_name'] ) ) : '';
		return trim( $author );
	}

	/**
	 * Gets AI configuration from settings.
	 *
	 * @since 1.8.0
	 *
	 * @return array|WP_Error AI config array or error.
	 */
	protected function get_ai_config() {
		$settings = get_option( self::OPTION_NAME, array() );
		$provider = isset( $settings['ai_provider'] ) ? (string) $settings['ai_provider'] : '';
		$api_key  = isset( $settings['ai_api_key'] ) ? (string) $settings['ai_api_key'] : '';
		$model    = isset( $settings['ai_model'] ) ? (string) $settings['ai_model'] : '';

		if ( empty( $provider ) || empty( $api_key ) || empty( $model ) ) {
			return new WP_Error(
				'missing_ai_config',
				__( 'AI settings are not configured. Please configure Provider, API key, and Model in Plugin Check settings first.', 'wp-verifier' )
			);
		}

		return array(
			'provider' => $provider,
			'api_key'  => $api_key,
			'model'    => $model,
		);
	}

	/**
	 * Renders the page.
	 *
	 * @since 1.8.0
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get current tab
		$current_tab = isset( $_GET['tab'] ) ? sanitize_title( wp_unslash( $_GET['tab'] ) ) : 'evaluate';

		// Load tabs class
		if ( ! class_exists( 'WordPress\\Plugin_Check\\Admin\\Namer_Page_Tabs' ) ) {
			require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Namer_Page_Tabs.php';
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Plugin Check Namer Tool', 'wp-verifier' ); ?></h1>
			<?php Namer_Page_Tabs::render_tabs(); ?>

			<?php
			switch ( $current_tab ) {
				case 'evaluate':
					$this->render_evaluate_tab();
					break;
				case 'analytics':
				case 'saved':
					Namer_Page_Tabs::render_coming_soon();
					break;
				default:
					$this->render_evaluate_tab();
					break;
			}
			?>
		</div>
		<?php
	}

	/**
	 * Renders the evaluate tab content.
	 *
	 * @since 1.8.0
	 */
	public function render_evaluate_tab() {
		?>
		<form id="plugin-check-namer-form" method="post">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
							<th scope="row">
								<label for="plugin_check_namer_input"><?php echo esc_html__( 'Plugin name', 'wp-verifier' ); ?></label>
							</th>
							<td>
								<input
									type="text"
									id="plugin_check_namer_input"
									name="plugin_check_namer_input"
									class="large-text"
									value=""
									required
								/>
								<p class="description">
									<?php echo esc_html__( 'Enter the plugin name you want to evaluate.', 'wp-verifier' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="plugin_check_namer_author"><?php echo esc_html__( 'Author name', 'wp-verifier' ); ?></label>
							</th>
							<td>
								<input
									type="text"
									id="plugin_check_namer_author"
									name="plugin_check_namer_author"
									class="regular-text"
									value=""
								/>
								<p class="description">
									<?php echo esc_html__( 'Optional: Enter the author or brand name if you own the trademark.', 'wp-verifier' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Additional Checks', 'wp-verifier' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="checkbox" name="check_wporg" id="check_wporg" value="1" checked />
										<?php echo esc_html__( 'Check WordPress.org plugin directory', 'wp-verifier' ); ?>
									</label>
									<br>
									<label>
										<input type="checkbox" name="check_domain" id="check_domain" value="1" />
										<?php echo esc_html__( 'Check domain availability (.com)', 'wp-verifier' ); ?>
									</label>
									<p class="description">
										<?php echo esc_html__( 'Perform additional availability checks for the plugin name.', 'wp-verifier' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="description">
					<strong><?php echo esc_html__( 'Note:', 'wp-verifier' ); ?></strong> 
					<br/>
					<?php echo esc_html__( 'This tool provides guidance only and is not definitive. It contains a prompt that is used to evaluate the similarity of a plugin name to other plugin names and ensure compliance with trademark regulations.', 'wp-verifier' ); ?>
					<br/>
					<?php echo esc_html__( 'This analysis performs two AI checks for similarity and trademark conflicts, which may take a moment to complete.', 'wp-verifier' ); ?>
				</p>
				<p class="submit">
					<button type="submit" class="button button-primary" id="plugin-check-namer-submit"><?php echo esc_html__( 'Evaluate name', 'wp-verifier' ); ?></button>
					<span class="spinner plugin-check-namer-spinner" id="plugin-check-namer-spinner"></span>
				</p>
			</form>

			<div id="plugin-check-namer-error" class="notice notice-error plugin-check-namer-hidden"><p></p></div>

			<div id="plugin-check-namer-result" class="plugin-check-namer-hidden">
				<h2><?php echo esc_html__( 'Result', 'wp-verifier' ); ?></h2>
				
				<div id="plugin-check-namer-wporg-result" class="plugin-check-namer-check-result plugin-check-namer-hidden">
					<h3><?php echo esc_html__( 'WordPress.org Plugin Directory', 'wp-verifier' ); ?></h3>
					<p id="plugin-check-namer-wporg-status"></p>
				</div>

				<div id="plugin-check-namer-domain-result" class="plugin-check-namer-check-result plugin-check-namer-hidden">
					<h3><?php echo esc_html__( 'Domain Availability', 'wp-verifier' ); ?></h3>
					<p id="plugin-check-namer-domain-status"></p>
				</div>

				<div id="plugin-check-namer-verdict-container" class="plugin-check-namer-verdict-container plugin-check-namer-hidden">
					<p class="plugin-check-namer-verdict-item">
						<strong><?php echo esc_html__( 'Verdict:', 'wp-verifier' ); ?></strong>
						<span id="plugin-check-namer-verdict"></span>
					</p>
					<p class="plugin-check-namer-verdict-item">
						<strong><?php echo esc_html__( 'Explanation:', 'wp-verifier' ); ?></strong>
						<span id="plugin-check-namer-explanation"></span>
					</p>
				<p id="plugin-check-namer-timing" class="plugin-check-namer-meta plugin-check-namer-hidden">
					<strong><?php echo esc_html__( 'Analysis completed in:', 'wp-verifier' ); ?></strong>
					<span id="plugin-check-namer-timing-value"></span>
				</p>
				<p id="plugin-check-namer-tokens" class="plugin-check-namer-meta plugin-check-namer-hidden">
					<strong><?php echo esc_html__( 'Tokens used:', 'wp-verifier' ); ?></strong>
					<span id="plugin-check-namer-tokens-value"></span>
				</p>
			</div>
				<div id="plugin-check-namer-confusion-plugins" class="plugin-check-namer-confusion plugin-check-namer-hidden">
					<p><strong><?php echo esc_html__( 'Similar Existing Plugins', 'wp-verifier' ); ?></strong></p>
					<div id="plugin-check-namer-confusion-plugins-list"></div>
				</div>

				<div id="plugin-check-namer-confusion-others" class="plugin-check-namer-confusion plugin-check-namer-hidden">
					<h3><?php echo esc_html__( 'Similar Existing Projects/Trademarks', 'wp-verifier' ); ?></h3>
					<div id="plugin-check-namer-confusion-others-list"></div>
				</div>
			</div>
		<?php
	}

	/**
	 * Handles the analysis form submission.
	 *
	 * @since 1.8.0
	 */
	public function handle_analyze() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-verifier' ) );
		}

		check_admin_referer( 'plugin_check_namer_analyze', 'plugin_check_namer_nonce' );

		$input = isset( $_POST['plugin_check_namer_input'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_check_namer_input'] ) ) : '';
		$input = trim( $input );

		$author = isset( $_POST['plugin_check_namer_author'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_check_namer_author'] ) ) : '';
		$author = trim( $author );

		$user_id = get_current_user_id();

		if ( empty( $input ) ) {
			$this->handle_analyze_error( $user_id, '', new WP_Error( 'missing_input', __( 'Please enter a plugin name.', 'wp-verifier' ) ) );
			return;
		}

		$ai_config = $this->get_ai_config();
		if ( is_wp_error( $ai_config ) ) {
			$this->handle_analyze_error( $user_id, $input, $ai_config );
			return;
		}

		$analysis = $this->run_name_analysis( $ai_config['provider'], $ai_config['api_key'], $ai_config['model'], $input, $author );

		if ( is_wp_error( $analysis ) ) {
			$this->handle_analyze_error( $user_id, $input, $analysis );
			return;
		}

		$this->store_result(
			$user_id,
			array(
				'input'    => $input,
				'analysis' => $analysis,
			)
		);
		wp_safe_redirect( $this->get_page_url() );
		exit;
	}

	/**
	 * Handles analyze error and redirects.
	 *
	 * @since 1.8.0
	 *
	 * @param int      $user_id User ID.
	 * @param string   $input   Input value.
	 * @param WP_Error $error   Error object.
	 */
	protected function handle_analyze_error( $user_id, $input, $error ) {
		$this->store_result(
			$user_id,
			array(
				'input' => $input,
				'error' => $error,
			)
		);
		wp_safe_redirect( $this->get_page_url() );
		exit;
	}

	/**
	 * Gets the page URL.
	 *
	 * @since 1.8.0
	 *
	 * @return string
	 */
	protected function get_page_url() {
		return add_query_arg( array( 'page' => self::MENU_SLUG ), admin_url( 'tools.php' ) );
	}

	/**
	 * Formats JSON output with proper indentation if the text is valid JSON.
	 *
	 * @since 1.8.0
	 *
	 * @param string $text Text that might be JSON.
	 * @return string Formatted JSON or original text.
	 */
	protected function format_json_output( $text ) {
		if ( empty( $text ) || ! is_string( $text ) ) {
			return $text;
		}

		$trimmed = $this->remove_markdown_fences( trim( $text ) );

		if ( ! $this->looks_like_json( $trimmed ) ) {
			return $text;
		}

		$json_text = $this->extract_json_text( $trimmed );
		$decoded   = json_decode( $json_text, true );

		if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
			return wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		}

		return $text;
	}

	/**
	 * Removes markdown code fences from text.
	 *
	 * @since 1.8.0
	 *
	 * @param string $text Text with possible markdown fences.
	 * @return string Text without markdown fences.
	 */
	protected function remove_markdown_fences( $text ) {
		$text = preg_replace( '/^```(?:json)?\s*\n?/m', '', $text );
		$text = preg_replace( '/\n?```\s*$/m', '', $text );
		return trim( $text );
	}

	/**
	 * Checks if text looks like JSON.
	 *
	 * @since 1.8.0
	 *
	 * @param string $text Text to check.
	 * @return bool True if looks like JSON.
	 */
	protected function looks_like_json( $text ) {
		return ! empty( $text ) && ( '{' === $text[0] || '[' === $text[0] );
	}

	/**
	 * Extracts JSON text from mixed content.
	 *
	 * @since 1.8.0
	 *
	 * @param string $text Text containing JSON.
	 * @return string Extracted JSON text.
	 */
	protected function extract_json_text( $text ) {
		$bounds = $this->find_json_bounds( $text );

		if ( -1 !== $bounds['start'] && -1 !== $bounds['end'] && $bounds['end'] > $bounds['start'] ) {
			return substr( $text, $bounds['start'], $bounds['end'] - $bounds['start'] + 1 );
		}

		return $text;
	}

	/**
	 * Finds JSON boundaries in text.
	 *
	 * @since 1.8.0
	 *
	 * @param string $text Text to search.
	 * @return array Array with 'start' and 'end' positions.
	 */
	protected function find_json_bounds( $text ) {
		$first_brace   = strpos( $text, '{' );
		$first_bracket = strpos( $text, '[' );

		if ( false !== $first_brace && ( false === $first_bracket || $first_brace < $first_bracket ) ) {
			return array(
				'start' => $first_brace,
				'end'   => strrpos( $text, '}' ),
			);
		}

		if ( false !== $first_bracket ) {
			return array(
				'start' => $first_bracket,
				'end'   => strrpos( $text, ']' ),
			);
		}

		return array(
			'start' => -1,
			'end'   => -1,
		);
	}
}
