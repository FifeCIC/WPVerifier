<?php
/**
 * Verification Tab - Consolidated Basic & Advanced Verification
 * Merged from admin-page-basic-verification.php and admin-page-advanced-verification.php
 *
 * @package wp-verifier
 */

use WordPress\Plugin_Check\Utilities\Template_Helper;
use WordPress\Plugin_Check\Utilities\Path_Builder;

$available_plugins = Template_Helper::get_available_plugins();
$current_plugin = Template_Helper::get_current_plugin();

// Define verification options
$categories = array(
    'security' => __('Security', 'wp-verifier'),
    'performance' => __('Performance', 'wp-verifier'),
    'accessibility' => __('Accessibility', 'wp-verifier'),
    'general' => __('General', 'wp-verifier')
);

$types = array(
    'error' => __('Errors', 'wp-verifier'),
    'warning' => __('Warnings', 'wp-verifier')
);

$user_enabled_categories = array('security', 'performance', 'accessibility', 'general');
$has_experimental_checks = true;

// Check which plugins have saved results
$plugins_with_results = array();
$plugin_dirs = glob(WP_PLUGIN_DIR . '/*', GLOB_ONLYDIR);
foreach ($plugin_dirs as $plugin_dir) {
    if (file_exists($plugin_dir . '/.wpv-results.json')) {
        $plugins_with_results[] = basename($plugin_dir);
    }
}
?>

<div class="wrap">
    <?php Template_Helper::render_page_header(
        'Verification', 
        'Run code quality checks and security analysis on your plugin.',
        $current_plugin,
        $available_plugins
    ); ?>

    <?php if ($current_plugin && isset($available_plugins[$current_plugin])) : ?>
        <!-- Readiness Checklist -->
        <div style="margin-bottom: 20px; padding: 20px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">
            <h3><?php esc_html_e('Verification Readiness Checklist', 'wp-verifier'); ?></h3>
            <p><strong><?php esc_html_e('Active Plugin:', 'wp-verifier'); ?></strong> <?php echo esc_html($available_plugins[$current_plugin]['Name']); ?></p>
            
            <?php
            $plugin_dir = Path_Builder::get_plugin_directory_path( $current_plugin );
            
            // Check file existence
            $results_exists = file_exists($plugin_dir . '/.wpv-results.json');
            $verification_exists = file_exists($plugin_dir . '/.wpv-verification.json');
            $config_exists = file_exists($plugin_dir . '/.wpv-config.json');
            
            // Check file content
            $has_hashes = false;
            $has_config = false;
            
            if ($verification_exists) {
                $verification_data = json_decode(file_get_contents($plugin_dir . '/.wpv-verification.json'), true);
                $has_hashes = !empty($verification_data['file_hashes']);
            }
            
            if ($config_exists) {
                $config_data = json_decode(file_get_contents($plugin_dir . '/.wpv-config.json'), true);
                $has_config = !empty($config_data['configuration']);
            }
            
            $readiness_score = 0;
            if ($results_exists) $readiness_score += 20;
            if ($verification_exists) $readiness_score += 20;
            if ($config_exists) $readiness_score += 20;
            if ($has_hashes) $readiness_score += 20;
            if ($has_config) $readiness_score += 20;
            ?>
            
            <div id="readiness-score-display" style="margin: 15px 0; display: none;">
                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <strong><?php esc_html_e('Readiness Score:', 'wp-verifier'); ?></strong>
                    <div style="margin-left: 10px; width: 200px; height: 20px; background: #f0f0f0; border-radius: 10px; overflow: hidden;">
                        <div style="width: <?php echo $readiness_score; ?>%; height: 100%; background: <?php echo $readiness_score >= 80 ? '#46b450' : ($readiness_score >= 60 ? '#ffb900' : '#dc3232'); ?>; transition: width 0.3s;"></div>
                    </div>
                    <span style="margin-left: 10px; font-weight: bold; color: <?php echo $readiness_score >= 80 ? '#46b450' : ($readiness_score >= 60 ? '#ffb900' : '#dc3232'); ?>;"><?php echo $readiness_score; ?>%</span>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php esc_html_e('Status', 'wp-verifier'); ?></th>
                        <th><?php esc_html_e('Requirement', 'wp-verifier'); ?></th>
                        <th><?php esc_html_e('Description', 'wp-verifier'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $results_exists ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>'; ?></td>
                        <td><?php esc_html_e('Results File', 'wp-verifier'); ?></td>
                        <td><?php esc_html_e('.wpv-results.json file exists for storing scan results', 'wp-verifier'); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo $verification_exists ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>'; ?></td>
                        <td><?php esc_html_e('Verification File', 'wp-verifier'); ?></td>
                        <td><?php esc_html_e('.wpv-verification.json file exists for tracking verification status', 'wp-verifier'); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo $config_exists ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>'; ?></td>
                        <td><?php esc_html_e('Configuration File', 'wp-verifier'); ?></td>
                        <td><?php esc_html_e('.wpv-config.json file exists with plugin settings', 'wp-verifier'); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo $has_hashes ? '<span style="color: green;">✓</span>' : '<span style="color: orange;">⚠</span>'; ?></td>
                        <td><?php esc_html_e('File Hashes', 'wp-verifier'); ?></td>
                        <td><?php esc_html_e('File hashes generated for incremental scanning (recommended)', 'wp-verifier'); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo $has_config ? '<span style="color: green;">✓</span>' : '<span style="color: orange;">⚠</span>'; ?></td>
                        <td><?php esc_html_e('Plugin Configuration', 'wp-verifier'); ?></td>
                        <td><?php esc_html_e('Plugin configuration completed (WordPress.org prep, ignore rules)', 'wp-verifier'); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <?php if ($readiness_score < 60) : ?>
                <div class="notice notice-warning" style="margin-top: 15px;">
                    <p><strong><?php esc_html_e('Setup Required:', 'wp-verifier'); ?></strong> <?php esc_html_e('Please complete the configuration in TAB01 and TAB02 before running verification.', 'wp-verifier'); ?></p>
                </div>
            <?php endif; ?>
        </div>

    <div class="plugin-check-content" style="display: flex; gap: 20px;">
        <!-- Verification Options Panel -->
        <div style="flex: 0 0 auto; display: flex; gap: 20px;">
            <div style="flex: 1;">
                <?php if (!empty($available_plugins)) : ?>
                    <form id="verification-form">
                        <h2><?php esc_html_e('Verification Options', 'wp-verifier'); ?></h2>
                        <div class="plugin-check__options">
                            <div style="display: flex; gap: 30px;">
                                <div>
                                    <h4><?php esc_attr_e('Categories', 'wp-verifier'); ?></h4>
                                    <?php if (!empty($categories)) : ?>
                                        <table id="plugin-check__categories">
                                            <?php foreach ($categories as $category => $label) : ?>
                                                <tr>
                                                    <td>
                                                        <fieldset>
                                                            <legend class="screen-reader-text"><?php echo esc_html($category); ?></legend>
                                                            <label for="<?php echo esc_attr($category); ?>">
                                                                <input type="checkbox" id="<?php echo esc_attr($category); ?>" name="categories" value="<?php echo esc_attr($category); ?>" <?php checked(in_array($category, $user_enabled_categories ?? array(), true)); ?> />
                                                                <?php echo esc_html($label); ?>
                                                            </label>
                                                        </fieldset>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php endif; ?>
                                </div>
                                
                                <div id="plugin-check__types-container">
                                    <h4><?php esc_attr_e('Types', 'wp-verifier'); ?></h4>
                                    <?php if (!empty($types)) : ?>
                                        <table id="plugin-check__types">
                                            <?php foreach ($types as $type => $label) : ?>
                                                <tr>
                                                    <td>
                                                        <fieldset>
                                                            <legend class="screen-reader-text"><?php echo esc_html($type); ?></legend>
                                                            <label for="<?php echo esc_attr($type); ?>">
                                                                <input type="checkbox" id="<?php echo esc_attr($type); ?>" name="types" value="<?php echo esc_attr($type); ?>" checked="checked" />
                                                                <?php echo esc_html($label); ?>
                                                            </label>
                                                        </fieldset>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <h4><?php esc_attr_e('Other Options', 'wp-verifier'); ?></h4>
                                    <?php if ($has_experimental_checks ?? false) : ?>
                                        <p>
                                            <label><input type="checkbox" value="include-experimental" id="plugin-check__include-experimental" /> <?php esc_html_e('Include Experimental Checks', 'wp-verifier'); ?></label>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <h4><?php esc_attr_e('Limit Results', 'wp-verifier'); ?></h4>
                                    <table id="plugin-check__limits">
                                        <tr><td><label><input type="radio" name="issue_limit" value="0" checked="checked" /> <?php esc_html_e('No limit', 'wp-verifier'); ?></label></td></tr>
                                        <tr><td><label><input type="radio" name="issue_limit" value="20" /> <?php esc_html_e('20 issues (testing)', 'wp-verifier'); ?></label></td></tr>
                                        <tr><td><label><input type="radio" name="issue_limit" value="250" /> <?php esc_html_e('250 issues', 'wp-verifier'); ?></label></td></tr>
                                        <tr><td><label><input type="radio" name="issue_limit" value="500" /> <?php esc_html_e('500 issues', 'wp-verifier'); ?></label></td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php else : ?>
                    <h2><?php esc_html_e('No plugins available.', 'wp-verifier'); ?></h2>
                <?php endif; ?>
            </div>

            <!-- Plugin Selection Panel -->
            <div style="flex: 0 0 300px;">
                <?php if ($current_plugin && isset($available_plugins[$current_plugin])) : ?>
                    <h2><?php esc_html_e('Run Verification', 'wp-verifier'); ?></h2>
                    <p><?php esc_html_e('Execute code quality checks and security analysis on the active plugin.', 'wp-verifier'); ?></p>
                    
                    <p>
                        <input type="submit" value="<?php esc_attr_e('Run Verification', 'wp-verifier'); ?>" id="plugin-check__submit" class="button button-primary" <?php echo $readiness_score < 60 ? 'disabled' : ''; ?> />
                        <span id="plugin-check__spinner" class="spinner" style="float: none;"></span>
                    </p>
                    
                    <!-- Progress Bar -->
                    <div id="verification-progress" style="margin-top: 15px; display: none;">
                        <div style="margin-bottom: 10px;">
                            <strong><?php esc_html_e('Verification Progress:', 'wp-verifier'); ?></strong>
                            <span id="progress-percentage" style="float: right; font-weight: bold;">0%</span>
                        </div>
                        <div style="width: 100%; height: 20px; background: #f0f0f0; border-radius: 10px; overflow: hidden; margin-bottom: 10px;">
                            <div id="progress-bar" style="width: 0%; height: 100%; background: #0073aa; transition: width 0.3s;"></div>
                        </div>
                        <div id="progress-message" style="font-size: 12px; color: #666; min-height: 16px;"><?php esc_html_e('Initializing verification...', 'wp-verifier'); ?></div>
                    </div>
                    
                    <?php if ($readiness_score < 60) : ?>
                        <p style="color: #d63638; font-size: 12px;"><?php esc_html_e('Complete setup requirements to enable verification.', 'wp-verifier'); ?></p>
                    <?php endif; ?>
                <?php else : ?>
                    <h2><?php esc_html_e('No Active Plugin', 'wp-verifier'); ?></h2>
                    <p><?php esc_html_e('Please select a plugin in TAB01 first.', 'wp-verifier'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php else : ?>
        <div style="padding: 20px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">
            <h3><?php esc_html_e('No Active Plugin Selected', 'wp-verifier'); ?></h3>
            <p><?php esc_html_e('Please go to TAB01 (Select Plugin) to choose an active plugin before running verification.', 'wp-verifier'); ?></p>
        </div>
    <?php endif; ?>
    </div>

    <!-- Progress Container -->
    <div id="verification-progress-container" style="margin: 20px 0; display: none;">
        <div style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px;">
            <h3><?php esc_html_e('Verification in Progress', 'wp-verifier'); ?></h3>
            <div style="margin: 15px 0;">
                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <strong><?php esc_html_e('Overall Progress:', 'wp-verifier'); ?></strong>
                    <span id="overall-progress-percentage" style="margin-left: auto; font-weight: bold;">0%</span>
                </div>
                <div style="width: 100%; height: 25px; background: #f0f0f0; border-radius: 12px; overflow: hidden; margin-bottom: 15px;">
                    <div id="overall-progress-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #0073aa, #005177); transition: width 0.5s;"></div>
                </div>
                <div id="current-step" style="font-size: 14px; color: #333; margin-bottom: 10px; min-height: 20px;"><?php esc_html_e('Preparing verification...', 'wp-verifier'); ?></div>
                <div id="step-details" style="font-size: 12px; color: #666; min-height: 16px;"></div>
            </div>
        </div>
    </div>

    <!-- Results Display -->
    <div id="plugin-check__export-controls" class="plugin-check__export-controls"></div>
    <div id="plugin-check__results"></div>

    <!-- Files Actually Ignored Panel -->
    <div id="wpv-ignored-files-panel" style="background: #f0f6fc; padding: 20px; border: 1px solid #2271b1; margin: 20px 0; display: none;">
        <h3><?php esc_html_e('Files Actually Ignored During Processing', 'wp-verifier'); ?></h3>
        <div id="wpv-ignored-files-content"></div>
    </div>

    <!-- AST Results Container -->
    <div class="wpv-ast-container" style="margin-top: 20px; display: none;" id="wpv-ast-container">
        <div class="wpv-ast-layout">
            <div class="wpv-ast-table-container">
                <div class="wpv-ast-table" id="wpv-ast-results"></div>
                <div id="wpv-ast-details" style="margin-top: 20px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; display: none;"></div>
                <div id="wpv-ast-ai-guidance" style="margin-top: 20px; padding: 20px; background: #f0f6fc; border: 1px solid #0073aa; border-radius: 4px; display: none;">
                    <h3 style="margin: 0 0 15px 0;"><?php esc_html_e('AI Guidance', 'wp-verifier'); ?></h3>
                    <div id="wpv-ai-guidance-content"></div>
                </div>
            </div>
        </div>
        <div id="wpv-ast-ignored-folders" style="margin-top: 15px; display: none;">
            <h4><?php esc_html_e('Ignored Folders', 'wp-verifier'); ?></h4>
            <ul id="wpv-ignored-folders-list" style="list-style: none; margin: 0; padding: 0;"></ul>
        </div>
    </div>
</div>