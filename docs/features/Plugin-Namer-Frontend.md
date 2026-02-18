# Plugin Namer Frontend Implementation Plan

## Overview

The Plugin Namer feature requires two distinct frontend interfaces:
1. **Admin Interface** - Full-featured tool for plugin developers (authenticated users)
2. **Public Interface** - Simplified tool for public access (non-authenticated users)

## Admin Interface (Tools > WP Verifier > Plugin Namer Tab)

### Location
- Integrated as a tab in the main WP Verifier admin page
- Path: `wp-admin/tools.php?page=plugin-check&tab=plugin-namer`
- Requires: `activate_plugins` capability

### Features
- **Name Input & Analysis**
  - Text input field for plugin name
  - Real-time analysis button
  - Loading states during API calls
  
- **Domain Availability Display**
  - Grid showing .com, .net, .org, .io, .dev, .app
  - Visual indicators (available/taken/checking)
  - Cached results indicator
  
- **Conflict Detection Results**
  - WordPress.org exact match warning
  - List of similar plugins with links
  - Author information for conflicts
  
- **SEO Analysis Dashboard**
  - Overall score gauge (0-100)
  - Length analysis (character/word count)
  - Keyword analysis (WP keywords, descriptive terms)
  - Readability score
  - Actionable recommendations list
  
- **Trademark Checker**
  - Risk level indicator (low/medium/high)
  - List of detected conflicts
  - WordPress trademark compliance warnings
  - Guidelines with links to policies
  
- **Saved Names Management**
  - Save current evaluation with notes
  - View all saved names in table
  - Favorite/unfavorite names
  - Delete saved names
  - Side-by-side comparison view
  - Export saved names as JSON

## Public Interface (Shortcode & Widget)

### Access Methods
1. **Shortcode**: `[wpv_plugin_namer]`
2. **Widget**: "WP Verifier Plugin Namer"

### Features (Simplified)
- Name input with basic analysis
- Domain availability (read-only)
- Conflict detection (basic warning)
- SEO score (summary only)
- Trademark warnings (high-risk only)
- No saved names (prompt to login)
- Rate limiting (10 checks per IP per hour)

## Implementation Phases

### Phase 1: Admin Interface - Core Analysis
- [ ] Create Plugin_Namer_Tab.php admin page class
- [ ] Add name input form
- [ ] Implement domain checking display
- [ ] Add conflict detection results
- [ ] Create SEO analysis dashboard
- [ ] Implement trademark checker display

### Phase 2: Admin Interface - Saved Names
- [ ] Create saved names table view
- [ ] Add save/delete functionality
- [ ] Implement favorites system
- [ ] Build comparison interface
- [ ] Add export functionality

### Phase 3: Public Interface - Shortcode
- [ ] Create Public_Plugin_Namer.php class
- [ ] Register shortcode handler
- [ ] Build simplified analysis form
- [ ] Implement rate limiting
- [ ] Create public-facing CSS/JS

### Phase 4: Public Interface - Widget
- [ ] Create Plugin_Namer_Widget.php class
- [ ] Register widget
- [ ] Add widget settings
- [ ] Implement compact display mode

## Technical Requirements

### Admin Interface Files
- `includes/Admin/Plugin_Namer_Tab.php` - Main tab class
- `assets/css/admin-plugin-namer.css` - Admin styles
- `assets/js/admin-plugin-namer.js` - Admin JavaScript

### Public Interface Files
- `includes/Public/Public_Plugin_Namer.php` - Shortcode handler
- `includes/Public/Plugin_Namer_Widget.php` - Widget class
- `assets/css/public-plugin-namer.css` - Public styles
- `assets/js/public-plugin-namer.js` - Public JavaScript

## Security

### Admin
- Capability checks: `activate_plugins`
- Nonce verification on all AJAX
- Input sanitization and output escaping

### Public
- Rate limiting per IP (10/hour)
- Nonce verification
- No database writes
- XSS prevention
