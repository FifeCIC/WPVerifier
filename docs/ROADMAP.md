# WP Verifier Development Roadmap
This roadmap consolidates all planned features and tracks implementation progress. Most features will be supported by existing folders, systems and standard approaches so check for existing implementation before creating new files, functions or classes.

## External Verification Tracking System 🚫

**Goal**: Implement function-level hash tracking system to keep code files clean while maintaining intelligent verification status that invalidates only when specific functions change.

**Storage**: JSON files stored within plugin directory for portability and version control.

### Phase 1: Core Tracking System (High Priority)
- [ ] **Verification File Structure**:
  - [x] Create `.wpv-verification.json` format specification
  - [ ] Schema structure:
    ```json
    {
      "file_level": {
        "includes/admin/admin.php": {
          "hash": "a1b2c3d4",
          "status": "verified",
          "verified_by": "user_id",
          "verified_at": "2024-01-15T10:30:00Z"
        }
      },
      "function_level": {
        "WPSeed_Admin::includes": {
          "file": "includes/admin/admin.php",
          "hash": "e5f6g7h8",
          "issues": [
            {
              "line": 64,
              "type": "WordPress.Security.NonceVerification.Recommended",
              "status": "verified",
              "note": "Page parameter used only for routing",
              "verified_by": "user_id",
              "verified_at": "2024-01-15T10:30:00Z"
            }
          ]
        }
      }
    }
    ```
  - [ ] Version field for schema evolution
  - [ ] Store in plugin root directory for portability

- [ ] **Hash Generation** (`includes/Verification/Hash_Generator.php`):
  - [ ] Parse PHP files using `token_get_all()`
  - [ ] Extract function/method/class boundaries (opening `{` to closing `}`)
  - [ ] Generate SHA256 hash of function body, use first 8 chars
  - [ ] Generate SHA256 hash of entire file for file-level verification
  - [ ] Normalize whitespace before hashing
  - [ ] Handle nested functions and closures

- [ ] **Verification Matcher** (`includes/Verification/Verification_Matcher.php`):
  - [ ] `is_verified()` - Check if issue is verified
  - [ ] Match by function name + hash
  - [ ] Match by file-level hash (entire file verified)
  - [ ] Return verification status and metadata
  - [ ] Log when hash doesn't match (code changed)

### Phase 2: JSON Storage (High Priority)
- [ ] **JSON Storage Handler** (`includes/Verification/JSON_Storage.php`):
  - [ ] Read/write `.wpv-verification.json` in plugin root
  - [ ] Atomic file writes (prevent corruption)
  - [ ] Backup before modifications
  - [ ] Merge verification data from multiple sources
  - [ ] Validate JSON structure on load

### Phase 3: Integration with Scanning (High Priority)
- [ ] **Modify `run_phpcs_on_file()`**:
  - [ ] Load verification data before processing results
  - [ ] Check each issue against verification status
  - [ ] Mark verified issues in results
  - [ ] Log when hash doesn't match (code changed, needs re-verification)
  - [ ] Track verification coverage (% of issues verified)

- [ ] **Modify `save_results()`**:
  - [ ] Include verification status in saved results
  - [ ] Store "verified_count" in scan metadata
  - [ ] Separate verified vs unverified issues in display
  - [ ] Show stale verifications (hash mismatch)

### Phase 4: UI Management (Medium Priority)
- [ ] **Verification Management Page** (new tab in WP Verifier):
  - [ ] List all verified functions/files
  - [ ] Filter by plugin, file, verification status
  - [ ] Show verification health (active/stale/invalid)
  - [ ] Bulk operations (re-verify, remove verification)
  - [ ] Search and sort functionality

- [ ] **Mark as Verified from Results**:
  - [ ] "Mark as Fixed" button in Selected Issue Details
  - [ ] Modal: Enter note, choose scope (this function/entire file)
  - [ ] Preview what will be marked verified
  - [ ] Generate hash and save to `.wpv-verification.json`
  - [ ] Track who verified and when

- [ ] **Verification Health Dashboard**:
  - [ ] Show stale verifications (hash no longer matches)
  - [ ] Show verification coverage per file
  - [ ] Show recently verified items
  - [ ] Suggest files ready for full verification

### Phase 5: Advanced Features (Medium Priority)
- [ ] **Batch Verification**:
  - [ ] Verify all functions in a file at once
  - [ ] Verify all files in a directory
  - [ ] Bulk verification with single note
  - [ ] Progress tracking for large batches

- [ ] **Verification History**:
  - [ ] Track verification changes over time
  - [ ] Show who verified what and when
  - [ ] Revert to previous verification state
  - [ ] Export verification history

- [ ] **Team Collaboration**:
  - [ ] Track who verified each function
  - [ ] Add comments/notes to verifications
  - [ ] Require approval for file-level verification
  - [ ] Audit log of verification changes

### Phase 6: Validation & Cleanup (Low Priority)
- [ ] **Verification Validation**:
  - [ ] Check all verifications against current codebase
  - [ ] Report stale verifications (hash mismatch)
  - [ ] Report orphaned verifications (function/file doesn't exist)
  - [ ] Suggest re-verification or removal
  - [ ] Auto-cleanup invalid verifications

- [ ] **Import/Export**:
  - [ ] Export verifications to shareable format
  - [ ] Import verifications from other projects
  - [ ] Merge verification files from multiple sources
  - [ ] Conflict resolution UI

### Phase 7: Analytics & Reporting (Future)
- [ ] **Verification Analytics**:
  - [ ] Verification coverage by plugin/file
  - [ ] Verification trends over time
  - [ ] Most verified issue types
  - [ ] Team verification activity

- [ ] **Smart Suggestions**:
  - [ ] Suggest functions ready for verification
  - [ ] Detect patterns in verified issues
  - [ ] Recommend file-level verification when appropriate
  - [ ] Learn from team's verification decisions

### Implementation Strategy

**Phase 1-2: Foundation (Week 1-2)**
- Build core verification tracking system
- JSON storage in plugin directory
- Function-level hash generation

**Phase 3: Integration (Week 3)**
- Integrate with scanning process
- Mark verified issues in results
- Track verification coverage

**Phase 4: UI (Week 4-5)**
- Add "Mark as Fixed" functionality
- Verification management interface
- Health dashboard for stale verifications

**Phase 5-7: Enhancement (Future)**
- Advanced features as needed
- Based on user feedback and usage patterns

### Benefits of Function-Level Verification Tracking

1. **Clean Code Files**: No inline comments cluttering code
2. **Intelligent Invalidation**: Function-level hashing survives unrelated changes
3. **Portable**: JSON files travel with plugin, no database dependency
4. **Version Control Friendly**: Track verification decisions in git
5. **Line-Number Independent**: Adding lines above doesn't invalidate verification
6. **Audit Trail**: Complete history of who verified what and when
7. **Dual-Level Tracking**: Both file-level and function-level verification
8. **Progress Tracking**: See verification coverage per file/plugin

### Technical Considerations

- **Hash Algorithm**: SHA256 (cryptographically secure)
- **Hash Length**: 8 characters (sufficient for collision avoidance)
- **Scope**: Function body from opening `{` to closing `}`
- **Normalization**: Trim whitespace, normalize line endings
- **Performance**: Cache parsed functions per request
- **Storage**: JSON files in plugin root directory
- **Portability**: Verification data travels with plugin package
- **Validation**: Regular checks for stale/orphaned verifications


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
