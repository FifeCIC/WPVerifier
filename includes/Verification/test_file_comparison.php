<?php
/**
 * Test file hash comparison system
 *
 * @package wp-verifier
 */

// Test the file hash comparison system
require_once __DIR__ . '/Hash_Generator.php';

use WordPress\Plugin_Check\Verification\Hash_Generator;

echo "Testing File Hash Comparison System\n";
echo "==================================\n\n";

// Create test files
$test_dir = __DIR__ . '/test_files';
if (!is_dir($test_dir)) {
    mkdir($test_dir, 0755, true);
}

$test_file1 = $test_dir . '/test1.php';
$test_file2 = $test_dir . '/test2.php';

// Create test content
file_put_contents($test_file1, "<?php\nfunction test_function() {\n    return 'hello';\n}\n");
file_put_contents($test_file2, "<?php\nfunction another_function() {\n    return 'world';\n}\n");

$hash_generator = new Hash_Generator();

// Test 1: Generate hashes for both files
echo "Test 1: Generate file hashes\n";
$hash1 = $hash_generator->generate_file_hash($test_file1);
$hash2 = $hash_generator->generate_file_hash($test_file2);

echo "File 1 hash: $hash1\n";
echo "File 2 hash: $hash2\n";
echo "Hashes different: " . ($hash1 !== $hash2 ? 'YES' : 'NO') . "\n\n";

// Test 2: Modify file and check hash changes
echo "Test 2: Modify file and check hash change\n";
$original_hash = $hash_generator->generate_file_hash($test_file1);
echo "Original hash: $original_hash\n";

// Modify the file
file_put_contents($test_file1, "<?php\nfunction test_function() {\n    return 'hello world';\n}\n");

$new_hash = $hash_generator->generate_file_hash($test_file1);
echo "New hash: $new_hash\n";
echo "Hash changed: " . ($original_hash !== $new_hash ? 'YES' : 'NO') . "\n\n";

// Test 3: Simulate file comparison logic
echo "Test 3: Simulate file comparison logic\n";

// Simulate existing hashes (from previous scan)
$existing_hashes = array(
    'test1.php' => $original_hash,
    'test2.php' => $hash2,
);

// Simulate current files
$current_files = array($test_file1, $test_file2);
$changed_files = array();

foreach ($current_files as $file_path) {
    $current_hash = $hash_generator->generate_file_hash($file_path);
    $relative_path = basename($file_path);
    
    // Compare with existing hash
    if (!isset($existing_hashes[$relative_path]) || 
        $existing_hashes[$relative_path] !== $current_hash) {
        $changed_files[] = $file_path;
        echo "File changed: $relative_path (hash: $current_hash)\n";
    } else {
        echo "File unchanged: $relative_path\n";
    }
}

echo "\nFiles to scan: " . count($changed_files) . " out of " . count($current_files) . "\n";
echo "Performance improvement: " . (100 - (count($changed_files) / count($current_files) * 100)) . "%\n\n";

// Cleanup
unlink($test_file1);
unlink($test_file2);
rmdir($test_dir);

echo "Test completed successfully!\n";