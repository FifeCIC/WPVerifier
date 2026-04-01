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
			'id'        => 'wpverifier_faq_tab',
			'title'     => __( 'FAQ', 'wpverifier' ),
			'content'   => '',
			'callback'  => array( __CLASS__, 'faq' ),
		) );

		$screen->add_help_tab( array(
			'id'       => 'wp-verifier-performance',
			'title'    => __( 'Performance', 'wp-verifier' ),
			'content'  => '',
			'callback' => array( __CLASS__, 'render_performance_tab' ),
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
			0 => __( '-- Select a question --', 'wpverifier' ),
			1 => __( "Do I need to give credit to you (Ryan Bayne) if I create a plugin using the seed?", 'wpverifier' ),
			2 => __( "Can I hire you (Ryan Bayne) to create a plugin for me using the seed?", 'wpverifier' ),
			3 => __( "Is there support for anyone using this boilerplate to create a plugin?", 'wpverifier' ),
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
				<?php esc_html_e('There are multiple developers mentioned in the documentation of this plugin. You must continue to give credit to them all. Removing credits and any reference to repositories will make it difficult for developers to maintain the plugin you create. If you want my support you must also mentioned myself and the WordPress Plugin Seed on your plugins main page.', 'wpverifier');?>
			</li>
			<li class="faq-answer" id='q2'>
				<p> <?php esc_html_e('Yes, you can hire me (the plugin author) to create a plugin for you and prices vary but start very low. Technically it takes a only a few minutes to create a new plugin using my boilerplate. You can pay me a small fee to start your plugin and then make separate agreements for doing more work to it.', 'wpverifier');?> </p>
			</li>

			<li class="faq-answer" id='q3'>
				<p> <?php esc_html_e('There is always some level of free support but I will expect to see some credit giving to myself and the project. Support is only offered when getting started or your plugin is already available on the WordPress.org repository. If you require support for a premium/commercial plugin project then you will have to pay a small consultation fee.', 'wpverifier');?> </p>
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
							.attr( 'label', '" . esc_js( __( 'Advanced: This part of FAQ requires some knowledge about HTML, PHP and/or WordPress coding.', 'wpverifier' ) ) . "' );

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
						.text( '" . esc_js( __( 'Select a question', 'wpverifier' ) ) . "' )
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

	/**
	 * Renders the performance help tab.
	 *
	 * Explains the scan architecture, the bottlenecks that were identified
	 * and resolved, and the design decisions behind the batched processing
	 * approach.
	 *
	 * @since 1.1.0
	 */
	public static function render_performance_tab() {
		?>
		<div class="wpv-performance">
			<h2><?php esc_html_e( 'Scan Performance Architecture', 'wp-verifier' ); ?></h2>
			<p><?php esc_html_e( 'WP Verifier runs PHP CodeSniffer (PHPCS) under the hood to analyse your plugin\'s source code against WordPress coding standards. PHPCS is a powerful but resource-intensive tool — scanning a plugin with hundreds of PHP files can take minutes. This page explains how WP Verifier optimises that process and why certain design decisions were made.', 'wp-verifier' ); ?></p>

			<h3><?php esc_html_e( 'The Problem: Monolithic Scanning', 'wp-verifier' ); ?></h3>
			<p><?php esc_html_e( 'The simplest way to run PHPCS is to point it at an entire plugin directory and let it process every file in a single invocation. While straightforward, this approach has significant drawbacks:', 'wp-verifier' ); ?></p>
			<ul>
				<li><?php esc_html_e( 'The scan is a single blocking operation — it cannot be interrupted or stopped early, even if you only need a subset of results.', 'wp-verifier' ); ?></li>
				<li><?php esc_html_e( 'Memory usage grows continuously as PHPCS accumulates results for every file.', 'wp-verifier' ); ?></li>
				<li><?php esc_html_e( 'There is no opportunity to report progress or terminate when enough issues have been found.', 'wp-verifier' ); ?></li>
			</ul>
			<p><?php esc_html_e( 'In testing, a monolithic scan of a plugin producing approximately 1,500 issues took around 2 minutes 40 seconds. This was the baseline we set out to improve.', 'wp-verifier' ); ?></p>

			<h3><?php esc_html_e( 'The Solution: Batched Processing', 'wp-verifier' ); ?></h3>
			<p><?php esc_html_e( 'Instead of passing the entire plugin directory to PHPCS, WP Verifier expands the directory into individual PHP files and processes them in small batches (currently 20 files per batch). Each batch is a separate PHPCS invocation with its own setup and teardown cycle.', 'wp-verifier' ); ?></p>
			<p><?php esc_html_e( 'This approach provides two key benefits:', 'wp-verifier' ); ?></p>
			<ul>
				<li><strong><?php esc_html_e( 'Early termination:', 'wp-verifier' ); ?></strong> <?php esc_html_e( 'When an issue limit is set (e.g. 250 or 500 issues), the scan stops at the next batch boundary once the limit is reached. A 250-issue limited scan completes in roughly 8 seconds compared to 2 minutes 40 seconds for a full scan.', 'wp-verifier' ); ?></li>
				<li><strong><?php esc_html_e( 'Reduced memory pressure:', 'wp-verifier' ); ?></strong> <?php esc_html_e( 'Each batch processes a smaller set of files, keeping PHPCS\'s internal memory footprint manageable. This avoids the compounding overhead that occurs when PHPCS processes hundreds of files in a single pass.', 'wp-verifier' ); ?></li>
			</ul>

			<h3><?php esc_html_e( 'Why Batches of 20?', 'wp-verifier' ); ?></h3>
			<p><?php esc_html_e( 'Each PHPCS invocation carries a fixed overhead: command-line argument setup, configuration reset via PHP Reflection, coding standard loading, and JSON output parsing. Processing files one at a time would mean paying this overhead for every single file — with 100+ files, that adds up quickly.', 'wp-verifier' ); ?></p>
			<p><?php esc_html_e( 'Batching 20 files per invocation strikes a balance: the per-invocation overhead is amortised across 20 files (reducing total overhead by roughly 20×), while still allowing the scan to stop reasonably close to the issue limit rather than overshooting by hundreds of issues.', 'wp-verifier' ); ?></p>

			<h3><?php esc_html_e( 'Bottlenecks Identified and Resolved', 'wp-verifier' ); ?></h3>
			<p><?php esc_html_e( 'During performance profiling, five specific bottlenecks were identified in the scan pipeline. Each was addressed with a targeted fix:', 'wp-verifier' ); ?></p>

			<table class="widefat striped" style="margin-top: 10px;">
				<thead>
					<tr>
						<th style="width: 10%;"><?php esc_html_e( '#', 'wp-verifier' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'Severity', 'wp-verifier' ); ?></th>
						<th style="width: 30%;"><?php esc_html_e( 'Problem', 'wp-verifier' ); ?></th>
						<th style="width: 45%;"><?php esc_html_e( 'Fix', 'wp-verifier' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>1</td>
						<td><span style="color: #dc3232; font-weight: bold;"><?php esc_html_e( 'Critical', 'wp-verifier' ); ?></span></td>
						<td><?php esc_html_e( 'Issue counting traversed the entire results array (O(n²) complexity) after every file was processed. With 1,000 accumulated issues and 100 files, this meant approximately 200,000 iterations just for counting.', 'wp-verifier' ); ?></td>
						<td><?php esc_html_e( 'Replaced with O(1) counters already maintained internally by the results object. Each call to get the count is now a simple integer read instead of a full array traversal.', 'wp-verifier' ); ?></td>
					</tr>
					<tr>
						<td>2</td>
						<td><span style="color: #dc3232; font-weight: bold;"><?php esc_html_e( 'Critical', 'wp-verifier' ); ?></span></td>
						<td><?php esc_html_e( 'The same O(n²) counting pattern existed in the check orchestration layer, which counted issues before and after each check type ran.', 'wp-verifier' ); ?></td>
						<td><?php esc_html_e( 'Same fix — replaced with O(1) counter reads. The redundant "before" count was also removed as it was computed but never used.', 'wp-verifier' ); ?></td>
					</tr>
					<tr>
						<td>3</td>
						<td><span style="color: #ffb900; font-weight: bold;"><?php esc_html_e( 'Medium', 'wp-verifier' ); ?></span></td>
						<td><?php esc_html_e( 'PHPCS was invoked once per individual file, meaning the full bootstrap cycle (configuration reset, standard loading, runner instantiation) ran for every single PHP file in the plugin.', 'wp-verifier' ); ?></td>
						<td><?php esc_html_e( 'Files are now grouped into batches of 20 per PHPCS invocation, reducing bootstrap overhead by approximately 20×.', 'wp-verifier' ); ?></td>
					</tr>
					<tr>
						<td>4</td>
						<td><?php esc_html_e( 'Low', 'wp-verifier' ); ?></td>
						<td><?php esc_html_e( 'A hash generator object was instantiated inside the file results loop, creating a new object for every file that had issues.', 'wp-verifier' ); ?></td>
						<td><?php esc_html_e( 'Moved instantiation outside the loop so a single object is reused across all files.', 'wp-verifier' ); ?></td>
					</tr>
					<tr>
						<td>5</td>
						<td><?php esc_html_e( 'Low', 'wp-verifier' ); ?></td>
						<td><?php esc_html_e( 'A variable capturing the issue count before each file was computed using the expensive O(n²) traversal but was never actually referenced anywhere in the code.', 'wp-verifier' ); ?></td>
						<td><?php esc_html_e( 'Removed the dead code entirely.', 'wp-verifier' ); ?></td>
					</tr>
				</tbody>
			</table>

			<h3><?php esc_html_e( 'The Issue Limit Feature', 'wp-verifier' ); ?></h3>
			<p><?php esc_html_e( 'The "Limit Results" radio buttons on the verification page (250, 500, or no limit) control how many issues the scan will collect before stopping. This is not simply truncating the display — the scan genuinely stops processing files once the limit is reached.', 'wp-verifier' ); ?></p>
			<p><?php esc_html_e( 'This is particularly useful during active development: if your plugin has hundreds of issues, you rarely need to see all of them at once. Setting a limit of 250 lets you work through the most pressing issues first, then run another scan to surface the next batch. The performance difference is dramatic — a limited scan can complete in seconds rather than minutes.', 'wp-verifier' ); ?></p>

			<h3><?php esc_html_e( 'Observed Performance', 'wp-verifier' ); ?></h3>
			<table class="widefat striped" style="margin-top: 10px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Scan Mode', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Issues Found', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Time (Before Optimisation)', 'wp-verifier' ); ?></th>
						<th><?php esc_html_e( 'Time (After Optimisation)', 'wp-verifier' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php esc_html_e( '250 issue limit', 'wp-verifier' ); ?></td>
						<td>~257</td>
						<td><?php esc_html_e( '~8 seconds', 'wp-verifier' ); ?></td>
						<td><?php esc_html_e( '~8 seconds', 'wp-verifier' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( '500 issue limit', 'wp-verifier' ); ?></td>
						<td>~508</td>
						<td><?php esc_html_e( '~17 seconds', 'wp-verifier' ); ?></td>
						<td><?php esc_html_e( '~17 seconds', 'wp-verifier' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'No limit (all issues)', 'wp-verifier' ); ?></td>
						<td>~1,577</td>
						<td><?php esc_html_e( '~2 min 40 sec (monolithic)', 'wp-verifier' ); ?></td>
						<td><?php esc_html_e( 'Significantly reduced (batched)', 'wp-verifier' ); ?></td>
					</tr>
				</tbody>
			</table>
			<p><em><?php esc_html_e( 'Benchmarks are from a real plugin with approximately 100 PHP files. Actual times will vary depending on plugin size, server hardware, and PHP version.', 'wp-verifier' ); ?></em></p>

			<h3><?php esc_html_e( 'Technical Summary', 'wp-verifier' ); ?></h3>
			<p><?php esc_html_e( 'The scan pipeline now works as follows:', 'wp-verifier' ); ?></p>
			<ol>
				<li><?php esc_html_e( 'The plugin directory is expanded into a list of individual PHP files, respecting all ignore patterns (vendor directories, config-based exclusions, file-level ignores with hash validation).', 'wp-verifier' ); ?></li>
				<li><?php esc_html_e( 'Files are grouped into batches of 20.', 'wp-verifier' ); ?></li>
				<li><?php esc_html_e( 'Each batch is passed to PHPCS as a single invocation. Results are parsed and added to the running total using O(1) counters.', 'wp-verifier' ); ?></li>
				<li><?php esc_html_e( 'If an issue limit is set, the count is checked at each batch boundary. Once the limit is reached, remaining batches are skipped.', 'wp-verifier' ); ?></li>
				<li><?php esc_html_e( 'If no limit is set, all batches are processed — but the batched approach still outperforms the monolithic approach due to reduced memory pressure and more efficient PHPCS internal handling.', 'wp-verifier' ); ?></li>
			</ol>
		</div>
		<?php
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