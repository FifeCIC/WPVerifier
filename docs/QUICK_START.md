# Quick Start Guide - Test in 5 Minutes

## ⚡ Fastest Way to Test

### Step 1: Open Plugin (30 seconds)
```
Navigate to: http://localhost/EcoSystem/wp-admin/plugins.php?page=wp-verifier
```

### Step 2: Run Scan (2 minutes)
1. Select any plugin from dropdown
2. Click "Check it!" button
3. Wait for scan to complete

### Step 3: Test Ignore (1 minute)
1. Click any file accordion to expand
2. Click any issue in the list
3. Look for "Ignore Code" button in sidebar
4. Click it
5. Watch URL change
6. See success notice

### Step 4: Verify (1 minute)
1. Check green success notice at top
2. Run new scan on same plugin
3. Verify issue is gone

## 🎯 What to Look For

### ✅ Success Indicators
- Link is clickable (not grayed out)
- URL changes when clicked
- Page redirects back
- Green notice appears: "Issue ignored successfully..."
- Database has new entry

### ❌ Failure Indicators
- Nothing happens when clicking
- "Invalid nonce" error
- "Insufficient permissions" error
- No success notice
- Issue still appears after new scan

## 🔍 Quick Debug

### If nothing happens:
```javascript
// Open browser console (F12)
// Check for errors
console.log('Check for JavaScript errors here');
```

### If "Invalid nonce" error:
1. Refresh the page
2. Try clicking again
3. Check if you're logged in

### If issue still appears:
```sql
-- Check database
SELECT * FROM wp_options WHERE option_name = 'wpv_ignore_rules';
```

## 📋 Expected Results

### Before clicking:
```
URL: /wp-admin/plugins.php?page=wp-verifier&tab=verify
Issue visible in results
```

### After clicking:
```
URL: /wp-admin/plugins.php?page=wp-verifier&tab=verify&plugin=X&ignored=1
Green notice at top
Issue still visible (until new scan)
```

### After new scan:
```
Issue no longer in results
Filtered out by ignore rules
```

## 🎬 Screen Recording Checklist

If recording a demo:
1. ✅ Show plugin page loading
2. ✅ Show selecting plugin
3. ✅ Show scan running
4. ✅ Show clicking issue
5. ✅ Show sidebar with "Ignore Code" button
6. ✅ Show clicking button
7. ✅ Show URL changing
8. ✅ Show success notice
9. ✅ Show running new scan
10. ✅ Show issue filtered out

## 🐛 Emergency Debug

If completely broken, add this to wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then check: `wp-content/debug.log`

## 📞 Quick Help

### Problem: Can't find "Ignore Code" button
**Solution:** It's in the sidebar when you click an issue

### Problem: Button is grayed out
**Solution:** Issue is already ignored

### Problem: Nothing in database
**Solution:** Check if redirect happened, verify nonce

### Problem: Issue still shows
**Solution:** Run NEW scan, old results are cached

## 🎓 Understanding the Flow

```
Click → Navigate → Detect → Verify → Save → Redirect → Notice
```

Each step should take < 1 second except the scan itself.

## ✨ Success Criteria

After 5 minutes, you should have:
- ✅ Clicked "Ignore Code" link
- ✅ Seen success notice
- ✅ Verified database entry
- ✅ Confirmed issue filtered in new scan

## 🚀 Next Steps

Once basic test works:
1. Test with different plugins
2. Test with different error codes
3. Test ignoring multiple issues
4. Test with different users
5. Test error cases (invalid nonce, etc.)

## 💡 Pro Tips

1. **Keep browser console open** - Catch errors immediately
2. **Watch the URL** - Should change when clicking
3. **Check database** - Verify rules are saved
4. **Clear cache** - If results seem wrong
5. **Use fresh scan** - Old results don't update

## 📊 Timing Expectations

| Action | Expected Time |
|--------|--------------|
| Page load | 1-2 seconds |
| Scan | 30-120 seconds |
| Click ignore | Instant |
| Redirect | 1 second |
| Notice display | Instant |
| New scan | 30-120 seconds |

## 🎯 One-Line Test

```bash
# If you just want to verify the URL works:
curl "http://localhost/EcoSystem/wp-admin/plugins.php?page=wp-verifier&tab=verify&action=ignore_code&plugin=test/test.php&file=test.php&code=TestCode&_wpnonce=YOUR_NONCE"
```

## 📝 Test Notes Template

```
Date: ___________
Plugin tested: ___________
Issue code: ___________
Click worked: YES / NO
Redirect worked: YES / NO
Notice showed: YES / NO
Database updated: YES / NO
New scan filtered: YES / NO
Overall: PASS / FAIL
Notes: ___________
```

---

**Remember:** The entire flow should feel instant to the user. If anything takes more than 2 seconds (except the scan), something is wrong.
