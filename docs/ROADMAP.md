# WP Verifier Development Roadmap

## PHASE 2: Path Building Consolidation ✅ COMPLETE

Centralized `Path_Builder` class created. All path logic consolidated. VSCode URLs working.

## PHASE 5: Results Tab Refactor ✅ MOSTLY COMPLETE

Merged TAB04 + TAB05 into single "Results" tab with pure PHP rendering. No JavaScript panel replacement.

### Completed
- [x] `templates/admin-page-results.php` — unified template, URL-driven sidebar via `$_GET['issue_id']`
- [x] `Admin_Page_Tabs.php` — single "Results" entry replaces TAB04+TAB05
- [x] `Admin_Page.php` — routing updated
- [x] `Asset_Manager.php` — single `enqueue_results_assets()` method
- [x] `assets/js/admin-results.js` — minimal JS (~80 lines): accordion, copy, Fixed/Ignore AJAX
- [x] Fixed button AJAX working via `wpv_mark_resolved`
- [x] Ignore button AJAX working via `wpv_mark_ignored`

### Fixed: Duplicate Issue IDs (Root Cause of "Fixed removes multiple issues")
The issue_id formula was `md5(file + line + code)` which produced identical IDs when multiple issues shared the same file, line, and code (different columns, or same rule flagged multiple times). Fixed by including column AND a counter: `md5(file + line + column + code + counter)`. **Requires re-running verification** to regenerate IDs.

### Remaining Cleanup
- [ ] Delete old files: `admin-page-issues-byfile.php`, `admin-page-issues.php`, `admin-page-saved.js`, `issues-tab.js`, `plugin-check-saved.js`
- [ ] Strip TAB04/TAB05 code from `wp-verifier-ast.js` (keep TAB03 post-verification display)
- [ ] Remove `results-ast.php` template from `admin_footer()` if safe
- [ ] Update TAB11 Architecture documentation
- [ ] Remove debug logging from `mark_issue_as_fixed()`

---

## PHASE 3: Function-Based Issue Management

**Objective**: Group issues by function/method instead of just file. Function-level ignore/fixed actions.

### Phase 3.1: JSON Schema Enhancement (HIGH PRIORITY)
- [ ] Add `function_name`, `function_hash`, `class_name`, `function_signature` fields to issues
- [ ] Use `token_get_all()` to map line numbers to containing function/method
- [ ] Backward compatibility for existing results without function data

### Phase 3.2: Function-Centric UI (MEDIUM PRIORITY)
- [ ] Accordion grouped by function (reuse TAB04 pattern)
- [ ] Function-level actions: Mark Fixed, Ignore, Copy Prompt
- [ ] VSCode button opens to function definition

### Phase 3.3: Enhanced Function Management (MEDIUM PRIORITY)
- [ ] Hash-based stale detection (function changed since ignore)
- [ ] Class → method hierarchy display
- [ ] Most problematic functions ranking

---

## PHASE 4: Real-Time Verification Progress

**Objective**: Replace simulated progress bar with actual file-based progress tracking.

### Phase 4.2: Client-Side Real-Time Updates (HIGH PRIORITY)
- [ ] Replace `startProgressSimulation()` with `pollProgressStatus()`
- [ ] Show "Processing file X of Y" with current filename
- [ ] Estimated time remaining based on processing speed

### Phase 4.3: Advanced Progress Features (LOW PRIORITY)
- [ ] Phase-specific progress weighting
- [ ] Processing speed analytics
- [ ] Per-category progress breakdown

---

## Internationalization Tool (i18n)

### JavaScript Translation (Medium Priority)
- [ ] Create translation object in PHP where scripts are enqueued
- [ ] Pass translated strings to JavaScript via localized object
- [ ] Update all JS files to use localized strings

### PHP Translation (Low Priority)
- [ ] Ensure all user-facing strings use translation functions
- [ ] Add text domain 'wp-verifier' consistently

---

## Developer Guidance Panel

- [ ] Footer panel visible only when `WP_DEVELOPMENT_MODE` is active
- [ ] Track loaded templates and display hierarchy
- [ ] Collapsible/expandable interface

---

## Additional Developer Workflow Enhancements

### Hash-Aware Ignore / Verify Workflow (High Priority)
- [ ] "Mark as Verified" action with notes
- [ ] Auto-expire ignores when hash mismatches
- [ ] Store verification metadata in `.wpv-verification.json`

### Review Queue & Audit Trail (High Priority)
- [ ] New issues since last scan
- [ ] Stale verifications (hash mismatch)
- [ ] Export verification/ignore history for QA reviews
