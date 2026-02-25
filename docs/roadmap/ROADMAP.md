# WP Verifier Development Roadmap
This roadmap consolidates all planned features and tracks implementation progress. Most features will be supported by existing folders, systems and standard approaches so check for existing implementation before creating new files, functions or classes.


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
