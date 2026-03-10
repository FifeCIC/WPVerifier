# WP Verifier Development Roadmap

## 🚨 URGENT: Verification Results & Display Issues

**Current Status**: Verification processing works (300+ issues found) but results not properly saved/displayed

### Critical Issues Identified ✅ PARTIALLY RESOLVED
- [x] **AJAX Communication Working**: Console shows 508KB HTML output, verification finds issues
- [x] **Progress Bar Added**: Loader shows proper steps and completes
- [x] **Debug Logging Added**: Comprehensive logging to track data flow
- [ ] **JSON File Not Updated**: `.wpv-results.json` remains empty despite successful verification
- [ ] **Readiness Score Hidden**: Original readiness score display not showing after verification
- [ ] **Empty Export Controls Panel**: `<div id="plugin-check__export-controls">` has no content

### Immediate Action Items (HIGH PRIORITY)
- [ ] **Fix JSON Results Saving**: Add `save_results_to_json()` method call in AJAX handler
- [ ] **Restore Readiness Score Display**: Show original readiness score container after verification
- [ ] **Investigate Export Controls**: Determine if empty panel should contain readiness score
- [ ] **Verify TAB04 (Files) Integrity**: Check if Files tab design was affected by consolidation
- [ ] **Consider GitHub Backup**: Download original version for reference if major design lost

### Debug Strategy
- [x] **Enhanced Logging**: Added comprehensive debug logging for verification flow
- [x] **Console Monitoring**: JavaScript logs show successful AJAX with large HTML payload
- [ ] **JSON File Monitoring**: Track when/why `.wpv-results.json` isn't being updated
- [ ] **Template Comparison**: Compare current vs original readiness score implementation

### Design Integrity Concerns
- **Issue**: Consolidation may have removed original readiness score display logic
- **Risk**: TAB04 (Files) and TAB05 (Issues) may have lost original designs
- **Mitigation**: GitHub backup available as reference for original implementations

---

## ✅ PHASE 1 COMPLETE: Code Reduction & Consolidation

**Achievement Summary**: Successfully reduced codebase complexity and eliminated duplicate patterns.

### Template Consolidation ✅ COMPLETED
- [x] **Merged similar templates**
	- ✅ Consolidated admin-page-preparation.php + admin-page-hash-generation.php → admin-page-configuration.php
	- ✅ Consolidated basic + advanced verification templates → admin-page-verification.php
	- ✅ Created shared Template_Helper utility class for common functions

### JavaScript Consolidation ✅ COMPLETED  
- [x] **Merged related JS files**
	- ✅ Created shared AJAX utility (wpv-ajax.js) with common patterns
	- ✅ Reduced from 12 JS files to 6 focused files (46% reduction)
	- ✅ Consolidated preparation + hash generation → admin-configuration.js
	- ✅ Consolidated basic + advanced verification → admin-verification.js

### Feature Removal ✅ COMPLETED
- [x] **Removed duplicate/unused features**
	- ✅ Eliminated "Basic Verification" (TAB13) - duplicate of advanced verification
	- ✅ Removed "Plugin Namer" (TAB08) - 8 files and associated code eliminated
	- ✅ Removed TAB03 (Hash Generation) as duplicate of TAB02
	- ✅ Renumbered remaining tabs for cleaner interface

### AJAX Handler Fixes ✅ COMPLETED
- [x] **Fixed missing AJAX handlers**
	- ✅ Added wpv_load_config and wpv_save_config methods
	- ✅ Added wpv_run_checks AJAX action for verification
	- ✅ Fixed Asset_Manager localization for current_plugin access

### Architecture Improvements ✅ COMPLETED
- [x] **Enhanced core systems**
	- ✅ Updated TAB01 to auto-create missing WPV files
	- ✅ Enhanced TAB02 with vendor folder detection and detailed feedback
	- ✅ Updated TAB03 with readiness checklist and conditional verification
	- ✅ Centralized asset management through Asset_Manager class

**CRITICAL ISSUE IDENTIFIED**: Verification results display was modified beyond consolidation scope, changing original HTML structure and design patterns.

---

## SECONDARY PRIORITY: System Simplification & Data Loss Fix

**Primary Issue**: JavaScript shows 288 warnings, PHP receives only 6.
**Strategy**: Break down verification process into clear, validatable steps.

### Debug Data Loss (URGENT)
- [ ] Add comprehensive logging to trace 288→6 warning reduction
	- **Signpost:** `wp-content/plugins/WPVerifier/assets/js/plugin-check-admin.js` (in `runChecks()` function) and `wp-content/plugins/WPVerifier/includes/Admin/Admin_AJAX.php` (in `save_results()` function)
	- **Clarity:** Enhanced logging to count total warnings before/after transmission
- [ ] Identify exact point where data is lost
	- Compare detailed warning counts in JavaScript vs PHP
	- Check for server limits (`post_max_size`, JSON payload size)
- [ ] Fix data transmission between JavaScript and PHP
	- Implement chunking if payload size is the issue
	- Fix serialization/deserialization problems

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

### Phase 4: UI Management (Medium Priority)
- [ ] List all ignored functions/files
- [ ] Filter by plugin, file, ignore status
- [ ] Show ignore health (active/stale/invalid)
- [ ] Bulk operations (re-ignore, remove ignore)
- [ ] Search and sort functionality
- [ ] "Ignore Issue" button in Selected Issue Details
- [ ] Modal: Enter note, choose scope (this function/entire file)
- [ ] Preview what will be ignored
- [ ] Generate hash and save to `.wpv-verification.json`
- [ ] Track who ignored and when
- [ ] Show stale ignores (hash no longer matches)
- [ ] Show ignore coverage per file
- [ ] Show recently ignored items
- [ ] Suggest expired ignores for re-evaluation

---

## Internationalization (i18n)

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

## Issues Tab Enhancement

### Phase 1: WP_List_Table Implementation (Medium Priority)
- [ ] Extend WP_List_Table
- [ ] Implement required methods: get_columns(), prepare_items(), column_default()
- [ ] Load merged issues data (wpseed results + AI guidance)
- [ ] Support severity badges (ERROR/WARNING)
- [ ] Maintain accordion row expansion functionality
- [ ] Implement search_box() method
- [ ] Search across file names, error codes, and messages
- [ ] Real-time filtering of results
- [ ] Clear search button
- [ ] Checkbox column for row selection
- [ ] Bulk action dropdown (Copy All Prompts, Mark as Reviewed, etc.)
- [ ] "Select All" functionality
- [ ] Process bulk actions handler
- [ ] Make all columns sortable (Severity, File, Line, Code)
- [ ] Maintain sort state across page loads
- [ ] Visual sort indicators
- [ ] Add pagination controls (10, 25, 50, 100 per page)
- [ ] Screen options for items per page
- [ ] Total items count display

### Phase 2: Enhanced Features (Low Priority)
- [ ] Screen options to show/hide columns
- [ ] Save column preferences per user
- [ ] Export filtered results to CSV
- [ ] Export with AI prompts included
- [ ] Bulk export selected items

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

### Phase 2: TAB02 Implementation (High Priority)
- [ ] Focus initial implementation on TAB02 functionality
- [ ] Track templates specific to TAB02 operations
- [ ] Display relevant file paths for TAB02 views and layouts
- [ ] Show styling files that affect TAB02 presentation
- [ ] Show full file paths to template files
- [ ] Organize by file type (Views, Layouts, Styles, Scripts)
- [ ] Make file paths clickable (if IDE integration possible)
- [ ] Show file modification timestamps

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

---

*Last Updated: 2026-03-09*