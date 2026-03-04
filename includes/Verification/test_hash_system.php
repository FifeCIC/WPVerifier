<?php
/**
 * Test script for hash tracking system
 * Run this to verify the system works with Settings_Page.php
 */

// Include WordPress if not already loaded
if ( ! defined( 'WP_PLUGIN_CHECK_PLUGIN_DIR_PATH' ) ) {
	define( 'WP_PLUGIN_CHECK_PLUGIN_DIR_PATH', __DIR__ . '/../' );
}

require_once __DIR__ . '/Hash_Generator.php';
require_once __DIR__ . '/JSON_Storage.php';
require_once __DIR__ . '/Verification_Matcher.php';

use WordPress\Plugin_Check\Verification\Hash_Generator;
use WordPress\Plugin_Check\Verification\JSON_Storage;
use WordPress\Plugin_Check\Verification\Verification_Matcher;

// Test file path
$test_file = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Admin/Settings_Page.php';

echo "=== Hash Tracking System Test ===\n";
echo "Testing with file: " . basename( $test_file ) . "\n\n";

// Test 1: Generate file hash
$hash_generator = new Hash_Generator();
$file_hash = $hash_generator->generate_file_hash( $test_file );

echo "1. File Hash Generation:\n";
echo "   File hash: " . ( $file_hash ?: 'FAILED' ) . "\n\n";

// Test 2: Generate function hash
$function_name = 'Settings_Page::add_hooks';
$function_hash = $hash_generator->generate_function_hash( $test_file, $function_name );

echo "2. Function Hash Generation:\n";
echo "   Function: {$function_name}\n";
echo "   Function hash: " . ( $function_hash ?: 'FAILED' ) . "\n\n";

// Test 3: JSON Storage
$storage = new JSON_Storage();
$verification_data = $storage->load_verification_data();

echo "3. JSON Storage Test:\n";
echo "   Default structure loaded: " . ( isset( $verification_data['version'] ) ? 'SUCCESS' : 'FAILED' ) . "\n";
echo "   Version: " . ( $verification_data['version'] ?? 'N/A' ) . "\n\n";

// Test 4: Mark function as verified
if ( $function_hash ) {
	$test_issues = array(
		array(
			'line' => 50,
			'type' => 'WordPress.Security.NonceVerification.Recommended',
		),
	);
	
	$mark_result = $storage->mark_function_verified(
		$function_name,
		'includes/Admin/Settings_Page.php',
		$function_hash,
		$test_issues,
		'Test verification'
	);
	
	echo "4. Mark Function as Verified:\n";
	echo "   Result: " . ( $mark_result ? 'SUCCESS' : 'FAILED' ) . "\n\n";
}

// Test 5: Verification Matcher
$matcher = new Verification_Matcher();
$test_issue = array(
	'file' => 'includes/Admin/Settings_Page.php',
	'line' => 50,
	'type' => 'WordPress.Security.NonceVerification.Recommended',
);

$verification_result = $matcher->is_verified( $test_issue, $function_name );

echo "5. Verification Matching:\n";
echo "   Issue verified: " . ( $verification_result['verified'] ? 'YES' : 'NO' ) . "\n";
echo "   Status: " . ( $verification_result['status'] ?? 'unknown' ) . "\n";
if ( isset( $verification_result['reason'] ) ) {
	echo "   Reason: " . $verification_result['reason'] . "\n";
}

echo "\n=== Test Complete ===\n";
echo "Check .wpv-verification.json file in plugin root for saved data.\n";