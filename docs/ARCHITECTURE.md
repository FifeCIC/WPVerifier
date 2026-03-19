# WP Verifier Architecture (WIP)

## Core Purpose
**WP Verifier** scans WordPress plugins for code quality issues and provides tools to manage, track, and resolve them efficiently.

## Target Architecture (Clean Approach)

### Three-File JSON System
1. **`.wpv-config.json`** - Plugin configuration (WordPress.org prep, vendor exclusions)
2. **`.wpv-results.json`** - Scan results only (issues, readiness scores)
3. **`.wpv-verification.json`** - Verification tracking (file hashes, verification status)

### Core Storage Classes
- `Config_Storage` - Manages `.wpv-config.json`
- `Results_Storage` - Manages `.wpv-results.json`
- `JSON_Storage` - Manages `.wpv-verification.json`

### AJAX Handler Classes (PENDING)
- `Verification_AJAX_Handler` - Scan execution (~300 lines)
- `Results_AJAX_Handler` - Results management (~400 lines)
- `Config_AJAX_Handler` - Configuration management (~200 lines)
- `Hash_AJAX_Handler` - Hash operations (~150 lines)

---

## Current State vs Target

### ❌ Current Issues
- **Monolithic Admin_AJAX.php** (3,000+ lines)
- **Duplicate configuration** in both results.json AND config.json
- **Direct JSON operations** bypassing storage classes
- **Mixed responsibilities** in single files
- **JavaScript direct file access** instead of AJAX endpoints

### ✅ Target Clean Architecture
- **Focused AJAX handlers** (4 classes, ~1,050 total lines)
- **Single source of truth** for each data type
- **Consistent storage class usage** throughout
- **Clear separation of concerns**
- **AJAX-only data access** from frontend

---

## Data Flow (Target)

### 1. Configuration Phase
```
User → Config Tab → Config_AJAX_Handler → Config_Storage → .wpv-config.json
```

### 2. Hash Generation Phase
```
User → Hash Tab → Hash_AJAX_Handler → JSON_Storage → .wpv-verification.json
```

### 3. Verification Phase
```
User → Verification Tab → Verification_AJAX_Handler → Results_Storage → .wpv-results.json
```

### 4. Results Management
```
User → Results Tab → Results_AJAX_Handler → Results_Storage → Display
```

---

## File Ignoring Architecture

### Current Implementation (Multi-Layer Filtering)

**Layer 1: File Discovery & Filtering**
- `Abstract_PHP_CodeSniffer_Check::get_files_to_scan()` - Determines scan scope
- `Abstract_PHP_CodeSniffer_Check::get_php_files()` - **Only called with issue limiting + full plugin scan**
- `Plugin_Request_Utility::get_directories_to_ignore()` - Core WordPress ignore patterns
- `Plugin_Request_Utility::get_files_to_ignore()` - Core WordPress ignore files

**Layer 2: PHPCS Internal Filtering**
- `get_argv_defaults()` - Passes `--ignore` patterns to PHPCS
- PHPCS processes ignore patterns during file discovery
- **Primary filtering mechanism for most scans**

**Layer 3: Config-Based Filtering**
- `.wpv-config.json` ignored_paths - User-defined ignore patterns
- **Currently only applied in get_php_files() method**
- **Not applied to PHPCS --ignore patterns**

### Current Problem: Inconsistent Filtering

**Issue**: Config ignored paths only work with:
- Issue limiting enabled (limit to 20 issues)
- Full plugin directory scan (not individual files)
- Otherwise, PHPCS handles file discovery and ignores config

**Root Cause**: Two separate file discovery paths:
1. **PHPCS Internal** (most common): Uses `--ignore` patterns only
2. **Manual Discovery** (rare): Uses `get_php_files()` with full filtering

### Target Architecture: Unified Filtering

**Proposed Solution**: Always apply config ignored paths to PHPCS `--ignore` patterns

```php
// In get_argv_defaults()
$ignore_patterns = array();

// Layer 1: Core WordPress patterns
$directories_to_ignore = Plugin_Request_Utility::get_directories_to_ignore();
$files_to_ignore = Plugin_Request_Utility::get_files_to_ignore();

// Layer 2: Config-based patterns (NEW)
$config_ignored_paths = $this->get_config_ignored_paths($plugin_path);
$directories_to_ignore = array_merge($directories_to_ignore, $config_ignored_paths);

// Apply to PHPCS --ignore
if (!empty($directories_to_ignore)) {
    $ignore_patterns[] = '*/' . implode('/*,*/', $directories_to_ignore) . '/*';
}
```

### Future Enhancement: Individual File Skipping

**Planned Feature**: Skip individual files based on `.wpv-verification.json`

**Implementation Points**:
- `get_files_to_scan()` - Skip verified files with matching hashes
- `get_php_files()` - Skip verified files during manual discovery
- File-level verification status tracking
- Hash-based change detection for individual files

**Data Structure** (`.wpv-verification.json`):
```json
{
  "file_level": {
    "path/to/file.php": {
      "verified_by": "admin",
      "verified_at": "2026-03-18T20:00:00Z",
      "hash": "abc123",
      "skip_scan": true
    }
  }
}
```

---

## Verification Process Flow

### Step 1: Scan Initiation
- User triggers verification via AJAX
- `Verification_AJAX_Handler::handle_verification_request()`
- Loads configuration from `.wpv-config.json`

### Step 2: File Filtering (Multi-Path)

**Path A: Full Plugin Scan (Default)**
```
Abstract_PHP_CodeSniffer_Check::run()
├── get_files_to_scan() → Returns plugin directory
├── get_argv_defaults() → Builds PHPCS --ignore patterns
│   ├── Plugin_Request_Utility patterns (core)
│   └── Config ignored paths (MISSING - needs fix)
└── PHPCS Runner → Internal file discovery with --ignore
```

**Path B: Issue Limited Scan (Testing Feature)**
```
Abstract_PHP_CodeSniffer_Check::run()
├── get_files_to_scan() → Returns plugin directory
├── run_with_issue_limit()
│   ├── get_php_files() → Manual file discovery
│   │   ├── Plugin_Request_Utility patterns ✓
│   │   └── Config ignored paths ✓
│   └── Process files individually until limit reached
└── Early termination when 20 issues found
```

**Path C: Changed Files Only (Hash-Based)**
```
Abstract_PHP_CodeSniffer_Check::run()
├── get_files_to_scan() → Returns specific changed files
├── Hash comparison with .wpv-results.json
├── get_argv_defaults() → No --ignore (specific files)
└── PHPCS Runner → Scans only changed files
```

### Step 3: PHPCS Execution
- `run_phpcs_on_files()` - Executes PHPCS with configured arguments
- PHPCS processes files according to ignore patterns
- Results returned in JSON format

### Step 4: Hash Generation
- `Hash_Generator::generate_file_hash()` - Creates file hashes for tracking
- Hashes stored for future change detection
- Silent operation (non-breaking)

### Step 5: Results Processing
- Parse PHPCS JSON output
- Add results to `Check_Result` object
- Apply early termination if issue limit reached
- Store results in `.wpv-results.json`

---

## File Responsibilities (Target)

### `.wpv-config.json` (PENDING: Remove duplicates)
```json
{
  "configuration": {
    "wporg_preparation": true,
    "skipped_rules": []
  },
  "ignored_paths": [
    {"path": "vendor/", "reason": "vendor", "added_by": "admin"}
  ]
}
```

### `.wpv-results.json` (PENDING: Remove config duplication)
```json
{
  "generated_at": "2026-03-04 21:17:15",
  "plugin": "Plugin Name",
  "readiness": {"overall": 85, "errors": 5, "warnings": 12},
  "results": {
    "file.php": [
      {"issue_id": "E-abc123", "message": "Issue", "line": 42}
    ]
  }
}
```

### `.wpv-verification.json` (PENDING: Remove hash duplication)
```json
{
  "version": "1.0",
  "file_hashes": {"file.php": "abc12345"},
  "function_hashes": {"file.php": {"func": "def67890"}},
  "file_level": {"file.php": {"verified_by": "admin"}},
  "function_level": {"Class::method": {"verified_by": "admin"}}
}
```

---

## Clean Usage Patterns (Target)

### Configuration Management
```php
// CURRENT (direct file access)
$data = json_decode(file_get_contents('.wpv-results.json'), true);

// TARGET (storage class)
$storage = new Config_Storage($plugin);
$config = $storage->load_config_data();
```

### Results Management
```php
// CURRENT (mixed in Admin_AJAX.php)
file_put_contents($json_file, wp_json_encode($data));

// TARGET (dedicated handler)
$handler = new Results_AJAX_Handler();
$handler->save_results($data);
```

### Frontend Data Access
```javascript
// CURRENT (direct JSON fetch)
fetch('/plugins/plugin/.wpv-results.json')

// TARGET (AJAX endpoint)
fetch(ajaxurl, {action: 'wpv_load_results', plugin: 'plugin'})
```

---

---

## Debug Analysis: Ignored Paths Issue

### Current Status
**Problem**: Config ignored paths from `.wpv-config.json` not being respected

**Debug Findings**:
- Config loading works correctly: `includes/libraries/carbon-fields, includes/libraries/action-scheduler`
- No debug output from `get_php_files()` method during scan
- Indicates normal scan path (Path A) is being used, not issue-limited path (Path B)
- TAB12-09A in admin interface shows validation logic (post-scan analysis), not actual filtering process

**Root Cause**: Config ignored paths only applied in `get_php_files()` method, which is only called during issue-limited scans. Normal scans use PHPCS internal file discovery with `--ignore` patterns that don't include config paths.

**Solution Required**: Modify `get_argv_defaults()` to include config ignored paths in PHPCS `--ignore` patterns for all scan types.

### Implementation Priority
1. **Immediate**: Fix config ignored paths for all scan types
2. **Future**: Implement individual file skipping based on verification status
3. **Enhancement**: Unified file filtering architecture

---

*This architecture document reflects the intended clean state after ROADMAP.md consolidation tasks are completed.*