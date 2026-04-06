<?php
/**
 * Template Helper - Common template functions
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Utilities;

if (!defined('ABSPATH')) {
    exit;
}

class Template_Helper {
    
    /**
     * Get available plugins with metadata
     */
    public static function get_available_plugins() {
        $available_plugins = array();
        if (function_exists('get_plugins')) {
            $all_plugins = get_plugins();
            foreach ($all_plugins as $plugin_basename => $plugin_data) {
                $available_plugins[$plugin_basename] = $plugin_data;
            }
        }
        return $available_plugins;
    }
    
    /**
     * Get current selected plugin
     */
    public static function get_current_plugin() {
        return get_user_meta(get_current_user_id(), 'wpv_last_selected_plugin', true);
    }
    
    /**
     * Validate plugin files exist
     */
    public static function validate_plugin_files($plugin_basename) {
        if (!$plugin_basename) return false;
        
        $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin_basename);
        return is_dir($plugin_dir) && file_exists(WP_PLUGIN_DIR . '/' . $plugin_basename);
    }
    
    /**
     * Check if files exist for plugin operations
     */
    public static function wpv_check_files_exist($plugin_basename) {
        if (!self::validate_plugin_files($plugin_basename)) {
            return array('status' => 'error', 'message' => 'Plugin files not found');
        }
        
        $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($plugin_basename);
        $config_file = $plugin_dir . '/.wpv-config.json';
        $results_file = $plugin_dir . '/.wpv-results.json';
        $verification_file = $plugin_dir . '/.wpv-verification.json';
        
        return array(
            'status' => 'success',
            'files' => array(
                'config' => file_exists($config_file),
                'results' => file_exists($results_file),
                'verification' => file_exists($verification_file)
            )
        );
    }
    
    /**
     * Render plugin selection dropdown
     */
    public static function render_plugin_selector($current_plugin = null, $available_plugins = null) {
        if (!$available_plugins) {
            $available_plugins = self::get_available_plugins();
        }
        
        if (!$current_plugin) {
            $current_plugin = self::get_current_plugin();
        }
        
        if (empty($available_plugins)) {
            return '<p style="color: #d63638;">' . esc_html__('No plugins found.', 'wpverifier') . '</p>';
        }
        
        ob_start();
        ?>
        <select name="plugin" style="min-width: 300px;">
            <option value=""><?php esc_html_e('-- Select Plugin --', 'wpverifier'); ?></option>
            <?php foreach ($available_plugins as $basename => $data) : ?>
                <option value="<?php echo esc_attr($basename); ?>" <?php selected($current_plugin, $basename); ?>>
                    <?php echo esc_html($data['Name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render status messages
     */
    public static function render_status_messages() {
        $messages = array();
        
        if (isset($_GET['added']) && '1' === $_GET['added']) {
            $messages[] = array('type' => 'success', 'message' => __('Operation completed successfully.', 'wpverifier'));
        }
        if (isset($_GET['removed']) && '1' === $_GET['removed']) {
            $messages[] = array('type' => 'success', 'message' => __('Item removed successfully.', 'wpverifier'));
        }
        if (isset($_GET['imported']) && '1' === $_GET['imported']) {
            $messages[] = array('type' => 'success', 'message' => __('Data imported successfully.', 'wpverifier'));
        }
        if (isset($_GET['error'])) {
            $messages[] = array('type' => 'error', 'message' => sanitize_text_field($_GET['error']));
        }
        
        foreach ($messages as $message) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($message['type']),
                esc_html($message['message'])
            );
        }
    }
    
    /**
     * Render page header with plugin context
     */
    public static function render_page_header($title, $description = '', $current_plugin = null, $available_plugins = null) {
        if (!$available_plugins) {
            $available_plugins = self::get_available_plugins();
        }
        
        if (!$current_plugin) {
            $current_plugin = self::get_current_plugin();
        }
        
        $page_title = $title;
        if ($current_plugin && isset($available_plugins[$current_plugin])) {
            $page_title = sprintf('%s - %s', $title, $available_plugins[$current_plugin]['Name']);
        }
        
        printf('<h2>%s</h2>', esc_html($page_title));
        
        if ($description) {
            printf('<p>%s</p>', esc_html($description));
        }
        
        self::render_status_messages();
    }
}