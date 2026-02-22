# Ignore Code Button - Simple Link Implementation

## What Changed

The "Ignore Code" button has been converted from an AJAX-based button to a simple link using $_GET parameters.

## Implementation Steps

### 1. JavaScript Changes (wp-verifier-ast.js)
- **Removed**: `addIgnoreRule()` function that used AJAX/fetch
- **Changed**: Button converted to link in `showDetails()` function
- **Link URL**: Builds URL with parameters: `?page=wp-verifier&tab=verify&action=ignore_code&plugin=X&file=Y&code=Z&_wpnonce=N`

### 2. PHP Changes (Admin_Page.php)
- **Added**: `handle_ignore_code_request()` method
- **Hooked**: `admin_init` action to detect and process the request
- **Process**:
  1. Checks for `page=wp-verifier` and `action=ignore_code`
  2. Verifies nonce and permissions
  3. Sanitizes plugin, file, and code parameters
  4. Adds ignore rule to `wpv_ignore_rules` option
  5. Redirects back with `&ignored=1` parameter
- **Added**: Success notice when `&ignored=1` is present

## How It Works

1. User clicks "Ignore Code" link in issue details
2. Browser navigates to URL with GET parameters
3. WordPress loads admin page
4. `admin_init` hook fires
5. `handle_ignore_code_request()` detects the action
6. Ignore rule is saved to database
7. User is redirected back to verify page
8. Success notice is displayed
9. User runs new scan to see filtered results

## Testing Steps

1. Open WP Verifier plugin page
2. Select a plugin and run verification
3. Click on an issue to view details
4. Click "Ignore Code" link
5. Verify redirect happens
6. Check for success notice
7. Run new scan
8. Verify issue is filtered out

## Debug Output

To track each step, add these debug lines:

```php
// In handle_ignore_code_request()
error_log('Step 1: Detected ignore_code action');
error_log('Step 2: Plugin=' . $plugin . ', File=' . $file . ', Code=' . $code);
error_log('Step 3: Ignore rule added');
error_log('Step 4: Redirecting...');
```

## Benefits

- No JavaScript complexity
- No AJAX errors
- Simple to debug
- Works without JavaScript enabled
- Standard WordPress pattern
- Easy to track in browser network tab
