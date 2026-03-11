# WordPress Development Standards
✅ Core Standards: Always adhere to official WordPress PHP Coding Standards.
❌ No Inline Assets: No <style> or <script> tags inside .php files. Use enqueuing.
✅ Sanitization: Always sanitize inputs, escape outputs, and use nonces for all actions.

Project Specific Rules: [PLUGIN-NAME]
Procedures: Check [PATH/TO/PROCEDURES] before starting. Create one if a new workflow is established.

# Asset Management
Do not create new CSS/JS files without checking existing libraries.
Storage: Store new .css in [CSS-DIR] and .js in [JS-DIR].
Enqueuing: Register all new assets in [ASSET-LOADER-FILE].

Version Control & Documentation
✅ Commits: Use prefixes: [Feature], [Fix], [Update], [Docs].
✅ Atomic Changes: Each implementation phase must be its own commit.
✅ Language: All documentation and UI text must be in British English.
✅ PHPDoc: Mandatory for all new functions, classes, and hooks (including examples).
❌ No TODOs: Do not leave // TODO comments; create an issue in [TASK-FILE] instead.

Integrated Debugging System (The "BugNet" Protocol)
MANDATORY: Systematically improve the debugging layer during all tasks.

Logging: Add [CUSTOM-LOG-FUNCTION] to all new functions and form handlers.

Helpers: Use [DEBUG-HELPER], [USER-ACTION-LOG], and [ERROR-LOG] functions.

Metrics: Add execution timers using [TIMER-START-FUNCTION] and [TIMER-END-FUNCTION].

Context: Include User ID, Action Type, and Stack Traces in every log entry.

## Enhanced Debugging Protocol (WPSeed Unified Logging)

### Core Logging Functions
- `wpseed_log()->start_context('operation_name')` - Begin logging context
- `wpseed_trace('TYPE', 'message', $data)` - Log individual operations
- `wpseed_log()->end_context($summary)` - End context with summary

### Verification Process Debugging
- `wpseed_verification_log()->log_step($step, $input, $output, $details)` - Track data flow
- `wpseed_log_js_data($operation, $count, $size)` - Log JavaScript transmissions
- `wpseed_verification_log()->log_filter($name, $input, $output)` - Track filtering

### Loop Debugging (Smart Counting)
- `wpseed_log()->loop_trace($loop_id, $data)` - Log loop iterations (every 10th)
- `wpseed_log()->loop_end($loop_id, $summary)` - End loop with total count

### JavaScript Debugging
```javascript
WPSeedLogger.startContext('verification');
WPSeedLogger.trace('OPERATION', 'message', data);
WPSeedVerificationLogger.logStep('step_name', inputCount, outputCount);
WPSeedLogger.endContext();
```

### Data Loss Detection
- Automatic flagging when input_count > output_count
- Immediate error logging for data loss events
- Summary reports showing where data is lost

### Debug Log Analysis
1. Check `debug.log` for "WPSeed_Trace" entries
2. Look for "DATA_LOSS" flags
3. Review "SUMMARY" entries for step-by-step counts
4. Use "LOOP_END" entries to identify excessive iterations

Logic & Planning Rules
AI often duplicates code. To prevent bloat:

Search First: Find similar systems in the codebase and extend existing classes rather than recreating them.

Justify: New classes must be unique in functionality. Explain why a new file is necessary.

Deprecate: Explicitly mark old files/functions as @deprecated when replacing them.

API-First: Design functions to be accessible via hooks/API so they are usable by other parts of WordPress.

Confirm: AI must log its logic to allow the developer to verify the "thought process."

🚨 MANDATORY TESTING PROTOCOL
AI must never assume success. Every task must conclude with this protocol.

1. Pre-Implementation Validation
Identify exactly which files will be modified.

List the expected outcomes and affected UI elements.

2. Post-Implementation Instructions
Navigation: Provide the exact path: [Admin Page] -> [Tab] -> [Section].

Interaction: List specific buttons to click or forms to fill.

Indicators: Describe what a "Success" state looks like visually.

3. Validation Checkpoints (User Interaction Required)
Hold: AI must stop and wait for user confirmation after implementation.

Troubleshoot: Provide a "If this failed, check X" guide for the developer.