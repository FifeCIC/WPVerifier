# WP Verifier

![Version](https://img.shields.io/badge/version-1.9.0-green)
![License](https://img.shields.io/badge/license-GPLv2+-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.8%20tested-blue)

Verified code quality audit trail and standards enforcement for WordPress plugin development.

---

## What WP Verifier Actually Is

Most plugin checkers tell you what is wrong. WP Verifier tells you what was wrong, when it was fixed, who fixed it, and proves the file was in a known state at a known time.

The distinction matters. WP Verifier produces a **verified code quality audit trail** — a structured, hash-backed record of every issue found, every fix applied, every decision made, and every file verified. That record travels with your plugin in portable JSON files and can be shared with clients, reviewers, or team members without giving anyone wp-admin access.

This makes WP Verifier useful not just for getting a plugin into the WordPress.org directory, but for the entire development lifecycle: initial audit, iterative fixing, client sign-off, and ongoing maintenance.

---

## Features

### ✅ Available Now

- **PHPCS-Powered Scanning** — Full WordPress Coding Standards enforcement via PHP_CodeSniffer
- **Verified Audit Trail** — Hash-backed record of every issue, fix, and verification decision stored in portable JSON
- **AI Guidance Per Issue** — Contextual fix guidance for every error code, configurable via `ai-guidance-config.json`
- **Results Tab** — Unified accordion view of all issues grouped by file, with issue detail sidebar and AI prompt panel
- **Error Codes Tab (TAB08)** — Browse every error code encountered, with AI guidance and per-code context
- **File-Level Ignore System** — Ignore entire files (hash-validated); ignored files are skipped on subsequent scans until the file changes
- **Issue-Level Ignore / Fix Tracking** — Mark individual issues as fixed or ignored; ignored issues are excluded from the active task list
- **Ignore Rules System** — Filter third-party code and false positives; supports directory, file, and error-code scopes
- **Auto-Detection of Vendor Directories** — `vendor/`, `node_modules/`, `libraries/` and others excluded automatically
- **Export / Import Ignore Rules** — Share ignore rules as JSON across a team
- **Readiness Score** — Live score reflecting how many issues remain unresolved
- **JSON-Based Storage** — `.wpv-results.json`, `.wpv-config.json`, `.wpv-verification.json` travel with the plugin being verified
- **Setup Wizard** — Guided first-use configuration
- **WP-CLI Support** — Run checks from the command line

### 🔄 In Development

- **Function-Level Verification Tracking** — Hash scoped to individual function bodies; changes to one function don't invalidate ignores on others
- **Overwatch System** — Active file monitoring during development; re-scans only changed functions on save
- **Single File Re-Scan** — Re-scan one file from the Results tab without running a full plugin check
- **JSON Storage Directory Migration** — Move all `.wpv-*.json` files into a dedicated `wpevolveverifier/` subfolder to keep plugin roots clean

### 🔮 Planned

- **Native Custom Check Engine** — First-party checks beyond PHPCS: PHP quality, WordPress idiom compliance, documentation completeness, correctness patterns. Each check carries its own `WPV-{CATEGORY}-{NNN}` code, AI guidance, and bad/good code examples
- **Per-Code Global Overrides** — Enable/disable or change severity of any check code (PHPCS or WPV native) from the Error Codes tab, stored in `.wpv-config.json`
- **Shareable Results View** — Generate a temporary public URL to share verification results with a client or reviewer without granting wp-admin access
- **Export Reports** — Download results as PDF, CSV, or XML for client delivery, QA records, or import into other tools
- **Professional Services Quote** — When a scan finds a significant number of issues, WP Verifier can generate a weighted effort estimate and link to EvolveWP professional remediation services
- **Real-Time Verification Progress** — Live file-by-file progress during scanning with estimated time remaining
- **Plugin Selection Duplicate File Warning** — Detect pre-existing WP Verifier data files when switching to a new plugin

---

## The Audit Trail Concept

Every verification action WP Verifier takes is recorded:

| Action | What is stored |
|---|---|
| Issue found | File path, line, code, message, severity, timestamp |
| Issue fixed | Marked resolved, hash of file at fix time |
| Issue ignored | Marked ignored with reason, hash of file at ignore time |
| File verified | MD5 hash of file contents, timestamp, user ID |
| File ignored | Hash stored; file skipped on all future scans until hash changes |

This means you can answer questions that no other plugin checker can answer:
- *Was this file clean when we shipped version 2.0?*
- *Which issues were present when the client signed off?*
- *Did this file change after we marked it verified?*

The hash-based approach means the audit trail survives deploys, staging copies, and save-without-edit operations — only genuine content changes invalidate a verification.

---

## Installation

1. Upload the `WPVerifier` folder to `/wp-content/plugins/`
2. Run `composer install` in the plugin directory
3. Activate via **Plugins** in WordPress admin
4. Navigate to **Tools > WP Verifier**
5. Select the plugin you want to verify and run a check

**Requirements:** WordPress 6.3+, PHP 7.4+, Composer

---

## WP-CLI

```bash
wp plugin check plugin-slug
```

For runtime checks via CLI, load the CLI bootstrap manually:

```bash
wp plugin check plugin-slug --require=./wp-content/plugins/WPVerifier/cli.php
```

---

## Credits

WP Verifier is built on [Plugin Check](https://github.com/WordPress/plugin-check/) by the WordPress Performance Team and Plugin Review Team. The audit trail architecture, custom check engine, AI guidance system, and results UI are original additions.

---

## License

GPLv2 or later — same as the original Plugin Check project.
