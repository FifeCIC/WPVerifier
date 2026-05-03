# WP Verifier Development Roadmap

## PHASE 5: Remaining Cleanup 📋 PLANNED
- [ ] Strip TAB04/TAB05 code from `wp-verifier-ast.js` (keep TAB03 post-verification display)
- [ ] Remove `results-ast.php` template from `admin_footer()` if safe

---

## PHASE 6: File-Level Ignore System 🔄 CURRENT FOCUS

### Overview
When a developer has ignored ALL issues in a file one by one, the file is automatically marked as ignored in `.wpv-verification.json`. On subsequent plugin checks, ignored files are skipped entirely — making checks faster and keeping `.wpv-results.json` as a clean task list of only actionable issues.

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
- **Full Overwatch implementation is planned as part of the WP Verifier Pro premium module — see Phase 13**

---

## PHASE 4: Real-Time Verification Progress 📋 PLANNED
- Replace simulated progress bar with actual file-based progress tracking
- Show "Processing file X of Y" with current filename
- Estimated time remaining based on processing speed

---

## PHASE 4.5: Scan Performance Bottleneck Fixes 📋 PLANNED

### Overview
Profiling the limited-results scan revealed that a 250-issue limit completes in ~8 seconds while an unlimited scan producing ~1,577 issues takes ~2m 41s. The scaling is non-linear due to several O(n²) patterns and per-file overhead in the scan pipeline. This phase addresses the five identified bottlenecks.

### Bottleneck #1 (Critical): O(n²) issue counting in `Abstract_PHP_CodeSniffer_Check`
**File:** `includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php`
**Problem:** `run_with_issue_limit()` calls `count_current_issues()` before and after every file. That method calls `$result->get_errors()` and `$result->get_warnings()`, retrieves the full nested arrays, then walks every file → line → column → issue to count them. With 1,000 accumulated issues and 100 files to process, this is ~200,000 iterations just for counting.
**Fix:** `Check_Result` already maintains `$error_count` and `$warning_count` as integers, incremented in `add_message()`. Replace all `count_current_issues()` / `count_issues_in_array()` calls with `$result->get_error_count() + $result->get_warning_count()`. This is O(1) per check.

### Bottleneck #2 (Critical): Same O(n²) counting in `Checks.php`
**File:** `includes/Checker/Checks.php`
**Problem:** `run_checks()` calls `count_issues_in_result()` before and after each check type. Same full-array traversal as Bottleneck #1.
**Fix:** Same — use `$result->get_error_count() + $result->get_warning_count()` instead of `count_issues_in_result()`.

### Bottleneck #3 (Medium): Per-file PHPCS bootstrap overhead
**File:** `includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php`
**Problem:** `run_with_issue_limit()` calls `run_phpcs_on_files()` once per file. Each call performs: `$_SERVER['argv']` backup/restore, `get_args()`, `reset_php_codesniffer_config()` (Reflection), `Config::getConfigData`/`setConfigData`, full argv defaults rebuild including ignore patterns, `new Runner()` instantiation, JSON output parsing, and `Hash_Generator` instantiation per file with issues. For 100+ files this adds up.
**Fix:** Batch files into small groups (e.g. 10–20 files per PHPCS invocation) instead of one-at-a-time. After each batch, check the count. This reduces PHPCS bootstrap overhead by 10–20× while still allowing early termination at batch boundaries.

### Bottleneck #4 (Low): `Hash_Generator` instantiated per file
**File:** `includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php`
**Problem:** Inside `run_phpcs_on_files()`, a new `Hash_Generator` is created for every file that has issues. Object creation is cheap but unnecessary.
**Fix:** Instantiate `Hash_Generator` once outside the file loop and reuse it.

### Bottleneck #5 (Low): Dead code — `$issues_before` never used
**File:** `includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php`
**Problem:** `$issues_before = $this->count_current_issues( $result )` is computed but never referenced. It's a full array traversal for nothing.
**Fix:** Remove the line.

### Implementation Order
1. Fix #1 and #2 first (biggest impact, simplest change — swap method calls)
2. Fix #5 (trivial removal)
3. Fix #4 (move instantiation outside loop)
4. Fix #3 (batch file processing — more involved refactor)

### Expected Impact
Fixes #1 and #2 alone should reduce unlimited scan time from ~2m 41s to roughly proportional to the 250-limit time (~8s) scaled by file count, since the per-file PHPCS work is constant but the counting overhead that grows quadratically is eliminated. Estimated unlimited scan time after fixes: 30–60 seconds.

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

## PHASE 9: Single File Re-Scan 📋 PLANNED

### Overview
Allow a developer to re-scan a single file directly from TAB04 without running a full plugin check. Useful when working through issues file by file — fix the code, re-scan, confirm issues are resolved.

### UI
- Add a "Re-scan File" button to PAN01 (Issue Details sidebar) in TAB04
- Button is contextual to the currently selected issue's file
- Shows the filename being scanned for clarity

### Logic
1. AJAX request sends the relative file path to a new handler
2. Handler runs PHPCS against that single file only (pass absolute file path to PHPCS instead of plugin directory)
3. Remove all existing issues for that file from `.wpv-results.json`
4. Insert new issues for that file (keyed by file path, same structure)
5. Recalculate readiness score
6. Redirect to TAB04 with the file's first new issue selected, or show a "No issues found" state if clean

### Limitations
- Non-PHPCS checks (`missing_direct_file_access_protection`, `stable_tag_mismatch`, readme checks) run at plugin level and will not be included in a single file scan
- Ignored files (hash match in `.wpv-verification.json`) will be skipped as normal

### Files to modify
- `includes/Checker/Checks/Abstract_PHP_CodeSniffer_Check.php` — accept optional single file path in `get_files_to_scan()`
- `includes/Admin/Verification_AJAX_Handler.php` — new `rescan_single_file()` method
- `templates/admin-page-results.php` — add Re-scan File button to PAN01

---

## PHASE 10: Native Custom Check Engine 📋 PLANNED

### Overview
WPCS/PHPCS covers style and some security patterns but leaves a significant layer of semantic code quality, WordPress idiom compliance, and documentation completeness entirely unchecked. Phase 10 introduces a first-party custom check engine — a structured library of WP Verifier–owned checks that run alongside PHPCS and produce findings in the same format as existing issues, complete with their own error codes, AI guidance, inline examples, and per-code global overrides.

The goal is a perfectionist's toolkit: every check is precisely documented, individually toggleable, and surfaced through the existing Results and Error Codes UI.

---

### Phase 10.1: Custom_Check Base Architecture

**Core concept:** A single abstract base class that every custom check extends. Each check is self-describing — it carries its own code, title, severity, category, description, AI guidance, and code examples as class properties. This makes adding a new check as simple as creating a new file and filling in the metadata.

**`includes/Checker/Custom/Abstract_Custom_Check.php`**
```
Abstract_Custom_Check
  - string $code          — unique check code, e.g. WPV-PHP-001
  - string $title         — short human-readable title
  - string $severity      — 'error' | 'warning' | 'info'
  - string $category      — 'php_quality' | 'wp_idioms' | 'documentation' | 'correctness'
  - string $description   — full explanation of what the check detects
  - string $guidance      — AI guidance text (same field as existing ai_guidance)
  - array  $examples      — [ 'bad' => '...', 'good' => '...' ] code snippets
  - abstract run( $file_path, $tokens ) : array  — returns array of findings
```

Each `run()` call receives the file path and a pre-tokenised array (from `token_get_all()`) so checks can inspect the AST without re-parsing. Findings are returned as arrays matching the existing `.wpv-results.json` issue structure so they slot directly into the results pipeline.

**`includes/Checker/Custom/Custom_Check_Registry.php`**
- Holds all registered check instances
- `register( Abstract_Custom_Check $check )` — adds a check
- `get_all()` — returns all registered checks
- `get_enabled()` — returns only checks not globally disabled via the Error Codes tab
- `get_by_code( $code )` — lookup by WPV code
- Auto-discovers checks in `includes/Checker/Custom/Checks/` on init

**`includes/Checker/Custom/Custom_Check_Runner.php`**
- Iterates files to scan (same list as PHPCS runner)
- For each file: tokenises once, passes tokens to every enabled check
- Merges findings into `.wpv-results.json` alongside PHPCS findings
- Respects file-level and function-level ignores (Phase 6 / Phase 3)

---

### Phase 10.2: WPV Error Code System

Every custom check gets a unique code in the format `WPV-{CATEGORY}-{NNN}`:

| Prefix | Category |
|---|---|
| `WPV-PHP` | PHP quality & correctness |
| `WPV-WP` | WordPress idiom compliance |
| `WPV-DOC` | Documentation completeness |
| `WPV-SEC` | Security patterns beyond PHPCS |
| `WPV-PERF` | Performance patterns |

Examples:
- `WPV-PHP-001` — Stray escape sequence in string literal
- `WPV-PHP-002` — `json_encode()` used instead of `wp_json_encode()`
- `WPV-WP-001` — `array()` used instead of short array syntax `[]` (or vice versa, configurable)
- `WPV-WP-002` — `absint()` preferred over `(int)` cast for user input
- `WPV-DOC-001` — Function/method missing `@since` tag
- `WPV-DOC-002` — Function/method missing `@version` tag
- `WPV-DOC-003` — Function/method missing `@return` tag
- `WPV-DOC-004` — Class missing docblock entirely
- `WPV-SEC-001` — `sanitize_text_field()` used on an email value (should be `sanitize_email()`)

Codes are stable — once assigned they never change, so existing `.wpv-results.json` files remain valid across WP Verifier upgrades.

---

### Phase 10.3: Error Codes Tab (TAB08) Enhancements

TAB08 currently displays PHPCS error codes with AI guidance. Phase 10.3 extends it to be the control centre for all check codes — both PHPCS and WPV native.

**Per-code controls:**
- **Enabled / Disabled toggle** — globally disable a specific code across all scans for the current plugin. Stored in `.wpv-config.json` under `disabled_codes[]`.
- **Severity override** — change a code from Warning to Error or Info without editing any PHP.
- **Examples panel** — expandable section showing the `bad` / `good` code snippets defined in the check class. PHPCS codes show examples where available from the ai-guidance-config.
- **AI Guidance** — existing field, now also populated for WPV native codes from the check class `$guidance` property.

**UI layout per code row:**
```
[CODE]  [TITLE]                    [CATEGORY]  [SEVERITY ▾]  [● Enabled]
  ↳ Description text
  ↳ [AI Guidance]  [Examples ▾]  [Override Notes]
```

**Filtering:**
- Filter by category (PHP Quality / WP Idioms / Documentation / Security / Performance / PHPCS)
- Filter by source (WPV Native / PHPCS)
- Filter by enabled/disabled state
- Search by code or title

**`.wpv-config.json` additions:**
```json
{
  "disabled_codes": ["WPV-DOC-002", "WPV-WP-001"],
  "severity_overrides": {
    "WPV-DOC-001": "error",
    "WordPress.DB.DirectDatabaseQuery.DirectQuery": "info"
  }
}
```

---

### Phase 10.4: Initial Check Library

First batch of checks to ship with the engine. Each is a concrete class in `includes/Checker/Custom/Checks/`.

**PHP Quality**
- `WPV-PHP-001` — Stray escape sequence (`\n`, `\t` etc.) in a single-quoted string literal where it has no effect
- `WPV-PHP-002` — `json_encode()` used; `wp_json_encode()` preferred in WordPress context
- `WPV-PHP-003` — Variable assigned but never read within its scope
- `WPV-PHP-004` — `count()` called inside a loop condition (performance)

**WordPress Idioms**
- `WPV-WP-001` — `(int)` cast on user input; `absint()` preferred
- `WPV-WP-002` — `sanitize_text_field()` applied to an email field; `sanitize_email()` preferred
- `WPV-WP-003` — Direct `echo` of translated string; `esc_html_e()` / `esc_attr_e()` preferred
- `WPV-WP-004` — `add_option()` called without explicit `$autoload` argument

**Documentation**
- `WPV-DOC-001` — Public function or method missing `@since` tag
- `WPV-DOC-002` — Function or method missing `@version` tag
- `WPV-DOC-003` — Function or method missing `@return` tag when return value is non-void
- `WPV-DOC-004` — Class missing docblock entirely
- `WPV-DOC-005` — File header docblock missing `@package` tag

**Correctness**
- `WPV-PHP-005` — `isset()` used with multiple comma-separated superglobal keys (sniff cannot confirm each is sanitised at point of use)
- `WPV-PHP-006` — `end()` called on a function return value directly (deprecated in PHP 8.1+)

---

### Phase 10.5: Check Configuration UI (TAB08 Extension)

Some checks are configurable — e.g. `WPV-WP-001` could be set to warn on `(int)` only when the value comes from `$_GET`/`$_POST`, not internal calculations. Phase 10.5 adds a per-code configuration panel in TAB08 for checks that expose options.

**`Abstract_Custom_Check::get_options()` — optional override:**
```php
public function get_options() : array {
    return [
        'scope' => [
            'type'    => 'select',
            'label'   => 'Apply to',
            'options' => [ 'all' => 'All integers', 'user_input' => 'User input only' ],
            'default' => 'user_input',
        ],
    ];
}
```

Options are stored in `.wpv-config.json` under `check_options.{code}` and passed to `run()` at scan time.

---

### Phase 10.6: Results Integration

WPV native findings appear in TAB04 (Results) identically to PHPCS findings:
- Same accordion structure, same sidebar detail panel
- `source` field in the issue distinguishes `wpv_native` from `phpcs`
- WPV codes link to TAB08 for the full description, examples, and toggle
- AI guidance populated from the check class `$guidance` property (no separate config file needed)
- Fix/Ignore/Unignore AJAX works identically

---

### Phase 10.7: Developer Documentation

`docs/CUSTOM-CHECKS.md` — guide for adding new checks:
1. Create `includes/Checker/Custom/Checks/Check_WPV_XXX_NNN.php`
2. Extend `Abstract_Custom_Check`
3. Fill in the metadata properties
4. Implement `run( $file_path, $tokens ) : array`
5. Register in `Custom_Check_Registry` (or rely on auto-discovery)
6. The check appears automatically in TAB08 and runs on the next scan

The guide includes a full annotated example check and a reference table of all token constants used in common patterns.

---

### Phase 10 Milestones Summary

| Milestone | Deliverable |
|---|---|
| 10.1 | `Abstract_Custom_Check`, `Custom_Check_Registry`, `Custom_Check_Runner` |
| 10.2 | WPV code namespace, code assignment table, stable code registry |
| 10.3 | TAB08 per-code toggle, severity override, examples panel, filtering |
| 10.4 | First 13 checks shipped and tested |
| 10.5 | Per-check configuration options in TAB08 |
| 10.6 | Results tab integration, source badge, TAB08 deep-link |
| 10.7 | `CUSTOM-CHECKS.md` developer guide |

---

## PHASE 11: Export Formats 📋 PLANNED

### Overview
Downloadable export formats for results data. The sharing and client-communication features (temporary public URL, PDF report) are part of the WP Verifier Pro premium module — see Phase 13. The formats below are free-tier exports intended for developer use: piping results into CI/CD pipelines, spreadsheets, or bug trackers.

---

### Phase 11.2: XML Export

**Concept:** Structured XML export mirroring the PHPCS XML output format so existing tooling that consumes PHPCS XML can consume WP Verifier output without modification.

```xml
<results plugin="wpseed" version="2.0.0" scan_date="2026-01-15">
  <file path="includes/admin/admin-settings.php" errors="0" warnings="2">
    <issue line="42" code="WordPress.DB.DirectDatabaseQuery.DirectQuery"
           type="WARNING" severity="5" status="open"
           message="Use of a direct database call is discouraged." />
  </file>
</results>
```

**Files to create:** `includes/Export/XML_Exporter.php`

---

### Phase 11.3: Export UI in TAB04

A compact export toolbar added to the TAB04 header:

```
[ Export ▾  CSV / XML ]  [ Share Results → Pro ]
```

- Export dropdown: triggers the relevant exporter
- Share Results: teaser link pointing to the Pro module (Phase 13)

---

### Phase 11 Milestones Summary

| Milestone | Deliverable |
|---|---|
| 11.1 | ~~CSV export~~ ✅ Complete |
| 11.2 | XML export in PHPCS-compatible format |
| 11.3 | Export toolbar in TAB04 |

---

## PHASE 13: WP Verifier Pro — Active Development Monitor & Client Communication 📋 PLANNED (PREMIUM MODULE)

### Overview

WP Verifier Pro is a paid add-on module that extends the free plugin with two capabilities that go beyond code auditing into active development support and professional client communication.

**Active Development Monitor (Overwatch)** — watches plugin files in real time as a developer writes code, scanning changed functions immediately and surfacing new issues without requiring a manual full-plugin check.

**Client Communication Suite** — gives developers professional tools to share verification results with clients, generate branded reports, and present audit progress in a way that non-technical stakeholders can understand.

Both capabilities are premium because they require infrastructure beyond a standard WordPress plugin: Overwatch needs a persistent polling or file-watch mechanism, and the Client Communication Suite needs a hosted public-facing layer for shareable URLs and branded PDF generation.

---

### Phase 13.1: Overwatch — Active Development Monitor

**Concept:** While a developer is actively working on a plugin, Overwatch continuously monitors the plugin's files and re-scans only the functions that have changed since the last scan. New issues appear in TAB04 within seconds of saving a file — no manual check required.

**Prerequisites:** Phase 3.1 (function detection) and Phase 3.2 (function-level ignore) must be complete before Overwatch can be built.

**How it works:**

1. Developer activates Overwatch for the current plugin from a new TAB in WP Verifier Pro
2. A WordPress cron job (or optional server-side file watcher via WP-CLI) polls the plugin directory every N seconds (configurable: 5s / 15s / 30s / 60s)
3. On each poll, file MD5 hashes are compared against the last-known state
4. Changed files are tokenised; only functions whose body hash has changed are re-scanned
5. New issues are written to `.wpv-results.json` and a badge count on TAB04 updates via AJAX long-poll
6. Ignored functions are skipped entirely — only new or changed code is checked
7. Developer sees a live "Overwatch active" indicator with last-scan timestamp and files-watched count

**Overwatch session log:**
- Each Overwatch session is recorded: start time, end time, files watched, functions re-scanned, issues found
- Session log is visible in the Pro tab for review and export

**Performance safeguards:**
- Maximum poll frequency capped at 5 seconds to prevent server overload
- Overwatch automatically pauses if CPU load exceeds a configurable threshold
- Overwatch stops automatically after a configurable idle period (default: 30 minutes of no file changes)
- Only the plugin currently selected in WP Verifier is watched — never the entire WordPress installation

**Files to create:**
- `pro/Overwatch/Overwatch_Controller.php` — session management, start/stop/pause
- `pro/Overwatch/File_Watcher.php` — hash-based change detection
- `pro/Overwatch/Function_Rescanner.php` — targeted re-scan of changed functions only
- `pro/Overwatch/Session_Logger.php` — session recording and retrieval
- `pro/templates/admin-page-overwatch.php` — Overwatch tab UI
- `pro/assets/js/overwatch.js` — live badge update, status indicator, AJAX long-poll

---

### Phase 13.2: Overwatch Notification System

**Concept:** When Overwatch detects new issues, the developer is notified immediately without having to watch the screen.

**Notification channels:**
- **WordPress admin bar badge** — issue count badge on the WP Verifier admin bar icon updates in real time
- **Browser notification** — optional Web Notifications API alert (requires one-time browser permission grant)
- **Admin notice** — dismissible notice at the top of any wp-admin screen when new issues are found
- **Email digest** — optional email summary of issues found during the current Overwatch session, sent when Overwatch stops

**Notification settings** (per-developer, stored in user meta):
- Enable / disable each channel independently
- Minimum severity threshold for notifications (e.g. only notify on Error or Critical, not Warning)
- Email digest: immediate / end of session / daily summary

**Files to create:**
- `pro/Overwatch/Notification_Manager.php`
- `pro/assets/js/overwatch-notifications.js` — Web Notifications API integration

---

### Phase 13.3: Client Communication — Temporary Public Results URL

**Concept:** Generate a unique, time-limited URL that renders a read-only view of the current verification results. The recipient sees a clean, professional presentation of the audit without needing wp-admin access.

**How it works:**
1. Developer clicks "Share with Client" in TAB04
2. WP Verifier Pro generates a UUID v4 token stored as a transient (default TTL: 72 hours, configurable up to 30 days)
3. A public URL is produced: `https://site.com/?wpv_share={token}`
4. The URL renders a standalone branded results page
5. Token expires automatically; developer can revoke it immediately from TAB04

**What the shared view shows:**
- Plugin name and version, scan date, readiness score with visual indicator
- Summary: total issues by severity, files affected, percentage resolved
- Full issue list grouped by file — same accordion structure as TAB04
- Issue detail on click: code, plain-English message, line reference, AI guidance
- Optional developer note (free-text field set at share time, e.g. "Issues from initial audit — 60% resolved as of [date]")
- "Powered by WP Verifier" footer

**What it does NOT show:**
- Fix / Ignore / Unignore action buttons
- Internal server paths beyond what is already in the issue
- Any other plugin or site data

**Security:**
- Token is UUID v4 — 128-bit random, not guessable
- Transient TTL enforced server-side — expired tokens return a clean 404 page
- No authentication required to view (by design — it is a share link)
- Developer can set a shorter TTL or revoke immediately

**Files to create:**
- `pro/Share/Share_Handler.php` — token generation, storage, revocation
- `pro/Share/Public_Results_Controller.php` — handles `?wpv_share=` query var
- `pro/templates/public-results.php` — standalone branded results template
- `pro/assets/css/public-results.css` — clean stylesheet with no wp-admin dependency
- TAB04 template update — "Share with Client" button, active share link display, TTL indicator, revoke button

---

### Phase 13.4: Client Communication — PDF Report

**Concept:** Professionally formatted PDF report suitable for client delivery, QA sign-off records, or project documentation.

**Report contents:**
- Cover page: plugin name, version, scan date, readiness score, developer/agency branding (configurable logo and name)
- Executive summary: total issues, files affected, top 5 most common error codes, issues resolved vs outstanding
- Per-file breakdown: each file as a section with its issues listed
- Per-issue detail: code, line, plain-English message, severity, AI guidance (condensed)
- Appendix: full list of ignored issues with ignore reason
- Footer: generated by WP Verifier Pro, EvolveWP.dev

**Branding options** (stored in Pro settings):
- Agency/developer name
- Logo URL
- Accent colour
- Custom footer text

**Implementation:** PHP-only PDF library (TCPDF, FPDF, or Dompdf — no server binary dependency). Generated on demand via AJAX, streamed as a file download, never stored on disk.

**Filename format:** `wpv-{plugin-slug}-audit-{date}.pdf`

**Files to create:**
- `pro/Export/PDF_Exporter.php`
- `pro/templates/pdf-report.php` — HTML template fed to the PDF renderer
- `pro/assets/css/pdf-report.css`
- `pro/Settings/Branding_Settings.php` — logo, name, colour, footer text

---

### Phase 13.5: Client Communication — Audit Progress View

**Concept:** A simplified, non-technical view of audit progress designed specifically for clients who want to know "how is it going?" without understanding PHPCS error codes.

**What it shows:**
- A progress bar: "X of Y issues resolved"
- Severity breakdown in plain English: "3 security issues, 12 code quality issues, 8 documentation gaps"
- A timeline of scan dates showing the trend (issues going down over time)
- Current status label: In Progress / Review Ready / Clean
- The developer's optional status note

**Access:** Available via the same shared URL as Phase 13.3, toggled by a "Client View / Technical View" switch at the top of the shared page.

**Files to create:**
- `pro/templates/public-results-client.php` — simplified client-facing template
- `pro/assets/js/public-results-toggle.js` — view switcher

---

### Phase 13.6: Pro Licensing & Activation

**Concept:** Standard licence key activation for the Pro module. Licence is tied to a domain and validated against EvolveWP.dev.

**Licence tiers:**

| Tier | Overwatch | Sharing & PDF | Branding | Sites |
|---|---|---|---|---|
| Pro Single | ✅ | ✅ | ✅ | 1 |
| Pro Agency | ✅ | ✅ | ✅ | Unlimited |

**Activation flow:**
1. Developer purchases licence at EvolveWP.dev
2. Enters licence key in WP Verifier → Settings → Pro
3. Key validated against EvolveWP.dev API (one outbound request on activation only)
4. Pro features unlock immediately; licence status cached locally
5. Annual re-validation on licence renewal date

**Graceful degradation:** If licence expires or cannot be validated, Pro features are hidden but all free features continue to work normally. No data is lost.

**Files to create:**
- `pro/Licensing/Licence_Manager.php`
- `pro/Licensing/Licence_Validator.php`
- `pro/templates/admin-page-pro-settings.php`

---

### Phase 13 Milestones Summary

| Milestone | Deliverable |
|---|---|
| 13.1 | Overwatch file watcher, function re-scanner, session logger, Overwatch tab UI |
| 13.2 | Notification system: admin bar badge, browser notification, email digest |
| 13.3 | Temporary public results URL with UUID token, TTL, revoke, developer note |
| 13.4 | PDF report with branding options, streamed on demand |
| 13.5 | Client-facing audit progress view with plain-English severity summary |
| 13.6 | Pro licence key activation, EvolveWP.dev validation, graceful degradation |

---

## PHASE 12: Professional Services Quote 📋 PLANNED (FREE TIER)

### Overview
When a scan reveals a significant number of issues, WP Verifier shows a contextual, dismissible offer panel with a weighted effort estimate and a link to EvolveWP.dev professional remediation services. Fully compliant with WordPress.org plugin guidelines (section 11) — dismissible, no automatic data transmission, no obtrusive UI.

### Scoring Formula

| Factor | Weight |
|---|---|
| Critical severity issue | ×4 |
| Error severity issue | ×3 |
| Warning severity issue | ×1 |
| Security-category issue (any severity) | +50% on base weight |
| Distinct files affected | ×0.5 per file (complexity multiplier) |
| Plugin file count | ×0.1 per file (context multiplier) |

### Four Effort Bands

| Band | Score | Label |
|---|---|---|
| 1 | 0–24 | Minor tidy-up |
| 2 | 25–74 | Moderate remediation |
| 3 | 75–149 | Significant work |
| 4 | 150+ | Major audit required |

Only the band label and a plain-English description are shown — not the raw score.

### Offer Panel

- Shown at Band 2+ as a dismissible sidebar card in TAB04 (not a modal, not blocking)
- Dismiss stored in user meta; does not reappear until a new scan produces a higher score
- CTA links to `https://evolvewp.dev/plugin-audit/?band={n}&slug={slug}&src=wpverifier`
- No issue details, no file paths, no PII transmitted

**Example (Band 3):**
```
⚠️  Significant remediation work detected

This plugin has a high concentration of security and database-related
issues across 14 files. Resolving these typically takes 8–16 hours.

[ Get a professional quote → EvolveWP.dev ]   [ Dismiss ]
```

### Files to Create
- `includes/Services/Effort_Estimator.php` — scoring logic and band calculation
- `includes/Services/Effort_Band.php` — band definitions and plain-English descriptions
- `includes/Services/Offer_Panel.php` — panel rendering and dismiss logic

### Phase 12 Milestones Summary

| Milestone | Deliverable |
|---|---|
| 12.1 | `Effort_Estimator` with weighted scoring and four-band output |
| 12.2 | Dismissible offer panel in TAB04, shown at Band 2+ |
| 12.3 | EvolveWP.dev landing page query string integration |

---

## PHASE 14: Community Score Submission & Feedback 📋 PLANNED (FREE TIER)

### Overview
After a successful scan, developers can voluntarily submit their plugin's readiness score and a short comment to EvolveWP.dev. Submissions go through moderation before appearing publicly. The feature serves three purposes: community benchmarking (developers can see how their score compares to others), product feedback for WP Verifier development, and a lead-generation signal for EvolveWP professional services.

All submission is opt-in, clearly labelled, and fully compliant with WordPress.org plugin guidelines — no data is sent without an explicit user action.

---

### Phase 14.1: Submission UI in TAB04

**Trigger:** Shown after a scan completes, as a collapsible panel below the readiness score display. Not shown if the score is 100 (nothing useful to submit).

**Panel contents:**
- Readiness score (pre-filled, read-only)
- Plugin slug (pre-filled from current selection, editable)
- WP Verifier version (pre-filled, read-only)
- Developer comment (free-text textarea, max 500 characters)
  - Placeholder: *"e.g. First scan of a legacy plugin — most issues are in vendor code"*
- Email address (optional — used for correlation on EvolveWP.dev, never displayed publicly)
- Website URL (optional)
- Consent checkbox: *"I agree to submit this score and comment to EvolveWP.dev for moderation and potential public display"*
- Submit button
- Dismiss link (stores dismiss in user meta for 30 days)

**What is submitted:**
```json
{
  "plugin_slug": "wpseed",
  "score": 85,
  "errors": 1,
  "warnings": 13,
  "wpv_version": "1.9.0",
  "wp_version": "6.9",
  "php_version": "8.2",
  "comment": "Developer-supplied free text",
  "email": "optional@example.com",
  "website": "https://optional.example.com",
  "submitted_at": "2026-04-01 12:00:00"
}
```

**What is NOT submitted:** file paths, issue details, code content, server paths, user ID, site URL (unless developer explicitly provides website field).

**WordPress.org compliance:**
- Opt-in only — no submission without explicit checkbox consent ✅
- Dismissible ✅
- No automatic outbound requests ✅
- No PII collected beyond what the developer voluntarily provides ✅

**Files to create/modify:**
- `includes/Services/Score_Submission.php` — payload assembly, outbound POST to EvolveWP.dev API
- `templates/admin-page-results.php` — add submission panel below readiness score
- `assets/js/admin-results.js` — submission AJAX handler, dismiss logic

---

### Phase 14.2: EvolveWP.dev Submission Endpoint

WP Verifier POSTs to `https://evolvewp.dev/wp-json/evolvewp/v1/wpverifier/submit-score`.

The endpoint is part of the EvolveWP Feedback Service (see `ROADMAP-EVOLVEWPCORE-DECISIONS.md` Decision 11). It:
1. Validates the payload (required fields, score range, version format)
2. Stores the submission in the `evolvewp_wpv_submissions` database table with `status = pending`
3. Triggers a moderation queue notification to the EvolveWP admin
4. Returns a JSON response: `{ "success": true, "message": "Thank you — your submission is under review." }`

Rate limiting: maximum 3 submissions per IP per 24 hours to prevent abuse.

---

### Phase 14.3: Moderation & Public Display

**Moderation queue** (EvolveWP.dev admin):
- List view of pending submissions with approve / reject / edit actions
- Approved submissions become publicly visible on the WP Verifier page at EvolveWP.dev
- Rejected submissions are soft-deleted (retained for abuse tracking)
- Moderator can edit the comment before approval (e.g. remove accidental PII)

**Public display** (EvolveWP.dev):
- Score leaderboard / distribution chart: *"Most submitted scores fall between 70–90"*
- Recent approved comments feed (plugin slug, score, comment, date — no email or website shown publicly unless developer opted in)
- Filterable by WP Verifier version, PHP version, score band

**Customer correlation** (EvolveWP.dev internal):
- If email was provided, submission is linked to the EvolveWP customer record
- Customer service view shows: all submissions from this email, scores over time, comments, associated website
- Useful for identifying customers who are struggling (high issue counts, repeated submissions) and proactively offering help
- See Decision 11 in `ROADMAP-EVOLVEWPCORE-DECISIONS.md` for the full EvolveWP Feedback Service architecture

---

### Phase 14.4: In-Plugin Score History

After a developer submits, WP Verifier stores a local record of the submission in user meta:
- Submission date, score, comment snippet
- Moderation status (pending / approved / rejected) — polled from EvolveWP.dev on TAB04 load (cached for 1 hour)
- A small "Your submissions" panel in TAB04 showing the last 3 submissions and their status

---

### Phase 14 Milestones Summary

| Milestone | Deliverable |
|---|---|
| 14.1 | Submission panel in TAB04, payload assembly, outbound POST, dismiss logic |
| 14.2 | EvolveWP.dev submission endpoint, validation, DB storage, rate limiting |
| 14.3 | Moderation queue, public display, customer correlation view |
| 14.4 | In-plugin submission history panel with moderation status polling |

---

## PHASE 15: Automated Plugin Audit Pipeline 📋 PLANNED

### Overview
An automated system that downloads WordPress.org plugins, runs EvolveWP.Verifier against each one, generates a professional audit report, and queues an outreach email to the plugin author. The pipeline runs entirely on localhost, is controlled via a dedicated WP admin dashboard, and is designed to process plugins at a measured pace — one every few minutes — with parallel workers consuming a shared queue.

This phase covers only the pipeline mechanics and EvolveWP.Verifier's role within it. Outbound email campaign management, author contact tracking, and response handling are the responsibility of a separate orchestration plugin (see Phase 15.7).

---

### Phase 15.1: Database Schema

Three tables support the pipeline. All created on plugin activation.

**`wpv_audit_queue`** — jobs waiting to be processed:
```sql
CREATE TABLE wpv_audit_queue (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    plugin_slug     VARCHAR(200) NOT NULL UNIQUE,
    plugin_version  VARCHAR(50),
    status          ENUM('pending','running','complete','failed') DEFAULT 'pending',
    worker_id       VARCHAR(50) NULL,
    priority        TINYINT DEFAULT 5,
    claimed_at      DATETIME NULL,
    completed_at    DATETIME NULL,
    attempts        TINYINT DEFAULT 0,
    last_error      TEXT NULL,
    queued_at       DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**`wpv_audit_results`** — one row per completed scan:
```sql
CREATE TABLE wpv_audit_results (
    id                  BIGINT AUTO_INCREMENT PRIMARY KEY,
    plugin_slug         VARCHAR(200) NOT NULL,
    plugin_version      VARCHAR(50),
    plugin_name         VARCHAR(300),
    author_slug         VARCHAR(200),
    active_installs     INT,
    scanned_at          DATETIME,
    total_errors        INT,
    total_warnings      INT,
    readiness_score     INT,
    activation_status   ENUM('ok','error','skipped') DEFAULT 'skipped',
    activation_output   TEXT NULL,
    deactivation_status ENUM('ok','error','skipped') DEFAULT 'skipped',
    deactivation_output TEXT NULL,
    report_path         VARCHAR(500) NULL,
    scan_duration_secs  DECIMAL(8,2)
);
```

**`wpv_audit_scan_log`** — prevents re-scanning too soon:
```sql
CREATE TABLE wpv_audit_scan_log (
    plugin_slug     VARCHAR(200) PRIMARY KEY,
    last_scanned_at DATETIME,
    last_version    VARCHAR(50),
    scan_count      INT DEFAULT 1
);
```

**Rescan policy:** A plugin is eligible for re-queuing only if its version has changed since the last scan, or if the last scan was more than 90 days ago. This is enforced by the dispatcher before adding to the queue.

---

### Phase 15.2: WordPress.org Plugin Discovery

**Source:** The public WordPress.org plugins API — `api.wordpress.org/plugins/info/1.2/?action=query_plugins`

**Pagination:** The API returns up to 250 plugins per page. The dispatcher iterates pages until the queue reaches a configured maximum depth (default: 500 pending jobs).

**Filtering options** (configurable in the dashboard):
- Minimum active installs (default: 1,000 — filters out abandoned plugins)
- Last updated within N days (default: 365 — filters out unmaintained plugins)
- Specific tags or categories
- Exclude plugins already scanned within the rescan window

**Download:** Each plugin is downloaded from `downloads.wordpress.org/plugin/{slug}.latest-stable.zip` into a temporary working directory (`wp-content/wpv-audit-workspace/{slug}/`). The zip is extracted, scanned, then the entire directory is deleted after the scan completes regardless of outcome.

**Rate limiting:** One API request per 2 seconds. One plugin download per configured interval (default: 5 minutes between job starts across all workers combined). This is enforced at the dispatcher level, not per-worker.

---

### Phase 15.3: CLI Worker Architecture

Workers run as PHP CLI processes, completely outside of the web request cycle. This sidesteps `max_execution_time` entirely — CLI PHP has no execution time limit by default.

**Worker lifecycle:**
1. Worker starts, registers itself in a `wpv_workers` option with a unique ID and timestamp
2. Queries `wpv_audit_queue` for the oldest `pending` job, claims it atomically using a database transaction (prevents two workers taking the same job)
3. Downloads and extracts the plugin to the workspace directory
4. Runs WP Verifier scan via the existing `AJAX_Runner` pipeline (called directly, not via HTTP)
5. Optionally runs activation/deactivation test (Phase 15.4)
6. Generates the audit report (Phase 15.5)
7. Writes results to `wpv_audit_results` and `wpv_audit_scan_log`
8. Marks job `complete`, deletes workspace directory, picks up next job
9. If any step throws, marks job `failed` with error message, increments `attempts`

**Stale job recovery:** The dispatcher checks for jobs in `running` status with `claimed_at` older than 20 minutes and resets them to `pending` (up to 3 attempts before marking permanently `failed`).

**Parallelism:** On a machine with 8 GB free RAM and a modern multi-core CPU, 3–4 simultaneous workers is a reasonable starting point. Each PHPCS scan is CPU-bound; beyond the core count workers compete rather than cooperate. The dashboard shows a recommended worker count based on available CPU cores (read from `sys_getloadavg()` on Linux or a Windows equivalent).

**Worker script location:** `wp-content/plugins/WPVerifier/cli/audit-worker.php`

**Starting workers (example — 3 parallel):**
```
php cli/audit-worker.php --worker-id=1 &
php cli/audit-worker.php --worker-id=2 &
php cli/audit-worker.php --worker-id=3 &
```

The dashboard provides a copy-paste command block for the configured worker count.

---

### Phase 15.4: Activation & Deactivation Testing

One of the most valuable signals in the audit is whether the plugin activates and deactivates cleanly. Many plugins produce PHP notices, warnings, or fatal errors on activation that the author has never seen because they develop with error reporting off.

**How it works:**
1. Worker installs the downloaded plugin into the WordPress plugins directory
2. Calls `activate_plugin()` with output buffering active — captures any output produced during activation
3. Records: success/failure, any output captured, any PHP errors caught via a custom error handler
4. Calls `deactivate_plugins()` with the same capture approach
5. Removes the plugin from the plugins directory
6. Stores activation/deactivation status and captured output in `wpv_audit_results`

**Output capture categories:**
- Clean — no output, no errors
- Notice/Warning — PHP notices or warnings produced (non-fatal, but worth reporting)
- Fatal — activation caused a fatal error (very high value finding for the author)
- Output — unexpected HTML or text output produced (common cause of "headers already sent" issues)

**Safety:** The worker runs in an isolated WordPress context. If a plugin causes a fatal error, the worker catches it via `register_shutdown_function()`, records it, and continues to the next job. The plugin is always removed from the plugins directory after testing regardless of outcome.

---

### Phase 15.5: Report Generation

The audit report is a self-contained HTML file (and optionally PDF) generated per plugin. It is designed to be genuinely useful to the plugin author — a freebie that demonstrates value before any ask.

**Report sections:**
1. **Header** — plugin name, version, scan date, WP Verifier branding
2. **Readiness Score** — large visual score with status label (same as TAB04)
3. **Executive Summary** — total errors/warnings, files affected, top 5 most common error codes
4. **Activation Health** — clean / notice / warning / fatal, with captured output if any
5. **Issue Breakdown by Category** — security, code quality, documentation, performance
6. **Comparison Context** — "This plugin has X errors. The average plugin with similar install count has Y." (populated once enough scans exist in `wpv_audit_results`)
7. **Top Issues Detail** — the 10 most severe issues with file, line, plain-English explanation, and fix guidance (drawn from the existing AI guidance config)
8. **Full Issue List** — all issues in a compact table (errors first, then warnings)
9. **How to Fix** — brief section pointing to WordPress Coding Standards documentation and WP Verifier's free download link
10. **Footer** — generated by WP Verifier / EvolveWP.dev, unsubscribe notice

**Report storage:** `wp-content/wpv-audit-reports/{slug}-{version}-{date}.html`

**PDF generation:** Optional — uses Dompdf (already planned in Phase 13.4). PDF is attached to the outreach email.

---

### Phase 15.6: Audit Pipeline Dashboard

A new admin page within WP Verifier (`Tools → Plugin Audit Pipeline`) providing full visibility and control.

**Queue panel:**
- Total pending / running / complete / failed job counts
- Table of recent jobs with status, plugin slug, duration, worker ID
- Manual controls: Add plugin slug, Pause all workers, Clear failed jobs, Reset stale jobs
- Configurable scan interval (minutes between job starts)

**Worker panel:**
- Active worker count with last heartbeat timestamp
- Recommended worker count for current hardware
- Copy-paste CLI command to start N workers
- Stop all workers button (writes a stop flag file that workers poll)

**Results panel:**
- Total plugins scanned, average readiness score, score distribution chart
- Filter by score band, activation status, date range
- Per-plugin row: slug, version, score, errors, warnings, activation status, report link
- Bulk actions: Queue for re-scan, Mark for outreach, Export CSV

**Scan log panel:**
- Shows which plugins have been scanned and when
- Highlights plugins eligible for re-scan (version changed or >90 days old)

---

### Phase 15.7: Separation of Concerns — EvolveWP.Outreach Plugin

EvolveWP.Verifier's responsibility ends at generating the report and storing results. Everything related to contacting plugin authors — email composition, sending, queuing, response tracking, campaign management — belongs in a separate plugin within the EvolveWP ecosystem.

**Rationale:** Email outreach is a distinct domain from code verification. Mixing them would bloat EvolveWP.Verifier and create a plugin that does too many unrelated things. A dedicated outreach plugin can also serve other EvolveWP tools beyond EvolveWP.Verifier.

**EvolveWP.Verifier exposes a clean API for the outreach plugin to consume:**

```php
// REST endpoint — returns completed audits not yet handed to outreach
GET /wp-json/wpverifier/v1/audit-results?status=pending_outreach&limit=50

// REST endpoint — marks an audit as handed off
POST /wp-json/wpverifier/v1/audit-results/{id}/mark-outreach-sent

// WordPress action hook — fired when a new audit completes
do_action( 'wpv_audit_complete', $plugin_slug, $result_id, $report_path, $readiness_score );
```

The outreach plugin hooks into `wpv_audit_complete` and/or polls the REST endpoint to pick up new results.

**Proposed outreach plugin name:** `EvolveWP.Outreach` — see ecosystem architecture note below.

---

### Phase 15.8: Ecosystem Architecture Note — EvolveWP.Outreach Plugin

The question of where email campaign management, automation, and outreach logic lives across the EvolveWP ecosystem is worth resolving now before building.

**Recommendation: a dedicated `EvolveWP.Outreach` plugin**, separate from both EvolveWP.Verifier and EvolveWP.PredictiveERP.

**Reasoning:**
- EvolveWP.PredictiveERP is business/financial intelligence — mixing outreach campaign logic into it would blur its purpose and make it harder to sell as a focused product
- EvolveWP.Verifier is a developer tool — email campaigns are not part of its core value proposition
- A standalone outreach plugin can hook into *any* EvolveWP tool that fires action hooks, making it the universal communication layer for the entire ecosystem
- It can be offered as a free or paid add-on independently of EvolveWP.Verifier licensing

**What `EvolveWP.Outreach` handles:**
- Consuming audit results from WP Verifier via the REST API / action hooks
- Maintaining a contact database of plugin authors (slug → email/contact method)
- Email queue with configurable send rate (e.g. 10 emails per day, spread across business hours)
- Email template library with variation support — multiple subject lines and body variants rotated to reduce spam appearance
- Per-contact send history — never email the same author twice within a configurable window (default: 6 months)
- Response tracking — if an author replies, flag their record for manual follow-up
- Unsubscribe handling — one-click unsubscribe stored permanently, never contacts that address again
- SMTP relay integration (Mailgun, SendGrid, or Postmark) — not localhost mail
- Campaign analytics — open rates, reply rates, conversion tracking

**What it does NOT handle:**
- Running scans (WP Verifier's job)
- Generating reports (WP Verifier's job)
- Any ERP or financial data (WPPredictiveERP's job)

**Integration pattern:**
```
EvolveWP.Verifier  →  fires wpv_audit_complete hook
                       ↓
EvolveWP.Outreach  →  queues email job
                       ↓
                   sends via SMTP relay at configured rate
                       ↓
                   records outcome, handles replies/unsubscribes
```

---

### Phase 15 Milestones Summary

| Milestone | Deliverable |
|---|---|
| 15.1 | Database schema — queue, results, scan log tables |
| 15.2 | WordPress.org API discovery, download, rate limiting |
| 15.3 | CLI worker — claim, scan, write results, stale job recovery |
| 15.4 | Activation/deactivation testing with output capture |
| 15.5 | Report generation — HTML + optional PDF, all 10 sections |
| 15.6 | Audit pipeline dashboard — queue, workers, results, scan log |
| 15.7 | WP Verifier REST API + action hook for outreach plugin integration |
| 15.8 | EvolveWP Outreach plugin architecture decision documented |

---

## Ecosystem Plugin Naming Convention

All plugins in the EvolveWP ecosystem follow the `EvolveWP.{ProductName}` naming convention. This satisfies WordPress.org guidelines ("WP" is not the first word), creates a consistent brand namespace, and reads cleanly in the repository where the Author field shows "EvolveWP".

| Plugin Name | WordPress.org Slug | Status |
|---|---|---|
| EvolveWP.Verifier | `evolvewp-verifier` | In development |
| EvolveWP.ClientJourney | `evolvewp-clientjourney` | Planned |
| EvolveWP.OpsStudio | `evolvewp-opsstudio` | Planned |
| EvolveWP.PredictiveERP | `evolvewp-predictiveerp` | Planned |
| EvolveWP.Outreach | `evolvewp-outreach` | Planned (Phase 15) |

The dot in the display name is a visual brand convention only — WordPress.org slugs use hyphens. Physical folder names on disk will be updated when each plugin reaches its first release.

---

## Additional Notes

### Hash-Aware Ignore / Verify Workflow
- Auto-expire ignores when hash mismatches (Phase 6.3 covers file level)
- Store verification metadata in `.wpv-verification.json`

### Review Queue & Audit Trail
- New issues since last scan
- Stale verifications (hash mismatch)
- Export verification/ignore history for QA reviews (Phase 11 covers export formats)

### Internationalization (i18n)
- Pass translated strings to JavaScript via localized object
- Ensure all PHP strings use translation functions with 'wp-verifier' text domain
