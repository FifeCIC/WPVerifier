# Custom Rulesets Feature

## Overview
The Custom Rulesets feature allows you to create, manage, and enforce custom coding standards specific to your plugin ecosystem. This extends WP Verifier beyond the standard WordPress.org plugin directory requirements.

## Accessing Custom Rulesets

Navigate to **Tools > WP Verifier Rulesets** in your WordPress admin panel.

## Creating a Custom Ruleset

1. Click **Add New Ruleset**
2. Fill in the following fields:
   - **Ruleset Name**: A descriptive name for your ruleset (e.g., "Company Coding Standards")
   - **Description**: Optional description explaining the purpose of this ruleset
   - **Check Categories**: Select which check categories to include:
     - Plugin repo
     - Security
     - Performance
     - Accessibility
     - General
     - And more...

3. Click **Create Ruleset**

## Managing Rulesets

### Editing a Ruleset
- Click **Edit** next to any ruleset in the list
- Modify the name, description, or categories
- Click **Update Ruleset** to save changes

### Deleting a Ruleset
- Click **Delete** next to the ruleset you want to remove
- Confirm the deletion when prompted

## Import/Export Functionality

### Exporting a Ruleset
1. Click **Export** next to the ruleset you want to share
2. A JSON file will be downloaded to your computer
3. Share this file with team members or use it on other installations

### Importing a Ruleset
1. Click **Import Ruleset** button
2. Select a previously exported JSON file
3. Click **Upload**
4. The ruleset will be added to your list

## Using Custom Rulesets

Once created, custom rulesets can be selected when running plugin checks:

1. Go to **Tools > WP Verifier**
2. Select your plugin to check
3. Choose your custom ruleset from the dropdown
4. Run the verification

## Ruleset Structure

Custom rulesets are stored as JSON with the following structure:

```json
{
  "name": "My Custom Ruleset",
  "description": "Enforces company-specific coding standards",
  "categories": [
    "plugin_repo",
    "security",
    "performance"
  ],
  "created": 1234567890,
  "modified": 1234567890
}
```

## Best Practices

### Naming Conventions
- Use clear, descriptive names
- Include the purpose or team name (e.g., "Frontend Team Standards")
- Avoid generic names like "Ruleset 1"

### Category Selection
- Start with essential categories (security, plugin_repo)
- Add performance and accessibility for production plugins
- Use general category for comprehensive checks

### Team Collaboration
- Export rulesets and store in version control
- Document custom ruleset requirements in your team wiki
- Review and update rulesets quarterly

## Use Cases

### 1. Internal Plugin Development
Create a ruleset that enforces your company's specific coding standards beyond WordPress requirements.

### 2. Client Projects
Maintain separate rulesets for different clients with varying requirements.

### 3. Progressive Enhancement
Start with basic checks and gradually add more categories as your team's capabilities grow.

### 4. Specialized Plugins
Create focused rulesets for specific plugin types (e.g., WooCommerce extensions, membership plugins).

## Technical Details

### Storage
- Rulesets are stored in the WordPress options table
- Option name: `wp_verifier_custom_rulesets`
- Format: Serialized PHP array

### Permissions
- Requires `manage_options` capability
- Only administrators can create/edit/delete rulesets

### File Format
- Export format: JSON
- Encoding: UTF-8
- File extension: `.json`

## Troubleshooting

### Import Fails
- Ensure the file is valid JSON
- Check that the file was exported from WP Verifier
- Verify file permissions and upload limits

### Ruleset Not Appearing
- Clear WordPress object cache
- Check that you have administrator permissions
- Verify the ruleset was saved successfully

### Categories Not Working
- Ensure the selected categories are available in your WP Verifier installation
- Update WP Verifier to the latest version
- Check for plugin conflicts

## Future Enhancements

Planned improvements for custom rulesets:

- [ ] Custom check rules (beyond category selection)
- [ ] Severity level customization
- [ ] Automated ruleset application based on plugin type
- [ ] Ruleset templates for common scenarios
- [ ] Bulk operations (apply ruleset to multiple plugins)
- [ ] Ruleset versioning and history
- [ ] Team sharing and collaboration features

## API Reference

### Getting All Rulesets
```php
$rulesets_manager = new \WordPress\Plugin_Check\Admin\Custom_Rulesets();
$all_rulesets = $rulesets_manager->get_rulesets();
```

### Getting a Specific Ruleset
```php
$rulesets_manager = new \WordPress\Plugin_Check\Admin\Custom_Rulesets();
$ruleset = $rulesets_manager->get_ruleset( 'ruleset_1234567890' );
```

### Programmatically Creating a Ruleset
```php
$rulesets = get_option( 'wp_verifier_custom_rulesets', array() );
$rulesets['my_custom_id'] = array(
    'name' => 'My Ruleset',
    'description' => 'Description here',
    'categories' => array( 'security', 'performance' ),
    'created' => time(),
    'modified' => time(),
);
update_option( 'wp_verifier_custom_rulesets', $rulesets );
```

## Support

For issues or questions about custom rulesets:
- Check this documentation first
- Review the main WP Verifier documentation
- Submit issues to the project repository
