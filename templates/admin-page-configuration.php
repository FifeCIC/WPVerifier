<?php
/**
 * Configuration Tab - Plugin Setup, Vendor Detection & Hash Generation
 * Consolidated from admin-page-preparation.php and admin-page-hash-generation.php
 *
 * @package wp-verifier
 */

use WordPress\Plugin_Check\Utilities\Ignore_Rules;
use WordPress\Plugin_Check\Utilities\Template_Helper;
use WordPress\Plugin_Check\Admin\Saved_Results_Handler;
use WordPress\Plugin_Check\Admin\Vendor_Detector;

$available_plugins = Template_Helper::get_available_plugins();
$current_plugin = Template_Helper::get_current_plugin();
$rules = Ignore_Rules::get_rules();
?>

<div class="wrap">
    <?php Template_Helper::render_page_header(
        'Configuration', 
        'Configure plugin settings, manage ignore rules, and generate file hashes for verification tracking.',
        $current_plugin,
        $available_plugins
    ); ?>

    <?php if (!empty($available_plugins)) : ?>
        <div style="max-width: 800px;">
            <!-- Plugin Configuration Section -->
            <div id="plugin-configuration" style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">
                <h3><?php esc_html_e('Plugin Configuration', 'wpverifier'); ?></h3>
                <div id="config-content">
                    <?php if ($current_plugin && isset($available_plugins[$current_plugin])) : ?>
                        <p><em><?php esc_html_e('Go to Select Plugin tab to change the active plugin.', 'wpverifier'); ?></em></p>
                        
                        <!-- Vendor Folders Detection -->
                        <h4><?php esc_html_e('Vendor/Library Folders', 'wpverifier'); ?></h4>
                        <p><?php esc_html_e('Drag folders between columns to include or exclude them from verification:', 'wpverifier'); ?></p>
                        
                        <div id="vendor-folders-manager" style="margin: 15px 0;">
                            <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                                <div style="flex: 1;">
                                    <h5 style="margin: 0 0 10px 0; color: #0073aa;"><?php esc_html_e('Include in Verification', 'wpverifier'); ?></h5>
                                    <div id="included-folders" class="folder-drop-zone" style="min-height: 150px; padding: 10px; border: 2px dashed #0073aa; border-radius: 4px; background: #f0f8ff;">
                                        <p class="drop-hint" style="text-align: center; color: #666; margin: 50px 0;"><?php esc_html_e('Folders to be scanned will appear here', 'wpverifier'); ?></p>
                                    </div>
                                </div>
                                <div style="flex: 1;">
                                    <h5 style="margin: 0 0 10px 0; color: #dc3232;"><?php esc_html_e('Exclude from Verification', 'wpverifier'); ?></h5>
                                    <div id="excluded-folders" class="folder-drop-zone" style="min-height: 150px; padding: 10px; border: 2px dashed #dc3232; border-radius: 4px; background: #fff5f5;">
                                        <p class="drop-hint" style="text-align: center; color: #666; margin: 50px 0;"><?php esc_html_e('Folders to be ignored will appear here', 'wpverifier'); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="text-align: center; margin: 15px 0;">
                                <button type="button" id="detect-vendors" class="button"><?php esc_html_e('Detect Vendor Folders', 'wpverifier'); ?></button>
                                <button type="button" id="reset-folders" class="button"><?php esc_html_e('Reset All', 'wpverifier'); ?></button>
                            </div>
                        </div>
                        
                        <div id="config-form"></div>
                        <p>
                            <button type="button" id="load-config" class="button button-primary"><?php esc_html_e('Load Configuration', 'wpverifier'); ?></button>
                        </p>
                    <?php else : ?>
                        <p style="color: #d63638;"><?php esc_html_e('No active plugin selected. Please go to the Select Plugin tab first.', 'wpverifier'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hash Generation Section -->
            <?php if ($current_plugin && isset($available_plugins[$current_plugin])) : ?>
                <div id="hash-generation-panel" style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">
                    <h3><?php esc_html_e('Hash Generation', 'wpverifier'); ?></h3>
                    <p><strong><?php esc_html_e('Active Plugin:', 'wpverifier'); ?></strong> <?php echo esc_html($available_plugins[$current_plugin]['Name']); ?></p>
                    <div id="hash-status"></div>
                    <div id="hash-progress" style="margin: 15px 0;"></div>
                    <div id="hash-results" style="margin-top: 15px;"></div>
                    <p>
                        <button type="button" id="generate-hashes" class="button button-primary"><?php esc_html_e('Generate File Hashes', 'wpverifier'); ?></button>
                        <button type="button" id="validate-hashes" class="button" style="display: none;"><?php esc_html_e('Validate Existing Hashes', 'wpverifier'); ?></button>
                        <span id="hash-spinner" class="spinner" style="float: none;"></span>
                    </p>
                    
                    <div style="margin-top: 15px; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                        <h4><?php esc_html_e('What are File Hashes?', 'wpverifier'); ?></h4>
                        <p><?php esc_html_e('File hashes create a unique fingerprint for each file and function in your plugin. This enables:', 'wpverifier'); ?></p>
                        <ul>
                            <li><?php esc_html_e('Incremental scanning - only check files that have changed', 'wpverifier'); ?></li>
                            <li><?php esc_html_e('Issue tracking - link problems to specific code versions', 'wpverifier'); ?></li>
                            <li><?php esc_html_e('Change detection - identify what code has been modified', 'wpverifier'); ?></li>
                            <li><?php esc_html_e('Ignore management - track which issues have been reviewed', 'wpverifier'); ?></li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <p><?php esc_html_e('No plugins found.', 'wpverifier'); ?></p>
    <?php endif; ?>

    <!-- Ignore Rules Management Section -->
    <?php if ($current_plugin && isset($available_plugins[$current_plugin])) : ?>
        <div id="ignore-rules-panel" style="margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">
            <h3><?php esc_html_e('Ignore Rules Management', 'wpverifier'); ?></h3>
            <p><?php esc_html_e('Manage rules to filter out third-party code and false positives from verification results.', 'wpverifier'); ?></p>

            <div style="display: flex; gap: 20px; margin: 20px 0;">
                <button type="button" class="button" onclick="document.getElementById('add-rule-form').style.display='block'"><?php esc_html_e('Add Rule', 'wpverifier'); ?></button>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wpv_export_ignore_rules'), 'wpv_export_rules')); ?>" class="button"><?php esc_html_e('Export Rules', 'wpverifier'); ?></a>
                <button type="button" class="button" onclick="document.getElementById('import-form').style.display='block'"><?php esc_html_e('Import Rules', 'wpverifier'); ?></button>
            </div>

            <!-- Add Rule Form -->
            <div id="add-rule-form" style="display:none; background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin: 15px 0; border-radius: 4px;">
                <h4><?php esc_html_e('Add Ignore Rule', 'wpverifier'); ?></h4>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('wpv_add_ignore_rule', 'wpv_nonce'); ?>
                    <input type="hidden" name="action" value="wpv_add_ignore_rule" />
                    <table class="form-table">
                        <tr>
                            <th><label for="scope"><?php esc_html_e('Scope', 'wpverifier'); ?></label></th>
                            <td>
                                <select name="scope" id="scope" required>
                                    <option value="directory"><?php esc_html_e('Directory', 'wpverifier'); ?></option>
                                    <option value="file"><?php esc_html_e('File', 'wpverifier'); ?></option>
                                    <option value="code"><?php esc_html_e('Error Code', 'wpverifier'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="plugin"><?php esc_html_e('Plugin', 'wpverifier'); ?></label></th>
                            <td>
                                <?php echo Template_Helper::render_plugin_selector($current_plugin, $available_plugins); ?>
                                <p class="description"><?php esc_html_e('Optional: Select a plugin to auto-fill the base path', 'wpverifier'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="path"><?php esc_html_e('Path', 'wpverifier'); ?></label></th>
                            <td>
                                <input type="text" name="path" id="path" class="regular-text" required placeholder="includes/libraries/vendor/" />
                                <p class="description"><?php esc_html_e('Relative path from plugin root (e.g., includes/libraries/vendor/ or vendor/)', 'wpverifier'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="code"><?php esc_html_e('Error Code', 'wpverifier'); ?></label></th>
                            <td><input type="text" name="code" id="code" class="regular-text" placeholder="WordPress.Security.EscapeOutput" /></td>
                        </tr>
                        <tr>
                            <th><label for="reason"><?php esc_html_e('Reason', 'wpverifier'); ?></label></th>
                            <td>
                                <select name="reason" id="reason">
                                    <option value="vendor"><?php esc_html_e('Vendor/Library', 'wpverifier'); ?></option>
                                    <option value="other"><?php esc_html_e('Other', 'wpverifier'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="note"><?php esc_html_e('Note', 'wpverifier'); ?></label></th>
                            <td><input type="text" name="note" id="note" class="regular-text" /></td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" class="button button-primary"><?php esc_html_e('Add Rule', 'wpverifier'); ?></button>
                        <button type="button" class="button" onclick="document.getElementById('add-rule-form').style.display='none'"><?php esc_html_e('Cancel', 'wpverifier'); ?></button>
                    </p>
                </form>
            </div>

            <!-- Import Form -->
            <div id="import-form" style="display:none; background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin: 15px 0; border-radius: 4px;">
                <h4><?php esc_html_e('Import Rules', 'wpverifier'); ?></h4>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field('wpv_import_rules', 'wpv_nonce'); ?>
                    <input type="hidden" name="action" value="wpv_import_ignore_rules" />
                    <p>
                        <input type="file" name="rules_file" accept=".json" required />
                    </p>
                    <p>
                        <button type="submit" class="button button-primary"><?php esc_html_e('Import', 'wpverifier'); ?></button>
                        <button type="button" class="button" onclick="document.getElementById('import-form').style.display='none'"><?php esc_html_e('Cancel', 'wpverifier'); ?></button>
                    </p>
                </form>
            </div>

            <!-- Active Rules Table -->
            <h4><?php esc_html_e('Active Rules', 'wpverifier'); ?></h4>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Scope', 'wpverifier'); ?></th>
                        <th><?php esc_html_e('Path', 'wpverifier'); ?></th>
                        <th><?php esc_html_e('Code', 'wpverifier'); ?></th>
                        <th><?php esc_html_e('Reason', 'wpverifier'); ?></th>
                        <th><?php esc_html_e('Note', 'wpverifier'); ?></th>
                        <th><?php esc_html_e('Actions', 'wpverifier'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rules)) : ?>
                        <tr>
                            <td colspan="6"><?php esc_html_e('No ignore rules defined.', 'wpverifier'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($rules as $id => $rule) : ?>
                            <tr>
                                <td><?php echo esc_html(ucfirst($rule['scope'] ?? '')); ?></td>
                                <td><code><?php echo esc_html($rule['path'] ?? ''); ?></code></td>
                                <td><?php echo esc_html($rule['code'] ?? ''); ?></td>
                                <td><?php echo esc_html(ucfirst($rule['reason'] ?? '')); ?></td>
                                <td><?php echo esc_html($rule['note'] ?? ''); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wpv_remove_ignore_rule&rule_id=' . $id), 'wpv_remove_rule_' . $id)); ?>" 
                                       onclick="return confirm('<?php esc_attr_e('Remove this rule?', 'wpverifier'); ?>');">
                                        <?php esc_html_e('Remove', 'wpverifier'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>