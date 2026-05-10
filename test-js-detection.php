<?php
/**
 * Test JS Library Detection
 * Run this from command line or browser to test the new detection
 */

// Load WordPress
require_once __DIR__ . '/../../../../../wp-load.php';

// Load required classes
require_once __DIR__ . '/includes/Utilities/JS_Library_Signatures.php';
require_once __DIR__ . '/includes/Admin/JS_Library_Detector.php';
require_once __DIR__ . '/includes/Utilities/Path_Builder.php';

use WordPress\Plugin_Check\Admin\JS_Library_Detector;
use WordPress\Plugin_Check\Admin\Vendor_Detector;

echo "=== Testing JS Library Detection ===\n\n";

$plugin_slug = 'tradepress/tradepress.php';

echo "Testing plugin: $plugin_slug\n\n";

// Test JS Library Detection
echo "--- JS Libraries Detected ---\n";
$js_libraries = JS_Library_Detector::detect_libraries($plugin_slug);

if (empty($js_libraries)) {
    echo "No JS libraries detected.\n";
} else {
    foreach ($js_libraries as $key => $library) {
        echo "Library: {$library['name']}\n";
        foreach ($library['files'] as $file) {
            echo "  - {$file['path']} (v{$file['version']})\n";
        }
    }
}

echo "\n--- Vendor Folders Detected ---\n";
$vendors = Vendor_Detector::detect_vendors($plugin_slug);

if (empty($vendors)) {
    echo "No vendor folders detected.\n";
} else {
    foreach ($vendors as $path => $subdirs) {
        echo "Path: $path\n";
        if (!empty($subdirs)) {
            echo "  Subdirs: " . implode(', ', $subdirs) . "\n";
        }
    }
}

echo "\n=== Test Complete ===\n";
