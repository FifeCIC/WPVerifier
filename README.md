# WP Verifier
Systematic code monitoring and standards enforcement for WordPress plugin development.

## 🚧 Work In Progress

WP Verifier is currently under active development. This project is being adapted to work within a bespoke plugin ecosystem where automated verification and standards enforcement will streamline development workflows.

**Status:** Coming Soon  
**Current Version:** 0.0.1 (Development)

---

## About This Project

WP Verifier is based on the excellent [Plugin Check (PCP)](https://github.com/WordPress/plugin-check/) tool developed by the WordPress Performance Team and WordPress Plugin Review Team. We are adapting this proven codebase to serve a specialized plugin ecosystem with enhanced automation and custom verification workflows.

### Why Fork Plugin Check?

While Plugin Check is designed for WordPress.org plugin directory submissions, WP Verifier extends this foundation to:

- **Integrate with custom development workflows** - Automated checks tailored to our specific ecosystem requirements
- **Enforce bespoke coding standards** - Beyond WordPress.org requirements to match internal best practices
- **Enable continuous verification** - Automated monitoring throughout the development lifecycle
- **Support ecosystem-specific features** - Custom checks for proprietary frameworks and patterns

### Credits

This project builds upon Plugin Check, originally created by:
- WordPress Performance Team
- WordPress Plugin Review Team
- Contributors: wordpressdotorg

Original project: https://github.com/WordPress/plugin-check/

---

## Features

### ✅ Available Now
- **Plugin Structure Validation** - Comprehensive checks for WordPress plugin requirements
- **Coding Standards Compliance** - WordPress, security, performance, and accessibility standards
- **Custom Ruleset Management** - Create and enforce ecosystem-specific standards
- **Ignore Rules System** - Filter third-party code and false positives from results
- **AI-Powered Plugin Namer** - Evaluate plugin names for availability and compliance
- **Files Tab Progress Tracker** - Monitor verification progress per file with detailed panels
- **Setup Wizard** - Guided configuration on first use
- **JSON-Based Storage** - Portable results that travel with your plugin

### 🔄 In Development
- **Function-Level Verification Tracking** - Smart hash-based system to track verified issues
  - Intelligent invalidation (only affected functions need re-review)
  - Clean code (no inline ignore comments)
  - Portable JSON storage in plugin directory
  - Progress tracking per file and function
- **Enhanced Files Tab** - File details panel, issue tracking, verification status
- **Issues Tab Enhancement** - WP_List_Table implementation with search, sort, and bulk actions

### 🔮 Planned Features
- **Verification Analytics** - Coverage metrics, trends, and team activity
- **Batch Verification** - Verify entire files or directories at once
- **Team Collaboration** - Shared verification notes and approval workflows
- **Advanced Plugin Namer** - Multi-TLD domain checking, trademark search, name alternatives

*Additional features will be announced as development progresses.*

---

## Installation

**Note:** WP Verifier is not yet ready for production use.

For development/testing:

1. Clone or download this repository to your WordPress plugins directory
2. Run `composer install` in the plugin directory
3. Activate the plugin through WordPress admin
4. Access via **Plugins > Verify Plugins** in the admin menu

---

## Requirements

- WordPress 6.3 or higher
- PHP 7.4 or higher
- Composer (for dependency management)

---

## Development Status

We're actively working on adapting Plugin Check's robust foundation to serve our ecosystem's unique needs. Stay tuned for updates as we roll out new features and capabilities.

### Current Focus
- Function-level verification tracking system
- Enhanced Files tab with progress monitoring
- JSON-based storage architecture
- Team collaboration features

---

## License

GPLv2 or later - Same as the original Plugin Check project

---

## Support

As this is a work-in-progress project, support is limited. For issues related to the core Plugin Check functionality, please refer to the [original project](https://github.com/WordPress/plugin-check/).
