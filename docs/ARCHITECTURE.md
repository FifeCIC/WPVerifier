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

*This architecture document reflects the intended clean state after ROADMAP.md consolidation tasks are completed.*