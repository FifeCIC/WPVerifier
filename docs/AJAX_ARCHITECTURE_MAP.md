# AJAX Architecture Map

## Purpose
This document maps all AJAX actions to their handlers to prevent duplication and ensure proper architecture.

## AJAX Actions & Handlers

### Results Management (Results_AJAX_Handler)
- `plugin_check_save_results` → `save_results()`
- `plugin_check_load_results` → `load_results()`
- `plugin_check_list_saved_results` → `list_saved_results()`
- `plugin_check_export_results` → `export_results()`
- `plugin_check_delete_results` → `delete_results()`
- `wpv_recheck_file` → `recheck_file()`

### Verification (Verification_AJAX_Handler)
- `wpv_run_checks` → `run_checks()`
- `plugin_check_clean_up_environment` → `clean_up_environment()`
- `plugin_check_set_up_environment` → `set_up_environment()`
- `plugin_check_get_checks_to_run` → `get_checks_to_run()`
- `plugin_check_run_checks` → `run_checks()`
- `plugin_check_basic_check` → `basic_check()`
- `plugin_check_validate_structure` → `validate_structure()`

### Configuration (Config_AJAX_Handler)
- Configuration-related AJAX actions

### Hash Management (Hash_AJAX_Handler)
- Hash-related AJAX actions

### Legacy Actions (AJAX_Handler_Manager)
- `plugin_check_domains` → `check_domains()`
- `plugin_check_save_name` → `save_name()`
- `plugin_check_get_saved_names` → `get_saved_names()`
- `plugin_check_name_conflicts` → `check_conflicts()`
- `plugin_check_analyze_seo` → `analyze_seo()`
- `plugin_check_check_trademarks` → `check_trademarks()`
- `plugin_check_start_monitoring` → `start_monitoring()`
- `plugin_check_stop_monitoring` → `stop_monitoring()`
- `plugin_check_file_changes` → `check_file_changes()`
- `plugin_check_monitor_log` → `get_monitor_log()`
- `plugin_check_mark_complete` → `mark_complete()`
- `plugin_check_add_ignore_rule` → `add_ignore_rule()`
- `plugin_check_add_ignore_directory` → `add_ignore_directory()`
- `wpv_mark_resolved` → `mark_resolved()` ⭐ **FIXED BUTTON HANDLER**
- `wpv_mark_ignored` → `mark_ignored()` ⭐ **IGNORE BUTTON HANDLER**
- `plugin_check_get_scan_history` → `get_scan_history()`
- `plugin_check_clear_scan_history` → `clear_scan_history()`
- `plugin_check_generate_report` → `generate_report()`
- `save_user_meta` → `save_user_meta()`
- `wpv_get_mark_fixed_nonce` → `get_mark_fixed_nonce()`

## JavaScript Localization Map

### Global Scripts
- `PLUGIN_CHECK` - Available on all pages via `plugin-check-admin.js`

### Tab-Specific Localizations
- **Preparation Tab**: `wpv_ajax_object` (via `wpv-ajax` script)
- **Verification Tab**: `wpv_ajax_object` (via `wpv-ajax` script)
- **Issues Tab**: `wpv_ajax_object` (via `wpv-issues-tab` script) ⭐ **ADDED**
- **Monitoring Tab**: `PluginMonitorConfig` (via `plugin-monitoring` script)
- **Saved Results Tab**: `wpvSavedData`, `wpvConfig` (via `admin-page-saved` script)
- **Settings Tab**: `pluginCheckSettings` (via `plugin-check-admin-settings` script)

## Fixed Button Implementation

### JavaScript Implementations

#### Issues Tab (issues-tab.js lines 60+)
```javascript
$(document).on('click', '.wpv-fixed-link', function(e) {
    // Uses wpv_ajax_object.ajax_url, wpv_ajax_object.nonce
    // Calls action: 'wpv_mark_resolved'
});
```

#### Saved Results Tab (plugin-check-saved.js line ~216)
```javascript
$('.wpv-fixed-btn').on('click', function(e) {
    // Uses wpvConfig.nonce
    // Calls action: 'wpv_mark_resolved'
});

$('.wpv-ignore-btn').on('click', function(e) {
    // Uses wpvConfig.nonce  
    // Calls action: 'wpv_mark_ignored'
});
```

### PHP Handlers (AJAX_Handler_Manager)
```php
public function mark_resolved() {
    // Validates nonce using 'plugin-check-run-checks'
    // Updates .wpv-results.json file
    // Marks issue as resolved with timestamp and user
}

public function mark_ignored() {
    // Validates nonce using 'plugin-check-run-checks'
    // Updates .wpv-results.json file  
    // Marks issue as ignored with timestamp and user
}
```

### Template Usage
- **TAB04 (Issues)**: `admin-page-issues.php` - Contains `.wpv-fixed-link` buttons
- **TAB03 (Saved Results)**: Dynamic content in `plugin-check-saved.js` - Contains `.wpv-fixed-btn` and `.wpv-ignore-btn` buttons
- **TAB05 (Monitoring)**: Currently no Fixed buttons found

## Rules for Future Development

1. **Before adding AJAX handlers**: Check this map first
2. **Before adding JavaScript**: Check localization map
3. **When adding new actions**: Update this document
4. **When consolidating**: Use existing handlers, don't duplicate

## Nonce Usage
- Most handlers use: `plugin-check-run-checks` nonce
- Settings use: `plugin_check_get_models` nonce
- Mark fixed uses: `wpv_mark_fixed` nonce (legacy)

**Current nonce for Fixed button**: `plugin-check-run-checks` (via `$this->ajax_manager->get_nonce()`)