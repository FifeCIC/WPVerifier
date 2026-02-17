<?php
/**
 * WP Verifier Asset Management
 *
 * @package wp-verifier
 */

namespace WordPress\Plugin_Check\Assets;

if (!defined('ABSPATH')) {
    exit;
}

class Asset_Manager {
    
    private $assets_dir;
    private $assets_url;
    private $assets = array();
    private $missing_assets = array();
    
    public function __construct() {
        $this->assets_dir = WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'assets/';
        $this->assets_url = WP_PLUGIN_CHECK_PLUGIN_DIR_URL . 'assets/';
        $this->init_assets();
    }
    
    private function init_assets() {
        $this->assets = array(
            'css' => require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'assets/style-assets.php',
            'js' => require WP_PLUGIN_CHECK_PLUGIN_DIR_PATH . 'assets/script-assets.php'
        );
    }
    
    public function get_asset_url($type, $name) {
        if (!isset($this->assets[$type])) {
            return false;
        }
        
        foreach ($this->assets[$type] as $category => $assets) {
            if (isset($assets[$name])) {
                $path = $this->assets_dir . $assets[$name]['path'];
                
                if (!file_exists($path)) {
                    $this->log_missing_asset($type, $name, $path);
                    return false;
                }
                
                return $this->assets_url . $assets[$name]['path'];
            }
        }
        
        return false;
    }
    
    public function get_all_assets($type = null) {
        if ($type && isset($this->assets[$type])) {
            return $this->assets[$type];
        }
        
        return $this->assets;
    }
    
    public function get_assets_by_page($page) {
        $page_assets = array();
        
        foreach (array('css', 'js') as $type) {
            if (!isset($this->assets[$type])) continue;
            
            foreach ($this->assets[$type] as $category => $assets) {
                foreach ($assets as $name => $asset) {
                    if (!isset($asset['pages'])) continue;
                    
                    if (in_array('all', $asset['pages']) || in_array($page, $asset['pages'])) {
                        $page_assets[$type][$name] = $asset;
                    }
                }
            }
        }
        
        return $page_assets;
    }
    
    public function asset_exists($type, $name) {
        if (!isset($this->assets[$type])) {
            return false;
        }
        
        foreach ($this->assets[$type] as $category => $assets) {
            if (isset($assets[$name])) {
                $path = $this->assets_dir . $assets[$name]['path'];
                return file_exists($path);
            }
        }
        
        return false;
    }
    
    private function log_missing_asset($type, $name, $path) {
        $this->missing_assets[] = array(
            'type' => $type,
            'name' => $name,
            'path' => $path,
            'time' => current_time('mysql')
        );
    }
    
    public function get_missing_assets() {
        return $this->missing_assets;
    }
    
    public function has_missing_assets() {
        return !empty($this->missing_assets);
    }
    
    public function get_asset_stats($type) {
        $total = 0;
        $found = 0;
        $missing = 0;
        
        if (!isset($this->assets[$type])) {
            return array('total' => 0, 'found' => 0, 'missing' => 0);
        }
        
        foreach ($this->assets[$type] as $category => $assets) {
            foreach ($assets as $name => $asset) {
                $total++;
                $path = $this->assets_dir . $asset['path'];
                
                if (file_exists($path)) {
                    $found++;
                } else {
                    $missing++;
                }
            }
        }
        
        return array('total' => $total, 'found' => $found, 'missing' => $missing);
    }
}
