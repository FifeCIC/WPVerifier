# Technical Architecture

## 🔌 WPSeed Integration
We will leverage the **WPSeed Boilerplate** for the plugin foundation.
- **Admin UI**: Use WPSeed's admin page structure.
- **Table Component**: Extend the WPSeed List Table (or React equivalent) for the Error Dashboard.
- **Settings**: Use WPSeed's settings API for configuring "Library Paths" and file monitoring.

## 📁 File Monitoring System

### Architecture
The file monitoring system runs independently but feeds into the main validation pipeline.

**Components**:
1. **File Watcher**: Tracks file timestamps in monitored plugin
2. **Structure Validator**: Checks for required files and folders
3. **File Generator**: Creates missing files from templates
4. **Monitor Dashboard**: Displays file status and validation results

**Data Flow**:
```
File Save Event
    ↓
Timestamp Change Detection (2-5s delay)
    ↓
Structure Validation
    ↓
Database Update (wp_wpguardrail_file_monitor)
    ↓
Dashboard Refresh
    ↓
Developer Alert
```

**Required Files Checked**:
- Language folder (`/languages` or `/lang`)
- Language files (`.pot`, `.po`, `.mo`)
- License file (`LICENSE`, `LICENSE.txt`, `LICENSE.md`)
- README file (`README.md`, `readme.txt`)
- Plugin header (main plugin file with WordPress headers)

**Database Tables**:
- `wp_wpguardrail_file_monitor`: Current file status and timestamps
- `wp_wpguardrail_file_history`: Historical file change log

## 🧠 The Logic Engine

### 1. The Scanner Wrapper
Instead of reinventing the wheel, we invoke the existing Plugin Check classes.
```php
// Pseudo-code concept
$checker = new WP_Plugin_Check_Runner();
$results = $checker->run( 'my-plugin-slug' );
// $results is a massive array of objects
```

### 2. The Processor (The "Brain")
Before displaying results, pass them through a `ResultProcessor` class.
- **Input**: Raw Error List
- **Process**:
    1. Check against `IgnoreRules` database.
    2. Check against `LibraryPaths` configuration.
    3. Apply "Severity Weighting" (Security > Warning > Notice).
- **Output**: Cleaned, grouped dataset for the UI.

### 3. Data Storage
We need persistent storage for "Smart Ignores" that is more complex than a simple text file.

**Table: `wp_pcm_ignore_rules`**
- `id`
- `rule_type` (file_path, error_code, regex_pattern)
- `value` (e.g., `includes/libs/*`)
- `scope` (global, project_specific)
- `reason` (User comment: "False positive because...")

**Table: `wp_pcm_scan_history`**
- `id`
- `scan_date`
- `error_count`
- `warning_count`
- `raw_data` (JSON blob for diffing)

## 🛡️ Wrapper Verification Strategy
This is the unique selling point.
1. **Identify Library Class**: User marks `My_Library` as external.
2. **Scan Usage**: We parse *our* code for `My_Library::get_data()`.
3. **Check Context**: Is `echo My_Library::get_data()` wrapped in `esc_html()`?
   - **YES**: Pass.
   - **NO**: Critical Error (even if the library itself is ignored).