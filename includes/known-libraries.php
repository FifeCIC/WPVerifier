<?php
/**
 * Known Library Paths Configuration
 * 
 * @package WPVerifier
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    // WPSeed libraries
    'includes/libraries/action-scheduler',
    'includes/libraries/carbon-fields',
    
    // WPVerifier vendor libraries
    'vendor/automattic',
    'vendor/clue',
    'vendor/composer',
    'vendor/dealerdirect',
    'vendor/nikic',
    'vendor/nyholm',
    'vendor/patrickschur',
    'vendor/php-http',
    'vendor/phpcsstandards',
    'vendor/plugin-check',
    'vendor/psr',
    'vendor/sirbrillig',
    'vendor/squizlabs',
    'vendor/symfony',
    'vendor/wordpress',
    'vendor/wp-coding-standards',
);
