# WPVerifier: Technical Architecture

## 🔌 Core Components

### 1. Verification Engine
**Purpose**: Scan plugin files and identify issues

**Responsibilities**:
- File scanning and parsing
- Issue detection and categorization
- Severity assessment
- Result compilation

**Key Classes**:
- `WPVerifier_Scanner`: Main scanning orchestrator
- `WPVerifier_File_Analyzer`: Individual file analysis
- `WPVerifier_Issue_Detector`: Issue identification
- `WPVerifier_Result_Compiler`: Result aggregation

### 2. Library Detection System
**Purpose**: Identify and exclude third-party code

**Detection Methods**:
- Directory pattern matching (`vendor/`, `node_modules/`, `lib/`, `libs/`)
- Composer/npm metadata detection
- Common library signatures
- User-defined library paths

**Key Classes**:
- `WPVerifier_Library_Detector`: Auto-detection logic
- `WPVerifier_Library_Registry`: Library tracking
- `WPVerifier_Exclusion_Manager`: Exclusion rules

### 3. Dashboard UI
**Purpose**: Display verification results

**Features**:
- Issue list with filtering
- Severity-based grouping
- Quick-fix suggestions
- Historical tracking
- Export functionality

**Key Classes**:
- `WPVerifier_Admin_Dashboard`: Main dashboard page
- `WPVerifier_Issue_Display`: Issue rendering
- `WPVerifier_Filter_Manager`: Filtering logic

### 4. Integration Layer
**Purpose**: Connect with WordPress and external systems

**Integrations**:
- WordPress admin hooks
- GitHub Actions workflow
- CI/CD pipeline
- WPSeed compatibility

**Key Classes**:
- `WPVerifier_WordPress_Integration`: Admin integration
- `WPVerifier_CLI_Interface`: Command-line support
- `WPVerifier_API`: External API

---

## 📊 Data Structures

### Issue Object
```php
[
    'id' => 'unique_issue_id',
    'file' => '/path/to/file.php',
    'line' => 42,
    'type' => 'security|quality|standards|best_practice',
    'severity' => 'critical|high|medium|low',
    'code' => 'ISSUE_CODE',
    'message' => 'Human-readable message',
    'suggestion' => 'How to fix it',
    'library' => false, // Is this in a library?
]
```

### Verification Result
```php
[
    'plugin' => 'plugin-slug',
    'timestamp' => time(),
    'total_issues' => 42,
    'by_severity' => [
        'critical' => 2,
        'high' => 5,
        'medium' => 15,
        'low' => 20,
    ],
    'by_type' => [
        'security' => 7,
        'quality' => 20,
        'standards' => 10,
        'best_practice' => 5,
    ],
    'issues' => [ /* array of issue objects */ ],
    'libraries_detected' => [ /* array of detected libraries */ ],
]
```

### Library Definition
```php
[
    'name' => 'vendor/package',
    'path' => '/path/to/vendor/package',
    'type' => 'composer|npm|custom',
    'excluded' => true,
]
```

---

## 🔍 Verification Rules

### Security Checks
- SQL injection patterns
- XSS vulnerabilities
- Missing nonces
- Missing capability checks
- Insecure data handling
- Hardcoded credentials

### Code Quality Checks
- Missing PHPDoc comments
- Inconsistent naming conventions
- Complex functions (>50 lines)
- Unused variables
- Dead code
- Performance issues

### Standards Compliance
- WordPress coding standards
- PHP version compatibility
- Deprecated functions
- Missing plugin headers
- Incorrect file structure

### Best Practices
- Asset management (no inline styles/scripts)
- Caching implementation
- Error handling
- Logging patterns
- Configuration management

---

## 🔄 Scanning Workflow

```
1. Initialize Scanner
   ├─ Load plugin metadata
   ├─ Detect libraries
   └─ Load exclusion rules

2. File Discovery
   ├─ Scan directory structure
   ├─ Filter by file type (.php, .js, .css)
   └─ Exclude library files

3. File Analysis
   ├─ Parse file content
   ├─ Run security checks
   ├─ Run quality checks
   ├─ Run standards checks
   └─ Run best practice checks

4. Issue Compilation
   ├─ Aggregate issues
   ├─ Assign severity
   ├─ Generate suggestions
   └─ Create result object

5. Result Storage
   ├─ Save to database
   ├─ Generate report
   └─ Trigger notifications
```

---

## 💾 Database Schema

### Table: `wp_wpverifier_scans`
```sql
CREATE TABLE wp_wpverifier_scans (
    id BIGINT(20) NOT NULL AUTO_INCREMENT,
    plugin_slug VARCHAR(255) NOT NULL,
    scan_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_issues INT(11),
    critical_count INT(11),
    high_count INT(11),
    medium_count INT(11),
    low_count INT(11),
    result_data LONGTEXT, -- JSON
    PRIMARY KEY (id),
    KEY plugin_idx (plugin_slug),
    KEY date_idx (scan_date)
);
```

### Table: `wp_wpverifier_issues`
```sql
CREATE TABLE wp_wpverifier_issues (
    id BIGINT(20) NOT NULL AUTO_INCREMENT,
    scan_id BIGINT(20) NOT NULL,
    file_path VARCHAR(500),
    line_number INT(11),
    issue_type VARCHAR(50),
    severity VARCHAR(20),
    issue_code VARCHAR(100),
    message LONGTEXT,
    suggestion LONGTEXT,
    is_library TINYINT(1),
    PRIMARY KEY (id),
    KEY scan_idx (scan_id),
    KEY severity_idx (severity),
    FOREIGN KEY (scan_id) REFERENCES wp_wpverifier_scans(id)
);
```

### Table: `wp_wpverifier_libraries`
```sql
CREATE TABLE wp_wpverifier_libraries (
    id BIGINT(20) NOT NULL AUTO_INCREMENT,
    plugin_slug VARCHAR(255),
    library_name VARCHAR(255),
    library_path VARCHAR(500),
    library_type VARCHAR(50),
    excluded TINYINT(1),
    PRIMARY KEY (id),
    UNIQUE KEY plugin_lib (plugin_slug, library_path)
);
```

---

## 🛠️ Implementation Approach

### Phase 1: Core Scanner
1. Build file discovery system
2. Implement basic issue detection
3. Create result compilation
4. Test on sample plugins

### Phase 2: Library Detection
1. Implement directory pattern matching
2. Add metadata detection
3. Create exclusion system
4. Test library identification

### Phase 3: Dashboard
1. Create admin page structure
2. Build issue display
3. Add filtering/sorting
4. Implement quick actions

### Phase 4: Integration
1. WordPress admin integration
2. GitHub Actions workflow
3. CLI interface
4. Documentation

---

## 🔗 Integration Points

### WordPress
- Admin menu registration
- Settings page
- Transients for caching
- Admin notices for alerts

### WPSeed
- Uses WPSeed admin structure
- Leverages WPSeed components
- Follows WPSeed patterns

### External Systems
- GitHub Actions workflow file
- CI/CD pipeline integration
- API endpoints for external tools

---

## 📈 Performance Considerations

### Optimization Strategies
- Incremental scanning (only changed files)
- Caching of results
- Background processing for large plugins
- Batch processing for multiple plugins

### Performance Targets
- Scan 1000 files in <30 seconds
- Dashboard load in <2 seconds
- Issue detection in <5 seconds per file

---

## 🔐 Security Considerations

- Sanitize all file paths
- Escape all output
- Validate user input
- Restrict admin access
- Secure result storage
- No sensitive data in logs

---

## 📝 Configuration

### Settings
- Plugins to monitor
- Scan frequency
- Library paths
- Exclusion rules
- Severity thresholds
- Notification preferences

### Transients
- `wpverifier_scan_results_{plugin}`: Latest scan results
- `wpverifier_libraries_{plugin}`: Detected libraries
- `wpverifier_last_scan_{plugin}`: Last scan timestamp
