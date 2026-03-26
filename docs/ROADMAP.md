# WP Verifier Development Roadmap

## PHASE 2: Path Building Consolidation ✅ COMPLETE
Centralized `Path_Builder` class. All path logic consolidated. VSCode URLs working.

## PHASE 5: Results Tab Refactor ✅ COMPLETE
Merged TAB04 + TAB05 into single "Results" tab with pure PHP rendering.

### Completed
- [x] `templates/admin-page-results.php` — unified template, URL-driven sidebar via `$_GET['issue_id']`
- [x] `Admin_Page_Tabs.php` — single "Results" entry replaces TAB04+TAB05
- [x] `Admin_Page.php` — routing updated
- [x] `Asset_Manager.php` — single `enqueue_results_assets()` method
- [x] `assets/js/admin-results.js` — minimal JS: accordion, copy, Fixed/Ignore/Unignore AJAX
- [x] Fixed button AJAX working via `wpv_mark_resolved` → `mark_issue_as_fixed()`
- [x] Ignore button AJAX working via `wpv_mark_ignored` → `mark_issue_as_ignored()`
- [x] Unignore button AJAX working via `wpv_mark_unignored` → `mark_issue_as_unignored()`
- [x] IGNORED badge displayed on ignored issues in list and sidebar
- [x] Fixed PHP reference bug (`&$issues` + `break 2` = data corruption). Solution: remove reference, use explicit `$results_data['results'][$file_path] = $issues`

### Remaining Cleanup
- [x] Delete old files: `admin-page-issues-byfile.php`, `admin-page-issues.php` — deleted. `admin-page-saved.js`, `issues-tab.js`, `plugin-check-saved.js` — not found, likely already removed.
- [ ] Strip TAB04/TAB05 code from `wp-verifier-ast.js` (keep TAB03 post-verification display)
- [ ] Remove `results-ast.php` template from `admin_footer()` if safe

---

## PHASE 6: File-Level Ignore System 🔄 CURRENT FOCUS

### Overview
When a developer has ignored ALL issues in a file one by one, the file is automatically marked as ignored in `.wpv-verification.json`. On subsequent plugin checks, ignored files are skipped entirely — making checks faster and keeping `.wpv-results.json` as a clean task list of only actionable issues.

### Why Hash Not Timestamp
Hash (MD5 of file contents) only changes when actual content changes. Timestamps change on deploy, copy, or save-without-edit — causing false invalidations. Hash means:
- Deploy to staging → hash unchanged → ignore still valid ✅
- Open file, save without changes → hash unchanged → ignore still valid ✅
- Actually fix/change code → hash changes → ignore correctly invalidated ✅

### Known Issues
- [ ] **"File Resolved!" button message not displaying** — `file_ignored: true` is returned correctly in the AJAX response and the JS handler is correct, but the button text change is not visible before the redirect fires. The notification/admin message system needs a proper queued message approach rather than relying on button text mutation. This should be solved by building a proper admin notice queue (see Additional Features below) and using it here instead.

### Phase 6.1: Auto-Detect All-Ignored Files
**Trigger:** When user clicks Ignore on any issue in TAB04.
**Logic in `mark_issue_as_ignored()`:**
1. After setting `ignored: true` on the target issue, check if ALL issues in that file now have `ignored: true`
2. If yes → call `mark_file_as_ignored( $file_path, $plugin )`
3. `mark_file_as_ignored()`:
   - Computes MD5 hash of the actual file on disk
   - Writes entry to `.wpv-verification.json` under `ignored_files` key
   - Deletes ALL issues for that file from `.wpv-results.json` (clean task list)
   - Recalculates readiness score

**`.wpv-verification.json` structure for ignored files:**
```json
{
  "ignored_files": {
    "includes/admin/admin-settings.php": {
      "hash": "d41d8cd98f00b204e9800998ecf8427e",
      "ignored_at": "2026-03-20 20:00:00",
      "ignored_by": 1
    }
  }
}
```

**Files to modify:**
- `includes/Admin/Verification_AJAX_Handler.php` — add `mark_file_as_ignored()`, update `mark_issue_as_ignored()`
- `includes/Verification/JSON_Storage.php` — add `write_ignored_file()`, `remove_ignored_file()`

**Testing:**
1. Ignore all issues in a file one by one
2. On last ignore → verify `.wpv-verification.json` gets `ignored_files` entry with correct hash
3. Verify all issues for that file are removed from `.wpv-results.json`
4. Verify readiness score recalculates correctly
5. Verify TAB04 no longer shows that file

### Phase 6.2: Visual Indicator for Ignored Files in TAB04
**Requirement:** Files that are fully ignored should show an IGNORED badge on their accordion header.

**Logic in `admin-page-results.php`:**
1. Load `.wpv-verification.json` at top of template
2. For each file accordion header, check if file exists in `ignored_files`
3. If yes → add `ignored` CSS class to accordion row + show IGNORED badge

**Note:** Since all issues are deleted from `.wpv-results.json` when a file is ignored, ignored files won't appear in the results loop at all. The visual indicator is only needed if we decide to show ignored files separately (future enhancement).

### Phase 6.3: Hash Validation on Plugin Check
**Trigger:** When a new plugin check is run (TAB03).
**Logic in `Abstract_PHP_CodeSniffer_Check.php` → `get_files_to_scan()`:**
1. Load `.wpv-verification.json`
2. For each file about to be scanned:
   - If file is in `ignored_files` → compute current MD5 hash
   - If hash matches stored hash → skip file entirely (do not scan)
   - If hash differs → file has changed → remove from `ignored_files`, scan normally
3. After scan completes, any file removed from `ignored_files` due to hash mismatch gets re-scanned and new issues appear in `.wpv-results.json`

**Files to modify:**
- `includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php` — `get_files_to_scan()` reads `ignored_files`, skips or invalidates
- `includes/Verification/JSON_Storage.php` — `get_ignored_files()`, `remove_ignored_file()`

**Testing:**
1. Get a file to fully-ignored state (Phase 6.1 complete)
2. Run a new plugin check
3. Verify ignored file does NOT appear in new results
4. Modify the ignored file on disk
5. Run another plugin check
6. Verify the modified file IS scanned and its issues appear in results
7. Verify the file's entry is removed from `ignored_files` in `.wpv-verification.json`

### Phase 6.4: Unignore File (Manual Override)
**Requirement:** Allow developer to manually unignore a file from TAB04.
**Where:** Show "Unignore File" button somewhere accessible — possibly a separate ignored files list panel.
**Logic:**
1. Remove file from `ignored_files` in `.wpv-verification.json`
2. Do NOT restore issues to `.wpv-results.json` (they are gone — user must re-run check)
3. On next plugin check, file will be scanned normally

---

## PHASE 3: Function-Level Ignore System 📋 PLANNED (DELAYED)

### Why Delayed
Function-level ignoring is only useful when a developer wants to run checks on a file that has false positives — they want new code checked but don't want old false positives showing up. This is primarily needed for the Overwatch system (active file monitoring during development). Since Overwatch requires function-level ignoring to work first, both are planned together.

### Overview
Same concept as file-level ignoring but scoped to individual functions/methods. A function is ignored when all its issues are individually ignored. Hash is computed from the function body only (not the whole file), so changes to other functions in the same file don't invalidate the ignore.

### Phase 3.1: Function Detection
- Use `token_get_all()` to map line numbers to containing function/method
- Add `function_name`, `function_hash`, `class_name` fields to issues in `.wpv-results.json`
- Store function-level ignores in `.wpv-verification.json` under `ignored_functions` key

### Phase 3.2: Function-Level UI
- Function accordion grouping in TAB04 (below file accordion)
- Function-level Ignore action
- Hash validation: if function body changes → invalidate function ignore

### Phase 3.3: Overwatch System (Requires Phase 3.1 + 3.2)
- Actively monitors files while a project is being developed
- On file save → re-scan only changed functions
- Ignored functions are skipped
- New issues in non-ignored functions appear immediately in TAB04

---

## PHASE 4: Real-Time Verification Progress 📋 PLANNED
- Replace simulated progress bar with actual file-based progress tracking
- Show "Processing file X of Y" with current filename
- Estimated time remaining based on processing speed

---

## PHASE 7: JSON Storage Directory Migration 📋 PLANNED

### Overview
Move all `.wpv-*.json` files from the target plugin's root into a dedicated `wpevolveverifier/` subfolder. This keeps the plugin root clean and uses a unique folder name to avoid collisions. A `README.md` is placed inside the folder explaining its purpose.

### Phase 7.0: Path_Builder Consolidation (Prerequisite) ⚠️ CRITICAL
**Problem:** Multiple files bypass `Path_Builder` and hardcode JSON paths directly. A directory change will break them all unless consolidated first.

**Files that hardcode JSON paths (must be refactored to use Path_Builder):**
- `includes/Admin/Results_AJAX_Handler.php` — `$dir . '/.wpv-results.json'`
- `includes/Admin/Saved_Results_Handler.php` — `$plugin_dir . '/.wpv-results.json'`
- `includes/Admin/Config_AJAX_Handler.php` — hardcodes `.wpv-config.json`
- `includes/Admin/Verification_AJAX_Handler.php` — hardcodes `.wpv-config.json`
- `includes/Admin/Hash_AJAX_Handler.php` — hardcodes `.wpv-verification.json` (×2)
- `includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php` — hardcodes `.wpv-config.json` + `.wpv-verification.json`
- `includes/Utilities/Template_Helper.php` — hardcodes all three JSON files
- `includes/Utilities/Plugin_Request_Utility.php` — hardcodes all three in `get_files_to_ignore()`
- `includes/Verification/Results_Storage.php` — own `RESULTS_FILE` constant
- `includes/Verification/Config_Storage.php` — own `CONFIG_FILE` constant
- `includes/Verification/JSON_Storage.php` — own `VERIFICATION_FILE` constant

**Action:** Every reference above must call `Path_Builder::get_results_file_path()`, `get_config_file_path()`, or `get_verification_file_path()` instead. The Storage class constants should delegate to `Path_Builder` or be removed.

**Once consolidated, changing the directory is a single-line edit in `Path_Builder`.**

### Phase 7.1: Move JSON Files to `wpevolveverifier/` Subfolder
**Changes to `Path_Builder`:**
- Update `get_results_file_path()`, `get_config_file_path()`, `get_verification_file_path()` to return paths under `wpevolveverifier/` subfolder
- Add `get_storage_directory_path( $plugin_slug )` method that returns `{plugin_root}/wpevolveverifier/`
- Add `ensure_storage_directory( $plugin_slug )` method that creates the folder + `README.md` if missing

**`wpevolveverifier/README.md` contents:**
Auto-generated file explaining:
- This folder is created and managed by the WP Verifier plugin
- Contains verification results, configuration, and ignore tracking data
- Safe to delete (will be regenerated on next plugin check)
- Should be added to `.gitignore` for the target plugin

**Update `Plugin_Request_Utility::get_directories_to_ignore()`:**
- Add `wpevolveverifier` to the default ignored directories list (so the folder itself is never scanned)

### Phase 7.2: Migration of Existing Files
- On plugin load or first access, detect old `.wpv-*.json` files in plugin root
- Move them into `wpevolveverifier/` automatically
- Clean up old files from root after successful migration

---

## PHASE 8: Plugin Selection Duplicate File Warning 📋 PLANNED

### Overview
When a user selects a new plugin for verification, check if the target plugin already contains `wpevolveverifier/` folder or `.wpv-*.json` files. If files exist, display a clear admin warning: these files may not have been created by WP Verifier (e.g. manually placed, or from a different WP Verifier installation).

### Implementation
**Trigger:** Plugin selection change (when `wpv_last_selected_plugin` is updated via AJAX).
**Logic:**
1. Check if `wpevolveverifier/` folder or any `.wpv-*.json` files exist in the newly selected plugin's root
2. If found → return a warning flag in the AJAX response
3. JS displays an admin notice: _"This plugin already contains WP Verifier data files. These may have been created by a previous installation or another tool. Proceeding will use the existing data. Run a fresh check to regenerate."_
4. User can dismiss or proceed

**Files to modify:**
- `includes/Admin/AJAX_Handler_Manager.php` — add existence check on plugin selection
- `assets/js/` — display warning notice in UI

---

## Additional Features

### Hash-Aware Ignore / Verify Workflow
- Auto-expire ignores when hash mismatches (Phase 6.3 covers file level)
- Store verification metadata in `.wpv-verification.json`

### Review Queue & Audit Trail
- New issues since last scan
- Stale verifications (hash mismatch)
- Export verification/ignore history for QA reviews

### Internationalization (i18n)
- Pass translated strings to JavaScript via localized object
- Ensure all PHP strings use translation functions with 'wp-verifier' text domain
