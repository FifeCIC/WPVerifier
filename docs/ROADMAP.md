# WP Verifier Development Roadmap
This roadmap consolidates all planned features and tracks implementation progress. Most features will be supported by existing folders, systems and standard approaches so check for existing implementation before creating new files, functions or classes.

## Hash-Based Issue Ignore System 🚫

**Goal**: Implement function-level hash tracking system that allows intelligent ignoring of issues. Ignored issues automatically resurface when the related code changes, preventing stale ignores from hiding newly relevant problems.

**Core Concept**: "Ignore this issue, but only while the code stays exactly the same"

**Storage**: JSON files stored within plugin directory for portability and version control.

### Phase 1: Core Tracking System (High Priority) - **WIP**
- [x] **Ignore Tracking File Structure**:
  - [x] Create `.wpv-verification.json` format specification
  - [x] Schema structure implemented
  - [x] Version field for schema evolution
  - [x] Store in plugin root directory for portability

- [x] **Hash Generation** (`includes/Verification/Hash_Generator.php`) - **COMPLETED**:
  - [x] Parse PHP files using `token_get_all()`
  - [x] Extract function/method/class boundaries (opening `{` to closing `}`)
  - [x] Generate SHA256 hash of function body, use first 8 chars
  - [x] Generate SHA256 hash of entire file for file-level verification
  - [x] Normalize whitespace before hashing
  - [x] Handle nested functions and closures

- [ ] **Ignore Status Matcher** (`includes/Verification/Verification_Matcher.php`) - **TODO**:
  - [ ] `is_ignored()` - Check if issue is currently ignored
  - [ ] Match by function name + hash
  - [ ] Match by file-level hash (entire file ignored)
  - [ ] Return ignore status: active/stale/none
  - [ ] Log when hash doesn't match (code changed, ignore expired)

### Phase 2: JSON Storage (High Priority) - **PARTIALLY COMPLETE**
- [x] **JSON Storage Handler** (`includes/Verification/JSON_Storage.php`) - **BASIC COMPLETE**:
  - [x] Read/write `.wpv-verification.json` in plugin root
  - [x] Atomic file writes (prevent corruption)
  - [x] Backup before modifications
  - [x] Initialize verification file for new plugins
  - [x] Validate JSON structure on load
  - [ ] Merge verification data from multiple sources - **TODO**

### Phase 3: Integration with Scanning (High Priority) - **IN PROGRESS**
- [x] **Hash Generation During Scan** - **COMPLETED**:
  - [x] Generate file hashes during `save_results()`
  - [x] Store hashes in `.wpv-results.json` alongside scan results
  - [x] Hash all files that have issues (errors/warnings)
  - [x] Preserve existing data when re-running scans

- [ ] **Modify `run_phpcs_on_file()`** - **TODO**:
  - [ ] Load ignore data before processing results
  - [ ] Check each issue against ignore status
  - [ ] Filter out actively ignored issues from results
  - [ ] Log when hash doesn't match (code changed, ignore expired)
  - [ ] Track ignore coverage (% of issues ignored)

- [ ] **Enhanced `save_results()`** - **TODO**:
  - [ ] Include ignore status in saved results
  - [ ] Store "ignored_count" in scan metadata
  - [ ] Separate ignored vs active issues in display
  - [ ] Show stale ignores (hash mismatch, need re-evaluation)

### Phase 4: UI Management (Medium Priority)
- [ ] **Ignore Management Page** (new tab in WP Verifier):
  - [ ] List all ignored functions/files
  - [ ] Filter by plugin, file, ignore status
  - [ ] Show ignore health (active/stale/invalid)
  - [ ] Bulk operations (re-ignore, remove ignore)
  - [ ] Search and sort functionality

- [ ] **Mark as Ignored from Results**:
  - [ ] "Ignore Issue" button in Selected Issue Details
  - [ ] Modal: Enter note, choose scope (this function/entire file)
  - [ ] Preview what will be ignored
  - [ ] Generate hash and save to `.wpv-verification.json`
  - [ ] Track who ignored and when

- [ ] **Ignore Health Dashboard**:
  - [ ] Show stale ignores (hash no longer matches)
  - [ ] Show ignore coverage per file
  - [ ] Show recently ignored items
  - [ ] Suggest expired ignores for re-evaluation

### Phase 5: Advanced Features (Medium Priority)
- [ ] **Batch Ignoring**:
  - [ ] Ignore all functions in a file at once
  - [ ] Ignore all files in a directory
  - [ ] Bulk ignoring with single note
  - [ ] Progress tracking for large batches

- [ ] **Ignore History**:
  - [ ] Track ignore changes over time
  - [ ] Show who ignored what and when
  - [ ] Revert to previous ignore state
  - [ ] Export ignore history

- [ ] **Team Collaboration**:
  - [ ] Track who ignored each function
  - [ ] Add comments/notes to ignores
  - [ ] Require approval for file-level ignoring
  - [ ] Audit log of ignore changes

### Phase 6: Validation & Cleanup (Low Priority)
- [ ] **Ignore Validation**:
  - [ ] Check all ignores against current codebase
  - [ ] Report stale ignores (hash mismatch)
  - [ ] Report orphaned ignores (function/file doesn't exist)
  - [ ] Suggest re-evaluation or removal
  - [ ] Auto-cleanup invalid ignores

- [ ] **Import/Export**:
  - [ ] Export ignores to shareable format
  - [ ] Import ignores from other projects
  - [ ] Merge ignore files from multiple sources
  - [ ] Conflict resolution UI

### Phase 7: Analytics & Reporting (Future)
- [ ] **Ignore Analytics**:
  - [ ] Ignore coverage by plugin/file
  - [ ] Ignore trends over time
  - [ ] Most ignored issue types
  - [ ] Team ignore activity

- [ ] **Smart Suggestions**:
  - [ ] Suggest functions ready for ignoring
  - [ ] Detect patterns in ignored issues
  - [ ] Recommend file-level ignoring when appropriate
  - [ ] Learn from team's ignore decisions

### Implementation Strategy - **UPDATED**

**Phase 1-2: Foundation (Week 1-2) - IN PROGRESS**
- [x] Build core verification tracking system
- [x] JSON storage in plugin directory  
- [x] Function-level hash generation
- [x] File-level hash generation during scans
- [ ] **NEXT**: Complete Verification_Matcher class
- [ ] **NEXT**: Integrate ignore checking into scan results

**Phase 3: Integration (Week 3) - PARTIALLY COMPLETE**
- [x] Hash generation integrated with scanning process
- [ ] Filter out ignored issues from results
- [ ] Track ignore coverage

**Phase 4: UI (Week 4-5) - PENDING**
- [ ] Add "Ignore Issue" functionality
- [ ] Ignore management interface
- [ ] Health dashboard for stale ignores

**Phase 5-7: Enhancement (Future)**
- [ ] Advanced features as needed
- [ ] Based on user feedback and usage patterns

### Current Status - **March 2026**

**✅ COMPLETED:**
- Hash_Generator class with file and function hashing
- JSON_Storage class with basic CRUD operations
- Hash generation integrated into save_results() method
- File hashes stored in .wpv-results.json alongside scan results
- Verification file initialization for new plugins

**🚧 IN PROGRESS:**
- Incremental testing approach (Step 2 complete)
- Hash data now appears in scan results JSON

**📋 NEXT STEPS:**
1. Create Verification_Matcher class
2. Integrate verification status checking into scan pipeline
3. Add ignore status to issue display
4. Build "Ignore Issue" UI functionality

### Benefits of Hash-Based Issue Ignoring

1. **Clean Code Files**: No inline comments cluttering code
2. **Intelligent Expiration**: Ignores automatically expire when code changes
3. **Prevents Stale Ignores**: Hash mismatch reveals when ignored issues need re-evaluation
4. **Portable**: JSON files travel with plugin, no database dependency
5. **Version Control Friendly**: Track ignore decisions in git
6. **Line-Number Independent**: Adding lines above doesn't invalidate ignores
7. **Audit Trail**: Complete history of who ignored what and when
8. **Dual-Level Tracking**: Both file-level and function-level ignoring
9. **Smart Workflow**: "Ignore while code unchanged" prevents permanent hiding of issues

### Technical Considerations

- **Hash Algorithm**: SHA256 (cryptographically secure)
- **Hash Length**: 8 characters (sufficient for collision avoidance)
- **Scope**: Function body from opening `{` to closing `}`
- **Normalization**: Trim whitespace, normalize line endings
- **Performance**: Cache parsed functions per request
- **Storage**: JSON files in plugin root directory
- **Portability**: Verification data travels with plugin package
- **Validation**: Regular checks for stale/orphaned ignores


## Internationalization (i18n) 🌍

**Goal**: Make WP Verifier fully translatable following WordPress standards.

### JavaScript Translation (Medium Priority)
- [ ] **Implement wp_localize_script() for JS strings**:
  - [ ] Create translation object in PHP where scripts are enqueued
  - [ ] Pass translated strings to JavaScript via localized object
  - [ ] Update all JS files to use localized strings instead of hardcoded text
  - [ ] Files to update:
    - [ ] `assets/js/plugin-check-preparation.js`
    - [ ] `assets/js/plugin-check-saved.js`
    - [ ] `assets/js/plugin-check.js`
    - [ ] Other JS files with user-facing text

### PHP Translation (Low Priority)
- [ ] **Audit all PHP files for translation readiness**:
  - [ ] Ensure all user-facing strings use `__()`, `_e()`, `esc_html__()`, `esc_html_e()`
  - [ ] Add text domain 'wp-verifier' to all translation functions
  - [ ] Add translator comments for context where needed

### Translation Infrastructure (Low Priority)
- [ ] **POT file generation**: Set up automated POT file generation
- [ ] **Translation platform**: Consider GlotPress or similar for community translations
- [ ] **RTL support**: Test and ensure RTL language compatibility


## Issues Tab Enhancement 🔍

**Goal**: Upgrade Issues tab to use WordPress core WP_List_Table class for professional features.

### Phase 1: WP_List_Table Implementation (Medium Priority)
- [ ] **Create Custom List Table Class** (`includes/Admin/Issues_List_Table.php`):
  - [ ] Extend WP_List_Table
  - [ ] Implement required methods: get_columns(), prepare_items(), column_default()
  - [ ] Load merged issues data (wpseed results + AI guidance)
  - [ ] Support severity badges (ERROR/WARNING)
  - [ ] Maintain accordion row expansion functionality

- [ ] **Add Search Functionality**:
  - [ ] Implement search_box() method
  - [ ] Search across file names, error codes, and messages
  - [ ] Real-time filtering of results
  - [ ] Clear search button

- [ ] **Add Bulk Actions**:
  - [ ] Checkbox column for row selection
  - [ ] Bulk action dropdown (Copy All Prompts, Mark as Reviewed, etc.)
  - [ ] "Select All" functionality
  - [ ] Process bulk actions handler

- [ ] **Column Sorting**:
  - [ ] Make all columns sortable (Severity, File, Line, Code)
  - [ ] Maintain sort state across page loads
  - [ ] Visual sort indicators

- [ ] **Pagination**:
  - [ ] Add pagination controls (10, 25, 50, 100 per page)
  - [ ] Screen options for items per page
  - [ ] Total items count display

### Phase 2: Enhanced Features (Low Priority)
- [ ] **Column Visibility**:
  - [ ] Screen options to show/hide columns
  - [ ] Save column preferences per user

- [ ] **Export Functionality**:
  - [ ] Export filtered results to CSV
  - [ ] Export with AI prompts included
  - [ ] Bulk export selected items

### Benefits
- Professional WordPress admin interface
- Built-in search and filtering
- Bulk operations for efficiency
- Better performance with pagination
- Consistent with WordPress UX patterns


## Developer Guidance Panel 🛠️

**Goal**: Create a development-mode footer panel that helps developers navigate the template system and find relevant files for modifications.

### Phase 1: Foundation (High Priority)
- [ ] **Footer Panel Infrastructure**:
  - [ ] Check for existing footer output functionality
  - [ ] Create footer panel that only displays when `WP_DEVELOPMENT_MODE` is active
  - [ ] Design collapsible/expandable panel interface
  - [ ] Position panel at bottom of page without interfering with normal functionality

- [ ] **Template Tracking System**:
  - [ ] Create early-set variable to track loaded templates
  - [ ] Hook into template loading process to capture file paths
  - [ ] Track view files, layout files, and styling files as they're loaded
  - [ ] Store template hierarchy information (parent/child relationships)

### Phase 2: TAB02 Implementation (High Priority)
- [ ] **TAB02 Specific Integration**:
  - [ ] Focus initial implementation on TAB02 functionality
  - [ ] Track templates specific to TAB02 operations
  - [ ] Display relevant file paths for TAB02 views and layouts
  - [ ] Show styling files that affect TAB02 presentation

- [ ] **File Path Display**:
  - [ ] Show full file paths to template files
  - [ ] Organize by file type (Views, Layouts, Styles, Scripts)
  - [ ] Make file paths clickable (if IDE integration possible)
  - [ ] Show file modification timestamps

### Phase 3: Enhanced Information (Medium Priority)
- [ ] **Template Hierarchy Visualization**:
  - [ ] Show template inheritance chain
  - [ ] Display which templates override others
  - [ ] Indicate custom vs default templates
  - [ ] Show template priority/loading order

- [ ] **Developer Hints**:
  - [ ] Add tooltips explaining what each file controls
  - [ ] Show common modification patterns
  - [ ] Link to relevant documentation sections
  - [ ] Display template hooks and filters available

### Phase 4: Advanced Features (Low Priority)
- [ ] **Template Performance Info**:
  - [ ] Show template loading times
  - [ ] Identify slow-loading templates
  - [ ] Display template file sizes
  - [ ] Cache status indicators

- [ ] **Quick Actions**:
  - [ ] "Copy file path" buttons
  - [ ] "Open in editor" links (if possible)
  - [ ] Template validation status
  - [ ] Quick template switching for testing

### Implementation Notes
- Only active when `WP_DEVELOPMENT_MODE` is set
- Should not impact production performance
- Panel should be unobtrusive but easily accessible
- Focus on TAB02 initially, expand to other areas later
- Consider using WordPress admin bar or footer hooks
- Template tracking should be lightweight and efficient

### Benefits
- Faster development workflow
- Easier onboarding for new developers
- Better understanding of template system
- Reduced time searching for relevant files
- Improved debugging capabilities
- AI-friendly documentation of file structure


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

## Additional Developer Workflow Enhancements (Backlog) 🧰

These items were identified while reviewing the docs and are intended to improve day-to-day developer productivity, team collaboration, and CI readiness.

### Hash-Aware Ignore / Verify Workflow (High Priority)
- [ ] **Mark as Verified (Function Scope)**:
  - [ ] Add “Mark as Verified” action for a specific issue/function
  - [ ] Require verification note (why acceptable / mitigation)
  - [ ] Store `verified_by`, `verified_at`, `verification_notes` in `.wpv-verification.json`
- [ ] **Ignore While Code Unchanged**:
  - [ ] Add ignore decision with scope (function vs file)
  - [ ] Auto-expire ignores when hash mismatches (stale ignores)
  - [ ] Show “stale” state prominently in UI and reporting

### Review Queue & Audit Trail (High Priority)
- [ ] **Review Queue**:
  - [ ] New issues since last scan
  - [ ] Stale verifications (hash mismatch)
  - [ ] Orphaned ignores/verifications (function/file no longer exists)
- [ ] **Approval Workflow (Optional)**:
  - [ ] Require second approver for file-level ignoring
  - [ ] Require approval for ignoring certain severities (e.g. security)
- [ ] **Audit Export**:
  - [ ] Export verification/ignore history to CSV/JSON for QA/security reviews

### Baselines & Regression Protection (Medium Priority)
- [ ] **Baseline Mode**:
  - [ ] Capture and store a baseline snapshot (per plugin + ruleset)
  - [ ] Compare new scan against baseline and highlight deltas
- [ ] **Regression-Only Mode**:
  - [ ] Fail/flag only new issues or severity regressions
  - [ ] Summary: “new errors”, “new warnings”, “resolved since baseline”

### Developer Integrations (Medium Priority)
- [ ] **WP-CLI Commands**:
  - [ ] `wp wpverifier scan --plugin=... --ruleset=... --format=json`
  - [ ] `wp wpverifier export --plugin=... --format=csv|json|html`
- [ ] **CI Templates**:
  - [ ] Provide a GitHub Actions workflow example (run scan, upload artefacts)
- [ ] **IDE Deep Links / Copy Helpers**:
  - [ ] “Copy file path / open in editor” links (VS Code / PhpStorm)
  - [ ] Copy-ready commands for running PHPCS on a single file/rule

### Reporting & Summaries (Medium Priority)
- [ ] **Human-Friendly Reports**:
  - [ ] HTML report (print-friendly)
  - [ ] CSV export for triage
  - [ ] JSON export for pipelines
- [ ] **Trends Over Time**:
  - [ ] Track errors/warnings per plugin over time
  - [ ] Display delta vs previous run (or baseline)
- [ ] **Release Readiness Thresholds**:
  - [ ] Configurable pass/fail thresholds per plugin/ruleset

### Ignored Paths UX (Low/Medium Priority)
- [ ] **Ignored Paths Suggestions**:
  - [ ] Suggest common vendor folders (composer `vendor/`, bundled libs)
  - [ ] Warn on overly broad ignores (e.g., `includes/` root)
- [ ] **Ignored Coverage Metrics**:
  - [ ] Show how many files/issues were excluded due to ignored paths
  - [ ] Surface “risky” ignored paths for review

### Safe Fix Recipes (Low Priority)
- [ ] **Fix Recipe Library**:
  - [ ] Provide vetted remediation snippets for common findings (nonces, escaping, sanitisation, capabilities)
  - [ ] “Copy patch” (diff text) option for developers (no auto-writing)
