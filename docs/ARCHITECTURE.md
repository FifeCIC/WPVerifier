# WP Verifier Architecture

## File Structure & Data Separation

The WP Verifier system uses two separate JSON files to maintain clean separation of concerns:

### `.wpv-results.json` - Scan Results Only
**Purpose**: Stores plugin check scan results and metadata
**Location**: Plugin root directory
**Updated**: Every scan execution

```json
{
  "generated_at": "2026-03-04 21:17:15",
  "plugin": "Plugin Name",
  "readiness": {
    "overall": 0,
    "errors": 24,
    "warnings": 299,
    "status": "needs-work"
  },
  "configuration": {
    "wporg_preparation": false,
    "skipped_rules": ["rule1", "rule2"]
  },
  "ignored_paths": [
    {
      "path": "vendor/library",
      "reason": "vendor",
      "added_by": "admin",
      "added_at": "2026-03-02T23:36:06.456Z"
    }
  ],
  "results": {
    "file_path": [
      {
        "issue_id": "W-123abc",
        "message": "Issue description",
        "code": "Rule.Code",
        "severity": 5,
        "type": "WARNING",
        "line": 42,
        "column": 10
      }
    ]
  }
}
```

### `.wpv-verification.json` - Verification Tracking
**Purpose**: Tracks file/function hashes and verification status
**Location**: Plugin root directory  
**Updated**: During scans (hashes) and verification actions (status)

```json
{
  "version": "1.0",
  "plugin": "Plugin Name",
  "created_at": "2026-03-04 21:17:15",
  "file_level": {
    "file_path": {
      "hash": "abc12345",
      "last_verified": "2026-03-04 21:17:15",
      "verified_by": "admin",
      "verification_notes": "Reviewed and approved"
    }
  },
  "function_level": {
    "file_path::function_name": {
      "hash": "def67890",
      "last_verified": "2026-03-04 21:17:15", 
      "verified_by": "admin",
      "verification_notes": "Function logic verified"
    }
  }
}
```

## Data Relationships

- **File Path**: Primary key linking both files
- **Issue ID**: Unique identifier for each scan result
- **Hash**: Change detection mechanism for verification status
- **Verification Status**: Independent of scan results

## Benefits of This Architecture

1. **No Duplication**: File hashes stored once regardless of issue count
2. **Clean Separation**: Scan data vs verification data are logically separate
3. **Efficient Storage**: Hash storage not tied to issue count
4. **Easy Merging**: Join datasets using file paths when needed
5. **Independent Updates**: Scan results and verification status update separately

## Usage Patterns

### Reading Scan Results
```php
$results = json_decode(file_get_contents('.wpv-results.json'), true);
$file_issues = $results['results']['path/to/file.php'];
```

### Reading Verification Status  
```php
$verification = json_decode(file_get_contents('.wpv-verification.json'), true);
$file_status = $verification['file_level']['path/to/file.php'];
```

### Merging Data for Display
```php
// Get both datasets
$results = load_results();
$verification = load_verification();

// Merge for display
foreach ($results['results'] as $file_path => $issues) {
    $file_hash = $verification['file_level'][$file_path]['hash'] ?? null;
    $verified = isset($verification['file_level'][$file_path]['last_verified']);
    // Display logic here
}
```