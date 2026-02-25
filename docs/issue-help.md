# WP Verifier - Issue Help Documentation

This file contains help content for verification issues that don't have official WordPress.org documentation.

---

## PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent

**Code:** `PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent`

**Message:** Found call to wp_enqueue_script() with external resource. Offloading scripts to your servers or any remote service is disallowed.

**Severity:** ERROR

**Description:**
WordPress.org plugin directory guidelines prohibit loading JavaScript files from external CDNs or remote servers. All scripts must be included within your plugin package.

**Why This Matters:**
- Security: External resources can be modified without your control
- Reliability: External services can go down, breaking your plugin
- Privacy: External requests can track users
- Performance: No guarantee of availability or speed

**How to Fix:**
1. Download the external script file
2. Place it in your plugin's `assets/js/` directory
3. Update `wp_enqueue_script()` to use the local file:

```php
// ❌ Wrong - External URL
wp_enqueue_script('mermaid', 'https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js');

// ✅ Correct - Local file
wp_enqueue_script(
    'mermaid',
    plugin_dir_url(__FILE__) . 'assets/js/mermaid.min.js',
    array(),
    '10.6.1',
    true
);
```

**Exceptions:**
- Google Fonts API (allowed)
- Payment gateway APIs (Stripe, PayPal, etc.)
- Video embeds (YouTube, Vimeo)
- Social media widgets (when necessary)

**Related Guidelines:**
- [WordPress Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

