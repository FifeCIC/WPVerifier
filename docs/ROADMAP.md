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

What approach should we take to the following feature?>
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

## PHASE 4: Plugin Verification Progress Bar Gets Real-Time Progress Tracking

**Objective**: Replace simulated progress with actual file-based progress tracking for accurate verification progress indication.

### Problem Statement
Current progress simulation reaches 95% quickly and stays there for most of the verification time on large plugins, creating poor user experience.

### Core Concept
- **File Count-Based Progress**: Track actual files being processed vs total files to scan
- **Real-Time Updates**: Use AJAX polling or WebSocket to get actual progress from server
- **Phase-Aware Progress**: Different progress calculations for different verification phases
- **Accurate Time Estimates**: Show estimated time remaining based on actual processing speed

### Phase 4.1: Server-Side Progress Tracking (HIGH PRIORITY)
- [ ] **Progress Storage System**
	- Create temporary `.wpv-progress.json` file during verification
	- Store current phase, files processed, total files, start time
	- Update progress file as each file is processed
	- Clean up progress file on completion/failure
- [ ] **File Counting Integration**
	- Count total files to be scanned before starting verification
	- Update progress after each file is processed in verification loops
	- Track progress per verification phase (security, performance, etc.)
	- Handle ignored files in progress calculations
- [ ] **Progress API Endpoint**
	- Create AJAX endpoint to read current progress from `.wpv-progress.json`
	- Return structured progress data (phase, percentage, files processed, ETA)
	- Handle cases where progress file doesn't exist or is corrupted

### Phase 4.2: Client-Side Real-Time Updates (HIGH PRIORITY)
- [ ] **Replace Simulation with Polling**
	- Remove current `startProgressSimulation()` method
	- Implement `pollProgressStatus()` with configurable intervals
	- Update progress bar based on actual server progress
	- Show current file being processed and phase information
- [ ] **Enhanced Progress Display**
	- Show "Processing file X of Y" with current filename
	- Display current verification phase (Security, Performance, etc.)
	- Show estimated time remaining based on processing speed
	- Add cancel verification option during long-running scans
- [ ] **Error Handling & Fallback**
	- Fallback to time-based estimation if progress polling fails
	- Handle server timeouts and connection issues gracefully
	- Show appropriate error messages if verification stalls

### Phase 4.3: Advanced Progress Features (MEDIUM PRIORITY)
- [ ] **Phase-Specific Progress Weighting**
	- Assign different weights to verification phases based on complexity
	- Security checks might be 40%, Performance 30%, Accessibility 20%, etc.
	- Adjust overall progress based on phase completion and weighting
- [ ] **Processing Speed Analytics**
	- Track files per second processing rate
	- Show processing speed in UI ("Processing 2.3 files/sec")
	- Use historical data to improve time estimates
	- Detect and warn about unusually slow processing
- [ ] **Detailed Progress Breakdown**
	- Show progress per category (Security: 45%, Performance: 23%, etc.)
	- Display file type breakdown (PHP: 80%, JS: 60%, CSS: 90%)
	- Show ignored files count and percentage

### Phase 4.4: Performance & Optimization (LOW PRIORITY)
- [ ] **Progress Update Optimization**
	- Batch progress updates to avoid excessive file I/O
	- Update progress every N files instead of every file
	- Use memory-based progress tracking with periodic file writes
- [ ] **Client-Side Optimization**
	- Implement exponential backoff for polling intervals
	- Reduce polling frequency as verification progresses
	- Cache progress data to reduce server requests
- [ ] **Large Plugin Handling**
	- Special handling for plugins with 1000+ files
	- Show sub-progress for large directories
	- Implement progress chunking for very large scans

### Technical Implementation Notes
- **Backward Compatibility**: Maintain fallback to simulation if real-time tracking fails
- **Performance Impact**: Minimize overhead of progress tracking on verification speed
- **Concurrency**: Handle multiple simultaneous verifications safely
- **Cleanup**: Ensure progress files are cleaned up even if verification is interrupted

### Success Metrics
- Progress bar accurately reflects actual verification progress
- Users can see meaningful progress updates throughout entire verification
- Time estimates are within 20% accuracy for most plugins
- No significant performance impact on verification speed
- Graceful handling of edge cases and errors
