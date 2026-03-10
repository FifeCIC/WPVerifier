<?php
/**
 * WP Verifier Script Assets Registry
 *
 * @package wp-verifier
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'admin' => array(
        // Consolidated AJAX utilities - shared across all pages
        'wpv-ajax' => array(
            'path' => 'js/wpv-ajax.js',
            'purpose' => 'Shared AJAX utility functions',
            'pages' => array('wp-verifier'),
            'dependencies' => array('jquery'),
            'localize' => array(
                'name' => 'wpv_ajax_object',
                'data' => array(
                    'ajax_url' => 'admin_url:admin-ajax.php',
                    'nonce' => 'nonce:plugin-check-run-checks',
                    'current_plugin' => 'php:get_user_meta(get_current_user_id(), "wpv_last_selected_plugin", true)',
                ),
            ),
        ),
        // Consolidated configuration functionality
        'admin-configuration' => array(
            'path' => 'js/admin-configuration.js',
            'purpose' => 'Configuration tab functionality (merged preparation + hash generation)',
            'pages' => array('wp-verifier'),
            'dependencies' => array('jquery', 'wpv-ajax')
        ),
        // Consolidated verification functionality
        'admin-verification' => array(
            'path' => 'js/admin-verification.js',
            'purpose' => 'Verification tab functionality (merged basic + advanced)',
            'pages' => array('wp-verifier'),
            'dependencies' => array('jquery', 'wpv-ajax')
        ),
        // Legacy - marked for removal after consolidation
        'plugin-check-admin' => array(
            'path' => 'js/plugin-check-admin.js',
            'purpose' => 'LEGACY: Main admin functionality - TO BE REMOVED',
            'pages' => array('wp-verifier'),
            'dependencies' => array('wp-util'),
            'deprecated' => true
        ),
        'plugin-check-namer' => array(
            'path' => 'js/plugin-check-namer.js',
            'purpose' => 'REMOVED: Plugin namer tool - ELIMINATED',
            'pages' => array(),
            'dependencies' => array(),
            'removed' => true
        ),
        'admin-settings' => array(
            'path' => 'js/admin-settings.js',
            'purpose' => 'Settings page functionality',
            'pages' => array('plugin-check-settings'),
            'dependencies' => array()
        ),
        'wp-verifier-ast' => array(
            'path' => 'js/wp-verifier-ast.js',
            'purpose' => 'AST (Accordion Sidebar Table) functionality',
            'pages' => array('wp-verifier'),
            'dependencies' => array('jquery')
        ),
        'wpv-plugin-namer' => array(
            'path' => 'js/admin-plugin-namer.js',
            'purpose' => 'REMOVED: Plugin Namer tab functionality - ELIMINATED',
            'pages' => array(),
            'dependencies' => array('jquery'),
            'removed' => true
        ),
        // Legacy files - marked for removal after consolidation
        'admin-page-preparation' => array(
            'path' => 'js/admin-page-preparation.js',
            'purpose' => 'LEGACY: Preparation page functionality - CONSOLIDATED INTO admin-configuration.js',
            'pages' => array(),
            'dependencies' => array('jquery'),
            'deprecated' => true
        ),
        'admin-page-hash-generation' => array(
            'path' => 'js/admin-page-hash-generation.js',
            'purpose' => 'LEGACY: Hash generation page functionality - CONSOLIDATED INTO admin-configuration.js',
            'pages' => array(),
            'dependencies' => array('jquery'),
            'deprecated' => true
        ),
        'basic-verification' => array(
            'path' => 'js/basic-verification.js',
            'purpose' => 'REMOVED: Basic verification functionality - ELIMINATED',
            'pages' => array(),
            'dependencies' => array('jquery'),
            'removed' => true
        ),
    ),
);
