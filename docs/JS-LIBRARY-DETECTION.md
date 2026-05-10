# JavaScript Library Detection Enhancement

## Overview
WP Verifier now detects JavaScript libraries in addition to traditional vendor directories. This enhancement helps identify outdated or vulnerable JS libraries that WordPress.org reviewers care about.

## What Was Added

### 1. New Files Created
- `includes/Utilities/JS_Library_Signatures.php` - Library signature patterns for 20+ common JS libraries
- `includes/Admin/JS_Library_Detector.php` - Scans JS files and matches against signatures

### 2. Enhanced Files
- `includes/Utilities/Vendor_Patterns.php` - Added asset directory patterns (assets/js/libs, assets/js/vendor, etc.)
- `includes/Admin/Vendor_Detector.php` - Now scans asset directories in addition to vendor folders
- `includes/Admin/Config_AJAX_Handler.php` - Returns JS library detection results in AJAX responses
- `templates/admin-page-configuration.php` - Displays detected JS libraries with warning box

## Detected Libraries

### jQuery Plugins
- jQuery BlockUI
- jQuery UI
- jQuery Validation
- jQuery DataTables
- jQuery Chosen
- Select2
- Slick Slider
- Owl Carousel
- Magnific Popup
- jQuery Migrate

### Standalone Libraries
- Lodash
- Underscore.js
- Moment.js
- Chart.js
- D3.js
- Mermaid
- Axios
- Popper.js
- Tippy.js
- SweetAlert
- Toastr

## How It Works

### Detection Method
1. Scans all `.js` files in plugin directory recursively
2. Reads first 50KB of each file for performance
3. Matches against regex patterns for each library
4. Extracts version numbers when available
5. Returns library name, file path, and version

### Signature Patterns
Each library has multiple detection patterns:
- Version comment patterns: `/Select2\s+(\d+\.\d+\.\d+)/`
- Object declaration patterns: `/jquery\.fn\.select2/`
- License header patterns: `/@license.*select2/i`

### Asset Directory Patterns
Now scans these additional directories:
- `assets/js/libs`
- `assets/js/vendor`
- `assets/js/libraries`
- `assets/libraries`
- `assets/vendor`
- `js/vendor`
- `js/libs`
- `js/libraries`
- `lib`
- `libs`

## User Interface

### Configuration Tab (TAB02)
When JS libraries are detected, a yellow warning box displays:
- Library name
- File path(s)
- Version number (if detected)
- Suggestion to exclude or update

### AJAX Response
The `wpv_detect_vendors` AJAX call now returns:
```json
{
  "vendors": [...],
  "js_libraries": {
    "select2": {
      "name": "Select2",
      "files": [
        {
          "path": "assets/js/select2/select2.min.js",
          "version": "3.5.4"
        }
      ]
    }
  }
}
```

## Testing

### Manual Test
1. Navigate to WP Verifier → Configure tab
2. Select a plugin with JS libraries
3. Click "Detect Vendor Folders"
4. Check for yellow warning box showing detected libraries

### Command Line Test
Run the test script:
```bash
php wp-content/plugins/wpverifier/test-js-detection.php
```

### Expected Results for TradePress
Should detect:
- Select2 v3.5.4 in `assets/js/select2/select2.min.js`
- jQuery BlockUI in `assets/js/jquery-blockui/jquery.blockUI.js`
- Mermaid in `assets/js/libs/mermaid.min.js`

## Benefits

### For Plugin Developers
- Identifies outdated libraries before WordPress.org submission
- Highlights security vulnerabilities in bundled JS
- Suggests which directories to exclude from verification

### For WordPress.org Review
- Addresses reviewer concerns about third-party code
- Provides version information for security assessment
- Helps distinguish plugin code from library code

### For WP Verifier
- More comprehensive library detection
- Better false positive reduction
- Improved ignore rule suggestions

## Future Enhancements

### Phase 2 (Planned)
- CVE database integration for vulnerability checking
- Latest version comparison and update suggestions
- Automatic ignore rule generation for detected libraries
- Library license detection and compatibility checking

### Phase 3 (Planned)
- CSS library detection (Bootstrap, Foundation, etc.)
- PHP library detection improvements
- Dependency tree visualization
- Automated library update notifications

## API Usage

### Detect JS Libraries Programmatically
```php
use WordPress\Plugin_Check\Admin\JS_Library_Detector;

$plugin_slug = 'my-plugin/my-plugin.php';
$libraries = JS_Library_Detector::detect_libraries($plugin_slug);

foreach ($libraries as $key => $library) {
    echo $library['name'] . "\n";
    foreach ($library['files'] as $file) {
        echo "  {$file['path']} v{$file['version']}\n";
    }
}
```

### Add Custom Library Signatures
Edit `includes/Utilities/JS_Library_Signatures.php`:
```php
'my-library' => array(
    'patterns' => array(
        '/MyLibrary.*(\d+\.\d+\.\d+)/',
        '/window\.MyLib\s*=/',
    ),
    'name' => 'My Custom Library',
),
```

## Performance Considerations

- Only scans first 50KB of each JS file
- Uses efficient regex patterns
- Caches results in AJAX responses
- Skips binary and non-JS files

## Compatibility

- WordPress 5.0+
- PHP 7.4+
- Works with minified and unminified JS files
- Handles both development and production builds

## Related Issues

This enhancement addresses:
- TradePress WordPress.org review feedback about undocumented libraries
- WP Verifier improvement strategy for better library detection
- Gap where libraries in assets/ directories weren't detected

## Credits

Developed as part of the WP Verifier improvement strategy based on real-world WordPress.org plugin review feedback.
