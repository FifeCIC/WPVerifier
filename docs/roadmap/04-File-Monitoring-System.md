# File Monitoring & Integrity System

## Overview
The File Monitoring System is a development-time tool that watches a selected plugin for file changes and validates plugin structure. It alerts developers to issues in real-time while they're actively developing.

**Key Principle**: Assume file saves are intentional lasting changes, but provide a grace period for the developer to continue working.

---

## Core Features

### 1. Real-Time File Watcher
**Purpose**: Monitor selected plugin directory for file modifications.

**Behavior**:
- Tracks file timestamps in the monitored plugin directory
- Detects when files are saved/modified
- Implements 2-5 second delay before validation (configurable)
- Prevents false alerts during active development sessions

**Configuration**:
- Select which plugin to monitor (dropdown of installed plugins)
- Set delay threshold (default: 3 seconds)
- Enable/disable monitoring via toggle

**Storage**:
- Store last known file timestamps in transients
- Track file modification history in custom table

### 2. Plugin Structure Validation
**Purpose**: Ensure plugin has all required files and folders.

**Required Elements**:
- **Language Folder**: `/languages` or `/lang` directory
- **Language Files**: `.pot` (template), `.po` (translations), `.mo` (compiled)
- **License File**: `LICENSE`, `LICENSE.txt`, or `LICENSE.md`
- **README File**: `README.md` or `readme.txt`
- **Plugin Header**: Main plugin file with proper WordPress headers

**Validation Rules**:
```
✓ Language folder exists
✓ At least one .pot file present
✓ License file present
✓ README file present
✓ Main plugin file has required headers
```

**Alert Levels**:
- **Critical**: Missing main plugin file or headers
- **Warning**: Missing language folder or license
- **Info**: Missing README or language files

### 3. File Creation Wizard
**Purpose**: Auto-generate missing files with templates.

**Offered Actions**:
- Create language folder
- Generate `.pot` template file
- Create LICENSE file (with template options: MIT, GPL-2.0, etc.)
- Create README.md with plugin information
- Add missing WordPress headers to main plugin file

**Templates**:
- **LICENSE**: Pre-populated with common licenses
- **README.md**: Includes plugin name, description, installation, usage sections
- **.pot file**: Generated from plugin code using WordPress i18n tools
- **Language folder**: Created with proper structure

### 4. Real-Time Dashboard
**Purpose**: Display file status and validation results.

**Dashboard Elements**:
- **File List**: All files in monitored plugin with last modified times
- **Status Indicators**: 
  - ✓ Valid/Present
  - ✗ Missing/Invalid
  - ⚠ Warning
- **Recent Changes**: Timeline of file modifications
- **Validation Results**: Current structure validation status
- **Quick Actions**: Create missing files, refresh scan, change monitored plugin

---

## Technical Implementation

### Database Tables

**Table: `wp_wpguardrail_file_monitor`**
```sql
CREATE TABLE wp_wpguardrail_file_monitor (
    id BIGINT(20) NOT NULL AUTO_INCREMENT,
    plugin_slug VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_hash VARCHAR(64),
    last_modified DATETIME,
    last_checked DATETIME,
    status VARCHAR(20), -- 'valid', 'missing', 'warning'
    PRIMARY KEY (id),
    UNIQUE KEY plugin_file (plugin_slug, file_path),
    KEY plugin_idx (plugin_slug)
);
```

**Table: `wp_wpguardrail_file_history`**
```sql
CREATE TABLE wp_wpguardrail_file_history (
    id BIGINT(20) NOT NULL AUTO_INCREMENT,
    plugin_slug VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    action VARCHAR(50), -- 'created', 'modified', 'deleted'
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY plugin_idx (plugin_slug),
    KEY timestamp_idx (timestamp)
);
```

### Core Classes

**WPGuardrail_File_Watcher**
- Monitors file timestamps
- Detects changes with configurable delay
- Triggers validation on file save

**WPGuardrail_Structure_Validator**
- Checks for required files and folders
- Validates WordPress headers
- Returns validation status and missing items

**WPGuardrail_File_Generator**
- Creates missing files from templates
- Generates `.pot` files
- Populates README and LICENSE with plugin info

**WPGuardrail_Monitor_Dashboard**
- Displays file status
- Shows validation results
- Provides quick actions

### Workflow

```
1. Developer saves file in monitored plugin
   ↓
2. File watcher detects timestamp change
   ↓
3. Wait 2-5 seconds (configurable delay)
   ↓
4. Run structure validation
   ↓
5. Display results in dashboard
   ↓
6. Alert developer to any issues
   ↓
7. Offer to create missing files
```

---

## User Experience

### Initial Setup
1. Open WPGuardrail settings
2. Select plugin to monitor from dropdown
3. Set monitoring delay (default: 3 seconds)
4. Enable monitoring
5. Dashboard shows current structure status

### During Development
1. Developer saves a file
2. Dashboard updates with validation results
3. If issues found, alert appears with quick-fix options
4. Developer can create missing files with one click

### File Creation Flow
1. Dashboard shows "Missing: Language Folder"
2. Developer clicks "Create Language Folder"
3. Folder is created with proper structure
4. Dashboard updates to show ✓ Valid

---

## Configuration

**Settings Page Options**:
- **Monitor Plugin**: Dropdown of installed plugins
- **Monitoring Enabled**: Toggle on/off
- **Delay Threshold**: 2-10 seconds (default: 3)
- **Auto-Create Files**: Checkbox to auto-generate missing files
- **Alert Level**: Critical only / Warnings / All info

**Transient Keys**:
- `wpguardrail_file_timestamps_{plugin_slug}`: Last known file timestamps
- `wpguardrail_validation_status_{plugin_slug}`: Current validation status

---

## Integration Points

### With Phase 1 (Sanity Layer)
- File monitoring runs independently
- Feeds into the main error dashboard
- Separate tab: "Structure" vs "Code Quality"

### With WordPress
- Uses WordPress file functions (`wp_remote_get`, `wp_filesystem`)
- Integrates with admin notices
- Uses WordPress transients for caching

### With WPSeed
- Uses WPSeed admin page structure
- Leverages WPSeed table components for file list
- Follows WPSeed coding standards

---

## Future Enhancements

### Phase 0.5: Advanced Monitoring
- Watch for specific file types (`.php`, `.js`, `.css`)
- Diff viewer for file changes
- Rollback capability for recent changes

### Phase 0.6: Template Library
- Custom license templates
- Plugin-specific README templates
- Localization file templates

### Phase 0.7: Team Collaboration
- Notify team members of file changes
- Assign file review tasks
- Comment on specific file changes

---

## Success Metrics

- **Adoption**: 80%+ of developers enable monitoring during development
- **Time Saved**: Average 5 minutes per development session
- **Completeness**: 95%+ of plugins have all required files
- **Accuracy**: <1% false positive rate on file change detection
