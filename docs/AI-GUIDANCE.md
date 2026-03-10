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
