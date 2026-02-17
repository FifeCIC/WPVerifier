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
        'plugin-check-admin' => array(
            'path' => 'js/plugin-check-admin.js',
            'purpose' => 'Main admin functionality',
            'pages' => array('wp-verifier'),
            'dependencies' => array('wp-util')
        ),
        'plugin-check-namer' => array(
            'path' => 'js/plugin-check-namer.js',
            'purpose' => 'Plugin namer tool',
            'pages' => array('plugin-check-namer'),
            'dependencies' => array()
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
    ),
);
