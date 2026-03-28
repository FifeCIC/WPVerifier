=== WP Verifier ===
Contributors: Ryan Bayne
Donate link: https://ryanbayne.uk
Tested up to:      6.9
Stable tag:        1.9.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Tags:              plugin check, code quality, coding standards, audit, developer tools
Requires PHP: 7.4

WP Verifier is a verified code quality audit trail and standards enforcement tool for WordPress plugin development.

== Description ==

Most plugin checkers tell you what is wrong. WP Verifier tells you what was wrong, when it was fixed, and proves the file was in a known state at a known time.

WP Verifier produces a **verified code quality audit trail** — a structured, hash-backed record of every issue found, every fix applied, every decision made, and every file verified. That record travels with your plugin in portable JSON files and can be shared with clients, reviewers, or team members without giving anyone wp-admin access.

This makes WP Verifier useful not just for getting a plugin into the WordPress.org directory, but for the entire development lifecycle: initial audit, iterative fixing, client sign-off, and ongoing maintenance.

= The Audit Trail =

Every verification action is recorded:

* **Issue found** — file path, line, code, message, severity, timestamp
* **Issue fixed** — marked resolved with hash of the file at fix time
* **Issue ignored** — marked ignored with hash of the file at ignore time
* **File verified** — MD5 hash of file contents, timestamp, user ID
* **File ignored** — hash stored; file skipped on all future scans until the file actually changes

The hash-based approach means the audit trail survives deploys, staging copies, and save-without-edit operations. Only genuine content changes invalidate a verification. You can answer questions no other plugin checker can answer: _Was this file clean when we shipped version 2.0? Which issues were present when the client signed off? Did this file change after we marked it verified?_

= Core Features =

* **PHPCS-Powered Scanning** — Full WordPress Coding Standards enforcement via PHP_CodeSniffer
* **AI Guidance Per Issue** — Contextual fix guidance for every error code
* **Results Tab** — Unified accordion view of all issues grouped by file, with issue detail sidebar
* **Error Codes Tab** — Browse every error code encountered with AI guidance and per-code context
* **File-Level Ignore System** — Ignore entire files (hash-validated); ignored files are skipped on subsequent scans until the file changes
* **Issue-Level Fix and Ignore Tracking** — Mark individual issues as fixed or ignored; ignored issues are excluded from the active task list
* **Ignore Rules System** — Filter third-party code and false positives; supports directory, file, and error-code scopes with auto-detection of vendor directories
* **Readiness Score** — Live score reflecting how many issues remain unresolved
* **JSON-Based Storage** — Portable results that travel with the plugin being verified
* **WP-CLI Support** — Run checks from the command line

= Coming Soon =

* **Native Custom Check Engine** — First-party checks beyond PHPCS covering PHP quality, WordPress idiom compliance, and documentation completeness, each with its own error code, AI guidance, and code examples
* **Per-Code Global Overrides** — Enable, disable, or change the severity of any check code from the Error Codes tab
* **Shareable Results View** — Generate a temporary public URL to share verification results with a client or reviewer without granting wp-admin access
* **Export Reports** — Download results as PDF, CSV, or XML
* **Professional Services Quote** — Weighted effort estimate for remediation when a scan finds a significant number of issues
* **Function-Level Verification Tracking** — Hash scoped to individual function bodies for fine-grained ignore management

This plugin is based on the original Plugin Check tool developed by the WordPress Performance Team and Plugin Review Team.

= WP-CLI Usage =

To check a plugin using WP-CLI:

    wp plugin check plugin-slug

For runtime checks, load the CLI bootstrap manually:

    wp plugin check plugin-slug --require=./wp-content/plugins/WPVerifier/cli.php

**Ignore Rules System**

WP Verifier includes a powerful Ignore Rules system to filter out third-party code and false positives from verification results.

Key features:

* Ignore entire directories (e.g., vendor/, node_modules/)
* Ignore specific files
* Ignore specific error codes for files or directories
* Auto-detect common vendor directories
* Export and import ignore rules as JSON for team sharing

Access the Ignore Rules manager via the Ignore Rules tab in the main WP Verifier interface.

== Installation ==

= Installation from within WordPress =

1. Visit **Plugins > Add New**.
2. Search for **WP Verifier**.
3. Install and activate the WP Verifier plugin.

= Manual installation =

1. Upload the entire `WPVerifier` folder to the `/wp-content/plugins/` directory.
2. Visit **Plugins**.
3. Activate the WP Verifier plugin.

== Frequently Asked Questions ==

= Where can I contribute to the plugin? =

All development for this plugin is handled via [GitHub](https://github.com/WordPress/plugin-check/) any issues or pull requests should be posted there.

= What if the plugin reports something that's correct as an "error" or "warning"? =

We strive to write a plugin in a way that minimizes false positives but if you find one, please report it in the GitHub repo. For certain false positives, such as those detected by PHPCodeSniffer, you may be able to annotate the code to ignore the specific problem for a specific line.

= Why does it flag something as bad? =

It's not flagging "bad" things, as such. WP Verifier is designed to be a non-perfect way to test for compliance with the [Plugin Review guidelines](https://make.wordpress.org/plugins/handbook/performing-reviews/review-checklist/), as well as additional plugin development best practices in accessibility, performance, security and other areas. Not all plugins must adhere to these guidelines. The purpose of the checking tool is to ensure that plugins uploaded to the [central WordPress.org plugin repository](https://wordpress.org/plugins/) meet the latest standards of WordPress plugin and will work on a wide variety of sites.

Many sites use custom plugins, and that's perfectly okay. But plugins that are intended for use on many different kinds of sites by the public need to have a certain minimum level of capabilities, in order to ensure proper functioning in many different environments. The Plugin Review guidelines are created with that goal in mind.

This plugin checker is not perfect, and never will be. It is only a tool to help plugin authors, or anybody else who wants to make their plugin more capable. All plugins submitted to WordPress.org are hand-reviewed by a team of experts. The automated plugin checker is meant to be a useful tool only, not an absolute system of measurement.

= Does a plugin need to pass all checks to be approved in the WordPress.org plugin directory? =

To be approved in the WordPress.org plugin directory, a plugin must typically pass all checks in the "Plugin repo" category. Other checks are additional and may not be required to pass.

In any case, passing the checks in this tool likely helps to achieve a smooth plugin review process, but is no guarantee that a plugin will be approved in the WordPress.org plugin directory.

== Changelog ==

= 1.9.0 =

**CONSOLIDATION UPDATE:**
* Consolidated: Template files reduced through merging similar functionality
* Consolidated: JavaScript files reduced from 12 to 6 files (46% reduction)
* Consolidated: AJAX handlers unified with proper action hooks
* Removed: Basic Verification tab (duplicate of Advanced Verification)
* Removed: Plugin Namer feature and associated files
* Removed: Duplicate TAB03 (Hash Generation) functionality
* Enhanced: TAB01 auto-creates missing WPV files on plugin activation
* Enhanced: TAB02 configuration with vendor folder detection
* Enhanced: TAB03 verification with readiness checklist and progress tracking
* Fixed: Missing AJAX handlers for configuration and verification
* Fixed: Asset localization for current plugin access
* Improved: Centralized asset management through Asset_Manager class

**ORIGINAL FEATURES (Preserved):**
* Added: Ignore Rules system for filtering third-party code and false positives
* Added: Auto-detection of vendor directories (vendor/, node_modules/, libraries/, etc.)
* Added: Export/Import ignore rules as JSON for team collaboration
* Added: Support for directory, file, and error code-level ignore scopes
* Added: Files tab with File Details panel (PAN00) for progress tracking
* Added: Enhanced sidebar panels for Selected Issue Details (PAN01) and AI Prompt (PAN02)
* Added: Header code display system for easier navigation (toggle in settings)
* Improved: JSON-based storage architecture for portability
* Fixed: Removed duplicate menu entries in Tools menu

**In Development:**
* Function-level verification tracking with intelligent hash-based invalidation
* Enhanced Files tab with verification progress monitoring
* Issues tab upgrade to WP_List_Table with search, sort, and bulk actions

= 1.8.0 =

Initial release of WP Verifier, based on Plugin Check 1.8.0 by WordPress Performance Team and Plugin Review Team.

== Credits ==

WP Verifier is based on the Plugin Check tool originally developed by:
* WordPress Performance Team
* WordPress Plugin Review Team
* Contributors: wordpressdotorg

Original Plugin Check: https://github.com/WordPress/plugin-check/
