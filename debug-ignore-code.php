<?php
/**
 * Debug Helper for Ignore Code Feature
 * 
 * Add this to wp-config.php to enable debug output:
 * define('WPV_DEBUG_IGNORE', true);
 * 
 * Then check debug.log file in wp-content folder
 */

// Example debug output locations:

// 1. In Admin_Page.php handle_ignore_code_request():
if ( defined('WPV_DEBUG_IGNORE') && WPV_DEBUG_IGNORE ) {
    error_log('=== IGNORE CODE REQUEST START ===');
    error_log('Page: ' . ($_GET['page'] ?? 'not set'));
    error_log('Action: ' . ($_GET['action'] ?? 'not set'));
    error_log('Plugin: ' . ($_GET['plugin'] ?? 'not set'));
    error_log('File: ' . ($_GET['file'] ?? 'not set'));
    error_log('Code: ' . ($_GET['code'] ?? 'not set'));
    error_log('Nonce: ' . ($_GET['_wpnonce'] ?? 'not set'));
}

// 2. After nonce verification:
if ( defined('WPV_DEBUG_IGNORE') && WPV_DEBUG_IGNORE ) {
    error_log('Nonce verified successfully');
}

// 3. After permission check:
if ( defined('WPV_DEBUG_IGNORE') && WPV_DEBUG_IGNORE ) {
    error_log('User has activate_plugins capability');
}

// 4. Before saving:
if ( defined('WPV_DEBUG_IGNORE') && WPV_DEBUG_IGNORE ) {
    error_log('Current ignore rules: ' . print_r($ignore_rules, true));
}

// 5. After saving:
if ( defined('WPV_DEBUG_IGNORE') && WPV_DEBUG_IGNORE ) {
    error_log('Ignore rule added successfully');
    error_log('Updated ignore rules: ' . print_r($ignore_rules, true));
}

// 6. Before redirect:
if ( defined('WPV_DEBUG_IGNORE') && WPV_DEBUG_IGNORE ) {
    error_log('Redirecting to: ' . admin_url('plugins.php?page=wp-verifier&tab=verify&plugin=' . urlencode($plugin) . '&ignored=1'));
    error_log('=== IGNORE CODE REQUEST END ===');
}

// To view the log:
// tail -f wp-content/debug.log
