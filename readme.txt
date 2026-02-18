=== WP Verifier ===

Contributors:      wordpressdotorg
Tested up to:      6.9
Stable tag:        1.9.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Tags:              plugin best practices, testing, accessibility, performance, security

WP Verifier is a tool which provides checks to help plugins meet the directory requirements and follow various best practices. Based on WordPress.org Plugin Check.

== Description ==

WP Verifier is a tool for testing whether your plugin meets the required standards for the WordPress.org plugin directory. With this plugin you will be able to run most of the checks used for new submissions, and check if your plugin meets the requirements.

This plugin is based on the original Plugin Check tool developed by the WordPress Performance Team and Plugin Review Team.

Additionally, the tool flags violations or concerns around plugin development best practices, from basic requirements like correct usage of internationalization functions to accessibility, performance, and security best practices.

The checks can be run either using the WP Admin user interface or WP-CLI:

* To check a plugin using WP Admin, please navigate to the _Tools > WP Verifier_ menu. You need to be able to manage plugins on your site in order to access that screen.
* To check a plugin using WP-CLI, please use the `wp plugin check` command. For example, to check the "Hello Dolly" plugin: `wp plugin check hello.php`
    * Note that by default when using WP-CLI, only static checks can be executed. In order to also include runtime checks, a workaround is currently necessary using the `--require` argument of WP-CLI, to manually load the `cli.php` file within the plugin checker directory before WordPress is loaded. For example: `wp plugin check hello.php --require=./wp-content/plugins/WPVerifier/cli.php`
    * You could use arbitrary path or URL to check a plugin. For example, to check a plugin from a URL: `wp plugin check https://example.com/plugin.zip` or to check a plugin from a path: `wp plugin check /path/to/plugin`

The checks are grouped into several categories, so that you can customize which kinds of checks you would like to run on a plugin.

Keep in mind that this plugin is not a replacement for the manual review process, but it will help you speed up the process of getting your plugin approved for the WordPress.org plugin repository, and it will also help you avoid some common mistakes.

Even if you do not intend to host your plugin in the WordPress.org directory, you are encouraged to use WP Verifier so that your plugin follows the base requirements and best practices for WordPress plugins.

**Ignore Rules System**

WP Verifier includes a powerful Ignore Rules system to filter out third-party code and false positives from verification results. This feature is essential for working with plugins that include vendor libraries or third-party dependencies.

Key features:

* Ignore entire directories (e.g., vendor/, node_modules/)
* Ignore specific files
* Ignore specific error codes for files or directories
* Auto-detect common vendor directories
* Export and import ignore rules as JSON for team sharing
* Categorize rules by reason (vendor/library or custom)

Access the Ignore Rules manager via the "Ignore Rules" tab in the main WP Verifier interface.

**Plugin Namer Tool**

WP Verifier now includes an AI-powered Plugin Namer tool (accessible via _Tools > WP Verifier Namer_) that helps plugin authors evaluate plugin names before submission. This tool checks for:

* Similarity to existing plugins in the WordPress.org directory
* Potential trademark conflicts with well-known brands
* Compliance with WordPress plugin naming guidelines
* Generic or overly broad naming issues

The Plugin Namer provides instant feedback with actionable suggestions, helping you choose a clear, unique, and policy-compliant name that stands out in the plugin directory. This feature requires AI provider configuration in the settings.

**Important:** The Plugin Namer tool provides guidance only and is not definitive. All plugin name decisions are subject to final review and approval by the WordPress.org Plugins team reviewers.

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

* Added: Ignore Rules system for filtering third-party code and false positives
* Added: Auto-detection of vendor directories (vendor/, node_modules/, libraries/, etc.)
* Added: Export/Import ignore rules as JSON for team collaboration
* Added: Support for directory, file, and error code-level ignore scopes
* Improved: Consolidated all features into main plugin tabs interface
* Fixed: Removed duplicate menu entries in Tools menu

= 1.8.0 =

Initial release of WP Verifier, based on Plugin Check 1.8.0 by WordPress Performance Team and Plugin Review Team.

== Credits ==

WP Verifier is based on the Plugin Check tool originally developed by:
* WordPress Performance Team
* WordPress Plugin Review Team
* Contributors: wordpressdotorg

Original Plugin Check: https://github.com/WordPress/plugin-check/
