# Implementation Roadmap

## Phase 0: File Monitoring & Integrity (Pre-MVP)
**Goal**: Detect file changes in real-time and validate plugin structure during development.

- [ ] **File Watcher**: Monitor selected plugin directory for file timestamp changes.
- [ ] **Delayed Validation**: Wait 2-5 seconds after file save before running checks (assume developer is still working).
- [ ] **Change Detection**: Alert developer when file modifications are detected with validation results.
- [ ] **Structure Validation**: Check for required files and folders:
  - [ ] Language folder (`/languages` or `/lang`)
  - [ ] Language files (`.pot`, `.po`, `.mo`)
  - [ ] License file (`LICENSE`, `LICENSE.txt`, `LICENSE.md`)
  - [ ] README file (`README.md`, `readme.txt`)
  - [ ] Plugin header file (main plugin file with proper headers)
- [ ] **File Creation Wizard**: Offer to auto-generate missing files with templates.
- [ ] **Real-time Dashboard**: Show file status, last modified times, and validation results.

## Phase 1: The "Sanity" Layer (MVP)
**Goal**: Get the errors into a WPSeed Table View and allow basic filtering.

- [ ] **Scanner Integration**: Run Plugin Check programmatically and capture JSON output.
- [ ] **WPSeed Table Integration**: Render errors in a sortable, filterable table.
- [ ] **Basic Grouping**: Group errors by File, Type (Security vs. Style), and Severity.
- [ ] **"Library" Definition**: Simple settings page to define paths (e.g., `includes/libraries/*`) as "Third Party".
- [ ] **Basic Ignore**: "Ignore this file" and "Ignore this error code" actions.

## Phase 2: The "Smart" Layer
**Goal**: Automate the cleanup of third-party noise.

- [ ] **Ignore Rules System**: Implement flexible ignore rules with WordPress Options storage.
  - [ ] **"Ignore Code" Button**: Add button next to "Learn More" in issue details to ignore specific error codes for a file.
  - [ ] **Options Storage**: Store ignore rules in `wpv_ignore_rules` option as serialized array (efficient single-query retrieval).
  - [ ] **Ignore Scopes**: Support three ignore levels:
    - **Directory**: Ignore all issues in a folder (e.g., `vendor/`, `includes/libraries/`)
    - **File**: Ignore all issues in a specific file
    - **Code**: Ignore specific error code(s) for a file or directory
  - [ ] **Ignore Reasons**: Categorize ignore rules by reason:
    - **Vendor/Library**: Third-party code (use "Vendor" in UI, "Library" in code)
    - **Other**: Custom reason with optional note
  - [ ] **Vendor Directory Manager**: UI tool to select plugin folders for ignoring with reason selection.
  - [ ] **Rule Management Screen**: Admin interface to view, edit, and delete ignore rules.
  - [ ] **Apply Rules on Scan**: Filter scan results based on active ignore rules before display.
  - [ ] **Export/Import Rules**: Allow sharing ignore rule sets between installations as JSON.
- [ ] **Smart Library Detection**: Auto-detect `vendor`, `node_modules`, or common library structures.
- [ ] **Wrapper Verification Logic**: 
    - *Concept*: If a file is marked as a "Library", suppress internal errors.
    - *Enforcement*: Flag anywhere the *Main Plugin* calls a method from that Library without escaping the output.
- [ ] **Bulk Actions**: Select 50 errors -> "Add to Ignore List".
- [ ] **Ignore Templates**: Pre-sets for Action Scheduler, Freemius, Carbon Fields, etc.
- [ ] **Vendor Library Sync**: Crowdsourced vendor/library database stored in options.
  - [ ] **Community Library Option**: Store shared vendor library data in `wpv_vendor_libraries` option.
  - [ ] **Sync Feature**: Allow users to sync their vendor selections to contribute to community database via API.
  - [ ] **Auto-Suggest**: Suggest ignore rules based on detected vendor libraries from community database.
  - [ ] **Library Metadata**: Store library name, typical paths, common error codes, and usage statistics.
  - [ ] **Periodic Updates**: Fetch updated vendor library list from remote API on schedule.

## Phase 3: The "Workflow" Layer
**Goal**: Make it a team tool.

- [ ] **Diff Mode**: Compare current scan vs. last scan (Show only NEW errors).
- [ ] **Export/Import**: Generate a standard `.plugincheckignore` file for CI/CD usage.
- [ ] **Context-Aware Ignore**: Right-click error -> "Ignore this error in this function only".

## Phase 4: Advanced & AI
- [ ] **Auto-Fixer**: Simple regex-based fixes for whitespace or missing docblocks.
- [ ] **AI Analysis**: "Ask AI" button next to a security warning to explain *why* it's flagged and suggest a fix.

## Phase 5: Transparency & User Visibility
**Goal**: Make verification processes visible and understandable to users.

### Quick Wins
- [ ] **Settings Connection Test**: Add "Test Connection" buttons for AI/API settings with real-time feedback.
- [ ] **Check Progress Indicator**: Show which checks are running during verification with progress bar.
- [ ] **Readiness Score**: Display overall plugin readiness percentage with breakdown by category.
- [ ] **Check Details Panel**: Expandable panel showing what each check category validates.

### Plugin Verification Process
- [ ] **Pre-Check Summary**: Show which checks will run before starting verification.
- [ ] **Real-time Check Status**: Display current check being executed with estimated time.
- [ ] **Check Results Breakdown**: Separate results by category (Security, Performance, Accessibility, etc.).
- [ ] **Skipped Checks Indicator**: Show which checks were skipped and why.

### Asset Loading Transparency
- [ ] **Asset Load Log**: Show which CSS/JS files loaded on current page.
- [ ] **Missing Asset Alerts**: Notify when expected assets fail to load.
- [ ] **Asset Dependencies**: Display dependency tree for loaded assets.

### Settings Validation
- [ ] **Field Validation Feedback**: Real-time validation messages for settings fields.
- [ ] **Configuration Impact**: Show how settings changes affect verification behavior.
- [ ] **Default vs Custom Indicator**: Highlight which settings differ from defaults.

### Historical Results
- [ ] **Scan Comparison**: Compare current scan with previous scans.
- [ ] **Trend Visualization**: Show error count trends over time.
- [ ] **Change Log**: Track what changed between scans.

### Submission Readiness
- [ ] **Checklist View**: Show all WordPress.org submission requirements.
- [ ] **Blocking Issues**: Highlight issues that prevent submission.
- [ ] **Recommendation Priority**: Sort recommendations by impact on approval.

### Custom Ruleset Impact
- [ ] **Ruleset Preview**: Show which rules are active in current configuration.
- [ ] **Rule Effect**: Display how many issues each rule detects.
- [ ] **Ruleset Comparison**: Compare results with different rulesets.

### Compatibility Checks
- [ ] **PHP Version Testing**: Show compatibility across PHP versions.
- [ ] **WordPress Version Testing**: Test against multiple WP versions.
- [ ] **Conflict Detection**: Identify potential conflicts with popular plugins.

## Phase 6: Plugin Namer Enhancements
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