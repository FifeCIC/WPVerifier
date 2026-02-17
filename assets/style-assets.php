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
    ),
);
