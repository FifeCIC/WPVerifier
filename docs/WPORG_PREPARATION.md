# WordPress.org Preparation Configuration

## Overview

The WordPress.org Preparation feature allows you to configure whether WordPress.org-specific checks should be applied to your plugin. This is useful when developing plugins that won't be submitted to WordPress.org (e.g., premium plugins, GitHub-only plugins, or internal tools).

## Features

### Per-Plugin Configuration

Each plugin can have its own configuration stored in its `results.json` file. The configuration includes:

- **wporg_preparation**: Boolean flag to enable/disable WordPress.org specific checks
- **skipped_rules**: Array of rule codes that are skipped when wporg_preparation is disabled
- **ignored_paths**: Array of vendor/library paths to exclude from verification

### WordPress.org Specific Rules

When WordPress.org Preparation is disabled, the following checks are automatically skipped:

1. **hidden_files** - Hidden files like `.phpcs.xml.dist` are not permitted on WordPress.org
2. **application_detected** - Application files detection
3. **plugin_updater_detected** - Custom plugin updater detection (not allowed on WordPress.org)
4. **outdated_tested_upto_header** - Tested up to version requirements
5. **stable_tag_mismatch** - Stable tag must match plugin version
6. **readme_mismatched_header_requires** - Readme header consistency
7. **mismatched_tested_up_to_header** - Tested up to header consistency
8. **missing_direct_file_access_protection** - Direct file access protection (ABSPATH check)

## Usage

### 1. Configure in Preparation Tab

1. Navigate to **WP Verifier → Preparation**
2. Select your plugin from the dropdown
3. The current configuration will be displayed
4. Toggle "Enable WordPress.org specific checks" checkbox
5. Click "Save Configuration"

### 2. Run Verification

When you run verification on a plugin:
- The system automatically reads the saved configuration
- If wporg_preparation is disabled, WordPress.org specific issues are filtered out
- Results are saved with the current configuration

### 3. View Configuration

The configuration is stored in: `wp-content/verifier-results/{plugin-folder}/results.json`

Example structure:
```json
{
  "generated_at": "2026-02-27 15:35:12",
  "plugin": "My Plugin",
  "configuration": {
    "wporg_preparation": false,
    "skipped_rules": [
      "hidden_files",
      "application_detected",
      "plugin_updater_detected",
      "outdated_tested_upto_header",
      "stable_tag_mismatch",
      "readme_mismatched_header_requires",
      "mismatched_tested_up_to_header",
      "missing_direct_file_access_protection"
    ]
  },
  "ignored_paths": [
    {
      "path": "includes/libraries/vendor",
      "reason": "vendor",
      "added_by": "admin",
      "added_at": "2026-02-27 15:34:10"
    }
  ],
  "readiness": {
    "overall": 85,
    "errors": 5,
    "warnings": 2,
    "status": "good"
  },
  "results": { ... }
}
```

## Example Use Cases

### Premium Plugin (Not for WordPress.org)

For a premium plugin that will never be submitted to WordPress.org:
1. Disable WordPress.org Preparation
2. This allows:
   - Hidden files like `.phpcs.xml.dist` for development
   - Custom plugin updater integration
   - Flexible readme.txt requirements
   - No forced ABSPATH checks in every file

### GitHub-Only Plugin

For an open-source plugin distributed via GitHub:
1. Disable WordPress.org Preparation
2. Focus on code quality and security without WordPress.org-specific requirements

### WordPress.org Submission

For plugins intended for WordPress.org:
1. Keep WordPress.org Preparation enabled (default)
2. All WordPress.org requirements will be enforced
3. Ensures compliance before submission

## Technical Details

### Files Modified

- `includes/Utilities/WPOrg_Rules.php` - New utility class for managing WordPress.org specific rules
- `includes/Admin/Admin_AJAX.php` - Added filtering logic and configuration endpoint
- `templates/admin-page-preparation.php` - Added configuration UI
- `assets/js/plugin-check-preparation.js` - Added configuration management
- `assets/js/plugin-check-admin.js` - Updated to read configuration from JSON

### Filter Application

The filtering happens at two levels:

1. **During Check Execution** (`run_checks` method):
   - Reads `wporg_preparation` parameter
   - If disabled, filters results using `WPOrg_Rules::filter_results()`
   - Returns filtered Check_Result object

2. **During Save** (`save_results` method):
   - Stores configuration in JSON
   - Includes list of skipped rules for transparency
   - Configuration persists across verification runs

## Benefits

1. **Flexibility**: Different rules for different plugin types
2. **Transparency**: Configuration is visible and stored with results
3. **Per-Plugin**: Each plugin can have its own settings
4. **Persistent**: Configuration survives across verification runs
5. **Auditable**: Skipped rules are explicitly listed in results

## Future Enhancements

Potential improvements:
- Custom rule selection (pick individual rules to skip)
- Rule presets (e.g., "Premium Plugin", "Internal Tool", "WordPress.org")
- Import/export configuration between plugins
- Global defaults for new plugins
