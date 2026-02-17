<?php
/**
 * WP Verifier Assets Tab View
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Admin;

use WordPress\Plugin_Check\Assets\Asset_Manager;

if (!defined('ABSPATH')) {
    exit;
}

class Assets_Tab {

    public static function render() {
        if (!class_exists('WordPress\\Plugin_Check\\Assets\\Asset_Manager')) {
            require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'includes/Assets/Asset_Manager.php';
        }

        $asset_manager = new Asset_Manager();
        $css_assets = $asset_manager->get_all_assets('css');
        $js_assets = $asset_manager->get_all_assets('js');
        $css_stats = $asset_manager->get_asset_stats('css');
        $js_stats = $asset_manager->get_asset_stats('js');
        $overall_status = self::get_overall_status($css_stats, $js_stats);
        ?>
        <div class="wp-verifier-assets-container">
            <div class="notice notice-<?php echo $overall_status['type']; ?>">
                <p>
                    <span class="dashicons <?php echo esc_attr($overall_status['icon']); ?>"></span>
                    <strong><?php echo esc_html($overall_status['message']); ?></strong>
                    <?php if (!empty($overall_status['details'])): ?>
                        - <?php echo esc_html($overall_status['details']); ?>
                    <?php endif; ?>
                </p>
            </div>
            
            <h3><?php esc_html_e('CSS Assets', 'wp-verifier'); ?></h3>
            <p>
                <strong><?php echo esc_html($css_stats['total']); ?></strong> total | 
                <span style="color:green;"><?php echo esc_html($css_stats['found']); ?> available</span> | 
                <span style="color:red;"><?php echo esc_html($css_stats['missing']); ?> missing</span>
            </p>
            <?php self::render_asset_table($css_assets, 'css', $asset_manager); ?>
            
            <h3><?php esc_html_e('JavaScript Assets', 'wp-verifier'); ?></h3>
            <p>
                <strong><?php echo esc_html($js_stats['total']); ?></strong> total | 
                <span style="color:green;"><?php echo esc_html($js_stats['found']); ?> available</span> | 
                <span style="color:red;"><?php echo esc_html($js_stats['missing']); ?> missing</span>
            </p>
            <?php self::render_asset_table($js_assets, 'js', $asset_manager); ?>
        </div>
        <?php
    }

    private static function render_asset_table($assets, $type, $asset_manager) {
        if (empty($assets)) {
            echo '<p>' . esc_html__('No assets found.', 'wp-verifier') . '</p>';
            return;
        }
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'wp-verifier'); ?></th>
                    <th><?php esc_html_e('Category', 'wp-verifier'); ?></th>
                    <th><?php esc_html_e('Purpose', 'wp-verifier'); ?></th>
                    <th><?php esc_html_e('Status', 'wp-verifier'); ?></th>
                    <th><?php esc_html_e('Path', 'wp-verifier'); ?></th>
                    <th><?php esc_html_e('Pages', 'wp-verifier'); ?></th>
                    <th><?php esc_html_e('Dependencies', 'wp-verifier'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($assets as $category => $category_assets):
                    foreach ($category_assets as $name => $asset):
                        $exists = $asset_manager->asset_exists($type, $name);
                        $status_icon = $exists ? 'dashicons-yes' : 'dashicons-no';
                        $status_color = $exists ? 'green' : 'red';
                        $status_text = $exists ? __('Available', 'wp-verifier') : __('Missing', 'wp-verifier');
                ?>
                        <tr>
                            <td><code><?php echo esc_html($name); ?></code></td>
                            <td><?php echo esc_html(ucwords(str_replace('_', ' ', $category))); ?></td>
                            <td><?php echo esc_html($asset['purpose'] ?? ''); ?></td>
                            <td>
                                <span class="dashicons <?php echo esc_attr($status_icon); ?>" style="color:<?php echo esc_attr($status_color); ?>;"></span>
                                <?php echo esc_html($status_text); ?>
                            </td>
                            <td><small><?php echo esc_html($asset['path']); ?></small></td>
                            <td>
                                <?php
                                $pages = $asset['pages'] ?? array();
                                echo esc_html(implode(', ', array_slice($pages, 0, 2)));
                                echo count($pages) > 2 ? '...' : '';
                                ?>
                            </td>
                            <td>
                                <?php
                                $deps = $asset['dependencies'] ?? array();
                                if (!empty($deps)) {
                                    echo esc_html(implode(', ', $deps));
                                }
                                ?>
                            </td>
                        </tr>
                <?php 
                    endforeach;
                endforeach; 
                ?>
            </tbody>
        </table>
        <?php
    }

    private static function get_overall_status($css_stats, $js_stats) {
        $total_missing = $css_stats['missing'] + $js_stats['missing'];
        $total_assets = $css_stats['total'] + $js_stats['total'];
        
        if ($total_missing === 0) {
            return array(
                'type' => 'success',
                'icon' => 'dashicons-yes',
                'message' => __('All Assets Available', 'wp-verifier'),
                'details' => sprintf(__('%d assets managed', 'wp-verifier'), $total_assets)
            );
        } elseif ($total_missing <= 2) {
            return array(
                'type' => 'warning',
                'icon' => 'dashicons-warning',
                'message' => __('Some Assets Missing', 'wp-verifier'),
                'details' => sprintf(__('%1$d of %2$d missing', 'wp-verifier'), $total_missing, $total_assets)
            );
        } else {
            return array(
                'type' => 'error',
                'icon' => 'dashicons-no',
                'message' => __('Many Assets Missing', 'wp-verifier'),
                'details' => sprintf(__('%1$d of %2$d missing', 'wp-verifier'), $total_missing, $total_assets)
            );
        }
    }
}
