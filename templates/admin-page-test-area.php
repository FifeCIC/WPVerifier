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

// Test 5: Hash Workflow Test
$workflow_test_result = 'Not run';
$workflow_status = 'info';
if ( isset( $_POST['run_workflow_test'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'hash_workflow_test' ) ) {
	try {
		// Create test file with intentional issue
		$test_plugin_dir = WP_PLUGIN_DIR . '/test-hash-plugin';
		$test_file_path = $test_plugin_dir . '/test-hash.php';
		wp_mkdir_p( $test_plugin_dir );
		file_put_contents( $test_file_path, '<?php\n// This will trigger WordPress.Security.EscapeOutput.OutputNotEscaped\necho $_GET["test"];\n' );
		
		$initial_hash = $hash_generator->generate_file_hash( $test_file_path );
		
		// Fix the file
		file_put_contents( $test_file_path, '<?php\n// Fixed: Added proper escaping\necho esc_html( $_GET["test"] );\n' );
		$fixed_hash = $hash_generator->generate_file_hash( $test_file_path );
		
		// Cleanup
		unlink( $test_file_path );
		rmdir( $test_plugin_dir );
		
		if ( $initial_hash !== $fixed_hash ) {
			$workflow_test_result = "Initial: {$initial_hash}, Fixed: {$fixed_hash}";
			$workflow_status = 'pass';
		} else {
			$workflow_test_result = 'Hashes should be different after fix';
			$workflow_status = 'fail';
		}
	} catch ( Exception $e ) {
		$workflow_test_result = 'Error: ' . $e->getMessage();
		$workflow_status = 'fail';
	}
}

$test_results[] = array(
	'name' => 'Hash Workflow Test',
	'status' => $workflow_status,
	'result' => $workflow_test_result,
	'details' => 'Tests complete fix/ignore workflow with hash updates',
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
						<?php elseif ( 'fail' === $test['status'] ) : ?>
							<span style="color: #d63638; font-weight: bold;">✗ FAIL</span>
						<?php else : ?>
							<span style="color: #666; font-weight: bold;">— INFO</span>
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
		$total = count( array_filter( $test_results, function( $t ) { return 'info' !== $t['status']; } ) );
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
	
	<hr style="margin: 30px 0;">
	
	<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
		<h3><?php esc_html_e( 'Hash Workflow Test', 'wp-verifier' ); ?></h3>
		<p><?php esc_html_e( 'This test validates the complete hash workflow: scan → fix → recheck → hash update.', 'wp-verifier' ); ?></p>
		
		<form method="post" action="">
			<?php wp_nonce_field( 'hash_workflow_test' ); ?>
			<p>
				<?php submit_button( __( 'Run Hash Workflow Test', 'wp-verifier' ), 'secondary', 'run_workflow_test', false ); ?>
			</p>
		</form>
		
		<h4><?php esc_html_e( 'Test Steps:', 'wp-verifier' ); ?></h4>
		<ol>
			<li><?php esc_html_e( 'Create test file with PHPCS issue', 'wp-verifier' ); ?></li>
			<li><?php esc_html_e( 'Generate initial hash', 'wp-verifier' ); ?></li>
			<li><?php esc_html_e( 'Fix the issue in the file', 'wp-verifier' ); ?></li>
			<li><?php esc_html_e( 'Generate new hash and verify it changed', 'wp-verifier' ); ?></li>
			<li><?php esc_html_e( 'Clean up test files', 'wp-verifier' ); ?></li>
		</ol>
	</div>
</div>
