<?php
/**
 * Results complete notice template.
 *
 * @package WPVerifier
 * @version 1.9.0 Added ABSPATH direct access protection.
 */

// Prevent direct file access for security.
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="notice notice-{{ data.type }}">
    <p><?php esc_html_e( 'Checks complete.', 'wpverifier' ); ?> {{ data.message }}</p>
</div>
