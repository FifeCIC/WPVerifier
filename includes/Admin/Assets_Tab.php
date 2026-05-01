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
            require_once WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'assets/Asset_Manager.php';
        }

        $asset_manager = new Asset_Manager();
        $css_assets = self::get_registered_assets('css');
        $js_assets = self::get_registered_assets('js');
        $css_stats = self::get_asset_stats( $css_assets, 'css', $asset_manager );
        $js_stats = self::get_asset_stats( $js_assets, 'js', $asset_manager );
        $overall_status = self::get_overall_status($css_stats, $js_stats);
        ?>
        <div class="wp-verifier-assets-container">
            <div class="notice notice-<?php echo esc_attr( $overall_status['type'] ); ?>">
                <p>
                    <span class="dashicons <?php echo esc_attr($overall_status['icon']); ?>"></span>
                    <strong><?php echo esc_html($overall_status['message']); ?></strong>
                    <?php if (!empty($overall_status['details'])): ?>
                        - <?php echo esc_html($overall_status['details']); ?>
                    <?php endif; ?>
                </p>
            </div>
            
            <h3><?php esc_html_e('CSS Assets', 'wpverifier'); ?></h3>
            <p>
                <strong><?php echo esc_html($css_stats['total']); ?></strong> total | 
                <span style="color:green;"><?php echo esc_html($css_stats['found']); ?> available</span> | 
                <span style="color:red;"><?php echo esc_html($css_stats['missing']); ?> missing</span>
            </p>
            <?php self::render_asset_table($css_assets, 'css', $asset_manager); ?>
            
            <h3><?php esc_html_e('JavaScript Assets', 'wpverifier'); ?></h3>
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
            echo '<p>' . esc_html__('No assets found.', 'wpverifier') . '</p>';
            return;
        }
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'wpverifier'); ?></th>
                    <th><?php esc_html_e('Category', 'wpverifier'); ?></th>
                    <th><?php esc_html_e('Purpose', 'wpverifier'); ?></th>
                    <th><?php esc_html_e('Status', 'wpverifier'); ?></th>
                    <th><?php esc_html_e('Path', 'wpverifier'); ?></th>
                    <th><?php esc_html_e('Pages', 'wpverifier'); ?></th>
                    <th><?php esc_html_e('Dependencies', 'wpverifier'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($assets as $category => $category_assets):
                    foreach ($category_assets as $name => $asset):
                        $exists = $asset_manager->asset_exists($type, $name);
                        $status_icon = $exists ? 'dashicons-yes' : 'dashicons-no';
                        $status_color = $exists ? 'green' : 'red';
                        $status_text = $exists ? __('Available', 'wpverifier') : __('Missing', 'wpverifier');
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
                'message' => __('All Assets Available', 'wpverifier'),
                /* translators: %d: total number of assets */
                'details' => sprintf(__('%d assets managed', 'wpverifier'), $total_assets)
            );
        } elseif ($total_missing <= 2) {
            return array(
                'type' => 'warning',
                'icon' => 'dashicons-warning',
                'message' => __('Some Assets Missing', 'wpverifier'),
                /* translators: %1$d: missing count, %2$d: total count */
                'details' => sprintf(__('%1$d of %2$d missing', 'wpverifier'), $total_missing, $total_assets)
            );
        } else {
            return array(
                'type' => 'error',
                'icon' => 'dashicons-no',
                'message' => __('Many Assets Missing', 'wpverifier'),
                /* translators: %1$d: missing count, %2$d: total count */
                'details' => sprintf(__('%1$d of %2$d missing', 'wpverifier'), $total_missing, $total_assets)
            );
        }
    }

    /**
     * Load registered assets from the asset registry files.
     *
     * @param string $type Asset type (css|js).
     * @return array
     */
    private static function get_registered_assets( $type ) {
        $registry_file = 'css' === $type
            ? WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'assets/style-assets.php'
            : WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'assets/script-assets.php';

        if ( ! file_exists( $registry_file ) ) {
            return array();
        }

        $assets = require $registry_file;

        return is_array( $assets ) ? $assets : array();
    }

    /**
     * Build summary stats for a registry grouped by category.
     *
     * @param array         $assets        Registry data.
     * @param string        $type          Asset type (css|js).
     * @param Asset_Manager $asset_manager Asset manager instance.
     * @return array
     */
    private static function get_asset_stats( array $assets, $type, Asset_Manager $asset_manager ) {
        $total   = 0;
        $found   = 0;
        $missing = 0;

        foreach ( $assets as $category_assets ) {
            if ( ! is_array( $category_assets ) ) {
                continue;
            }

            foreach ( $category_assets as $name => $asset ) {
                ++$total;

                $exists = method_exists( $asset_manager, 'asset_exists' )
                    ? $asset_manager->asset_exists( $type, $name )
                    : self::asset_exists_from_registry( $asset );

                if ( $exists ) {
                    ++$found;
                } else {
                    ++$missing;
                }
            }
        }

        return array(
            'total'   => $total,
            'found'   => $found,
            'missing' => $missing,
        );
    }

    /**
     * Fallback existence check when helper methods are unavailable.
     *
     * @param array $asset Asset data.
     * @return bool
     */
    private static function asset_exists_from_registry( $asset ) {
        if ( ! is_array( $asset ) || empty( $asset['path'] ) || ! is_string( $asset['path'] ) ) {
            return false;
        }

        $relative_path = ltrim( $asset['path'], '/\\' );
        $absolute_path = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'assets/' . $relative_path;

        return file_exists( $absolute_path );
    }
}
