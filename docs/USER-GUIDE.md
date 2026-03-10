# WP Verifier User Guide

## Quick Start Workflow

### 1. Select Plugin
- Navigate to **WP Verifier → Select Plugin**
- Choose your plugin from the dropdown
- Click "Set as Active Plugin"
- Verify WPV files status shows your plugin configuration

### 2. Configure Settings
- Go to **Configure** tab
- Click "Load Configuration" to see current settings
- Choose plugin distribution type (WordPress.org, GitHub, or Other)
- Select vendor folders to exclude from scanning
- Click "Save Configuration" to create `.wpv-config.json`

### 3. Generate File Hashes (Recommended)
- Go to **Hash Generation** tab
- Click "Generate File Hashes" to create baseline
- This enables incremental scanning and change detection
- Creates `.wpv-verification.json` for tracking

### 4. Run Verification
- Go to **Advanced Verification** tab
- Click "Run Verification" to scan your plugin
- Results are saved to `.wpv-results.json`
- Review issues in **Files** and **Issues** tabs

### 5. Manage Issues
- Use ignore rules to filter out false positives
- Mark issues as resolved when fixed
- Export results for documentation

---

## File Hash System

### What are File Hashes?
File hashes create a unique fingerprint for each file and function in your plugin. This enables:
- **Incremental scanning** - only check files that have changed
- **Issue tracking** - link problems to specific code versions
- **Change detection** - identify what code has been modified
- **Ignore management** - track which issues have been reviewed

### Hash Generation Process
1. Navigate to **Hash Generation** tab
2. Ensure your plugin is selected as active
3. Click "Generate File Hashes"
4. System creates SHA256 hashes for all PHP files
5. Hashes are stored in `.wpv-verification.json`
6. Use "Validate Existing Hashes" to check for changes

---

## WordPress.org Preparation Configuration

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
