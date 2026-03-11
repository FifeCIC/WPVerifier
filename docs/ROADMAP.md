# WP Verifier Development Roadmap


## PHASE 3: Function-Based Issue Management

**Objective**: Transform TAB05 from generic "Issues" to function-centric "Functions" tab with enhanced developer workflow.

### Core Concept
- **Rename TAB05**: "Issues" → "Functions" 
- **UI Pattern**: Copy TAB04 accordion design but group by function instead of file
- **Granular Control**: Function-level ignore/fixed status instead of file-level
- **Developer Focus**: Organize issues by logical code boundaries (functions/methods)

### Phase 3.1: JSON Schema Enhancement (HIGH PRIORITY)
- [ ] **Extend `.wpv-results.json` structure**
	- Add `function_name` field to each issue
	- Add `function_hash` field for change tracking
	- Add optional `class_name` field for OOP context
	- Add `function_signature` for display purposes
- [ ] **Function Detection System**
	- Use `token_get_all()` to parse PHP files during scan
	- Map line numbers to containing function/method
	- Extract function signatures and boundaries
	- Generate function-specific hashes for change detection
- [ ] **Backward Compatibility**
	- Handle existing results without function data
	- Graceful degradation for non-PHP files
	- Migration strategy for existing `.wpv-results.json` files

### Phase 3.2: Function-Centric UI (MEDIUM PRIORITY)
- [ ] **Copy TAB04 Architecture**
	- Duplicate accordion structure and styling
	- Adapt JavaScript for function-based grouping
	- Maintain existing VSCode integration and AI prompts
- [ ] **Accordion Headers**
	- Primary: Function signature (e.g., `public function handle_ajax_request()`)
	- Secondary: File path and line range
	- Badge: Issue count per function
- [ ] **Function-Level Actions**
	- "Mark Function as Fixed" - affects all issues in function
	- "Ignore Function Issues" - uses function hash for tracking
	- "Copy Function Prompt" - AI prompt with full function context
	- VSCode button opens to function definition

### Phase 3.3: Enhanced Function Management (MEDIUM PRIORITY)
- [ ] **Function Status Tracking**
	- Store function-level ignore/fixed status in `.wpv-verification.json`
	- Hash-based validation (detect when function changes)
	- Stale status detection and warnings
- [ ] **Function Hierarchy Display**
	- Show class context for methods
	- Group by class → method structure
	- Support for nested functions and closures
- [ ] **Function-Level Statistics**
	- Issues per function metrics
	- Function complexity indicators
	- Most problematic functions ranking

### Phase 3.4: Advanced Function Features (LOW PRIORITY)
- [ ] **Function Comparison**
	- Before/after function diff when hash changes
	- Show what changed to cause stale ignore status
	- Suggest re-evaluation of ignored issues
- [ ] **Function Documentation Integration**
	- Extract and display function docblocks
	- Show parameter types and return values
	- Link to internal documentation if available
- [ ] **Bulk Function Operations**
	- Select multiple functions for bulk actions
	- Export function-specific reports
	- Batch ignore/fixed status updates

### Technical Implementation Notes
- **Function Detection**: Leverage existing hash generation system in `Hash_Generator.php`
- **UI Consistency**: Maintain TAB04's proven accordion pattern for familiarity
- **Performance**: Cache function parsing results to avoid re-parsing on each load
- **Extensibility**: Design for future language support (JavaScript, CSS, etc.)

### Success Metrics
- Function-level ignore/fixed actions working correctly
- Hash-based change detection preventing stale ignores
- Developer workflow improvement (faster issue resolution)
- Maintained performance with function-level granularity

## Phase 2: Tab Structure Redesign

### 1 - Configure Tab (formerly Preparation)
- [x] Rename "TAB01" from "Preparation" to "Configure"
- [x] Update references in new "Instructions" area
- [x] Focus on initial setup and configuration options

### 2 - Hash Generation Tab ✅ COMPLETED
- [x] Create dedicated "Hash" tab with test steps
- [x] Move hash generation from main verification process (TAB02)
- [x] Populate `.wpv-verification.json` as currently done
- [x] Add validation steps between hash generation phases

### 3 - Exclusions Tab
- [ ] Create dedicated exclusions management tab
- [ ] Populate new `.wpv-exclusions.json` file
- [ ] Move "ignored_files_found" from `.wpv-results.json` to new file
- [ ] Create new .js files, move logic from Admin_AJAX.php

### 4 - Data Cleanup
- [ ] Remove duplicate "file_hashes" storage
- [ ] Stop duplicate storage in `.wpv-results.json`
- [ ] Keep only in `.wpv-verification.json`

### 5 - Readiness Tab
- [ ] Create new tab to generate "Readiness Score" from JSON files
- [ ] Compare with existing Advanced Verification readiness score
- [ ] Identify discrepancies between calculation methods
- [ ] Maintain existing end-of-verification score for comparison

### 6 - Advanced Verification Cleanup
- [ ] Remove "Readiness Score" notice from top of TAB03
- [ ] Clean up WordPress core styled notices

---

## Enhanced Logging System ✅ COMPLETED
- [x] Created unified logging system with loop counting
- [x] Added specialized verification logger for data flow tracing
- [x] Implemented JavaScript logging enhancements
- [x] Smart counting for repetitive operations (logs every 10th iteration)

---

## Hash-Based Issue Ignore System

### Phase 1: Core Tracking System (High Priority)
- [x] Create `.wpv-verification.json` format specification
- [x] Schema structure implemented
- [x] Version field for schema evolution
- [x] Store in plugin root directory for portability
- [x] Parse PHP files using `token_get_all()`
- [x] Extract function/method/class boundaries
- [x] Generate SHA256 hash of function body, use first 8 chars
- [x] Generate SHA256 hash of entire file for file-level verification
- [x] Normalize whitespace before hashing
- [x] Handle nested functions and closures
- [ ] Create `is_ignored()` - Check if issue is currently ignored
- [ ] Match by function name + hash
- [ ] Match by file-level hash (entire file ignored)
- [ ] Return ignore status: active/stale/none
- [ ] Log when hash doesn't match (code changed, ignore expired)

### Phase 2: JSON Storage (High Priority)
- [x] Read/write `.wpv-verification.json` in plugin root
- [x] Atomic file writes (prevent corruption)
- [x] Backup before modifications
- [x] Initialize verification file for new plugins
- [x] Validate JSON structure on load
- [ ] Merge verification data from multiple sources

### Phase 3: Integration with Scanning (High Priority)
- [x] Generate file hashes during `save_results()`
- [x] Store hashes in `.wpv-results.json` alongside scan results
- [x] Hash all files that have issues (errors/warnings)
- [x] Preserve existing data when re-running scans
- [ ] Load ignore data before processing results
- [ ] Check each issue against ignore status
- [ ] Filter out actively ignored issues from results
- [ ] Log when hash doesn't match (code changed, ignore expired)
- [ ] Track ignore coverage (% of issues ignored)
- [ ] Include ignore status in saved results
- [ ] Store "ignored_count" in scan metadata
- [ ] Separate ignored vs active issues in display
- [ ] Show stale ignores (hash mismatch, need re-evaluation)

---

## Internationalization Tool (i18n)
Detect strings yet to be translated and any translation related issues.

### JavaScript Translation (Medium Priority)
- [ ] Create translation object in PHP where scripts are enqueued
- [ ] Pass translated strings to JavaScript via localized object
- [ ] Update all JS files to use localized strings instead of hardcoded text
- [ ] Update `assets/js/plugin-check-preparation.js`
- [ ] Update `assets/js/plugin-check-saved.js`
- [ ] Update `assets/js/plugin-check.js`
- [ ] Update other JS files with user-facing text

### PHP Translation (Low Priority)
- [ ] Ensure all user-facing strings use `__()`, `_e()`, `esc_html__()`, `esc_html_e()`
- [ ] Add text domain 'wp-verifier' to all translation functions
- [ ] Add translator comments for context where needed

### Translation Infrastructure (Low Priority)
- [ ] Set up automated POT file generation
- [ ] Consider GlotPress or similar for community translations
- [ ] Test and ensure RTL language compatibility

---

## Developer Guidance Panel

### Phase 1: Foundation (High Priority)
- [ ] Check for existing footer output functionality
- [ ] Create footer panel that only displays when `WP_DEVELOPMENT_MODE` is active
- [ ] Design collapsible/expandable panel interface
- [ ] Position panel at bottom of page without interfering with normal functionality
- [ ] Create early-set variable to track loaded templates
- [ ] Hook into template loading process to capture file paths
- [ ] Track view files, layout files, and styling files as they're loaded
- [ ] Store template hierarchy information (parent/child relationships)

---

## Additional Developer Workflow Enhancements

### Hash-Aware Ignore / Verify Workflow (High Priority)
- [ ] Add "Mark as Verified" action for a specific issue/function
- [ ] Require verification note (why acceptable / mitigation)
- [ ] Store `verified_by`, `verified_at`, `verification_notes` in `.wpv-verification.json`
- [ ] Add ignore decision with scope (function vs file)
- [ ] Auto-expire ignores when hash mismatches (stale ignores)
- [ ] Show "stale" state prominently in UI and reporting

### Review Queue & Audit Trail (High Priority)
- [ ] New issues since last scan
- [ ] Stale verifications (hash mismatch)
- [ ] Orphaned ignores/verifications (function/file no longer exists)
- [ ] Require second approver for file-level ignoring
- [ ] Require approval for ignoring certain severities (e.g. security)
- [ ] Export verification/ignore history to CSV/JSON for QA/security reviews
