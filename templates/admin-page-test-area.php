<?php
/**
 * Template for Test Area tab.
 *
 * @package wp-verifier
 */

if ( ! class_exists( 'WordPress\\Plugin_Check\\Verification\\Hash_Generator' ) ) {
	require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Verification/Hash_Generator.php';
}

use WordPress\Plugin_Check\Verification\Hash_Generator;

$test_results = array();

// Test 1: File Hash Generation
$test_file = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/helper-functions.php';
$hash_generator = new Hash_Generator();
$file_hash = $hash_generator->generate_file_hash( $test_file );
$test_results[] = array(
	'name' => 'File Hash Generation',
	'status' => $file_hash !== false ? 'pass' : 'fail',
	'result' => $file_hash ? $file_hash : 'Failed to generate hash',
	'details' => 'Testing: ' . basename( $test_file ),
);

// Test 2: Function Hash Generation
$function_hash = $hash_generator->generate_function_hash( $test_file, 'wpverifier_header' );
$test_results[] = array(
	'name' => 'Function Hash Generation',
	'status' => $function_hash !== false ? 'pass' : 'fail',
	'result' => $function_hash ? $function_hash : 'Failed to generate hash',
	'details' => 'Testing: wpverifier_header() function',
);

// Test 3: Invalid File
$invalid_hash = $hash_generator->generate_file_hash( '/nonexistent/file.php' );
$test_results[] = array(
	'name' => 'Invalid File Handling',
	'status' => $invalid_hash === false ? 'pass' : 'fail',
	'result' => $invalid_hash === false ? 'Correctly returned false' : 'Should have returned false',
	'details' => 'Testing: Non-existent file',
);

// Test 4: Hash Consistency
$hash1 = $hash_generator->generate_file_hash( $test_file );
$hash2 = $hash_generator->generate_file_hash( $test_file );
$test_results[] = array(
	'name' => 'Hash Consistency',
	'status' => $hash1 === $hash2 ? 'pass' : 'fail',
	'result' => $hash1 === $hash2 ? 'Hashes match' : 'Hashes do not match',
	'details' => sprintf( 'Hash 1: %s, Hash 2: %s', $hash1, $hash2 ),
);

// Test 5: Plugin File Hash Generation (for next step)
$plugin_file = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Settings_Page.php';
$plugin_file_hash = $hash_generator->generate_file_hash( $plugin_file );
$plugin_function_hash = $hash_generator->generate_function_hash( $plugin_file, 'Settings_Page::add_hooks' );
$test_results[] = array(
	'name' => 'Plugin File Hash Ready',
	'status' => ($plugin_file_hash && $plugin_function_hash) ? 'pass' : 'fail',
	'result' => $plugin_file_hash ? "File: {$plugin_file_hash}, Function: {$plugin_function_hash}" : 'Failed',
	'details' => 'Ready for verification integration',
);

?>

<div class="wrap">
	<h2><?php esc_html_e( 'Test Area - Hash Generator Tests', 'wp-verifier' ); ?></h2>
	
	<div style="margin: 20px 0;">
		<p><?php esc_html_e( 'Testing the Hash_Generator class for verification tracking system.', 'wp-verifier' ); ?></p>
	</div>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width: 30%;"><?php esc_html_e( 'Test Name', 'wp-verifier' ); ?></th>
				<th style="width: 15%;"><?php esc_html_e( 'Status', 'wp-verifier' ); ?></th>
				<th style="width: 25%;"><?php esc_html_e( 'Result', 'wp-verifier' ); ?></th>
				<th style="width: 30%;"><?php esc_html_e( 'Details', 'wp-verifier' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $test_results as $test ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $test['name'] ); ?></strong></td>
					<td>
						<?php if ( 'pass' === $test['status'] ) : ?>
							<span style="color: #00a32a; font-weight: bold;">✓ PASS</span>
						<?php else : ?>
							<span style="color: #d63638; font-weight: bold;">✗ FAIL</span>
						<?php endif; ?>
					</td>
					<td><code><?php echo esc_html( $test['result'] ); ?></code></td>
					<td><?php echo esc_html( $test['details'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div style="margin-top: 30px; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1;">
		<h3><?php esc_html_e( 'Test Summary', 'wp-verifier' ); ?></h3>
		<?php
		$passed = count( array_filter( $test_results, function( $t ) { return 'pass' === $t['status']; } ) );
		$total = count( $test_results );
		?>
		<p>
			<strong><?php echo esc_html( sprintf( '%d / %d tests passed', $passed, $total ) ); ?></strong>
			<?php if ( $passed === $total ) : ?>
				<span style="color: #00a32a; margin-left: 10px;">✓ All tests passed!</span>
			<?php else : ?>
				<span style="color: #d63638; margin-left: 10px;">✗ Some tests failed</span>
			<?php endif; ?>
		</p>
	</div>
</div>
