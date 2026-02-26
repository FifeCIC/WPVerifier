# WP Verifier Development Roadmap
This roadmap consolidates all planned features and tracks implementation progress. Most features will be supported by existing folders, systems and standard approaches so check for existing implementation before creating new files, functions or classes.

## External Ignore System 🚫

**Goal**: Implement hash-based external ignore file system to keep code files clean while maintaining intelligent ignore rules that invalidate when code changes.

**Integration**: This system will be designed to work seamlessly with both JSON and Database storage modes, storing ignores in the same storage backend as results.

### Phase 1: Core Ignore System (High Priority)
- [ ] **Ignore File Structure**:
  - [ ] Create `.wpv-ignores.json` format specification
  - [ ] Schema: file path, line number, 8-char hash, rule code, reason, date, user
  - [ ] Support for wildcard patterns (e.g., `function wpseed_db_*`)
  - [ ] Support for file-level ignores (all lines in file)
  - [ ] Version field for schema evolution

- [ ] **Hash Generation**:
  - [ ] Implement `generate_line_hash()` - MD5 first 8 chars
  - [ ] Normalize whitespace before hashing (trim, single spaces)
  - [ ] Store optional snippet (first 50 chars) for human verification
  - [ ] Handle multi-line statements (hash combined lines)

- [ ] **Ignore Matcher** (`includes/Ignores/Ignore_Matcher.php`):
  - [ ] `should_ignore()` - Check if issue should be ignored
  - [ ] Line number + hash matching
  - [ ] Pattern matching for function/class names
  - [ ] File-level ignore checking
  - [ ] Logging when ignore doesn't match (hash changed)

### Phase 2: Dual Storage for Ignores (High Priority)
- [ ] **JSON Storage** (`includes/Ignores/JSON_Ignore_Storage.php`):
  - [ ] Read/write `.wpv-ignores.json` in project root
  - [ ] Merge with per-plugin ignore files
  - [ ] Atomic file writes (prevent corruption)
  - [ ] Backup before modifications

- [ ] **Database Storage** (`includes/Ignores/DB_Ignore_Storage.php`):
  - [ ] New table: `wp_wpv_ignores`
    - Columns: `id`, `plugin_slug`, `file_path`, `line`, `hash`, `snippet`, `rule_code`, `reason`, `pattern`, `scope` (line/file/pattern), `added_by`, `added_at`, `last_matched`
  - [ ] CRUD operations for ignores
  - [ ] Query optimization with indexes
  - [ ] Track when ignores are matched (analytics)

- [ ] **Ignore Storage Router** (`includes/Ignores/Ignore_Storage_Router.php`):
  - [ ] Route to JSON or Database based on settings
  - [ ] Unified interface for both storage types
  - [ ] Sync between JSON and Database
  - [ ] Migration tools (JSON ↔ Database)

### Phase 3: Integration with Scanning (High Priority)
- [ ] **Modify `run_phpcs_on_file()`**:
  - [ ] Load ignores before processing results
  - [ ] Filter out ignored issues
  - [ ] Log when hash doesn't match (code changed)
  - [ ] Track ignore effectiveness (how many filtered)

- [ ] **Modify `save_results()`**:
  - [ ] Apply ignores before saving
  - [ ] Store "ignored_count" in scan metadata
  - [ ] Option to save ignored issues separately (for audit)

- [ ] **Backward Compatibility**:
  - [ ] Continue supporting inline `// phpcs:ignore` comments
  - [ ] Detect inline ignores and offer to migrate to external file
  - [ ] Hybrid mode: both inline and external work together

### Phase 4: UI Management (Medium Priority)
- [ ] **Ignore Management Page** (new tab in WP Verifier):
  - [ ] List all active ignores
  - [ ] Filter by plugin, file, rule type
  - [ ] Show ignore status (active/stale/invalid)
  - [ ] Bulk operations (delete, re-validate)
  - [ ] Search and sort functionality

- [ ] **Add Ignore from Results**:
  - [ ] "Ignore This Issue" button in Selected Issue Details
  - [ ] Modal: Enter reason, choose scope (this line/this file/this pattern)
  - [ ] Preview what will be ignored
  - [ ] Confirm and save to storage

- [ ] **Ignore Health Dashboard**:
  - [ ] Show stale ignores (hash no longer matches)
  - [ ] Show unused ignores (never matched in recent scans)
  - [ ] Show most-used ignores
  - [ ] Suggest cleanup actions

### Phase 5: Advanced Features (Medium Priority)
- [ ] **Pattern-Based Ignores**:
  - [ ] Ignore all functions matching pattern: `wpseed_db_*`
  - [ ] Ignore all files in directory: `includes/vendor/*`
  - [ ] Ignore specific rule in entire file
  - [ ] Regex support for advanced patterns

- [ ] **Temporary Ignores**:
  - [ ] Expiration date for ignores
  - [ ] "Snooze" functionality (ignore for 7 days)
  - [ ] Auto-cleanup expired ignores
  - [ ] Notifications when temporary ignores expire

- [ ] **Team Collaboration**:
  - [ ] Track who added each ignore
  - [ ] Require approval for certain ignore types
  - [ ] Comment/discussion on ignores
  - [ ] Audit log of ignore changes

### Phase 6: Migration & Cleanup (Low Priority)
- [ ] **Inline Comment Migration**:
  - [ ] Scan codebase for `// phpcs:ignore` comments
  - [ ] Extract to external ignore file
  - [ ] Generate hash for each line
  - [ ] Optionally remove inline comments
  - [ ] Preview before applying

- [ ] **Ignore Validation**:
  - [ ] Check all ignores against current codebase
  - [ ] Report stale ignores (hash mismatch)
  - [ ] Report orphaned ignores (file/line doesn't exist)
  - [ ] Suggest updates or removals

- [ ] **Import/Export**:
  - [ ] Export ignores to shareable format
  - [ ] Import ignores from other projects
  - [ ] Merge ignore files from multiple sources
  - [ ] Conflict resolution UI

### Phase 7: Analytics & Reporting (Future)
- [ ] **Ignore Analytics**:
  - [ ] Most ignored rules across projects
  - [ ] Ignore trends over time
  - [ ] False positive rate estimation
  - [ ] Ignore effectiveness metrics

- [ ] **Smart Suggestions**:
  - [ ] AI-powered ignore recommendations
  - [ ] Detect patterns in manual ignores
  - [ ] Suggest file-level or pattern ignores
  - [ ] Learn from team's ignore decisions

### Implementation Strategy

**Phase 1-2: Foundation (Week 1-2)**
- Build core ignore system alongside existing inline comments
- No disruption to current workflow
- Both systems work in parallel

**Phase 3: Integration (Week 3)**
- Integrate with scanning process
- Filter results using external ignores
- Maintain backward compatibility

**Phase 4: UI (Week 4-5)**
- Add management interface
- Allow users to add/remove ignores via UI
- Provide migration path from inline comments

**Phase 5-7: Enhancement (Future)**
- Advanced features as needed
- Based on user feedback and usage patterns

### Database Integration Notes

**When Database Storage is Enabled:**
- Ignores stored in `wp_wpv_ignores` table
- Fast querying with proper indexes
- Track ignore usage and effectiveness
- Analytics on ignore patterns

**When JSON Storage is Used:**
- Ignores in `.wpv-ignores.json` file
- Version controlled with code
- Easy to share across team
- Simple backup and restore

**Hybrid Approach:**
- Both storage types can coexist
- Sync between JSON and Database
- Choose display source in settings
- Migration tools for switching

### Benefits of External Ignore System

1. **Clean Code Files**: No `// phpcs:ignore` comments cluttering code
2. **Intelligent Invalidation**: Hash-based matching detects code changes
3. **Centralized Management**: All ignores in one place, easy to review
4. **Team Collaboration**: Track who added what and why
5. **Analytics**: Understand ignore patterns and effectiveness
6. **Flexible Storage**: Works with both JSON and Database backends
7. **Version Control**: Ignore changes tracked separately from code
8. **Audit Trail**: Complete history of ignore decisions

### Technical Considerations

- **Hash Algorithm**: MD5 (fast, sufficient collision resistance)
- **Hash Length**: 8 characters (4.3 billion combinations)
- **Normalization**: Trim whitespace, normalize line endings
- **Performance**: Cache loaded ignores per request
- **Backward Compat**: Support inline comments indefinitely
- **Migration**: Gradual, opt-in, non-breaking
- **Storage**: Unified interface for JSON/Database
- **Validation**: Regular checks for stale/orphaned ignores


## Database Storage System 🗄️

**Goal**: Implement dual storage system (JSON + Database) for verification results with database as optional enhancement.

### Phase 1: Database Foundation (High Priority)
- [ ] **Custom Table Creation**:
  - [ ] `wp_wpv_results` - Main results table
    - Columns: `id`, `plugin_slug`, `file_path`, `issue_type` (error/warning), `line`, `column`, `code`, `message`, `severity`, `issue_id`, `ignored`, `resolved`, `created_at`, `updated_at`
  - [ ] `wp_wpv_scans` - Scan metadata table
    - Columns: `id`, `plugin_slug`, `scan_date`, `total_errors`, `total_warnings`, `readiness_score`, `check_mode` (full/limit_10/single_file), `categories_checked`, `scan_duration`
  - [ ] `wp_wpv_ignored_paths` - Ignored paths table
    - Columns: `id`, `plugin_slug`, `path`, `reason`, `added_by`, `added_at`
  - [ ] Database schema versioning for migrations
  - [ ] Activation hook to create tables
  - [ ] Deactivation cleanup option

### Phase 2: Settings & Configuration (High Priority)
- [ ] **Settings Page Updates**:
  - [ ] Add "Storage" section to Settings tab
  - [ ] Checkbox: "Enable Database Storage" (default: OFF)
    - Warning: "Database mode is not recommended for production websites. Use for development/testing only."
    - Info: "When enabled, results are stored in both database and JSON files."
  - [ ] Checkbox: "Disable JSON Storage" (default: OFF, requires Database enabled)
    - Warning: "Only disable JSON if database storage is working correctly."
  - [ ] Radio: "UI Data Source" (default: JSON)
    - Options: "JSON Files" or "Database"
    - Info: "Choose which storage to use for displaying results in the interface."
  - [ ] Button: "Migrate JSON to Database" (one-time import)
  - [ ] Button: "Export Database to JSON" (backup/export)
  - [ ] Display: Storage statistics (DB size, JSON size, record counts)

### Phase 3: Dual Storage Implementation (Medium Priority)
- [ ] **Save Results Handler**:
  - [ ] Modify `save_results()` in Admin_AJAX.php
  - [ ] Check settings for storage preferences
  - [ ] Save to JSON (if not disabled)
  - [ ] Save to Database (if enabled)
  - [ ] Transaction support for database writes
  - [ ] Error handling for storage failures
  - [ ] Logging for storage operations

- [ ] **Database Writer Class** (`includes/Storage/Database_Writer.php`):
  - [ ] `save_scan_metadata()` - Save scan info to wp_wpv_scans
  - [ ] `save_results()` - Save issues to wp_wpv_results
  - [ ] `save_ignored_paths()` - Save ignored paths
  - [ ] `update_file_results()` - Update single file results
  - [ ] Batch insert optimization for large result sets
  - [ ] Index management for performance

### Phase 4: Data Retrieval Layer (Medium Priority)
- [ ] **Database Reader Class** (`includes/Storage/Database_Reader.php`):
  - [ ] `get_plugin_results()` - Fetch all results for a plugin
  - [ ] `get_file_results()` - Fetch results for specific file
  - [ ] `get_scan_history()` - Fetch scan metadata
  - [ ] `get_ignored_paths()` - Fetch ignored paths
  - [ ] Query optimization with proper indexes
  - [ ] Caching layer for frequently accessed data
  - [ ] Convert database format to JSON-compatible format

- [ ] **Storage Router** (`includes/Storage/Storage_Router.php`):
  - [ ] Check UI data source setting
  - [ ] Route requests to JSON or Database reader
  - [ ] Fallback logic (try DB, fall back to JSON)
  - [ ] Unified interface for both storage types

### Phase 5: UI Integration (Low Priority)
- [ ] **Results Page Updates**:
  - [ ] Modify `admin-page-saved.php` to use Storage_Router
  - [ ] Update AJAX handlers to support both sources
  - [ ] Add storage source indicator in UI
  - [ ] Performance comparison display (JSON vs DB load times)

- [ ] **Recheck File Updates**:
  - [ ] Update `recheck_file()` to save to both storages
  - [ ] Ensure single-file updates work with database

### Phase 6: Migration & Maintenance (Low Priority)
- [ ] **Migration Tools**:
  - [ ] JSON to Database importer
    - Parse all JSON files in verifier-results/
    - Bulk insert into database tables
    - Progress indicator for large imports
    - Validation and error reporting
  - [ ] Database to JSON exporter
    - Query all results from database
    - Generate JSON files in correct format
    - Preserve directory structure
  - [ ] Sync checker (compare JSON vs DB)
    - Identify discrepancies
    - Offer to sync missing data

- [ ] **Maintenance Features**:
  - [ ] Database cleanup (remove old scans)
  - [ ] Optimize database tables
  - [ ] Repair corrupted data
  - [ ] Storage integrity checker

### Phase 7: Advanced Features (Future)
- [ ] **Query Builder UI**:
  - [ ] Filter results by plugin, file, error code, severity
  - [ ] Date range filtering for scans
  - [ ] Export filtered results
  - [ ] Saved filter presets

- [ ] **Analytics Dashboard**:
  - [ ] Most common errors across all plugins
  - [ ] Error trends over time
  - [ ] Plugin comparison charts
  - [ ] Readiness score history graphs

- [ ] **Performance Optimization**:
  - [ ] Database query caching
  - [ ] Lazy loading for large result sets
  - [ ] Pagination for database queries
  - [ ] Background processing for large imports

### Implementation Notes
- **Both systems run simultaneously** - JSON and Database storage are independent
- **JSON remains default** - Database is opt-in enhancement
- **UI can switch sources** - Choose which storage to display from
- **Production warning** - Clear messaging that database mode is for development
- **Graceful degradation** - If database fails, fall back to JSON
- **No data loss** - Both systems maintain complete data independently
- **Migration is optional** - Users can start fresh or import existing JSON

### Technical Considerations
- Use WordPress $wpdb for all database operations
- Follow WordPress database naming conventions
- Implement proper sanitization and escaping
- Use prepared statements for all queries
- Add database indexes for performance
- Consider multisite compatibility
- Plan for large datasets (10,000+ issues)
- Implement database cleanup/archival strategy


## Future Phases 🔮

### File Monitoring System
- [x] Plugin selection for active monitoring
- [x] File change detection (timestamp-based)
- [x] Background check execution on file changes
- [x] Monitoring activity logger
- [x] Admin notification system for new issues
- [x] **File Watcher**: Monitor selected plugin directory for file timestamp changes.
- [ ] **Delayed Validation**: Wait 2-5 seconds after file save before running checks (assume developer is still working).
- [x] **Change Detection**: Alert developer when file modifications are detected with validation results.
- [x] **Structure Validation**: Check for required files and folders:
  - [x] Language folder (`/languages` or `/lang`)
  - [x] Language files (`.pot`, `.po`, `.mo`)
  - [x] License file (`LICENSE`, `LICENSE.txt`, `LICENSE.md`)
  - [x] README file (`README.md`, `readme.txt`)
  - [x] Plugin header file (main plugin file with proper headers)
- [ ] **File Creation Wizard**: Offer to auto-generate missing files with templates.
- [x] **Real-time Dashboard**: Show file status, last modified times, and validation results.

## Plugin Namer Tool Features

**Goal**: Make plugin naming tool more comprehensive and user-friendly.

### Priority 1: Visual Dashboard (High Priority - Quick Win)
- [ ] **Unified Status Dashboard**: Create at-a-glance availability status at top of results.
  - [ ] WordPress.org status indicator (✓ Available / ✗ Taken)
  - [ ] Domain availability indicators for multiple TLDs
  - [ ] Trademark status (✓ Clear / ⚠ Review / ✗ Conflict)
  - [ ] Overall viability score (0-100)
  - [ ] Color-coded visual indicators throughout
- [ ] **Collapsible Result Sections**: Organize detailed results into expandable sections.
- [ ] **Quick Action Buttons**: Add Save Name, Check Alternatives, Export buttons.

### Priority 2: Multi-TLD Domain Checking (High Priority)
- [ ] **Simultaneous TLD Checks**: Check .com, .net, .org, .io at once.
- [ ] **Compact Table Display**: Show all TLD results in organized table format.
- [ ] **Domain Price Integration**: Display registration costs from popular registrars.
- [ ] **WHOIS Information**: Show domain registration details if taken.
- [ ] **Expiration Tracking**: Track when taken domains expire for monitoring.

### Priority 3: Name Alternatives Generator (Medium Priority)
- [ ] **AI-Powered Suggestions**: Generate 3-5 available alternatives when name is taken.
- [ ] **Brand Intent Preservation**: Maintain original naming intent in suggestions.
- [ ] **Instant Availability Check**: Show availability status for each suggestion.
- [ ] **One-Click Evaluation**: Allow quick evaluation of suggested alternatives.

### Priority 4: Confidence Scoring System (Medium Priority)
- [ ] **Numerical Viability Score**: Replace simple verdict with 0-100 score.
- [ ] **Category Breakdown**: 
  - [ ] Availability Score (40%): WordPress.org + Domain status
  - [ ] Trademark Score (30%): Conflict risk assessment
  - [ ] SEO Score (15%): Search optimization potential
  - [ ] Memorability Score (15%): Ease of recall and pronunciation
- [ ] **Visual Progress Bars**: Display score breakdown with progress indicators.
- [ ] **Score Explanation**: Provide reasoning for each category score.

### Priority 5: Saved Names & Comparison (Medium Priority)
- [ ] **Save Evaluated Names**: Store names with full analysis results.
- [ ] **Side-by-Side Comparison**: Compare up to 4 saved names simultaneously.
- [ ] **Comparison Matrix**: Show all metrics in unified comparison table.
- [ ] **Notes & Tags**: Add custom notes and categorize saved names.
- [ ] **Re-check Availability**: Bulk re-check all saved names for status changes.
- [ ] **Export Options**: Export comparison as PDF/CSV/JSON.
- [ ] **Favorite/Star System**: Mark preferred names for quick access.

### Priority 6: Enhanced Results Layout (Low Priority)
- [ ] **Tabbed Results View**: Organize results into logical tabs.
- [ ] **Expandable Details**: Collapsible sections for each check type.
- [ ] **Visual Status Indicators**: Consistent color-coding throughout interface.
- [ ] **Result Summary Cards**: Card-based layout for key findings.
- [ ] **Print-Friendly View**: Optimized layout for printing/PDF export.

### Priority 7: Social Media Integration (Low Priority)
- [ ] **Handle Availability Checks**: Twitter, Instagram, Facebook, LinkedIn.
- [ ] **Unified Dashboard Display**: Include social status in main dashboard.
- [ ] **Direct Registration Links**: Link to social platform registration pages.
- [ ] **Handle Variations**: Check common variations (@name, @nameofficial, etc.).

### Advanced Name Evaluation
- [ ] **Trademark Search**: Check USPTO and international trademark databases.
- [ ] **Similar Name Detection**: Find existing plugins with similar names (fuzzy matching).
- [ ] **SEO Analysis**: Evaluate name for search engine optimization potential.
- [ ] **Length Analysis**: Optimal character count for readability.
- [ ] **Pronunciation Guide**: Suggest phonetic spelling for complex names.
- [ ] **Cultural Sensitivity Check**: Flag potentially problematic names in different languages/cultures.
- [ ] **Keyword Relevance**: Analyze if name reflects plugin functionality.
- [ ] **Market Positioning**: Compare name against competitors.

### AI-Powered Features
- [ ] **AI Name Generation**: Generate plugin names based on description/features.
- [ ] **Bulk Name Check**: Upload list of names for batch checking.
- [ ] **Smart Suggestions**: Context-aware alternative name recommendations.
- [ ] **Trend Analysis**: Identify naming trends in plugin ecosystem.

### Integration & Automation
- [ ] **API Integration**: Connect to domain registrars for direct purchase.
- [ ] **Notification System**: Alert when monitored domain becomes available.
- [ ] **Webhook Support**: Trigger external actions on name availability changes.
- [ ] **CLI Tool**: Command-line interface for batch name checking.
