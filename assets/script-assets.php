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
        'wpv-plugin-namer' => array(
            'path' => 'js/admin-plugin-namer.js',
            'purpose' => 'Plugin Namer tab functionality',
            'pages' => array('wp-verifier'),
            'dependencies' => array('jquery'),
            'localize' => array(
                'name' => 'wpvPluginNamer',
                'data' => array(
                    'ajaxUrl' => 'admin_url:admin-ajax.php',
                    'nonce' => 'nonce:plugin-check-run-checks',
                    'actions' => array(
                        'checkDomains' => 'constant:ACTION_CHECK_DOMAINS',
                        'checkConflicts' => 'constant:ACTION_CHECK_CONFLICTS',
                        'analyzeSeo' => 'constant:ACTION_ANALYZE_SEO',
                        'checkTrademarks' => 'constant:ACTION_CHECK_TRADEMARKS',
                        'saveName' => 'constant:ACTION_SAVE_NAME',
                        'getSavedNames' => 'constant:ACTION_GET_SAVED_NAMES',
                    ),
                    'i18n' => array(
                        'available' => 'i18n:Available',
                        'taken' => 'i18n:Taken',
                        'checking' => 'i18n:Checking...',
                        'error' => 'i18n:Error',
                        'noConflicts' => 'i18n:No conflicts found',
                        'exactMatch' => 'i18n:Exact match found!',
                        'similar' => 'i18n:Similar plugins found',
                        'saved' => 'i18n:Evaluation saved successfully',
                        'saveFailed' => 'i18n:Failed to save evaluation',
                    ),
                ),
            ),
        ),
    ),
);
