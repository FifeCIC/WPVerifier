# Quick Test Checklist - Ignore Code Feature

## Pre-Test Setup
- [ ] WordPress site is running
- [ ] WPVerifier plugin is active
- [ ] At least one plugin available to test

## Test Steps

### Step 1: Navigate to Plugin
- [ ] Go to: `/wp-admin/plugins.php?page=wp-verifier`
- [ ] Page loads successfully
- [ ] "Verify Plugins" heading visible

### Step 2: Run Verification
- [ ] Select a plugin from dropdown
- [ ] Click "Check it!" button
- [ ] Wait for scan to complete
- [ ] Results appear in accordion view

### Step 3: View Issue Details
- [ ] Click on any file accordion to expand
- [ ] Click on an issue in the list
- [ ] Sidebar shows issue details
- [ ] "Ignore Code" button/link is visible

### Step 4: Click Ignore Code
- [ ] Click "Ignore Code" link
- [ ] Browser URL changes (watch address bar)
- [ ] Page redirects back to verify page
- [ ] Green success notice appears at top

### Step 5: Verify Success Notice
Expected text: "Issue ignored successfully. Run a new scan to see updated results."
- [ ] Notice is visible
- [ ] Notice is green (success style)
- [ ] Notice has dismiss button

### Step 6: Verify Database
- [ ] Open phpMyAdmin or database tool
- [ ] Find `wp_options` table
- [ ] Search for option_name = `wpv_ignore_rules`
- [ ] Verify option_value contains your ignored rule

### Step 7: Run New Scan
- [ ] Select same plugin
- [ ] Click "Check it!" button
- [ ] Wait for scan to complete
- [ ] Verify ignored issue is NOT in results

## Expected URL Pattern

When clicking "Ignore Code", URL should look like:
```
/wp-admin/plugins.php?page=wp-verifier&tab=verify&action=ignore_code&plugin=PLUGIN_NAME&file=FILE_PATH&code=ERROR_CODE&_wpnonce=NONCE_VALUE
```

## Troubleshooting

### Issue: Nothing happens when clicking link
**Check:**
- Is it actually a link (not a button)?
- Does URL change in address bar?
- Check browser console for errors

### Issue: "Invalid nonce" error
**Check:**
- Is nonce being added to URL?
- Is user logged in?
- Try refreshing page and clicking again

### Issue: "Insufficient permissions" error
**Check:**
- User has admin/activate_plugins capability
- Try with administrator account

### Issue: No success notice appears
**Check:**
- Did page redirect?
- Is `&ignored=1` in URL?
- Check page source for notice HTML

### Issue: Issue still appears after new scan
**Check:**
- Was ignore rule actually saved? (check database)
- Is filtering logic working? (check Ignore_Rules class)
- Try clearing WordPress cache

## Debug Mode

Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then check: `wp-content/debug.log`

## Success Criteria

✅ Link is clickable
✅ URL changes when clicked
✅ Page redirects back
✅ Success notice displays
✅ Database contains ignore rule
✅ New scan filters out ignored issue

## Test Data Example

```
Plugin: my-plugin/my-plugin.php
File: includes/class-main.php
Code: WordPress.Security.EscapeOutput.OutputNotEscaped
```

Expected database entry:
```json
{
  "my-plugin/my-plugin.php": [
    {
      "file": "includes/class-main.php",
      "code": "WordPress.Security.EscapeOutput.OutputNotEscaped",
      "added": "2024-01-15 10:30:00"
    }
  ]
}
```
