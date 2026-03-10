<?php
/**
 * Class WordPress\Plugin_Check\Admin\Help_Tabs
 *
 * @package plugin-check
 */

namespace WordPress\Plugin_Check\Admin;

use WordPress\Plugin_Check\Checker\Check;
use WordPress\Plugin_Check\Checker\Check_Categories;
use WordPress\Plugin_Check\Checker\Check_Repository;
use WordPress\Plugin_Check\Checker\Default_Check_Repository;

/**
 * Handles admin help tabs functionality.
 *
 * @since 1.0.0
 */
final class Help_Tabs {

	/**
	 * Adds the plugin help tabs.
	 *
	 * @since 1.1.0
	 */
	public static function add_help_tabs() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$screen->add_help_tab(
			array(
				'id'       => 'wp-verifier-instructions',
				'title'    => __( 'Instructions', 'wp-verifier' ),
				'content'  => '',
				'callback' => array( __CLASS__, 'render_instructions_tab' ),
			)
		);

		$screen->add_help_tab(
			array(
				'id'       => 'wp-verifier',
				'title'    => __( 'Checks', 'wp-verifier' ),
				'content'  => '',
				'callback' => array( __CLASS__, 'render_help_tab' ),
			)
		);

		$screen->add_help_tab(
			array(
				'id'       => 'wp-verifier-setup',
				'title'    => __( 'Setup', 'wp-verifier' ),
				'content'  => '',
				'callback' => array( __CLASS__, 'render_setup_help_tab' ),
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'wp-verifier-about',
				'title'   => __( 'FifeCIC', 'wp-verifier' ),
				'content' => '<!-- FifeCIC About Tab v1.0 --><h2>' . __( 'About FifeCIC', 'wp-verifier' ) . '</h2>' .
					'<p>' . __( 'This plugins developer is supported by FifeCIC (Fife Community Interest Company), a non-profit organization dedicated to serving our local community through technology and innovation.', 'wp-verifier' ) . '</p>' .
					'<h3>' . __( 'Our Mission', 'wp-verifier' ) . '</h3>' .
					'<p>' . __( 'FifeCIC exists to empower communities through accessible digital solutions. We believe that quality software should be available to everyone, regardless of budget, and that technology can be a force for positive social change.', 'wp-verifier' ) . '</p>' .
					'<h3>' . __( 'Volunteer Development', 'wp-verifier' ) . '</h3>' .
					'<p>' . __( 'This plugin was lovingly crafted by Ryan Bayne, a volunteer developer committed to FifeCIC\'s vision. Every feature, every line of code, represents hours of unpaid dedication to making WordPress better for everyone.', 'wp-verifier' ) . '</p>' .
					'<p>' . __( 'As a Community Interest Company, we reinvest everything back into our projects and community initiatives. We don\'t have corporate backing or venture capital—just passionate people who believe in what we\'re doing.', 'wp-verifier' ) . '</p>' .
					'<h3>' . __( 'How You Can Help', 'wp-verifier' ) . '</h3>' .
					'<p>💝 <strong>' . __( 'Donate:', 'wp-verifier' ) . '</strong> ' . __( 'Your financial support helps us dedicate more time to development, hosting, and community outreach. Every contribution, no matter how small, makes a real difference.', 'wp-verifier' ) . '</p>' .
					'<p>🤝 <strong>' . __( 'Get Involved:', 'wp-verifier' ) . '</strong> ' . __( 'Whether you\'re a developer, designer, tester, or just enthusiastic about our mission, we\'d love to have you join us. Check out our GitHub repository or contact us directly.', 'wp-verifier' ) . '</p>' .
					'<p>⭐ <strong>' . __( 'Spread the Word:', 'wp-verifier' ) . '</strong> ' . __( 'Leave a review, share with colleagues, or simply tell others about FifeCIC. Community support is our lifeblood.', 'wp-verifier' ) . '</p>' .
					'<p>🐛 <strong>' . __( 'Report Issues:', 'wp-verifier' ) . '</strong> ' . __( 'Help us improve by reporting bugs and suggesting features. Your feedback shapes our roadmap.', 'wp-verifier' ) . '</p>' .
					'<h3>' . __( 'Connect With Us', 'wp-verifier' ) . '</h3>' .
					'<p><a href="#" class="button">' . __( 'Website', 'wp-verifier' ) . '</a> ' .
					'<a href="#" class="button">' . __( 'GitHub', 'wp-verifier' ) . '</a> ' .
					'<a href="#" class="button">' . __( 'Email', 'wp-verifier' ) . '</a> ' .
					'<a href="#" class="button button-primary">' . __( 'Donate', 'wp-verifier' ) . '</a></p>'
			)
		);

		$screen->add_help_tab( array(
			'id'        => 'wpseed_faq_tab',
			'title'     => __( 'FAQ', 'wpseed' ),
			'content'   => '',
			'callback'  => array( __CLASS__, 'faq' ),
		) );
	}

	/**
	 * Renders the instructions help tab.
	 *
	 * @since 1.0.0
	 */
	public static function render_instructions_tab() {
		?>
		<div class="wpv-instructions">
			<h2><?php esc_html_e( 'Step-by-Step Verification Process', 'wp-verifier' ); ?></h2>
			<p><?php esc_html_e( 'Follow these tabs in order to achieve optimal verification results:', 'wp-verifier' ); ?></p>
			
			<div class="instruction-steps">
				<div class="step-card">
					<h3><span class="step-number">1</span> <?php esc_html_e( 'Configure', 'wp-verifier' ); ?></h3>
					<p><?php esc_html_e( 'Set up your verification preferences, exclusion rules, and scanning options. This determines what files will be checked and which rules to apply.', 'wp-verifier' ); ?></p>
					<p><strong><?php esc_html_e( 'Key Actions:', 'wp-verifier' ); ?></strong> <?php esc_html_e( 'Select verification rules, configure exclusions, set scanning depth.', 'wp-verifier' ); ?></p>
				</div>
				
				<div class="step-card">
					<h3><span class="step-number">2</span> <?php esc_html_e( 'Hash Generation', 'wp-verifier' ); ?></h3>
					<p><?php esc_html_e( 'Generate file hashes for incremental scanning. This creates a baseline to detect which files have changed since the last scan.', 'wp-verifier' ); ?></p>
					<p><strong><?php esc_html_e( 'Key Actions:', 'wp-verifier' ); ?></strong> <?php esc_html_e( 'Generate initial hashes, validate hash creation, review file coverage.', 'wp-verifier' ); ?></p>
				</div>
				
				<div class="step-card">
					<h3><span class="step-number">3</span> <?php esc_html_e( 'Exclusions', 'wp-verifier' ); ?></h3>
					<p><?php esc_html_e( 'Manage files and directories to exclude from verification. This step processes your exclusion rules and creates the final scan list.', 'wp-verifier' ); ?></p>
					<p><strong><?php esc_html_e( 'Key Actions:', 'wp-verifier' ); ?></strong> <?php esc_html_e( 'Review excluded files, add new exclusions, validate exclusion patterns.', 'wp-verifier' ); ?></p>
				</div>
				
				<div class="step-card">
					<h3><span class="step-number">4</span> <?php esc_html_e( 'Readiness Check', 'wp-verifier' ); ?></h3>
					<p><?php esc_html_e( 'Verify your configuration is ready for verification. This generates a readiness score based on your current settings and file status.', 'wp-verifier' ); ?></p>
					<p><strong><?php esc_html_e( 'Key Actions:', 'wp-verifier' ); ?></strong> <?php esc_html_e( 'Review readiness score, address any issues, confirm scan parameters.', 'wp-verifier' ); ?></p>
				</div>
				
				<div class="step-card">
					<h3><span class="step-number">5</span> <?php esc_html_e( 'Advanced Verification', 'wp-verifier' ); ?></h3>
					<p><?php esc_html_e( 'Run the comprehensive verification scan. This performs the actual code analysis and generates your final results.', 'wp-verifier' ); ?></p>
					<p><strong><?php esc_html_e( 'Key Actions:', 'wp-verifier' ); ?></strong> <?php esc_html_e( 'Start verification, monitor progress, review results and recommendations.', 'wp-verifier' ); ?></p>
				</div>
			</div>
			
			<div class="instruction-tips">
				<h3><?php esc_html_e( 'Important Tips', 'wp-verifier' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Complete each step before moving to the next for best results', 'wp-verifier' ); ?></li>
					<li><?php esc_html_e( 'Use the validation features in each step to ensure proper configuration', 'wp-verifier' ); ?></li>
					<li><?php esc_html_e( 'The readiness score helps identify potential issues before running the full scan', 'wp-verifier' ); ?></li>
					<li><?php esc_html_e( 'Review exclusions carefully to avoid scanning unnecessary files', 'wp-verifier' ); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}

	public static function faq() {
		$questions = array(
			0 => __( '-- Select a question --', 'wpseed' ),
			1 => __( "Do I need to give credit to you (Ryan Bayne) if I create a plugin using the seed?", 'wpseed' ),
			2 => __( "Can I hire you (Ryan Bayne) to create a plugin for me using the seed?", 'wpseed' ),
			3 => __( "Is there support for anyone using this boilerplate to create a plugin?", 'wpseed' ),
		);  
		
		wp_add_inline_style( 'admin-footer', '.faq-answers li { background:white; padding:10px 20px; border:1px solid #cacaca; }' );
		
		?>

		<p>
			<ul id="faq-index">
				<?php foreach ( $questions as $question_index => $question ): ?>
					<li data-answer="<?php echo esc_attr($question_index); ?>"><a href="#q<?php echo esc_attr($question_index); ?>"><?php echo esc_html($question); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</p>
		
		<ul class="faq-answers">
			<li class="faq-answer" id='q1'>
				<?php esc_html_e('There are multiple developers mentioned in the documentation of this plugin. You must continue to give credit to them all. Removing credits and any reference to repositories will make it difficult for developers to maintain the plugin you create. If you want my support you must also mentioned myself and the WordPress Plugin Seed on your plugins main page.', 'wpseed');?>
			</li>
			<li class="faq-answer" id='q2'>
				<p> <?php esc_html_e('Yes, you can hire me (the plugin author) to create a plugin for you and prices vary but start very low. Technically it takes a only a few minutes to create a new plugin using my boilerplate. You can pay me a small fee to start your plugin and then make separate agreements for doing more work to it.', 'wpseed');?> </p>
			</li>

			<li class="faq-answer" id='q3'>
				<p> <?php esc_html_e('There is always some level of free support but I will expect to see some credit giving to myself and the project. Support is only offered when getting started or your plugin is already available on the WordPress.org repository. If you require support for a premium/commercial plugin project then you will have to pay a small consultation fee.', 'wpseed');?> </p>
			</li>
	 
		</ul>
			 
		<?php
		$faq_script = "
			jQuery( document).ready( function( $ ) {
				var selectedQuestion = '';

				function selectQuestion() {
					var q = $( '#' + $(this).val() );
					if ( selectedQuestion.length ) {
						selectedQuestion.hide();
					}
					q.show();
					selectedQuestion = q;
				}

				var faqAnswers = $('.faq-answer');
				var faqIndex = $('#faq-index');
				faqAnswers.hide();
				faqIndex.hide();

				var indexSelector = $('<select/>')
					.attr( 'id', 'question-selector' )
					.addClass( 'widefat' );
				var questions = faqIndex.find( 'li' );
				var advancedGroup = false;
				questions.each( function () {
					var self = $(this);
					var answer = self.data('answer');
					var text = self.text();
					var option;

					if ( answer === 39 ) {
						advancedGroup = $( '<optgroup />' )
							.attr( 'label', '" . esc_js( __( 'Advanced: This part of FAQ requires some knowledge about HTML, PHP and/or WordPress coding.', 'wpseed' ) ) . "' );

						indexSelector.append( advancedGroup );
					}

					if ( answer !== '' && text !== '' ) {
						option = $( '<option/>' )
							.val( 'q' + answer )
							.text( text );
						if ( advancedGroup ) {
							advancedGroup.append( option );
						}
						else {
							indexSelector.append( option );
						}

					}

				});

				faqIndex.after( indexSelector );
				indexSelector.before(
					$('<label />')
						.attr( 'for', 'question-selector' )
						.text( '" . esc_js( __( 'Select a question', 'wpseed' ) ) . "' )
						.addClass( 'screen-reader-text' )
				);

				indexSelector.change( selectQuestion );
			});
		";
		wp_add_inline_script( 'jquery', $faq_script );
		?>        

		<?php 
	}
	
	/**
	 * Renders the plugin help tab.
	 *
	 * @since 1.1.0
	 */
	public static function render_help_tab() {
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

	public static function render_setup_help_tab() {
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
}