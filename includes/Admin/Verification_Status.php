<?php
/**
 * Verification Status Display for admin interface
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Admin;

use WordPress\Plugin_Check\Verification\Hash_Generator;
use WordPress\Plugin_Check\Verification\JSON_Storage;
use WordPress\Plugin_Check\Verification\Verification_Matcher;

/**
 * Displays verification status in admin interface
 */
class Verification_Status {

	/**
	 * Add verification status to settings page
	 */
	public static function add_verification_section() {
		add_settings_section(
			'verification_status_section',
			__( 'Hash Tracking System Status', 'wpverifier' ),
			array( __CLASS__, 'render_verification_section' ),
			'plugin-check-settings'
		);
	}

	/**
	 * Render verification status section
	 */
	public static function render_verification_section() {
		try {
			$hash_generator = new Hash_Generator();
			$storage = new JSON_Storage();
			$matcher = new Verification_Matcher();

			// Test with Settings_Page.php
			$test_file = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Settings_Page.php';
			$file_hash = $hash_generator->generate_file_hash( $test_file );
			$function_hash = $hash_generator->generate_function_hash( $test_file, 'Settings_Page::add_hooks' );
			
			$verification_data = $storage->load_verification_data();
			$total_files = count( $verification_data['file_level'] );
			$total_functions = count( $verification_data['function_level'] );

			?>
			<div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
				<h4><?php esc_html_e( 'System Status', 'wpverifier' ); ?></h4>
				<table class="form-table" style="margin: 0;">
					<tr>
						<th scope="row"><?php esc_html_e( 'Hash Generation', 'wpverifier' ); ?></th>
						<td>
							<span class="dashicons dashicons-<?php echo $file_hash ? 'yes-alt' : 'dismiss'; ?>" style="color: <?php echo $file_hash ? '#46b450' : '#dc3232'; ?>;"></span>
							<?php echo $file_hash ? esc_html__( 'Working', 'wpverifier' ) : esc_html__( 'Failed', 'wpverifier' ); ?>
							<?php if ( $file_hash ) : ?>
								<code style="margin-left: 10px;"><?php echo esc_html( $file_hash ); ?></code>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Function Tracking', 'wpverifier' ); ?></th>
						<td>
							<span class="dashicons dashicons-<?php echo $function_hash ? 'yes-alt' : 'dismiss'; ?>" style="color: <?php echo $function_hash ? '#46b450' : '#dc3232'; ?>;"></span>
							<?php echo $function_hash ? esc_html__( 'Working', 'wpverifier' ) : esc_html__( 'Failed', 'wpverifier' ); ?>
							<?php if ( $function_hash ) : ?>
								<code style="margin-left: 10px;"><?php echo esc_html( $function_hash ); ?></code>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Verification Storage', 'wpverifier' ); ?></th>
						<td>
							<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
							<?php esc_html_e( 'Ready', 'wpverifier' ); ?>
							<span style="margin-left: 10px; color: #666;">
								<?php
								printf(
									/* translators: %1$d: number of verified files, %2$d: number of verified functions */
									esc_html__( '%1$d files, %2$d functions tracked', 'wpverifier' ),
									$total_files,
									$total_functions
								);
								?>
							</span>
						</td>
					</tr>
				</table>
				
				<?php if ( $file_hash && $function_hash ) : ?>
					<div style="margin-top: 15px; padding: 10px; background: #e7f7e7; border-left: 4px solid #46b450;">
						<strong><?php esc_html_e( 'Hash Tracking System is operational!', 'wpverifier' ); ?></strong>
						<p style="margin: 5px 0 0 0; font-size: 13px;">
							<?php esc_html_e( 'The system can now track file and function-level changes for intelligent verification status.', 'wpverifier' ); ?>
						</p>
					</div>
				<?php endif; ?>
			</div>
			<?php

		} catch ( Exception $e ) {
			?>
			<div style="background: #ffeaea; padding: 15px; border: 1px solid #dc3232; border-radius: 4px;">
				<strong><?php esc_html_e( 'Hash Tracking System Error', 'wpverifier' ); ?></strong>
				<p><?php echo esc_html( $e->getMessage() ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Test verification system with sample data
	 */
	public static function test_verification_system() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Mock WordPress functions if not available
		if ( ! function_exists( 'current_time' ) ) {
			function current_time( $format ) {
				return gmdate( $format );
			}
		}
		
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			function wp_get_current_user() {
				return (object) array( 'ID' => 1 );
			}
		}

		try {
			$storage = new JSON_Storage();
			$hash_generator = new Hash_Generator();
			
			// Test file
			$test_file = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Settings_Page.php';
			$function_name = 'Settings_Page::add_hooks';
			
			// Generate hashes
			$file_hash = $hash_generator->generate_file_hash( $test_file );
			$function_hash = $hash_generator->generate_function_hash( $test_file, $function_name );
			
			if ( $function_hash ) {
				// Mark a test issue as verified
				$test_issues = array(
					array(
						'line' => 50,
						'type' => 'WordPress.Security.NonceVerification.Recommended',
					),
				);
				
				$result = $storage->mark_function_verified(
					$function_name,
					'includes/Admin/Settings_Page.php',
					$function_hash,
					$test_issues,
					'Initial test verification - system is working'
				);
				
				if ( $result ) {
					add_action( 'admin_notices', function() {
						?>
						<div class="notice notice-success is-dismissible">
							<p><?php esc_html_e( 'Hash tracking system test completed successfully! Check the verification status below.', 'wpverifier' ); ?></p>
						</div>
						<?php
					});
				}
			}
			
		} catch ( Exception $e ) {
			add_action( 'admin_notices', function() use ( $e ) {
				?>
				<div class="notice notice-error is-dismissible">
					<p><?php printf( esc_html__( 'Hash tracking system test failed: %s', 'wpverifier' ), esc_html( $e->getMessage() ) ); ?></p>
				</div>
				<?php
			});
		}
	}
}