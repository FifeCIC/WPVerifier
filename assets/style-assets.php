<?php
/**
 * WP Verifier Style Assets Registry
 *
 * @package wp-verifier
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'admin' => array(
        'plugin-check-admin' => array(
            'path' => 'css/plugin-check-admin.css',
            'purpose' => 'Main admin styles',
            'pages' => array('wp-verifier', 'plugin-check-namer'),
            'dependencies' => array()
        ),
        'wp-verifier-tabs' => array(
            'path' => 'css/wp-verifier-tabs.css',
            'purpose' => 'Tab navigation styles',
            'pages' => array('wp-verifier', 'plugin-check-namer', 'plugin-check-settings'),
            'dependencies' => array()
        ),
        // Consolidated configuration styles
        'admin-configuration' => array(
            'path' => 'css/admin-configuration.css',
            'purpose' => 'Configuration tab styles (merged preparation + hash generation)',
            'pages' => array('wp-verifier'),
            'dependencies' => array()
        ),
        // Consolidated verification styles
        'admin-verification' => array(
            'path' => 'css/admin-verification.css',
            'purpose' => 'Verification tab styles (merged basic + advanced)',
            'pages' => array('wp-verifier'),
            'dependencies' => array()
        ),
        'wp-verifier-setup' => array(
            'path' => 'css/wp-verifier-setup.css',
            'purpose' => 'Setup wizard styles',
            'pages' => array('wp-verifier-setup'),
            'dependencies' => array('dashicons', 'install')
        ),
        'wp-verifier-ast' => array(
            'path' => 'css/wp-verifier-ast.css',
            'purpose' => 'AST (Accordion Sidebar Table) layout',
            'pages' => array('wp-verifier'),
            'dependencies' => array()
        ),
        'wpv-plugin-namer' => array(
            'path' => 'css/admin-plugin-namer.css',
            'purpose' => 'REMOVED: Plugin Namer tab styles - ELIMINATED',
            'pages' => array(),
            'dependencies' => array(),
            'removed' => true
        ),
        // Legacy styles - marked for removal after consolidation
        'admin-page-preparation' => array(
            'path' => 'css/admin-page-preparation.css',
            'purpose' => 'LEGACY: Preparation page styles - CONSOLIDATED INTO admin-configuration.css',
            'pages' => array(),
            'dependencies' => array(),
            'deprecated' => true
        ),
        'admin-page-hash-generation' => array(
            'path' => 'css/admin-page-hash-generation.css',
            'purpose' => 'LEGACY: Hash generation page styles - CONSOLIDATED INTO admin-configuration.css',
            'pages' => array(),
            'dependencies' => array(),
            'deprecated' => true
        ),
    ),
);
